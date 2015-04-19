<?php

namespace Sabre\VObject\Property\VCard;

use
    Sabre\VObject,
    Sabre\VObject\Reader;

class DateAndOrTimeTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider dates
     */
    function testGetJsonValue($input, $output) {

        $vcard = new VObject\Component\VCard();
        $prop = $vcard->createProperty('BDAY', $input);

        $this->assertEquals(array($output), $prop->getJsonValue());

    }

    function dates() {

        return array(
            array(
                "19961022T140000",
                "1996-10-22T14:00:00",
            ),
            array(
                "--1022T1400",
                "--10-22T14:00",
            ),
            array(
                "---22T14",
                "---22T14",
            ),
            array(
                "19850412",
                "1985-04-12",
            ),
            array(
                "1985-04",
                "1985-04",
            ),
            array(
                "1985",
                "1985",
            ),
            array(
                "--0412",
                "--04-12",
            ),
            array(
                "T102200",
                "T10:22:00",
            ),
            array(
                "T1022",
                "T10:22",
            ),
            array(
                "T10",
                "T10",
            ),
            array(
                "T-2200",
                "T-22:00",
            ),
            array(
                "T102200Z",
                "T10:22:00Z",
            ),
            array(
                "T102200-0800",
                "T10:22:00-0800",
            ),
            array(
                "T--00",
                "T--00",
            ),
        );

    }

    public function testSetParts() {

        $vcard = new VObject\Component\VCard();

        $prop = $vcard->createProperty('BDAY');
        $prop->setParts(array(
            new \DateTime('2014-04-02 18:37:00')
        ));

        $this->assertEquals('20140402T183700Z', $prop->getValue());

    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetPartsTooMany() {

        $vcard = new VObject\Component\VCard();

        $prop = $vcard->createProperty('BDAY');
        $prop->setParts(array(
            1,
            2
        ));

    }

    public function testSetPartsString() {

        $vcard = new VObject\Component\VCard();

        $prop = $vcard->createProperty('BDAY');
        $prop->setParts(array(
            "20140402T183700Z"
        ));

        $this->assertEquals('20140402T183700Z', $prop->getValue());

    }

    public function testSetValueDateTime() {

        $vcard = new VObject\Component\VCard();

        $prop = $vcard->createProperty('BDAY');
        $prop->setValue(
            new \DateTime('2014-04-02 18:37:00')
        );

        $this->assertEquals('20140402T183700Z', $prop->getValue());

    }

    public function testSetDateTimeOffset() {

        $vcard = new VObject\Component\VCard();

        $prop = $vcard->createProperty('BDAY');
        $prop->setValue(
            new \DateTime('2014-04-02 18:37:00', new \DateTimeZone('America/Toronto'))
        );

        $this->assertEquals('20140402T183700-0400', $prop->getValue());

    }

    public function testGetDateTime() {

        $datetime = new \DateTime('2014-04-02 18:37:00', new \DateTimeZone('America/Toronto'));

        $vcard = new VObject\Component\VCard();
        $prop = $vcard->createProperty('BDAY', $datetime);

        $dt = $prop->getDateTime();
        $this->assertEquals('2014-04-02T18:37:00-04:00', $dt->format('c'), "For some reason this one failed. Current default timezone is: " . date_default_timezone_get());

    }

    public function testGetDateIncomplete() {

        $datetime = '--0407';

        $vcard = new VObject\Component\VCard();
        $prop = $vcard->add('BDAY', $datetime);

        $dt = $prop->getDateTime();
        // Note: if the year changes between the last line and the next line of
        // code, this test may fail.
        //
        // If that happens, head outside and have a drink.
        $current = new \DateTime('now');
        $year = $current->format('Y');

        $this->assertEquals($year . '0407', $dt->format('Ymd'));

    }

    public function testGetDateIncompleteFromVCard() {

        $vcard = <<<VCF
BEGIN:VCARD
VERSION:4.0
BDAY:--0407
END:VCARD
VCF;
        $vcard = Reader::read($vcard);
        $prop = $vcard->BDAY;

        $dt = $prop->getDateTime();
        // Note: if the year changes between the last line and the next line of
        // code, this test may fail.
        //
        // If that happens, head outside and have a drink.
        $current = new \DateTime('now');
        $year = $current->format('Y');

        $this->assertEquals($year . '0407', $dt->format('Ymd'));

    }

    public function testValidate() {

        $datetime = '--0407';

        $vcard = new VObject\Component\VCard();
        $prop = $vcard->add('BDAY', $datetime);

        $this->assertEquals(array(), $prop->validate());

    }

    public function testValidateBroken() {

        $datetime = '123';

        $vcard = new VObject\Component\VCard();
        $prop = $vcard->add('BDAY', $datetime);

        $this->assertEquals(array(array(
            'level' => 3,
            'message' => 'The supplied value (123) is not a correct DATE-AND-OR-TIME property',
            'node' => $prop,
        )), $prop->validate());

    }
}

