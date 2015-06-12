<?php

namespace Sabre\CalDAV\Schedule;
use Sabre\CalDAV;
use Sabre\DAV;

class OutboxTest extends \PHPUnit_Framework_TestCase {

    function testSetup() {

        $outbox = new Outbox('principals/user1');
        $this->assertEquals('outbox', $outbox->getName());
        $this->assertEquals(array(), $outbox->getChildren());
        $this->assertEquals('principals/user1', $outbox->getOwner());
        $this->assertEquals(null, $outbox->getGroup());

        $this->assertEquals(array(
            array(
                'privilege' => '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-query-freebusy',
                'principal' => 'principals/user1',
                'protected' => true,
            ),

            array(
                'privilege' => '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-post-vevent',
                'principal' => 'principals/user1',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1',
                'protected' => true,
            ),
            array(
                'privilege' => '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-query-freebusy',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ),
            array(
                'privilege' => '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-post-vevent',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-read',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ),
        ), $outbox->getACL());

        $ok = false;
        try {
            $outbox->setACL(array());
        } catch (DAV\Exception\MethodNotAllowed $e) {
            $ok = true;
        }
        if (!$ok) {
            $this->fail('Exception was not emitted');
        }

    }

    function testGetSupportedPrivilegeSet() {

        $outbox = new Outbox('principals/user1');
        $r = $outbox->getSupportedPrivilegeSet();

        $ok = 0;
        foreach($r['aggregates'] as $priv) {

            if ($priv['privilege'] == '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-query-freebusy') {
                $ok++;
            }
            if ($priv['privilege'] == '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-post-vevent') {
                $ok++;
            }
        }

        $this->assertEquals(2, $ok, "We're missing one or more privileges");

    }


}
