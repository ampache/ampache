<?php 
require_once("modules/init.php");
// To end a legitimate session, just call logout.
setcookie("amp_longsess","",null); 
logout();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Sample logout page</title>
</head>
<body>
Congrats, you are logged out.
<br /><a href="./login.php">login</a>
</body></html>
