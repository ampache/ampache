<?php

namespace Sabre\DAV\Property;

class ResponseListTest extends \PHPUnit_Framework_TestCase {

    /**
     * This was the only part not yet covered by other tests, so I'm going to
     * be lazy and (for now) only test this case.
     *
     * @expectedException InvalidArgumentException
     */
    public function testInvalidArg() {

        $response = new ResponseList(array(1,2));

    }

}
