<?php

namespace Sabre\CalDAV\Schedule;

class PluginBasicTest extends \Sabre\DAVServerTest {

    public $setupCalDAV = true;
    public $setupCalDAVScheduling = true;

    function testOptions() {

        $plugin = new Plugin();
        $this->assertEquals(['calendar-auto-schedule'], $plugin->getFeatures());

    }

    function testGetHTTPMethods() {

        $this->assertEquals([], $this->caldavSchedulePlugin->getHTTPMethods('notfound'));
        $this->assertEquals([], $this->caldavSchedulePlugin->getHTTPMethods('calendars/user1'));
        $this->assertEquals(['POST'], $this->caldavSchedulePlugin->getHTTPMethods('calendars/user1/outbox'));

    }

}
