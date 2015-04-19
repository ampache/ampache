<?php

namespace Sabre\CalDAV\Schedule;

use Sabre\DAVACL;
use Sabre\DAV;
use Sabre\HTTP;

class PluginPropertiesTest extends \Sabre\DAVServerTest {

    protected $setupCalDAV = true;
    protected $setupCalDAVScheduling = true;

    function setUp() {

        parent::setUp();
        $this->caldavBackend->createCalendar(
            'principals/user1',
            'default',
            [

            ]
        );

    }

    function testPrincipalProperties() {

        $props = $this->server->getPropertiesForPath('/principals/user1',array(
            '{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL',
            '{urn:ietf:params:xml:ns:caldav}schedule-outbox-URL',
            '{urn:ietf:params:xml:ns:caldav}calendar-user-address-set',
            '{urn:ietf:params:xml:ns:caldav}calendar-user-type',
            '{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL',
        ));

        $this->assertArrayHasKey(0,$props);
        $this->assertArrayHasKey(200,$props[0]);

        $this->assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}schedule-outbox-URL',$props[0][200]);
        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}schedule-outbox-URL'];
        $this->assertTrue($prop instanceof DAV\Property\Href);
        $this->assertEquals('calendars/user1/outbox/',$prop->getHref());

        $this->assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL',$props[0][200]);
        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL'];
        $this->assertTrue($prop instanceof DAV\Property\Href);
        $this->assertEquals('calendars/user1/inbox/',$prop->getHref());

        $this->assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}calendar-user-address-set',$props[0][200]);
        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}calendar-user-address-set'];
        $this->assertTrue($prop instanceof DAV\Property\HrefList);
        $this->assertEquals(array('mailto:user1.sabredav@sabredav.org','/principals/user1/'),$prop->getHrefs());

        $this->assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}calendar-user-type',$props[0][200]);
        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}calendar-user-type'];
        $this->assertEquals('INDIVIDUAL',$prop);

        $this->assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL',$props[0][200]);
        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL'];
        $this->assertEquals('calendars/user1/default/',$prop->getHref());

    }

}
