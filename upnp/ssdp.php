<?php
/**
 * UPnP simple service discovery protocol (SSDP)
 * https://github.com/clue/reactphp-multicast/blob/master/examples/ssdp.php
 */
define('NO_SESSION', '1');
require_once '../lib/init.php';

use Clue\React\Multicast\Factory;

require_once  '../lib/vendor/autoload.php';

$address = '239.255.255.250:1900';

$loop    = React\EventLoop\Factory::create();
$factory = new Factory($loop);

$socket = $factory->createReceiver($address);
// $hex = new Hexdump();


$socket->on('message', function ($data, $remote) use ($socket) {
    //debug_event('upnp', "Received message", 5);
    $unpacked = explode(PHP_EOL, $data);
    $command  = explode(' ', $unpacked[0]);
    if ($command[0] == 'M-SEARCH' && $command[1] == '*') {
        Upnp_Api::discovery_request($data, $remote);
    } elseif ($command[0] == 'NOTIFY' && $command[1] == '*') {
        Upnp_Api::notify_request($data, $remote);
    } else {
        debug_event('upnp', 'Unknown UPNP command from ' . $remote, 5);
        debug_event('upnp', $data, 5);
    }
});

/**
 * @param $signal
 */
function ssdpShutdown($signal)
{
    debug_event('upnp', 'SSDP server being shut down by signal ' . $signal, 5);
    // Send a couple of times as UDP is unreliable
    Upnp_Api::sddpSend(1, "239.255.255.250", 1900, "NT", false);
    Upnp_Api::sddpSend(1, "239.255.255.250", 1900, "NT", false);
    exit(1);
}

function sayAlive()
{
    // Send a couple of times as UDP is unreliable
    Upnp_Api::sddpSend(1, "239.255.255.250", 1900, "NT", true);
    Upnp_Api::sddpSend(1, "239.255.255.250", 1900, "NT", true);
}

// dump all incoming messages
//$sender->on('message', function ($data, $remote) {
//    echo 'Received from ' . $remote . PHP_EOL;
//    echo $data . PHP_EOL;
//});

// stop waiting for incoming messages after 3.0s (MX is 2s)
// Patch the following in to terminate this program after a short debug while
///$loop->addTimer(300.0, function () use ($socket) {
//    $socket->pause();
///});

// Print ini file source to log file to help debug oddities
debug_event('upnp', "=====================================================" . PHP_EOL . 'Beginning SSDP service', 5);
$inipath = php_ini_loaded_file();
if ($inipath) {
    debug_event('upnp', 'Loaded php.ini: ' . $inipath, 5);
} else {
    debug_event('upnp', 'A php.ini file is not loaded', 5);
}

$loop->addPeriodicTimer(30, 'sayAlive');
sayAlive();
debug_event('upnp', 'Alive sent & will be sent every  30 seconds from now on', 5);

// get called by Apache when shutting down
// register_shutdown_function ( 'ssdpShutdown' );

pcntl_signal(SIGINT, 'ssdpShutdown');

$loop->run();
