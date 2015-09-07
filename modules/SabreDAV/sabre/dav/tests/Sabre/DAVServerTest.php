<?php

namespace Sabre;

use
    Sabre\HTTP\Request,
    Sabre\HTTP\Response,
    Sabre\HTTP\Sapi;

/**
 * This class may be used as a basis for other webdav-related unittests.
 *
 * This class is supposed to provide a reasonably big framework to quickly get
 * a testing environment running.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
abstract class DAVServerTest extends \PHPUnit_Framework_TestCase {

    protected $setupCalDAV = false;
    protected $setupCardDAV = false;
    protected $setupACL = false;
    protected $setupCalDAVSharing = false;
    protected $setupCalDAVScheduling = false;
    protected $setupCalDAVSubscriptions = false;
    protected $setupCalDAVICSExport = false;
    protected $setupLocks = false;
    protected $setupFiles = false;

    /**
     * An array with calendars. Every calendar should have
     *   - principaluri
     *   - uri
     */
    protected $caldavCalendars = array();
    protected $caldavCalendarObjects = array();

    protected $carddavAddressBooks = array();
    protected $carddavCards = array();

    /**
     * @var Sabre\DAV\Server
     */
    protected $server;
    protected $tree = array();

    protected $caldavBackend;
    protected $carddavBackend;
    protected $principalBackend;
    protected $locksBackend;

    /**
     * @var Sabre\CalDAV\Plugin
     */
    protected $caldavPlugin;

    /**
     * @var Sabre\CardDAV\Plugin
     */
    protected $carddavPlugin;

    /**
     * @var Sabre\DAVACL\Plugin
     */
    protected $aclPlugin;

    /**
     * @var Sabre\CalDAV\SharingPlugin
     */
    protected $caldavSharingPlugin;

    /**
     * CalDAV scheduling plugin
     *
     * @var CalDAV\Schedule\Plugin
     */
    protected $caldavSchedulePlugin;

    /**
     * @var Sabre\DAV\Auth\Plugin
     */
    protected $authPlugin;

    /**
     * @var Sabre\DAV\Locks\Plugin
     */
    protected $locksPlugin;

    /**
     * If this string is set, we will automatically log in the user with this
     * name.
     */
    protected $autoLogin = null;

    function setUp() {

        $this->setUpBackends();
        $this->setUpTree();

        $this->server = new DAV\Server($this->tree);
        $this->server->sapi = new HTTP\SapiMock();
        $this->server->debugExceptions = true;

        if ($this->setupCalDAV) {
            $this->caldavPlugin = new CalDAV\Plugin();
            $this->server->addPlugin($this->caldavPlugin);
        }
        if ($this->setupCalDAVSharing) {
            $this->caldavSharingPlugin = new CalDAV\SharingPlugin();
            $this->server->addPlugin($this->caldavSharingPlugin);
        }
        if ($this->setupCalDAVScheduling) {
            $this->caldavSchedulePlugin = new CalDAV\Schedule\Plugin();
            $this->server->addPlugin($this->caldavSchedulePlugin);
        }
        if ($this->setupCalDAVSubscriptions) {
            $this->server->addPlugin(new CalDAV\Subscriptions\Plugin());
        }
        if ($this->setupCalDAVICSExport) {
            $this->caldavICSExportPlugin = new CalDAV\ICSExportPlugin();
            $this->server->addPlugin($this->caldavICSExportPlugin);
        }
        if ($this->setupCardDAV) {
            $this->carddavPlugin = new CardDAV\Plugin();
            $this->server->addPlugin($this->carddavPlugin);
        }
        if ($this->setupACL) {
            $this->aclPlugin = new DAVACL\Plugin();
            $this->server->addPlugin($this->aclPlugin);
        }
        if ($this->setupLocks) {
            $this->locksPlugin = new DAV\Locks\Plugin(
                $this->locksBackend
            );
            $this->server->addPlugin($this->locksPlugin);
        }
        if ($this->autoLogin) {
            $authBackend = new DAV\Auth\Backend\Mock();
            $authBackend->defaultUser = $this->autoLogin;
            $this->authPlugin = new DAV\Auth\Plugin($authBackend, 'SabreDAV');
            $this->server->addPlugin($this->authPlugin);

            // This will trigger the actual login procedure
            $this->authPlugin->beforeMethod(new Request(), new Response());
        }

    }

    /**
     * Makes a request, and returns a response object.
     *
     * You can either pass an instance of Sabre\HTTP\Request, or an array,
     * which will then be used as the _SERVER array.
     *
     * @param array|\Sabre\HTTP\Request $request
     * @return \Sabre\HTTP\Response
     */
    function request($request) {

        if (is_array($request)) {
            $request = HTTP\Request::createFromServerArray($request);
        }
        $this->server->httpRequest = $request;
        $this->server->httpResponse = new HTTP\ResponseMock();
        $this->server->exec();

        return $this->server->httpResponse;

    }

    /**
     * Override this to provide your own Tree for your test-case.
     */
    function setUpTree() {

        if ($this->setupCalDAV) {
            $this->tree[] = new CalDAV\CalendarRoot(
                $this->principalBackend,
                $this->caldavBackend
            );
        }
        if ($this->setupCardDAV) {
            $this->tree[] = new CardDAV\AddressBookRoot(
                $this->principalBackend,
                $this->carddavBackend
            );
        }

        if ($this->setupCardDAV || $this->setupCalDAV) {
            $this->tree[] = new DAVACL\PrincipalCollection(
                $this->principalBackend
            );
        }
        if ($this->setupFiles) {

            $this->tree[] = new DAV\Mock\Collection('files');

        }

    }

    function setUpBackends() {

        if ($this->setupCalDAVSharing && is_null($this->caldavBackend)) {
            $this->caldavBackend = new CalDAV\Backend\MockSharing($this->caldavCalendars, $this->caldavCalendarObjects);
        }
        if ($this->setupCalDAVSubscriptions && is_null($this->caldavBackend)) {
            $this->caldavBackend = new CalDAV\Backend\MockSubscriptionSupport($this->caldavCalendars, $this->caldavCalendarObjects);
        }
        if ($this->setupCalDAV && is_null($this->caldavBackend)) {
            if ($this->setupCalDAVScheduling) {
                $this->caldavBackend = new CalDAV\Backend\MockScheduling($this->caldavCalendars, $this->caldavCalendarObjects);
            } else {
                $this->caldavBackend = new CalDAV\Backend\Mock($this->caldavCalendars, $this->caldavCalendarObjects);
            }
        }
        if ($this->setupCardDAV && is_null($this->carddavBackend)) {
            $this->carddavBackend = new CardDAV\Backend\Mock($this->carddavAddressBooks, $this->carddavCards);
        }
        if ($this->setupCardDAV || $this->setupCalDAV) {
            $this->principalBackend = new DAVACL\PrincipalBackend\Mock();
        }
        if ($this->setupLocks) {
            $this->locksBackend = new DAV\Locks\Backend\Mock();
        }

    }


    function assertHTTPStatus($expectedStatus, HTTP\Request $req) {

        $resp = $this->request($req);
        $this->assertEquals((int)$expectedStatus, (int)$resp->status,'Incorrect HTTP status received: ' . $resp->body);

    }

}
