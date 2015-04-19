<?php

namespace Sabre\DAV\Property;
use Sabre\DAV;

class HrefListTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $href = new HrefList(array('foo','bar'));
        $this->assertEquals(array('foo','bar'),$href->getHrefs());

    }

    function testSerialize() {

        $href = new HrefList(array('foo','bar'));
        $this->assertEquals(array('foo','bar'),$href->getHrefs());

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
<d:anything xmlns:d="DAV:"><d:href>/bla/foo</d:href><d:href>/bla/bar</d:href></d:anything>
', $xml);

    }

    function testSerializeNoPrefix() {

        $href = new HrefList(array('foo','bar'), false);
        $this->assertEquals(array('foo','bar'),$href->getHrefs());

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
<d:anything xmlns:d="DAV:"><d:href>foo</d:href><d:href>bar</d:href></d:anything>
', $xml);

    }

    function testUnserialize() {

        $xml = '<?xml version="1.0"?>
<d:anything xmlns:d="urn:DAV"><d:href>/bla/foo</d:href><d:href>/bla/bar</d:href></d:anything>
';

        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $href = HrefList::unserialize($dom->firstChild, array());
        $this->assertEquals(array('/bla/foo','/bla/bar'),$href->getHrefs());

    }

    function testUnserializeIncompatible() {

        $xml = '<?xml version="1.0"?>
<d:anything xmlns:d="urn:DAV"><d:href2>/bla/foo</d:href2></d:anything>
';

        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $href = HrefList::unserialize($dom->firstChild, array());
        $this->assertEquals(array(), $href->getHrefs());

    }

}
