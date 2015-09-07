<?php

namespace Sabre\VObject\RecurrenceIterator;

use Sabre\VObject\Reader;
use DateTime;

class OverrideFirstEventTest extends \PHPUnit_Framework_TestCase {

    function testOverrideFirstEvent() {

        $input =  <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar
DTSTART:20140803T120000Z
RRULE:FREQ=WEEKLY
SUMMARY:Original
END:VEVENT
BEGIN:VEVENT
UID:foobar
RECURRENCE-ID:20140803T120000Z
DTSTART:20140803T120000Z
SUMMARY:Overridden
END:VEVENT
END:VCALENDAR
ICS;

        $vcal = Reader::read($input);
        $vcal->expand(new DateTime('2014-08-01'), new DateTime('2014-09-01'));

        $expected = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar
RECURRENCE-ID:20140803T120000Z
DTSTART:20140803T120000Z
SUMMARY:Overridden
END:VEVENT
BEGIN:VEVENT
UID:foobar
DTSTART:20140810T120000Z
SUMMARY:Original
RECURRENCE-ID:20140810T120000Z
END:VEVENT
BEGIN:VEVENT
UID:foobar
DTSTART:20140817T120000Z
SUMMARY:Original
RECURRENCE-ID:20140817T120000Z
END:VEVENT
BEGIN:VEVENT
UID:foobar
DTSTART:20140824T120000Z
SUMMARY:Original
RECURRENCE-ID:20140824T120000Z
END:VEVENT
BEGIN:VEVENT
UID:foobar
DTSTART:20140831T120000Z
SUMMARY:Original
RECURRENCE-ID:20140831T120000Z
END:VEVENT
END:VCALENDAR

ICS;

        $newIcs = $vcal->serialize();
        $newIcs = str_replace("\r\n","\n", $newIcs);
        $this->assertEquals(
            $expected,
            $newIcs
        );


    }

    function testRemoveFirstEvent() {

        $input =  <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar
DTSTART:20140803T120000Z
RRULE:FREQ=WEEKLY
EXDATE:20140803T120000Z
SUMMARY:Original
END:VEVENT
END:VCALENDAR
ICS;

        $vcal = Reader::read($input);
        $vcal->expand(new DateTime('2014-08-01'), new DateTime('2014-08-19'));

        $expected = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foobar
DTSTART:20140810T120000Z
SUMMARY:Original
RECURRENCE-ID:20140810T120000Z
END:VEVENT
BEGIN:VEVENT
UID:foobar
DTSTART:20140817T120000Z
SUMMARY:Original
RECURRENCE-ID:20140817T120000Z
END:VEVENT
END:VCALENDAR

ICS;

        $newIcs = $vcal->serialize();
        $newIcs = str_replace("\r\n","\n", $newIcs);
        $this->assertEquals(
            $expected,
            $newIcs
        );


    }
}
