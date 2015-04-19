<?php

namespace Sabre\CalDAV\Property;

use Sabre\CalDAV;
use Sabre\DAV;

class SupportedCalendarDataTest extends \PHPUnit_Framework_TestCase {

    function testSimple() {

        $sccs = new SupportedCalendarData();
        $this->assertInstanceOf('Sabre\CalDAV\Property\SupportedCalendarData', $sccs);

    }

    /**
     * @depends testSimple
     */
    function testSerialize() {

        $property = new SupportedCalendarData();

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
'<cal:calendar-data content-type="text/calendar" version="2.0"/>' .
'<cal:calendar-data content-type="application/calendar+json"/>' .
'</d:root>
', $xml);

    }

}
