<?php
define('NO_SESSION', '1');
$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';

if (!AmpConfig::get('upnp_backend')) {
    echo T_("Disabled");

    return false;
}

if (($_GET['btnSend']) || ($_GET['btnSendAuto'])) {
    $msIP = 1;
    Upnp_Api::sddpSend($msIP);
} ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<!-- Propelled by Ampache | ampache.org -->
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php
    if (Core::get_get('btnSendAuto') !== '') {
        echo '<meta http-equiv="refresh" content="1">';
    } ?>
<title><?php echo T_("Ampache") . " " . T_("UPnP"); ?></title>
<style media="screen">
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
    } ?>
</body>
</html>
