var httpSendChat;

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
	AlertMsg=Sht_Alert1+" \n"+ HtmlText+"\n";
	AlertMsg+=Sht_Alert2;
	if(confirm(AlertMsg)) {
		if (httpSendChat.readyState == 4 || httpSendChat.readyState == 0) {
			param = 'mode=del&id='+ encodeURIComponent(id);
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