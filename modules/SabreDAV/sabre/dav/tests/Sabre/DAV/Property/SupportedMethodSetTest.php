<?php

namespace Sabre\DAV\Property;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';
require_once 'Sabre/DAV/AbstractServer.php';

class SupportedMethodSetTest extends DAV\AbstractServer {

    public function sendPROPFIND($body) {

        $request = new HTTP\Request('PROPFIND', '/', ['Depth' => '0' ]);
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

    }

    /**
     */
    function testMethods() {

        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:supported-method-set />
  </d:prop>
</d:propfind>';

        $this->sendPROPFIND($xml);

        $this->assertEquals(207, $this->response->status,'We expected a multi-status response. Full response body: ' . $this->response->body);

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/","xmlns\\1=\"urn:DAV\"",$this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d','urn:DAV');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop');
        $this->assertEquals(1,count($data),'We expected 1 \'d:prop\' element');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supported-method-set');
        $this->assertEquals(1,count($data),'We expected 1 \'d:supported-method-set\' element');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:status');
        $this->assertEquals(1,count($data),'We expected 1 \'d:status\' element');

        $this->assertEquals('HTTP/1.1 200 OK',(string)$data[0],'The status for this property should have been 200');

    }

}

