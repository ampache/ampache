<?php

namespace Sabre\VObject\Component;

use
    Sabre\VObject\Component,
    Sabre\VObject\Reader;

class VTodoTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider timeRangeTestData
     */
    public function testInTimeRange(VTodo $vtodo,$start,$end,$outcome) {

        $this->assertEquals($outcome, $vtodo->isInTimeRange($start, $end));

    }

    public function timeRangeTestData() {

        $tests = array();

        $calendar = new VCalendar();

        $vtodo = $calendar->createComponent('VTODO');
        $vtodo->DTSTART = '20111223T120000Z';
        $tests[] = array($vtodo, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true);
        $tests[] = array($vtodo, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false);

        $vtodo2 = clone $vtodo;
        $vtodo2->DURATION = 'P1D';
        $tests[] = array($vtodo2, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true);
        $tests[] = array($vtodo2, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false);

        $vtodo3 = clone $vtodo;
        $vtodo3->DUE = '20111225';
        $tests[] = array($vtodo3, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true);
        $tests[] = array($vtodo3, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false);

        $vtodo4 = $calendar->createComponent('VTODO');
        $vtodo4->DUE = '20111225';
        $tests[] = array($vtodo4, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true);
        $tests[] = array($vtodo4, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false);

        $vtodo5 = $calendar->createComponent('VTODO');
        $vtodo5->COMPLETED = '20111225';
        $tests[] = array($vtodo5, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true);
        $tests[] = array($vtodo5, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false);

        $vtodo6 = $calendar->createComponent('VTODO');
        $vtodo6->CREATED = '20111225';
        $tests[] = array($vtodo6, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true);
        $tests[] = array($vtodo6, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false);

        $vtodo7 = $calendar->createComponent('VTODO');
        $vtodo7->CREATED = '20111225';
        $vtodo7->COMPLETED = '20111226';
        $tests[] = array($vtodo7, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true);
        $tests[] = array($vtodo7, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false);

        $vtodo7 = $calendar->createComponent('VTODO');
        $tests[] = array($vtodo7, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true);
        $tests[] = array($vtodo7, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), true);

        return $tests;

    }

    public function testValidate() {

        $input = <<<HI
BEGIN:VCALENDAR
VERSION:2.0
PRODID:YoYo
BEGIN:VTODO
UID:1234-21355-123156
DTSTAMP:20140402T183400Z
END:VTODO
END:VCALENDAR
HI;

        $obj = Reader::read($input);

        $warnings = $obj->validate();
        $messages = array();
        foreach($warnings as $warning) {
            $messages[] = $warning['message'];
        }

        $this->assertEquals(array(), $messages);

    }

    public function testValidateInvalid() {

        $input = <<<HI
BEGIN:VCALENDAR
VERSION:2.0
PRODID:YoYo
BEGIN:VTODO
END:VTODO
END:VCALENDAR
HI;

        $obj = Reader::read($input);

        $warnings = $obj->validate();
        $messages = array();
        foreach($warnings as $warning) {
            $messages[] = $warning['message'];
        }

        $this->assertEquals(array(
            "UID MUST appear exactly once in a VTODO component",
            "DTSTAMP MUST appear exactly once in a VTODO component",
        ), $messages);

    }

    public function testValidateDUEDTSTARTMisMatch() {

        $input = <<<HI
BEGIN:VCALENDAR
VERSION:2.0
PRODID:YoYo
BEGIN:VTODO
UID:FOO
DTSTART;VALUE=DATE-TIME:20140520T131600Z
DUE;VALUE=DATE:20140520
DTSTAMP;VALUE=DATE-TIME:20140520T131600Z
END:VTODO
END:VCALENDAR
HI;

        $obj = Reader::read($input);

        $warnings = $obj->validate();
        $messages = array();
        foreach($warnings as $warning) {
            $messages[] = $warning['message'];
        }

        $this->assertEquals(array(
            "The value type (DATE or DATE-TIME) must be identical for DUE and DTSTART",
        ), $messages);

    }

    public function testValidateDUEbeforeDTSTART() {

        $input = <<<HI
BEGIN:VCALENDAR
VERSION:2.0
PRODID:YoYo
BEGIN:VTODO
UID:FOO
DTSTART;VALUE=DATE:20140520
DUE;VALUE=DATE:20140518
DTSTAMP;VALUE=DATE-TIME:20140520T131600Z
END:VTODO
END:VCALENDAR
HI;

        $obj = Reader::read($input);

        $warnings = $obj->validate();
        $messages = array();
        foreach($warnings as $warning) {
            $messages[] = $warning['message'];
        }

        $this->assertEquals(array(
            "DUE must occur after DTSTART",
        ), $messages);

    }

}

