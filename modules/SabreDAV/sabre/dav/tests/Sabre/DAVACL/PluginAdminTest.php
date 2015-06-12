<?php

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\HTTP;


require_once 'Sabre/DAVACL/MockACLNode.php';
require_once 'Sabre/HTTP/ResponseMock.php';

class PluginAdminTest extends \PHPUnit_Framework_TestCase {

    function testNoAdminAccess() {

        $principalBackend = new PrincipalBackend\Mock();

        $tree = array(
            new MockACLNode('adminonly', array()),
            new PrincipalCollection($principalBackend),
        );

        $fakeServer = new DAV\Server($tree);
        $fakeServer->sapi = new HTTP\SapiMock();
        $plugin = new DAV\Auth\Plugin(new DAV\Auth\Backend\Mock(),'realm');
        $fakeServer->addPlugin($plugin);
        $plugin = new Plugin();
        $fakeServer->addPlugin($plugin);

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'OPTIONS',
            'HTTP_DEPTH' => 1,
            'REQUEST_URI' => '/adminonly',
        ));

        $response = new HTTP\ResponseMock();

        $fakeServer->httpRequest = $request;
        $fakeServer->httpResponse = $response;

        $fakeServer->exec();

        $this->assertEquals(403, $response->status);

    }

    /**
     * @depends testNoAdminAccess
     */
    function testAdminAccess() {

        $principalBackend = new PrincipalBackend\Mock();

        $tree = array(
            new MockACLNode('adminonly', array()),
            new PrincipalCollection($principalBackend),
        );

        $fakeServer = new DAV\Server($tree);
        $fakeServer->sapi = new HTTP\SapiMock();
        $plugin = new DAV\Auth\Plugin(new DAV\Auth\Backend\Mock(),'realm');
        $fakeServer->addPlugin($plugin);
        $plugin = new Plugin();
        $plugin->adminPrincipals = array(
            'principals/admin',
        );
        $fakeServer->addPlugin($plugin);

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'OPTIONS',
            'HTTP_DEPTH' => 1,
            'REQUEST_URI' => '/adminonly',
        ));

        $response = new HTTP\ResponseMock();

        $fakeServer->httpRequest = $request;
        $fakeServer->httpResponse = $response;

        $fakeServer->exec();

        $this->assertEquals(200, $response->status);

    }
}
