<?php

namespace Sabre\DAVACL\Property;

use Sabre\DAV;
use Sabre\HTTP;

class PrincipalTest extends \PHPUnit_Framework_TestCase {

    function testSimple() {

        $principal = new Principal(Principal::UNAUTHENTICATED);
        $this->assertEquals(Principal::UNAUTHENTICATED, $principal->getType());
        $this->assertNull($principal->getHref());

        $principal = new Principal(Principal::AUTHENTICATED);
        $this->assertEquals(Principal::AUTHENTICATED, $principal->getType());
        $this->assertNull($principal->getHref());

        $principal = new Principal(Principal::HREF,'admin');
        $this->assertEquals(Principal::HREF, $principal->getType());
        $this->assertEquals('admin',$principal->getHref());

    }

    /**
     * @depends testSimple
     * @expectedException Sabre\DAV\Exception
     */
    function testNoHref() {

        $principal = new Principal(Principal::HREF);

    }

    /**
     * @depends testSimple
     */
    function testSerializeUnAuthenticated() {

        $prin = new Principal(Principal::UNAUTHENTICATED);

        $doc = new \DOMDocument();
        $root = $doc->createElement('d:principal');
        $root->setAttribute('xmlns:d','DAV:');

        $doc->appendChild($root);
        $node = new DAV\SimpleCollection('rootdir');
        $server = new DAV\Server($node);

        $prin->serialize($server, $root);

        $xml = $doc->saveXML();

        $this->assertEquals(
'<?xml version="1.0"?>
<d:principal xmlns:d="DAV:">' .
'<d:unauthenticated/>' .
'</d:principal>
', $xml);

    }


    /**
     * @depends testSerializeUnAuthenticated
     */
    function testSerializeAuthenticated() {

        $prin = new Principal(Principal::AUTHENTICATED);

        $doc = new \DOMDocument();
        $root = $doc->createElement('d:principal');
        $root->setAttribute('xmlns:d','DAV:');

        $doc->appendChild($root);
        $objectTree = new DAV\Tree(new DAV\SimpleCollection('rootdir'));
        $server = new DAV\Server($objectTree);

        $prin->serialize($server, $root);

        $xml = $doc->saveXML();

        $this->assertEquals(
'<?xml version="1.0"?>
<d:principal xmlns:d="DAV:">' .
'<d:authenticated/>' .
'</d:principal>
', $xml);

    }


    /**
     * @depends testSerializeUnAuthenticated
     */
    function testSerializeHref() {

        $prin = new Principal(Principal::HREF,'principals/admin');

        $doc = new \DOMDocument();
        $root = $doc->createElement('d:principal');
        $root->setAttribute('xmlns:d','DAV:');

        $doc->appendChild($root);
        $objectTree = new DAV\Tree(new DAV\SimpleCollection('rootdir'));
        $server = new DAV\Server($objectTree);

        $prin->serialize($server, $root);

        $xml = $doc->saveXML();

        $this->assertEquals(
'<?xml version="1.0"?>
<d:principal xmlns:d="DAV:">' .
'<d:href>/principals/admin</d:href>' .
'</d:principal>
', $xml);

    }

    function testUnserializeHref() {

        $xml = '<?xml version="1.0"?>
<d:principal xmlns:d="DAV:">' .
'<d:href>/principals/admin</d:href>' .
'</d:principal>';

        $dom = DAV\XMLUtil::loadDOMDocument($xml);

        $principal = Principal::unserialize($dom->firstChild, array());
        $this->assertEquals(Principal::HREF, $principal->getType());
        $this->assertEquals('/principals/admin', $principal->getHref());

    }

    function testUnserializeAuthenticated() {

        $xml = '<?xml version="1.0"?>
<d:principal xmlns:d="DAV:">' .
'  <d:authenticated />' .
'</d:principal>';

        $dom = DAV\XMLUtil::loadDOMDocument($xml);

        $principal = Principal::unserialize($dom->firstChild, array());
        $this->assertEquals(Principal::AUTHENTICATED, $principal->getType());

    }

    function testUnserializeUnauthenticated() {

        $xml = '<?xml version="1.0"?>
<d:principal xmlns:d="DAV:">' .
'  <d:unauthenticated />' .
'</d:principal>';

        $dom = DAV\XMLUtil::loadDOMDocument($xml);

        $principal = Principal::unserialize($dom->firstChild, array());
        $this->assertEquals(Principal::UNAUTHENTICATED, $principal->getType());

    }

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testUnserializeUnknown() {

        $xml = '<?xml version="1.0"?>
<d:principal xmlns:d="DAV:">' .
'  <d:foo />' .
'</d:principal>';

        $dom = DAV\XMLUtil::loadDOMDocument($xml);

        Principal::unserialize($dom->firstChild, array());

    }

}
