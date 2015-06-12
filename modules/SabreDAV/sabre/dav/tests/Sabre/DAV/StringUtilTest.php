<?php

namespace Sabre\DAV;

class StringUtilTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider dataset
     */
    function testTextMatch($haystack, $needle, $collation, $matchType, $result) {

        $this->assertEquals($result, StringUtil::textMatch($haystack, $needle, $collation, $matchType));

    }

    function dataset() {

        return array(
            array('FOOBAR', 'FOO',    'i;octet', 'contains', true),
            array('FOOBAR', 'foo',    'i;octet', 'contains', false),
            array('FÖÖBAR', 'FÖÖ',    'i;octet', 'contains', true),
            array('FÖÖBAR', 'föö',    'i;octet', 'contains', false),
            array('FOOBAR', 'FOOBAR', 'i;octet', 'equals', true),
            array('FOOBAR', 'fooBAR', 'i;octet', 'equals', false),
            array('FOOBAR', 'FOO',    'i;octet', 'starts-with', true),
            array('FOOBAR', 'foo',    'i;octet', 'starts-with', false),
            array('FOOBAR', 'BAR',    'i;octet', 'starts-with', false),
            array('FOOBAR', 'bar',    'i;octet', 'starts-with', false),
            array('FOOBAR', 'FOO',    'i;octet', 'ends-with', false),
            array('FOOBAR', 'foo',    'i;octet', 'ends-with', false),
            array('FOOBAR', 'BAR',    'i;octet', 'ends-with', true),
            array('FOOBAR', 'bar',    'i;octet', 'ends-with', false),

            array('FOOBAR', 'FOO',    'i;ascii-casemap', 'contains', true),
            array('FOOBAR', 'foo',    'i;ascii-casemap', 'contains', true),
            array('FÖÖBAR', 'FÖÖ',    'i;ascii-casemap', 'contains', true),
            array('FÖÖBAR', 'föö',    'i;ascii-casemap', 'contains', false),
            array('FOOBAR', 'FOOBAR', 'i;ascii-casemap', 'equals', true),
            array('FOOBAR', 'fooBAR', 'i;ascii-casemap', 'equals', true),
            array('FOOBAR', 'FOO',    'i;ascii-casemap', 'starts-with', true),
            array('FOOBAR', 'foo',    'i;ascii-casemap', 'starts-with', true),
            array('FOOBAR', 'BAR',    'i;ascii-casemap', 'starts-with', false),
            array('FOOBAR', 'bar',    'i;ascii-casemap', 'starts-with', false),
            array('FOOBAR', 'FOO',    'i;ascii-casemap', 'ends-with', false),
            array('FOOBAR', 'foo',    'i;ascii-casemap', 'ends-with', false),
            array('FOOBAR', 'BAR',    'i;ascii-casemap', 'ends-with', true),
            array('FOOBAR', 'bar',    'i;ascii-casemap', 'ends-with', true),

            array('FOOBAR', 'FOO',    'i;unicode-casemap', 'contains', true),
            array('FOOBAR', 'foo',    'i;unicode-casemap', 'contains', true),
            array('FÖÖBAR', 'FÖÖ',    'i;unicode-casemap', 'contains', true),
            array('FÖÖBAR', 'föö',    'i;unicode-casemap', 'contains', true),
            array('FOOBAR', 'FOOBAR', 'i;unicode-casemap', 'equals', true),
            array('FOOBAR', 'fooBAR', 'i;unicode-casemap', 'equals', true),
            array('FOOBAR', 'FOO',    'i;unicode-casemap', 'starts-with', true),
            array('FOOBAR', 'foo',    'i;unicode-casemap', 'starts-with', true),
            array('FOOBAR', 'BAR',    'i;unicode-casemap', 'starts-with', false),
            array('FOOBAR', 'bar',    'i;unicode-casemap', 'starts-with', false),
            array('FOOBAR', 'FOO',    'i;unicode-casemap', 'ends-with', false),
            array('FOOBAR', 'foo',    'i;unicode-casemap', 'ends-with', false),
            array('FOOBAR', 'BAR',    'i;unicode-casemap', 'ends-with', true),
            array('FOOBAR', 'bar',    'i;unicode-casemap', 'ends-with', true),
        );

    }

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    public function testBadCollation() {

        StringUtil::textMatch('foobar','foo','blabla','contains');

    }


    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    public function testBadMatchType() {

        StringUtil::textMatch('foobar','foo','i;octet','booh');

    }

    public function testEnsureUTF8_ascii() {

        $inputString = "harkema";
        $outputString = "harkema";

        $this->assertEquals(
            $outputString,
            StringUtil::ensureUTF8($inputString)
        );

    }

    public function testEnsureUTF8_latin1() {

        $inputString = "m\xfcnster";
        $outputString = "münster";

        $this->assertEquals(
            $outputString,
            StringUtil::ensureUTF8($inputString)
        );

    }

    public function testEnsureUTF8_utf8() {

        $inputString = "m\xc3\xbcnster";
        $outputString = "münster";

        $this->assertEquals(
            $outputString,
            StringUtil::ensureUTF8($inputString)
        );

    }

}
