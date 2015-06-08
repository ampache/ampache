<?php
namespace GuzzleHttp\Tests\Subscriber\Cache;

use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Cache\CacheSubscriber;

class CacheSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testCreatesAndAttachedDefaultSubscriber()
    {
        $client = new Client();
        $cache = CacheSubscriber::attach($client);
        $this->assertArrayHasKey('subscriber', $cache);
        $this->assertArrayHasKey('storage', $cache);
        $this->assertInstanceOf(
            'GuzzleHttp\Subscriber\Cache\CacheStorage',
            $cache['storage']
        );
        $this->assertInstanceOf(
            'GuzzleHttp\Subscriber\Cache\CacheSubscriber',
            $cache['subscriber']
        );
        $this->assertTrue($client->getEmitter()->hasListeners('error'));
    }
}
