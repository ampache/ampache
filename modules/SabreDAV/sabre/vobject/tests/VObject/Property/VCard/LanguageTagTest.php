<?php

namespace Sabre\VObject\Property\VCard;

use Sabre\VObject;

class LanguageTagTest extends \PHPUnit_Framework_TestCase {

    function testMimeDir() {

        $input = "BEGIN:VCARD\r\nVERSION:4.0\r\nLANG:nl\r\nEND:VCARD\r\n";
        $mimeDir = new VObject\Parser\MimeDir($input);

        $result = $mimeDir->parse($input);

        $this->assertInstanceOf('Sabre\VObject\Property\VCard\LanguageTag', $result->LANG);

        $this->assertEquals('nl', $result->LANG->getValue());

        $this->assertEquals(
            $input,
            $result->serialize()
        );

    }

}
