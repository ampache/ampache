<?php

namespace Sabre\VObject;

use DateTime;
use DateTimeZone;
use DateInterval;

class DateTimeParserTest extends \PHPUnit_Framework_TestCase {

    function testParseICalendarDuration() {

        $this->assertEquals('+1 weeks', DateTimeParser::parseDuration('P1W',true));
        $this->assertEquals('+5 days',  DateTimeParser::parseDuration('P5D',true));
        $this->assertEquals('+5 days 3 hours 50 minutes 12 seconds', DateTimeParser::parseDuration('P5DT3H50M12S',true));
        $this->assertEquals('-1 weeks 50 minutes', DateTimeParser::parseDuration('-P1WT50M',true));
        $this->assertEquals('+50 days 3 hours 2 seconds', DateTimeParser::parseDuration('+P50DT3H2S',true));
        $this->assertEquals('+0 seconds', DateTimeParser::parseDuration('+PT0S',true));
        $this->assertEquals(new DateInterval('PT0S'), DateTimeParser::parseDuration('PT0S'));

    }

    function testParseICalendarDurationDateInterval() {

        $expected = new DateInterval('P7D');
        $this->assertEquals($expected, DateTimeParser::parseDuration('P1W'));
        $this->assertEquals($expected, DateTimeParser::parse('P1W'));

        $expected = new DateInterval('PT3M');
        $expected->invert = true;
        $this->assertEquals($expected, DateTimeParser::parseDuration('-PT3M'));

    }

    /**
     * @expectedException LogicException
     */
    function testParseICalendarDurationFail() {

        DateTimeParser::parseDuration('P1X',true);

    }

    function testParseICalendarDateTime() {

        $dateTime = DateTimeParser::parseDateTime('20100316T141405');

        $compare = new DateTime('2010-03-16 14:14:05',new DateTimeZone('UTC'));

        $this->assertEquals($compare, $dateTime);

    }

    /**
     * @depends testParseICalendarDateTime
     * @expectedException LogicException
     */
    function testParseICalendarDateTimeBadFormat() {

        $dateTime = DateTimeParser::parseDateTime('20100316T141405 ');

    }

    /**
     * @depends testParseICalendarDateTime
     */
    function testParseICalendarDateTimeUTC() {

        $dateTime = DateTimeParser::parseDateTime('20100316T141405Z');

        $compare = new DateTime('2010-03-16 14:14:05',new DateTimeZone('UTC'));
        $this->assertEquals($compare, $dateTime);

    }

    /**
     * @depends testParseICalendarDateTime
     */
    function testParseICalendarDateTimeUTC2() {

        $dateTime = DateTimeParser::parseDateTime('20101211T160000Z');

        $compare = new DateTime('2010-12-11 16:00:00',new DateTimeZone('UTC'));
        $this->assertEquals($compare, $dateTime);

    }

    /**
     * @depends testParseICalendarDateTime
     */
    function testParseICalendarDateTimeCustomTimeZone() {

        $dateTime = DateTimeParser::parseDateTime('20100316T141405', new DateTimeZone('Europe/Amsterdam'));

        $compare = new DateTime('2010-03-16 14:14:05',new DateTimeZone('Europe/Amsterdam'));
        $this->assertEquals($compare, $dateTime);

    }

    function testParseICalendarDate() {

        $dateTime = DateTimeParser::parseDate('20100316');

        $expected = new DateTime('2010-03-16 00:00:00',new DateTimeZone('UTC'));

        $this->assertEquals($expected, $dateTime);

        $dateTime = DateTimeParser::parse('20100316');
        $this->assertEquals($expected, $dateTime);

    }

    /**
     * TCheck if a date with year > 4000 will not throw an exception. iOS seems to use 45001231 in yearly recurring events
     */
    function testParseICalendarDateGreaterThan4000() {

        $dateTime = DateTimeParser::parseDate('45001231');

        $expected = new DateTime('4500-12-31 00:00:00',new DateTimeZone('UTC'));

        $this->assertEquals($expected, $dateTime);

        $dateTime = DateTimeParser::parse('45001231');
        $this->assertEquals($expected, $dateTime);

    }

    /**
     * Check if a datetime with year > 4000 will not throw an exception. iOS seems to use 45001231T235959 in yearly recurring events
     */
    function testParseICalendarDateTimeGreaterThan4000() {

        $dateTime = DateTimeParser::parseDateTime('45001231T235959');

        $expected = new DateTime('4500-12-31 23:59:59',new DateTimeZone('UTC'));

        $this->assertEquals($expected, $dateTime);

        $dateTime = DateTimeParser::parse('45001231T235959');
        $this->assertEquals($expected, $dateTime);

    }

    /**
     * @depends testParseICalendarDate
     * @expectedException LogicException
     */
    function testParseICalendarDateBadFormat() {

        $dateTime = DateTimeParser::parseDate('20100316T141405');

    }

    /**
     * @dataProvider vcardDates
     */
    function testVCardDate($input, $output) {

        $this->assertEquals(
            $output,
            DateTimeParser::parseVCardDateTime($input)
        );

    }

    /**
     * @dataProvider vcardDates
     * @expectedException \InvalidArgumentException
     */
    function testBadVCardDate() {

        DateTimeParser::parseVCardDateTime('1985---01');

    }

    /**
     * @dataProvider vcardDates
     * @expectedException \InvalidArgumentException
     */
    function testBadVCardTime() {

        DateTimeParser::parseVCardTime('23:12:166');

    }

    function vcardDates() {

        return array(
            array(
                "19961022T140000",
                array(
                    "year" => 1996,
                    "month" => 10,
                    "date" => 22,
                    "hour" => 14,
                    "minute" => 00,
                    "second" => 00,
                    "timezone" => null
                ),
            ),
            array(
                "--1022T1400",
                array(
                    "year" => null,
                    "month" => 10,
                    "date" => 22,
                    "hour" => 14,
                    "minute" => 00,
                    "second" => null,
                    "timezone" => null
                ),
            ),
            array(
                "---22T14",
                array(
                    "year" => null,
                    "month" => null,
                    "date" => 22,
                    "hour" => 14,
                    "minute" => null,
                    "second" => null,
                    "timezone" => null
                ),
            ),
            array(
                "19850412",
                array(
                    "year" => 1985,
                    "month" => 4,
                    "date" => 12,
                    "hour" => null,
                    "minute" => null,
                    "second" => null,
                    "timezone" => null
                ),
            ),
            array(
                "1985-04",
                array(
                    "year" => 1985,
                    "month" => 04,
                    "date" => null,
                    "hour" => null,
                    "minute" => null,
                    "second" => null,
                    "timezone" => null
                ),
            ),
            array(
                "1985",
                array(
                    "year" => 1985,
                    "month" => null,
                    "date" => null,
                    "hour" => null,
                    "minute" => null,
                    "second" => null,
                    "timezone" => null
                ),
            ),
            array(
                "--0412",
                array(
                    "year" => null,
                    "month" => 4,
                    "date" => 12,
                    "hour" => null,
                    "minute" => null,
                    "second" => null,
                    "timezone" => null
                ),
            ),
            array(
                "---12",
                array(
                    "year" => null,
                    "month" => null,
                    "date" => 12,
                    "hour" => null,
                    "minute" => null,
                    "second" => null,
                    "timezone" => null
                ),
            ),
            array(
                "T102200",
                array(
                    "year" => null,
                    "month" => null,
                    "date" => null,
                    "hour" => 10,
                    "minute" => 22,
                    "second" => 0,
                    "timezone" => null
                ),
            ),
            array(
                "T1022",
                array(
                    "year" => null,
                    "month" => null,
                    "date" => null,
                    "hour" => 10,
                    "minute" => 22,
                    "second" => null,
                    "timezone" => null
                ),
            ),
            array(
                "T10",
                array(
                    "year" => null,
                    "month" => null,
                    "date" => null,
                    "hour" => 10,
                    "minute" => null,
                    "second" => null,
                    "timezone" => null
                ),
            ),
            array(
                "T-2200",
                array(
                    "year" => null,
                    "month" => null,
                    "date" => null,
                    "hour" => null,
                    "minute" => 22,
                    "second" => 00,
                    "timezone" => null
                ),
            ),
            array(
                "T--00",
                array(
                    "year" => null,
                    "month" => null,
                    "date" => null,
                    "hour" => null,
                    "minute" => null,
                    "second" => 00,
                    "timezone" => null
                ),
            ),
            array(
                "T102200Z",
                array(
                    "year" => null,
                    "month" => null,
                    "date" => null,
                    "hour" => 10,
                    "minute" => 22,
                    "second" => 00,
                    "timezone" => 'Z'
                ),
            ),
            array(
                "T102200-0800",
                array(
                    "year" => null,
                    "month" => null,
                    "date" => null,
                    "hour" => 10,
                    "minute" => 22,
                    "second" => 00,
                    "timezone" => '-0800'
                ),
            ),

            // extended format
            array(
                "2012-11-29T15:10:53Z",
                array(
                    "year" => 2012,
                    "month" => 11,
                    "date" => 29,
                    "hour" => 15,
                    "minute" => 10,
                    "second" => 53,
                    "timezone" => 'Z'
                ),
            ),

            // with milliseconds
            array(
                "20121129T151053.123Z",
                array(
                    "year" => 2012,
                    "month" => 11,
                    "date" => 29,
                    "hour" => 15,
                    "minute" => 10,
                    "second" => 53,
                    "timezone" => 'Z'
                ),
            ),

            // extended format with milliseconds
            array(
                "2012-11-29T15:10:53.123Z",
                array(
                    "year" => 2012,
                    "month" => 11,
                    "date" => 29,
                    "hour" => 15,
                    "minute" => 10,
                    "second" => 53,
                    "timezone" => 'Z'
                ),
            ),

        );

    }

}
