<?php

namespace Sabre\VObject;

class JCardTest extends \PHPUnit_Framework_TestCase {

    function testToJCard() {

        $card = new Component\VCard(array(
            "VERSION" => "4.0",
            "UID" => "foo",
            "BDAY" => "19850407",
            "REV"  => "19951031T222710Z",
            "LANG" => "nl",
            "N" => array("Last", "First", "Middle", "", ""),
            "item1.TEL" => "+1 555 123456",
            "item1.X-AB-LABEL" => "Walkie Talkie",
            "ADR" => array(
                "",
                "",
                array("My Street", "Left Side", "Second Shack"),
                "Hometown",
                "PA",
                "18252",
                "U.S.A",
            ),
        ));

        $card->add('BDAY', '1979-12-25', array('VALUE' => 'DATE', 'X-PARAM' => array(1,2)));
        $card->add('BDAY', '1979-12-25T02:00:00', array('VALUE' => 'DATE-TIME'));


        $card->add('X-TRUNCATED', '--1225', array('VALUE' => 'DATE'));
        $card->add('X-TIME-LOCAL', '123000', array('VALUE' => 'TIME'));
        $card->add('X-TIME-UTC', '12:30:00Z', array('VALUE' => 'TIME'));
        $card->add('X-TIME-OFFSET', '12:30:00-08:00', array('VALUE' => 'TIME'));
        $card->add('X-TIME-REDUCED', '23', array('VALUE' => 'TIME'));
        $card->add('X-TIME-TRUNCATED', '--30', array('VALUE' => 'TIME'));

        $card->add('X-KARMA-POINTS', '42', array('VALUE' => 'INTEGER'));
        $card->add('X-GRADE', '1.3', array('VALUE' => 'FLOAT'));

        $card->add('TZ', '-05:00', array('VALUE' => 'UTC-OFFSET'));

        $expected = array(
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
                    "-//Sabre//Sabre VObject " . Version::VERSION . "//EN",
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

        $this->assertEquals($expected, $card->jsonSerialize());

    }

}
