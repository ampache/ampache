<?php

namespace Sabre\CardDAV;

use Sabre\HTTP;
use Sabre\DAV;

require_once 'Sabre/HTTP/ResponseMock.php';

class MultiGetTest extends AbstractPluginTest {

    function testMultiGet() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI' => '/addressbooks/user1/book1',
        ));

        $request->setBody(
'<?xml version="1.0"?>
<c:addressbook-multiget xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
      <c:address-data />
    </d:prop>
    <d:href>/addressbooks/user1/book1/card1</d:href>
</c:addressbook-multiget>'
            );

        $response = new HTTP\ResponseMock();

        $this->server->httpRequest = $request;
        $this->server->httpResponse = $response;

        $this->server->exec();

        $this->assertEquals(207, $response->status, 'Incorrect status code. Full response body:' . $response->body);

        // using the client for parsing
        $client = new DAV\Client(array('baseUri'=>'/'));

        $result = $client->parseMultiStatus($response->body);

        $this->assertEquals(array(
            '/addressbooks/user1/book1/card1' => array(
                200 => array(
                    '{DAV:}getetag' => '"' . md5("BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD") . '"',
                    '{urn:ietf:params:xml:ns:carddav}address-data' => "BEGIN:VCARD\r\nVERSION:3.0\r\nUID:12345\r\nEND:VCARD\r\n",
                )
            )
        ), $result);

    }

    function testMultiGetVCard4() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI' => '/addressbooks/user1/book1',
        ));

        $request->setBody(
'<?xml version="1.0"?>
<c:addressbook-multiget xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
      <c:address-data content-type="text/vcard" version="4.0" />
    </d:prop>
    <d:href>/addressbooks/user1/book1/card1</d:href>
</c:addressbook-multiget>'
            );

        $response = new HTTP\ResponseMock();

        $this->server->httpRequest = $request;
        $this->server->httpResponse = $response;

        $this->server->exec();

        $this->assertEquals(207, $response->status, 'Incorrect status code. Full response body:' . $response->body);

        // using the client for parsing
        $client = new DAV\Client(array('baseUri'=>'/'));

        $result = $client->parseMultiStatus($response->body);

        $prodId = "PRODID:-//Sabre//Sabre VObject " . \Sabre\VObject\Version::VERSION . "//EN";

        $this->assertEquals(array(
            '/addressbooks/user1/book1/card1' => array(
                200 => array(
                    '{DAV:}getetag' => '"' . md5("BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD") . '"',
                    '{urn:ietf:params:xml:ns:carddav}address-data' => "BEGIN:VCARD\r\nVERSION:4.0\r\n$prodId\r\nUID:12345\r\nEND:VCARD\r\n",
                )
            )
        ), $result);

    }
}
