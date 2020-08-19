<?php
// No event subscribe support but avoid few players error (e.g. Windows Media Player).

define('NO_SESSION', '1');
$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';

$headers = getallheaders();
//$callback = $headers['Callback'];
//$nt = $headers['NT'];
$timeout = $headers['Timeout'];
if (empty($timeout)) {
    $timeout = "Second-3600";
}

header("SID: uuid:" . uniqid());
header("TIMEOUT:" . $timeout);
header("Connection: close");

return false;
