<?php

namespace Sabre\CalDAV\Property;

use Sabre\CalDAV;
use Sabre\DAV;

class ScheduleCalendarTranspTest extends \PHPUnit_Framework_TestCase {

    function testSimple() {

        $sccs = new ScheduleCalendarTransp('transparent');
        $this->assertEquals('transparent', $sccs->getValue());

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testBadArg() {

        $sccs = new ScheduleCalendarTransp('foo');

    }

    function values() {

        return array(
            array('transparent'),
            array('opaque'),
        );

    }

    /**
     * @depends testSimple
     * @dataProvider values
     */
    function testSerialize($value) {

        $property = new ScheduleCalendarTransp($value);

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
'<cal:' . $value . '/>' .
'</d:root>
', $xml);

    }

    /**
     * @depends testSimple
     * @dataProvider values
     */
    function testUnserializer($value) {

        $xml = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . CalDAV\Plugin::NS_CALDAV . '">' .
'<cal:'.$value.'/>' .
'</d:root>';

        $dom = DAV\XMLUtil::loadDOMDocument($xml);

        $property = ScheduleCalendarTransp::unserialize($dom->firstChild, array());

        $this->assertTrue($property instanceof ScheduleCalendarTransp);
        $this->assertEquals($value, $property->getValue());

    }

    /**
     * @depends testSimple
     */
    function testUnserializerBadData() {

        $xml = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . CalDAV\Plugin::NS_CALDAV . '">' .
'<cal:foo/>' .
'</d:root>';

        $dom = DAV\XMLUtil::loadDOMDocument($xml);

        $this->assertNull(ScheduleCalendarTransp::unserialize($dom->firstChild, array()));

    }
}
