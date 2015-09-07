<?php

namespace Sabre\CalDAV;

use Sabre\DAV;
use Sabre\HTTP;
use Sabre\VObject;
use Sabre\DAVACL;

require_once 'Sabre/CalDAV/TestUtil.php';
require_once 'Sabre/HTTP/ResponseMock.php';

class ICSExportPluginTest extends \PHPUnit_Framework_TestCase {

    function setUp() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');

    }

    function testInit() {

        $p = new ICSExportPlugin();
        $s = new DAV\Server();
        $s->addPlugin($p);
        $this->assertEquals($p, $s->getPlugin('Sabre\CalDAV\ICSExportPlugin'));

    }

    function testBeforeMethod() {

        $cbackend = TestUtil::getBackend();

        $props = [
            'uri'=>'UUID-123467',
            'principaluri' => 'admin',
            'id' => 1,
            '{DAV:}displayname' => 'Hello!',
            '{http://apple.com/ns/ical/}calendar-color' => '#AA0000FF',
        ];
        $tree = [
            new Calendar($cbackend,$props),
        ];

        $p = new ICSExportPlugin();

        $s = new DAV\Server($tree);
        $s->addPlugin($p);
        $s->addPlugin(new Plugin());

        $h = HTTP\Sapi::createFromServerArray([
            'REQUEST_URI' => '/UUID-123467?export',
            'REQUEST_METHOD' => 'GET',
        ]);

        $s->httpRequest = $h;
        $s->httpResponse = new HTTP\ResponseMock();

        $this->assertFalse($p->httpGet($h, $s->httpResponse));

        $this->assertEquals(200, $s->httpResponse->status);
        $this->assertEquals([
            'Content-Type' => ['text/calendar'],
        ], $s->httpResponse->getHeaders());

        $obj = VObject\Reader::read($s->httpResponse->body);

        $this->assertEquals(7,count($obj->children()));
        $this->assertEquals(1,count($obj->VERSION));
        $this->assertEquals(1,count($obj->CALSCALE));
        $this->assertEquals(1,count($obj->PRODID));
        $this->assertTrue(strpos((string)$obj->PRODID, DAV\Version::VERSION)!==false);
        $this->assertEquals(1,count($obj->VTIMEZONE));
        $this->assertEquals(1,count($obj->VEVENT));
        $this->assertEquals("Hello!", $obj->{"X-WR-CALNAME"});
        $this->assertEquals("#AA0000FF", $obj->{"X-APPLE-CALENDAR-COLOR"});

    }
    function testBeforeMethodNoVersion() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');
        $cbackend = TestUtil::getBackend();

        $props = [
            'uri'=>'UUID-123467',
            'principaluri' => 'admin',
            'id' => 1,
        ];
        $tree = [
            new Calendar($cbackend,$props),
        ];

        $p = new ICSExportPlugin();

        $s = new DAV\Server($tree);

        $s->addPlugin($p);
        $s->addPlugin(new Plugin());

        $h = HTTP\Sapi::createFromServerArray([
            'REQUEST_URI' => '/UUID-123467?export',
            'REQUEST_METHOD' => 'GET',
        ]);

        $s->httpRequest = $h;
        $s->httpResponse = new HTTP\ResponseMock();

        DAV\Server::$exposeVersion = false;
        $this->assertFalse($p->httpGet($h, $s->httpResponse));
        DAV\Server::$exposeVersion = true;

        $this->assertEquals(200, $s->httpResponse->status);
        $this->assertEquals([
            'Content-Type' => ['text/calendar'],
        ], $s->httpResponse->getHeaders());

        $obj = VObject\Reader::read($s->httpResponse->body);

        $this->assertEquals(5,count($obj->children()));
        $this->assertEquals(1,count($obj->VERSION));
        $this->assertEquals(1,count($obj->CALSCALE));
        $this->assertEquals(1,count($obj->PRODID));
        $this->assertFalse(strpos((string)$obj->PRODID, DAV\Version::VERSION)!==false);
        $this->assertEquals(1,count($obj->VTIMEZONE));
        $this->assertEquals(1,count($obj->VEVENT));

    }

    function testBeforeMethodNoExport() {

        $p = new ICSExportPlugin();

        $s = new DAV\Server();
        $s->addPlugin($p);

        $h = HTTP\Sapi::createFromServerArray([
            'REQUEST_URI' => '/UUID-123467',
            'REQUEST_METHOD' => 'GET',
        ]);
        $this->assertNull($p->httpGet($h, $s->httpResponse));

    }

    function testACLIntegrationBlocked() {

        $cbackend = TestUtil::getBackend();

        $props = array(
            'uri'=>'UUID-123467',
            'principaluri' => 'admin',
            'id' => 1,
        );
        $tree = array(
            new Calendar($cbackend,$props),
        );

        $p = new ICSExportPlugin();

        $s = new DAV\Server($tree);
        $s->addPlugin($p);
        $s->addPlugin(new Plugin());
        $s->addPlugin(new DAVACL\Plugin());

        $h = HTTP\Sapi::createFromServerArray([
            'REQUEST_URI' => '/UUID-123467?export',
        ]);

        $s->httpRequest = $h;
        $s->httpResponse = new HTTP\ResponseMock();

        $p->httpGet($h, $s->httpResponse);

        // If the ACL system blocked this request, the effect will be that
        // there's no response, because the calendar information could not be
        // fetched.
        $this->assertNull($s->httpResponse->getStatus());

    }

    function testACLIntegrationNotBlocked() {

        $cbackend = TestUtil::getBackend();
        $pbackend = new DAVACL\PrincipalBackend\Mock();

        $props = array(
            'uri'=>'UUID-123467',
            'principaluri' => 'admin',
            'id' => 1,
        );
        $tree = array(
            new Calendar($cbackend,$props),
            new DAVACL\PrincipalCollection($pbackend),
        );

        $p = new ICSExportPlugin();

        $s = new DAV\Server($tree);
        $s->sapi = new HTTP\SapiMock();
        $s->addPlugin($p);
        $s->addPlugin(new Plugin());
        $s->addPlugin(new DAVACL\Plugin());
        $s->addPlugin(new DAV\Auth\Plugin(new DAV\Auth\Backend\Mock(),'SabreDAV'));

        // Forcing login
        $s->getPlugin('acl')->adminPrincipals = array('principals/admin');


        $h = HTTP\Sapi::createFromServerArray([
            'REQUEST_URI' => '/UUID-123467?export',
            'REQUEST_METHOD' => 'GET',
        ]);

        $s->httpRequest = $h;
        $s->httpResponse = new HTTP\ResponseMock();

        $s->exec();

        $this->assertEquals(200, $s->httpResponse->status,'Invalid status received. Response body: '. $s->httpResponse->body);
        $this->assertEquals(array(
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type' => ['text/calendar'],
        ), $s->httpResponse->getHeaders());

        $obj = VObject\Reader::read($s->httpResponse->body);

        $this->assertEquals(5,count($obj->children()));
        $this->assertEquals(1,count($obj->VERSION));
        $this->assertEquals(1,count($obj->CALSCALE));
        $this->assertEquals(1,count($obj->PRODID));
        $this->assertEquals(1,count($obj->VTIMEZONE));
        $this->assertEquals(1,count($obj->VEVENT));

    }

    function testBadStartParam() {

        $cbackend = TestUtil::getBackend();
        $pbackend = new DAVACL\PrincipalBackend\Mock();

        $props = array(
            'uri'=>'UUID-123467',
            'principaluri' => 'admin',
            'id' => 1,
        );
        $tree = array(
            new Calendar($cbackend,$props),
            new DAVACL\PrincipalCollection($pbackend),
        );

        $p = new ICSExportPlugin();

        $s = new DAV\Server($tree);
        $s->sapi = new HTTP\SapiMock();
        $s->addPlugin($p);
        $s->addPlugin(new Plugin());

        $h = HTTP\Sapi::createFromServerArray([
            'REQUEST_URI' => '/UUID-123467?export&start=foo',
            'REQUEST_METHOD' => 'GET',
        ]);

        $s->httpRequest = $h;
        $s->httpResponse = new HTTP\ResponseMock();

        $s->exec();

        $this->assertEquals(400, $s->httpResponse->status,'Invalid status received. Response body: '. $s->httpResponse->body);

    }

    function testBadEndParam() {

        $cbackend = TestUtil::getBackend();
        $pbackend = new DAVACL\PrincipalBackend\Mock();

        $props = array(
            'uri'=>'UUID-123467',
            'principaluri' => 'admin',
            'id' => 1,
        );
        $tree = array(
            new Calendar($cbackend,$props),
            new DAVACL\PrincipalCollection($pbackend),
        );

        $p = new ICSExportPlugin();

        $s = new DAV\Server($tree);
        $s->sapi = new HTTP\SapiMock();
        $s->addPlugin($p);
        $s->addPlugin(new Plugin());

        $h = HTTP\Sapi::createFromServerArray([
            'REQUEST_URI' => '/UUID-123467?export&end=foo',
            'REQUEST_METHOD' => 'GET',
        ]);

        $s->httpRequest = $h;
        $s->httpResponse = new HTTP\ResponseMock();

        $s->exec();

        $this->assertEquals(400, $s->httpResponse->status,'Invalid status received. Response body: '. $s->httpResponse->body);

    }

    function testFilterStartEnd() {

        $cbackend = TestUtil::getBackend();
        $pbackend = new DAVACL\PrincipalBackend\Mock();

        $props = array(
            'uri'=>'UUID-123467',
            'principaluri' => 'admin',
            'id' => 1,
        );
        $tree = array(
            new Calendar($cbackend,$props),
            new DAVACL\PrincipalCollection($pbackend),
        );

        $p = new ICSExportPlugin();

        $s = new DAV\Server($tree);
        $s->sapi = new HTTP\SapiMock();
        $s->addPlugin($p);
        $s->addPlugin(new Plugin());

        $h = HTTP\Sapi::createFromServerArray([
            'REQUEST_URI' => '/UUID-123467?export&start=1&end=2',
            'REQUEST_METHOD' => 'GET',
        ]);

        $s->httpRequest = $h;
        $s->httpResponse = new HTTP\ResponseMock();

        $s->exec();

        $this->assertEquals(200, $s->httpResponse->status,'Invalid status received. Response body: '. $s->httpResponse->body);
        $obj = VObject\Reader::read($s->httpResponse->body);

        $this->assertEquals(0,count($obj->VTIMEZONE));
        $this->assertEquals(0,count($obj->VEVENT));

    }

    function testExpandNoStart() {

        $cbackend = TestUtil::getBackend();
        $pbackend = new DAVACL\PrincipalBackend\Mock();

        $props = array(
            'uri'=>'UUID-123467',
            'principaluri' => 'admin',
            'id' => 1,
        );
        $tree = array(
            new Calendar($cbackend,$props),
            new DAVACL\PrincipalCollection($pbackend),
        );

        $p = new ICSExportPlugin();

        $s = new DAV\Server($tree);
        $s->sapi = new HTTP\SapiMock();
        $s->addPlugin($p);
        $s->addPlugin(new Plugin());

        $h = HTTP\Sapi::createFromServerArray([
            'REQUEST_URI' => '/UUID-123467?export&expand=1&end=1',
            'REQUEST_METHOD' => 'GET',
        ]);

        $s->httpRequest = $h;
        $s->httpResponse = new HTTP\ResponseMock();

        $s->exec();

        $this->assertEquals(400, $s->httpResponse->status,'Invalid status received. Response body: '. $s->httpResponse->body);

    }

    function testExpand() {

        $cbackend = TestUtil::getBackend();
        $pbackend = new DAVACL\PrincipalBackend\Mock();

        $props = array(
            'uri'=>'UUID-123467',
            'principaluri' => 'admin',
            'id' => 1,
        );
        $tree = array(
            new Calendar($cbackend,$props),
            new DAVACL\PrincipalCollection($pbackend),
        );

        $p = new ICSExportPlugin();

        $s = new DAV\Server($tree);
        $s->sapi = new HTTP\SapiMock();
        $s->addPlugin($p);
        $s->addPlugin(new Plugin());

        $h = HTTP\Sapi::createFromServerArray([
            'REQUEST_URI' => '/UUID-123467?export&start=1&end=2000000000&expand=1',
            'REQUEST_METHOD' => 'GET',
        ]);

        $s->httpRequest = $h;
        $s->httpResponse = new HTTP\ResponseMock();

        $s->exec();

        $this->assertEquals(200, $s->httpResponse->status,'Invalid status received. Response body: '. $s->httpResponse->body);
        $obj = VObject\Reader::read($s->httpResponse->body);

        $this->assertEquals(0,count($obj->VTIMEZONE));
        $this->assertEquals(1,count($obj->VEVENT));

    }

    function testJCal() {

        $cbackend = TestUtil::getBackend();
        $pbackend = new DAVACL\PrincipalBackend\Mock();

        $props = array(
            'uri'=>'UUID-123467',
            'principaluri' => 'admin',
            'id' => 1,
        );
        $tree = array(
            new Calendar($cbackend,$props),
            new DAVACL\PrincipalCollection($pbackend),
        );

        $p = new ICSExportPlugin();

        $s = new DAV\Server($tree);
        $s->sapi = new HTTP\SapiMock();
        $s->addPlugin($p);
        $s->addPlugin(new Plugin());

        $h = HTTP\Sapi::createFromServerArray([
            'REQUEST_URI' => '/UUID-123467?export',
            'REQUEST_METHOD' => 'GET',
            'HTTP_ACCEPT' => 'application/calendar+json',
        ]);

        $s->httpRequest = $h;
        $s->httpResponse = new HTTP\ResponseMock();

        $s->exec();

        $this->assertEquals(200, $s->httpResponse->status,'Invalid status received. Response body: '. $s->httpResponse->body);
        $this->assertEquals('application/calendar+json', $s->httpResponse->getHeader('Content-Type'));

    }

    function testJCalInUrl() {

        $cbackend = TestUtil::getBackend();
        $pbackend = new DAVACL\PrincipalBackend\Mock();

        $props = array(
            'uri'=>'UUID-123467',
            'principaluri' => 'admin',
            'id' => 1,
        );
        $tree = array(
            new Calendar($cbackend,$props),
            new DAVACL\PrincipalCollection($pbackend),
        );

        $p = new ICSExportPlugin();

        $s = new DAV\Server($tree);
        $s->sapi = new HTTP\SapiMock();
        $s->addPlugin($p);
        $s->addPlugin(new Plugin());

        $h = HTTP\Sapi::createFromServerArray([
            'REQUEST_URI' => '/UUID-123467?export&accept=jcal',
            'REQUEST_METHOD' => 'GET',
        ]);

        $s->httpRequest = $h;
        $s->httpResponse = new HTTP\ResponseMock();

        $s->exec();

        $this->assertEquals(200, $s->httpResponse->status,'Invalid status received. Response body: '. $s->httpResponse->body);
        $this->assertEquals('application/calendar+json', $s->httpResponse->getHeader('Content-Type'));

    }

    function testNegotiateDefault() {

        $cbackend = TestUtil::getBackend();
        $pbackend = new DAVACL\PrincipalBackend\Mock();

        $props = array(
            'uri'=>'UUID-123467',
            'principaluri' => 'admin',
            'id' => 1,
        );
        $tree = array(
            new Calendar($cbackend,$props),
            new DAVACL\PrincipalCollection($pbackend),
        );

        $p = new ICSExportPlugin();

        $s = new DAV\Server($tree);
        $s->sapi = new HTTP\SapiMock();
        $s->addPlugin($p);
        $s->addPlugin(new Plugin());

        $h = HTTP\Sapi::createFromServerArray([
            'REQUEST_URI' => '/UUID-123467?export',
            'REQUEST_METHOD' => 'GET',
            'HTTP_ACCEPT' => 'text/plain',
        ]);

        $s->httpRequest = $h;
        $s->httpResponse = new HTTP\ResponseMock();

        $s->exec();

        $this->assertEquals(200, $s->httpResponse->status,'Invalid status received. Response body: '. $s->httpResponse->body);
        $this->assertEquals('text/calendar', $s->httpResponse->getHeader('Content-Type'));

    }
}
