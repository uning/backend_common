<?php
require_once 'vk.php';
//$vk = new Vk();
//echo "secure_getAppBalance\n";
//var_dump( $vk->api_client->secure_getAppBalance() );

//var_dump( $vk->api_client->secure_sendNotification(45688684,"xxxx") );



//echo "\ngetProfiles\n";
$vk1 = new Vk(41159893);
//var_dump( $vk1->api_client->getProfiles(41159893) );
var_dump( $vk1->api_client->getFriends() );
//var_dump( $vk1->api_client->getAppFriends(41159893) );




?>
