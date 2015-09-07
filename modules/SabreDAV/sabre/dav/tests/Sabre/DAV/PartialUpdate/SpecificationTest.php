<?php

namespace Sabre\DAV\PartialUpdate;

use Sabre\DAV\FSExt\File;
use Sabre\DAV\Server;
use Sabre\HTTP;

/**
 * This test is an end-to-end sabredav test that goes through all
 * the cases in the specification.
 *
 * See: http://sabre.io/dav/http-patch/
 */
class SpecificationTest extends \PHPUnit_Framework_TestCase {

    protected $server;

    public function setUp() {

        $tree = array(
            new File(SABRE_TEMPDIR . '/foobar.txt')
        );
        $server = new Server($tree);
        $server->debugExceptions = true;
        $server->addPlugin(new Plugin());

        $tree[0]->put('1234567890');

        $this->server = $server;

    }

    public function tearDown() {

        \Sabre\TestUtil::clearTempDir();

    }

    /**
     * @dataProvider data
     */
    public function testUpdateRange($headerValue, $httpStatus, $endResult, $contentLength = 4) {

        $headers = [
            'Content-Type' => 'application/x-sabredav-partialupdate',
            'X-Update-Range' => $headerValue,
        ];

        if ($contentLength) {
            $headers['Content-Length'] = (string)$contentLength;
        }

        $request = new HTTP\Request('PATCH', '/foobar.txt', $headers, '----');

        $request->setBody('----');
        $this->server->httpRequest = $request;
        $this->server->httpResponse = new HTTP\ResponseMock();
        $this->server->sapi = new HTTP\SapiMock();
        $this->server->exec();

        $this->assertEquals($httpStatus, $this->server->httpResponse->status, 'Incorrect http status received: ' . $this->server->httpResponse->body);
        if (!is_null($endResult)) {
            $this->assertEquals($endResult, file_get_contents(SABRE_TEMPDIR . '/foobar.txt'));
        }

    } 

    public function data() {

        return array(
            // Problems
            array('foo',       400, null),
            array('bytes=0-3', 411, null, 0),
            array('bytes=4-1', 416, null),

            array('bytes=0-3', 204, '----567890'),
            array('bytes=1-4', 204, '1----67890'),
            array('bytes=0-',  204, '----567890'),
            array('bytes=-4',  204, '123456----'),
            array('bytes=-2',  204, '12345678----'),
            array('bytes=2-',  204, '12----7890'),
            array('append',    204, '1234567890----'),

        );

    }

}
