<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Ampache UPnP</title>
<style type="text/css" media="screen">
body {
    color:black;
    background-color:white;
    background-image:url(images/upnp.jpg);
    background-repeat:no-repeat;
    background-position:50% 50%;
    height: 400px;
}
</style>
</head>

<body>
<?php
define('NO_SESSION','1');
require_once '../lib/init.php';

if (!AmpConfig::get('upnp_backend')) {
    echo "Disabled.";
    exit;
}
?>
<form method="get" action="">
  <label>Ampache UPnP backend enabled.
  </label>
  <br />
  <br />
  <br />
  <input type="submit" name="btnSend" id="id-btnSend" value="Send SSDP broadcast" />
</form>
<br />
<?php
if ($_GET['btnSend']) {
    Upnp_Api::sddpSend($msIP);
    echo 'SSDP sent at '.date('H:i:s').'.';
}
?>
</body>
</html>
