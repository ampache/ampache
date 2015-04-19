<?php

namespace Sabre\CalDAV\Notifications;
use Sabre\DAVACL;
use Sabre\DAV;
use Sabre\CalDAV;
use Sabre\HTTP;

class PluginTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Sabre\DAV\Server
     */
    protected $server;
    /**
     * @var Sabre\CalDAV\Plugin
     */
    protected $plugin;
    protected $response;
    /**
     * @var Sabre\CalDAV\Backend\PDO
     */
    protected $caldavBackend;

    function setup() {

        $this->caldavBackend = new CalDAV\Backend\MockSharing();
        $principalBackend = new DAVACL\PrincipalBackend\Mock();
        $calendars = new CalDAV\CalendarRoot($principalBackend,$this->caldavBackend);
        $principals = new CalDAV\Principal\Collection($principalBackend);

        $root = new DAV\SimpleCollection('root');
        $root->addChild($calendars);
        $root->addChild($principals);

        $this->server = new DAV\Server($root);
        $this->server->sapi = new HTTP\SapiMock();
        $this->server->debugExceptions = true;
        $this->server->setBaseUri('/');
        $this->plugin = new Plugin();
        $this->server->addPlugin($this->plugin);


        // Adding ACL plugin
        $this->server->addPlugin(new DAVACL\Plugin());

        // CalDAV is also required.
        $this->server->addPlugin(new CalDAV\Plugin());
        // Adding Auth plugin, and ensuring that we are logged in.
        $authBackend = new DAV\Auth\Backend\Mock();
        $authBackend->defaultUser = 'user1';
        $authPlugin = new DAV\Auth\Plugin($authBackend, 'SabreDAV');
        $this->server->addPlugin($authPlugin);

        // This forces a login
        $authPlugin->beforeMethod(new HTTP\Request(), new HTTP\Response());

        $this->response = new HTTP\ResponseMock();
        $this->server->httpResponse = $this->response;

    }

    function testSimple() {

        $this->assertEquals([], $this->plugin->getFeatures());
        $this->assertEquals('notifications', $this->plugin->getPluginName());

    }

    function testPrincipalProperties() {

        $httpRequest = HTTP\Sapi::createFromServerArray(array(
            'HTTP_HOST' => 'sabredav.org',
        ));
        $this->server->httpRequest = $httpRequest;

        $props = $this->server->getPropertiesForPath('/principals/user1',array(
            '{' . Plugin::NS_CALENDARSERVER . '}notification-URL',
        ));

        $this->assertArrayHasKey(0,$props);
        $this->assertArrayHasKey(200,$props[0]);


        $this->assertArrayHasKey('{'.Plugin::NS_CALENDARSERVER .'}notification-URL',$props[0][200]);
        $prop = $props[0][200]['{'.Plugin::NS_CALENDARSERVER .'}notification-URL'];
        $this->assertTrue($prop instanceof DAV\Property\Href);
        $this->assertEquals('calendars/user1/notifications/', $prop->getHref());

    }

    function testNotificationProperties() {

        $notification = new Node(
            $this->caldavBackend,
            'principals/user1',
            new Notification\SystemStatus('foo','"1"')
        );
        $propFind = new DAV\PropFind('calendars/user1/notifications', [
            '{' . Plugin::NS_CALENDARSERVER . '}notificationtype',
        ]);

        $this->plugin->propFind($propFind, $notification);

        $this->assertEquals(
            $notification->getNotificationType(),
            $propFind->get('{' . Plugin::NS_CALENDARSERVER . '}notificationtype')
        );

    }

    function testNotificationGet() {

        $notification = new Node(
            $this->caldavBackend,
            'principals/user1',
            new Notification\SystemStatus('foo','"1"')
        );

        $server = new DAV\Server(array($notification));
        $caldav = new Plugin();

        $server->httpRequest = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_URI' => '/foo.xml',
        ));
        $httpResponse = new HTTP\ResponseMock();
        $server->httpResponse = $httpResponse;

        $server->addPlugin($caldav);

        $caldav->httpGet($server->httpRequest, $server->httpResponse);

        $this->assertEquals(200, $httpResponse->status);
        $this->assertEquals(array(
            'Content-Type' => ['application/xml'],
            'ETag'         => ['"1"'],
        ), $httpResponse->getHeaders());

        $expected =
'<?xml version="1.0" encoding="UTF-8"?>
<cs:notification xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:cs="http://calendarserver.org/ns/">
  <cs:systemstatus type="high"/>
</cs:notification>
';

        $this->assertEquals($expected, $httpResponse->body);

    }

    function testGETPassthrough() {

        $server = new DAV\Server();
        $caldav = new Plugin();

        $httpResponse = new HTTP\ResponseMock();
        $server->httpResponse = $httpResponse;

        $server->addPlugin($caldav);

        $this->assertNull($caldav->httpGet(new HTTP\Request('GET','/foozz'), $server->httpResponse));

    }


}
