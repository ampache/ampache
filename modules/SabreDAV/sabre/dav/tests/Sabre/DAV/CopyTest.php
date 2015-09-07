<?php

namespace Sabre\DAV;

use Sabre\HTTP;

class CopyTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {

        \Sabre\TestUtil::clearTempDir();

    }

    /**
     * This test makes sure that a path like /foo cannot be copied into a path
     * like /foo/bar/
     *
     * @expectedException \Sabre\DAV\Exception\Conflict
     */
    public function testCopyIntoSubPath() {

        $dir = new FS\Directory(SABRE_TEMPDIR);
        $server = new Server($dir);

        $dir->createDirectory('foo');

        $request = new HTTP\Request('COPY','/foo', [
            'Destination' => '/foo/bar',
        ]);
        $response = new HTTP\ResponseMock();

        $server->invokeMethod($request, $response);

    }

}
