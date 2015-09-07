<?php

namespace Sabre\DAV\Auth\Backend;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';

class AbstractBasicTest extends \PHPUnit_Framework_TestCase {

    /**
     * @expectedException Sabre\DAV\Exception\NotAuthenticated
     */
    public function testAuthenticateNoHeaders() {

        $response = new HTTP\ResponseMock();
        $server = new DAV\Server();
        $server->httpResponse = $response;

        $backend = new AbstractBasicMock();
        $backend->authenticate($server,'myRealm');

    }

    /**
     * @expectedException Sabre\DAV\Exception\NotAuthenticated
     */
    public function testAuthenticateUnknownUser() {

        $response = new HTTP\ResponseMock();
        $tree = new DAV\Tree(new DAV\SimpleCollection('bla'));
        $server = new DAV\Server($tree);
        $server->httpResponse = $response;

        $request = HTTP\Sapi::createFromServerArray(array(
            'PHP_AUTH_USER' => 'username',
            'PHP_AUTH_PW' => 'wrongpassword',
        ));
        $server->httpRequest = $request;

        $backend = new AbstractBasicMock();
        $backend->authenticate($server,'myRealm');

    }

    public function testAuthenticate() {

        $response = new HTTP\ResponseMock();
        $tree = new DAV\Tree(new DAV\SimpleCollection('bla'));
        $server = new DAV\Server($tree);
        $server->httpResponse = $response;

        $request = HTTP\Sapi::createFromServerArray(array(
            'PHP_AUTH_USER' => 'username',
            'PHP_AUTH_PW' => 'password',
        ));
        $server->httpRequest = $request;

        $backend = new AbstractBasicMock();
        $this->assertTrue($backend->authenticate($server,'myRealm'));

        $result = $backend->getCurrentUser();

        $this->assertEquals('username', $result);

    }


}


class AbstractBasicMock extends AbstractBasic {

    /**
     * Validates a username and password
     *
     * This method should return true or false depending on if login
     * succeeded.
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    function validateUserPass($username, $password) {

        return ($username == 'username' && $password == 'password');

    }

}
