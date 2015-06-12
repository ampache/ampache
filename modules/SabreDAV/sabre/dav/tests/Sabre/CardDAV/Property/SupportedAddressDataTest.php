<?php

namespace Sabre\CardDAV\Property;

use Sabre\CardDAV;
use Sabre\DAV;

class SupportedAddressDataDataTest extends \PHPUnit_Framework_TestCase {

    function testSimple() {

        $property = new SupportedAddressData();
        $this->assertInstanceOf('Sabre\CardDAV\Property\SupportedAddressData', $property);

    }

    /**
     * @depends testSimple
     */
    function testSerialize() {

        $property = new SupportedAddressData();

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
'<card:address-data-type content-type="text/vcard" version="3.0"/>' .
'<card:address-data-type content-type="text/vcard" version="4.0"/>' .
'<card:address-data-type content-type="application/vcard+json" version="4.0"/>' .
'</card:root>
', $xml);

    }

}
