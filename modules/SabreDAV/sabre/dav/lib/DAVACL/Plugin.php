<?php

namespace Sabre\DAVACL;

use
    Sabre\DAV,
    Sabre\DAV\INode,
    Sabre\HTTP\URLUtil,
    Sabre\HTTP\RequestInterface,
    Sabre\HTTP\ResponseInterface;

/**
 * SabreDAV ACL Plugin
 *
 * This plugin provides functionality to enforce ACL permissions.
 * ACL is defined in RFC3744.
 *
 * In addition it also provides support for the {DAV:}current-user-principal
 * property, defined in RFC5397 and the {DAV:}expand-property report, as
 * defined in RFC3253.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Plugin extends DAV\ServerPlugin {

    /**
     * Recursion constants
     *
     * This only checks the base node
     */
    const R_PARENT = 1;

    /**
     * Recursion constants
     *
     * This checks every node in the tree
     */
    const R_RECURSIVE = 2;

    /**
     * Recursion constants
     *
     * This checks every parentnode in the tree, but not leaf-nodes.
     */
    const R_RECURSIVEPARENTS = 3;

    /**
     * Reference to server object.
     *
     * @var Sabre\DAV\Server
     */
    protected $server;

    /**
     * List of urls containing principal collections.
     * Modify this if your principals are located elsewhere.
     *
     * @var array
     */
    public $principalCollectionSet = [
        'principals',
    ];

    /**
     * By default ACL is only enforced for nodes that have ACL support (the
     * ones that implement IACL). For any other node, access is
     * always granted.
     *
     * To override this behaviour you can turn this setting off. This is useful
     * if you plan to fully support ACL in the entire tree.
     *
     * @var bool
     */
    public $allowAccessToNodesWithoutACL = true;

    /**
     * By default nodes that are inaccessible by the user, can still be seen
     * in directory listings (PROPFIND on parent with Depth: 1)
     *
     * In certain cases it's desirable to hide inaccessible nodes. Setting this
     * to true will cause these nodes to be hidden from directory listings.
     *
     * @var bool
     */
    public $hideNodesFromListings = false;

    /**
     * This string is prepended to the username of the currently logged in
     * user. This allows the plugin to determine the principal path based on
     * the username.
     *
     * @var string
     */
    public $defaultUsernamePath = 'principals';

    /**
     * This list of properties are the properties a client can search on using
     * the {DAV:}principal-property-search report.
     *
     * The keys are the property names, values are descriptions.
     *
     * @var array
     */
    public $principalSearchPropertySet = [
        '{DAV:}displayname' => 'Display name',
        '{http://sabredav.org/ns}email-address' => 'Email address',
    ];

    /**
     * Any principal uri's added here, will automatically be added to the list
     * of ACL's. They will effectively receive {DAV:}all privileges, as a
     * protected privilege.
     *
     * @var array
     */
    public $adminPrincipals = [];

    /**
     * Returns a list of features added by this plugin.
     *
     * This list is used in the response of a HTTP OPTIONS request.
     *
     * @return array
     */
    function getFeatures() {

        return ['access-control', 'calendarserver-principal-property-search'];

    }

    /**
     * Returns a list of available methods for a given url
     *
     * @param string $uri
     * @return array
     */
    function getMethods($uri) {

        return ['ACL'];

    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using Sabre\DAV\Server::getPlugin
     *
     * @return string
     */
    function getPluginName() {

        return 'acl';

    }

    /**
     * Returns a list of reports this plugin supports.
     *
     * This will be used in the {DAV:}supported-report-set property.
     * Note that you still need to subscribe to the 'report' event to actually
     * implement them
     *
     * @param string $uri
     * @return array
     */
    function getSupportedReportSet($uri) {

        return [
            '{DAV:}expand-property',
            '{DAV:}principal-property-search',
            '{DAV:}principal-search-property-set',
        ];

    }


    /**
     * Checks if the current user has the specified privilege(s).
     *
     * You can specify a single privilege, or a list of privileges.
     * This method will throw an exception if the privilege is not available
     * and return true otherwise.
     *
     * @param string $uri
     * @param array|string $privileges
     * @param int $recursion
     * @param bool $throwExceptions if set to false, this method won't throw exceptions.
     * @throws Sabre\DAVACL\Exception\NeedPrivileges
     * @return bool
     */
    function checkPrivileges($uri, $privileges, $recursion = self::R_PARENT, $throwExceptions = true) {

        if (!is_array($privileges)) $privileges = [$privileges];

        $acl = $this->getCurrentUserPrivilegeSet($uri);

        if (is_null($acl)) {
            if ($this->allowAccessToNodesWithoutACL) {
                return true;
            } else {
                if ($throwExceptions)
                    throw new Exception\NeedPrivileges($uri,$privileges);
                else
                    return false;

            }
        }

        $failed = [];
        foreach($privileges as $priv) {

            if (!in_array($priv, $acl)) {
                $failed[] = $priv;
            }

        }

        if ($failed) {
            if ($throwExceptions)
                throw new Exception\NeedPrivileges($uri,$failed);
            else
                return false;
        }
        return true;

    }

    /**
     * Returns the standard users' principal.
     *
     * This is one authorative principal url for the current user.
     * This method will return null if the user wasn't logged in.
     *
     * @return string|null
     */
    function getCurrentUserPrincipal() {

        $authPlugin = $this->server->getPlugin('auth');
        if (is_null($authPlugin)) return null;
        /** @var $authPlugin Sabre\DAV\Auth\Plugin */

        $userName = $authPlugin->getCurrentUser();
        if (!$userName) return null;

        return $this->defaultUsernamePath . '/' .  $userName;

    }


    /**
     * Returns a list of principals that's associated to the current
     * user, either directly or through group membership.
     *
     * @return array
     */
    function getCurrentUserPrincipals() {

        $currentUser = $this->getCurrentUserPrincipal();

        if (is_null($currentUser)) return [];

        return array_merge(
            [$currentUser],
            $this->getPrincipalMembership($currentUser)
        );

    }

    /**
     * This array holds a cache for all the principals that are associated with
     * a single principal.
     *
     * @var array
     */
    protected $principalMembershipCache = [];


    /**
     * Returns all the principal groups the specified principal is a member of.
     *
     * @param string $principal
     * @return array
     */
    function getPrincipalMembership($mainPrincipal) {

        // First check our cache
        if (isset($this->principalMembershipCache[$mainPrincipal])) {
            return $this->principalMembershipCache[$mainPrincipal];
        }

        $check = [$mainPrincipal];
        $principals = [];

        while(count($check)) {

            $principal = array_shift($check);

            $node = $this->server->tree->getNodeForPath($principal);
            if ($node instanceof IPrincipal) {
                foreach($node->getGroupMembership() as $groupMember) {

                    if (!in_array($groupMember, $principals)) {

                        $check[] = $groupMember;
                        $principals[] = $groupMember;

                    }

                }

            }

        }

        // Store the result in the cache
        $this->principalMembershipCache[$mainPrincipal] = $principals;

        return $principals;

    }

    /**
     * Returns the supported privilege structure for this ACL plugin.
     *
     * See RFC3744 for more details. Currently we default on a simple,
     * standard structure.
     *
     * You can either get the list of privileges by a uri (path) or by
     * specifying a Node.
     *
     * @param string|INode $node
     * @return array
     */
    function getSupportedPrivilegeSet($node) {

        if (is_string($node)) {
            $node = $this->server->tree->getNodeForPath($node);
        }

        if ($node instanceof IACL) {
            $result = $node->getSupportedPrivilegeSet();

            if ($result)
                return $result;
        }

        return self::getDefaultSupportedPrivilegeSet();

    }

    /**
     * Returns a fairly standard set of privileges, which may be useful for
     * other systems to use as a basis.
     *
     * @return array
     */
    static function getDefaultSupportedPrivilegeSet() {

        return [
            'privilege'  => '{DAV:}all',
            'abstract'   => true,
            'aggregates' => [
                [
                    'privilege'  => '{DAV:}read',
                    'aggregates' => [
                        [
                            'privilege' => '{DAV:}read-acl',
                            'abstract'  => false,
                        ],
                        [
                            'privilege' => '{DAV:}read-current-user-privilege-set',
                            'abstract'  => false,
                        ],
                    ],
                ], // {DAV:}read
                [
                    'privilege'  => '{DAV:}write',
                    'aggregates' => [
                        [
                            'privilege' => '{DAV:}write-acl',
                            'abstract'  => false,
                        ],
                        [
                            'privilege' => '{DAV:}write-properties',
                            'abstract'  => false,
                        ],
                        [
                            'privilege' => '{DAV:}write-content',
                            'abstract'  => false,
                        ],
                        [
                            'privilege' => '{DAV:}bind',
                            'abstract'  => false,
                        ],
                        [
                            'privilege' => '{DAV:}unbind',
                            'abstract'  => false,
                        ],
                        [
                            'privilege' => '{DAV:}unlock',
                            'abstract'  => false,
                        ],
                    ],
                ], // {DAV:}write
            ],
        ]; // {DAV:}all

    }

    /**
     * Returns the supported privilege set as a flat list
     *
     * This is much easier to parse.
     *
     * The returned list will be index by privilege name.
     * The value is a struct containing the following properties:
     *   - aggregates
     *   - abstract
     *   - concrete
     *
     * @param string|INode $node
     * @return array
     */
    final function getFlatPrivilegeSet($node) {

        $privs = $this->getSupportedPrivilegeSet($node);

        $flat = [];
        $this->getFPSTraverse($privs, null, $flat);

        return $flat;

    }

    /**
     * Traverses the privilege set tree for reordering
     *
     * This function is solely used by getFlatPrivilegeSet, and would have been
     * a closure if it wasn't for the fact I need to support PHP 5.2.
     *
     * @param array $priv
     * @param $concrete
     * @param array $flat
     * @return void
     */
    final private function getFPSTraverse($priv, $concrete, &$flat) {

        $myPriv = [
            'privilege' => $priv['privilege'],
            'abstract' => isset($priv['abstract']) && $priv['abstract'],
            'aggregates' => [],
            'concrete' => isset($priv['abstract']) && $priv['abstract']?$concrete:$priv['privilege'],
        ];

        if (isset($priv['aggregates']))
            foreach($priv['aggregates'] as $subPriv) $myPriv['aggregates'][] = $subPriv['privilege'];

        $flat[$priv['privilege']] = $myPriv;

        if (isset($priv['aggregates'])) {

            foreach($priv['aggregates'] as $subPriv) {

                $this->getFPSTraverse($subPriv, $myPriv['concrete'], $flat);

            }

        }

    }

    /**
     * Returns the full ACL list.
     *
     * Either a uri or a INode may be passed.
     *
     * null will be returned if the node doesn't support ACLs.
     *
     * @param string|DAV\INode $node
     * @return array
     */
    function getACL($node) {

        if (is_string($node)) {
            $node = $this->server->tree->getNodeForPath($node);
        }
        if (!$node instanceof IACL) {
            return null;
        }
        $acl = $node->getACL();
        foreach($this->adminPrincipals as $adminPrincipal) {
            $acl[] = [
                'principal' => $adminPrincipal,
                'privilege' => '{DAV:}all',
                'protected' => true,
            ];
        }
        return $acl;

    }

    /**
     * Returns a list of privileges the current user has
     * on a particular node.
     *
     * Either a uri or a DAV\INode may be passed.
     *
     * null will be returned if the node doesn't support ACLs.
     *
     * @param string|DAV\INode $node
     * @return array
     */
    function getCurrentUserPrivilegeSet($node) {

        if (is_string($node)) {
            $node = $this->server->tree->getNodeForPath($node);
        }

        $acl = $this->getACL($node);

        if (is_null($acl)) return null;

        $principals = $this->getCurrentUserPrincipals();

        $collected = [];

        foreach($acl as $ace) {

            $principal = $ace['principal'];

            switch($principal) {

                case '{DAV:}owner' :
                    $owner = $node->getOwner();
                    if ($owner && in_array($owner, $principals)) {
                        $collected[] = $ace;
                    }
                    break;


                // 'all' matches for every user
                case '{DAV:}all' :

                // 'authenticated' matched for every user that's logged in.
                // Since it's not possible to use ACL while not being logged
                // in, this is also always true.
                case '{DAV:}authenticated' :
                    $collected[] = $ace;
                    break;

                // 'unauthenticated' can never occur either, so we simply
                // ignore these.
                case '{DAV:}unauthenticated' :
                    break;

                default :
                    if (in_array($ace['principal'], $principals)) {
                        $collected[] = $ace;
                    }
                    break;

            }


        }

        // Now we deduct all aggregated privileges.
        $flat = $this->getFlatPrivilegeSet($node);

        $collected2 = [];
        while(count($collected)) {

            $current = array_pop($collected);
            $collected2[] = $current['privilege'];

            foreach($flat[$current['privilege']]['aggregates'] as $subPriv) {
                $collected2[] = $subPriv;
                $collected[] = $flat[$subPriv];
            }

        }

        return array_values(array_unique($collected2));

    }

    /**
     * Returns a principal url based on an email address.
     *
     * Note that wether or not this works may depend on wether a search
     * facility is built into the server.
     *
     * This method returns false if the principal could not be found.
     *
     * @deprecated use getPrincipalByUri instead.
     * @return string|bool
     */
    function getPrincipalByEmail($email) {

        $result = $this->getPrincipalByUri('mailto:' . $email);
        return $result?:false;

    }

    /**
     * Returns a principal based on its uri.
     *
     * Returns null if the principal could not be found.
     *
     * @param string $uri
     * @return null|string
     */
    function getPrincipalByUri($uri) {

        $result = null;
        $collections = $this->principalCollectionSet;
        foreach($collections as $collection) {

            $principalCollection = $this->server->tree->getNodeForPath($collection);
            if (!$principalCollection instanceof IPrincipalCollection) {
                // Not a principal collection, we're simply going to ignore
                // this.
                continue;
            }

            $result = $principalCollection->findByUri($uri);
            if ($result) {
                return $result;
            }

        }

    }

    /**
     * Principal property search
     *
     * This method can search for principals matching certain values in
     * properties.
     *
     * This method will return a list of properties for the matched properties.
     *
     * @param array $searchProperties    The properties to search on. This is a
     *                                   key-value list. The keys are property
     *                                   names, and the values the strings to
     *                                   match them on.
     * @param array $requestedProperties This is the list of properties to
     *                                   return for every match.
     * @param string $collectionUri      The principal collection to search on.
     *                                   If this is ommitted, the standard
     *                                   principal collection-set will be used.
     * @param string $test               "allof" to use AND to search the
     *                                   properties. 'anyof' for OR.
     * @return array     This method returns an array structure similar to
     *                  Sabre\DAV\Server::getPropertiesForPath. Returned
     *                  properties are index by a HTTP status code.
     *
     */
    function principalSearch(array $searchProperties, array $requestedProperties, $collectionUri = null, $test = 'allof') {

        if (!is_null($collectionUri)) {
            $uris = [$collectionUri];
        } else {
            $uris = $this->principalCollectionSet;
        }

        $lookupResults = [];
        foreach($uris as $uri) {

            $principalCollection = $this->server->tree->getNodeForPath($uri);
            if (!$principalCollection instanceof IPrincipalCollection) {
                // Not a principal collection, we're simply going to ignore
                // this.
                continue;
            }

            $results = $principalCollection->searchPrincipals($searchProperties, $test);
            foreach($results as $result) {
                $lookupResults[] = rtrim($uri,'/') . '/' . $result;
            }

        }

        $matches = [];

        foreach($lookupResults as $lookupResult) {

            list($matches[]) = $this->server->getPropertiesForPath($lookupResult, $requestedProperties, 0);

        }

        return $matches;

    }

    /**
     * Sets up the plugin
     *
     * This method is automatically called by the server class.
     *
     * @param DAV\Server $server
     * @return void
     */
    function initialize(DAV\Server $server) {

        $this->server = $server;
        $server->on('propFind',            [$this,'propFind'], 20);
        $server->on('beforeMethod',        [$this,'beforeMethod'],20);
        $server->on('beforeBind',          [$this,'beforeBind'],20);
        $server->on('beforeUnbind',        [$this,'beforeUnbind'],20);
        $server->on('propPatch',           [$this,'propPatch']);
        $server->on('beforeUnlock',        [$this,'beforeUnlock'],20);
        $server->on('report',              [$this,'report']);
        $server->on('method:ACL',          [$this,'httpAcl']);

        array_push($server->protectedProperties,
            '{DAV:}alternate-URI-set',
            '{DAV:}principal-URL',
            '{DAV:}group-membership',
            '{DAV:}principal-collection-set',
            '{DAV:}current-user-principal',
            '{DAV:}supported-privilege-set',
            '{DAV:}current-user-privilege-set',
            '{DAV:}acl',
            '{DAV:}acl-restrictions',
            '{DAV:}inherited-acl-set',
            '{DAV:}owner',
            '{DAV:}group'
        );

        // Automatically mapping nodes implementing IPrincipal to the
        // {DAV:}principal resourcetype.
        $server->resourceTypeMapping['Sabre\\DAVACL\\IPrincipal'] = '{DAV:}principal';

        // Mapping the group-member-set property to the HrefList property
        // class.
        $server->propertyMap['{DAV:}group-member-set'] = 'Sabre\\DAV\\Property\\HrefList';

    }

    /* {{{ Event handlers */

    /**
     * Triggered before any method is handled
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    function beforeMethod(RequestInterface $request, ResponseInterface $response) {

        $method = $request->getMethod();
        $path = $request->getPath();

        $exists = $this->server->tree->nodeExists($path);

        // If the node doesn't exists, none of these checks apply
        if (!$exists) return;

        switch($method) {

            case 'GET' :
            case 'HEAD' :
            case 'OPTIONS' :
                // For these 3 we only need to know if the node is readable.
                $this->checkPrivileges($path,'{DAV:}read');
                break;

            case 'PUT' :
            case 'LOCK' :
            case 'UNLOCK' :
                // This method requires the write-content priv if the node
                // already exists, and bind on the parent if the node is being
                // created.
                // The bind privilege is handled in the beforeBind event.
                $this->checkPrivileges($path,'{DAV:}write-content');
                break;


            case 'PROPPATCH' :
                $this->checkPrivileges($path,'{DAV:}write-properties');
                break;

            case 'ACL' :
                $this->checkPrivileges($path,'{DAV:}write-acl');
                break;

            case 'COPY' :
            case 'MOVE' :
                // Copy requires read privileges on the entire source tree.
                // If the target exists write-content normally needs to be
                // checked, however, we're deleting the node beforehand and
                // creating a new one after, so this is handled by the
                // beforeUnbind event.
                //
                // The creation of the new node is handled by the beforeBind
                // event.
                //
                // If MOVE is used beforeUnbind will also be used to check if
                // the sourcenode can be deleted.
                $this->checkPrivileges($path,'{DAV:}read',self::R_RECURSIVE);

                break;

        }

    }

    /**
     * Triggered before a new node is created.
     *
     * This allows us to check permissions for any operation that creates a
     * new node, such as PUT, MKCOL, MKCALENDAR, LOCK, COPY and MOVE.
     *
     * @param string $uri
     * @return void
     */
    function beforeBind($uri) {

        list($parentUri) = URLUtil::splitPath($uri);
        $this->checkPrivileges($parentUri,'{DAV:}bind');

    }

    /**
     * Triggered before a node is deleted
     *
     * This allows us to check permissions for any operation that will delete
     * an existing node.
     *
     * @param string $uri
     * @return void
     */
    function beforeUnbind($uri) {

        list($parentUri) = URLUtil::splitPath($uri);
        $this->checkPrivileges($parentUri,'{DAV:}unbind',self::R_RECURSIVEPARENTS);

    }

    /**
     * Triggered before a node is unlocked.
     *
     * @param string $uri
     * @param DAV\Locks\LockInfo $lock
     * @TODO: not yet implemented
     * @return void
     */
    function beforeUnlock($uri, DAV\Locks\LockInfo $lock) {


    }

    /**
     * Triggered before properties are looked up in specific nodes.
     *
     * @param DAV\PropFind $propFind
     * @param DAV\INode $node
     * @param array $requestedProperties
     * @param array $returnedProperties
     * @TODO really should be broken into multiple methods, or even a class.
     * @return bool
     */
    function propFind(DAV\PropFind $propFind, DAV\INode $node) {

        $path = $propFind->getPath();

        // Checking the read permission
        if (!$this->checkPrivileges($path,'{DAV:}read',self::R_PARENT,false)) {
            // User is not allowed to read properties

            // Returning false causes the property-fetching system to pretend
            // that the node does not exist, and will cause it to be hidden
            // from listings such as PROPFIND or the browser plugin.
            if ($this->hideNodesFromListings) {
                return false;
            }

            // Otherwise we simply mark every property as 403.
            foreach($propFind->getRequestedProperties() as $requestedProperty) {
                $propFind->set($requestedProperty, null, 403);
            }

            return;

        }

        /* Adding principal properties */
        if ($node instanceof IPrincipal) {

            $propFind->handle('{DAV:}alternate-URI-set', function() use ($node) {
                return new DAV\Property\HrefList($node->getAlternateUriSet());
            });
            $propFind->handle('{DAV:}principal-URL', function() use ($node) {
                return new DAV\Property\Href($node->getPrincipalUrl() . '/');
            });
            $propFind->handle('{DAV:}group-member-set', function() use ($node) {
                $members = $node->getGroupMemberSet();
                foreach($members as $k=>$member) {
                    $members[$k] = rtrim($member,'/') . '/';
                }
                return new DAV\Property\HrefList($members);
            });
            $propFind->handle('{DAV:}group-membership', function() use ($node) {
                $members = $node->getGroupMembership();
                foreach($members as $k=>$member) {
                    $members[$k] = rtrim($member,'/') . '/';
                }
                return new DAV\Property\HrefList($members);
            });
            $propFind->handle('{DAV:}displayname', [$node, 'getDisplayName']);

        }

        $propFind->handle('{DAV:}principal-collection-set', function() {

            $val = $this->principalCollectionSet;
            // Ensuring all collections end with a slash
            foreach($val as $k=>$v) $val[$k] = $v . '/';
            return new DAV\Property\HrefList($val);

        });
        $propFind->handle('{DAV:}current-user-principal', function() {
            if ($url = $this->getCurrentUserPrincipal()) {
                return new Property\Principal(Property\Principal::HREF, $url . '/');
            } else {
                return new Property\Principal(Property\Principal::UNAUTHENTICATED);
            }
        });
        $propFind->handle('{DAV:}supported-privilege-set', function() use ($node) {
            return new Property\SupportedPrivilegeSet($this->getSupportedPrivilegeSet($node));
        });
        $propFind->handle('{DAV:}current-user-privilege-set', function() use ($node, $propFind, $path) {
            if (!$this->checkPrivileges($path, '{DAV:}read-current-user-privilege-set', self::R_PARENT, false)) {
                $propFind->set('{DAV:}current-user-privilege-set', null, 403);
            } else {
                $val = $this->getCurrentUserPrivilegeSet($node);
                if (!is_null($val)) {
                    return new Property\CurrentUserPrivilegeSet($val);
                }
            }
        });
        $propFind->handle('{DAV:}acl', function() use ($node, $propFind, $path) {
            /* The ACL property contains all the permissions */
            if (!$this->checkPrivileges($path, '{DAV:}read-acl', self::R_PARENT, false)) {
                $propFind->set('{DAV:}acl', null, 403);
            } else {
                $acl = $this->getACL($node);
                if (!is_null($acl)) {
                    return new Property\Acl($this->getACL($node));
                }
            }
        });
        $propFind->handle('{DAV:}acl-restrictions', function() {
            return new Property\AclRestrictions();
        });

        /* Adding ACL properties */
        if ($node instanceof IACL) {
            $propFind->handle('{DAV:}owner', function() use ($node) {
                return new DAV\Property\Href($node->getOwner() . '/');
            });
        }

    }

    /**
     * This method intercepts PROPPATCH methods and make sure the
     * group-member-set is updated correctly.
     *
     * @param string $path
     * @param DAV\PropPatch $propPatch
     * @return void
     */
    function propPatch($path, DAV\PropPatch $propPatch) {

        $propPatch->handle('{DAV:}group-member-set', function($value) use ($path) {
            if (is_null($value)) {
                $memberSet = [];
            } elseif ($value instanceof DAV\Property\HrefList) {
                $memberSet = array_map(
                    [$this->server,'calculateUri'],
                    $value->getHrefs()
                );
            } else {
                throw new DAV\Exception('The group-member-set property MUST be an instance of Sabre\DAV\Property\HrefList or null');
            }
            $node = $this->server->tree->getNodeForPath($path);
            if (!($node instanceof IPrincipal)) {
                // Fail
                return false;
            }

            $node->setGroupMemberSet($memberSet);
            // We must also clear our cache, just in case

            $this->principalMembershipCache = [];

            return true;
        });

    }

    /**
     * This method handles HTTP REPORT requests
     *
     * @param string $reportName
     * @param \DOMNode $dom
     * @return bool
     */
    function report($reportName, $dom) {

        switch($reportName) {

            case '{DAV:}principal-property-search' :
                $this->server->transactionType = 'report-principal-property-search';
                $this->principalPropertySearchReport($dom);
                return false;
            case '{DAV:}principal-search-property-set' :
                $this->server->transactionType = 'report-principal-search-property-set';
                $this->principalSearchPropertySetReport($dom);
                return false;
            case '{DAV:}expand-property' :
                $this->server->transactionType = 'report-expand-property';
                $this->expandPropertyReport($dom);
                return false;

        }

    }

    /**
     * This method is responsible for handling the 'ACL' event.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return bool
     */
    function httpAcl(RequestInterface $request, ResponseInterface $response) {

        $path = $request->getPath();
        $body = $request->getBodyAsString();
        $dom = DAV\XMLUtil::loadDOMDocument($body);

        $newAcl =
            Property\Acl::unserialize($dom->firstChild, $this->server->propertyMap)
            ->getPrivileges();

        // Normalizing urls
        foreach($newAcl as $k=>$newAce) {
            $newAcl[$k]['principal'] = $this->server->calculateUri($newAce['principal']);
        }
        $node = $this->server->tree->getNodeForPath($path);

        if (!($node instanceof IACL)) {
            throw new DAV\Exception\MethodNotAllowed('This node does not support the ACL method');
        }

        $oldAcl = $this->getACL($node);

        $supportedPrivileges = $this->getFlatPrivilegeSet($node);

        /* Checking if protected principals from the existing principal set are
           not overwritten. */
        foreach($oldAcl as $oldAce) {

            if (!isset($oldAce['protected']) || !$oldAce['protected']) continue;

            $found = false;
            foreach($newAcl as $newAce) {
                if (
                    $newAce['privilege'] === $oldAce['privilege'] &&
                    $newAce['principal'] === $oldAce['principal'] &&
                    $newAce['protected']
                )
                $found = true;
            }

            if (!$found)
                throw new Exception\AceConflict('This resource contained a protected {DAV:}ace, but this privilege did not occur in the ACL request');

        }

        foreach($newAcl as $newAce) {

            // Do we recognize the privilege
            if (!isset($supportedPrivileges[$newAce['privilege']])) {
                throw new Exception\NotSupportedPrivilege('The privilege you specified (' . $newAce['privilege'] . ') is not recognized by this server');
            }

            if ($supportedPrivileges[$newAce['privilege']]['abstract']) {
                throw new Exception\NoAbstract('The privilege you specified (' . $newAce['privilege'] . ') is an abstract privilege');
            }

            // Looking up the principal
            try {
                $principal = $this->server->tree->getNodeForPath($newAce['principal']);
            } catch (DAV\Exception\NotFound $e) {
                throw new Exception\NotRecognizedPrincipal('The specified principal (' . $newAce['principal'] . ') does not exist');
            }
            if (!($principal instanceof IPrincipal)) {
                throw new Exception\NotRecognizedPrincipal('The specified uri (' . $newAce['principal'] . ') is not a principal');
            }

        }
        $node->setACL($newAcl);

        $response->setStatus(200);

        // Breaking the event chain, because we handled this method.
        return false;

    }

    /* }}} */

    /* Reports {{{ */

    /**
     * The expand-property report is defined in RFC3253 section 3-8.
     *
     * This report is very similar to a standard PROPFIND. The difference is
     * that it has the additional ability to look at properties containing a
     * {DAV:}href element, follow that property and grab additional elements
     * there.
     *
     * Other rfc's, such as ACL rely on this report, so it made sense to put
     * it in this plugin.
     *
     * @param \DOMElement $dom
     * @return void
     */
    protected function expandPropertyReport($dom) {

        $requestedProperties = $this->parseExpandPropertyReportRequest($dom->firstChild->firstChild);
        $depth = $this->server->getHTTPDepth(0);
        $requestUri = $this->server->getRequestUri();

        $result = $this->expandProperties($requestUri,$requestedProperties,$depth);

        $dom = new \DOMDocument('1.0','utf-8');
        $dom->formatOutput = true;
        $multiStatus = $dom->createElement('d:multistatus');
        $dom->appendChild($multiStatus);

        // Adding in default namespaces
        foreach($this->server->xmlNamespaces as $namespace=>$prefix) {

            $multiStatus->setAttribute('xmlns:' . $prefix,$namespace);

        }

        foreach($result as $response) {
            $response->serialize($this->server, $multiStatus);
        }

        $xml = $dom->saveXML();
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setBody($xml);

    }

    /**
     * This method is used by expandPropertyReport to parse
     * out the entire HTTP request.
     *
     * @param \DOMElement $node
     * @return array
     */
    protected function parseExpandPropertyReportRequest($node) {

        $requestedProperties = [];
        do {

            if (DAV\XMLUtil::toClarkNotation($node)!=='{DAV:}property') continue;

            if ($node->firstChild) {

                $children = $this->parseExpandPropertyReportRequest($node->firstChild);

            } else {

                $children = [];

            }

            $namespace = $node->getAttribute('namespace');
            if (!$namespace) $namespace = 'DAV:';

            $propName = '{'.$namespace.'}' . $node->getAttribute('name');
            $requestedProperties[$propName] = $children;

        } while ($node = $node->nextSibling);

        return $requestedProperties;

    }

    /**
     * This method expands all the properties and returns
     * a list with property values
     *
     * @param array $path
     * @param array $requestedProperties the list of required properties
     * @param int $depth
     * @return array
     */
    protected function expandProperties($path, array $requestedProperties, $depth) {

        $foundProperties = $this->server->getPropertiesForPath($path, array_keys($requestedProperties), $depth);

        $result = [];

        foreach($foundProperties as $node) {

            foreach($requestedProperties as $propertyName=>$childRequestedProperties) {

                // We're only traversing if sub-properties were requested
                if(count($childRequestedProperties)===0) continue;

                // We only have to do the expansion if the property was found
                // and it contains an href element.
                if (!array_key_exists($propertyName,$node[200])) continue;

                if ($node[200][$propertyName] instanceof DAV\Property\IHref) {
                    $hrefs = [$node[200][$propertyName]->getHref()];
                } elseif ($node[200][$propertyName] instanceof DAV\Property\HrefList) {
                    $hrefs = $node[200][$propertyName]->getHrefs();
                }

                $childProps = [];
                foreach($hrefs as $href) {
                    $childProps = array_merge($childProps, $this->expandProperties($href, $childRequestedProperties, 0));
                }
                $node[200][$propertyName] = new DAV\Property\ResponseList($childProps);

            }
            $result[] = new DAV\Property\Response($node['href'], $node);

        }

        return $result;

    }

    /**
     * principalSearchPropertySetReport
     *
     * This method responsible for handing the
     * {DAV:}principal-search-property-set report. This report returns a list
     * of properties the client may search on, using the
     * {DAV:}principal-property-search report.
     *
     * @param \DOMDocument $dom
     * @return void
     */
    protected function principalSearchPropertySetReport(\DOMDocument $dom) {

        $httpDepth = $this->server->getHTTPDepth(0);
        if ($httpDepth!==0) {
            throw new DAV\Exception\BadRequest('This report is only defined when Depth: 0');
        }

        if ($dom->firstChild->hasChildNodes())
            throw new DAV\Exception\BadRequest('The principal-search-property-set report element is not allowed to have child elements');

        $dom = new \DOMDocument('1.0','utf-8');
        $dom->formatOutput = true;
        $root = $dom->createElement('d:principal-search-property-set');
        $dom->appendChild($root);
        // Adding in default namespaces
        foreach($this->server->xmlNamespaces as $namespace=>$prefix) {

            $root->setAttribute('xmlns:' . $prefix,$namespace);

        }

        $nsList = $this->server->xmlNamespaces;

        foreach($this->principalSearchPropertySet as $propertyName=>$description) {

            $psp = $dom->createElement('d:principal-search-property');
            $root->appendChild($psp);

            $prop = $dom->createElement('d:prop');
            $psp->appendChild($prop);

            $propName = null;
            preg_match('/^{([^}]*)}(.*)$/',$propertyName,$propName);

            $currentProperty = $dom->createElement($nsList[$propName[1]] . ':' . $propName[2]);
            $prop->appendChild($currentProperty);

            $descriptionElem = $dom->createElement('d:description');
            $descriptionElem->setAttribute('xml:lang','en');
            $descriptionElem->appendChild($dom->createTextNode($description));
            $psp->appendChild($descriptionElem);


        }

        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->setStatus(200);
        $this->server->httpResponse->setBody($dom->saveXML());

    }

    /**
     * principalPropertySearchReport
     *
     * This method is responsible for handing the
     * {DAV:}principal-property-search report. This report can be used for
     * clients to search for groups of principals, based on the value of one
     * or more properties.
     *
     * @param \DOMDocument $dom
     * @return void
     */
    protected function principalPropertySearchReport(\DOMDocument $dom) {

        list(
            $searchProperties,
            $requestedProperties,
            $applyToPrincipalCollectionSet,
            $test
        ) = $this->parsePrincipalPropertySearchReportRequest($dom);

        $uri = null;
        if (!$applyToPrincipalCollectionSet) {
            $uri = $this->server->getRequestUri();
        }
        $result = $this->principalSearch($searchProperties, $requestedProperties, $uri, $test);

        $prefer = $this->server->getHTTPPRefer();

        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Vary','Brief,Prefer');
        $this->server->httpResponse->setBody($this->server->generateMultiStatus($result, $prefer['return-minimal']));

    }

    /**
     * parsePrincipalPropertySearchReportRequest
     *
     * This method parses the request body from a
     * {DAV:}principal-property-search report.
     *
     * This method returns an array with two elements:
     *  1. an array with properties to search on, and their values
     *  2. a list of propertyvalues that should be returned for the request.
     *
     * @param \DOMDocument $dom
     * @return array
     */
    protected function parsePrincipalPropertySearchReportRequest($dom) {

        $httpDepth = $this->server->getHTTPDepth(0);
        if ($httpDepth!==0) {
            throw new DAV\Exception\BadRequest('This report is only defined when Depth: 0');
        }

        $searchProperties = [];

        $applyToPrincipalCollectionSet = false;

        $test = $dom->firstChild->getAttribute('test') === 'anyof' ? 'anyof' : 'allof';

        // Parsing the search request
        foreach($dom->firstChild->childNodes as $searchNode) {

            if (DAV\XMLUtil::toClarkNotation($searchNode) == '{DAV:}apply-to-principal-collection-set') {
                $applyToPrincipalCollectionSet = true;
            }

            if (DAV\XMLUtil::toClarkNotation($searchNode)!=='{DAV:}property-search')
                continue;

            $propertyName = null;
            $propertyValue = null;

            foreach($searchNode->childNodes as $childNode) {

                switch(DAV\XMLUtil::toClarkNotation($childNode)) {

                    case '{DAV:}prop' :
                        $property = DAV\XMLUtil::parseProperties($searchNode);
                        reset($property);
                        $propertyName = key($property);
                        break;

                    case '{DAV:}match' :
                        $propertyValue = $childNode->textContent;
                        break;

                }


            }

            if (is_null($propertyName) || is_null($propertyValue))
                throw new DAV\Exception\BadRequest('Invalid search request. propertyname: ' . $propertyName . '. propertvvalue: ' . $propertyValue);

            $searchProperties[$propertyName] = $propertyValue;

        }

        return [
            $searchProperties,
            array_keys(DAV\XMLUtil::parseProperties($dom->firstChild)),
            $applyToPrincipalCollectionSet,
            $test
        ];

    }


    /* }}} */

}
