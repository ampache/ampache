<?php

namespace Sabre\DAV\Property;

use Sabre\DAV;

class HrefTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $href = new Href('path');
        $this->assertEquals('path',$href->getHref());

    }

    function testSerialize() {

        $href = new Href('path');
        $this->assertEquals('path',$href->getHref());

        $doc = new \DOMDocument();
        $root = $doc->createElement('d:anything');
        $root->setAttribute('xmlns:d','DAV:');

        $doc->appendChild($root);
        $server = new DAV\Server();
        $server->setBaseUri('/bla/');

        $href->serialize($server, $root);

        $xml = $doc->saveXML();

        $this->assertEquals(
'<?xml version="1.0"?>
<d:anything xmlns:d="DAV:"><d:href>/bla/path</d:href></d:anything>
', $xml);

    }

    function testSerializeNoPrefix() {

        $href = new Href('path',false);
        $this->assertEquals('path',$href->getHref());

        $doc = new \DOMDocument();
        $root = $doc->createElement('d:anything');
        $root->setAttribute('xmlns:d','DAV:');

        $doc->appendChild($root);
        $server = new DAV\Server();
        $server->setBaseUri('/bla/');

        $href->serialize($server, $root);

        $xml = $doc->saveXML();

        $this->assertEquals(
'<?xml version="1.0"?>
<d:anything xmlns:d="DAV:"><d:href>path</d:href></d:anything>
', $xml);

    }

    function testUnserialize() {

        $xml = '<?xml version="1.0"?>
<d:anything xmlns:d="urn:DAV"><d:href>/bla/path</d:href></d:anything>
';

        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $href = Href::unserialize($dom->firstChild, array());
        $this->assertEquals('/bla/path',$href->getHref());

    }

    function testUnserializeIncompatible() {

        $xml = '<?xml version="1.0"?>
<d:anything xmlns:d="urn:DAV"><d:href2>/bla/path</d:href2></d:anything>
';

        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $href = Href::unserialize($dom->firstChild, array());
        $this->assertNull($href);

    }

    /**
     * This method tests if hrefs containing & are correctly encoded.
     */
    function testSerializeEntity() {

        $href = new Href('http://example.org/?a&b', false);
        $this->assertEquals('http://example.org/?a&b',$href->getHref());

        $doc = new \DOMDocument();
        $root = $doc->createElement('d:anything');
        $root->setAttribute('xmlns:d','DAV:');

        $doc->appendChild($root);
        $server = new DAV\Server();
        $server->setBaseUri('/bla/');

        $href->serialize($server, $root);

        $xml = $doc->saveXML();

        $this->assertEquals(
'<?xml version="1.0"?>
<d:anything xmlns:d="DAV:"><d:href>http://example.org/?a&amp;b</d:href></d:anything>
', $xml);

    }

}
