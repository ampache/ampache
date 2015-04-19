<?php

namespace Sabre\DAV\Auth;

use
    Sabre\DAV,
    Sabre\HTTP\RequestInterface,
    Sabre\HTTP\ResponseInterface;

/**
 * This plugin provides Authentication for a WebDAV server.
 *
 * It relies on a Backend object, which provides user information.
 *
 * Additionally, it provides support for:
 *  * {DAV:}current-user-principal property from RFC5397
 *  * {DAV:}principal-collection-set property from RFC3744
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Plugin extends DAV\ServerPlugin {

    /**
     * Reference to main server object
     *
     * @var Sabre\DAV\Server
     */
    protected $server;

    /**
     * Authentication backend
     *
     * @var Backend\BackendInterface
     */
    protected $authBackend;

    /**
     * The authentication realm.
     *
     * @var string
     */
    private $realm;

    /**
     * @return string
     */
    function getRealm() {
        return $this->realm;
    }

    /**
     * __construct
     *
     * @param Backend\BackendInterface $authBackend
     * @param string $realm
     */
    function __construct(Backend\BackendInterface $authBackend, $realm) {

        $this->authBackend = $authBackend;
        $this->realm = $realm;

    }

    /**
     * Initializes the plugin. This function is automatically called by the server
     *
     * @param DAV\Server $server
     * @return void
     */
    function initialize(DAV\Server $server) {

        $this->server = $server;
        $this->server->on('beforeMethod', [$this,'beforeMethod'], 10);

    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using DAV\Server::getPlugin
     *
     * @return string
     */
    function getPluginName() {

        return 'auth';

    }

    /**
     * Returns the current users' principal uri.
     *
     * If nobody is logged in, this will return null.
     *
     * @return string|null
     */
    function getCurrentUser() {

        $userInfo = $this->authBackend->getCurrentUser();
        if (!$userInfo) return null;

        return $userInfo;

    }

    /**
     * This method is called before any HTTP method and forces users to be authenticated
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return bool
     */
    function beforeMethod(RequestInterface $request, ResponseInterface $response) {

        $this->authBackend->authenticate($this->server,$this->getRealm());

    }

}
