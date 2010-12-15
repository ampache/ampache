<?php
	require_once '../../lib/init.php';
	session_start();
	
	$_SESSION['twitterusername'] = false;
	header('Location: ' . Config::get('web_path') );
?>

