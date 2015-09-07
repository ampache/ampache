<?php

namespace Sabre\VObject\Parser;

/**
 * Note that most MimeDir related tests can actually be found in the ReaderTest
 * class one level up.
 */
class MimeDirTest extends \PHPUnit_Framework_TestCase {

    /**
     * @expectedException \Sabre\VObject\ParseException
     */
    function testParseError() {

        $mimeDir = new MimeDir();
        $mimeDir->parse(fopen(__FILE__,'a'));

    }

}
