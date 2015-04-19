<?php

namespace Sabre\HTTP\Auth;

use Sabre\HTTP\Request;
use Sabre\HTTP\Response;

class BasicTest extends \PHPUnit_Framework_TestCase {

    function testGetCredentials() {

        $request = new Request('GET','/',array(
            'Authorization' => 'Basic ' . base64_encode('user:pass:bla')
        ));

        $basic = new Basic('Dagger', $request, new Response());

        $this->assertEquals(array(
            'user',
            'pass:bla',
        ), $basic->getCredentials($request));

    }

    function testGetCredentialsNoheader() {

        $request = new Request('GET','/',array());
        $basic = new Basic('Dagger', $request, new Response());

        $this->assertNull($basic->getCredentials($request));

    }

    function testGetCredentialsNotBasic() {

        $request = new Request('GET','/',array(
            'Authorization' => 'QBasic ' . base64_encode('user:pass:bla')
        ));
        $basic = new Basic('Dagger', $request, new Response());

        $this->assertNull($basic->getCredentials($request));

    }

    function testRequireLogin() {

        $response = new Response();
        $basic = new Basic('Dagger', new Request(), $response);

        $basic->requireLogin();

        $this->assertEquals('Basic realm="Dagger"', $response->getHeader('WWW-Authenticate'));
        $this->assertEquals(401, $response->getStatus());

    }

}
