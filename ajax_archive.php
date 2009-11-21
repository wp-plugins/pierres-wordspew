<?php
require('../../../wp-config.php');
header("Cache-Control: must-revalidate");
// expire every 60 days
$offset = 60*60*24*60;
$ExpStr = gmdate("D, d M Y H:i:s",time() + $offset)." GMT";
$last_modified_time = gmdate("D, d M Y H:i:s",filemtime($_SERVER['SCRIPT_FILENAME']))." GMT";

header("Last-Modified: ".$last_modified_time);
header("Cache-Control: max-age=".$offset.", must-revalidate");
header("Pragma: private");
header("Expires: ".$ExpStr);
header('Content-Type: application/x-javascript; charset='.get_option('blog_charset'));
?>
var SendChaturl = "<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/pierres-wordspew/wordspew.php?jalSendChat=yes";
var httpSendChat;
var tb_prefix="<?php echo $_SESSION['tb_prefix']; ?>";

if(typeof window.addEventListener != 'undefined') {
	window.addEventListener('load', initJavaScript, false);
}
else if(typeof document.addEventListener != 'undefined') {
	document.addEventListener('load', initJavaScript, false);
}
else if(typeof window.attachEvent != 'undefined') {
	window.attachEvent('onload', initJavaScript);
}

function initJavaScript() {
	httpSendChat = getHTTPObject();
}

function deleteComment(id, offset, limit) {
	theHTML=document.getElementById('comment-new'+id).innerHTML;
	var HtmlText = theHTML.replace(/<[a-zA-Z\/][^>]*>x?/g, "");
	AlertMsg="<?php _e('Are you sure to delete the message:',wordspew); ?> \n"+ HtmlText+"\n";
	AlertMsg+="<?php _e('\'Cancel\' to stop, \'OK\' to delete.',wordspew); ?>";
	if(confirm(AlertMsg)) {
		if (httpSendChat.readyState == 4 || httpSendChat.readyState == 0) {
			param = 'mode=del&id='+ encodeURIComponent(id)+'&tb='+tb_prefix;
			httpSendChat.open("POST", SendChaturl, true);
			httpSendChat.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
			httpSendChat.send(param);
			Fat.fade_element('comment-new'+id,30,1000,"#FF0000");
			setTimeout('delChild('+id+','+offset+','+limit+')',1000);
		}
	}
}

function delChild(id, offset, limit) {
	var enfant = document.getElementById("comment-new"+id);
	var anciennoeud = enfant.parentNode.removeChild(enfant);
	var ElemCount=document.getElementById("count");
	var compteur=parseInt(ElemCount.innerHTML)-1;
	ElemCount.innerHTML=compteur;
	if (compteur<=(offset + limit)) {
		if(document.getElementById("older")) {
			var enfant = document.getElementById("older");
			var anciennoeud = enfant.parentNode.removeChild(enfant);
		}
	}
}