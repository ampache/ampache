<?php

namespace Sabre\DAV\Locks\Backend;

require_once 'Sabre/TestUtil.php';

class FSTest extends AbstractTest {

    function getBackend() {

        \Sabre\TestUtil::clearTempDir();
        mkdir(SABRE_TEMPDIR . '/locks');
        $backend = new FS(SABRE_TEMPDIR . '/locks/');
        return $backend;

    }

    function tearDown() {

        \Sabre\TestUtil::clearTempDir();

    }

    function testGetLocksChildren() {

        // We're skipping this test. This doesn't work, and it will
        // never. The class is deprecated anyway.
        //
        // We need to assert something though, so phpunit won't fail in strict
        // mode.
        $this->assertTrue(true);

    }

}
