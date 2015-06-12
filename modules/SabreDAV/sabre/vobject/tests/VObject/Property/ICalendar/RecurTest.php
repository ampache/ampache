<?php

namespace Sabre\VObject\Property\ICalendar;

use Sabre\VObject\Component\VCalendar;

class RecurTest extends \PHPUnit_Framework_TestCase {

    function testParts() {

        $vcal = new VCalendar();
        $recur = $vcal->add('RRULE', 'FREQ=Daily');

        $this->assertInstanceOf('Sabre\VObject\Property\ICalendar\Recur', $recur);

        $this->assertEquals(array('FREQ'=>'DAILY'), $recur->getParts());
        $recur->setParts(array('freq'=>'MONTHLY'));

        $this->assertEquals(array('FREQ'=>'MONTHLY'), $recur->getParts());

    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testSetValueBadVal() {

        $vcal = new VCalendar();
        $recur = $vcal->add('RRULE', 'FREQ=Daily');
        $recur->setValue(new \Exception());

    }

    function testSetSubParts() {

        $vcal = new VCalendar();
        $recur = $vcal->add('RRULE', array('FREQ'=>'DAILY', 'BYDAY'=>'mo,tu', 'BYMONTH' => array(0,1)));

        $this->assertEquals(array(
            'FREQ'=>'DAILY',
            'BYDAY' => array('MO','TU'),
            'BYMONTH' => array(0,1),
        ), $recur->getParts());

    }
}
