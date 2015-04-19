<?php

namespace Sabre\DAV\FSExt;

use Sabre\DAV;
use Sabre\DAV\PropPatch;

require_once 'Sabre/TestUtil.php';

class NodeTest extends \PHPUnit_Framework_TestCase {

    function setUp() {

        mkdir(SABRE_TEMPDIR . '/dir');
        file_put_contents(SABRE_TEMPDIR . '/dir/file.txt', 'Contents');
        file_put_contents(SABRE_TEMPDIR . '/dir/file2.txt', 'Contents2');

    }

    function tearDown() {

        \Sabre\TestUtil::clearTempDir();

    }

    function testUpdateProperties() {

        $file = new File(SABRE_TEMPDIR . '/dir/file.txt');
        $properties = array(
            '{http://sabredav.org/NS/2010}test1' => 'foo',
            '{http://sabredav.org/NS/2010}test2' => 'bar',
        );

        $propPatch = new PropPatch($properties);
        $file->propPatch($propPatch);
        $propPatch->commit();

        $getProperties = $file->getProperties(array_keys($properties));
        $this->assertEquals($properties, $getProperties);

    }

    /**
     * @depends testUpdateProperties
     */
    function testUpdatePropertiesAgain() {

        $file = new File(SABRE_TEMPDIR . '/dir/file.txt');
        $mutations = array(
            '{http://sabredav.org/NS/2010}test1' => 'foo',
            '{http://sabredav.org/NS/2010}test2' => 'bar',
        );

        $propPatch = new PropPatch($mutations);
        $file->propPatch($propPatch);
        $result = $propPatch->commit();

        $this->assertEquals(true, $result);

        $mutations = array(
            '{http://sabredav.org/NS/2010}test1' => 'foo',
            '{http://sabredav.org/NS/2010}test3' => 'baz',
        );

        $propPatch = new PropPatch($mutations);
        $file->propPatch($propPatch);
        $result = $propPatch->commit();

        $this->assertEquals(true, $result);
    }

    /**
     * @depends testUpdateProperties
     */
    function testUpdatePropertiesDelete() {

        $file = new File(SABRE_TEMPDIR . '/dir/file.txt');

        $mutations = array(
            '{http://sabredav.org/NS/2010}test1' => 'foo',
            '{http://sabredav.org/NS/2010}test2' => 'bar',
        );

        $propPatch = new PropPatch($mutations);
        $file->propPatch($propPatch);
        $result = $propPatch->commit();

        $this->assertEquals(true, $result);

        $mutations = array(
            '{http://sabredav.org/NS/2010}test1' => null,
            '{http://sabredav.org/NS/2010}test3' => null
        );


        $propPatch = new PropPatch($mutations);
        $file->propPatch($propPatch);
        $result = $propPatch->commit();

        $this->assertEquals(true, $result);

        $properties = $file->getProperties(array('http://sabredav.org/NS/2010}test1','{http://sabredav.org/NS/2010}test2','{http://sabredav.org/NS/2010}test3'));

        $this->assertEquals(array(
            '{http://sabredav.org/NS/2010}test2' => 'bar',
        ), $properties);
    }

    /**
     * @depends testUpdateProperties
     */
    function testUpdatePropertiesMove() {

        $file = new File(SABRE_TEMPDIR . '/dir/file.txt');

        $mutations = array(
            '{http://sabredav.org/NS/2010}test1' => 'foo',
            '{http://sabredav.org/NS/2010}test2' => 'bar',
        );

        $propPatch = new PropPatch($mutations);
        $file->propPatch($propPatch);
        $result = $propPatch->commit();

        $this->assertEquals(true, $result);

        $properties = $file->getProperties(array('{http://sabredav.org/NS/2010}test1','{http://sabredav.org/NS/2010}test2','{http://sabredav.org/NS/2010}test3'));

        $this->assertEquals(array(
            '{http://sabredav.org/NS/2010}test1' => 'foo',
            '{http://sabredav.org/NS/2010}test2' => 'bar',
        ), $properties);

        // Renaming
        $file->setName('file3.txt');

        $this->assertFalse(file_exists(SABRE_TEMPDIR . '/dir/file.txt'));
        $this->assertTrue(file_exists(SABRE_TEMPDIR . '/dir/file3.txt'));
        $this->assertEquals('file3.txt',$file->getName());

        $newFile = new File(SABRE_TEMPDIR . '/dir/file3.txt');
        $this->assertEquals('file3.txt',$newFile->getName());

        $properties = $newFile->getProperties(array('{http://sabredav.org/NS/2010}test1','{http://sabredav.org/NS/2010}test2','{http://sabredav.org/NS/2010}test3'));

        $this->assertEquals(array(
            '{http://sabredav.org/NS/2010}test1' => 'foo',
            '{http://sabredav.org/NS/2010}test2' => 'bar',
        ), $properties);
    }

    /**
     * @depends testUpdatePropertiesMove
     */
    function testUpdatePropertiesDeleteBleed() {

        $file = new File(SABRE_TEMPDIR . '/dir/file.txt');
        $mutations = array(
            '{http://sabredav.org/NS/2010}test1' => 'foo',
            '{http://sabredav.org/NS/2010}test2' => 'bar',
        );

        $propPatch = new PropPatch($mutations);
        $file->propPatch($propPatch);
        $result = $propPatch->commit();

        $this->assertEquals(true, $result);

        $properties = $file->getProperties(array('{http://sabredav.org/NS/2010}test1','{http://sabredav.org/NS/2010}test2','{http://sabredav.org/NS/2010}test3'));

        $this->assertEquals(array(
            '{http://sabredav.org/NS/2010}test1' => 'foo',
            '{http://sabredav.org/NS/2010}test2' => 'bar',
        ), $properties);

        // Deleting
        $file->delete();

        $this->assertFalse(file_exists(SABRE_TEMPDIR . '/dir/file.txt'));

        // Creating it again
        file_put_contents(SABRE_TEMPDIR . '/dir/file.txt','New Contents');
        $file = new File(SABRE_TEMPDIR . '/dir/file.txt');

        $properties = $file->getProperties(array('http://sabredav.org/NS/2010}test1','{http://sabredav.org/NS/2010}test2','{http://sabredav.org/NS/2010}test3'));

        $this->assertEquals(array(), $properties);

    }

}
