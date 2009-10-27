<?php

include_once ('wordspew.php');

function widget_wordspew($args) {
global $user_ID, $user_level, $shout_opt, $shout_where;
	if(where_shout($shout_opt['where'],0)) {
		extract($args);
		$jal_wp_url = get_bloginfo('wpurl').'/wp-content/plugins/pierres-wordspew';
		$UseRSS=$shout_opt['use_rss'];
		$show_to_level=$shout_opt['level_for_shoutbox'];
		$user_level=isset($user_level) ? $user_level : -1;
		$current=($show_to_level==-1) ? 1 : current_user_can('level_'.$show_to_level);

		if ($user_level >= $show_to_level || $current==1) {
		$Woptions = get_option('widget_wordspew');
		$title = $Woptions['title'];
		$RSSLink="";
		$libcat=" onmouseover=\"ChangeURL('shoutboxRSS','".$jal_wp_url."/wordspew-rss.php','?shout_cat=')\"";
		if ($UseRSS=='1') $RSSLink=' <a href="'.$jal_wp_url.'/wordspew-rss.php"'.$libcat.' id="shoutboxRSS"><img 
		src="'.$jal_wp_url.'/img/rss.gif" border="0" alt="" title="'.__('Wordspew-RSS-Feed for:', wordspew).' ' . get_bloginfo('name').'"/></a>';
		
		echo $before_widget . $before_title . $title . $RSSLink . $after_title;
		jal_get_shoutbox($shout_where);
		echo $after_widget;
		}
	}
}

function widget_wordspew_control() {
	$shout_opt = get_option('widget_wordspew');
	if ( !is_array($shout_opt) )
		$shout_opt = array('title'=>'ShoutBox');
	if ( $_POST['wordspew-submit'] ) {
		$shout_opt['title'] = strip_tags(stripslashes($_POST['wordspew-title']));
		update_option('widget_wordspew', $shout_opt);
	}

	$title = htmlspecialchars($shout_opt['title'], ENT_QUOTES);

	echo '<p><label for="wordspew-title">';
	_e('Title:',wordspew);
	echo ' <input style="width: 200px;" id="wordspew-title" name="wordspew-title" type="text" value="'.$title.'" /></label></p>
		  <input type="hidden" id="wordspew-submit" name="wordspew-submit" value="1" />';
}

function jal_on_plugins_loaded() {
	if (function_exists('register_sidebar_widget')) {
		register_sidebar_widget("Shoutbox",'widget_wordspew');
	}
	if (function_exists('register_widget_control')) {
		register_widget_control("Shoutbox", "widget_wordspew_control", 250, 80);
	}
}
?>