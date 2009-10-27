<?php
include_once ('common.php');
include_once ('usersonline.php');

define('split',16);

if (!isset($table_prefix)) {
	$html = implode('', file("../../../wp-config.php"));
	$html = str_replace ("require_once", "// ", $html);
	$html = str_replace ("<?php", "", $html);
	eval($html);
}
$jal_table_prefix = $table_prefix;

// Register globals - Thanks Karan et Etienne
$jal_lastID    = isset($_GET['jal_lastID']) ? $_GET['jal_lastID'] : "";
$jal_user_name = isset($_POST['n']) ? $_POST['n'] : "";
$jal_user_url  = isset($_POST['u']) ? $_POST['u'] : "";
$jal_user_text = isset($_POST['c']) ? $_POST['c'] : "";
$jal_user_calc = isset($_POST['shoutboxOp']) ? $_POST['shoutboxOp'] : "-2";
$jal_user_Control=isset($_POST['shoutboxControl']) ? $_POST['shoutboxControl'] : "-3";
$jalGetChat    = isset($_GET['jalGetChat']) ? $_GET['jalGetChat'] : "";
$jalSendChat   = isset($_GET['jalSendChat']) ? $_GET['jalSendChat'] : "";
$mode=isset($_POST['mode']) ? $_POST['mode'] : "";
$delID=isset($_POST['id']) ? $_POST['id'] : "";

if (isset($_POST['shout_cat']))
	$shout_cat=$_POST['shout_cat'];
elseif (isset($_GET['shout_cat']))
	$shout_cat=$_GET['shout_cat'];
else
	$shout_cat="";

// function to print the external javascript and css links
function jal_add_to_head () {
global $jal_version, $jal_table_prefix, $jal_admin_user_level, $user_ID, $user_email, $user_level, $wpdb, $show, $size, $position, $wp_version, $shout_opt, $user_identity, $user_nickname, $theuser_nickname;

	$jal_admin_user_level = (get_option('shoutbox_admin_level')!="") ? get_option('shoutbox_admin_level') : 10;
	$shout_opt = get_option('shoutbox_options');
	if(where_shout($shout_opt['where'],1)) {
		$show_to_level=$shout_opt['level_for_shoutbox'];
		$user_level=isset($user_level) ? $user_level : -1;
		$theuser_nickname=(round($wp_version)>=2)? $user_identity : $user_nickname;
		$current=($show_to_level==-1) ? 1 : current_user_can('level_'.$show_to_level);

		if ($user_level >= $show_to_level || $current==1) {
			$jal_wp_url = get_bloginfo('wpurl') . "/";
			$UseRSS=$shout_opt['use_rss'];
			$dateCSS=(filemtime(dirname(__FILE__)."/css.php")+$shout_opt['cssDate']);
			$dateJS=filemtime(dirname(__FILE__)."/ajax_shout.php");
			$ShowRSS="";
			if ($UseRSS=='1') 
			$ShowRSS='<link rel="alternate" type="application/rss+xml" title="'. __('Wordspew-RSS-Feed for:', wordspew). ' '
			. get_bloginfo('name').'" href="'.$jal_wp_url.'wp-content/plugins/pierres-wordspew/wordspew-rss.php" />'."\n";

			$show=$shout_opt['show_avatar'];
			$size=$shout_opt['avatar_size'];
			$position=$shout_opt['avatar_position'];
echo '
<!-- Added By Wordspew Plugin, modified by Pierre, version '.$jal_version.' -->'."\n"
.$ShowRSS.
'<link rel="stylesheet" href="'.$jal_wp_url.'wp-content/plugins/pierres-wordspew/css.php?dt='.$dateCSS.'" type="text/css" />
<script type="text/javascript">
//<![CDATA[
var Old_Sname;
function trim(s) {
return s.replace(/^( | )+/, \'\').replace(/( | )+$/, \'\');
}
';
$isAdmin=($user_level >= $jal_admin_user_level || current_user_can('level_'.$jal_admin_user_level)==1) ? "true" : "false";
$the_nickname=isset($theuser_nickname) ? $theuser_nickname : str_replace("\'", "'", $_COOKIE['jalUserName']);

echo '
var show_avatar='.$show.', avatar_position="'.$position.'", avatar_size='.$size.', isAdmin='.$isAdmin.';
var var_XHTML='.intval($shout_opt['xhtml']).', show_smiley='.$shout_opt['show_smiley'].', shout_user="'.$the_nickname.'";
var jal_org_timeout='.$shout_opt['update_seconds'].', fade_length='.$shout_opt['fade_length'].', fade_from="'.$shout_opt['fade_from'].'", fade_to="'.$shout_opt['fade_to'].'";

function CheckSpam(theText,theURL) {
theMsg=document.getElementById(\'chatbarText\').value;
theMsg=theMsg.toLowerCase();
count_http=theMsg.split("http").length;
var limit=2;
if((document.getElementById(\'shoutboxU\').value).length>7) {
	if(document.getElementById(\'shoutboxU\').style.display!="none") {
		limit++;
		count_http++;
	}
}
if(count_http>limit) {
	alert("'. __('Sorry, but you can post only one url by message...',wordspew) .'");
	return false;
}
theText+=\' \'+theURL;';
	$spam=get_option('moderation_keys');
	$_SESSION['badwords']=$spam;

	if($spam!="") {
		$spam = str_replace("'", "\'", $spam);
		$spam = str_replace("\r\n", "','", $spam);
		$spam="'".strtolower($spam)."'";
		}
echo '
var spam = ['. str_replace(",''", "", $spam) .'];
TextToScan=theText.toLowerCase();
for (var i = 0; i < spam.length; i++) {
	if(TextToScan.indexOf(spam[i])!=-1) {
		alert("'. __('No, sorry you used a banned word!',wordspew) .'\n-> "+spam[i].toUpperCase());
		return false;
		break;
	}
}
return true;
	}
//]]>
</script>
<script type="text/javascript" src="'.$jal_wp_url.'wp-content/plugins/pierres-wordspew/fade.php"></script>
<script type="text/javascript" src="'.$jal_wp_url.'wp-content/plugins/pierres-wordspew/ajax_shout.php?dt='.$dateJS.'"></script>
';
			$users = $shout_opt['hidden_users'];
			$users = str_replace(", ", ",", $users);
			$UsersToHide = stripslashes($users);

			$_SESSION['HideUsers']=explode(",",strtolower($UsersToHide));
			$_SESSION['CurrentUser']=$user_email;
			$_SESSION['CookieHash']=COOKIEHASH;
			$_SESSION['LoggedMsg']=__('No, sorry you used the name of a registered user! You have to change it please.',wordspew);

			if(!isset($_SESSION['LoggedUsers'])) {
				$column = (floatval($wp_version) > '1.5') ? "display_name" : "user_nickname";
				$LoggedUsers = $wpdb->get_col("SELECT ".$column." FROM ".$jal_table_prefix."users");
				$_SESSION['LoggedUsers']=$LoggedUsers;
			}
		}
	}
}

// Never cache this page
if ($jalGetChat == "yes" || $jalSendChat == "yes") {
	header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
	header( "Last-Modified: ".gmdate( "D, d M Y H:i:s" )."GMT" );
	header( "Cache-Control: no-cache, must-revalidate" );
	header( "Pragma: no-cache" );
	header("Content-Type: text/html; charset=utf-8");
	//if the request does not provide the id of the last know message the id is set to 0
	if (!$jal_lastID) $jal_lastID = 0;
}

// retrieves all messages with an id greater than $jal_lastID
if ($jalGetChat == "yes") {
	jal_getData($jal_lastID, $shout_cat);
}

// Where the shoutbox receives information
function jal_getData ($jal_lastID, $cat="") {
global $jal_table_prefix;

$who=($_SESSION['Show_Users']==0) ? "" : jal_get_useronline_extended();

if(isset($_SESSION['spam_msg'])) {
	$loop =$jal_lastID."---Info---".$_SESSION['spam_msg'];
	unset($_SESSION['spam_msg']);
}
else {
	$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	mysql_select_db(DB_NAME, $conn);
	@mysql_query("SET CHARACTER SET 'utf8'", $conn);
	@mysql_query("SET NAMES utf8", $conn);
	$sql = "SELECT * FROM ".$jal_table_prefix."liveshoutbox WHERE cat='".mysql_real_escape_string($cat)."' AND id > ".$jal_lastID;
	$sql.=" ORDER BY id DESC";
	$results = mysql_query($sql, $conn);
	$loop = "";
$first=0;
	while ($row = mysql_fetch_array($results)) {
		$id   = $row[0];
		$time = jal_time_since($row[1]); if($first==0) $_SESSION['Chrono']=$row[1];
		$name = $row[2];
		$text = $row[3];
		$url  = $row[4];
		$email= ($row[6]!="") ? md5($row[6]) : "";
		$ip = isset($_SESSION['isAdmin']) ? $row[5] :" ";
		if(verifyName($name))
			$user=1;
		else
			$user=0;
		// append the new id's to the beginning of $loop --- is being used to separate the fields in the output
		$loop = $id."---".stripslashes($name)."---".stripslashes($text)."---".
		stripslashes($url)."---".$user."---".$email."---".$time."---".$ip."---".$loop;
		$first=1;
	}
}
echo jal_time_since($_SESSION['Chrono'])."---".$who."\n".$loop;
}

function jal_special_chars ($s) {
	$s = htmlspecialchars($s, ENT_NOQUOTES,'UTF-8');
	$s = str_replace("\n"," ",$s);
	return str_replace("---","&minus;-&minus;",$s);
}

function check_ip_address($from, $checkip) {
global $spam_msg;

	$checkip=trim($checkip);
	if(strpos($checkip,"*") || strpos($checkip,"/")) {
		$checkip =str_replace("*", "([0-9]{1,3})", $checkip);
		if(strpos($checkip,"/")) {
			$ar=explode(".",$checkip);
			for($i=0; $i<@count($ar); $i++) {
				$ar2=explode("/",$ar[$i]);
				if(@count($ar2)==2) {
					$ip="(";
					for($j=intval($ar2[0]); $j<intval($ar2[1]);$j++) {
						$ip.=$j."|";
					}
					$ip.=$ar2[1].")";
					$ar[$i]=eregi_replace("([0-9]{1,3})/([0-9]{1,3})", $ip, $ar[$i]);
				}
			}
			$checkip =$ar[0].".".$ar[1].".".$ar[2].".".$ar[3];
		}
		if (eregi($checkip, $from))	return false;
	}
	elseif($from==$checkip) return false;

	return true;
}

function CheckSpam($theText,$TheURL) {
global $spam_msg, $jal_table_prefix, $ip;

$count_http=substr_count($theText,"http");
if($count_http>1) {
	$spam_msg=$_SESSION['HTTPLimit'];
	return false;
}
$count_content_type=substr_count($theText,"content-type");
if($count_content_type>=1) {
	$spam_msg=$_SESSION['DLSpam'];
	return false;
}

$theText.=$TheURL;
$ip = $_SERVER['REMOTE_ADDR'];
$spam=$_SESSION['badwords'];
$spam=explode("\r\n",strtolower($spam));
if($spam[0]!="") {
	for($i=0;$i<@count($spam);$i++) {
		$str=$spam[$i];
		if (strlen($str)>8 && intval($str)) {
			if(!check_ip_address($ip, $str)) {
				$spam_msg=$_SESSION['IPLogged'];
				return false;
				break;
			}
		}
		$pos=strpos($theText,$str);
		if(is_int($pos)) {
			$spam_msg=$_SESSION['DLSpam'];
			return false;
			break;
		}
	}
}
return true;
}
//////////////////////////////////////////////////////
// Functions Below are for submitting comments to the database
//////////////////////////////////////////////////////
// When user submits and javascript fails
if (isset($_POST['shout_no_js'])) {
	$myURL = isset($_POST['shoutboxU']) ? $_POST['shoutboxU'] : "";
	if ($_POST['shoutboxname'] != '' && $_POST['chatbarText'] != '')
		jal_addData($_POST['shoutboxname'], $_POST['chatbarText'], $myURL);
	else echo "You must have a name and a comment...";
}

//only if a name and a message have been provides the information is added to the db
if ($jal_user_name != '' && $jal_user_text != '' && $jalSendChat == "yes") {
	jal_addData($jal_user_name,$jal_user_text,$jal_user_url); //adds new data to the database
	echo "0";
}
if ($delID!= '' && $mode=="del") {
	del($delID); //delete data from the database
	echo "0";
}

function mySplit ($captures){
	// si url ou email, on passe...
	if(preg_match('#^(?:(?:http|ftp)s?://|[-_a-z0-9]+(?:\.[-_a-z0-9]+)*@[-a-z0-9]+(?:\.[-a-z0-9]+)*\.[a-z]{2,6})#i',$captures[0])) {
		$return = $captures[0];
	}
	else {
		$splited = preg_replace("/([^\s]{".split."})/iu","$1 ",$captures[0]);
		$return = trim($splited);
	}
	return $return;
}

function jal_addData($jal_user_name,$jal_user_text,$jal_user_url) {
global $spam_msg, $jal_table_prefix, $jal_user_val, $jal_user_calc, $jal_user_Control, $ip, $shout_cat;

	//if the BadCalc variable is not set then it's a bot (direct access to wordspew)
	if(!isset($_SESSION['BadCalc'])) {
		AddSpam("I DON'T LIKE SPAM !!!");
		exit;
	}

	$SearchText=strtolower(trim($jal_user_text));
	$SearchURL=strtolower(trim($jal_user_url));
	//replacement of non-breaking spaces...
	$SearchName=str_replace(" "," ",$jal_user_name);
	$SearchName=trim($SearchName);
	$SearchName=strtolower($SearchName);
	$myBolean="";

	if($SearchURL == "http://") $SearchURL="";

	if($SearchName==$SearchText || isset($_POST['shoutboxurl'])) {
		AddSpam($_SESSION['DLSpam']);
		exit;
	}

	$hashtext = $_SESSION['hashtext'];
	$jal_user_calc=md5($jal_user_calc.$hashtext);
	if($jal_user_calc!=$jal_user_Control) {
		AddSpam($_SESSION['BadCalc']);
		exit;
	}

	if(!isset($_SESSION['Logged']) && (verifyName($SearchName) && $SearchName!=$_COOKIE['jalUser_'.$_SESSION['CookieHash']])) {
		AddSpam($_SESSION['LoggedMsg']);
		exit;
	}

	if(CheckSpam($SearchText.' '.$SearchName, $SearchURL)) {
		setcookie("jalUserName",$jal_user_name,time()+60*60*24*30*3,'/');
		setcookie("jalCombo",$shout_cat,time()+60*60*24*30,'/');
		//the message is cut of after 500 letters
		$jal_user_text = trim(substr($jal_user_text,0,500));

		// mask to catch string longer than $split car.
		$pattern = '#[^ ]{'.split.',}#u';
		$jal_user_text = preg_replace_callback($pattern, 'mySplit', $jal_user_text);
		$jal_user_text=jal_special_chars($jal_user_text);
		$jal_user_url = ($jal_user_url == "http://") ? "" : jal_special_chars($jal_user_url);

		$email="";
		if($_SESSION['CurrentUser']!="") {
			$email=$_SESSION['CurrentUser'];
			//keep user informations for later use (once disconnected)
			setcookie("jalEmail_".$_SESSION['CookieHash'],strtolower($email),time()+60*60*24*30,'/');
			setcookie("jalUser_".$_SESSION['CookieHash'],strtolower($jal_user_name),time()+60*60*24*30,'/');
		}
		else {
			if (strpos($jal_user_url,"@")!=false) {
				$email=$jal_user_url;
				$jal_user_url ="mailto:".$jal_user_url;
			}
			else {
				if (isset($_COOKIE['jalEmail_'.$_SESSION['CookieHash']])) $email=$_COOKIE['jalEmail_'.$_SESSION['CookieHash']];
				elseif (isset($_COOKIE['comment_author_email_'.$_SESSION['CookieHash']])) $email=$_COOKIE['comment_author_email_'.$_SESSION['CookieHash']];
			}
		}

		$jal_user_name = substr(trim($jal_user_name), 0,18);
		$jal_user_name=jal_special_chars($jal_user_name);

		$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
		mysql_select_db(DB_NAME, $conn);
		@mysql_query("SET CHARACTER SET 'utf8'", $conn);
		@mysql_query("SET NAMES utf8", $conn);
		if($jal_user_url!="") {
			$jal_user_url=str_replace(" ", "", $jal_user_url);
			setcookie("jalUrl",str_replace("mailto:","",$jal_user_url),time()+60*60*24*30*3,'/');
			if($_SESSION['useURL']=="") $myBolean="false";
		}
		if (substr($jal_user_url,0,3)=="www") $jal_user_url ="http://".$jal_user_url;

		if($myBolean=="") {
			if($_SESSION['useCaptcha']=="1") setcookie("jalCaptcha","Ok",time()+60*60*24*30*3,'/');
			$SQL="INSERT INTO ".$jal_table_prefix."liveshoutbox (time,name,text,url,ipaddr,email,cat) VALUES ('".time()."','";
			$SQL.=mysql_real_escape_string($jal_user_name)."','".mysql_real_escape_string($jal_user_text)."','";
			$SQL.=mysql_real_escape_string($jal_user_url)."', '".mysql_real_escape_string($ip)."','";
			$SQL.=mysql_real_escape_string(strtolower($email))."','".mysql_real_escape_string($shout_cat)."')";
			mysql_query($SQL, $conn);
			jal_deleteOld($shout_cat); //some database maintenance
			//take them right back where they left off
			header('location: '.$_SERVER['HTTP_REFERER']);
			}
		else {
			AddSpam($_SESSION['DLSpam']);
		}
	}
	else AddSpam($spam_msg);
}

function AddSpam($msg) {
global $jal_table_prefix, $jalSendChat;

	$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	mysql_select_db(DB_NAME, $conn);

	$SQL= mysql_query("SELECT option_value FROM ".$jal_table_prefix."options WHERE option_name='shoutbox_spam'");
	$nb= mysql_result($SQL, 0)+1;
	mysql_query("UPDATE ".$jal_table_prefix."options SET option_value='".$nb."' WHERE option_name='shoutbox_spam'",$conn);

	if($jalSendChat=="yes") {
		$_SESSION['spam_msg']= $msg;
		header('location: '.$_SERVER['HTTP_REFERER']);
	}
	else echo $msg;
}

//Maintains the database by deleting past comments
function jal_deleteOld($cat="") {
global $jal_table_prefix;
	header("Content-Type: text/html; charset=utf-8");
	$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	mysql_select_db(DB_NAME, $conn);
	$SQL=mysql_query("SELECT option_value FROM ".$jal_table_prefix."options WHERE option_name = 'shoutbox_nb_comment'");
	$jal_number_of_comments=mysql_result($SQL,0);

	@mysql_query("SET CHARACTER SET 'utf8'");
	@mysql_query("SET NAMES utf8");

	$SQL="SELECT id FROM ".$jal_table_prefix."liveshoutbox WHERE cat='".mysql_real_escape_string($cat)."'";
	$SQL.=" ORDER BY id DESC LIMIT ".$jal_number_of_comments;
	$results = mysql_query($SQL,$conn);

	while ($row = mysql_fetch_array($results)) { $id = $row[0]; }

	if ($id) {
		$SQL="INSERT INTO ".$jal_table_prefix."liveshoutboxarchive (time,name,text,url,ipaddr,email,cat) SELECT ";
		$SQL.="time,name,text,url,ipaddr,email,cat FROM ".$jal_table_prefix."liveshoutbox WHERE cat='".mysql_real_escape_string($cat)."'";
		$SQL.=" AND id < ".$id;
		mysql_query($SQL, $conn);
		$SQL="DELETE FROM ".$jal_table_prefix."liveshoutbox WHERE cat='".mysql_real_escape_string($cat)."' AND id < ".$id;
		mysql_query($SQL, $conn);
	}
}

function sanitize_name($name) {
$bad = array(" ", " ", "'", ".");
$good= array("", "", "", "");
return str_replace($bad, $good, $name);
}

// Prints the html structure for the shoutbox
function jal_get_shoutbox ($cat="",$comboTheme=1) {
global $wpdb, $jal_table_prefix, $user_level, $theuser_nickname, $user_url, $user_ID, $jal_admin_user_level, $show, $size, $position, $shout_opt;

$HiddenCat="";
$show_to_level=$shout_opt['level_for_shoutbox'];
$level_for_archive=$shout_opt['level_for_archive'];
$level_for_theme=$shout_opt['level_for_theme'];
$user_level=isset($user_level) ? $user_level : -1;
$current=($show_to_level==-1) ? 1 : current_user_can('level_'.$show_to_level);
$curthe=($level_for_theme==-1) ? 1 : current_user_can('level_'.$level_for_theme);
$curadmin=current_user_can('level_'.$jal_admin_user_level);
$use_theme=$shout_opt['use_theme'];

if($cat!="") $HiddenCat=$cat;

if ($user_level >= $show_to_level || $current==1) {

	if($cat=="") {
		if(($user_level >= $level_for_theme || $curthe==1) && $use_theme==1)
			$HiddenCat=$cat=str_replace("\\","",$_COOKIE['jalCombo']);
	}

	$XHTML=$shout_opt['xhtml'];
	$Captcha=$shout_opt['use_captcha'];
	$jal_number_of_comments=get_option('shoutbox_nb_comment');
	$Actual_URL=get_bloginfo('wpurl');
	$_SESSION['Show_Users']=$shout_opt['show_user_online'];
	$_SESSION['BadCalc']=__("You should learn to count before use the shoutbox :)",wordspew);
	$_SESSION['DLSpam']=__("I DON'T LIKE SPAM !!!",wordspew);
	$_SESSION['HTTPLimit']=__("Sorry, but you can post only one url by message...",wordspew);
	$_SESSION['IPLogged']=__("Your IP address have been banned from this blog, if you feel this is in error please contact the webmaster.",wordspew);
	$_SESSION['hashtext']=$shout_opt['hash'];
	$_SESSION['useURL']=$shout_opt['use_url'];
	$_SESSION['useCaptcha']=$Captcha; ?>
<div id="wordspew">
	<div id="chatoutput">
	<?php
	@mysql_query("SET CHARACTER SET 'utf8'");
	@mysql_query("SET NAMES utf8");
	$wpdb->hide_errors();

	$SQLCat=html_entity_decode($cat,ENT_COMPAT,'UTF-8');
	$SQLCat=str_replace("'","\'",$SQLCat);
	$SQL="SELECT * FROM ".$jal_table_prefix."liveshoutbox WHERE cat='".mysql_real_escape_string($SQLCat)."'";
	$SQL.=" ORDER BY id DESC LIMIT ".$jal_number_of_comments;
	$results = $wpdb->get_results($SQL);

	// Will only add the last message div if it is looping for the first time
	$jal_first_time = true;
	$registered_only = ($shout_opt['registered_only'] == '1') ? TRUE : FALSE;

	$rand1=mt_rand(0,10);
	$rand2=mt_rand(0,10);
	$total=intval($rand1+$rand2);

	if ($shout_opt['use_sound']==1) {
		$img_sound=($_COOKIE['jalSound']==1 || $_COOKIE['jalSound']=="") ? "sound_1.gif" : "sound_0.gif";
		echo '<img src="'. $Actual_URL .'/wp-content/plugins/pierres-wordspew/img/'.$img_sound.'" alt="" onclick="setSound();" id="JalSound" 
		title="'.__("Click this to turn on/off sound",wordspew).'"/>';
	}
	if($shout_opt['show_spam']==1) {
		$nb = get_option('shoutbox_spam');
		printf(__('<div id="Show_Spam">%s spams blocked</div>',wordspew),$nb);
	}

	// Loops the messages into a list
	foreach( $results as $r ) {
		if ($jal_first_time) {
			$_SESSION['Chrono']=$r->time;
			printf(__('<div id="lastMessage"><span>Last Message</span><br/><div id="responseTime">%s</div>&nbsp;ago</div>',wordspew),jal_time_since($r->time));
		echo '<div id="usersOnline">'.jal_get_useronline_extended().'</div>
		<ul id="outputList">';
		}

		$target="";
		// Add links
		if (strpos($r->text, $Actual_URL)===false && $XHTML==0) $target=' target="_blank"';
		$theLink=__("link",wordspew); $theMail=__("email",wordspew);
		$r->text = preg_replace("`(http|ftp)+(s)?:(//)((\w|\.|\-|_)+)(/)?(\S+)?`i", "<a rel=\"nofollow\" href=\"\\0\"$target>&laquo;$theLink&raquo;</a>", $r->text);
		$r->text = preg_replace("`([-_a-z0-9]+(\.[-_a-z0-9]+)*@[-a-z0-9]+(\.[-a-z0-9]+)*\.[a-z]{2,6})`i","<a href=\"mailto:\\1\">&laquo;$theMail&raquo;</a>", $r->text);
		if ($jal_first_time) $lastID = $r->id;
		$target="";
		if($r->url!="") if (strpos($r->url, $Actual_URL)===false && $XHTML==0) $target=' target="_blank"';
		$url = (empty($r->url)) ? $r->name : '<a rel="nofollow" href="'.$r->url.'"'.$target.'>'.$r->name.'</a>';
		if($jal_first_time && !isset($_COOKIE['jalCaptcha']) && !$user_ID && !$registered_only && $_SESSION['useCaptcha'] == '1') 
			echo '<li><span>'.__("Info",wordspew).' : </span><b>'.__("Please, resolve the addition below before post any new comment...",wordspew).'</b></li>';

		$TheText=$r->text;
		$verif=true;
		if (substr($TheText,0,2)=="@@") {
			$verif=false;
			$PosSpace=strpos($TheText," ");
			$To=substr($TheText,2,$PosSpace-2);
			$Deb=strlen($To)+2;
			$TheText='<span class="InfoUser">'.__("Private message for", wordspew).' '.$To.':</span>'.substr($TheText,$Deb);
			$the_nickname=isset($theuser_nickname) ? $theuser_nickname : str_replace("\'", "'", $_COOKIE['jalUserName']);
			if((strtolower($the_nickname)==strtolower($To)) || (strtolower($the_nickname)==strtolower($r->name)) 
			|| ($user_level >= $jal_admin_user_level || $curadmin==1)) $verif=true;
		}

		if(verifyName($r->name)) {
			$class="jal_user ";
		}
		$delete="";
		if ($user_level >= $jal_admin_user_level || $curadmin==1) {
			$delete.=' <span onclick="deleteComment('.$r->id.')" class="delShout" title="'.__("Delete",wordspew).'">x</span>';
		}
		$avatar="";
		if ($show == '1' && $r->email!="") {
			$avatar=shout_get_avatar($r->email, $size, $position);
		}
		if($verif) {
			echo '<li id="comment-new'.$r->id.'">'.$avatar.'<span title="'.jal_time_since( $r->time ).'" 
			class="'.$class. sanitize_name($r->name).'">'.stripslashes($url).' : </span>'.convert_smilies(" ".stripslashes($TheText)).$delete.'</li>
		';
		}
		$jal_first_time = false;
		$class="";
	}

	if(!$results) {
		printf(__('<div id="lastMessage"><span>Last Message</span><br/><div id="responseTime">%s</div>&nbsp;ago</div>',wordspew),'0 '.__('minute',wordspew));
		echo '
		<div id="usersOnline">'.jal_get_useronline_extended().'</div>
		<ul id="outputList">
		<li>&nbsp;</li>
		';
	}
	$use_url = ($shout_opt['use_url']==1) ? TRUE : FALSE;
	$use_textarea = ($shout_opt['use_textarea']==1) ? TRUE : FALSE;
	
	$combo='<input type="hidden" name="shout_cat" id="shout_cat" value="'.$HiddenCat.'"/>';
	if($use_theme==1 && ($comboTheme==1 || $user_level >= $jal_admin_user_level)) {
		$SQL="SELECT DISTINCT cat FROM ".$jal_table_prefix."liveshoutbox WHERE cat!='' ORDER BY cat";
		$theme = $wpdb->get_results($SQL);
		$wpdb->show_errors();

		if(($user_level >= $level_for_theme || current_user_can('level_'.$level_for_theme)==1)) {
			if($theme || ($user_level >= $jal_admin_user_level || $curadmin==1)) {
				$combo.='<div id="shout_theme" style="display:none;"><b>'.__("Theme:",wordspew).'</b><br/>';
				$combo.='<select name="shout_cat_theme" id="shout_cat_theme" onchange="document.getElementById(\'chatbarText\').focus();" onblur="CleanBox()" 
				onfocus="oldval=this.options[this.selectedIndex].value">
				<option value="">'.__("Miscellaneous",wordspew).'</option>';
				foreach( $theme as $theme_name ) {
					$the_theme=stripslashes($theme_name->cat);
					$selected=($SQLCat==$the_theme || $HiddenCat==$the_theme) ? ' selected="true"' : '';
					$combo.='<option value="'.$the_theme.'"'.$selected.'>'.$the_theme.'</option>';
				}
				if($user_level >= $jal_admin_user_level || $curadmin==1)
					$combo.='<option value="add_custom" style="font-weight:bold">'.__("New theme",wordspew).'</option>';
				$combo.='</select></div>';
			}
		}
	}

	if (!defined("DB_CHARSET")) {
		@mysql_query("SET CHARACTER SET 'latin1'");
		@mysql_query("SET NAMES latin1");
	}
	?>
	</ul>
	</div>
	<div id="chatInput">
<?php
	$hashtext = $_SESSION['hashtext'];

	if (!$registered_only || ($registered_only && $user_ID)) {
	$display_name=($_COOKIE['jalUserName']) ? $_COOKIE['jalUserName'] : __("Guest_",wordspew).rand(0,5000);
	$display_name=str_replace("\'", "'", $display_name);
	?>
	<form id="chatForm" method="post" action="<?php echo $Actual_URL; ?>/wp-content/plugins/pierres-wordspew/wordspew.php">
	<input type="hidden" name="shoutboxControl" id="shoutboxControl" value="<?php echo md5($total.$hashtext); ?>"/>
	<?php

	if ($user_level >= $jal_admin_user_level || $curadmin==1) { // If user is allowed to use the admin page
		$_SESSION['isAdmin']=true;
		echo '<a href="'.$Actual_URL.'/wp-admin/edit.php?page=wordspew_admin" 
		onmouseover="ChangeURL(\'shoutboxAdmin\',\''.$Actual_URL.'/wp-admin/edit.php?page=wordspew_admin\',\'&amp;shout_cat=\')" id="shoutboxAdmin">'. __("Admin",wordspew).'</a>';
	}
	else unset($_SESSION['isAdmin']);
	if ($user_level >= $level_for_archive || current_user_can('level_'.$level_for_archive)==1) {
		echo '<div style="text-align:right;">
		<a href="'.$Actual_URL.'/wp-content/plugins/pierres-wordspew/wordspew_archive.php" 
		onmouseover="ChangeURL(\'shoutboxArchive\',\''.$Actual_URL.'/wp-content/plugins/pierres-wordspew/wordspew_archive.php\',\'?shout_cat=\')" id="shoutboxArchive">'.__("Archive",wordspew).'</a>
		</div>';
	}
	if (!empty($theuser_nickname)) { /* If they are logged in, then print their nickname */
	$_SESSION['Logged']="ok"; ?>
	<input type="hidden" name="shoutboxOp" id="shoutboxOp" value="<?php echo $total; ?>"/>
	<label><?php _e('Name',wordspew); ?>: <em><?php echo $theuser_nickname ?></em></label>
	<input type="hidden" name="shoutboxname" id="shoutboxname" value="<?php echo $theuser_nickname; ?>"/>
	<input type="hidden" name="shoutboxU" id="shoutboxU" value="<?php if($use_url) { echo $user_url; } ?>"/>
	<?php } else {
	unset($_SESSION['Logged']);
	echo "\n"; /* Otherwise allow the user to pick their own name */ ?>

	<?php if ($Captcha==1) { ?>
	<div id="shoutbox_captcha">
	<label><?php _e('Captcha',wordspew); ?>:</label> <select name="shoutboxOp" id="shoutboxOp" 
	onchange="MasqueSelect()" onclick="MasqueSelect()">
	<option value="-3"><?php echo $rand1."+".$rand2."="; ?></option>
	<?php for ($i = 0; $i < 21; $i++) {
	echo '<option value="'.$i.'">'.$i.'</option>';
	}
	echo '</select></div>';
	}
	else { ?>
		<input type="hidden" name="shoutboxOp" id="shoutboxOp" value="<?php echo $total; ?>"/>
	<?php } ?>
	<label for="shoutboxname"><?php _e('Name',wordspew); ?>:</label>
	<input type="text" name="shoutboxname" id="shoutboxname" value="<?php echo $display_name; ?>" onfocus="Old_Sname=this.value;this.value='';"/>
	<label for="shoutboxU"<?php if (!$use_url) echo ' style="display: none"'; ?>><?php _e('URL/Email',wordspew); ?>:</label>
	<input type="text" name="shoutboxU" id="shoutboxU" value="<?php if ($use_url) echo $_COOKIE['jalUrl']; ?>"<?php 
	if (!$use_url) echo ' style="display: none"'; ?>/>
	<?php  } echo "\n"; ?>
	<label for="chatbarText"><?php _e('Message',wordspew) ?>:</label>
	<?php if ($use_textarea) { ?>
	<textarea rows="4" cols="16" name="chatbarText" id="chatbarText" onkeypress="return pressedEnter(this,event);"></textarea>
	<?php } else { ?>
	<input type="text" name="chatbarText" id="chatbarText" onkeypress="return pressedEnter(this,event);"/>
	<?php } ?>
	<input type="hidden" id="jal_lastID" value="<?php echo $lastID + 1; ?>" name="jal_lastID"/>

	<?php echo $combo; ?>

	<input type="hidden" name="shout_no_js" value="true"/>
	<div id="SmileyList"></div>
	<input type="submit" id="submitchat" name="submit" value="<?php _e('Send',wordspew);?>"/>
	</form>
<?php }
else {
	if ($user_level >= $level_for_archive || current_user_can('level_'.$level_for_archive)==1) {
		echo '<div style="text-align:right;">
		<a href="'.$Actual_URL.'/wp-content/plugins/pierres-wordspew/wordspew_archive.php?shout_cat='.$cat.'">'. __("Archive",wordspew).'</a>
		</div>';
	}
?>
	<form id="chatForm" action="">
	<p align="center"><?php _e('You must be a registered user to participate in this chat',wordspew); ?></p>
	<input type="hidden" name="shoutboxOp" id="shoutboxOp" value="<?php echo $total; ?>"/>
	<input type="hidden" id="shoutboxname" value="<?php echo __("Guest_",wordspew).rand(0,5000); ?>"/>
	<input type="hidden" id="shoutboxU"/>
	<input type="hidden" id="chatbarText"/>
	<input type="hidden" id="shout_cat" value="<?php echo $HiddenCat; ?>"/>
	<input type="hidden" id="jal_lastID" value="<?php echo $lastID+1; ?>"/>
	<input type="submit" id="submitchat" name="submit" style="display:none;"/>
	</form>
<?php } ?>
	</div>
</div>
<?php if ($shout_opt['use_sound']==1)
//Thanks to Eric HEUNTHEP -> http://portfolio.neolao.com/ for its cool free mp3 player -> http://flash-mp3-player.net/en/players/js/preview/
	echo '<object id="TheBox" type="application/x-shockwave-flash" data="'. $Actual_URL .'/wp-content/plugins/pierres-wordspew/player.swf" 
	width="1" height="1"><param name="movie" value="'. $Actual_URL .'/wp-content/plugins/pierres-wordspew/player.swf"/><param 
	name="AllowScriptAccess" value="always" /><param name="FlashVars" value="listener=myBox"/></object>
	';
}
}
// Print to the <script> and <link> (for css) to the head of the document
if (function_exists('add_action')) {
	add_action('wp_head', 'jal_add_to_head');
}
?>