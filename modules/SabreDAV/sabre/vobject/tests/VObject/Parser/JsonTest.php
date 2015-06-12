<?php

namespace Sabre\VObject\Parser;

use
    Sabre\VObject;

class JsonTest extends \PHPUnit_Framework_TestCase {

    function testRoundTripJCard() {

        $input = array(
            "vcard",
            array(
                array(
                    "version",
                    new \StdClass(),
                    "text",
                    "4.0"
                ),
                array(
                    "prodid",
                    new \StdClass(),
                    "text",
                    "-//Sabre//Sabre VObject " . VObject\Version::VERSION . "//EN",
                ),
                array(
                    "uid",
                    new \StdClass(),
                    "text",
                    "foo",
                ),
                array(
                    "bday",
                    new \StdClass(),
                    "date-and-or-time",
                    "1985-04-07",
                ),
                array(
                    "rev",
                    new \StdClass(),
                    "timestamp",
                    "1995-10-31T22:27:10Z",
                ),
                array(
                    "lang",
                    new \StdClass(),
                    "language-tag",
                    "nl",
                ),
                array(
                    "n",
                    new \StdClass(),
                    "text",
                    array("Last", "First", "Middle", "", ""),
                ),
                array(
                    "tel",
                    (object)array(
                        "group" => "item1",
                    ),
                    "text",
                    "+1 555 123456",
                ),
                array(
                    "x-ab-label",
                    (object)array(
                        "group" => "item1",
                    ),
                    "unknown",
                    "Walkie Talkie",
                ),
                array(
                    "adr",
                    new \StdClass(),
                    "text",
                        array(
                            "",
                            "",
                            array("My Street", "Left Side", "Second Shack"),
                            "Hometown",
                            "PA",
                            "18252",
                            "U.S.A",
                        ),
                ),
                array(
                    "bday",
                    (object)array(
                        'x-param' => array(1,2),
                    ),
                    "date",
                    "1979-12-25",
                ),
                array(
                    "bday",
                    new \StdClass(),
                    "date-time",
                    "1979-12-25T02:00:00",
                ),
                array(
                    "x-truncated",
                    new \StdClass(),
                    "date",
                    "--12-25",
                ),
                array(
                    "x-time-local",
                    new \StdClass(),
                    "time",
                    "12:30:00"
                ),
                array(
                    "x-time-utc",
                    new \StdClass(),
                    "time",
                    "12:30:00Z"
                ),
                array(
                    "x-time-offset",
                    new \StdClass(),
                    "time",
                    "12:30:00-08:00"
                ),
                array(
                    "x-time-reduced",
                    new \StdClass(),
                    "time",
                    "23"
                ),
                array(
                    "x-time-truncated",
                    new \StdClass(),
                    "time",
                    "--30"
                ),
                array(
                    "x-karma-points",
                    new \StdClass(),
                    "integer",
                    42
                ),
                array(
                    "x-grade",
                    new \StdClass(),
                    "float",
                    1.3
                ),
                array(
                    "tz",
                    new \StdClass(),
                    "utc-offset",
                    "-05:00",
                ),
            ),
        );

        $parser = new Json(json_encode($input));
        $vobj = $parser->parse();        

        $version = VObject\Version::VERSION;


        $result = $vobj->serialize();
        $expected = <<<VCF
BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject $version//EN
UID:foo
BDAY:1985-04-07
REV:1995-10-31T22:27:10Z
LANG:nl
N:Last;First;Middle;;
item1.TEL:+1 555 123456
item1.X-AB-LABEL:Walkie Talkie
ADR:;;My Street,Left Side,Second Shack;Hometown;PA;18252;U.S.A
BDAY;X-PARAM=1,2;VALUE=DATE:1979-12-25
BDAY;VALUE=DATE-TIME:1979-12-25T02:00:00
X-TRUNCATED;VALUE=DATE:--12-25
X-TIME-LOCAL;VALUE=TIME:12:30:00
X-TIME-UTC;VALUE=TIME:12:30:00Z
X-TIME-OFFSET;VALUE=TIME:12:30:00-08:00
X-TIME-REDUCED;VALUE=TIME:23
X-TIME-TRUNCATED;VALUE=TIME:--30
X-KARMA-POINTS;VALUE=INTEGER:42
X-GRADE;VALUE=FLOAT:1.3
TZ;VALUE=UTC-OFFSET:-05:00
END:VCARD

VCF;
        $this->assertEquals($expected, str_replace("\r", "", $result));

        $this->assertEquals(
            $input,
            $vobj->jsonSerialize()
        );

    }

    function testRoundTripJCal() {

        $input = array(
            "vcalendar",
            array(
                array(
                    "version",
                    new \StdClass(),
                    "text",
                    "2.0"
                ),
                array(
                    "prodid",
                    new \StdClass(),
                    "text",
                    "-//Sabre//Sabre VObject " . VObject\Version::VERSION . "//EN",
                ),
                array(
                    "calscale",
                    new \StdClass(),
                    "text",
                    "GREGORIAN"
                ),
            ),
            array(
                array("vevent",
                    array(
                        array(
                            "uid", new \StdClass(), "text", "foo",
                        ),
                        array(
                            "dtstart", new \StdClass(), "date", "2013-05-26",
                        ),
                        array(
                            "duration", new \StdClass(), "duration", "P1D",
                        ),
                        array(
                            "categories", new \StdClass(), "text", "home", "testing",
                        ),
                        array(
                            "created", new \StdClass(), "date-time", "2013-05-26T18:10:00Z",
                        ),
                        array(
                            "attach", new \StdClass(), "binary", base64_encode('attachment')
                        ),
                        array(
                            "attendee", new \StdClass(), "cal-address", "mailto:armin@example.org",
                        ),
                        array(
                            "geo", new \StdClass(), "float", array(51.96668, 7.61876),
                        ),
                        array(
                            "sequence", new \StdClass(), "integer", 5
                        ),
                        array(
                            "freebusy", new \StdClass(), "period",  array("2013-05-26T21:02:13", "PT1H"), array("2013-06-26T12:00:00", "2013-06-26T13:00:00"),
                        ),
                        array(
                            "url", new \StdClass(), "uri", "http://example.org/",
                        ),
                        array(
                            "tzoffsetfrom", new \StdClass(), "utc-offset", "+05:00",
                        ),
                        array(
                            "rrule", new \StdClass(), "recur", array(
                                'freq' => 'WEEKLY',
                                'byday' => array('MO', 'TU'),
                            ),
                        ),
                        array(
                            "x-bool", new \StdClass(), "boolean", true
                        ),
                        array(
                            "x-time", new \StdClass(), "time", "08:00:00",
                        ),
                        array(
                            "attendee",
                            (object)array(
                                "cn" => "Dominik",
                                "partstat" => "DECLINED",
                            ),
                            "cal-address",
                            "mailto:dominik@example.org"
                        ),
                        array(
                            "request-status",
                            new \StdClass(),
                            "text",
                            array("2.0", "Success"),
                        ),
                        array(
                            "request-status",
                            new \StdClass(),
                            "text",
                            array("3.7", "Invalid Calendar User", "ATTENDEE:mailto:jsmith@example.org"),
                        ),
                    ),
                    array(
                        array("valarm",
                            array(
                                array(
                                    "action", new \StdClass(), "text", "DISPLAY",
                                ),
                            ),
                            array(),
                        ),
                    ),
                )
            ),
        );

        $parser = new Json(json_encode($input));
        $vobj = $parser->parse();        
        $result = $vobj->serialize();

        $version = VObject\Version::VERSION;

        $expected = <<<VCF
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject $version//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
UID:foo
DTSTART;VALUE=DATE:20130526
DURATION:P1D
CATEGORIES:home,testing
CREATED:20130526T181000Z
ATTACH;VALUE=BINARY:YXR0YWNobWVudA==
ATTENDEE:mailto:armin@example.org
GEO:51.96668;7.61876
SEQUENCE:5
FREEBUSY:20130526T210213/PT1H,20130626T120000/20130626T130000
URL:http://example.org/
TZOFFSETFROM:+05:00
RRULE:FREQ=WEEKLY;BYDAY=MO,TU
X-BOOL;VALUE=BOOLEAN:TRUE
X-TIME;VALUE=TIME:08:00:00
ATTENDEE;CN=Dominik;PARTSTAT=DECLINED:mailto:dominik@example.org
REQUEST-STATUS:2.0;Success
REQUEST-STATUS:3.7;Invalid Calendar User;ATTENDEE:mailto:jsmith@example.org
BEGIN:VALARM
ACTION:DISPLAY
END:VALARM
END:VEVENT
END:VCALENDAR

VCF;
        $this->assertEquals($expected, str_replace("\r", "", $result));

        $this->assertEquals(
            $input,
            $vobj->jsonSerialize()
        );

    }

    function testParseStreamArg() {

        $input = array(
            "vcard",
            array(
                array(
                    "FN", new \StdClass(), 'text', "foo",
                ),
            ),
        );

        $stream = fopen('php://memory','r+');
        fwrite($stream, json_encode($input));
        rewind($stream);

        $result = VObject\Reader::readJson($stream,0);
        $this->assertEquals('foo', $result->FN->getValue());

    }

    /**
     * @expectedException \Sabre\VObject\ParseException
     */
    function testParseInvalidData() {

        $json = new Json();
        $input = array(
            "vlist",
            array(
                array(
                    "FN", new \StdClass(), 'text', "foo",
                ),
            ),
        );

        $json->parse(json_encode($input), 0);

    }
}
