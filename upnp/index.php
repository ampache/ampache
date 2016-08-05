<?php
define('NO_SESSION', '1');
require_once '../lib/init.php';

if (!AmpConfig::get('upnp_backend')) {
    echo "Disabled.";
    exit;
}

if (($_GET['btnSend']) || ($_GET['btnSendAuto'])) {
    $msIP = 1;
    Upnp_Api::sddpSend($msIP);
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<!-- Propulsed by Ampache | ampache.org -->
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php
    if ($_GET['btnSendAuto']) {
        echo '<meta http-equiv="refresh" content="1">';
    }
?>
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
<form method="get" action="">
  <label>Ampache UPnP backend enabled.
  </label>
  <br />
  <br />
  <br />
  <input type="submit" name="btnSend" id="id-btnSend" value="Send SSDP broadcast" />
  <input type="submit" name="btnSendAuto" id="id-btnSendAuto" value="Send SSDP broadcast every second" />
</form>
<br />
<?php
if (($_GET['btnSend']) || ($_GET['btnSendAuto'])) {
    echo 'SSDP sent at ' . date('H:i:s') . '.';
}
?>
</body>
</html>
