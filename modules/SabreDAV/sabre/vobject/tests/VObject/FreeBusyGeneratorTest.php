<?php

namespace Sabre\VObject;

class FreeBusyGeneratorTest extends \PHPUnit_Framework_TestCase {

    function getInput() {

        $tests = array();

        $blob = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar
DTSTART:20110101T120000Z
DTEND:20110101T130000Z
END:VEVENT
END:VCALENDAR
ICS;

        $tests[] = array(
            $blob,
            "20110101T120000Z/20110101T130000Z"
        );

        // opaque, shows up
        $blob = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar2
TRANSP:OPAQUE
DTSTART:20110101T130000Z
DTEND:20110101T140000Z
END:VEVENT
END:VCALENDAR
ICS;

        $tests[] = array(
            $blob,
            "20110101T130000Z/20110101T140000Z"
        );

        // transparent, hidden
        $blob = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar3
TRANSP:TRANSPARENT
DTSTART:20110101T140000Z
DTEND:20110101T150000Z
END:VEVENT
END:VCALENDAR
ICS;

        $tests[] = array(
            $blob,
            null,
        );

        // cancelled, hidden
        $blob = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar4
STATUS:CANCELLED
DTSTART:20110101T160000Z
DTEND:20110101T170000Z
END:VEVENT
END:VCALENDAR
ICS;

        $tests[] = array(
            $blob,
            null,
        );

        // tentative, shows up
        $blob = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar5
STATUS:TENTATIVE
DTSTART:20110101T180000Z
DTEND:20110101T190000Z
END:VEVENT
END:VCALENDAR
ICS;

        $tests[] = array(
            $blob,
            '20110101T180000Z/20110101T190000Z',
        );

        // outside of time-range, hidden
        $blob = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar6
DTSTART:20110101T090000Z
DTEND:20110101T100000Z
END:VEVENT
END:VCALENDAR
ICS;

        $tests[] = array(
            $blob,
            null,
        );

        // outside of time-range, hidden
        $blob = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar7
DTSTART:20110104T090000Z
DTEND:20110104T100000Z
END:VEVENT
END:VCALENDAR
ICS;

        $tests[] = array(
            $blob,
            null,
        );

        // using duration, shows up
        $blob = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar8
DTSTART:20110101T190000Z
DURATION:PT1H
END:VEVENT
END:VCALENDAR
ICS;

        $tests[] = array(
            $blob,
            '20110101T190000Z/20110101T200000Z',
        );

        // Day-long event, shows up
        $blob = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar9
DTSTART;VALUE=DATE:20110102
END:VEVENT
END:VCALENDAR
ICS;

        $tests[] = array(
            $blob,
            '20110102T000000Z/20110103T000000Z',
        );


        // No duration, does not show up
        $blob = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar10
DTSTART:20110101T200000Z
END:VEVENT
END:VCALENDAR
ICS;

        $tests[] = array(
            $blob,
            null,
        );

        // encoded as object, shows up
        $blob = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar11
DTSTART:20110101T210000Z
DURATION:PT1H
END:VEVENT
END:VCALENDAR
ICS;

        $tests[] = array(
            Reader::read($blob),
            '20110101T210000Z/20110101T220000Z',
        );

        // Freebusy. Some parts show up
        $blob = <<<ICS
BEGIN:VCALENDAR
BEGIN:VFREEBUSY
FREEBUSY:20110103T010000Z/20110103T020000Z
FREEBUSY;FBTYPE=FREE:20110103T020000Z/20110103T030000Z
FREEBUSY:20110103T030000Z/20110103T040000Z,20110103T040000Z/20110103T050000Z
FREEBUSY:20120101T000000Z/20120101T010000Z
FREEBUSY:20110103T050000Z/PT1H
END:VFREEBUSY
END:VCALENDAR
ICS;

        $tests[] = array(
            Reader::read($blob),
            array(
                '20110103T010000Z/20110103T020000Z',
                '20110103T030000Z/20110103T040000Z',
                '20110103T040000Z/20110103T050000Z',
                '20110103T050000Z/20110103T060000Z',
            )
        );


        // Yearly recurrence rule, shows up
        $blob = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar13
DTSTART:20100101T220000Z
DTEND:20100101T230000Z
RRULE:FREQ=YEARLY
END:VEVENT
END:VCALENDAR
ICS;

        $tests[] = array(
            Reader::read($blob),
            '20110101T220000Z/20110101T230000Z',
        );


        // Yearly recurrence rule + duration, shows up
        $blob = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar14
DTSTART:20100101T230000Z
DURATION:PT1H
RRULE:FREQ=YEARLY
END:VEVENT
END:VCALENDAR
ICS;

        $tests[] = array(
            Reader::read($blob),
            '20110101T230000Z/20110102T000000Z',
        );

        // Floating time, no timezone
        $blob = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar
DTSTART:20110101T120000
DTEND:20110101T130000
END:VEVENT
END:VCALENDAR
ICS;

        $tests[] = array(
            $blob,
            "20110101T120000Z/20110101T130000Z"
        );

        // Floating time + reference timezone
        $blob = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar
DTSTART:20110101T120000
DTEND:20110101T130000
END:VEVENT
END:VCALENDAR
ICS;

        $tests[] = array(
            $blob,
            "20110101T170000Z/20110101T180000Z",
            new \DateTimeZone('America/Toronto')
        );

        // All-day event
        $blob = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar
DTSTART;VALUE=DATE:20110101
END:VEVENT
END:VCALENDAR
ICS;

        $tests[] = array(
            $blob,
            "20110101T000000Z/20110102T000000Z"
        );

        // All-day event + reference timezone
        $blob = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar
DTSTART;VALUE=DATE:20110101
END:VEVENT
END:VCALENDAR
ICS;

        $tests[] = array(
            $blob,
            "20110101T050000Z/20110102T050000Z",
            new \DateTimeZone('America/Toronto')
        );

        // Recurrence rule with no valid instances
        $blob = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar
DTSTART:20110101T100000Z
DTEND:20110103T120000Z
RRULE:FREQ=WEEKLY;COUNT=1
EXDATE:20110101T100000Z
END:VEVENT
END:VCALENDAR
ICS;

        $tests[] = array(
            $blob,
            array()
            );
        return $tests;

    }

    /**
     * @dataProvider getInput
     */
    function testGenerator($input, $expected, $timeZone = null) {

        $gen = new FreeBusyGenerator(
            new \DateTime('20110101T110000Z', new \DateTimeZone('UTC')),
            new \DateTime('20110103T110000Z', new \DateTimeZone('UTC')),
            $input,
            $timeZone
        );

        $result = $gen->getResult();

        $expected = (array)$expected;

        $freebusy = $result->VFREEBUSY->select('FREEBUSY');

        foreach($freebusy as $fb) {

            $this->assertContains((string)$fb, $expected, "$fb did not appear in our list of expected freebusy strings. This is concerning!");

            $k = array_search((string)$fb, $expected);
            unset($expected[$k]);

        }
        $this->assertTrue(
            count($expected) === 0,
            'There were elements in the expected array that were not found in the output: ' . "\n"  . print_r($expected,true) . "\n" . $result->serialize()
        );

    }

    function testGeneratorBaseObject() {

        $obj = new Component\VCalendar();
        $obj->METHOD = 'PUBLISH';

        $gen = new FreeBusyGenerator();
        $gen->setObjects(array());
        $gen->setBaseObject($obj);

        $result = $gen->getResult();

        $this->assertEquals('PUBLISH', $result->METHOD->getValue());

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testInvalidArg() {

        $gen = new FreeBusyGenerator(
            new \DateTime('2012-01-01'),
            new \DateTime('2012-12-31'),
            new \StdClass()
        );

    }

}
