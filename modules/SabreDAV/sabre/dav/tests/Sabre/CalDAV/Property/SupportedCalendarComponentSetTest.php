<?php

namespace Sabre\CalDAV\Property;

class SupportedCalendarComponentSetTest extends \PHPUnit_Framework_TestCase {

    function testSimple() {

        $sccs = new SupportedCalendarComponentSet(array('VEVENT'));
        $this->assertEquals(array('VEVENT'), $sccs->getValue());

    }

    /**
     * @depends testSimple
     */
    function testSerialize() {

        $property = new SupportedCalendarComponentSet(array('VEVENT','VJOURNAL'));

        $doc = new \DOMDocument();
        $root = $doc->createElement('d:root');
        $root->setAttribute('xmlns:d','DAV:');
        $root->setAttribute('xmlns:cal',\Sabre\CalDAV\Plugin::NS_CALDAV);

        $doc->appendChild($root);
        $server = new \Sabre\DAV\Server();

        $property->serialize($server, $root);

        $xml = $doc->saveXML();

        $this->assertEquals(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . \Sabre\CalDAV\Plugin::NS_CALDAV . '">' .
'<cal:comp name="VEVENT"/>' .
'<cal:comp name="VJOURNAL"/>' .
'</d:root>
', $xml);

    }

    /**
     * @depends testSimple
     */
    function testUnserializer() {

        $xml = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . \Sabre\CalDAV\Plugin::NS_CALDAV . '">' .
'<cal:comp name="VEVENT"/>' .
'<cal:comp name="VJOURNAL"/>' .
'</d:root>';

        $dom = \Sabre\DAV\XMLUtil::loadDOMDocument($xml);

        $property = SupportedCalendarComponentSet::unserialize($dom->firstChild, array());

        $this->assertTrue($property instanceof SupportedCalendarComponentSet);
        $this->assertEquals(array(
            'VEVENT',
            'VJOURNAL',
           ),
           $property->getValue());

    }

}
