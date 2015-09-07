<?php

namespace Sabre\DAV\Auth\Backend;

use Sabre\DAV;
use Sabre\HTTP;

class ApacheTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $backend = new Apache();
        $this->assertInstanceOf('Sabre\DAV\Auth\Backend\Apache', $backend);

    }

    /**
     * @expectedException Sabre\DAV\Exception
     */
    function testNoHeader() {

        $server = new DAV\Server();
        $backend = new Apache();
        $backend->authenticate($server,'Realm');

    }

    function testRemoteUser() {

        $backend = new Apache();

        $server = new DAV\Server();
        $request = HTTP\Sapi::createFromServerArray([
            'REMOTE_USER' => 'username',
        ]);
        $server->httpRequest = $request;

        $this->assertTrue($backend->authenticate($server, 'Realm'));

        $userInfo = 'username';

        $this->assertEquals($userInfo, $backend->getCurrentUser());

    }

    function testRedirectRemoteUser() {

        $backend = new Apache();

        $server = new DAV\Server();
        $request = HTTP\Sapi::createFromServerArray([
            'REDIRECT_REMOTE_USER' => 'username',
        ]);
        $server->httpRequest = $request;

        $this->assertTrue($backend->authenticate($server, 'Realm'));

        $userInfo = 'username';

        $this->assertEquals($userInfo, $backend->getCurrentUser());

    }
}
