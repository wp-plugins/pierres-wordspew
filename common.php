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
$myval=0;
function where_shout($where, $myBool) {
global $shout_where, $myval;

if($myval==1) return true;
if($myval==2) return false;

$myval=1;
	if($where!="") {
		$where=strtolower($where);
		$shout_where="";
		if(is_home() && strpos($where,"@homepage")!==false) {
			if(strpos($where,"@homepage(rubric)")!==false) $shout_where=__("Homepage",wordspew);
			return true;
		}
		elseif (is_front_page() && strpos($where,"@frontpage")!==false) {
			if(strpos($where,"@frontpage(rubric)")!==false) $shout_where=__("Frontpage",wordspew);
			return true;
			}
		elseif (is_page() && strpos($where,"@page")!==false) {
			$TitrePage=html_entity_decode(single_post_title('',false),ENT_COMPAT,'UTF-8');
			if(strpos($where,"@page[")!==false) {
				$myBoolPage=in_array(strtolower("@page[".$TitrePage."]"), explode(', ',$where));
				if($myBoolPage && $myBool) {
					$myval=2; 
					return true;
				}
				elseif(!in_array(strtolower($TitrePage), explode(', ',$where)) && strpos($where,"@pages")===false) {
					$myval=2;
					return false;
				}
			}
				if(strpos($where,"@pages(linked)")!==false) $shout_where=$TitrePage;
				if(strpos($where,"@pages(rubric)")!==false) $shout_where=__("Pages",wordspew);
			return true;
			}
		elseif ((is_singular() && !is_page()) && strpos($where,"@single")!==false) {
			if(strpos($where,"@single(linked)")!==false) $shout_where=single_post_title('',false);
			if(strpos($where,"@single(rubric)")!==false) $shout_where=__("Single",wordspew);
			return true;
			}
		elseif ((is_archive() && !is_category()) && strpos($where,"@archives")!==false) {
			if(strpos($where,"@archives(rubric)")!==false) $shout_where=__("Archives",wordspew);
			return true;
			}
		elseif (is_category() && strpos($where,"@category")!==false) {
			if(strpos($where,"@category(linked)")!==false) $shout_where=single_cat_title('',false);
			if(strpos($where,"@category(rubric)")!==false) $shout_where=__("Category",wordspew);
			return true;
			}
		else {
			$shout_where=single_post_title('',false);
			$myBoolPage=in_array(strtolower($shout_where), explode(', ',$where));
			if($myBoolPage) return true;
			$myval=2;
			return false;
		}
	}
	return true;
}

function del($id) {
$shout_tb=$_POST['tb'];
$temp="";
	if($_SESSION['isAdmin'.$shout_tb]==true) {
		$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
		mysql_select_db(DB_NAME, $conn);
		if(strpos($_SERVER['HTTP_REFERER'],"wordspew_archive.php")) $temp="archive";
		mysql_query("DELETE FROM ".$shout_tb."liveshoutbox".$temp." WHERE id = ".intval($id), $conn);
	}
}

function shout_get_avatar($email, $size, $position) {
$avatar = '<div class="ps_'.$position.'">';
	if (get_option('show_avatars')) {
		$avatar .= get_avatar($email, $size);
	}
$avatar.='</div>';
return $avatar;
}
?>