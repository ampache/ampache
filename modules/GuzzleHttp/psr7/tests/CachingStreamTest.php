<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\CachingStream;

/**
 * @covers GuzzleHttp\Psr7\CachingStream
 */
class CachingStreamTest extends \PHPUnit_Framework_TestCase
{
    /** @var CachingStream */
    protected $body;
    protected $decorated;

    public function setUp()
    {
        $this->decorated = Psr7\stream_for('testing');
        $this->body = new CachingStream($this->decorated);
    }

    public function tearDown()
    {
        $this->decorated->close();
        $this->body->close();
    }

    public function testUsesRemoteSizeIfPossible()
    {
        $body = Psr7\stream_for('test');
        $caching = new CachingStream($body);
        $this->assertEquals(4, $caching->getSize());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot seek to byte 10
     */
    public function testCannotSeekPastWhatHasBeenRead()
    {
        $this->body->seek(10);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage CachingStream::seek() supports SEEK_SET and SEEK_CUR
     */
    public function testCannotUseSeekEnd()
    {
        $this->body->seek(2, SEEK_END);
    }

    public function testRewindUsesSeek()
    {
        $a = Psr7\stream_for('foo');
        $d = $this->getMockBuilder('GuzzleHttp\Psr7\CachingStream')
            ->setMethods(array('seek'))
            ->setConstructorArgs(array($a))
            ->getMock();
        $d->expects($this->once())
            ->method('seek')
            ->with(0)
            ->will($this->returnValue(true));
        $d->seek(0);
    }

    public function testCanSeekToReadBytes()
    {
        $this->assertEquals('te', $this->body->read(2));
        $this->body->seek(0);
        $this->assertEquals('test', $this->body->read(4));
        $this->assertEquals(4, $this->body->tell());
        $this->body->seek(2);
        $this->assertEquals(2, $this->body->tell());
        $this->body->seek(2, SEEK_CUR);
        $this->assertEquals(4, $this->body->tell());
        $this->assertEquals('ing', $this->body->read(3));
    }

    public function testWritesToBufferStream()
    {
        $this->body->read(2);
        $this->body->write('hi');
        $this->body->seek(0);
        $this->assertEquals('tehiing', (string) $this->body);
    }

    public function testSkipsOverwrittenBytes()
    {
        $decorated = Psr7\stream_for(
            implode("\n", array_map(function ($n) {
                return str_pad($n, 4, '0', STR_PAD_LEFT);
            }, range(0, 25)))
        );

        $body = new CachingStream($decorated);

        $this->assertEquals("0000\n", Psr7\readline($body));
        $this->assertEquals("0001\n", Psr7\readline($body));
        // Write over part of the body yet to be read, so skip some bytes
        $this->assertEquals(5, $body->write("TEST\n"));
        $this->assertEquals(5, $this->readAttribute($body, 'skipReadBytes'));
        // Read, which skips bytes, then reads
        $this->assertEquals("0003\n", Psr7\readline($body));
        $this->assertEquals(0, $this->readAttribute($body, 'skipReadBytes'));
        $this->assertEquals("0004\n", Psr7\readline($body));
        $this->assertEquals("0005\n", Psr7\readline($body));

        // Overwrite part of the cached body (so don't skip any bytes)
        $body->seek(5);
        $this->assertEquals(5, $body->write("ABCD\n"));
        $this->assertEquals(0, $this->readAttribute($body, 'skipReadBytes'));
        $this->assertEquals("TEST\n", Psr7\readline($body));
        $this->assertEquals("0003\n", Psr7\readline($body));
        $this->assertEquals("0004\n", Psr7\readline($body));
        $this->assertEquals("0005\n", Psr7\readline($body));
        $this->assertEquals("0006\n", Psr7\readline($body));
        $this->assertEquals(5, $body->write("1234\n"));
        $this->assertEquals(5, $this->readAttribute($body, 'skipReadBytes'));

        // Seek to 0 and ensure the overwritten bit is replaced
        $body->seek(0);
        $this->assertEquals("0000\nABCD\nTEST\n0003\n0004\n0005\n0006\n1234\n0008\n0009\n", $body->read(50));

        // Ensure that casting it to a string does not include the bit that was overwritten
        $this->assertContains("0000\nABCD\nTEST\n0003\n0004\n0005\n0006\n1234\n0008\n0009\n", (string) $body);
    }

    public function testClosesBothStreams()
    {
        $s = fopen('php://temp', 'r');
        $a = Psr7\stream_for($s);
        $d = new CachingStream($a);
        $d->close();
        $this->assertFalse(is_resource($s));
    }
}
