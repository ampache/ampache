<?php

namespace Sabre\CalDAV\Property;

use Sabre\CalDAV;
use Sabre\DAV;

class SupportedCollationSetTest extends \PHPUnit_Framework_TestCase {

    function testSimple() {

        $scs = new SupportedCollationSet();
        $this->assertInstanceOf('Sabre\CalDAV\Property\SupportedCollationSet', $scs);

    }

    /**
     * @depends testSimple
     */
    function testSerialize() {

        $property = new SupportedCollationSet();

        $doc = new \DOMDocument();
        $root = $doc->createElement('d:root');
        $root->setAttribute('xmlns:d','DAV:');
        $root->setAttribute('xmlns:cal',CalDAV\Plugin::NS_CALDAV);

        $doc->appendChild($root);
        $server = new DAV\Server();

        $property->serialize($server, $root);

        $xml = $doc->saveXML();

        $this->assertEquals(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . CalDAV\Plugin::NS_CALDAV . '">' .
'<cal:supported-collation>i;ascii-casemap</cal:supported-collation>' .
'<cal:supported-collation>i;octet</cal:supported-collation>' .
'<cal:supported-collation>i;unicode-casemap</cal:supported-collation>' .
'</d:root>
', $xml);

    }

}
