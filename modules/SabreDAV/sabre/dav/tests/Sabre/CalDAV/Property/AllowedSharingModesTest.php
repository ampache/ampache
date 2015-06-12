<?php

namespace Sabre\CalDAV\Property;

use Sabre\CalDAV;
use Sabre\DAV;

class AllowedSharingModesTest extends \PHPUnit_Framework_TestCase {

    function testSimple() {

        $sccs = new AllowedSharingModes(true,true);
        $this->assertInstanceOf('Sabre\CalDAV\Property\AllowedSharingModes', $sccs);

    }

    /**
     * @depends testSimple
     */
    function testSerialize() {

        $property = new AllowedSharingModes(true,true);

        $doc = new \DOMDocument();
        $root = $doc->createElement('d:root');
        $root->setAttribute('xmlns:d','DAV:');
        $root->setAttribute('xmlns:cal',CalDAV\Plugin::NS_CALDAV);
        $root->setAttribute('xmlns:cs',CalDAV\Plugin::NS_CALENDARSERVER);

        $doc->appendChild($root);
        $server = new DAV\Server();

        $property->serialize($server, $root);

        $xml = $doc->saveXML();

        $this->assertEquals(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . CalDAV\Plugin::NS_CALDAV . '" xmlns:cs="' . CalDAV\Plugin::NS_CALENDARSERVER . '">' .
'<cs:can-be-shared/>' .
'<cs:can-be-published/>' .
'</d:root>
', $xml);

    }

}
