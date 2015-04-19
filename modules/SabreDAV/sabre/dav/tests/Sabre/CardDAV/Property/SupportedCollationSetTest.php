<?php

namespace Sabre\CardDAV\Property;

use Sabre\CardDAV;
use Sabre\DAV;

class SupportedCollationSetTest extends \PHPUnit_Framework_TestCase {

    function testSimple() {

        $property = new SupportedCollationSet();
        $this->assertInstanceOf('Sabre\CardDAV\Property\SupportedCollationSet', $property);

    }

    /**
     * @depends testSimple
     */
    function testSerialize() {

        $property = new SupportedCollationSet();

        $doc = new \DOMDocument();
        $root = $doc->createElementNS(CardDAV\Plugin::NS_CARDDAV, 'card:root');
        $root->setAttribute('xmlns:d','DAV:');

        $doc->appendChild($root);
        $server = new DAV\Server();

        $property->serialize($server, $root);

        $xml = $doc->saveXML();

        $this->assertEquals(
'<?xml version="1.0"?>
<card:root xmlns:card="' . CardDAV\Plugin::NS_CARDDAV . '" xmlns:d="DAV:">' .
'<card:supported-collation>i;ascii-casemap</card:supported-collation>' .
'<card:supported-collation>i;octet</card:supported-collation>' .
'<card:supported-collation>i;unicode-casemap</card:supported-collation>' .
'</card:root>
', $xml);

    }

}
