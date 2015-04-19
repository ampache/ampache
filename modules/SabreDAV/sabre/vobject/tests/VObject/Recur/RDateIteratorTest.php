<?php

namespace Sabre\VObject\Recur;

use DateTime;
use DateTimeZone;

class RDateIteratorTest extends \PHPUnit_Framework_TestCase {

    function testSimple() {

        $utc = new DateTimeZone('UTC');
        $it = new RDateIterator('20140901T000000Z,20141001T000000Z', new DateTime('2014-08-01 00:00:00', $utc));

        $expected = array(
            new DateTime('2014-08-01 00:00:00', $utc),
            new DateTime('2014-09-01 00:00:00', $utc),
            new DateTime('2014-10-01 00:00:00', $utc),
        );

        $this->assertEquals(
            $expected,
            iterator_to_array($it)
        );

        $this->assertFalse($it->isInfinite());

    }

    function testFastForward() {

        $utc = new DateTimeZone('UTC');
        $it = new RDateIterator('20140901T000000Z,20141001T000000Z', new DateTime('2014-08-01 00:00:00', $utc));

        $it->fastForward(new DateTime('2014-08-15 00:00:00'));

        $result = array();
        while($it->valid()) {
            $result[] = $it->current();
            $it->next();
        }

        $expected = array(
            new DateTime('2014-09-01 00:00:00', $utc),
            new DateTime('2014-10-01 00:00:00', $utc),
        );

        $this->assertEquals(
            $expected,
            $result
        );

        $this->assertFalse($it->isInfinite());

    }
}
