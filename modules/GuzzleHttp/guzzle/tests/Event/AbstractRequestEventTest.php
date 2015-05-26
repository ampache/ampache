<?php
namespace GuzzleHttp\Tests\Event;

use GuzzleHttp\Client;
use GuzzleHttp\Transaction;
use GuzzleHttp\Message\Request;

/**
 * @covers GuzzleHttp\Event\AbstractRequestEvent
 */
class AbstractRequestEventTest extends \PHPUnit_Framework_TestCase
{
    public function testHasTransactionMethods()
    {
        $t = new Transaction(new Client(), new Request('GET', '/'));
        $e = $this->getMockBuilder('GuzzleHttp\Event\AbstractRequestEvent')
            ->setConstructorArgs([$t])
            ->getMockForAbstractClass();
        $this->assertSame($t->client, $e->getClient());
        $this->assertSame($t->request, $e->getRequest());
    }

    public function testHasTransaction()
    {
        $t = new Transaction(new Client(), new Request('GET', '/'));
        $e = $this->getMockBuilder('GuzzleHttp\Event\AbstractRequestEvent')
            ->setConstructorArgs([$t])
            ->getMockForAbstractClass();
        $r = new \ReflectionMethod($e, 'getTransaction');
        $r->setAccessible(true);
        $this->assertSame($t, $r->invoke($e));
    }
}
