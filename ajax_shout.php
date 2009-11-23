<?php
// Why is this file php? so that wordpress can find its url and print it for the ajax...this saves
// some setup time in if you have a blog not in the root
// The headers below cache the file and make it javascript
require('../../../wp-config.php');
// expire every 30 days
$offset = 60*60*24*30;
$ExpStr = gmdate("D, d M Y H:i:s",time() + $offset)." GMT";
$last_modified_time = gmdate("D, d M Y H:i:s",filemtime($_SERVER['SCRIPT_FILENAME']))." GMT";

header("Last-Modified: ".$last_modified_time);
header("Cache-Control: max-age=".$offset.", must-revalidate");
header("Pragma: private");
header("Expires: ".$ExpStr);
header('Content-Type: application/x-javascript; charset='.get_option('blog_charset'));

define('wordspew', 'pierres-wordspew/lang/wordspew');
if(function_exists('load_plugin_textdomain')) load_plugin_textdomain(wordspew);
$pathtoSmiley = get_bloginfo('wpurl');
$PathToPlugin = $pathtoSmiley."/wp-content/plugins/pierres-wordspew";
$pathtoSmiley.=(version_compare($wp_version, '1.5', '>')) ? "/wp-includes/images/smilies/" : "/wp-images/smilies/";
$shout_opt = get_option('shoutbox_options');
?>
var myBox = new Object();
myBox.onInit = function() {}
//////////////////////////////////
// Generic onload by Brothercake
// http://www.brothercake.com/
////////////////////////////////
if(typeof window.addEventListener != 'undefined') {
	//.. gecko, safari, konqueror and standard
	window.addEventListener('load', initJavaScript, false);
}
else if(typeof document.addEventListener != 'undefined') {
	//.. opera 7
	document.addEventListener('load', initJavaScript, false);
}
else if(typeof window.attachEvent != 'undefined') {
	//.. win/ie
	window.attachEvent('onload', initJavaScript);
}
function jal_apply_filters(s) {
	return filter_smilies(make_links((s)));
}
var smilies=[<?php
if(is_array($wpsmiliestrans)) {
    // Get smileys information from Wordpress
	natsort($wpsmiliestrans);
    $strFatSmilies = '';
    foreach($wpsmiliestrans as $tag => $file) {
        $strFatSmilies .= "['".trim(str_replace("'","\'",$tag))."', '".trim($file)."'],"."\r\n";
    }
    $strFatSmilies = substr($strFatSmilies, 0, - 3);
    echo $strFatSmilies;
	}
?>
];

function make_links (s) {
	target="";
	if(var_XHTML==0) if (s.indexOf(this.location.href)==-1) target=' target="_blank"';
	var re = /((http|https|ftp):\/\/[^ ]*)/gi;	
	text = s.replace(re,"<a href=\"$1\""+target+">&laquo;<?php _e('link',wordspew);?>&raquo;</a>");
	return text;
}

var PathToSmiley="<?php echo $pathtoSmiley; ?>";

function filter_smilies(s) {
	for (var i = 0; i < smilies.length; i++) {
		var replace = '<img src="'+PathToSmiley + smilies[i][1] + '" class="wp-smiley" alt="[smiley]" />';
		var search = smilies[i][0].replace(/(\(|\)|\$|\?|\*|\+|\^|\[|\.|\|)/gi, "\\$1");
		re = new RegExp(search, 'gi');
		s = s.replace(re, replace);
	}
	var re =/([_.0-9a-z-]+@([0-9a-z][0-9a-z-]+.)+(\.[-a-z0-9]+)*\.[a-z]{2,6})/gi;
	s = s.replace(re,"<a href=\"mailto:$1\">&laquo;<?php _e('email',wordspew);?>&raquo;</a>");
	return s;
}

// XHTML live Chat
// author: alexander kohlhofer
// version: 1.0
// http://www.plasticshore.com
// http://www.plasticshore.com/projects/chat/
// please let the author know if you put any of this to use
// XHTML live Chat (including this script) is published under a creative commons license
// license: http://creativecommons.org/licenses/by-nc-sa/2.0/

var dateExp = new Date();
dateExp.setTime(dateExp.getTime()+<?php echo ($offset*1000);?>);
var DateExpires = dateExp.toGMTString();

var oldval, is_new=1;
var jal_loadtimes;
var jal_timeout = jal_org_timeout;

var tb_prefix="<?php echo $_SESSION['tb_prefix']; ?>";

var GetChaturl = "<?php echo $PathToPlugin; ?>/wordspew.php?jalGetChat=yes";
var SendChaturl = "<?php echo $PathToPlugin; ?>/wordspew.php?jalSendChat=yes";
var httpReceiveChat, httpSendChat;
var jalSound, shoutboxname, shoutboxU, chatbarText, jal_lastID, outputList, shout_theme;

function initJavaScript() {
shoutboxname=document.getElementById('shoutboxname');
shoutboxU=document.getElementById('shoutboxU');
chatbarText=document.getElementById('chatbarText');
jal_lastID=document.getElementById('jal_lastID');
outputList=document.getElementById("outputList");
shout_theme=document.getElementById('shout_theme');

	if (!chatbarText) { return; }
	if(shout_theme) shout_theme.style.display="";
//this non standard attribute prevents firefox' autofill function to clash with this script
	document.forms['chatForm'].elements['chatbarText'].setAttribute('autocomplete','off');

	checkStatus('');
	checkName();
	checkUrl();
	jalSound = (jal_getCookie("jalSound"+tb_prefix)==null || jal_getCookie("jalSound"+tb_prefix)==1) ? 1 : 0;
	jal_loadtimes = 1;

// initiates the two objects for sending and receiving data
	httpReceiveChat = getHTTPObject();
	httpSendChat = getHTTPObject();

	setTimeout('receiveChatText()', jal_timeout); //initiates the first data query

	shoutboxname.onblur = checkName;
	shoutboxU.onblur = checkUrl;
	chatbarText.onfocus = function () { checkStatus('active'); }	
	chatbarText.onblur = function () { checkStatus(''); }
	document.getElementById('submitchat').onclick = sendComment;
	document.getElementById('chatForm').onsubmit = function () { return false; }
// When user mouses over shoutbox
	document.getElementById('chatoutput').onmouseover = ResetChrono;
	
	var obj = "";
	if(show_smiley==1) {
		ActualSmile="", style="", lib="-";
		if (jal_getCookie("jalSmiley"+tb_prefix)==1) {
			style="display:none";
			lib="+"
		}
		obj+="<a href=\"javascript:ShowHide('SmileyParent','SmileyChild')\" id=\"SmileyParent\" ";
		obj+="title=\"<?php _e('Click here to expand/collapse the smiley\'s list',wordspew);?>\">"+lib+"</a> Smileys :";
		obj+="<div id='SmileyChild' style=\""+style+"\">";
		for (var i = 0; i < smilies.length; i++) {
			if(ActualSmile!=smilies[i][1]) {
				obj+="<a href=\"javascript:appendSmiley('"+smilies[i][0].replace("'","\\'")+"')\">";
				obj+="<img src=\""+PathToSmiley+smilies[i][1]+"\" alt=\"\" class=\"wp-smiley\"/></a> ";
			}
			ActualSmile=smilies[i][1];
		}
		obj+="</div>"	
		if(document.getElementById("SmileyList")) document.getElementById("SmileyList").innerHTML=obj;
	}
}

function ResetChrono() {
	if (jal_loadtimes > 9) {
		jal_loadtimes = 1;
		receiveChatText();
	}
	jal_timeout = jal_org_timeout;
}

function appendSmiley(text) {
	chatbarText.value+=' '+text;
	chatbarText.focus();
}
	
function ShowHide(parent, enfant) {
	txtParent=document.getElementById(parent).innerHTML;
	etatEnfant=document.getElementById(enfant).style.display;
	document.getElementById(parent).innerHTML=(txtParent=="+") ? "-" : "+";
	document.getElementById(enfant).style.display=(etatEnfant=="none") ? "" : "none";
	jalSmiley = (jal_getCookie("jalSmiley"+tb_prefix)==1) ? 0 : 1;
	document.cookie = "jalSmiley"+tb_prefix+"="+jalSmiley+";expires="+DateExpires+";path=/;";
}

//initiates the first data query
function receiveChatText() {
	lastID = parseInt(jal_lastID.value)-1;
	MyCat=encodeURIComponent(document.getElementById("shout_cat").value);

	if (httpReceiveChat.readyState == 4 || httpReceiveChat.readyState == 0) {
		httpReceiveChat.open("GET",GetChaturl+'&jal_lastID='+lastID+'&shout_cat='+MyCat+'&tb='+tb_prefix+'&rand='+Math.floor(Math.random() * 1000000), true);
		httpReceiveChat.onreadystatechange = handlehHttpReceiveChat; 
		httpReceiveChat.send(null);
		jal_loadtimes++;
		if (jal_loadtimes > 9) jal_timeout = jal_timeout * 5 / 4;
	}
	setTimeout('receiveChatText()',jal_timeout);
}

//deals with the servers' reply to requesting new content
function handlehHttpReceiveChat() {
	if (httpReceiveChat.readyState == 4) {
		firstarray = httpReceiveChat.responseText.split('\n');
		if (firstarray.length == 2) { // if != 2, it failed, we should skip the processing part
			results = firstarray[0].split('---');
			replaceUserOnline(results[0],results[1]);
			results = firstarray[1].split('---'); //the fields are seperated by ---
			if (results.length > 4) {
				for(i=0;i < (results.length-1);i=i+8) { //goes through the result one message at a time
				insertNewContent(results[i+1],results[i+2],results[i+3], results[i], results[i+4], results[i+5], results[i+6]);
				jal_lastID.value = parseInt(results[i])+1;
				}
				jal_timeout = jal_org_timeout;
				jal_loadtimes = 1;
			}
			else if(results.length==3) {
				insertNewContent(results[1], results[2], "", parseInt(results[0])+1,"","");
				jal_lastID.value = parseInt(results[0])+1;
			}
			//is_new=1;
		}
	}
}

function setSound() {
pathToImg="<?php echo $PathToPlugin; ?>/img/";
jalSound = (jal_getCookie("jalSound"+tb_prefix)=="" || jal_getCookie("jalSound"+tb_prefix)==0) ? 1 : 0;
document.cookie = "jalSound"+tb_prefix+"="+jalSound+";expires="+DateExpires+";path=/;";
document.getElementById('JalSound').src=(jalSound==1) ? pathToImg+"sound_1.gif": pathToImg+"sound_0.gif";
}

//inserts the new content into the page
function insertNewContent(liName, liText, liUrl, liId, liUser, liEmail, liTime) {
var myClass="";
if(liUser==1) myClass="jal_user ";
myClass+=liName;
verif=true;
	if (liText.substr(0,2)=="@@") {
		verif=false;
		PosSpace=liText.indexOf(" ");
		To=liText.substr(2,PosSpace-2);
		Deb=2+(To.length);
		shout_user=shout_user.toLowerCase();
		if(shout_user==To.toLowerCase() || isAdmin==true || shout_user==liName.toLowerCase()) {
			verif=true;
			liText='<span class="InfoUser"><?php $chaine=__("Private message for", wordspew);
			echo str_replace("'","\'",$chaine); ?> '+To+':</span>'+liText.substr(Deb); 
		}
	}
if (verif==true) {
	if(document.getElementById("TheBox") && jalSound==1 && is_new==1) {
		document.getElementById("TheBox").SetVariable("method:setUrl", "<?php echo $PathToPlugin; ?>/msg.mp3");
		document.getElementById("TheBox").SetVariable("method:play", "");
	}
	oLi = document.createElement('li');
	oLi.setAttribute('id','comment-new'+liId);
	
	if (show_avatar==1) {
		if(liEmail!="") {
			avatar = 'http://www.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536?s='+avatar_size;
			oDiv = document.createElement('div');
			oDiv.setAttribute('class','ps_'+avatar_position);
			oDiv.setAttribute('className','ps_'+avatar_position);
			oImg = document.createElement('img');
			oImg.setAttribute('src','http://www.gravatar.com/avatar.php?gravatar_id='+liEmail+'&default='+avatar+'&size='+ avatar_size);
			oDiv.appendChild(oImg);
			oLi.appendChild(oDiv);
		}
	}

	oSpan = document.createElement('span');
	oSpan.setAttribute('class',myClass);
	oSpan.setAttribute('title',liTime);

	oName = document.createTextNode(liName);

	if (liUrl != "http://" && liUrl != '') {
		oURL = document.createElement('a');
		oURL.href = liUrl;
		if(var_XHTML==0) if (liUrl.indexOf(this.location.href)==-1) oURL.setAttribute('target','_blank');
		oURL.appendChild(oName);
	} else {
		oURL = oName;
	}

	oSpan.appendChild(oURL);
	oSpan.appendChild(document.createTextNode(' : '));
	oLi.appendChild(oSpan);
	oLi.innerHTML += jal_apply_filters(liText);

	if(isAdmin==true) {
		oSpan = document.createElement('span');
		oSpan.setAttribute('class','delShout');
		oSpan.setAttribute('className','delShout');
		oSpan.setAttribute('title','<?php _e("Delete",wordspew);?>');
		oSpan.onclick = function () { deleteComment(liId); }
		oSpan.appendChild(document.createTextNode('x'));
		oLi.appendChild(oSpan);
	}

	outputList.insertBefore(oLi, outputList.firstChild);
	if(is_new==1) 
		Fat.fade_element("comment-new"+liId, 30, fade_length, "#"+fade_from, "#"+fade_to);
	}
}

function MasqueSelect() {
	mabox=document.getElementById('shoutboxOp');
	posEgal=mabox.options[0].text.indexOf("=");
	if(mabox.options[mabox.selectedIndex].value==eval(mabox.options[0].text.substr(0,posEgal)))
	document.getElementById('shoutbox_captcha').style.display="none";
}
//stores a new comment on the server
function sendComment() {
	currentChatText = chatbarText.value;
	currentUrl = shoutboxU.value;
	currentName = shoutboxname.value;
	shoutboxOp = document.getElementById('shoutboxOp').value;
	shoutboxControl= document.getElementById('shoutboxControl').value;
	MyCat=document.getElementById('shout_cat').value;

	if (currentChatText == '') return;
	if(CheckSpam(currentName+' '+currentChatText, currentUrl)) {
		if (httpSendChat.readyState == 4 || httpSendChat.readyState == 0) {
			param = 'n='+ encodeURIComponent(currentName)+'&tb='+tb_prefix+'&c='+ encodeURIComponent(currentChatText) +'&u='+ encodeURIComponent(currentUrl)+'&shoutboxOp='+encodeURIComponent(shoutboxOp)+'&shoutboxControl='+encodeURIComponent(shoutboxControl)+'&shout_cat='+encodeURIComponent(MyCat);	
			httpSendChat.open("POST", SendChaturl, true);
			httpSendChat.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
			httpSendChat.onreadystatechange = receiveChatText;
			httpSendChat.send(param);
			chatbarText.value = '';
		}
	}
}

function deleteComment(id) {
	theHTML=document.getElementById('comment-new'+id).innerHTML;
	var HtmlText = theHTML.replace(/<[a-zA-Z\/][^>]*>x?/g, "");
	AlertMsg="<?php _e('Are you sure to delete the message:',wordspew); ?> \n"+ HtmlText+"\n\n";
	AlertMsg+="<?php _e('\'Cancel\' to stop, \'OK\' to delete.',wordspew); ?>";
	if(confirm(AlertMsg)) {
		if (httpSendChat.readyState == 4 || httpSendChat.readyState == 0) {
			param = 'mode=del&id='+ encodeURIComponent(id)+'&tb='+tb_prefix;
			httpSendChat.open("POST", SendChaturl, true);
			httpSendChat.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
			httpSendChat.onreadystatechange = receiveChatText;
			httpSendChat.send(param);
			Fat.fade_element('comment-new'+id,30,1000,"#FF0000");
			setTimeout('delChild('+id+')',1000);
		}
	}
}

function delChild(id) {
	var enfant = document.getElementById("comment-new"+id); 
	var anciennoeud = outputList.removeChild(enfant);
}

// Piece of code from "TinyMCE Advanced" plugin. Thanks to -> Andrew Ozz : http://www.laptoptips.ca/
function onBlurEditableSelectInput () {
MyCat=document.getElementById("shout_cat_theme");
	if (MyCat.previousSibling.value != '') {
		var o = new Option(MyCat.previousSibling.value, MyCat.previousSibling.value);
		MyCat.options[MyCat.options.length] = o;
		optionvalue=MyCat.previousSibling.value
	}
	else optionvalue=oldval;
	
	for (var i=0; i<MyCat.options.length; i++) {
		var option = MyCat.options[i];
		if (option.value ==optionvalue )
			option.selected = true;
		else
			option.selected = false;
	}

	MyCat.style.display = 'inline';
	MyCat.parentNode.removeChild(MyCat.previousSibling);
	chatbarText.focus();
	CleanBox();
}

function onKeyDown(e) {
	e = e || window.event;
	if (e.keyCode == 13)
		onBlurEditableSelectInput();
}

function CleanBox() {
var new_val;

document.getElementById("shout_cat").value=document.getElementById("shout_cat_theme").value;

MyCat=document.getElementById("shout_cat_theme");
if (MyCat.options[MyCat.selectedIndex].value == 'add_custom') {
	new_val = document.createElement("input");
	new_val.id = MyCat.id + "_custom";
	new_val.name = MyCat.name + "_custom";
	new_val.type = "text";

	new_val.style.width = MyCat.offsetWidth + 'px';
	MyCat.parentNode.insertBefore(new_val, MyCat);
	MyCat.style.display = 'none';
	new_val.focus();
	new_val.onblur = onBlurEditableSelectInput;
	new_val.onkeydown = onKeyDown;
}
else {
	if(MyCat.value != oldval) {
		mycook=encodeURIComponent(MyCat.value);
		document.cookie = "jalCombo="+mycook+";expires="+DateExpires+";path=/;"
		jal_lastID.value=0;
		is_new=0;
		while (outputList.firstChild) {
			outputList.removeChild(outputList.firstChild);
		}
		receiveChatText();
	}
}
}

// http://www.codingforums.com/showthread.php?t=63818
function pressedEnter(field,event) {
	var theCode = event.keyCode ? event.keyCode : event.which ? event.which : event.charCode;
	if (theCode == 13) {
		is_new=1;
		sendComment();
		return false;
	} 
	else return true;
}

//does clever things to the input and submit
function checkStatus(focusState) {
	oSubmit = document.forms['chatForm'].elements['submit'];
	if (chatbarText.value != '' || focusState == 'active') {
		oSubmit.disabled = false;
	} else {
		oSubmit.disabled = true;
	}
}

function jal_getCookie(name) {
	var dc = document.cookie;
	var prefix = name + "=";
	var begin = dc.indexOf("; " + prefix);
	if (begin == -1) {
		begin = dc.indexOf(prefix);
		if (begin != 0) return null;
	} else
		begin += 2;
	var end = document.cookie.indexOf(";", begin);
	if (end == -1)
		end = dc.length;
	return unescape(decodeURI(dc.substring(begin + prefix.length, end)));
}

//autoasigns a random name to a new user
//If the user has chosen a name, use that
function checkName() {
	jalCookie = jal_getCookie("jalUserName");
	shout_user=shoutboxname.value;
	if (jalCookie && shoutboxname.value == '') {
		shoutboxname.value = jalCookie;
	}
	else {
		if(shout_user=='') shoutboxname.value=Old_Sname; 
		else Old_Sname=shout_user;
		}
}

function checkUrl() {
	if(shoutboxU.style.display!="none") {
		document.cookie = "jalUrl="+shoutboxU.value+";expires="+DateExpires+";path=/;"
		return;
	}
}