<?php

namespace Sabre\DAV\PropertyStorage\Backend;

use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;

abstract class AbstractPDOTest extends \PHPUnit_Framework_TestCase {

    /**
     * Should return an instance of \PDO with the current tables initialized,
     * and some test records.
     */
    abstract function getPDO();

    function getBackend() {

        return new PDO($this->getPDO());

    }

    function testPropFind() {

        $backend = $this->getBackend();

        $propFind = new PropFind('dir', ['{DAV:}displayname']);
        $backend->propFind('dir', $propFind);

        $this->assertEquals('Directory', $propFind->get('{DAV:}displayname'));

    }

    function testPropFindNothingToDo() {

        $backend = $this->getBackend();

        $propFind = new PropFind('dir', ['{DAV:}displayname']);
        $propFind->set('{DAV:}displayname', 'foo');
        $backend->propFind('dir', $propFind);

        $this->assertEquals('foo', $propFind->get('{DAV:}displayname'));

    }

    /**
     * @depends testPropFind
     */
    function testPropPatchUpdate() {

        $backend = $this->getBackend();

        $propPatch = new PropPatch(['{DAV:}displayname' => 'bar']);
        $backend->propPatch('dir', $propPatch);
        $propPatch->commit();

        $propFind = new PropFind('dir', ['{DAV:}displayname']);
        $backend->propFind('dir', $propFind);

        $this->assertEquals('bar', $propFind->get('{DAV:}displayname'));

    }

    /**
     * @depends testPropFind
     */
    function testPropPatchRemove() {

        $backend = $this->getBackend();

        $propPatch = new PropPatch(['{DAV:}displayname' => null]);
        $backend->propPatch('dir', $propPatch);
        $propPatch->commit();

        $propFind = new PropFind('dir', ['{DAV:}displayname']);
        $backend->propFind('dir', $propFind);

        $this->assertEquals(null, $propFind->get('{DAV:}displayname'));

    }

    /**
     * @depends testPropFind
     */
    function testDelete() {

        $backend = $this->getBackend();
        $backend->delete('dir');

        $propFind = new PropFind('dir', ['{DAV:}displayname']);
        $backend->propFind('dir', $propFind);

        $this->assertEquals(null, $propFind->get('{DAV:}displayname'));

    }

    /**
     * @depends testPropFind
     */
    function testMove() {

        $backend = $this->getBackend();
        // Creating a new child property.
        $propPatch = new PropPatch(['{DAV:}displayname' => 'child']);
        $backend->propPatch('dir/child', $propPatch);
        $propPatch->commit();

        $backend->move('dir','dir2');

        // Old 'dir'
        $propFind = new PropFind('dir', ['{DAV:}displayname']);
        $backend->propFind('dir', $propFind);
        $this->assertEquals(null, $propFind->get('{DAV:}displayname'));

        // Old 'dir/child'
        $propFind = new PropFind('dir/child', ['{DAV:}displayname']);
        $backend->propFind('dir/child', $propFind);
        $this->assertEquals(null, $propFind->get('{DAV:}displayname'));

        // New 'dir2'
        $propFind = new PropFind('dir2', ['{DAV:}displayname']);
        $backend->propFind('dir2', $propFind);
        $this->assertEquals('Directory', $propFind->get('{DAV:}displayname'));

        // New 'dir2/child'
        $propFind = new PropFind('dir2/child', ['{DAV:}displayname']);
        $backend->propFind('dir2/child', $propFind);
        $this->assertEquals('child', $propFind->get('{DAV:}displayname'));
    }

    /**
     * @depends testPropFind
     */
    function testDeepDelete() {

        $backend = $this->getBackend();
        $propPatch = new PropPatch(['{DAV:}displayname' => 'child']);
        $backend->propPatch('dir/child', $propPatch);
        $propPatch->commit();
        $backend->delete('dir');

        $propFind = new PropFind('dir/child', ['{DAV:}displayname']);
        $backend->propFind('dir/child', $propFind);

        $this->assertEquals(null, $propFind->get('{DAV:}displayname'));

    }
}
