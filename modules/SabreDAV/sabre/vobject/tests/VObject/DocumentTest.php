<?php

namespace Sabre\VObject;

class DocumentTest extends \PHPUnit_Framework_TestCase {

    function testGetDocumentType() {

        $doc = new MockDocument();
        $this->assertEquals(Document::UNKNOWN, $doc->getDocumentType());

    }

    function testConstruct() {

        $doc = new MockDocument('VLIST');
        $this->assertEquals('VLIST', $doc->name);

    }

    function testCreateComponent() {

        $vcal = new Component\VCalendar(array(), false);

        $event = $vcal->createComponent('VEVENT');

        $this->assertInstanceOf('Sabre\VObject\Component\VEvent', $event);
        $vcal->add($event);

        $prop = $vcal->createProperty('X-PROP','1234256',array('X-PARAM' => '3'));
        $this->assertInstanceOf('Sabre\VObject\Property', $prop);

        $event->add($prop);

        unset(
            $event->DTSTAMP,
            $event->UID
        );

        $out = $vcal->serialize();
        $this->assertEquals("BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nX-PROP;X-PARAM=3:1234256\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n", $out);

    }

    function testCreate() {

        $vcal = new Component\VCalendar(array(), false);

        $event = $vcal->create('VEVENT');
        $this->assertInstanceOf('Sabre\VObject\Component\VEvent', $event);

        $event = $vcal->create('CALSCALE');
        $this->assertInstanceOf('Sabre\VObject\Property\Text', $event);

    }

    function testGetClassNameForPropertyValue() {

        $vcal = new Component\VCalendar(array(), false);
        $this->assertEquals('Sabre\\VObject\\Property\\Text', $vcal->getClassNameForPropertyValue('TEXT'));
        $this->assertNull($vcal->getClassNameForPropertyValue('FOO'));

    }

}


class MockDocument extends Document {

}
