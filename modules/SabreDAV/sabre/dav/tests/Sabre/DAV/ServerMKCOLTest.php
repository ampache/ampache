<?php

namespace Sabre\DAV;

use Sabre\HTTP;

class ServerMKCOLTest extends AbstractServer {

    function testMkcol() {

        $serverVars = array(
            'REQUEST_URI'    => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody("");
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Length' => ['0'],
        ),$this->response->getHeaders());

        $this->assertEquals(201, $this->response->status);
        $this->assertEquals('', $this->response->body);
        $this->assertTrue(is_dir($this->tempDir . '/testcol'));

    }

    /**
     * @depends testMkcol
     */
    function testMKCOLUnknownBody() {

        $serverVars = array(
            'REQUEST_URI'    => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody("Hello");
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ),$this->response->getHeaders());

        $this->assertEquals(415, $this->response->status);

    }

    /**
     * @depends testMkcol
     */
    function testMKCOLBrokenXML() {

        $serverVars = array(
            'REQUEST_URI'    => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody("Hello");
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ),$this->response->getHeaders());

        $this->assertEquals(400, $this->response->status);

    }

    /**
     * @depends testMkcol
     */
    function testMKCOLUnknownXML() {

        $serverVars = array(
            'REQUEST_URI'    => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?><html></html>');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ),$this->response->getHeaders());

        $this->assertEquals(415, $this->response->status);

    }

    /**
     * @depends testMkcol
     */
    function testMKCOLNoResourceType() {

        $serverVars = array(
            'REQUEST_URI'    => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <displayname>Evert</displayname>
    </prop>
  </set>
</mkcol>');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ),$this->response->getHeaders());

        $this->assertEquals(400, $this->response->status, 'Wrong statuscode received. Full response body: ' .$this->response->body);

    }

    /**
     * @depends testMKCOLNoResourceType
     */
    function testMKCOLIncorrectResourceType() {

        $serverVars = array(
            'REQUEST_URI'    => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <resourcetype><blabla /></resourcetype>
    </prop>
  </set>
</mkcol>');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ),$this->response->getHeaders());

        $this->assertEquals(403, $this->response->status, 'Wrong statuscode received. Full response body: ' .$this->response->body);

    }

    /**
     * @depends testMKCOLIncorrectResourceType
     */
    function testMKCOLIncorrectResourceType2() {

        $serverVars = array(
            'REQUEST_URI'    => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <resourcetype><collection /><blabla /></resourcetype>
    </prop>
  </set>
</mkcol>');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ),$this->response->getHeaders());

        $this->assertEquals(403, $this->response->status, 'Wrong statuscode received. Full response body: ' .$this->response->body);

    }

    /**
     * @depends testMKCOLIncorrectResourceType2
     */
    function testMKCOLSuccess() {

        $serverVars = array(
            'REQUEST_URI'    => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <resourcetype><collection /></resourcetype>
    </prop>
  </set>
</mkcol>');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Length' => ['0'],
        ),$this->response->getHeaders());

        $this->assertEquals(201, $this->response->status, 'Wrong statuscode received. Full response body: ' .$this->response->body);

    }

    /**
     * @depends testMKCOLIncorrectResourceType2
     */
    function testMKCOLWhiteSpaceResourceType() {

        $serverVars = array(
            'REQUEST_URI'    => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <resourcetype>
            <collection />
        </resourcetype>
    </prop>
  </set>
</mkcol>');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Length' => ['0'],
        ),$this->response->getHeaders());

        $this->assertEquals(201, $this->response->status, 'Wrong statuscode received. Full response body: ' .$this->response->body);

    }

    /**
     * @depends testMKCOLIncorrectResourceType2
     */
    function testMKCOLNoParent() {

        $serverVars = array(
            'REQUEST_URI'    => '/testnoparent/409me',
            'REQUEST_METHOD' => 'MKCOL',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('');

        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ),$this->response->getHeaders());

        $this->assertEquals(409, $this->response->status, 'Wrong statuscode received. Full response body: ' .$this->response->body);

    }

    /**
     * @depends testMKCOLIncorrectResourceType2
     */
    function testMKCOLParentIsNoCollection() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt/409me',
            'REQUEST_METHOD' => 'MKCOL',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('');

        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ),$this->response->getHeaders());

        $this->assertEquals(409, $this->response->status, 'Wrong statuscode received. Full response body: ' .$this->response->body);

    }

    /**
     * @depends testMKCOLIncorrectResourceType2
     */
    function testMKCOLAlreadyExists() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'MKCOL',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('');

        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
            'Allow'        => ['OPTIONS, GET, HEAD, DELETE, PROPFIND, PUT, PROPPATCH, COPY, MOVE, REPORT'],
        ),$this->response->getHeaders());

        $this->assertEquals(405, $this->response->status, 'Wrong statuscode received. Full response body: ' .$this->response->body);

    }

    /**
     * @depends testMKCOLSuccess
     * @depends testMKCOLAlreadyExists
     */
    function testMKCOLAndProps() {

        $serverVars = array(
            'REQUEST_URI'    => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <resourcetype><collection /></resourcetype>
        <displayname>my new collection</displayname>
    </prop>
  </set>
</mkcol>');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(207, $this->response->status, 'Wrong statuscode received. Full response body: ' .$this->response->body);

        $this->assertEquals(array(
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ),$this->response->getHeaders());

    }

}
