var httpReceiveChat;
var httpSendChat;

var from=false;
var jal_loadtimes;
var jal_org_timeout = 4000;
var jal_timeout = jal_org_timeout;

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
	disable_enable("Show_Users", "HideUsers", true);
	disable_enable("level_for_shoutbox", "registered_only", false);
	disable_enable("Use_Theme", "Show_themes_to", true);
	from=true;

	httpReceiveChat = getHTTPObject();
	httpSendChat = getHTTPObject();
	jal_loadtimes = 1;

	setTimeout('receiveChatText()', jal_timeout); //initiates the first data query

	document.getElementById('chatoutput').onmouseover = function () {
		if (jal_loadtimes > 9) {
			jal_loadtimes = 1;
			receiveChatText();
		}
		jal_timeout = jal_org_timeout;
	}
}

function disable_enable(objCheckbox, objTextfield, boleen) {
inv=(boleen==true) ? false : true;
if(document.getElementById(objCheckbox) && document.getElementById(objTextfield)) {
	if(objCheckbox=="level_for_shoutbox") {
		ok=document.getElementById(objCheckbox).options[document.getElementById(objCheckbox).selectedIndex].text;
		document.getElementById("From_List").innerHTML=ok;

		look=parseInt(document.getElementById(objCheckbox).options[document.getElementById(objCheckbox).selectedIndex].value);
		arch=parseInt(document.getElementById("Show_archive_to").options[document.getElementById("Show_archive_to").selectedIndex].value);

		if(document.getElementById(objCheckbox).value==-1) {
			document.getElementById("Info").style.display="none";
			}
		else {
			if(look > arch)
				document.getElementById("Info").style.display="";
			else
				document.getElementById("Info").style.display="none";
			}
		}
	else
		document.getElementById(objTextfield).disabled = (document.getElementById(objCheckbox).checked) ? inv : boleen;

	if(document.getElementById(objTextfield).disabled==true)
		document.getElementById(objTextfield).style.backgroundColor = '#ccc';
	else {
		document.getElementById(objTextfield).style.backgroundColor = '#fff';
		if(boleen==true && from==true) document.getElementById(objTextfield).focus();
	}
}
}
function ChangeColor(id, color) {
if(color.length==6)
	document.getElementById(id).style.backgroundColor="#"+color;
}

//initiates the first data query
function receiveChatText() {
	cat=encodeURIComponent(document.getElementById('cat').value);
	if (httpReceiveChat.readyState == 4 || httpReceiveChat.readyState == 0) {
		jal_lastID = parseInt(document.getElementById('jal_lastID').value) - 1;
		httpReceiveChat.open("GET",GetChaturl+'&jal_lastID='+jal_lastID+'&shout_cat='+cat+'&rand='+Math.floor(Math.random() * 1000000), true);
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
		if (firstarray.length == 2) {
			results = firstarray[0].split('---');
			replaceUserOnline(results[0],results[1]);
			results = firstarray[1].split('---'); //the fields are seperated by ---
			if (results.length > 4) {
				for(i=0;i < (results.length-1);i=i+8) { //goes through the result one message at a time
				insertNewContent(results[i+1],results[i+2],results[i+3], results[i], results[i+4], results[i+5], results[i+6], results[i+7]);
				document.getElementById('jal_lastID').value = parseInt(results[i]) + 1;
				}
				jal_timeout = jal_org_timeout;
				jal_loadtimes = 1;
			}
		}
	}
}

function insertNewContent(liName, liText, liUrl, liId, liUser, liEmail, liTime, ip) {
var myClass="";
if(liUser==1) myClass="jal_user ";
myClass+=liName;
verif=true;
insertO = document.getElementById("outputList");
oLi = document.createElement('li');
oLi.setAttribute('id','comment-new'+liId);

oSpan = document.createElement('span');
oSpan.setAttribute('class',myClass);
oSpan.setAttribute('id','user_'+liId);
oSpan.setAttribute('title',liTime);
oName = document.createTextNode(liName);

if (liUrl != "http://" && liUrl != '') {
	oURL = document.createElement('a');
	oURL.href = liUrl;
	oURL.setAttribute('target','_blank');
	oURL.appendChild(oName);
} else {
	oURL = oName;
}

oSpan.appendChild(oURL);
oSpan.appendChild(document.createTextNode(' : '));

oStar = document.createTextNode("*");
oURL = document.createElement('a');
oURL.href = "http://whois.domaintools.com/"+ip;
oURL.setAttribute('target','_blank');
oURL.setAttribute('title','Whois');
oURL.appendChild(oStar);
oSpan.appendChild(oURL);
oLi.appendChild(oSpan);
oSpace = document.createTextNode(" ");
oLi.appendChild(oSpace);

oText=document.createElement("input");
oText.setAttribute('type','text');
oText.setAttribute('name','jal_text');
oText.setAttribute('size','60');
oText.setAttribute('value',liText);
oText.setAttribute('id','text_'+liId);
oLi.appendChild(oText);
oSpace = document.createTextNode(" ");
oLi.appendChild(oSpace);

oText=document.createElement("input");
oText.setAttribute('name','ip');
oText.setAttribute('value',ip);
oText.setAttribute('id','ip_'+liId);

if(can_Ban==true) {
	oText.setAttribute('type','text');
	oText.setAttribute('size','14');
	oLi.appendChild(oText);
	oSpace = document.createTextNode(" ");
	oLi.appendChild(oSpace);

	oBtn=document.createElement("input");
	oBtn.setAttribute('type','button');
	oBtn.setAttribute('name','jal_ban');
	oBtn.onclick = function () { BanIP(liId,ip); }
	oBtn.setAttribute('value',libBan);
	oLi.appendChild(oBtn);
	oSpace = document.createTextNode(" ");
	oLi.appendChild(oSpace);
}
else {
	oText.setAttribute('type','hidden');
	oLi.appendChild(oText);
	oSpace = document.createTextNode(" ");
	oLi.appendChild(oSpace);
}


oBtn=document.createElement("input");
oBtn.setAttribute('type','button');
oBtn.setAttribute('name','jal_delete');
oBtn.onclick = function () { deleteComment(liId); }
oBtn.setAttribute('value',libDel);
oLi.appendChild(oBtn);
oSpace = document.createTextNode(" ");
oLi.appendChild(oSpace);

oBtn=document.createElement("input");
oBtn.setAttribute('type','button');
oBtn.setAttribute('name','jal_edit');
oBtn.onclick = function () { EditComment(liId); }
oBtn.setAttribute('value',libEdit);
oLi.appendChild(oBtn);
oSpace = document.createTextNode(" ");
oLi.appendChild(oSpace);

insertO.insertBefore(oLi, insertO.firstChild);
}
function CleanBox(theme,lib) {
document.getElementById("theme").innerHTML=lib;
document.getElementById("cat").value=theme;
cur_theme=0;
Fat.fade_element("theme",30,1000,"#22CC22");
document.getElementById('theme').className="shout";
var parent = document.getElementById("outputList");
document.getElementById('jal_lastID').value=0;
while (parent.firstChild) {
	parent.removeChild(parent.firstChild);
}
receiveChatText();
}
function CountAndGo(theme,lib) {
	document.getElementById("theme").innerHTML=lib;
	document.getElementById("cat").value=theme;
	var parent = document.getElementById("outputList");
	document.getElementById('jal_lastID').value=0;

	while (parent.firstChild) {
		parent.removeChild(parent.firstChild);
	}

	if(cur_theme==0) {
		cur_theme=lib;
		Fat.fade_element("theme",30,1000,"#FF0000");
		document.getElementById('theme').className="archive";
	}
	else {
		if(cur_theme==lib) {
			document.location=url+encodeURIComponent(theme);
			cur_theme=0;
		}
		else {
			cur_theme=lib;
			Fat.fade_element("theme",30,1000,"#FF0000");
			document.getElementById('theme').className="archive";
			}
	}
}

function deleteComment(id) {
	theHTML=document.getElementById('user_'+id).innerHTML;
	var HtmlText = theHTML.replace(/<[a-zA-Z\/][^>]*>(x|\*)?/g, "");
	HtmlText+=document.getElementById('text_'+id).value;
	AlertMsg=Sht_Alert1+" \n"+HtmlText+"\n\n";
	AlertMsg+=Sht_Alert2;

	if(confirm(AlertMsg)) {
		if (httpSendChat.readyState == 4 || httpSendChat.readyState == 0) {
			param = 'mode=del&id='+ encodeURIComponent(id);
			httpSendChat.open("POST", SendChaturl, true);
			httpSendChat.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
			httpSendChat.onreadystatechange = receiveChatText;
			httpSendChat.send(param);
			Highlight(id,"#FF0000");
			setTimeout('delChild('+id+')',1500);
		}
	}	
}
function delChild(id) {
	var parent = document.getElementById("outputList"); 
	var enfant = document.getElementById("comment-new"+id); 
	var anciennoeud = parent.removeChild(enfant);
}
function Highlight(id,color) {
Fat.fade_element('text_'+id,30,1000,color);
if(can_Ban==true) Fat.fade_element('ip_'+id,30,1000,color);
}
function EditComment(id) {
Text=document.getElementById('text_'+id).value;
IP=document.getElementById('ip_'+id).value;
	if (httpSendChat.readyState == 4 || httpSendChat.readyState == 0) {
		param = 'mode=edit&id='+ encodeURIComponent(id)+'&text='+encodeURIComponent(Text)+'&ip='+IP;
		httpSendChat.open("POST", ModChaturl, true);
		httpSendChat.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
		httpSendChat.onreadystatechange = receiveChatText;
		httpSendChat.send(param);
	}
Highlight(id,"#22CC22");
}

function BanIP(id,ip) {
	AlertMsg=Sht_Alert3+" \n"+ip+"\n";
	AlertMsg+=Sht_Alert4;

	if(confirm(AlertMsg)) {
		if (httpSendChat.readyState == 4 || httpSendChat.readyState == 0) {
			param = 'mode=ban&id='+ encodeURIComponent(id)+'&ip='+ip;
			httpSendChat.open("POST", ModChaturl, true);
			httpSendChat.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
			httpSendChat.onreadystatechange = receiveChatText;
			httpSendChat.send(param);
			Highlight(id,"#FF0000");
			setTimeout('delChild('+id+')',1500);
		}
	}
}