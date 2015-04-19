<?php

namespace Sabre\DAV;

use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';

class ServerCopyMoveTest extends \PHPUnit_Framework_TestCase {

    private $response;
    /**
     * @var Server
     */
    private $server;

    function setUp() {

        $this->response = new HTTP\ResponseMock();
        $dir = new FS\Directory(SABRE_TEMPDIR);
        $tree = new Tree($dir);
        $this->server = new Server($tree);
        $this->server->sapi = new HTTP\SapiMock();
        $this->server->debugExceptions = true;
        $this->server->httpResponse = $this->response;
        file_put_contents(SABRE_TEMPDIR . '/test.txt', 'Test contents');
        file_put_contents(SABRE_TEMPDIR . '/test2.txt', 'Test contents2');
        mkdir(SABRE_TEMPDIR . '/col');
        file_put_contents(SABRE_TEMPDIR . 'col/test.txt', 'Test contents');

    }

    function tearDown() {

        $cleanUp = array('test.txt','testput.txt','testcol','test2.txt','test3.txt','col/test.txt','col','col2/test.txt','col2');
        foreach($cleanUp as $file) {
            $tmpFile = SABRE_TEMPDIR . '/' . $file;
            if (file_exists($tmpFile)) {

                if (is_dir($tmpFile)) {
                    rmdir($tmpFile);
                } else {
                    unlink($tmpFile);
                }

            }
        }

    }


    function testCopyOverWrite() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'COPY',
            'HTTP_DESTINATION' => '/test2.txt',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(204, $this->response->status, 'Received an incorrect HTTP status. Full body inspection: ' . $this->response->body);
        $this->assertEquals(array(
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Length' => ['0'],
            ),
            $this->response->getHeaders()
         );

        $this->assertEquals('Test contents', file_get_contents(SABRE_TEMPDIR. '/test2.txt'));

    }

    function testCopyToSelf() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'COPY',
            'HTTP_DESTINATION' => '/test.txt',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(403, $this->response->status, 'Received an incorrect HTTP status. Full body inspection: ' . $this->response->body);
        $this->assertEquals('Test contents', file_get_contents(SABRE_TEMPDIR. '/test.txt'));

    }

    function testMoveToSelf() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'MOVE',
            'HTTP_DESTINATION' => '/test.txt',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(403, $this->response->status, 'Received an incorrect HTTP status. Full body inspection: ' . $this->response->body);
        $this->assertEquals('Test contents', file_get_contents(SABRE_TEMPDIR. '/test.txt'));

    }

    function testMoveOverWrite() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'MOVE',
            'HTTP_DESTINATION' => '/test2.txt',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Length' => [0],
            ),
            $this->response->getHeaders()
         );

        $this->assertEquals(204, $this->response->status);
        $this->assertEquals('Test contents', file_get_contents(SABRE_TEMPDIR . '/test2.txt'));
        $this->assertFalse(file_exists(SABRE_TEMPDIR . '/test.txt'), 'The sourcefile test.txt should no longer exist at this point');

    }

    function testBlockedOverWrite() {

        $serverVars = array(
            'REQUEST_URI'      => '/test.txt',
            'REQUEST_METHOD'   => 'COPY',
            'HTTP_DESTINATION' => '/test2.txt',
            'HTTP_OVERWRITE'   => 'F',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Type' => ['application/xml; charset=utf-8'],
            ),
            $this->response->getHeaders()
         );

        $this->assertEquals(412, $this->response->status);
        $this->assertEquals('Test contents2', file_get_contents(SABRE_TEMPDIR . '/test2.txt'));


    }

    function testNonExistantParent() {

        $serverVars = array(
            'REQUEST_URI'      => '/test.txt',
            'REQUEST_METHOD'   => 'COPY',
            'HTTP_DESTINATION' => '/testcol2/test2.txt',
            'HTTP_OVERWRITE'   => 'F',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Type' => ['application/xml; charset=utf-8'],
            ),
            $this->response->getHeaders()
         );

        $this->assertEquals(409, $this->response->status);

    }

    function testRandomOverwriteHeader() {

        $serverVars = array(
            'REQUEST_URI'      => '/test.txt',
            'REQUEST_METHOD'   => 'COPY',
            'HTTP_DESTINATION' => '/testcol2/test2.txt',
            'HTTP_OVERWRITE'   => 'SURE!',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(400, $this->response->status);

    }

    function testCopyDirectory() {

        $serverVars = array(
            'REQUEST_URI'    => '/col',
            'REQUEST_METHOD' => 'COPY',
            'HTTP_DESTINATION' => '/col2',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(201, $this->response->status, 'Full response: ' . $this->response->getBody(true));

        $this->assertEquals(array(
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Length' => ['0'],
            ),
            $this->response->getHeaders()
         );

        $this->assertEquals('Test contents',file_get_contents(SABRE_TEMPDIR . '/col2/test.txt'));

    }

    function testSimpleCopyFile() {

        $serverVars = array(
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'COPY',
            'HTTP_DESTINATION' => '/test3.txt',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Length' => ['0'],
            ),
            $this->response->getHeaders()
         );

        $this->assertEquals(201, $this->response->status);
        $this->assertEquals('Test contents', file_get_contents(SABRE_TEMPDIR . '/test3.txt'));

    }

    function testSimpleCopyCollection() {

        $serverVars = array(
            'REQUEST_URI'    => '/col',
            'REQUEST_METHOD' => 'COPY',
            'HTTP_DESTINATION' => '/col2',
        );

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(201, $this->response->status, 'Incorrect status received. Full response body: ' . $this->response->body);

        $this->assertEquals(array(
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Length' => ['0'],
            ),
            $this->response->getHeaders()
         );


        $this->assertEquals('Test contents',file_get_contents(SABRE_TEMPDIR . '/col2/test.txt'));

    }

}
