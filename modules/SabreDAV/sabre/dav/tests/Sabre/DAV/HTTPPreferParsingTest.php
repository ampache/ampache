<?php

namespace Sabre\DAV;

use Sabre\HTTP;

class HTTPPReferParsingTest extends \Sabre\DAVServerTest {

    function testParseSimple() {

        $httpRequest = HTTP\Sapi::createFromServerArray(array(
            'HTTP_PREFER' => 'return-asynch',
        ));

        $server = new Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals(array(
            'return-asynch' => true,
            'return-minimal' => false,
            'return-representation' => false,
            'strict' => false,
            'lenient' => false,
            'wait' => null,
        ), $server->getHTTPPrefer());

    }

    function testParseValue() {

        $httpRequest = HTTP\Sapi::createFromServerArray(array(
            'HTTP_PREFER' => 'wait=10',
        ));

        $server = new Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals(array(
            'return-asynch' => false,
            'return-minimal' => false,
            'return-representation' => false,
            'strict' => false,
            'lenient' => false,
            'wait' => 10,
        ), $server->getHTTPPrefer());

    }

    function testParseMultiple() {

        $httpRequest = HTTP\Sapi::createFromServerArray(array(
            'HTTP_PREFER' => 'return-minimal, strict,lenient',
        ));

        $server = new Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals(array(
            'return-asynch' => false,
            'return-minimal' => true,
            'return-representation' => false,
            'strict' => true,
            'lenient' => true,
            'wait' => null,
        ), $server->getHTTPPrefer());

    }

    function testParseWeirdValue() {

        $httpRequest = HTTP\Sapi::createFromServerArray(array(
            'HTTP_PREFER' => 'BOOOH',
        ));

        $server = new Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals(array(
            'strict' => false,
            'lenient' => false,
            'wait' => null,
            'return-asynch' => false,
            'return-minimal' => false,
            'return-representation' => false,
        ), $server->getHTTPPrefer());

    }

    function testBrief() {

        $httpRequest = HTTP\Sapi::createFromServerArray(array(
            'HTTP_BRIEF' => 't',
        ));

        $server = new Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals(array(
            'strict' => false,
            'lenient' => false,
            'wait' => null,
            'return-asynch' => false,
            'return-minimal' => true,
            'return-representation' => false,
        ), $server->getHTTPPrefer());

    }

    /**
     * propfindMinimal
     *
     * @return void
     */
    function testpropfindMinimal() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'PROPFIND',
            'REQUEST_URI'    => '/',
            'HTTP_PREFER' => 'return-minimal',
        ));
        $request->setBody(<<<BLA
<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
    <d:prop>
        <d:something />
        <d:resourcetype />
    </d:prop>
</d:propfind>
BLA
        );

        $response = $this->request($request);

        $this->assertTrue(strpos($response->body, 'resourcetype')!==false);
        $this->assertTrue(strpos($response->body, 'something')===false);

    }

    function testproppatchMinimal() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'PROPPATCH',
            'REQUEST_URI'    => '/',
            'HTTP_PREFER' => 'return-minimal',
        ));
        $request->setBody(<<<BLA
<?xml version="1.0"?>
<d:proppatch xmlns:d="DAV:">
    <d:set>
        <d:prop>
            <d:something>nope!</d:something>
        </d:prop>
    </d:set>
</d:proppatch>
BLA
        );

        $this->server->on('propPatch', function($path, PropPatch $propPatch) {

            $propPatch->handle('{DAV:}something', function($props) {
                return true; 
            });

        });

        $response = $this->request($request);

        $this->assertEquals(0, strlen($response->body), 'Expected empty body: ' . $response->body);
        $this->assertEquals(204, $response->status);

    }

    function testproppatchMinimalError() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'PROPPATCH',
            'REQUEST_URI'    => '/',
            'HTTP_PREFER' => 'return-minimal',
        ));
        $request->setBody(<<<BLA
<?xml version="1.0"?>
<d:proppatch xmlns:d="DAV:">
    <d:set>
        <d:prop>
            <d:something>nope!</d:something>
        </d:prop>
    </d:set>
</d:proppatch>
BLA
        );

        $response = $this->request($request);

        $this->assertEquals(207, $response->status);
        $this->assertTrue(strpos($response->body, 'something')!==false);
        $this->assertTrue(strpos($response->body, '403 Forbidden')!==false);

    }
}
