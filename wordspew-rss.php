<?php
if (!isset($wpdb)) {
	require('../../../wp-blog-header.php');
}
//expire every 3 minutes
$offset = 60*3;
$ExpStr = gmdate("D, d M Y H:i:s",time() + $offset)." GMT";
header("Cache-Control: max-age=".$offset.", must-revalidate");
header("Pragma: private");
header("Expires: ".$ExpStr);

define('wordspew', 'pierres-wordspew/lang/wordspew');
if(function_exists('load_plugin_textdomain')) load_plugin_textdomain(wordspew);

$id	=  isset($_GET['id']) ? $_GET['id'] : "";
$jal_wp_url = get_bloginfo('wpurl');
$shout_opt = get_option('shoutbox_options');
$shout_cat=(isset($_GET['shout_cat'])) ? $_GET['shout_cat'] : "";

function rss_feed() {
global $wpdb, $wp_version, $table_prefix, $jal_wp_url, $user_ID, $user_level, $shout_opt, $shout_cat, $user_identity, $user_nickname, $theuser_nickname,$jal_admin_user_level;

$show_to_level=$shout_opt['level_for_shoutbox'];
$level_for_theme=$shout_opt['level_for_theme'];
$user_level=isset($user_level) ? $user_level : -1;
$theuser_nickname=(version_compare($wp_version, '2.0', '>=')) ? $user_identity : $user_nickname;
$current=($show_to_level==-1) ? 1 : current_user_can('level_'.$show_to_level);
$curthe=($level_for_theme==-1) ? 1 : current_user_can('level_'.$level_for_theme);

if (($user_level >= $show_to_level || $current==1) && ($user_level>=$level_for_theme || $shout_cat=="" || $curthe==1)) {

@mysql_query("SET CHARACTER SET 'utf8'");
@mysql_query("SET NAMES utf8");
$UseRSS=$shout_opt['use_rss'];

$events = $wpdb->get_results("SELECT * FROM ".$table_prefix."liveshoutbox WHERE cat='".mysql_real_escape_string($shout_cat)."' ORDER BY id DESC");
$jal_first_time = true;
header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?>
'; ?>
<!-- generator="wordpress/<?php bloginfo_rss('version') ?>" -->
<rss version="2.0" 
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/">
	<channel>
		<title><?php _e('Wordspew-RSS-Feed for:', wordspew);?> <?php bloginfo_rss('name'); ?></title>
		<link><?php bloginfo_rss('url') ?></link>
		<description><?php bloginfo_rss("description") ?></description>
		<generator>http://wordpress.org/?v=<?php bloginfo_rss('version'); ?></generator>
		<?php if ($UseRSS=='1') {
	foreach ($events as $event) {
		if ($jal_first_time == true) { ?><pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', gmdate( 'Y-m-d H:i:s', $event->time ), false); ?></pubDate>
		<language><?php echo get_option('rss_language'); ?></language>
<?php $jal_first_time = false; }
$TheText=$event->text;
$verif=true;
if (substr($TheText,0,2)=="@@") {
	$verif=false;
	$PosSpace=strpos($TheText," ");
	$To=substr($TheText,2,$PosSpace-2);
	$Deb=strlen($To)+2;
	$TheText='<font color="red">'.__("Private message for", wordspew).' '.$To.':</font> '.substr($TheText,$Deb);
	$the_nickname=isset($theuser_nickname) ? $theuser_nickname : str_replace("\'", "'", $_COOKIE['jalUserName']);
	if((strtolower($the_nickname)==strtolower($To)) || (strtolower($the_nickname)==strtolower($event->name)) 
	|| ($user_level >= $jal_admin_user_level || current_user_can('level_'.$jal_admin_user_level)==1)) $verif=true;
}
if($verif) { ?>
		<item>
			<title><?php echo $event->name.' ('.mysql2date('D, d M Y H:i:s', date('Y-m-d H:i:s',$event->time)).')'; ?></title>
			<link><?php echo $jal_wp_url;?>/wp-content/plugins/pierres-wordspew/wordspew-rss.php?id=<?php echo $event->id; ?></link>
			<category>Shoutbox</category>
			<guid isPermaLink="false"><?php echo $jal_wp_url;?>/wp-content/plugins/pierres-wordspew/wordspew-rss.php?id=<?php echo $event->id;?></guid>
			<description><![CDATA[<?php echo convert_smilies(stripslashes($TheText)); ?>]]></description>
			<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', gmdate( 'Y-m-d H:i:s', $event->time ), false); ?></pubDate>
		</item>
<?php }}} ?>
	</channel>
</rss>
<?php }}
function jal_getRSS ($ID) {
global $wpdb, $wp_version, $table_prefix, $jal_wp_url, $user_ID, $user_level, $shout_opt, $user_identity, $user_nickname, $theuser_nickname,$jal_admin_user_level;

$show_to_level=$shout_opt['level_for_shoutbox'];
$user_level=isset($user_level) ? $user_level : -1;
$theuser_nickname=(version_compare($wp_version, '2.0', '>=')) ? $user_identity : $user_nickname;
$current=($show_to_level==-1) ? 1 : current_user_can('level_'.$show_to_level);

if ($user_level >= $show_to_level || $current==1) {
	@mysql_query("SET CHARACTER SET 'utf8'");
	@mysql_query("SET NAMES utf8");
	$UseRSS=$shout_opt['use_rss'];
	$XHTML=$shout_opt['xhtml'];
	$html="";
	if ($UseRSS=='1') {
		$results = $wpdb->get_results("SELECT * FROM ".$table_prefix."liveshoutbox WHERE id = ".intval($ID));
		foreach( $results as $r ) {
			$target="";
			$TheText=$r->text;
			$verif=true;
			if (substr($TheText,0,2)=="@@") {
				$verif=false;
				$PosSpace=strpos($TheText," ");
				$To=substr($TheText,2,$PosSpace-2);
				$Deb=strlen($To)+2;
				$TheText='<font color="red">'.__("Private message for", wordspew).' '.$To.':</font> '.substr($TheText,$Deb);
				$the_nickname=isset($theuser_nickname) ? $theuser_nickname : str_replace("\'", "'", $_COOKIE['jalUserName']);
				if((strtolower($the_nickname)==strtolower($To)) || (strtolower($the_nickname)==strtolower($r->name)) 
				|| ($user_level >= $jal_admin_user_level || current_user_can('level_'.$jal_admin_user_level)==1)) $verif=true;
			} 
			if($verif) {
				if (strpos($TheText, $jal_wp_url)===false && $XHTML==0) $target=' target="_blank"';
				$theLink=__("link",wordspew); $theMail=__("email",wordspew);
				$TheText = preg_replace("`(http|ftp)+(s)?:(//)((\w|\.|\-|_)+)(/)?(\S+)?`i", "<a href=\"\\0\"$target>&laquo;$theLink&raquo;</a>", $TheText);
				$TheText = preg_replace("`([-_a-z0-9]+(\.[-_a-z0-9]+)*@[-a-z0-9]+(\.[-a-z0-9]+)*\.[a-z]{2,6})`i","<a href=\"mailto:\\1\">&laquo;$theMail&raquo;</a>", $TheText); 
				$url = (empty($r->url) && $r->url = "http://") ? $r->name : '<a href="'.$r->url.'"'.$target.'>'.$r->name.'</a>';
				$html.= '<div>'.stripslashes($url).' <small>(' .mysql2date('D, d M Y H:i:s', date( 'Y-m-d H:i:s', $r->time )).')</small></div>'; 
				$html.= "\n".'<div>'.convert_smilies(stripslashes($TheText)).'</div>'."\n";
			}
		}
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head profile="http://gmpg.org/xfn/11">
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
	<title><?php _e('Wordspew-RSS-Feed for:', wordspew);?> <?php bloginfo('name'); ?></title>
	<meta name="generator" content="WordPress <?php bloginfo('version'); ?>" /> <!-- leave this for stats -->
	<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen"/>
</head>

<body style="margin: 10px; text-align:left; font-size: 12pt; ">

<?php echo $html;?>

</body>
</html>
<?php
}
if($id=="")
	rss_feed();
else
	jal_getRSS ($id);
?>