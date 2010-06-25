<?php
/*
Plugin Name: Pierre's Wordspew
Plugin URI: http://wordpress.org/extend/plugins/pierres-wordspew/
Description: A plugin that creates a live shoutbox, using AJAX as a backend. Users can chat freely from your blog without refreshing the page! It uses the Fade Anything Technique for extra glamour
Author: Andrew Sutherland, Modified by Pierre
Author URI: http://pierre.dommiers.com/
Version: 5.3
*/

// Version of this plugin. Not very useful for you, but for the dev
$jal_version = "5.3";

include_once ('common.php');
include_once ('usersonline.php');

if (!isset($table_prefix)) {
	$html = implode('', file("../../../wp-config.php"));
	$html = str_replace ("require_once", "// ", $html);
	$html = str_replace ("<?php", "", $html);
	eval($html);
}

if(!isset($_SESSION['Cur_URL'])) {
	$_SESSION['Cur_URL']=$_SERVER['REQUEST_URI'];
	$_SESSION['tb_prefix']=$table_prefix;
}

if($_SESSION['Cur_URL']!=$_SERVER['REQUEST_URI']) {
	$_SESSION['Cur_URL']=$_SERVER['REQUEST_URI'];
	$_SESSION['tb_prefix']=$table_prefix;
}

$shout_cat=isset($_POST['cat']) ? $_POST['cat'] : "";

$mode=isset($_POST['mode']) ? $_POST['mode'] : "";
$shout_ID=isset($_POST['id']) ? $_POST['id'] : "";
$shout_IP=isset($_POST['ip']) ? $_POST['ip'] : "";
$shout_Text=isset($_POST['text']) ? $_POST['text'] : "";

if ($shout_ID!= '' && $mode=="edit") {
	jal_shout_edit($shout_ID, $shout_IP, $shout_Text); //edit data
	echo "0";
}
if ($shout_ID!= '' && $mode=="ban") {
	jal_shout_spam($shout_ID, $shout_IP); //ban IP
	echo "0";
}

function jal_install_shout () {
global $wpdb, $user_level, $wp_version;

	$jal_admin_user_level = (get_option('shoutbox_admin_level')!="") ? get_option('shoutbox_admin_level') : 10;
	$shout_opt = get_option('shoutbox_options');

    get_currentuserinfo();
	$current=current_user_can('level_'.$jal_admin_user_level);

	if($user_level < $jal_admin_user_level && $current!=1) return;

  	$result = mysql_list_tables(DB_NAME);
  	$tables = array();

  	while ($row = mysql_fetch_row($result)) { $tables[] = $row[0]; }

    if (!in_array($_SESSION['tb_prefix']."liveshoutbox", $tables)) {
    	$first_install = "yes";
    }

	$qry="CREATE TABLE ".$_SESSION['tb_prefix']."liveshoutbox (
			id mediumint(7) NOT NULL AUTO_INCREMENT,
			time bigint(11) DEFAULT '0' NOT NULL,
			name tinytext NOT NULL,
			text text NOT NULL,
			url text NOT NULL,
			ipaddr varchar(16),
			email tinytext NOT NULL,
			cat tinytext NOT NULL,
			UNIQUE KEY id (id)
			) CHARACTER SET utf8;

		CREATE TABLE ".$_SESSION['tb_prefix']."liveshoutboxarchive (
			id mediumint(7) NOT NULL AUTO_INCREMENT,
			time bigint(11) DEFAULT '0' NOT NULL,
			name tinytext NOT NULL,
			text text NOT NULL,
			url text NOT NULL,
			ipaddr varchar(16),
			email tinytext NOT NULL,
			cat tinytext NOT NULL,
			UNIQUE KEY id (id)
			) CHARACTER SET utf8;

		CREATE TABLE ".$_SESSION['tb_prefix']."liveshoutbox_useronline (
			timestamp int(15) NOT NULL default '0',
			username varchar(50) NOT NULL default '',
			ip varchar(40) NOT NULL default '',
			location varchar(255) NOT NULL default '',
			url varchar(255) NOT NULL default '',
			PRIMARY KEY  (timestamp),
			KEY username (username),
			KEY ip (ip),
			KEY file (location)
			) CHARACTER SET utf8;
	";
	$pathtoFunction = (version_compare($wp_version, '2.3', '>=')) ? "wp-admin/includes/upgrade.php" : "wp-admin/upgrade-functions.php";
	require_once(ABSPATH . $pathtoFunction);
	dbDelta($qry);

	if ($first_install == "yes") {
		$welcome_text = __('Congratulations, you just completed the installation of this shoutbox.',wordspew);
		@mysql_query("SET CHARACTER SET 'utf8'");
		@mysql_query("SET NAMES utf8");
		$wpdb->query("INSERT INTO ".$_SESSION['tb_prefix']."liveshoutbox (time,name,text) VALUES ('".time()."','Pierre','".$welcome_text."')");
	}
	else {
		$wpdb->query("ALTER TABLE ".$_SESSION['tb_prefix']."liveshoutbox CHARACTER SET utf8");
		$wpdb->query("ALTER TABLE ".$_SESSION['tb_prefix']."liveshoutbox MODIFY `text` TEXT NOT NULL, CHARACTER SET utf8");
		$wpdb->query("ALTER TABLE ".$_SESSION['tb_prefix']."liveshoutbox MODIFY `name` TINYTEXT NOT NULL, CHARACTER SET utf8");
		$wpdb->query("ALTER TABLE ".$_SESSION['tb_prefix']."liveshoutbox_useronline CHARACTER SET utf8");
		$wpdb->query("ALTER TABLE ".$_SESSION['tb_prefix']."liveshoutbox_useronline MODIFY `username` VARCHAR(50) NOT NULL, CHARACTER SET utf8");
	}

	if (!$shout_opt) {
		$shout_opt = array (
			'fade_from' => (get_option('shoutbox_fade_from')!="") ? get_option('shoutbox_fade_from') : '666666',
			'fade_to' => (get_option('shoutbox_fade_to')!="") ? get_option('shoutbox_fade_to') : 'FFFFFF',
			'update_seconds' => (get_option('shoutbox_update_seconds')!="") ? intval(get_option('shoutbox_update_seconds')) : 4000,
			'fade_length' => (get_option('shoutbox_fade_length')!="") ? intval(get_option('shoutbox_fade_length')) : 1500,
			'text_color' => (get_option('shoutbox_text_color')!="") ? get_option('shoutbox_text_color') : '333333',
			'name_color' => (get_option('shoutbox_name_color')!="") ? get_option('shoutbox_name_color') : '0066CC',
			'use_url' => (get_option('shoutbox_use_url')=="true") ? 1 : 0,
			'use_textarea' => (get_option('shoutbox_use_textarea')=="true") ? 1 : 0,
			'registered_only' => -1,
			'use_sound' => (get_option('shoutbox_sound')!="") ? intval(get_option('shoutbox_sound')) : 0,
			'xhtml' => (get_option('shoutbox_XHTML')!="") ? intval(get_option('shoutbox_XHTML')) : 0,
			'show_user_online' => (get_option('shoutbox_online')!="") ? intval(get_option('shoutbox_online')) : 0,
			'show_smiley' => (get_option('shoutbox_Smiley')!="") ? intval(get_option('shoutbox_Smiley')) : 0,
			'show_spam' => (get_option('shoutbox_Show_Spam')!="") ? intval(get_option('shoutbox_Show_Spam')) : 0,
			'use_captcha' => (get_option('shoutbox_Captcha')!="") ? intval(get_option('shoutbox_Captcha')) : 0,
			'hash' => ShoutboxHash(10),
			'hidden_users' => (get_option('shoutbox_HideUsers')!="") ? get_option('shoutbox_HideUsers') : '',
			'use_rss' => (get_option('shoutbox_UseRSS')!="") ? intval(get_option('shoutbox_UseRSS')) : 0,
			'level_for_shoutbox' => -1,
			'level_for_archive' => 10,
			'show_avatar' => 0,
			'avatar_size' => 16,
			'avatar_position' => 'left',
			'level_for_theme' => 10,
			'cssDate' => time(),
			'use_theme' => 0,
			'use_filters' => 1,
			'where' => ''
		);
		ksort ($shout_opt);
		add_option ('shoutbox_options', $shout_opt);
		//DELETE... old fields
		$wpdb->query("DELETE FROM ".$_SESSION['tb_prefix']."options WHERE option_name='shoutbox_fade_from'");
		$wpdb->query("DELETE FROM ".$_SESSION['tb_prefix']."options WHERE option_name='shoutbox_fade_to'");
		$wpdb->query("DELETE FROM ".$_SESSION['tb_prefix']."options WHERE option_name='shoutbox_update_seconds'");
		$wpdb->query("DELETE FROM ".$_SESSION['tb_prefix']."options WHERE option_name='shoutbox_fade_length'");
		$wpdb->query("DELETE FROM ".$_SESSION['tb_prefix']."options WHERE option_name='shoutbox_text_color'");
		$wpdb->query("DELETE FROM ".$_SESSION['tb_prefix']."options WHERE option_name='shoutbox_name_color'");
		$wpdb->query("DELETE FROM ".$_SESSION['tb_prefix']."options WHERE option_name='shoutbox_use_url'");
		$wpdb->query("DELETE FROM ".$_SESSION['tb_prefix']."options WHERE option_name='shoutbox_use_textarea'");
		$wpdb->query("DELETE FROM ".$_SESSION['tb_prefix']."options WHERE option_name='shoutbox_registered_only'");
		$wpdb->query("DELETE FROM ".$_SESSION['tb_prefix']."options WHERE option_name='shoutbox_sound'");
		$wpdb->query("DELETE FROM ".$_SESSION['tb_prefix']."options WHERE option_name='shoutbox_XHTML'");
		$wpdb->query("DELETE FROM ".$_SESSION['tb_prefix']."options WHERE option_name='shoutbox_online'");
		$wpdb->query("DELETE FROM ".$_SESSION['tb_prefix']."options WHERE option_name='shoutbox_Smiley'");
		$wpdb->query("DELETE FROM ".$_SESSION['tb_prefix']."options WHERE option_name='shoutbox_Show_Spam'");
		$wpdb->query("DELETE FROM ".$_SESSION['tb_prefix']."options WHERE option_name='shoutbox_Captcha'");
		$wpdb->query("DELETE FROM ".$_SESSION['tb_prefix']."options WHERE option_name='shoutbox_hash'");
		$wpdb->query("DELETE FROM ".$_SESSION['tb_prefix']."options WHERE option_name='shoutbox_HideUsers'");
		$wpdb->query("DELETE FROM ".$_SESSION['tb_prefix']."options WHERE option_name='shoutbox_UseRSS'");
		$wpdb->query("DELETE FROM ".$_SESSION['tb_prefix']."options WHERE option_name='shoutbox_Show_to_Register'");
	}

	add_option('shoutbox_spam', '0','','yes');
	add_option('shoutbox_admin_level', $jal_admin_user_level,'','yes');
	add_option('shoutbox_nb_comment', '35','','yes');
}

// In the administration page, add some style and script...
function jal_add_to_admin_head () {
global $wp_version, $wpdb, $shout_opt, $shout_tb;
$can_Ban=(current_user_can('manage_options')) ? "true" : "false";
$shout_opt = get_option('shoutbox_options');
if(!isset($_SESSION['LoggedUsers'])) {
	$column = (version_compare($wp_version, '1.5', '>')) ? "display_name" : "user_nickname";
	$LoggedUsers = $wpdb->get_col("SELECT ".$column." FROM ".$wpdb->users);
	$_SESSION['LoggedUsers']=$LoggedUsers;
	$_SESSION['Show_Users']=$shout_opt['show_user_online'];
	$users = $shout_opt['hidden_users'];
	$users = str_replace(", ", ",", $users);
	$UsersToHide = stripslashes($users);
	$_SESSION['HideUsers'.$shout_tb]=explode(",",strtolower($UsersToHide));
}
?>
<script type="text/javascript">
//<![CDATA[
<?php
echo 'var can_Ban='.$can_Ban.', cur_theme=0, url="'.get_bloginfo('wpurl').'/wp-content/plugins/pierres-wordspew/wordspew_archive.php?shout_cat=";
';
?>
//]]>
</script>
<script type="text/javascript" src="<?php echo get_bloginfo('wpurl');?>/wp-content/plugins/pierres-wordspew/fade.php"></script>
<script type="text/javascript" src="<?php echo get_bloginfo('wpurl');?>/wp-content/plugins/pierres-wordspew/ajax_admin.php"></script>
<style type="text/css">
input[name=jal_delete]:hover, #jal_truncate_all:hover, #jal_truncate_all_archive:hover, #jal_shout_archive:hover, input[name=jal_ban]:hover { background: #c22; color: #fff; cursor: pointer; }
input[name=jal_edit]:hover, #jal_admin_options:hover { background: #2c2; color: #fff; cursor: pointer; }
#shoutbox_options p { text-indent: 15px; padding: 5px 0; color: #555; }
#shoutbox_options .SousRub { margin: 0 0 20px 15px; color: #555; }
#shoutbox_options span, #shoutbox_options .UserOption { border: 1px dotted #ccc; padding: 4px 14px; }
#outputList { list-style-type:none; }
#theme { font-size: 14pt; text-align: center; text-transform: capitalize; }
div#responseTime { display: inline; }
.shout { cursor: pointer; color: #22CC22; }
.archive { cursor: pointer; color: #FF0000; }
</style>
<?php }
// HTML printed to the admin panel
function jal_shoutbox_admin () {
global $wpdb, $user_level, $nb, $jal_version, $wp_roles, $wp_version, $shout_opt, $shout_cat, $shout_tb;

	$jal_admin_user_level = (get_option('shoutbox_admin_level')!="") ? get_option('shoutbox_admin_level') : 10;

	get_currentuserinfo(); // Gets logged in user.
	$level_for_archive=$shout_opt['level_for_archive'];
	$show_to_level=$shout_opt['level_for_shoutbox'];
	$level_for_theme=$shout_opt['level_for_theme'];

	$jal_number_of_comments=get_option('shoutbox_nb_comment');
	$nb = get_option('shoutbox_spam');

	$current=current_user_can('level_'.$jal_admin_user_level);
	// If user is not allowed to use the admin page
	if ($user_level < $jal_admin_user_level && $current!=1) {
		echo '<div class="wrap"><h2>' . __("No Access for you!",wordspew) .'</h2></div>';
	} 
	else {
		$_SESSION['isAdmin'.$shout_tb]=true;
		if (isset($_POST['jal_truncate'])) {
			$what=stripslashes($_POST['cat']);
			$what=($what!="") ? $what : __("Miscellaneous",wordspew) ;
			$what='"'.$what.'"';?>
		<div class="updated"><p><?php printf(__("The content of the shoutbox %s has been wiped.",wordspew),$what); ?></p></div>
<?php } ?>
<div class="wrap">
	<h2><?php _e('Live Shoutbox',wordspew);?> v. <?php echo $jal_version; ?>
	<?php printf(__('(Actually <font color="red">%s</font> spams blocked)',wordspew),$nb);?></h2>
	<p><?php _e('When you update the Times and Colors, you may need to refresh/empty cache before you see the changes take effect',wordspew);?></p>
	<p><?php 
	$results = $wpdb->get_var("SELECT id FROM ".$_SESSION['tb_prefix']."liveshoutbox ORDER BY id DESC LIMIT 1");
	if($results) printf(__('There have been <b>%s</b> messages in this shoutbox',wordspew),$results);?>&nbsp;</p>
	<form name="shoutbox_options" action="edit.php?page=wordspew_admin" method="post" id="shoutbox_options"> 
	<fieldset>
	<legend><b><?php _e('Colors (Must be 6 digit hex)',wordspew);?></b></legend>
	<div class="UserOption">
	<input type="hidden" name="page" value="wordspew_admin" />
	<?php _e('Fade from',wordspew);?>: #<input type="text" maxlength="6" name="fade_from" value="<?php echo $shout_opt['fade_from']; ?>" 
	size="6" onchange="ChangeColor('fadefrom',this.value)" onkeyup="ChangeColor('fadefrom',this.value)"/>
	<span id="fadefrom" style="background:#<?php echo $shout_opt['fade_from']; ?>;">&nbsp;</span>
	<div class="SousRub"><?php _e('The color that new messages fade in from. Default',wordspew);?>: 
	<span style="background:#666;color:#fff;">666666</span></div>

	<?php _e('Fade to',wordspew);?>: #<input type="text" maxlength="6" name="fade_to" value="<?php echo $shout_opt['fade_to']; ?>" 
	size="6" onchange="ChangeColor('fadeto',this.value)" onkeyup="ChangeColor('fadeto',this.value)"/> 
	<span id="fadeto" style="background:#<?php echo $shout_opt['fade_to']; ?>;">&nbsp;</span>
	<div class="SousRub"><?php _e('Also used as the background color of the box. Default',wordspew);?>
	<span style="background:#fff;color:#000;">FFFFFF</span></div>

	<?php _e('Text Color',wordspew);?>: #<input type="text" maxlength="6" name="text_color" value="<?php echo $shout_opt['text_color']; ?>" 
	size="6" onchange="ChangeColor('textcolor',this.value)" onkeyup="ChangeColor('textcolor',this.value)"/> 
	<span id="textcolor" style="background:#<?php echo $shout_opt['text_color']; ?>;">&nbsp;</span>
	<div class="SousRub"><?php _e('The color of text within the box. Default',wordspew);?>: 
	<span style="background:#333;color:#fff;">333333</span></div>

	<?php _e('Name Color',wordspew);?>: #<input type="text" maxlength="6" name="name_color" value="<?php echo $shout_opt['name_color']; ?>" 
	size="6" onchange="ChangeColor('namecolor',this.value)" onkeyup="ChangeColor('namecolor',this.value)"/> 
	<span id="namecolor" style="background:#<?php echo $shout_opt['name_color']; ?>;">&nbsp;</span>
	<div class="SousRub"><?php _e('The color of peoples\' names. Default',wordspew);?>: 
	<span style="background:#06c;color:#fff;">0066CC</span></div>
	</div>
	</fieldset>
	<br />

	<fieldset>
	<legend><b><?php _e('Others',wordspew);?></b></legend>
	<div class="UserOption">
	<?php _e('Show',wordspew);?>:<input type="text" maxlength="3" name="nb_comment" value="<?php echo $jal_number_of_comments; ?>" 
	size="2" /> <?php _e('comments in the shoutbox',wordspew);?><br />
	<div class="SousRub"><?php _e('Enter, here, the number of shouts you want to show in your shoutbox', wordspew);?></div>
	<?php _e('Update Every',wordspew);?>: <input type="text" maxlength="3" name="update_seconds" 
	value="<?php echo $shout_opt['update_seconds'] / 1000; ?>" size="2" /> <?php _e('Seconds',wordspew);?><br />
	<div class="SousRub"><?php _e('This determines how "live" the shoutbox is. With a bigger number, it will take more time for messages to show up, but also decrease the server load. You may use decimals. This number is used as the base for the first 8 javascript loads. After that, the number gets successively bigger. Adding a new comment or mousing over the shoutbox will reset the interval to the number suplied above. Default: 4 Seconds',wordspew);?></div>
	<?php _e('Fade Length',wordspew);?>: <input type="text" maxlength="3" name="fade_length" 
	value="<?php echo $shout_opt['fade_length'] / 1000; ?>" size="2" /> <?php _e('Seconds',wordspew);?><br />
	<div class="SousRub"><?php _e('The amount of time it takes for the fader to completely blend with the background color. You may use decimals. Default 1.5 seconds',wordspew);?></div>
	<?php _e('Use textarea',wordspew);?>: <input type="checkbox" name="use_textarea"<?php 
	if($shout_opt['use_textarea'] == '1') { echo ' checked="checked" '; } ?>/>
	<div class="SousRub"><?php _e('A textarea is a bigger type of input box. Users will have more room to type their comments, but it will take up more space.',wordspew);?></div>
	<?php _e('Use URL field',wordspew);?>: <input type="checkbox" name="use_url"<?php 
	if($shout_opt['use_url'] == '1') echo ' checked="checked" '; ?>/>
	<div class="SousRub"><?php _e('Check this if you want users to have an option to add their URL when submitting a message.',wordspew);?></div>
	<?php _e('Use sound alert',wordspew);?>: <input type="checkbox" name="use_sound"<?php 
	if($shout_opt['use_sound'] == '1') echo ' checked="checked" '; ?>/>
	<div class="SousRub"><?php _e('Check this if you want to hear a sound alert when someone post message',wordspew);?></div>
	<?php _e('Show smileys list',wordspew);?>: <input type="checkbox" name="Show_Smiley"<?php 
	if($shout_opt['show_smiley'] == '1') echo ' checked="checked" '; ?>/>
	<div class="SousRub"><?php _e('Check this if you want to show the smileys list',wordspew);?></div>	
	<?php _e('Show blocked spams',wordspew);?>: <input type="checkbox" name="Show_Spam"<?php 
	if($shout_opt['show_spam'] == '1') echo ' checked="checked" '; ?>/>
	<div class="SousRub"><?php _e('Check this if you want to show blocked spams',wordspew);?></div>
	<?php _e('Use a captcha',wordspew);?>: <input type="checkbox" name="Captcha"<?php 
	if($shout_opt['use_captcha'] == '1') echo ' checked="checked" '; ?>/>
	<div class="SousRub"><?php _e('Check this if you want to use a captcha (in fact it\'s a simple addition that users have to resolve before post any new message in the shoutbox).',wordspew);?></div>
	<?php _e('Use Themes',wordspew);?>: <input type="checkbox" name="Use_Theme" id="Use_Theme" onClick="disable_enable('Use_Theme', 'Show_themes_to', true);"<?php 
	if($shout_opt['use_theme'] == '1') echo ' checked="checked" '; ?>/>
	<div class="SousRub"><?php _e('Check this if you want to use themes.',wordspew);?></div>
	<?php _e('Use the RSS feed',wordspew);?>: <input type="checkbox" name="Use_RSS"<?php 
	if($shout_opt['use_rss'] == '1') echo ' checked="checked" '; ?>/>
	<div class="SousRub"><?php _e('Check this if you want that users can show and/or use the RSS feed of the shoutbox.',wordspew);?></div>	
	<?php _e('XHTML strict',wordspew);?>: <input type="checkbox" name="XHTML"<?php 
	if($shout_opt['xhtml'] == '1') echo ' checked="checked" '; ?>/>
	<div class="SousRub"><?php _e('Check this if you want to use XHTML strict',wordspew);?></div>
	<b><?php _e('Use spam filters',wordspew);?></b>: <input type="checkbox" id="Use_Filters" name="Use_Filters" 
	<?php if($shout_opt['use_filters'] == '1' || !isset($shout_opt['use_filters'])) echo ' checked="checked" '; ?>/>
	<div class="SousRub"><?php _e('Check this if you want to use filters against spams',wordspew);?></div>

	<?php _e('Use the shoutbox only on:',wordspew);?> <input type="text" name="where" value="<?php echo $shout_opt['where']; ?>" style="width: 600px;"/>
	<div class="SousRub"><?php _e('If you want to use the shoutbox only on some pages, enter here their titles (each entry have to be separated by a comma) otherwise or if you don\'t understand its usage, let this field empty.',wordspew);?><br/><?php _e('An other possibilities, is to show the shoutbox <b>only on particulars sections</b>. You can use this values:',wordspew);?>
	<b>@homepage, @frontpage, @pages, @single, @archives, @category</b>.
	<br/><?php _e('And last but not least, you can use 2 kinds of values to be more specific.',wordspew);?> <b>(linked), (rubric)</b>.
	<br/><?php _e('If you use the term:',wordspew);?> @pages(rubric) <?php _e('It meens that you want to use the same specific shoutbox for <b>ALL</b> pages.',wordspew);?>
	<br/><?php _e('If you use the term:',wordspew);?> @pages(linked) <?php _e('It meens that you want to use a specific shoutbox on <b>EACH</b> page.',wordspew);?>
	<br/><?php _e('You can use these 2 keywords with :',wordspew);?> <b>@pages, @single, @archives</b> (<?php printf(__('only %s, here',wordspew),'rubric');?>), <b>@category</b>.
	<br/><?php _e('Finally, if you want to use the shoutbox in a page template you\'ve done by yourself, enter : ',wordspew);?> @page[<?php _e('The name of your page',wordspew);?>].
	</div>
	</div>
	</fieldset>
	<br />

	<fieldset>
	<legend><b><?php _e('Users options',wordspew);?></b></legend>
	<div class="UserOption">

	<?php if(current_user_can('level_10')) { ?>
	<font color="red"><?php _e('<b>Who can administrate the shoutbox</b>',wordspew);?>:</font>
	<select name="admin_user_level">
	<?php
	// Piece of code from "Role Manager" plugin for role and level part. Thanks to -> Thomas Schneider : http://www.im-web-gefunden.de/wordpress-plugins/role-manager/
	$array_admin=array();
	foreach($wp_roles->role_names as $roledex => $rolename) {
		$role = $wp_roles->get_role($roledex);
		$role_user_level = array_reduce(array_keys($role->capabilities), array('WP_User', 'level_reduction'), 0);
		if(!in_array($role_user_level, $array_admin)) {
			array_push($array_admin, $role_user_level);
			if($role_user_level >0) {
				$selected=($jal_admin_user_level==$role_user_level) ? ' selected="true"' : '';
				echo '<option value="'.$role_user_level.'"'.$selected.'>'.$rolename." (".__("level",wordspew) ." ". $role_user_level.')</option>
				';
			}
		}
	}
	?>
	</select>
	<div class="SousRub"><?php _e('Choose the level required to administrate the shoutbox',wordspew);?>. <?php printf(__('<a href="%s"><b>Click here</b></a> if you need more information on Roles and Capabilities.',wordspew),"http://codex.wordpress.org/Roles_and_Capabilities");?></div>
	<?php }; ?>
	
	<?php if(current_user_can('level_'.$jal_admin_user_level)) {
	_e('Who can <b>see the shoutbox</b>',wordspew);?>: <select id="level_for_shoutbox" name="level_for_shoutbox" 
	onchange="disable_enable('level_for_shoutbox', 'registered_only', false);">
	<?php
	if($wp_roles) {
		$array_box=array();
		foreach($wp_roles->role_names as $roledex => $rolename) {
			$role = $wp_roles->get_role($roledex);
			$role_user_level = array_reduce(array_keys($role->capabilities), array('WP_User', 'level_reduction'), 0);
			if(!in_array($role_user_level, $array_box)) {
				array_push($array_box, $role_user_level);
				$selected=($show_to_level==$role_user_level) ? ' selected="true"' : '';
				echo '<option value="'.$role_user_level.'"'.$selected.'>'.$rolename.' ('.__("level",wordspew) .' '. $role_user_level.')</option>
	';
			}
		}
		$selected=($show_to_level==-1) ? ' selected="true"' : '';
		echo '<option value="-1"'.$selected.'>'.__("Everybody",wordspew).'</option>';
	}
	?>
	</select>
	<div class="SousRub"><?php _e('Choose, here, users able to <strong>see and post</strong> in your shoutbox. Other users will simply view nothing :)',wordspew);?>
	<div style="color:red;" id="Info"><?php _e('<b>Note:</b> You have choose to show the shoutbox only to :',wordspew);?>
	<span id="From_List" style="font-weight:bold"><noscript><?php _e('Please activate Javascript...',wordspew);?></noscript></span>
	<?php _e('in the list above,<br/>it will take precedence over the next option',wordspew);?> (<em><?php _e('Who can <b>see the shoutbox archives</b>',wordspew);?></em>).</div>
	</div>
	
	<?php _e('Who can <b>use the shoutbox</b>',wordspew);?>: <select id="registered_only" name="registered_only">
	<?php
	if($wp_roles) {
		$array_box=array();
		foreach($wp_roles->role_names as $roledex => $rolename) {
			$role = $wp_roles->get_role($roledex);
			$role_user_level = array_reduce(array_keys($role->capabilities), array('WP_User', 'level_reduction'), 0);
			if(!in_array($role_user_level, $array_box)) {
				array_push($array_box, $role_user_level);
				$selected=($shout_opt['registered_only']==$role_user_level) ? ' selected="true"' : '';
				echo '<option value="'.$role_user_level.'"'.$selected.'>'.$rolename.' ('.__("level",wordspew) .' '. $role_user_level.')</option>
	';
			}
		}
		$selected=($shout_opt['registered_only']==-1) ? ' selected="true"' : '';
		echo '<option value="-1"'.$selected.'>'.__("Everybody",wordspew).'</option>';
	}
	?>
	</select>
	<div class="SousRub"><?php _e('Choose, here, users able to <strong>post messages</strong> in your shoutbox. Other users will simply view the discussion.',wordspew);?></div>

	<?php
	_e('Who can <b>see the shoutbox archives</b>',wordspew);?>: 
	<select id="Show_archive_to" name="Show_archive_to" onchange="disable_enable('level_for_shoutbox', 'registered_only', false);">
	<?php
	if($wp_roles) {
		$array_arch=array();
		foreach($wp_roles->role_names as $roledex => $rolename) {
			$role = $wp_roles->get_role($roledex);
			$role_user_level = array_reduce(array_keys($role->capabilities), array('WP_User', 'level_reduction'), 0);
			if(!in_array($role_user_level, $array_arch)) {
				array_push($array_arch, $role_user_level);
				$selected=($level_for_archive==$role_user_level) ? ' selected="true"' : '';
				echo '<option value="'.$role_user_level.'"'.$selected.'>'.$rolename.' ('.__("level",wordspew) .' '. $role_user_level.')</option>
	';
			}
		}
		$selected=($level_for_archive==-1) ? ' selected="true"' : '';
		echo '<option value="-1"'.$selected.'>'.__("Everybody",wordspew).'</option>';
	}
	?>
	</select>
	<div class="SousRub"><?php _e('Choose who is able to see archive of the shoutbox',wordspew);?>.</div>
	
	<?php
	_e('Who can see the differents themes',wordspew);?>: 
	<select id="Show_themes_to" name="Show_themes_to">
	<?php
	if($wp_roles) {
		$array_arch=array();
		foreach($wp_roles->role_names as $roledex => $rolename) {
			$role = $wp_roles->get_role($roledex);
			$role_user_level = array_reduce(array_keys($role->capabilities), array('WP_User', 'level_reduction'), 0);
			if(!in_array($role_user_level, $array_arch)) {
				array_push($array_arch, $role_user_level);
				$selected=($level_for_theme==$role_user_level) ? ' selected="true"' : '';
				echo '<option value="'.$role_user_level.'"'.$selected.'>'.$rolename.' ('.__("level",wordspew) .' '. $role_user_level.')</option>
	';
			}
		}
		$selected=($level_for_theme==-1) ? ' selected="true"' : '';
		echo '<option value="-1"'.$selected.'>'.__("Everybody",wordspew).'</option>';
	}
	?>
	</select>
	<div class="SousRub"><?php _e('Choose who is able to see the differents themes of your shoutbox',wordspew);?>.</div>

<?php }; ?>

	<hr/>
	
	<?php _e('Show users online',wordspew);?>: <input type="checkbox" id="Show_Users" name="Show_Users" 
	onClick="disable_enable('Show_Users', 'HideUsers', true);"<?php if($shout_opt['show_user_online'] == '1') echo ' checked="checked" '; ?>/>
	<div class="SousRub"><?php _e('Check this if you want to show, in real time, users online',wordspew);?></div>

	<blockquote>
	<?php _e('Users to hide',wordspew);?>: <input type="text" id="HideUsers" name="HideUsers" 
	value="<?php echo stripslashes($shout_opt['hidden_users']); ?>" size="30" />
	<div class="SousRub"><?php _e('Place here, separated by comma, users that you want to hide from the "Users online" function of the shoutbox.',wordspew);?></div>
	</blockquote>

	<?php _e('Use avatars',wordspew);?>: <input type="checkbox" name="Use_Avatar"<?php 
	if($shout_opt['show_avatar'] == '1') echo ' checked="checked" '; ?>/>
	<div class="SousRub"><?php _e('If checked, avatar will be shown.',wordspew);?></div>
	<?php _e('Avatar size',wordspew);?>: <input type="text" maxlength="2" name="Avatar_size" 
	value="<?php echo $shout_opt['avatar_size']; ?>" size="2" /> px<br />
	<div class="SousRub"><?php _e('Choose the size for the avatars. Valid values are from 1 to 80 inclusive. Any size other than 80 will cause the original Gravatar image to be downsampled using bicubic resampling before output',wordspew);?></div>
	<?php
	$position=$shout_opt['avatar_position'];
	_e('Avatar position',wordspew);?>: 
	<select name="Avatar_position">
	<option value="left"<?php if($position=="left") echo ' selected="true"';?>><?php _e('Left',wordspew);?></option>
	<option value="right"<?php if($position=="right") echo ' selected="true"';?>><?php _e('Right',wordspew);?></option>	
	</select>
	<div class="SousRub"><?php _e('Choose the alignement for avatars (left or right)',wordspew);?></div>

	</div>
	</fieldset><br />

	<input type="submit" name="jal_admin_options" value="<?php _e('Save',wordspew);?>" class="button" id="jal_admin_options"/><br/><br/>
	<input type="submit" name="jal_truncate" value="<?php _e('Delete ALL messages',wordspew);?>" class="button" id="jal_truncate_all" onclick="return confirm('<?php printf(__("You are about to delete ALL messages from : %s in the shoutbox.\\nAre you sure you want to do this?\\n\'Cancel\' to stop, \'OK\' to delete.",wordspew),"&quot;'+document.getElementById('theme').innerHTML+'&quot;"); ?>');"/>
	<input type="submit" name="jal_truncate_archive" value="<?php _e('Delete ALL ARCHIVED messages',wordspew);?>" class="button" id="jal_truncate_all_archive" onclick="return confirm('<?php printf(__("You are about to delete ALL ARCHIVED messages from : %s.\\nAre you sure you want to do this?\\n\'Cancel\' to stop, \'OK\' to delete.",wordspew),"&quot;'+document.getElementById('theme').innerHTML+'&quot;"); ?>');"/>
	<input type="submit" name="jal_shout_archive" value="<?php _e('Archive THEN Delete ALL messages',wordspew);?>" class="button" id="jal_shout_archive" onclick="return confirm('<?php printf(__("You are about to archive THEN delete ALL messages from : %s.\\nAre you sure you want to do this?\\n\'Cancel\' to stop, \'OK\' to delete.",wordspew),"&quot;'+document.getElementById('theme').innerHTML+'&quot;"); ?>');"/><br/><br/>

	<input type="hidden" name="cat" id="cat" value="<?php echo stripslashes($shout_cat);?>"/>
	</form>
	<fieldset>

	<?php if(current_user_can('manage_options')) { ?>
	<p><?php printf(__('<a href="%s"><b>Click here</b></a> to manage your banned words list and IP addresses.',wordspew),get_bloginfo('wpurl')."/wp-admin/options-discussion.php#moderation_keys");?></p>
	<p><?php _e('<b><font color="red">Important !</font></b> To ban a single IP address just click on "Ban this IP" button. If you want to ban a range of IP, use this syntax (for this example i can say good bye to Vsevolod Stetsinsky) : 195.225.176/179.* where slash means from 176 to 179 and * from 0 to 255.<br/>BTW i ban IP addresses from 195.225.176.0 to 195.225.179.255. You can mix the two options...',wordspew);?></p>
	<?php }; ?>

	<?php
	@mysql_query("SET CHARACTER SET 'utf8'");
	@mysql_query("SET NAMES utf8");
	$SQLCat=html_entity_decode($shout_cat,ENT_COMPAT,'UTF-8');
	$SQL="SELECT * FROM ".$_SESSION['tb_prefix']."liveshoutbox WHERE cat='".mysql_real_escape_string($SQLCat)."' ORDER BY id DESC LIMIT ". $jal_number_of_comments;
	$results = $wpdb->get_results($SQL);
	$jal_first_time = true; // Will only add the last message div if it is looping for the first time

	echo '<form action="edit.php?page=wordspew_admin" method="get">
	';
	echo '<b>'.__("Theme:",wordspew).'</b>';	
	$SQL="SELECT DISTINCT cat FROM ".$_SESSION['tb_prefix']."liveshoutbox ORDER BY cat";
	$theme = $wpdb->get_results($SQL);

	foreach( $theme as $theme_name ) {
		if($theme_name->cat=="") echo ' <a class="shout" onclick="CleanBox(\'\',\''.__("Miscellaneous",wordspew).'\');"><b>'.__("Miscellaneous",wordspew).'</b></a>,';
		else echo ' <a class="shout" onclick="CleanBox(\''.$theme_name->cat.'\',\''.str_replace(" "," ",$theme_name->cat).'\');">'.stripslashes($theme_name->cat).'</a>,';
	}
	$the_cat=($shout_cat=="") ? __("Miscellaneous",wordspew) : str_replace(" "," ",stripslashes($shout_cat));

	$SQL="SELECT DISTINCT cat FROM ".$_SESSION['tb_prefix']."liveshoutboxarchive ORDER BY cat";
	$theme = $wpdb->get_results($SQL);
	$first_time=0;

	echo "<br/><b>".__("Archive:",wordspew)."</b>";
	foreach( $theme as $theme_name ) {
		if($theme_name->cat=="") echo ' <a class="archive" onclick="CountAndGo(\'\',\''.__("Miscellaneous",wordspew).'\');"><b>'.__("Miscellaneous",wordspew).'</b></a>, ';
		else echo ' <a class="archive" onclick="CountAndGo(\''.$theme_name->cat.'\',\''.str_replace(" "," ",$theme_name->cat).'\');">'.stripslashes($theme_name->cat).'</a>,';
		$first_time+=1;
	}
	if($first_time>=1) echo "<br/>".__("<b>Information:</b> The first click on the desired shoutbox category will select it (if you want to empty its content for example), the second will let you browse to the archive page selected.",wordspew);
	printf(__('<div id="lastMessage"><span>Last Message</span><br/><div id="responseTime">%s</div>&nbsp;ago</div>',wordspew),jal_time_since($_SESSION['Chrono']));

	$class_id=(strpos($_SERVER['HTTP_REFERER'],"wordspew_archive.php")) ? "archive" : "shout";
	echo '<p id="usersOnline">'.jal_get_useronline_extended().'</p>
	<div id="theme" class="'.$class_id.'">'.$the_cat.'</div>
	<hr/>

	<div align="right" id="chatoutput">
	<ul id="outputList" style="white-space: nowrap;">';

	foreach( $results as $r ) { // Loops the messages into a list
		$class="";
		if(verifyName($r->name)) {
			$class="jal_user ";
		}
		$url = (empty($r->url)) ? $r->name : '<a href="'.$r->url.'" target="_blank">'.$r->name.'</a>';
		if ($jal_first_time) {
			$lastID = $r->id;
		}
	echo '<li id="comment-new'.$r->id.'">

	<span title="'.jal_time_since( $r->time ).'" class="'.$class. sanitize_name($r->name).'" id="user_'.$r->id.'">'.stripslashes($url).' : </span>
	<a href="http://whois.domaintools.com/'.$r->ipaddr.'" target="_blank" title="Whois">*</a>
	<input type="text" name="jal_text" id="text_'.$r->id.'" value="'.htmlspecialchars(stripslashes($r->text),ENT_QUOTES).'" size="60"/>';
	if(current_user_can('manage_options')) {
		echo '
		<input type="text" name="ip" id="ip_'.$r->id.'" value="'.$r->ipaddr.'" size="14"/>
		<input type="button" name="jal_ban" value="'.__("Ban this IP",wordspew).'" onclick="BanIP('.$r->id.',\''.$r->ipaddr.'\')"/>';
	}
	else echo '<input type="hidden" name="ip" id="ip_'.$r->id.'" value="'.$r->ipaddr.'"/>';
	echo '
	<input type="button" name="jal_delete" value="'.__("Delete",wordspew).'" onclick="deleteComment('.$r->id.')"/>
	<input type="button" name="jal_edit" value="'.__("Edit",wordspew).'" onclick="EditComment('.$r->id.')"/></li>
	'; 
	$jal_first_time = false; }
	
	if(!$results) {
		echo '<li>&nbsp;</li>';
	}
	?>
	</ul></div>
	<input type="hidden" id="jal_lastID" value="<?php echo $lastID + 1; ?>" name="jal_lastID"/>
	</form>
	</fieldset>
	</div>
<?php } 
}

// To add administration page under Management Section
function shoutbox_admin_page() {
	$jal_admin_user_level = (get_option('shoutbox_admin_level')!="") ? get_option('shoutbox_admin_level') : 10;
	add_management_page('Shoutbox Management', 'Live Shoutbox', $jal_admin_user_level, "wordspew_admin", 'jal_shoutbox_admin');
}
function ShoutboxHash($nc, $a='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
    $l=strlen($a)-1; $r='';
    while($nc-->0) $r.=$a{mt_rand(0,$l)};
    return $r;
}
/* Widget */
if (function_exists("add_action")) {
	include ('widgetized.php');
	add_action("plugins_loaded","jal_on_plugins_loaded");
}
/* End Widget */

function jal_admin_options() {
global $wpdb, $user_level, $shout_tb;

	$jal_admin_user_level = (get_option('shoutbox_admin_level')!="") ? get_option('shoutbox_admin_level') : 10;
	$shout_opt = get_option('shoutbox_options');
	$oldOption=$shout_opt;

    // Security
    get_currentuserinfo();
	$CSS=false;
	$current=current_user_can('level_'.$jal_admin_user_level);
    if ($user_level <  $jal_admin_user_level && $current!=1) die(__("Cheatin' uh ?"));

	if(!is_numeric($_POST['nb_comment']))
		$nb_comment=35;
	else
		$nb_comment=intval($_POST['nb_comment']);

	// Convert from milliseconds
	$fade_length = $_POST['fade_length'] * 1000;
	$update_seconds = $_POST['update_seconds'] * 1000;

	// Update choices from admin panel
	$shout_opt['fade_from']		=$_POST['fade_from'];
	$shout_opt['fade_to']		=$_POST['fade_to'];
	$shout_opt['update_seconds']=$update_seconds;
	$shout_opt['fade_length']	=$fade_length;
	$shout_opt['text_color']	=$_POST['text_color'];
	$shout_opt['name_color']	=$_POST['name_color'];

	if($oldOption['fade_from']!=$shout_opt['fade_from'] || $oldOption['fade_to']!=$shout_opt['fade_to'] ||
	$oldOption['text_color']!=$shout_opt['text_color'] || $oldOption['name_color']!=$shout_opt['name_color']) $CSS=true;

	$shout_opt['use_url'] 			= ($_POST['use_url']) ? 1 : 0;
	$shout_opt['use_textarea'] 		= ($_POST['use_textarea']) ? 1 : 0;
	$shout_opt['registered_only']	= ($_POST['registered_only']!="") ? intval($_POST['registered_only']) : -1;
	$shout_opt['use_sound'] 		= ($_POST['use_sound']) ? 1 : 0;
	$shout_opt['xhtml']				= ($_POST['XHTML']) ? 1 : 0;
	$shout_opt['show_user_online']	= ($_POST['Show_Users']) ? 1 : 0;
	$shout_opt['show_smiley']		= ($_POST['Show_Smiley']) ? 1 : 0;
	$shout_opt['show_spam']			= ($_POST['Show_Spam']) ? 1 : 0;
	$shout_opt['use_captcha']		= ($_POST['Captcha']) ? 1 : 0;
	$shout_opt['use_rss']			= ($_POST['Use_RSS']) ? 1 : 0;
	$shout_opt['use_theme']			= ($_POST['Use_Theme']) ? 1 : 0;
	$shout_opt['use_filters']		= ($_POST['Use_Filters']) ? 1 : 0;

	if($CSS) $shout_opt['cssDate']=time();

	if(isset($_POST['HideUsers'])) {
		$shout_opt['hidden_users']	= $_POST['HideUsers'];
		$users = str_replace(", ", ",", $shout_opt['hidden_users']);
		$UsersToHide = stripslashes($users);
		$_SESSION['HideUsers'.$shout_tb]=explode(",",strtolower($UsersToHide));
	}

	$where=explode(',',$_POST['where']);
	$swhere="";
	foreach ($where as $s) {
		$swhere.=trim($s)!="" ? trim($s).", " : "";
	}
	$shout_opt['where']=substr($swhere,0,-2);

	$shout_opt['level_for_shoutbox']=($_POST['level_for_shoutbox']!="") ? intval($_POST['level_for_shoutbox']) : -1;
	$shout_opt['show_avatar']=($_POST['Use_Avatar']) ? 1 : 0;
	$shout_opt['avatar_size']=($_POST['Avatar_size']) ? intval($_POST['Avatar_size']) : 16;
	$shout_opt['avatar_position']=($_POST['Avatar_position']) ? $_POST['Avatar_position'] : "left";

	$jal_admin_user_level=($_POST['admin_user_level']!="") ? intval($_POST['admin_user_level']) : $jal_admin_user_level;
	$shout_opt['level_for_archive']=($_POST['Show_archive_to']!="") ? intval($_POST['Show_archive_to']) : $jal_admin_user_level;
	$shout_opt['level_for_theme']=($_POST['Show_themes_to']!="") ? intval($_POST['Show_themes_to']) : $jal_admin_user_level;


	update_option ('shoutbox_admin_level', $jal_admin_user_level);
	update_option ('shoutbox_nb_comment', $nb_comment);

	ksort ($shout_opt);
	update_option ('shoutbox_options', $shout_opt);
	return $shout_opt;
}

function jal_shout_truncate() {
global $wpdb, $user_level;

	$jal_admin_user_level = (get_option('shoutbox_admin_level')!="") ? get_option('shoutbox_admin_level') : 10;
	// Security
	get_currentuserinfo();
	$current=current_user_can('level_'.$jal_admin_user_level);
    if ($user_level <  $jal_admin_user_level && $current!=1) die(__("Cheatin' uh ?"));

	@mysql_query("SET CHARACTER SET 'utf8'");
	@mysql_query("SET NAMES utf8");
	$thetable="liveshoutbox";
	if(isset($_POST['jal_truncate_archive'])) $thetable.="archive";
	$SQL="DELETE FROM ".$_SESSION['tb_prefix'].$thetable." WHERE cat='".mysql_real_escape_string($_POST['cat'])."'";
	$wpdb->query($SQL);
}

function jal_shout_archive() {
global $wpdb, $user_level;

	$jal_admin_user_level = (get_option('shoutbox_admin_level')!="") ? get_option('shoutbox_admin_level') : 10;
	// Security
	get_currentuserinfo();
	$current=current_user_can('level_'.$jal_admin_user_level);
    if ($user_level <  $jal_admin_user_level && $current!=1) die(__("Cheatin' uh ?"));

	$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	mysql_select_db(DB_NAME, $conn);

	@mysql_query("SET CHARACTER SET 'utf8'");
	@mysql_query("SET NAMES utf8");

	$SQL ="INSERT INTO ".$_SESSION['tb_prefix']."liveshoutboxarchive (time,name,text,url,ipaddr,email,cat) SELECT time,name,text,url,ipaddr,email,cat FROM ".$_SESSION['tb_prefix']."liveshoutbox WHERE cat='".mysql_real_escape_string($_POST['cat'])."';";
	mysql_query($SQL, $conn);

	$SQL="DELETE FROM ".$_SESSION['tb_prefix']."liveshoutbox WHERE cat='".mysql_real_escape_string($_POST['cat'])."'";
	mysql_query($SQL, $conn);
}

function jal_shout_edit($id, $ip, $text) {
$shout_tb=$_POST['tb'];
	if($_SESSION['isAdmin'.$shout_tb]==true) {
		$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
		mysql_select_db(DB_NAME, $conn);

		@mysql_query("SET CHARACTER SET 'utf8'");
		@mysql_query("SET NAMES utf8");

		$SQL="UPDATE ".$shout_tb."liveshoutbox SET text = '".mysql_real_escape_string($text)."',";
		$SQL.="ipaddr='".mysql_real_escape_string($ip)."' WHERE id = ".intval($id);
		mysql_query($SQL, $conn);
	}
}

function jal_shout_spam($id, $ip) {
$shout_tb=$_POST['tb'];
	if($_SESSION['isAdmin'.$shout_tb]==true) {
		$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
		mysql_select_db(DB_NAME, $conn);

		@mysql_query("SET CHARACTER SET 'utf8'");
		@mysql_query("SET NAMES utf8");

		mysql_query("DELETE FROM ".$shout_tb."liveshoutbox WHERE id = ".intval($id), $conn);

		$SQL="SELECT option_value FROM ".$shout_tb."options WHERE option_name = 'moderation_keys'";
		$spam=mysql_query($SQL,$conn);
		$sql_create_arr = mysql_fetch_array($spam);
		$spam_ar= $sql_create_arr[0];
		$ar=explode("\r\n",strtolower($spam_ar));
		$spam=mysql_result($spam,0);

		if(!in_array($ip, $ar)) {
			$SQL="UPDATE ".$shout_tb."options SET option_value='".$ip."\r\n".$spam."' WHERE option_name = 'moderation_keys'";
			mysql_query($SQL,$conn);
		}
	}
}

if (function_exists('add_action')) {
	add_action('admin_menu', 'shoutbox_admin_page');
	if (strstr($_SERVER['REQUEST_URI'], 'wordspew_admin'))
	   add_action('admin_head', 'jal_add_to_admin_head');
}
// If user has updated the admin panel
if (isset($_POST['jal_admin_options']))
    add_action('init', 'jal_admin_options');

// If someone has clicked the "delete all" button
if (isset($_POST['jal_truncate']) || isset($_POST['jal_truncate_archive']))
    add_action('init', 'jal_shout_truncate');

if (isset($_POST['jal_shout_archive']))
    add_action('init', 'jal_shout_archive');

if ((isset($_GET['activate']) && $_GET['activate'] == 'true') || (isset($_GET['activate-multi']) && $_GET['activate-multi'] == 'true')) {
	add_action('init', 'jal_install_shout');
}
?>