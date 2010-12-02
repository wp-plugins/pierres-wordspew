<?php
//$PathToPlugin=get_bloginfo('wpurl');
echo '
var ModChaturl="'.$PathToPlugin.'/wordspew_admin.php";
';
$Ban=TransGuil(__("Ban this IP",wordspew));
$Del=TransGuil(__("Delete",wordspew));
$Edit=TransGuil(__("Edit",wordspew));
$Sht_Alert1=TransGuil(__('Are you sure to delete the message:',wordspew));
$Sht_Alert2=TransGuil(__('\'Cancel\' to stop, \'OK\' to delete.',wordspew));
$Sht_Alert3=TransGuil(__("You're about to ban the following IP address:",wordspew));
$Sht_Alert4=TransGuil(__('\'Cancel\' to stop, \'OK\' to ban.',wordspew));

echo '
var libBan="'.$Ban.'", libDel="'.$Del.'", libEdit="'.$Edit.'", Sht_Alert1="'.$Sht_Alert1.'", Sht_Alert2="'.$Sht_Alert2.'", Sht_Alert3="'.$Sht_Alert3.'", Sht_Alert4="'.$Sht_Alert4.'";
';
?>