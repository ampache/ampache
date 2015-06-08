<?php
namespace GuzzleHttp\Subscriber\Cache;

use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\RequestEvents;
use GuzzleHttp\Event\SubscriberInterface;

/**
 * Automatically purges a URL when a non-idempotent request is made to it.
 */
class PurgeSubscriber implements SubscriberInterface
{
    /** @var CacheStorageInterface */
    private $storage;

    /** @var array */
    private static $purgeMethods = [
        'PUT'    => true,
        'POST'   => true,
        'DELETE' => true,
        'PATCH'  => true
    ];

    /**
     * @param CacheStorageInterface $storage Storage to modify if purging
     */
    public function __construct($storage)
    {
        $this->storage = $storage;
    }

    public function getEvents()
    {
        return ['before' => ['onBefore', RequestEvents::LATE]];
    }

    public function onBefore(BeforeEvent $event)
    {
        $request = $event->getRequest();

        if (isset(self::$purgeMethods[$request->getMethod()])) {
            $this->storage->purge($request);
        }
    }
}
