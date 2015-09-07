<?php

namespace Sabre\DAV\Auth\Backend;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';

class AbstractDigestTest extends \PHPUnit_Framework_TestCase {

    /**
     * @expectedException Sabre\DAV\Exception\NotAuthenticated
     */
    public function testAuthenticateNoHeaders() {

        $response = new HTTP\ResponseMock();
        $server = new DAV\Server();
        $server->httpResponse = $response;

        $backend = new AbstractDigestMock();
        $backend->authenticate($server,'myRealm');

    }

    /**
     * @expectedException Sabre\DAV\Exception
     */
    public function testAuthenticateBadGetUserInfoResponse() {

        $response = new HTTP\ResponseMock();
        $server = new DAV\Server();
        $server->httpResponse = $response;

        $header = 'username=null, realm=myRealm, nonce=12345, uri=/, response=HASH, opaque=1, qop=auth, nc=1, cnonce=1';
        $request = HTTP\Sapi::createFromServerArray(array(
            'PHP_AUTH_DIGEST' => $header,
        ));
        $server->httpRequest = $request;

        $backend = new AbstractDigestMock();
        $backend->authenticate($server,'myRealm');

    }

    /**
     * @expectedException Sabre\DAV\Exception
     */
    public function testAuthenticateBadGetUserInfoResponse2() {

        $response = new HTTP\ResponseMock();
        $server = new DAV\Server();
        $server->httpResponse = $response;

        $header = 'username=array, realm=myRealm, nonce=12345, uri=/, response=HASH, opaque=1, qop=auth, nc=1, cnonce=1';
        $request = HTTP\Sapi::createFromServerArray(array(
            'PHP_AUTH_DIGEST' => $header,
        ));
        $server->httpRequest = $request;

        $backend = new AbstractDigestMock();
        $backend->authenticate($server,'myRealm');

    }

    /**
     * @expectedException Sabre\DAV\Exception\NotAuthenticated
     */
    public function testAuthenticateUnknownUser() {

        $response = new HTTP\ResponseMock();
        $server = new DAV\Server();
        $server->httpResponse = $response;

        $header = 'username=false, realm=myRealm, nonce=12345, uri=/, response=HASH, opaque=1, qop=auth, nc=1, cnonce=1';
        $request = HTTP\Sapi::createFromServerArray(array(
            'PHP_AUTH_DIGEST' => $header,
        ));
        $server->httpRequest = $request;

        $backend = new AbstractDigestMock();
        $backend->authenticate($server,'myRealm');

    }

    /**
     * @expectedException Sabre\DAV\Exception\NotAuthenticated
     */
    public function testAuthenticateBadPassword() {

        $response = new HTTP\ResponseMock();
        $server = new DAV\Server();
        $server->httpResponse = $response;

        $header = 'username=user, realm=myRealm, nonce=12345, uri=/, response=HASH, opaque=1, qop=auth, nc=1, cnonce=1';
        $request = HTTP\Sapi::createFromServerArray(array(
            'PHP_AUTH_DIGEST' => $header,
            'REQUEST_METHOD'  => 'PUT',
        ));
        $server->httpRequest = $request;

        $backend = new AbstractDigestMock();
        $backend->authenticate($server,'myRealm');

    }

    public function testAuthenticate() {

        $response = new HTTP\ResponseMock();
        $server = new DAV\Server();
        $server->httpResponse = $response;

        $digestHash = md5('HELLO:12345:1:1:auth:' . md5('GET:/'));
        $header = 'username=user, realm=myRealm, nonce=12345, uri=/, response='.$digestHash.', opaque=1, qop=auth, nc=1, cnonce=1';
        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'  => 'GET',
            'PHP_AUTH_DIGEST' => $header,
            'REQUEST_URI'     => '/',
        ));
        $server->httpRequest = $request;

        $backend = new AbstractDigestMock();
        $this->assertTrue($backend->authenticate($server,'myRealm'));

        $result = $backend->getCurrentUser();

        $this->assertEquals('user', $result);
        $this->assertEquals('HELLO', $backend->getDigestHash('myRealm', $result));

    }


}


class AbstractDigestMock extends AbstractDigest {

    function getDigestHash($realm, $userName) {

        switch($userName) {
            case 'null' : return null;
            case 'false' : return false;
            case 'array' : return array();
            case 'user'  : return 'HELLO';
        }

    }

}
