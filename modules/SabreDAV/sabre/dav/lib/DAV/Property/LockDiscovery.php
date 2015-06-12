<?php

namespace Sabre\DAV\Property;

use Sabre\DAV;

/**
 * Represents {DAV:}lockdiscovery property
 *
 * This property contains all the open locks on a given resource
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class LockDiscovery extends DAV\Property {

    /**
     * locks
     *
     * @var array
     */
    public $locks;

    /**
     * Hides the {DAV:}lockroot element from the response.
     *
     * It was reported that showing the lockroot in the response can break
     * Office 2000 compatibility.
     */
    static public $hideLockRoot = false;

    /**
     * __construct
     *
     * @param array $locks
     * @param bool $revealLockToken
     */
    function __construct($locks) {

        $this->locks = $locks;

    }

    /**
     * serialize
     *
     * @param DAV\Server $server
     * @param \DOMElement $prop
     * @return void
     */
    function serialize(DAV\Server $server, \DOMElement $prop) {

        $doc = $prop->ownerDocument;

        foreach($this->locks as $lock) {

            $activeLock = $doc->createElementNS('DAV:','d:activelock');
            $prop->appendChild($activeLock);

            $lockScope = $doc->createElementNS('DAV:','d:lockscope');
            $activeLock->appendChild($lockScope);

            $lockScope->appendChild($doc->createElementNS('DAV:','d:' . ($lock->scope==DAV\Locks\LockInfo::EXCLUSIVE?'exclusive':'shared')));

            $lockType = $doc->createElementNS('DAV:','d:locktype');
            $activeLock->appendChild($lockType);

            $lockType->appendChild($doc->createElementNS('DAV:','d:write'));

            /* {DAV:}lockroot */
            if (!self::$hideLockRoot) {
                $lockRoot = $doc->createElementNS('DAV:','d:lockroot');
                $activeLock->appendChild($lockRoot);
                $href = $doc->createElementNS('DAV:','d:href');
                $href->appendChild($doc->createTextNode($server->getBaseUri() . $lock->uri));
                $lockRoot->appendChild($href);
            }

            $activeLock->appendChild($doc->createElementNS('DAV:','d:depth',($lock->depth == DAV\Server::DEPTH_INFINITY?'infinity':$lock->depth)));
            $activeLock->appendChild($doc->createElementNS('DAV:','d:timeout','Second-' . $lock->timeout));

            $lockToken = $doc->createElementNS('DAV:','d:locktoken');
            $activeLock->appendChild($lockToken);
            $lockToken->appendChild($doc->createElementNS('DAV:','d:href','opaquelocktoken:' . $lock->token));

            $activeLock->appendChild($doc->createElementNS('DAV:','d:owner',$lock->owner));

        }

    }

}

