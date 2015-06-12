<?php

namespace Sabre\CalDAV;
use Sabre\DAVACL;
use Sabre\DAV;

require_once 'Sabre/CalDAV/TestUtil.php';

/**
 */
class CalendarHomeTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Sabre\CalDAV\CalendarHome
     */
    protected $usercalendars;
    /**
     * @var Sabre\CalDAV\Backend\PDO
     */
    protected $backend;

    function setup() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');
        $this->backend = TestUtil::getBackend();
        $this->usercalendars = new CalendarHome($this->backend, array(
            'uri' => 'principals/user1'
        ));

    }

    function testSimple() {

        $this->assertEquals('user1',$this->usercalendars->getName());

    }

    /**
     * @expectedException Sabre\DAV\Exception\NotFound
     * @depends testSimple
     */
    function testGetChildNotFound() {

        $this->usercalendars->getChild('randomname');

    }

    function testChildExists() {

        $this->assertFalse($this->usercalendars->childExists('foo'));
        $this->assertTrue($this->usercalendars->childExists('UUID-123467'));

    }

    function testGetOwner() {

        $this->assertEquals('principals/user1', $this->usercalendars->getOwner());

    }

    function testGetGroup() {

        $this->assertNull($this->usercalendars->getGroup());

    }

    function testGetACL() {

        $expected = array(
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => 'principals/user1',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-read',
                'protected' => true,
            ),
        );
        $this->assertEquals($expected, $this->usercalendars->getACL());

    }

    /**
     * @expectedException Sabre\DAV\Exception\MethodNotAllowed
     */
    function testSetACL() {

        $this->usercalendars->setACL(array());

    }

    /**
     * @expectedException Sabre\DAV\Exception\Forbidden
     * @depends testSimple
     */
    function testSetName() {

        $this->usercalendars->setName('bla');

    }

    /**
     * @expectedException Sabre\DAV\Exception\Forbidden
     * @depends testSimple
     */
    function testDelete() {

        $this->usercalendars->delete();

    }

    /**
     * @depends testSimple
     */
    function testGetLastModified() {

        $this->assertNull($this->usercalendars->getLastModified());

    }

    /**
     * @expectedException Sabre\DAV\Exception\MethodNotAllowed
     * @depends testSimple
     */
    function testCreateFile() {

        $this->usercalendars->createFile('bla');

    }


    /**
     * @expectedException Sabre\DAV\Exception\MethodNotAllowed
     * @depends testSimple
     */
    function testCreateDirectory() {

        $this->usercalendars->createDirectory('bla');

    }

    /**
     * @depends testSimple
     */
    function testCreateExtendedCollection() {

        $result = $this->usercalendars->createExtendedCollection('newcalendar', array('{DAV:}collection', '{urn:ietf:params:xml:ns:caldav}calendar'), array());
        $this->assertNull($result);
        $cals = $this->backend->getCalendarsForUser('principals/user1');
        $this->assertEquals(3,count($cals));

    }

    /**
     * @expectedException Sabre\DAV\Exception\InvalidResourceType
     * @depends testSimple
     */
    function testCreateExtendedCollectionBadResourceType() {

        $this->usercalendars->createExtendedCollection('newcalendar', array('{DAV:}collection','{DAV:}blabla'), array());

    }

    /**
     * @expectedException Sabre\DAV\Exception\InvalidResourceType
     * @depends testSimple
     */
    function testCreateExtendedCollectionNotACalendar() {

        $this->usercalendars->createExtendedCollection('newcalendar', array('{DAV:}collection'), array());

    }

    function testGetSupportedPrivilegesSet() {

        $this->assertNull($this->usercalendars->getSupportedPrivilegeSet());

    }

    /**
     * @expectedException Sabre\DAV\Exception\NotImplemented
     */
    function testShareReplyFail() {

        $this->usercalendars->shareReply('uri', SharingPlugin::STATUS_DECLINED, 'curi', '1');

    }

}
