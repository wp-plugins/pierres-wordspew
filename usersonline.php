<?php

function jal_get_IP() {
	if (empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		$ip_address = $_SERVER["REMOTE_ADDR"];
	} else {
		$ip_address = $_SERVER["HTTP_X_FORWARDED_FOR"];
	}
	if(strpos($ip_address, ',') !== false) {
		$ip_address = explode(',', $ip_address);
		$ip_address = $ip_address[0];
	}
	return $ip_address;
}

function jal_get_useronline_engine($usertimeout = 60) {
global $shout_tb;

	$tableuseronline = $shout_tb.'liveshoutbox_useronline';
	$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	mysql_select_db(DB_NAME, $conn);
	@mysql_query("SET CHARACTER SET 'utf8'", $conn);
	@mysql_query("SET NAMES utf8", $conn);

	// Search Bots
	$bots = array('Google' => 'googlebot', 'Bing' => 'msnbot', 'Alex' => 'ia_archiver', 'Lycos' => 'lycos', 'Ask Jeeves' => 'askjeeves', 'Altavista' => 'scooter', 'AllTheWeb' => 'fast-webcrawler', 'Inktomi' => 'slurp@inktomi', 'Turnitin.com' => 'turnitinbot');

	// Useronline Settings
	$timeoutseconds = $usertimeout;
	$timestamp = time();
	$timeout = $timestamp-$timeoutseconds;

	// Check Members
	if(isset($_COOKIE['jalUserName']) && (strtolower(substr($_COOKIE['jalUserName'],0,4)) != strtolower(substr(trim($_SESSION['guest']),0,4)))) {
		$memberonline = mysql_real_escape_string(str_replace("\'", "'", $_COOKIE['jalUserName']));
		$where = "WHERE username='".$memberonline."'";
	} else { // guestify the user
		$memberonline = 'guest';
		$where = "WHERE username='".$memberonline."' AND ip='".jal_get_IP()."'";
	}
	// Check For Bot
	foreach ($bots as $name => $lookfor) {
		if (stristr($_SERVER['HTTP_USER_AGENT'], $lookfor) !== false) {
			$memberonline = mysql_real_escape_string($name);
			$where = "WHERE ip='".jal_get_IP()."'";
		} 
	}

	$visitinguri = $_SERVER['REQUEST_URI'];
	if (str_replace("/wordspew.php","",$_SERVER['REQUEST_URI']) != $_SERVER['REQUEST_URI']) $visitinguri = null;

	mysql_query("LOCK TABLES $tableuseronline WRITE", $conn);	

	if(!in_array(strtolower($memberonline), $_SESSION['HideUsers'.$shout_tb])) {
		$sql="UPDATE $tableuseronline SET timestamp = '$timestamp', ip = '".jal_get_IP()."' $where";
		mysql_query($sql, $conn);
	}
	// If No User Insert It
	if (mysql_affected_rows($conn) == 0) {
		if(!in_array(strtolower($memberonline), $_SESSION['HideUsers'.$shout_tb])) {
			$sql="INSERT INTO $tableuseronline VALUES ('$timestamp', '$memberonline', '".jal_get_IP()."', '', '/')";
			mysql_query($sql,$conn);
		}
	}
		$sql="DELETE FROM $tableuseronline WHERE timestamp < $timeout";
		mysql_query($sql,$conn);

	mysql_query("UNLOCK TABLES", $conn);

	$result = mysql_query("SELECT username FROM $tableuseronline",$conn);

	$useronline = array();
	while($element = mysql_fetch_array($result)) $useronline[] = $element["username"];

	$detected_bots = array();
	$registered_users = array();
	$guests = 0;

	foreach ($useronline as $element) {
		if (array_key_exists($element,$bots)) $detected_bots[] = $element;
		elseif ($element == "guest") $guests = $guests + 1;
		else {
			if(!in_array(strtolower($element), $_SESSION['HideUsers'.$shout_tb]))
				$registered_users[] = $element;
		}
	}

	if (!defined("DB_CHARSET")) {
		@mysql_query("SET CHARACTER SET 'latin1'", $conn);
		@mysql_query("SET NAMES latin1", $conn);
	}
	return array("num_guests"=>$guests,"bots"=>$detected_bots,"users"=>$registered_users);
}

function jal_implode_human($glue,$lastglue,$array) {
	if (count($array) == 0) return ""; // only one element
	if (count($array) == 1) return implode("",$array); // only one element
	if (count($array) == 2) return implode($lastglue,$array); // only one element

	$last_element = array_pop($array);
	$finalstring = implode($glue,$array);
	$finalstring .= $lastglue . $last_element;

	return $finalstring;
}

function jal_get_useronline_extended($usertimeout = 60) {
global $shout_tb;
if($_SESSION['Show_Users']==1) {
	if(!isset($_SESSION['guest'])) {
		$_SESSION['NoOne']=__('No one online.',wordspew);
		$_SESSION['guest']=" " . __('guest',wordspew);
		$_SESSION['guests']=" " . __('guests',wordspew);
		$_SESSION['glue']= " " . __('and',wordspew) . " ";
		$_SESSION['bot'] = " " . __('is crawling the site.',wordspew);
		$_SESSION['bots'] = " " . __('are crawling the site.',wordspew);
		$_SESSION['online'] = " " . __('is online.',wordspew);
		$_SESSION['onlines'] = " " . __('are online.',wordspew);
	}

	$array = jal_get_useronline_engine($usertimeout);
	$u = $array["users"];
	$g = $array["num_guests"];
	$b = $array["bots"];

	/* desired verbiage: */
	/* "Pierre, Framboise and 2 guests online.  Google is crawling the site." */
	/* "Pierre, Framboise and 1 guest online.  Google, Inktomi are crawling the site." */

	/* thus we get an array with nicknames and a string describing the number of guests */
	$tobeimploded = $u;
	if($g == 0) { /* do not do anything */ }
	else $tobeimploded[]= $g . pluralize($g,$_SESSION['guest'],$_SESSION['guests']);
	
	if ($g + count($u) + count($b) == 0) { return $_SESSION['NoOne']; } // no one's here
	$users_online = jal_implode_human(", ",$_SESSION['glue'],$tobeimploded);
	if($g > 0 || count($u) > 0) $users_online .= pluralize($g + count($u),$_SESSION['online'],$_SESSION['onlines']);;

	if (count($b)) {
		$bots_online = jal_implode_human(", ",$_SESSION['glue'],$b);
		$bots_online .=pluralize(count($b),$_SESSION['bot'],$_SESSION['bots']);
	}
	else {
		$bots_online = "";
	}
	return $users_online . " " . $bots_online;
}
else return "";
}
?>