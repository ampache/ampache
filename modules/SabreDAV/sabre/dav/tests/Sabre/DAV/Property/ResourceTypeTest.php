<?php

namespace Sabre\DAV\Property;

use Sabre\DAV;

class ResourceTypeTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $resourceType = new ResourceType(array('{DAV:}collection'));
        $this->assertEquals(array('{DAV:}collection'),$resourceType->getValue());

        $resourceType = new ResourceType('{DAV:}principal');
        $this->assertEquals(array('{DAV:}principal'),$resourceType->getValue());

    }

    /**
     * @depends testConstruct
     */
    function testSerialize() {

        $resourceType = new ResourceType(array('{DAV:}collection','{DAV:}principal'));

        $doc = new \DOMDocument();
        $root = $doc->createElement('d:anything');
        $root->setAttribute('xmlns:d','DAV:');

        $doc->appendChild($root);
        $server = new DAV\Server();
        $resourceType->serialize($server, $root);

        $xml = $doc->saveXML();

        $this->assertEquals(
'<?xml version="1.0"?>
<d:anything xmlns:d="DAV:"><d:collection/><d:principal/></d:anything>
', $xml);

    }

    /**
     * @depends testSerialize
     */
    function testSerializeCustomNS() {

        $resourceType = new ResourceType(array('{http://example.org/NS}article'));

        $doc = new \DOMDocument();
        $root = $doc->createElement('d:anything');
        $root->setAttribute('xmlns:d','DAV:');

        $doc->appendChild($root);
        $server = new DAV\Server();
        $resourceType->serialize($server, $root);

        $xml = $doc->saveXML();

        $this->assertEquals(
'<?xml version="1.0"?>
<d:anything xmlns:d="DAV:"><custom:article xmlns:custom="http://example.org/NS"/></d:anything>
', $xml);

    }

    /**
     * @depends testConstruct
     */
    function testIs() {

        $resourceType = new ResourceType(array('{DAV:}collection','{DAV:}principal'));
        $this->assertTrue($resourceType->is('{DAV:}collection'));
        $this->assertFalse($resourceType->is('{DAV:}blabla'));

    }

    /**
     * @depends testConstruct
     */
    function testAdd() {

        $resourceType = new ResourceType(array('{DAV:}collection','{DAV:}principal'));
        $resourceType->add('{DAV:}foo');
        $this->assertEquals(array('{DAV:}collection','{DAV:}principal','{DAV:}foo'), $resourceType->getValue());

    }

    /**
     * @depends testConstruct
     */
    function testUnserialize() {

        $xml ='<?xml version="1.0"?>
<d:anything xmlns:d="DAV:"><d:collection/><d:principal/></d:anything>
';

        $dom = DAV\XMLUtil::loadDOMDocument($xml);

        $resourceType = ResourceType::unserialize($dom->firstChild, array());
        $this->assertEquals(array('{DAV:}collection','{DAV:}principal'),$resourceType->getValue());

    }

}
