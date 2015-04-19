<?php

namespace Sabre\CalDAV\Backend;

use Sabre\CalDAV;
use Sabre\DAV;
use Sabre\DAV\PropPatch;

abstract class AbstractPDOTest extends \PHPUnit_Framework_TestCase {

    protected $pdo;

    function testConstruct() {

        $backend = new PDO($this->pdo);
        $this->assertTrue($backend instanceof PDO);

    }

    /**
     * @depends testConstruct
     */
    function testGetCalendarsForUserNoCalendars() {

        $backend = new PDO($this->pdo);
        $calendars = $backend->getCalendarsForUser('principals/user2');
        $this->assertEquals(array(),$calendars);

    }

    /**
     * @depends testConstruct
     */
    function testCreateCalendarAndFetch() {

        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array(
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new CalDAV\Property\SupportedCalendarComponentSet(array('VEVENT')),
            '{DAV:}displayname' => 'Hello!',
            '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new CalDAV\Property\ScheduleCalendarTransp('transparent'),
        ));
        $calendars = $backend->getCalendarsForUser('principals/user2');

        $elementCheck = array(
            'id'                => $returnedId,
            'uri'               => 'somerandomid',
            '{DAV:}displayname' => 'Hello!',
            '{urn:ietf:params:xml:ns:caldav}calendar-description' => '',
            '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new CalDAV\Property\ScheduleCalendarTransp('transparent'),
        );

        $this->assertInternalType('array',$calendars);
        $this->assertEquals(1,count($calendars));

        foreach($elementCheck as $name=>$value) {

            $this->assertArrayHasKey($name, $calendars[0]);
            $this->assertEquals($value,$calendars[0][$name]);

        }

    }

    /**
     * @depends testConstruct
     */
    function testUpdateCalendarAndFetch() {

        $backend = new PDO($this->pdo);

        //Creating a new calendar
        $newId = $backend->createCalendar('principals/user2','somerandomid',array());

        $propPatch = new PropPatch([
            '{DAV:}displayname' => 'myCalendar',
            '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new CalDAV\Property\ScheduleCalendarTransp('transparent'),
        ]);

        // Updating the calendar
        $backend->updateCalendar($newId, $propPatch);
        $result = $propPatch->commit();

        // Verifying the result of the update
        $this->assertTrue($result);

        // Fetching all calendars from this user
        $calendars = $backend->getCalendarsForUser('principals/user2');

        // Checking if all the information is still correct
        $elementCheck = array(
            'id'                => $newId,
            'uri'               => 'somerandomid',
            '{DAV:}displayname' => 'myCalendar',
            '{urn:ietf:params:xml:ns:caldav}calendar-description' => '',
            '{urn:ietf:params:xml:ns:caldav}calendar-timezone' => '',
            '{http://calendarserver.org/ns/}getctag' => 'http://sabre.io/ns/sync/2',
            '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new CalDAV\Property\ScheduleCalendarTransp('transparent'),
        );

        $this->assertInternalType('array',$calendars);
        $this->assertEquals(1,count($calendars));

        foreach($elementCheck as $name=>$value) {

            $this->assertArrayHasKey($name, $calendars[0]);
            $this->assertEquals($value,$calendars[0][$name]);

        }

    }

    /**
     * @depends testUpdateCalendarAndFetch
     */
    function testUpdateCalendarUnknownProperty() {

        $backend = new PDO($this->pdo);

        //Creating a new calendar
        $newId = $backend->createCalendar('principals/user2','somerandomid',array());

        $propPatch = new PropPatch([
            '{DAV:}displayname' => 'myCalendar',
            '{DAV:}yourmom'     => 'wittycomment',
        ]);

        // Updating the calendar
        $backend->updateCalendar($newId, $propPatch);
        $propPatch->commit();

        // Verifying the result of the update
        $this->assertEquals([
            '{DAV:}yourmom' => 403,
            '{DAV:}displayname' => 424,
        ], $propPatch->getResult());

    }

    /**
     * @depends testCreateCalendarAndFetch
     */
    function testDeleteCalendar() {

        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array(
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new CalDAV\Property\SupportedCalendarComponentSet(array('VEVENT')),
            '{DAV:}displayname' => 'Hello!',
        ));

        $backend->deleteCalendar($returnedId);

        $calendars = $backend->getCalendarsForUser('principals/user2');
        $this->assertEquals(array(),$calendars);

    }

    /**
     * @depends testCreateCalendarAndFetch
     * @expectedException \Sabre\DAV\Exception
     */
    function testCreateCalendarIncorrectComponentSet() {;

        $backend = new PDO($this->pdo);

        //Creating a new calendar
        $newId = $backend->createCalendar('principals/user2','somerandomid',array(
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => 'blabla',
        ));

    }

    function testCreateCalendarObject() {

        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT etag, size, calendardata, firstoccurence, lastoccurence, componenttype FROM calendarobjects WHERE uri = "random-id"');
        $this->assertEquals(array(
            'etag' => md5($object),
            'size' => strlen($object),
            'calendardata' => $object,
            'firstoccurence' => strtotime('20120101'),
            'lastoccurence' => strtotime('20120101')+(3600*24),
            'componenttype' => 'VEVENT',
        ), $result->fetch(\PDO::FETCH_ASSOC));

    }
    function testGetMultipleObjects() {

        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'id-1', $object);
        $backend->createCalendarObject($returnedId, 'id-2', $object);

        $check = [
            [
                'id' => 1,
                'etag' => '"' . md5($object) . '"',
                'uri' => 'id-1',
                'size' => strlen($object),
                'calendardata' => $object,
                'lastmodified' => null,
                'calendarid' => $returnedId,
            ],
            [
                'id' => 2,
                'etag' => '"' . md5($object) . '"',
                'uri' => 'id-2',
                'size' => strlen($object),
                'calendardata' => $object,
                'lastmodified' => null,
                'calendarid' => $returnedId,
            ],
        ];

        $result = $backend->getMultipleCalendarObjects($returnedId, [ 'id-1', 'id-2' ]);

        foreach($check as $index => $props) {

            foreach($props as $key=>$value) {

                if ($key!=='lastmodified') {
                    $this->assertEquals($value, $result[$index][$key]);
                } else {
                    $this->assertTrue(isset($result[$index][$key]));
                }

            }

        }

    }

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     * @depends testCreateCalendarObject
     */
    function testCreateCalendarObjectNoComponent() {

        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VTIMEZONE\r\nEND:VTIMEZONE\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

    }

    /**
     * @depends testCreateCalendarObject
     */
    function testCreateCalendarObjectDuration() {

        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nDURATION:P2D\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT etag, size, calendardata, firstoccurence, lastoccurence, componenttype FROM calendarobjects WHERE uri = "random-id"');
        $this->assertEquals(array(
            'etag' => md5($object),
            'size' => strlen($object),
            'calendardata' => $object,
            'firstoccurence' => strtotime('20120101'),
            'lastoccurence' => strtotime('20120101')+(3600*48),
            'componenttype' => 'VEVENT',
        ), $result->fetch(\PDO::FETCH_ASSOC));

    }

    /**
     * @depends testCreateCalendarObject
     */
    function testCreateCalendarObjectNoDTEND() {

        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE-TIME:20120101T100000Z\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT etag, size, calendardata, firstoccurence, lastoccurence, componenttype FROM calendarobjects WHERE uri = "random-id"');
        $this->assertEquals(array(
            'etag' => md5($object),
            'size' => strlen($object),
            'calendardata' => $object,
            'firstoccurence' => strtotime('2012-01-01 10:00:00'),
            'lastoccurence' => strtotime('2012-01-01 10:00:00'),
            'componenttype' => 'VEVENT',
        ), $result->fetch(\PDO::FETCH_ASSOC));

    }

    /**
     * @depends testCreateCalendarObject
     */
    function testCreateCalendarObjectWithDTEND() {

        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE-TIME:20120101T100000Z\r\nDTEND:20120101T110000Z\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT etag, size, calendardata, firstoccurence, lastoccurence, componenttype FROM calendarobjects WHERE uri = "random-id"');
        $this->assertEquals(array(
            'etag' => md5($object),
            'size' => strlen($object),
            'calendardata' => $object,
            'firstoccurence' => strtotime('2012-01-01 10:00:00'),
            'lastoccurence' => strtotime('2012-01-01 11:00:00'),
            'componenttype' => 'VEVENT',
        ), $result->fetch(\PDO::FETCH_ASSOC));

    }

    /**
     * @depends testCreateCalendarObject
     */
    function testCreateCalendarObjectInfiniteReccurence() {

        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE-TIME:20120101T100000Z\r\nRRULE:FREQ=DAILY\r\nUID:foo\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT etag, size, calendardata, firstoccurence, lastoccurence, componenttype FROM calendarobjects WHERE uri = "random-id"');
        $this->assertEquals(array(
            'etag' => md5($object),
            'size' => strlen($object),
            'calendardata' => $object,
            'firstoccurence' => strtotime('2012-01-01 10:00:00'),
            'lastoccurence' => strtotime(PDO::MAX_DATE),
            'componenttype' => 'VEVENT',
        ), $result->fetch(\PDO::FETCH_ASSOC));

    }

    /**
     * @depends testCreateCalendarObject
     */
    function testCreateCalendarObjectEndingReccurence() {

        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE-TIME:20120101T100000Z\r\nDTEND;VALUE=DATE-TIME:20120101T110000Z\r\nUID:foo\r\nRRULE:FREQ=DAILY;COUNT=1000\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT etag, size, calendardata, firstoccurence, lastoccurence, componenttype FROM calendarobjects WHERE uri = "random-id"');
        $this->assertEquals(array(
            'etag' => md5($object),
            'size' => strlen($object),
            'calendardata' => $object,
            'firstoccurence' => strtotime('2012-01-01 10:00:00'),
            'lastoccurence' => strtotime('2012-01-01 11:00:00') + (3600 * 24 * 999),
            'componenttype' => 'VEVENT',
        ), $result->fetch(\PDO::FETCH_ASSOC));

    }

    /**
     * @depends testCreateCalendarObject
     */
    function testCreateCalendarObjectTask() {

        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nDUE;VALUE=DATE-TIME:20120101T100000Z\r\nEND:VTODO\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT etag, size, calendardata, firstoccurence, lastoccurence, componenttype FROM calendarobjects WHERE uri = "random-id"');
        $this->assertEquals(array(
            'etag' => md5($object),
            'size' => strlen($object),
            'calendardata' => $object,
            'firstoccurence' => null,
            'lastoccurence' => null,
            'componenttype' => 'VTODO',
        ), $result->fetch(\PDO::FETCH_ASSOC));

    }

    /**
     * @depends testCreateCalendarObject
     */
    function testGetCalendarObjects() {

        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $data = $backend->getCalendarObjects($returnedId,'random-id');

        $this->assertEquals(1, count($data));
        $data = $data[0];

        $this->assertEquals($returnedId, $data['calendarid']);
        $this->assertEquals('random-id', $data['uri']);
        $this->assertEquals(strlen($object),$data['size']);


    }
    /**
     * @depends testCreateCalendarObject
     */
    function testGetCalendarObjectByUID() {

        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',[]);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nUID:foo\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $this->assertNull(
            $backend->getCalendarObjectByUID('principals/user2', 'bar')
        );
        $this->assertEquals(
            'somerandomid/random-id',
            $backend->getCalendarObjectByUID('principals/user2', 'foo')
        );

    }

    /**
     * @depends testCreateCalendarObject
     */
    function testUpdateCalendarObject() {

        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $object2 = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20130101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $backend->createCalendarObject($returnedId, 'random-id', $object);
        $backend->updateCalendarObject($returnedId, 'random-id', $object2);

        $data = $backend->getCalendarObject($returnedId,'random-id');

        $this->assertEquals($object2, $data['calendardata']);
        $this->assertEquals($returnedId, $data['calendarid']);
        $this->assertEquals('random-id', $data['uri']);


    }

    /**
     * @depends testCreateCalendarObject
     */
    function testDeleteCalendarObject() {

        $backend = new PDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2','somerandomid',array());

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $backend->createCalendarObject($returnedId, 'random-id', $object);
        $backend->deleteCalendarObject($returnedId, 'random-id');

        $data = $backend->getCalendarObject($returnedId,'random-id');
        $this->assertNull($data);

    }

    function testCalendarQueryNoResult() {

        $abstract = new PDO($this->pdo);
        $filters = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name' => 'VJOURNAL',
                    'comp-filters' => array(),
                    'prop-filters' => array(),
                    'is-not-defined' => false,
                    'time-range' => null,
                ),
            ),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => null,
        );

        $this->assertEquals(array(
        ), $abstract->calendarQuery(1, $filters));

    }

    function testCalendarQueryTodo() {

        $backend = new PDO($this->pdo);
        $backend->createCalendarObject(1, "todo", "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name' => 'VTODO',
                    'comp-filters' => array(),
                    'prop-filters' => array(),
                    'is-not-defined' => false,
                    'time-range' => null,
                ),
            ),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => null,
        );

        $this->assertEquals(array(
            "todo",
        ), $backend->calendarQuery(1, $filters));

    }
    function testCalendarQueryTodoNotMatch() {

        $backend = new PDO($this->pdo);
        $backend->createCalendarObject(1, "todo", "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name' => 'VTODO',
                    'comp-filters' => array(),
                    'prop-filters' => array(
                        array(
                            'name' => 'summary',
                            'text-match' => null,
                            'time-range' => null,
                            'param-filters' => array(),
                            'is-not-defined' => false,
                        ),
                    ),
                    'is-not-defined' => false,
                    'time-range' => null,
                ),
            ),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => null,
        );

        $this->assertEquals(array(
        ), $backend->calendarQuery(1, $filters));

    }

    function testCalendarQueryNoFilter() {

        $backend = new PDO($this->pdo);
        $backend->createCalendarObject(1, "todo", "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => null,
        );

        $result = $backend->calendarQuery(1, $filters);
        $this->assertTrue(in_array('todo', $result));
        $this->assertTrue(in_array('event', $result));

    }

    function testCalendarQueryTimeRange() {

        $backend = new PDO($this->pdo);
        $backend->createCalendarObject(1, "todo", "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event2", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120103\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name' => 'VEVENT',
                    'comp-filters' => array(),
                    'prop-filters' => array(),
                    'is-not-defined' => false,
                    'time-range' => array(
                        'start' => new \DateTime('20120103'),
                        'end'   => new \DateTime('20120104'),
                    ),
                ),
            ),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => null,
        );

        $this->assertEquals(array(
            "event2",
        ), $backend->calendarQuery(1, $filters));

    }
    function testCalendarQueryTimeRangeNoEnd() {

        $backend = new PDO($this->pdo);
        $backend->createCalendarObject(1, "todo", "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event2", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120103\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name' => 'VEVENT',
                    'comp-filters' => array(),
                    'prop-filters' => array(),
                    'is-not-defined' => false,
                    'time-range' => array(
                        'start' => new \DateTime('20120102'),
                        'end' => null,
                    ),
                ),
            ),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => null,
        );

        $this->assertEquals(array(
            "event2",
        ), $backend->calendarQuery(1, $filters));

    }

    function testGetChanges() {

        $backend = new PDO($this->pdo);
        $id = $backend->createCalendar(
            'principals/user1',
            'bla',
            []
        );
        $result = $backend->getChangesForCalendar($id, null, 1);

        $this->assertEquals([
            'syncToken' => 1,
            'modified' => [],
            'deleted' => [],
            'added' => [],
        ], $result);

        $currentToken = $result['syncToken'];

        $dummyTodo = "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($id, "todo1.ics", $dummyTodo);
        $backend->createCalendarObject($id, "todo2.ics", $dummyTodo);
        $backend->createCalendarObject($id, "todo3.ics", $dummyTodo);
        $backend->updateCalendarObject($id, "todo1.ics", $dummyTodo);
        $backend->deleteCalendarObject($id, "todo2.ics");

        $result = $backend->getChangesForCalendar($id, $currentToken, 1);

        $this->assertEquals([
            'syncToken' => 6,
            'modified'  => ["todo1.ics"],
            'deleted'   => ["todo2.ics"],
            'added'     => ["todo3.ics"],
        ], $result);

    }

    function testCreateSubscriptions() {

        $props = [
            '{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Property\Href('http://example.org/cal.ics', false),
            '{DAV:}displayname' => 'cal',
            '{http://apple.com/ns/ical/}refreshrate' => 'P1W',
            '{http://apple.com/ns/ical/}calendar-color' => '#FF00FFFF',
            '{http://calendarserver.org/ns/}subscribed-strip-todos' => true,
            //'{http://calendarserver.org/ns/}subscribed-strip-alarms' => true,
            '{http://calendarserver.org/ns/}subscribed-strip-attachments' => true,
        ];

        $backend = new PDO($this->pdo);
        $backend->createSubscription('principals/user1', 'sub1', $props);

        $subs = $backend->getSubscriptionsForUser('principals/user1');

        $expected = $props;
        $expected['id'] = 1;
        $expected['uri'] = 'sub1';
        $expected['principaluri'] = 'principals/user1';

        unset($expected['{http://calendarserver.org/ns/}source']);
        $expected['source'] = 'http://example.org/cal.ics';

        $this->assertEquals(1, count($subs));
        foreach($expected as $k=>$v) {
            $this->assertEquals($subs[0][$k], $expected[$k]);
        }

    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    function testCreateSubscriptionFail() {

        $props = [
        ];

        $backend = new PDO($this->pdo);
        $backend->createSubscription('principals/user1', 'sub1', $props);

    }

    function testUpdateSubscriptions() {

        $props = [
            '{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Property\Href('http://example.org/cal.ics', false),
            '{DAV:}displayname' => 'cal',
            '{http://apple.com/ns/ical/}refreshrate' => 'P1W',
            '{http://apple.com/ns/ical/}calendar-color' => '#FF00FFFF',
            '{http://calendarserver.org/ns/}subscribed-strip-todos' => true,
            //'{http://calendarserver.org/ns/}subscribed-strip-alarms' => true,
            '{http://calendarserver.org/ns/}subscribed-strip-attachments' => true,
        ];

        $backend = new PDO($this->pdo);
        $backend->createSubscription('principals/user1', 'sub1', $props);

        $newProps = [
            '{DAV:}displayname' => 'new displayname',
            '{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Property\Href('http://example.org/cal2.ics', false),
        ];

        $propPatch = new DAV\PropPatch($newProps);
        $backend->updateSubscription(1, $propPatch);
        $result = $propPatch->commit();

        $this->assertTrue($result);

        $subs = $backend->getSubscriptionsForUser('principals/user1');

        $expected = array_merge($props, $newProps);
        $expected['id'] = 1;
        $expected['uri'] = 'sub1';
        $expected['principaluri'] = 'principals/user1';

        unset($expected['{http://calendarserver.org/ns/}source']);
        $expected['source'] = 'http://example.org/cal2.ics';

        $this->assertEquals(1, count($subs));
        foreach($expected as $k=>$v) {
            $this->assertEquals($subs[0][$k], $expected[$k]);
        }

    }

    function testUpdateSubscriptionsFail() {

        $props = [
            '{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Property\Href('http://example.org/cal.ics', false),
            '{DAV:}displayname' => 'cal',
            '{http://apple.com/ns/ical/}refreshrate' => 'P1W',
            '{http://apple.com/ns/ical/}calendar-color' => '#FF00FFFF',
            '{http://calendarserver.org/ns/}subscribed-strip-todos' => true,
            //'{http://calendarserver.org/ns/}subscribed-strip-alarms' => true,
            '{http://calendarserver.org/ns/}subscribed-strip-attachments' => true,
        ];

        $backend = new PDO($this->pdo);
        $backend->createSubscription('principals/user1', 'sub1', $props);

        $propPatch = new DAV\PropPatch([
            '{DAV:}displayname' => 'new displayname',
            '{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Property\Href('http://example.org/cal2.ics', false),
            '{DAV:}unknown' => 'foo',
        ]);

        $backend->updateSubscription(1, $propPatch);
        $propPatch->commit();

        $this->assertEquals([
            '{DAV:}unknown' => 403,
            '{DAV:}displayname' => 424,
            '{http://calendarserver.org/ns/}source' => 424,
        ], $propPatch->getResult());

    }

    function testDeleteSubscriptions() {

        $props = [
            '{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Property\Href('http://example.org/cal.ics', false),
            '{DAV:}displayname' => 'cal',
            '{http://apple.com/ns/ical/}refreshrate' => 'P1W',
            '{http://apple.com/ns/ical/}calendar-color' => '#FF00FFFF',
            '{http://calendarserver.org/ns/}subscribed-strip-todos' => true,
            //'{http://calendarserver.org/ns/}subscribed-strip-alarms' => true,
            '{http://calendarserver.org/ns/}subscribed-strip-attachments' => true,
        ];

        $backend = new PDO($this->pdo);
        $backend->createSubscription('principals/user1', 'sub1', $props);

        $newProps = [
            '{DAV:}displayname' => 'new displayname',
            '{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Property\Href('http://example.org/cal2.ics', false),
        ];

        $backend->deleteSubscription(1);

        $subs = $backend->getSubscriptionsForUser('principals/user1');
        $this->assertEquals(0, count($subs));
    }

    function testSchedulingMethods() {

        $backend = new PDO($this->pdo);

        $calData = "BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n";

        $backend->createSchedulingObject(
            'principals/user1',
            'schedule1.ics',
            $calData
        );

        $expected = [
            'calendardata' => $calData,
            'uri' => 'schedule1.ics',
            'etag' => '"' . md5($calData) . '"',
            'size' => strlen($calData)
        ];

        $result = $backend->getSchedulingObject('principals/user1', 'schedule1.ics');
        foreach($expected as $k=>$v) {
            $this->assertArrayHasKey($k, $result);
            $this->assertEquals($v, $result[$k]);
        }

        $results = $backend->getSchedulingObjects('principals/user1');

        $this->assertEquals(1, count($results));
        $result = $results[0];
        foreach($expected as $k=>$v) {
            $this->assertEquals($v, $result[$k]);
        }

        $backend->deleteSchedulingObject('principals/user1', 'schedule1.ics');
        $result = $backend->getSchedulingObject('principals/user1', 'schedule1.ics');

        $this->assertNull($result);

    }

}
