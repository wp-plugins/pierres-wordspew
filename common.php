<?php
@session_start();
define('wordspew', 'pierres-wordspew/lang/wordspew');
// The required user level needed to access the admin page for this plugin
//Initialisation of the variable...
$jal_admin_user_level = 10;

if ( !function_exists('current_user_can') ) :
	function current_user_can() { return 0; }
endif;

if(function_exists('load_plugin_textdomain')) load_plugin_textdomain(wordspew);

if (!function_exists('__')) {
	function __($text, $domain = 'default') {
		return $text;
	}
}

// Time Since function courtesy 
// http://blog.natbat.co.uk/archive/2003/Jun/14/jal_time_since
// Works out the time since the entry post, takes a an argument in unix time (seconds)
function jal_time_since($original) {
	if(!isset($_SESSION['Year'])) {
		$_SESSION['Year'] 	= __('year',wordspew); $_SESSION['years']		= __('years',wordspew);
		$_SESSION['Month'] 	= __('month',wordspew); $_SESSION['months']		= __('months',wordspew);
		$_SESSION['Week'] 	= __('week',wordspew); $_SESSION['weeks'] 		= __('weeks',wordspew);
		$_SESSION['Day'] 	= __('day',wordspew); $_SESSION['days'] 		= __('days',wordspew);
		$_SESSION['Hour'] 	= __('hour',wordspew); $_SESSION['hours'] 		= __('hours',wordspew);
		$_SESSION['Minute'] = __('minute',wordspew); $_SESSION['minutes'] 	= __('minutes',wordspew);
	}

    // array of time period chunks
    $chunks = array(
        array(60 * 60 * 24 * 365 , $_SESSION['Year'],$_SESSION['years']),
        array(60 * 60 * 24 * 30 , $_SESSION['Month'],$_SESSION['months']),
        array(60 * 60 * 24 * 7, $_SESSION['Week'],$_SESSION['weeks']),
        array(60 * 60 * 24 , $_SESSION['Day'],$_SESSION['days']),
        array(60 * 60 , $_SESSION['Hour'],$_SESSION['hours']),
        array(60 , $_SESSION['Minute'],$_SESSION['minutes']),
    );
    $original = $original - 10; // Shaves a second, eliminates a bug where $time and $original match.
    $today = time(); /* Current unix time  */
    $since = $today - $original;
    
    // $j saves performing the count function each time around the loop
    for ($i = 0, $j = count($chunks); $i < $j; $i++) {
        $seconds = $chunks[$i][0];
        $name = $chunks[$i][1];
		$name_s = $chunks[$i][2];
        // finding the biggest chunk (if the chunk fits, break)
        if (($count = floor($since / $seconds)) != 0) {
            break;
        }
    }

	$print = $count ." ".pluralize($count,$name,$name_s);

    if ($i + 1 < $j) {
        // now getting the second item
        $seconds2 = $chunks[$i + 1][0];
        $name2 = $chunks[$i + 1][1];
		$name2_s= $chunks[$i + 1][2];

        // add second item if it's greater than 0
        if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) {
			$print .= ", " .$count2." ".pluralize($count2,$name2,$name2_s);
        }
    }
    return $print;
}

if(!function_exists('pluralize')) :
	function pluralize($count, $singular, $plural = false) {
	if (!$plural) $plural = $singular . 's';
	return ($count < 2 ? $singular : $plural) ;
	}
endif;

function verifyName($name) {
   $loggedUsers = array();
   $loggedUsers = (!is_array($_SESSION['LoggedUsers'])) ? explode(',',$_SESSION['LoggedUsers']) : $_SESSION['LoggedUsers'];
   return in_array(strtolower($name), array_map('strtolower', $loggedUsers));
}

function del($id) {
global $jal_table_prefix;
$temp="";
	if($_SESSION['isAdmin']==true) {
		$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
		mysql_select_db(DB_NAME, $conn);
		if(strpos($_SERVER['HTTP_REFERER'],"wordspew_archive.php")) $temp="archive";
		mysql_query("DELETE FROM ".$jal_table_prefix."liveshoutbox".$temp." WHERE id = ".intval($id), $conn);
	}
}

function shout_get_avatar($email, $size, $position) {
global $wp_version;
$avatar = '<div class="ps_'.$position.'">';
	if (floatval($wp_version) < '2.5') {
		$avatar .= get_shout_avatar($email, $size);
	}
	else {
		if (get_option('show_avatars')) {
			$avatar .= get_avatar($email, $size);
		}
	}
$avatar.='</div>';
	return $avatar;
}
function get_shout_avatar($email, $size) {
	$default = 'http://www.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536?s='.$size;
	$avatar = '<img src="http://www.gravatar.com/avatar.php?gravatar_id=';
	$avatar.= md5(strtolower($email)).'&amp;default='.urlencode($default).'&amp;size='.$size.'" alt=""/>';
	return $avatar;
}
?>