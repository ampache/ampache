<?php

namespace Sabre\CalDAV;

use Sabre\DAV;
use Sabre\HTTP;

class SharingPluginTest extends \Sabre\DAVServerTest {

    protected $setupCalDAV = true;
    protected $setupCalDAVSharing = true;
    protected $setupACL = true;
    protected $autoLogin = 'user1';

    function setUp() {

        $this->caldavCalendars = array(
            array(
                'principaluri' => 'principals/user1',
                'id' => 1,
                'uri' => 'cal1',
            ),
            array(
                'principaluri' => 'principals/user1',
                'id' => 2,
                'uri' => 'cal2',
                '{' . Plugin::NS_CALENDARSERVER . '}shared-url' => 'calendars/user1/cal2',
                '{http://sabredav.org/ns}owner-principal' => 'principals/user2',
                '{http://sabredav.org/ns}read-only' => 'true',
            ),
            array(
                'principaluri' => 'principals/user1',
                'id' => 3,
                'uri' => 'cal3',
            ),
        );

        parent::setUp();

        // Making the logged in user an admin, for full access:
        $this->aclPlugin->adminPrincipals[] = 'principals/user1';
        $this->aclPlugin->adminPrincipals[] = 'principals/user2';

    }

    function testSimple() {

        $this->assertInstanceOf('Sabre\\CalDAV\\SharingPlugin', $this->server->getPlugin('caldav-sharing'));

    }

    function testGetFeatures() {

        $this->assertEquals(array('calendarserver-sharing'), $this->caldavSharingPlugin->getFeatures());

    }

    function testBeforeGetShareableCalendar() {

        // Forcing the server to authenticate:
        $this->authPlugin->beforeMethod(new HTTP\Request(), new HTTP\Response());
        $props = $this->server->getProperties('calendars/user1/cal1', array(
            '{' . Plugin::NS_CALENDARSERVER . '}invite',
            '{' . Plugin::NS_CALENDARSERVER . '}allowed-sharing-modes',
        ));

        $this->assertInstanceOf('Sabre\\CalDAV\\Property\\Invite', $props['{' . Plugin::NS_CALENDARSERVER . '}invite']);
        $this->assertInstanceOf('Sabre\\CalDAV\\Property\\AllowedSharingModes', $props['{' . Plugin::NS_CALENDARSERVER . '}allowed-sharing-modes']);

    }

    function testBeforeGetSharedCalendar() {

        $props = $this->server->getProperties('calendars/user1/cal2', array(
            '{' . Plugin::NS_CALENDARSERVER . '}shared-url',
            '{' . Plugin::NS_CALENDARSERVER . '}invite',
        ));

        $this->assertInstanceOf('Sabre\\CalDAV\\Property\\Invite', $props['{' . Plugin::NS_CALENDARSERVER . '}invite']);
        $this->assertInstanceOf('Sabre\\DAV\\Property\\IHref', $props['{' . Plugin::NS_CALENDARSERVER . '}shared-url']);

    }

    function testUpdateProperties() {

        $this->caldavBackend->updateShares(1,
            array(
                array(
                    'href' => 'mailto:joe@example.org',
                ),
            ),
            array()
        );
        $result = $this->server->updateProperties('calendars/user1/cal1', array(
            '{DAV:}resourcetype' => new DAV\Property\ResourceType(array('{DAV:}collection'))
        ));

        $this->assertEquals(array(
            '{DAV:}resourcetype' => 200
        ), $result);

        $this->assertEquals(0, count($this->caldavBackend->getShares(1)));

    }

    function testUpdatePropertiesPassThru() {

        $result = $this->server->updateProperties('calendars/user1/cal3', array(
            '{DAV:}foo' => 'bar',
        ));

        $this->assertEquals(array(
            '{DAV:}foo' => 403,
        ), $result);

    }

    function testUnknownMethodNoPOST() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'PATCH',
            'REQUEST_URI'    => '/',
        ));

        $response = $this->request($request);

        $this->assertEquals(501, $response->status, $response->body);

    }

    function testUnknownMethodNoXML() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/',
            'CONTENT_TYPE'   => 'text/plain',
        ));

        $response = $this->request($request);

        $this->assertEquals(501, $response->status, $response->body);

    }

    function testUnknownMethodNoNode() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/foo',
            'CONTENT_TYPE'   => 'text/xml',
        ));

        $response = $this->request($request);

        $this->assertEquals(501, $response->status, $response->body);

    }

    function testShareRequest() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/calendars/user1/cal1',
            'CONTENT_TYPE'   => 'text/xml',
        ));

        $xml = <<<RRR
<?xml version="1.0"?>
<cs:share xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:">
    <cs:set>
        <d:href>mailto:joe@example.org</d:href>
        <cs:common-name>Joe Shmoe</cs:common-name>
        <cs:read-write />
    </cs:set>
    <cs:remove>
        <d:href>mailto:nancy@example.org</d:href>
    </cs:remove>
</cs:share>
RRR;

        $request->setBody($xml);

        $response = $this->request($request);
        $this->assertEquals(200, $response->status, $response->body);

        $this->assertEquals(array(array(
            'href' => 'mailto:joe@example.org',
            'commonName' => 'Joe Shmoe',
            'readOnly' => false,
            'status' => SharingPlugin::STATUS_NORESPONSE,
            'summary' => '',
        )), $this->caldavBackend->getShares(1));

        // Verifying that the calendar is now marked shared.
        $props = $this->server->getProperties('calendars/user1/cal1', array('{DAV:}resourcetype'));
        $this->assertTrue(
            $props['{DAV:}resourcetype']->is('{http://calendarserver.org/ns/}shared-owner')
        );

    }

    function testShareRequestNoShareableCalendar() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/calendars/user1/cal2',
            'CONTENT_TYPE'   => 'text/xml',
        ));

        $xml = '<?xml version="1.0"?>
<cs:share xmlns:cs="' . Plugin::NS_CALENDARSERVER . '" xmlns:d="DAV:">
    <cs:set>
        <d:href>mailto:joe@example.org</d:href>
        <cs:common-name>Joe Shmoe</cs:common-name>
        <cs:read-write />
    </cs:set>
    <cs:remove>
        <d:href>mailto:nancy@example.org</d:href>
    </cs:remove>
</cs:share>
';

        $request->setBody($xml);

        $response = $this->request($request);
        $this->assertEquals(501, $response->status, $response->body);

    }

    function testInviteReply() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/calendars/user1',
            'CONTENT_TYPE'   => 'text/xml',
        ));

        $xml = '<?xml version="1.0"?>
<cs:invite-reply xmlns:cs="' . Plugin::NS_CALENDARSERVER . '" xmlns:d="DAV:">
    <cs:hosturl><d:href>/principals/owner</d:href></cs:hosturl>
    <cs:invite-accepted />
</cs:invite-reply>
';

        $request->setBody($xml);
        $response = $this->request($request);
        $this->assertEquals(200, $response->status, $response->body);

    }

    function testInviteBadXML() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/calendars/user1',
            'CONTENT_TYPE'   => 'text/xml',
        ));

        $xml = '<?xml version="1.0"?>
<cs:invite-reply xmlns:cs="' . Plugin::NS_CALENDARSERVER . '" xmlns:d="DAV:">
</cs:invite-reply>
';
        $request->setBody($xml);
        $response = $this->request($request);
        $this->assertEquals(400, $response->status, $response->body);

    }

    function testInviteWrongUrl() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/calendars/user1/cal1',
            'CONTENT_TYPE'   => 'text/xml',
        ));

        $xml = '<?xml version="1.0"?>
<cs:invite-reply xmlns:cs="' . Plugin::NS_CALENDARSERVER . '" xmlns:d="DAV:">
    <cs:hosturl><d:href>/principals/owner</d:href></cs:hosturl>
</cs:invite-reply>
';
        $request->setBody($xml);
        $response = $this->request($request);
        $this->assertEquals(501, $response->status, $response->body);

        // If the plugin did not handle this request, it must ensure that the
        // body is still accessible by other plugins.
        $this->assertEquals($xml, $request->getBody(true));

    }

    function testPublish() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/calendars/user1/cal1',
            'CONTENT_TYPE'   => 'text/xml',
        ));

        $xml = '<?xml version="1.0"?>
<cs:publish-calendar xmlns:cs="' . Plugin::NS_CALENDARSERVER . '" xmlns:d="DAV:" />
';

        $request->setBody($xml);

        $response = $this->request($request);
        $this->assertEquals(202, $response->status, $response->body);

    }

    function testUnpublish() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/calendars/user1/cal1',
            'CONTENT_TYPE'   => 'text/xml',
        ));

        $xml = '<?xml version="1.0"?>
<cs:unpublish-calendar xmlns:cs="' . Plugin::NS_CALENDARSERVER . '" xmlns:d="DAV:" />
';

        $request->setBody($xml);

        $response = $this->request($request);
        $this->assertEquals(200, $response->status, $response->body);

    }

    function testPublishWrongUrl() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/calendars/user1/cal2',
            'CONTENT_TYPE'   => 'text/xml',
        ));

        $xml = '<?xml version="1.0"?>
<cs:publish-calendar xmlns:cs="' . Plugin::NS_CALENDARSERVER . '" xmlns:d="DAV:" />
';

        $request->setBody($xml);

        $response = $this->request($request);
        $this->assertEquals(501, $response->status, $response->body);

    }

    function testUnpublishWrongUrl() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/calendars/user1/cal2',
            'CONTENT_TYPE'   => 'text/xml',
        ));

        $xml = '<?xml version="1.0"?>
<cs:unpublish-calendar xmlns:cs="' . Plugin::NS_CALENDARSERVER . '" xmlns:d="DAV:" />
';

        $request->setBody($xml);

        $response = $this->request($request);
        $this->assertEquals(501, $response->status, $response->body);

    }

    function testUnknownXmlDoc() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/calendars/user1/cal2',
            'CONTENT_TYPE'   => 'text/xml',
        ));

        $xml = '<?xml version="1.0"?>
<cs:foo-bar xmlns:cs="' . Plugin::NS_CALENDARSERVER . '" xmlns:d="DAV:" />';

        $request->setBody($xml);

        $response = $this->request($request);
        $this->assertEquals(501, $response->status, $response->body);

    }
}
