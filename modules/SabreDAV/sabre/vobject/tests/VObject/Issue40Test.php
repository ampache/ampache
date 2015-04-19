<?php

namespace Sabre\VObject;

/**
 * This test is created to handle the issues brought forward by issue 40.
 *
 * https://github.com/fruux/sabre-vobject/issues/40
 */
class Issue40Test extends \PHPUnit_Framework_TestCase {

    function testEncode() {

        $card = new Component\VCard();
        $card->add('N', array('van der Harten', array('Rene','J.'), "", 'Sir','R.D.O.N.'), array('SORT-AS' => array('Harten','Rene')));

        $expected = implode("\r\n", array(
            "BEGIN:VCARD",
            "VERSION:3.0",
            "PRODID:-//Sabre//Sabre VObject " . Version::VERSION . '//EN',
            "N;SORT-AS=Harten,Rene:van der Harten;Rene,J.;;Sir;R.D.O.N.",
            "END:VCARD",
            ""
        ));

        $this->assertEquals($expected, $card->serialize());

    }

}
