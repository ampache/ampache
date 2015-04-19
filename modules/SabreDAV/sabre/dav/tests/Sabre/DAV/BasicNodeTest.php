<?php

namespace Sabre\DAV;

class BasicNodeTest extends \PHPUnit_Framework_TestCase {

    /**
     * @expectedException Sabre\DAV\Exception\Forbidden
     */
    public function testPut() {

        $file = new FileMock();
        $file->put('hi');

    }

    /**
     * @expectedException Sabre\DAV\Exception\Forbidden
     */
    public function testGet() {

        $file = new FileMock();
        $file->get();

    }

    public function testGetSize() {

        $file = new FileMock();
        $this->assertEquals(0,$file->getSize());

    }


    public function testGetETag() {

        $file = new FileMock();
        $this->assertNull($file->getETag());

    }

    public function testGetContentType() {

        $file = new FileMock();
        $this->assertNull($file->getContentType());

    }

    /**
     * @expectedException Sabre\DAV\Exception\Forbidden
     */
    public function testDelete() {

        $file = new FileMock();
        $file->delete();

    }

    /**
     * @expectedException Sabre\DAV\Exception\Forbidden
     */
    public function testSetName() {

        $file = new FileMock();
        $file->setName('hi');

    }

    public function testGetLastModified() {

        $file = new FileMock();
        // checking if lastmod is within the range of a few seconds
        $lastMod = $file->getLastModified();
        $compareTime = ($lastMod + 1)-time();
        $this->assertTrue($compareTime < 3);

    }

    public function testGetChild() {

        $dir = new DirectoryMock();
        $file = $dir->getChild('mockfile');
        $this->assertTrue($file instanceof FileMock);

    }

    public function testChildExists() {

        $dir = new DirectoryMock();
        $this->assertTrue($dir->childExists('mockfile'));

    }

    public function testChildExistsFalse() {

        $dir = new DirectoryMock();
        $this->assertFalse($dir->childExists('mockfile2'));

    }

    /**
     * @expectedException Sabre\DAV\Exception\NotFound
     */
    public function testGetChild404() {

        $dir = new DirectoryMock();
        $file = $dir->getChild('blabla');

    }

    /**
     * @expectedException Sabre\DAV\Exception\Forbidden
     */
    public function testCreateFile() {

        $dir = new DirectoryMock();
        $dir->createFile('hello','data');

    }

    /**
     * @expectedException Sabre\DAV\Exception\Forbidden
     */
    public function testCreateDirectory() {

        $dir = new DirectoryMock();
        $dir->createDirectory('hello');

    }

    public function testSimpleDirectoryConstruct() {

        $dir = new SimpleCollection('simpledir',array());
        $this->assertInstanceOf('Sabre\DAV\SimpleCollection', $dir);

    }

    /**
     * @depends testSimpleDirectoryConstruct
     */
    public function testSimpleDirectoryConstructChild() {

        $file = new FileMock();
        $dir = new SimpleCollection('simpledir',array($file));
        $file2 = $dir->getChild('mockfile');

        $this->assertEquals($file,$file2);

    }

    /**
     * @expectedException Sabre\DAV\Exception
     * @depends testSimpleDirectoryConstruct
     */
    public function testSimpleDirectoryBadParam() {

        $dir = new SimpleCollection('simpledir',array('string shouldn\'t be here'));

    }

    /**
     * @depends testSimpleDirectoryConstruct
     */
    public function testSimpleDirectoryAddChild() {

        $file = new FileMock();
        $dir = new SimpleCollection('simpledir');
        $dir->addChild($file);
        $file2 = $dir->getChild('mockfile');

        $this->assertEquals($file,$file2);

    }

    /**
     * @depends testSimpleDirectoryConstruct
     * @depends testSimpleDirectoryAddChild
     */
    public function testSimpleDirectoryGetChildren() {

        $file = new FileMock();
        $dir = new SimpleCollection('simpledir');
        $dir->addChild($file);

        $this->assertEquals(array($file),$dir->getChildren());

    }

    /*
     * @depends testSimpleDirectoryConstruct
     */
    public function testSimpleDirectoryGetName() {

        $dir = new SimpleCollection('simpledir');
        $this->assertEquals('simpledir',$dir->getName());

    }

    /**
     * @depends testSimpleDirectoryConstruct
     * @expectedException Sabre\DAV\Exception\NotFound
     */
    public function testSimpleDirectoryGetChild404() {

        $dir = new SimpleCollection('simpledir');
        $dir->getChild('blabla');

    }
}

class DirectoryMock extends Collection {

    function getName() {

        return 'mockdir';

    }

    function getChildren() {

        return array(new FileMock());

    }

}

class FileMock extends File {

    function getName() {

        return 'mockfile';

    }

}
