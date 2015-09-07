<?php

namespace Sabre\DAV\Auth;

use Sabre\HTTP;
use Sabre\DAV;

require_once 'Sabre/HTTP/ResponseMock.php';

class PluginTest extends \PHPUnit_Framework_TestCase {

    function testInit() {

        $fakeServer = new DAV\Server( new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock(),'realm');
        $this->assertTrue($plugin instanceof Plugin);
        $fakeServer->addPlugin($plugin);
        $this->assertEquals($plugin, $fakeServer->getPlugin('auth'));

    }

    /**
     * @depends testInit
     */
    function testAuthenticate() {

        $fakeServer = new DAV\Server( new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock(),'realm');
        $fakeServer->addPlugin($plugin);
        $this->assertTrue(
            $fakeServer->emit('beforeMethod', [new HTTP\Request(), new HTTP\Response()])
        );

    }

    /**
     * @depends testInit
     * @expectedException Sabre\DAV\Exception\NotAuthenticated
     */
    function testAuthenticateFail() {

        $fakeServer = new DAV\Server( new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock(),'failme');
        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod', [new HTTP\Request(), new HTTP\Response()]);

    }

    function testReportPassThrough() {

        $fakeServer = new DAV\Server(new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock(),'realm');
        $fakeServer->addPlugin($plugin);

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'REPORT',
            'HTTP_CONTENT_TYPE' => 'application/xml',
            'REQUEST_URI' => '/',
        ));
        $request->setBody('<?xml version="1.0"?><s:somereport xmlns:s="http://www.rooftopsolutions.nl/NS/example" />');

        $fakeServer->httpRequest = $request;
        $fakeServer->sapi = new HTTP\SapiMock();
        $fakeServer->httpResponse = new HTTP\ResponseMock();
        $fakeServer->exec();

        $this->assertEquals(415, $fakeServer->httpResponse->status);

    }

    /**
     * @depends testInit
     */
    function testGetCurrentUserPrincipal() {

        $fakeServer = new DAV\Server( new DAV\SimpleCollection('bla'));
        $plugin = new Plugin(new Backend\Mock(),'realm');
        $fakeServer->addPlugin($plugin);
        $fakeServer->emit('beforeMethod', [new HTTP\Request(), new HTTP\Response()]);
        $this->assertEquals('admin', $plugin->getCurrentUser());

    }

    /**
     * @depends testInit
     */
    function testPlugin() {
        $myRealmName = 'some_realm';
        $plugin = new Plugin(new Backend\Mock(),$myRealmName);
        $this->assertEquals($myRealmName, $plugin->getRealm());
    }

}

