<?php

namespace Sabre\DAV\Auth\Backend;

use Sabre\DAV;

class Mock implements BackendInterface {

    protected $currentUser;

    public $defaultUser = 'admin';

    /**
     * @param Sabre\DAV\Server $server
     * @param string $realm
     * @throws Sabre\DAV\Exception\NotAuthenticated
     */
    function authenticate(DAV\Server $server, $realm) {

        if ($realm=='failme') throw new DAV\Exception\NotAuthenticated('deliberate fail');
        $this->currentUser = $this->defaultUser;

    }

    function setCurrentUser($user) {

        $this->currentUser = $user;

    }

    function getCurrentUser() {

        return $this->currentUser;

    }

}
