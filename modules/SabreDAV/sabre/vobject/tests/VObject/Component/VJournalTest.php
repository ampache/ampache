<?php

namespace Sabre\VObject\Component;

use Sabre\VObject\Component;
use Sabre\VObject\Reader;

class VJournalTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider timeRangeTestData
     */
    public function testInTimeRange(VJournal $vtodo,$start,$end,$outcome) {

        $this->assertEquals($outcome, $vtodo->isInTimeRange($start, $end));

    }

    public function testValidate() {

        $input = <<<HI
BEGIN:VCALENDAR
VERSION:2.0
PRODID:YoYo
BEGIN:VJOURNAL
UID:12345678
DTSTAMP:20140402T174100Z
END:VJOURNAL
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

    public function testValidateBroken() {

        $input = <<<HI
BEGIN:VCALENDAR
VERSION:2.0
PRODID:YoYo
BEGIN:VJOURNAL
UID:12345678
DTSTAMP:20140402T174100Z
URL:http://example.org/
URL:http://example.com/
END:VJOURNAL
END:VCALENDAR
HI;

        $obj = Reader::read($input);

        $warnings = $obj->validate();
        $messages = array();
        foreach($warnings as $warning) {
            $messages[] = $warning['message'];
        }

        $this->assertEquals(
            array("URL MUST NOT appear more than once in a VJOURNAL component"),
            $messages
        );

    }

    public function timeRangeTestData() {

        $calendar = new VCalendar();

        $tests = array();

        $vjournal = $calendar->createComponent('VJOURNAL');
        $vjournal->DTSTART = '20111223T120000Z';
        $tests[] = array($vjournal, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true);
        $tests[] = array($vjournal, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false);

        $vjournal2 = $calendar->createComponent('VJOURNAL');
        $vjournal2->DTSTART = '20111223';
        $vjournal2->DTSTART['VALUE'] = 'DATE';
        $tests[] = array($vjournal2, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true);
        $tests[] = array($vjournal2, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false);

        $vjournal3 = $calendar->createComponent('VJOURNAL');
        $tests[] = array($vjournal3, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), false);
        $tests[] = array($vjournal3, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false);

        return $tests;
    }



}

