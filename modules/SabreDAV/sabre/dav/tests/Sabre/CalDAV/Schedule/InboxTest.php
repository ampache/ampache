<?php

namespace Sabre\CalDAV\Schedule;
use Sabre\CalDAV;
use Sabre\DAV;

class InboxTest extends \PHPUnit_Framework_TestCase {

    function testSetup() {

        $inbox = new Inbox(
            new CalDAV\Backend\MockScheduling(),
            'principals/user1'
        );
        $this->assertEquals('inbox', $inbox->getName());
        $this->assertEquals(array(), $inbox->getChildren());
        $this->assertEquals('principals/user1', $inbox->getOwner());
        $this->assertEquals(null, $inbox->getGroup());

        $this->assertEquals(array(
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write-properties',
                'principal' => 'principals/user1',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}unbind',
                'principal' => 'principals/user1',
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
            array(
                'privilege' => '{DAV:}unbind',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ),
            array(
                'privilege' => '{urn:ietf:params:xml:ns:caldav}schedule-deliver-invite',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ),
            array(
                'privilege' => '{urn:ietf:params:xml:ns:caldav}schedule-deliver-reply',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ),
        ), $inbox->getACL());

        $ok = false;
        try {
            $inbox->setACL(array());
        } catch (DAV\Exception\MethodNotAllowed $e) {
            $ok = true;
        }
        if (!$ok) {
            $this->fail('Exception was not emitted');
        }

    }

    function testGetSupportedPrivilegeSet() {

        $inbox = new Inbox(
            new CalDAV\Backend\MockScheduling(),
            'principals/user1'
        );
        $r = $inbox->getSupportedPrivilegeSet();

        $ok = 0;
        foreach($r['aggregates'] as $priv) {

            if ($priv['privilege'] == '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-deliver') {
                $ok++;
                foreach($priv['aggregates'] as $subpriv) {
                    if ($subpriv['privilege'] == '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-deliver-invite') {
                        $ok++;
                    }
                    if ($subpriv['privilege'] == '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-deliver-reply') {
                        $ok++;
                    }
                }
            }
        }

        $this->assertEquals(3, $ok, "We're missing one or more privileges");

    }

    /**
     * @depends testSetup
     */
    function testGetChildren() {

        $backend = new CalDAV\Backend\MockScheduling();
        $inbox = new Inbox(
            $backend,
            'principals/user1'
        );

        $this->assertEquals(
            0,
            count($inbox->getChildren())
        );
        $backend->createSchedulingObject('principals/user1', 'schedule1.ics', "BEGIN:VCALENDAR\r\nEND:VCALENDAR");
        $this->assertEquals(
            1,
            count($inbox->getChildren())
        );
        $this->assertInstanceOf('Sabre\CalDAV\Schedule\SchedulingObject', $inbox->getChildren()[0]);
        $this->assertEquals(
            'schedule1.ics',
            $inbox->getChildren()[0]->getName()
        );

    }

    /**
     * @depends testGetChildren
     */
    function testCreateFile() {

        $backend = new CalDAV\Backend\MockScheduling();
        $inbox = new Inbox(
            $backend,
            'principals/user1'
        );

        $this->assertEquals(
            0,
            count($inbox->getChildren())
        );
        $inbox->createFile('schedule1.ics', "BEGIN:VCALENDAR\r\nEND:VCALENDAR");
        $this->assertEquals(
            1,
            count($inbox->getChildren())
        );
        $this->assertInstanceOf('Sabre\CalDAV\Schedule\SchedulingObject', $inbox->getChildren()[0]);
        $this->assertEquals(
            'schedule1.ics',
            $inbox->getChildren()[0]->getName()
        );

    }

    /**
     * @depends testSetup
     */
    function testCalendarQuery() {

        $backend = new CalDAV\Backend\MockScheduling();
        $inbox = new Inbox(
            $backend,
            'principals/user1'
        );

        $this->assertEquals(
            0,
            count($inbox->getChildren())
        );
        $backend->createSchedulingObject('principals/user1', 'schedule1.ics', "BEGIN:VCALENDAR\r\nEND:VCALENDAR");
        $this->assertEquals(
            ['schedule1.ics'],
            $inbox->calendarQuery([
                'name' => 'VCALENDAR',
                'comp-filters' => [],
                'prop-filters' => [],
                'is-not-defined' => false
            ])
        );

    }
}
