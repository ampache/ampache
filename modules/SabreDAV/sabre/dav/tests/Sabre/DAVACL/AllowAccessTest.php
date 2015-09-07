<?php

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\HTTP;

class AllowAccessTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var DAV\Server
     */
    protected $server;

    function setUp() {

        $nodes = array(
            new DAV\SimpleCollection('testdir'),
        );

        $this->server = new DAV\Server($nodes);
        $aclPlugin = new Plugin();
        $aclPlugin->allowAccessToNodesWithoutACL = true;
        $this->server->addPlugin($aclPlugin);

    }

    function testGet() {

        $this->server->httpRequest->setMethod('GET');
        $this->server->httpRequest->setUrl('/testdir');

        $this->assertTrue($this->server->emit('beforeMethod', [$this->server->httpRequest, $this->server->httpResponse]));

    }

    function testGetDoesntExist() {

        $this->server->httpRequest->setMethod('GET');
        $this->server->httpRequest->setUrl('/foo');

        $this->assertTrue($this->server->emit('beforeMethod', [$this->server->httpRequest, $this->server->httpResponse]));

    }

    function testHEAD() {

        $this->server->httpRequest->setMethod('HEAD');
        $this->server->httpRequest->setUrl('/testdir');

        $this->assertTrue($this->server->emit('beforeMethod', [$this->server->httpRequest, $this->server->httpResponse]));

    }

    function testOPTIONS() {

        $this->server->httpRequest->setMethod('OPTIONS');
        $this->server->httpRequest->setUrl('/testdir');

        $this->assertTrue($this->server->emit('beforeMethod', [$this->server->httpRequest, $this->server->httpResponse]));

    }

    function testPUT() {

        $this->server->httpRequest->setMethod('PUT');
        $this->server->httpRequest->setUrl('/testdir');

        $this->assertTrue($this->server->emit('beforeMethod', [$this->server->httpRequest, $this->server->httpResponse]));

    }

    function testACL() {

        $this->server->httpRequest->setMethod('ACL');
        $this->server->httpRequest->setUrl('/testdir');

        $this->assertTrue($this->server->emit('beforeMethod', [$this->server->httpRequest, $this->server->httpResponse]));

    }

    function testPROPPATCH() {

        $this->server->httpRequest->setMethod('PROPPATCH');
        $this->server->httpRequest->setUrl('/testdir');

        $this->assertTrue($this->server->emit('beforeMethod', [$this->server->httpRequest, $this->server->httpResponse]));

    }

    function testCOPY() {

        $this->server->httpRequest->setMethod('COPY');
        $this->server->httpRequest->setUrl('/testdir');

        $this->assertTrue($this->server->emit('beforeMethod', [$this->server->httpRequest, $this->server->httpResponse]));

    }

    function testMOVE() {

        $this->server->httpRequest->setMethod('MOVE');
        $this->server->httpRequest->setUrl('/testdir');

        $this->assertTrue($this->server->emit('beforeMethod', [$this->server->httpRequest, $this->server->httpResponse]));

    }

    function testLOCK() {

        $this->server->httpRequest->setMethod('LOCK');
        $this->server->httpRequest->setUrl('/testdir');

        $this->assertTrue($this->server->emit('beforeMethod', [$this->server->httpRequest, $this->server->httpResponse]));

    }

    function testBeforeBind() {

        $this->assertTrue($this->server->emit('beforeBind', ['testdir/file']));

    }


    function testBeforeUnbind() {

        $this->assertTrue($this->server->emit('beforeUnbind', ['testdir']));

    }

}
