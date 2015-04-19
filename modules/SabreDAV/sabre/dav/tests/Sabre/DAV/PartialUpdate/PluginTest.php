<?php

namespace Sabre\DAV\PartialUpdate;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/PartialUpdate/FileMock.php';

class PluginTest extends \Sabre\DAVServerTest {

    protected $node;
    protected $plugin;

    public function setUp() {

        $this->node = new FileMock();
        $this->tree[] = $this->node;

        parent::setUp();

        $this->plugin = new Plugin();
        $this->server->addPlugin($this->plugin);



    }

    public function testInit() {

        $this->assertEquals('partialupdate', $this->plugin->getPluginName());
        $this->assertEquals(array('sabredav-partialupdate'), $this->plugin->getFeatures());
        $this->assertEquals(array(
            'PATCH'
        ), $this->plugin->getHTTPMethods('partial'));
        $this->assertEquals(array(
        ), $this->plugin->getHTTPMethods(''));

    }

    public function testPatchNoRange() {

        $this->node->put('00000000');
        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'PATCH',
            'REQUEST_URI'    => '/partial',
        ));
        $response = $this->request($request);

        $this->assertEquals(400, $response->status, 'Full response body:' . $response->body);

    }

    public function testPatchNotSupported() {

        $this->node->put('00000000');
        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'PATCH',
            'REQUEST_URI'    => '/',
            'X_UPDATE_RANGE' => '3-4',

        ));
        $request->setBody(
            '111'
        );
        $response = $this->request($request);

        $this->assertEquals(405, $response->status, 'Full response body:' . $response->body);

    }

    public function testPatchNoContentType() {

        $this->node->put('00000000');
        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'      => 'PATCH',
            'REQUEST_URI'         => '/partial',
            'HTTP_X_UPDATE_RANGE' => 'bytes=3-4',

        ));
        $request->setBody(
            '111'
        );
        $response = $this->request($request);

        $this->assertEquals(415, $response->status, 'Full response body:' . $response->body);

    }

    public function testPatchBadRange() {

        $this->node->put('00000000');
        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'      => 'PATCH',
            'REQUEST_URI'         => '/partial',
            'HTTP_X_UPDATE_RANGE' => 'bytes=3-4',
            'HTTP_CONTENT_TYPE'   => 'application/x-sabredav-partialupdate',
        ));
        $request->setBody(
            '111'
        );
        $response = $this->request($request);

        $this->assertEquals(411, $response->status, 'Full response body:' . $response->body);

    }

    public function testPatchSuccess() {

        $this->node->put('00000000');
        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'      => 'PATCH',
            'REQUEST_URI'         => '/partial',
            'HTTP_X_UPDATE_RANGE' => 'bytes=3-5',
            'HTTP_CONTENT_TYPE'   => 'application/x-sabredav-partialupdate',
            'HTTP_CONTENT_LENGTH' => 3,
        ));
        $request->setBody(
            '111'
        );
        $response = $this->request($request);

        $this->assertEquals(204, $response->status, 'Full response body:' . $response->body);
        $this->assertEquals('00011100', $this->node->get());

    }

    public function testPatchNoEndRange() {

        $this->node->put('00000');
        $request = new HTTP\Request('PATCH','/partial',[
            'X-Update-Range' => 'bytes=3-',
            'Content-Type'   => 'application/x-sabredav-partialupdate',
            'Content-Length' => '3',
        ], '111');

        $response = $this->request($request);

        $this->assertEquals(204, $response->getStatus(), 'Full response body:' . $response->getBodyAsString());
        $this->assertEquals('00111', $this->node->get());

    }

}
