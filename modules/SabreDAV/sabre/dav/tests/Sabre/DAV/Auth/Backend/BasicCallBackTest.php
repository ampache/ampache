<?php

namespace Sabre\DAV\Auth\Backend;

use
    Sabre\DAV\Server,
    Sabre\HTTP\Sapi;

class BasicCallBackTest extends \PHPUnit_Framework_TestCase {

    function testCallBack() {

        $args = array();
        $callBack = function($user, $pass) use (&$args) {

            $args = [$user, $pass];
            return true;

        };

        $backend = new BasicCallBack($callBack);

        $server = new Server();
        $server->httpRequest = Sapi::createFromServerArray([
            'HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('foo:bar'),
        ]);

        $this->assertTrue($backend->authenticate($server, 'Realm'));

        $this->assertEquals(['foo','bar'], $args);

    }

}
