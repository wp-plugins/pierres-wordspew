<?php
global $wpsmiliestrans;
$pathtoSmiley = get_bloginfo('wpurl');
$PathToPlugin = $pathtoSmiley."/wp-content/plugins/pierres-wordspew";
$pathtoSmiley.= "/wp-includes/images/smilies/";
$Sht_Chaine=TransGuil(__("Private message for", wordspew));
$Sht_Link=TransGuil(__('link',wordspew));
$Sht_Email=TransGuil(__('email',wordspew));
$Sht_Expand=TransGuil(__('Click here to expand/collapse the smiley\'s list',wordspew));
$Sht_Delete=TransGuil(__("Delete",wordspew));
$Sht_Alert1=TransGuil(__('Are you sure to delete the message:',wordspew));
$Sht_Alert2=TransGuil(__('\'Cancel\' to stop, \'OK\' to delete.',wordspew));

echo '
var PathToSmiley="'.$pathtoSmiley.'", pathToImg="'.$PathToPlugin.'/img/", pathToMP3="'.$PathToPlugin.'/msg.mp3";
var smilies=[';
if(is_array($wpsmiliestrans)) {
    // Get smileys information from Wordpress
	natsort($wpsmiliestrans);
    $strFatSmilies = '';
    foreach($wpsmiliestrans as $tag => $file) {
        $strFatSmilies .= "['".trim(str_replace("'","\'",$tag))."', '".trim($file)."'],";
    }
    $strFatSmilies = substr($strFatSmilies, 0, -1);
    echo $strFatSmilies;
	}
echo '];
var Sht_Link="'.$Sht_Link.'", Sht_Email="'.$Sht_Email.'";
var GetChaturl="'.$PathToPlugin.'/wordspew.php?jalGetChat=yes", SendChaturl="'.$PathToPlugin.'/wordspew.php?jalSendChat=yes";
var Sht_Expand="'.$Sht_Expand.'", Sht_Chaine="'.$Sht_Chaine.'", Sht_Delete="'.$Sht_Delete.'", Sht_Alert1="'.$Sht_Alert1.'", Sht_Alert2="'.$Sht_Alert2.'";
var jal_org_timeout='.$shout_opt['update_seconds'].';
';