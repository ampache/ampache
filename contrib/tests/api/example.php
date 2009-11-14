<?php 

// Require the API lib
require_once 'AmpacheApi.lib.php';

$username = ''; 
$password = ''; 

$ampache = new AmpacheApi(array('username'=>$username,'password'=>$password,'server'=>'localhost')); 
$ampache->parse_response($ampache->send_command('artists',array('filter'=>'e'))); 
print_r($ampache->get_response()); 
?>
