<?php
require_once('../../../wp-blog-header.php');
include_once ('common.php');

if (!isset($table_prefix)) {
	$html = implode('', file("../../../wp-config.php"));
	$html = str_replace ("require_once", "// ", $html);
	$html = str_replace ("<?php", "", $html);
	eval($html);
}

$limit=get_option('shoutbox_nb_comment');
$offset = intval((isset($_REQUEST['offset']) && $_REQUEST['offset'] > 0) ? $_REQUEST['offset'] : 0);
$shout_cat=$_GET['shout_cat'];

function jal_get_shoutboxarchive ($cat="") {
global $wpdb, $user_nickname, $user_ID, $user_level, $user_identity, $limit, $offset, $wp_version, $shout_cat;

$jal_admin_user_level = (get_option('shoutbox_admin_level')!="") ? get_option('shoutbox_admin_level') : 10;
$shout_opt = get_option('shoutbox_options');

$cat=($cat!="") ? $cat : $shout_cat;
//get_currentuserinfo(); // Gets logged in user.
$theuser_nickname=$user_nickname;
$ActualVersion=round(get_bloginfo('version'));
if($ActualVersion>=2) $theuser_nickname=$user_identity;
$XHTML=$shout_opt['xhtml'];
$Actual_URL=get_bloginfo('wpurl');
$show_to_level=$shout_opt['level_for_shoutbox']; 
$alt="alternate";
$link="";
$link_cat=($cat!="") ? "&amp;shout_cat=".urlencode(stripslashes($cat)) : "";
$link_cat2=str_replace("&amp;shout_cat=", "?shout_cat=",$link_cat);

$show=$shout_opt['show_avatar'];
$size=$shout_opt['avatar_size'];
$level_for_archive=$shout_opt['level_for_archive'];
$level_for_theme=$shout_opt['level_for_theme'];

$user_level=isset($user_level) ? $user_level : -1;
$Show_IP=true;

$current=($show_to_level==-1) ? 1 : current_user_can('level_'.$show_to_level);
$curarc=($level_for_archive==-1) ? 1 : current_user_can('level_'.$level_for_archive);
$curthe=($level_for_theme==-1) ? 1 : current_user_can('level_'.$level_for_theme);
$curadm=current_user_can('level_'.$jal_admin_user_level);

//if user can see the box, can see archive, can see theme or theme is not set.
if (($user_level >= $show_to_level || $current==1) && ($user_level>=$level_for_archive || $curarc==1) && ($user_level>=$level_for_theme || $cat=="" || $curthe==1)) {

	@mysql_query("SET CHARACTER SET 'utf8'");
	@mysql_query("SET NAMES utf8");

	$dateCSS=(filemtime(dirname(__FILE__)."/css.php")+$shout_opt['cssDate']);

	if(!isset($_SESSION['LoggedUsers'])) {
		$column = (floatval($wp_version) > '1.5') ? "display_name" : "user_nickname";
		$LoggedUsers = $wpdb->get_col("SELECT LOWER(".$column.") FROM ".$wpdb->users);
		$_SESSION['LoggedUsers']=$LoggedUsers;
	}

	$wpdb->hide_errors();
	$SQL="SELECT SQL_CALC_FOUND_ROWS id, time, name, text, url, ipaddr, email FROM ".$_SESSION['tb_prefix']."liveshoutboxarchive ";
	$SQL.="WHERE cat='".mysql_real_escape_string($cat)."' ORDER BY id DESC LIMIT ".$offset.",".$limit;
	$results = $wpdb->get_results($SQL);
	$wpdb->show_errors();

	$result	= mysql_query('SELECT FOUND_ROWS() AS total');
	$data	= mysql_fetch_assoc($result);
	$total=$data['total'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head profile="http://gmpg.org/xfn/11">
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
	<title><?php bloginfo('name'); ?> <?php _e("Archive for the shoutbox",wordspew); ?></title>
	<meta name="generator" content="WordPress <?php bloginfo('version'); ?>" /> <!-- leave this for stats -->
	<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
	<link rel="stylesheet" href="<?php echo $Actual_URL; ?>/wp-content/plugins/pierres-wordspew/css.php?dt=<?php echo $dateCSS;?>" type="text/css" />
	<script type="text/javascript" src="<?php echo $Actual_URL; ?>/wp-content/plugins/pierres-wordspew/fade.php"></script>
	<script type="text/javascript" src="<?php echo $Actual_URL; ?>/wp-content/plugins/pierres-wordspew/ajax_archive.php"></script>
</head>

<body class="shoutbox_archive">

<table width="100%" border="0" id="wordspew">
	<?php
	$colspan=($user_level >= $jal_admin_user_level || $curadm==1) ? 5 : 3;
	if($user_level >= $jal_admin_user_level && !current_user_can('manage_options')) {
		$colspan=4;
		$Show_IP=false;
	}

	echo '<tr>
	<th colspan="'.$colspan.'">
	<div class="goback"><a href="'.$Actual_URL.'"><img 
	src="'.$Actual_URL.'/wp-content/plugins/pierres-wordspew/img/home.png" border="0" width="32" height="32" alt=""
	title="'.__("Click here to go to the Homepage", wordspew).'"/></a></div>
	<h3><a href="wordspew_archive.php'.$link_cat2.'" title="'.__("Click here to refresh the page",wordspew).'">'.__("Shoutbox archive",wordspew);
	if($cat!="") echo ' '.__("Theme:",wordspew).' '. stripslashes($cat);
	echo '</a></h3>
	';
	if($shout_opt['show_spam']=='1') {
		$nb = get_option('shoutbox_spam');
		printf(__('<div id="Show_Spam">%s spams blocked</div>',wordspew),$nb);
	}
	echo '</th>
	</tr>
	<tr class="header">
	<th class="date">'.__("Date/Time",wordspew).'</th>
	<th class="name">'.__("Name",wordspew).'</th>
	<th class="msg">'.__("Message",wordspew).'</th>';
	if($user_level >= $jal_admin_user_level || $curadm==1) {
		$_SESSION['isAdmin'.$_SESSION['tb_prefix']]=true;
		$link='<a href="'.$Actual_URL.'/wp-admin/edit.php?page=wordspew_admin'.$link_cat.'" id="shoutboxAdmin">'. __("Admin",wordspew).'</a>';
		if($Show_IP) {
			echo '<th class="IP">'.__("IP",wordspew).'</th>';
		}
		echo '<td></td>';
	}
	else unset($_SESSION['isAdmin'.$_SESSION['tb_prefix']]);
	echo '
	</tr>				
	';
if($results) {
	foreach( $results as $r ) {
		$alt=($alt==" alternate") ? "" : " alternate";
		$target="";

		if (strpos($r->text, $Actual_URL)===false && $XHTML==0) $target=' target="_blank"';
		$theLink=__("link",wordspew); $theMail=__("email",wordspew);
		$r->text = preg_replace("`(http|ftp)+(s)?:(//)((\w|\.|\-|_)+)(/)?(\S+)?`i", "<a rel=\"nofollow\" href=\"\\0\"$target>&laquo;$theLink&raquo;</a>", $r->text);
		$r->text = preg_replace("`([-_a-z0-9]+(\.[-_a-z0-9]+)*@[-a-z0-9]+(\.[-a-z0-9]+)*\.[a-z]{2,6})`i","<a href=\"mailto:\\1\">&laquo;$theMail&raquo;</a>", $r->text); 

		$target="";
		if($r->url!="") if (strpos($r->url, $Actual_URL)===false && $XHTML==0) $target=' target="_blank"';
		if(!empty($r->url)) {
			if (strpos($r->url, "@")===false) $url ='<a rel="nofollow" href="'.$r->url.'"'.$target.'>'.$r->name.'</a>';
			else {
				if($user_level >= $jal_admin_user_level || $curadm==1) $url=$r->name.' <a href="'.$r->url.'"><img width="16" height="16" 
				src="'.$Actual_URL.'/wp-content/plugins/pierres-wordspew/img/mail.png" alt="'.__("email",wordspew).'"/></a>';
				else $url =$r->name;
			}
		}
		else $url=$r->name;

		if(verifyName($r->name)) {
			$class="jal_user ";
		}

		$TheName=$r->name;
		$TheMail=$r->email;
		$TheText=$r->text;
		if (substr($TheText,0,2)=="@@") {
			$PosSpace=strpos($TheText," ");
			$To=substr($TheText,2,$PosSpace-2);
			$Deb=strlen($To)+2;
			$TheText='<span class="InfoUser">'.__("Private message for", wordspew).' '.$To.':</span>'.substr($TheText,$Deb).'';
			$the_nickname=isset($theuser_nickname) ? $theuser_nickname : str_replace("\'", "'", $_COOKIE['jalUserName']);
			if((strtolower($the_nickname)==strtolower($To)) || (strtolower($the_nickname)==strtolower($TheName)) 
			|| ($user_level >= $jal_admin_user_level || $curadm==1)) $verif=true;
			else {
				$TheName="";
				$url=__("Private", wordspew);
				$TheMail="";
				$TheText='<span class="InfoUser">'.__("Private message", wordspew).'.</span>';
				$class="";
			}
		}

		$delete="";
		if ($user_level >= $jal_admin_user_level || $curadm==1) {
			$delete.=' <span onclick="deleteComment('.$r->id.',  '.$offset.', '.$limit.')" class="delShout" title="'.__("Delete",wordspew).'">x</span>';
		}
		$avatar="";
		if ($show == '1' && $TheMail!="") {
			$avatar=shout_get_avatar($TheMail, $size, "left");
		}
		setlocale(LC_ALL,WPLANG.".UTF8");
echo '<tr class="bg'.$alt.'" id="comment-new'.$r->id.'"><td class="date">'.strftime("%A %d %B %Y", $r->time).' <br/>'
.strftime("%H:%I", $r->time).'</td>
<td class="name">'.$avatar.'<span class="'.$class. sanitize_name($TheName).'">'.stripslashes($url).'</span></td>
<td class="msg">'.convert_smilies(stripslashes($TheText)).'</td>';
		if($user_level >= $jal_admin_user_level || $curadm==1) {
			if($Show_IP) {
echo '
<td class="IP"><a href="http://whois.domaintools.com/'.$r->ipaddr.'" target="_blank" title="Whois">'.$r->ipaddr.'</a></td>';
			}
			echo '<td>'.$delete.'</td>';
		}
		echo '
</tr>
		';
		$class="";
	}
}
	if (!defined("DB_CHARSET")) {
		@mysql_query("SET CHARACTER SET 'latin1'");
		@mysql_query("SET NAMES latin1");
	}
	?>
	<tr>
		<td colspan="<?php echo $colspan; ?>">
		<div align="center"><?php echo '<span id="count">'.$total.'</span> '.__('records', wordspew); ?></div>
			<div class="navigation">
			<?php
			if ($offset > 0) {
				echo '<div style="float:left;"><a href="wordspew_archive.php?offset='.($offset - $limit).$link_cat.'">&lt; '.__("Newer",wordspew).'</a></div> ';
			}
			if (($offset + $limit) < $total) {
				echo '<div style="float:right;" id="older"><a href="wordspew_archive.php?offset='.($offset + $limit).$link_cat.'">'.__("Older",wordspew).' &gt;</a></div>';
			}
			?>
			</div>
		</td>
	</tr>
</table>
<?php echo $link; ?>
</body>
</html>
<?php }
else header('location: '.$Actual_URL);
}
jal_get_shoutboxarchive(); ?>