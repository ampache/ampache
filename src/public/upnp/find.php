<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

/**
 * Class UPnPFind
 */
class UPnPFind
{
    /**
     * Find devices by UPnP multicast message and stores them to cache
     *
     * @return array  Parsed device list
     */
    public static function findDevices()
    {
        $discover = self::discover(10);

        return($discover); //!!

        /*
        $devices = array();
        flush();
        foreach ($discover as $response) {

            $device = new Device();
            if ($device->initByDiscoveryReponse($response)) {

                $device->saveToCache();

                try {
                    $client = $device->getClient('ConnectionManager');
                    $protocolInfo = $client->call('GetProtocolInfo');

                    $sink = $protocolInfo['Sink'];
                    $tmp = explode(',', $sink);

                    $protocols = array();

                    foreach ($tmp as $protocol) {
                        $t = explode(':', $protocol);
                        if ($t[0] == 'http-get') {
                            $protocols[] = $t[2];
                        }
                    }
                } catch (UPnPException $upnpe) {
                    $protocols = array();
                }

                $device->protocolInfo = $protocols;

                $cache[$device->getId()] = array(
                    'name' => $device->getName(),
                    'services' => $device->getServices(),
                    'icons' => $device->getIcons(),
                    'protocols' => $device->getProtocolInfo()
                );
            }
        }

        return $cache;
        */
    }

    /**
     * Performs a standardized UPnP multicast request to 239.255.255.250:1900
     * and listens $timeout seconds for responses
     *
     * Thanks to artheus (https://github.com/artheus/PHP-UPnP/blob/master/phpupnp.class.php)
     *
     * @param integer $timeout Timeout to wait for responses
     *
     * @return array  Response
     */
    private static function discover($timeout = 2)
    {
        $msg  = 'M-SEARCH * HTTP/1.1' . "\r\n";
        $msg .= 'HOST: 239.255.255.250:1900' . "\r\n";
        $msg .= 'MAN: "ssdp:discover"' . "\r\n";
        $msg .= "MX: 3\r\n";
        $msg .= "ST: upnp:rootdevice\r\n";
        $msg .= '' . "\r\n";

        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
        socket_sendto($socket, $msg, strlen($msg), 0, '239.255.255.250', 1900);

        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));

        $response = array();
        do {
            $buf  = null;
            $from = null;
            $port = null;
            socket_recvfrom($socket, $buf, 1024, MSG_WAITALL, $from, $port);

            if ($buf !== null) {
                $response[] = self::discoveryReponse2Array($buf);
            }
        } while ($buf !== null);
        //socket_close($socket);

        return $response;
    }

    /**
     * Transforms discovery response string to key/value array
     *
     * @param string $res discovery response
     * @return stdObj
     */
    private static function discoveryReponse2Array($res)
    {
        $result = array();
        $lines  = explode("\n", trim($res));

        if (trim($lines[0]) == 'HTTP/1.1 200 OK') {
            array_shift($lines);
        }

        foreach ($lines as $line) {
            $tmp = explode(':', trim($line));

            $key   = strtoupper(array_shift($tmp));
            $value = (count($tmp) > 0 ? trim(join(':', $tmp)) : null);

            $result[$key] = $value;
        }

        return (Object) $result;
    }
}

$devices = UPnPFind::findDevices(); ?>

<pre>
<?php print_r($devices); ?>
</pre>
