<?php
namespace GuzzleHttp\Subscriber\Cache;

use GuzzleHttp\Event\HasEmitterInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Event\ErrorEvent;
use GuzzleHttp\Event\RequestEvents;
use GuzzleHttp\Event\SubscriberInterface;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use Doctrine\Common\Cache\ArrayCache;

/**
 * Plugin to enable the caching of GET and HEAD requests.
 *
 * Caching can be done on all requests passing through this plugin or only
 * after retrieving resources with cacheable response headers.
 *
 * This is a simple implementation of RFC 2616 and should be considered a
 * private transparent proxy cache, meaning authorization and private data can
 * be cached.
 *
 * It also implements RFC 5861's `stale-if-error` Cache-Control extension,
 * allowing stale cache responses to be used when an error is encountered
 * (such as a `500 Internal Server Error` or DNS failure).
 */
class CacheSubscriber implements SubscriberInterface
{
    /** @var callable Determines if a request is cacheable */
    protected $canCache;

    /** @var CacheStorageInterface $cache Object used to cache responses */
    protected $storage;

    /**
     * @param CacheStorageInterface $cache    Cache storage
     * @param callable              $canCache Callable used to determine if a
     *                                        request can be cached. Accepts a
     *                                        RequestInterface and returns a
     *                                        boolean value.
     */
    public function __construct(
        CacheStorageInterface $cache,
        callable $canCache
    ) {
        $this->storage = $cache;
        $this->canCache = $canCache;
    }

    /**
     * Helper method used to easily attach a cache to a request or client.
     *
     * This method accepts an array of options that are used to control the
     * caching behavior:
     *
     * - storage: An optional GuzzleHttp\Subscriber\Cache\CacheStorageInterface.
     *   If no value is not provided, an in-memory array cache will be used.
     * - validate: Boolean value that determines if cached response are ever
     *   validated against the origin server. Defaults to true but can be
     *   disabled by passing false.
     * - purge: Boolean value that determines if cached responses are purged
     *   when non-idempotent requests are sent to their URI. Defaults to true
     *   but can be disabled by passing false.
     * - can_cache: An optional callable used to determine if a request can be
     *   cached. The callable accepts a RequestInterface and returns a boolean
     *   value. If no value is provided, the default behavior is utilized.
     *
     * @param HasEmitterInterface $subject Client or request to attach to,
     * @param array               $options Options used to control the cache.
     *
     * @return array Returns an associative array containing a 'subscriber' key
     *               that holds the created CacheSubscriber, and a 'storage'
     *               key that contains the cache storage used by the subscriber.
     */
    public static function attach(
        HasEmitterInterface $subject,
        array $options = []
    ) {
        if (!isset($options['storage'])) {
            $options['storage'] = new CacheStorage(new ArrayCache());
        }

        if (!isset($options['can_cache'])) {
            $options['can_cache'] = [
                'GuzzleHttp\Subscriber\Cache\Utils',
                'canCacheRequest'
            ];
        }

        $emitter = $subject->getEmitter();
        $cache = new self($options['storage'], $options['can_cache']);
        $emitter->attach($cache);

        if (!isset($options['validate']) || $options['validate'] === true) {
            $emitter->attach(new ValidationSubscriber(
                $options['storage'],
                $options['can_cache'])
            );
        }

        if (!isset($options['purge']) || $options['purge'] === true) {
            $emitter->attach(new PurgeSubscriber($options['storage']));
        }

        return ['subscriber' => $cache, 'storage' => $options['storage']];
    }

    public function getEvents()
    {
        return [
            'before'   => ['onBefore', RequestEvents::LATE],
            'complete' => ['onComplete', RequestEvents::EARLY],
            'error'    => ['onError', RequestEvents::EARLY]
        ];
    }

    /**
     * Checks if a request can be cached, and if so, intercepts with a cached
     * response is available.
     */
    public function onBefore(BeforeEvent $event)
    {
        $request = $event->getRequest();

        if (!$this->canCacheRequest($request)) {
            $this->cacheMiss($request);
            return;
        }

        if (!($response = $this->storage->fetch($request))) {
            $this->cacheMiss($request);
            return;
        }

        $response->setHeader('Age', Utils::getResponseAge($response));
        $valid = $this->validate($request, $response);

        // Validate that the response satisfies the request
        if ($valid) {
            $request->getConfig()->set('cache_lookup', 'HIT');
            $request->getConfig()->set('cache_hit', true);
            $event->intercept($response);
        } else {
            $this->cacheMiss($request);
        }
    }

    /**
     * Checks if the request and response can be cached, and if so, store it
     */
    public function onComplete(CompleteEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Cache the response if it can be cached and isn't already
        if ($request->getConfig()->get('cache_lookup') === 'MISS'
            && call_user_func($this->canCache, $request)
            && Utils::canCacheResponse($response)
        ) {
            $this->storage->cache($request, $response);
        }

        $this->addResponseHeaders($request, $response);
    }

    /**
     * If the request failed, then check if a cached response would suffice
     */
    public function onError(ErrorEvent $event)
    {
        $request = $event->getRequest();

        if (!call_user_func($this->canCache, $request)) {
            return;
        }

        $response = $this->storage->fetch($request);

        // Intercept the failed response if possible
        if ($response && $this->validateFailed($request, $response)) {
            $request->getConfig()->set('cache_hit', 'error');
            $response->setHeader('Age', Utils::getResponseAge($response));
            $this->addResponseHeaders($request, $response);
            $event->intercept($response);
        }
    }

    private function cacheMiss(RequestInterface $request)
    {
        $request->getConfig()->set('cache_lookup', 'MISS');
    }

    private function validate(
        RequestInterface $request,
        ResponseInterface $response
    ) {
        // Validation is handled in another subscriber and can be optionally
        // enabled/disabled.
        if (Utils::getDirective($response, 'must-revalidate')) {
            return true;
        }

        return Utils::isResponseValid($request, $response);
    }

    private function validateFailed(
        RequestInterface $request,
        ResponseInterface $response
    ) {
        $req = Utils::getDirective($request, 'stale-if-error');
        $res = Utils::getDirective($response, 'stale-if-error');

        if (!$req && !$res) {
            return false;
        }

        $responseAge = Utils::getResponseAge($response);
        $maxAge = Utils::getMaxAge($response);

        if (($req && $responseAge - $maxAge > $req) ||
            ($responseAge - $maxAge > $res)
        ) {
            return false;
        }

        return true;
    }

    private function canCacheRequest(RequestInterface $request)
    {
        return !$request->getConfig()->get('cache.disable')
        && call_user_func($this->canCache, $request);
    }

    private function addResponseHeaders(
        RequestInterface $request,
        ResponseInterface $response
    ) {
        $params = $request->getConfig();
        $lookup = $params['cache_lookup'] . ' from GuzzleCache';
        $response->addHeader('X-Cache-Lookup', $lookup);

        if ($params['cache_hit'] === true) {
            $response->addHeader('X-Cache', 'HIT from GuzzleCache');
        } elseif ($params['cache_hit'] == 'error') {
            $response->addHeader('X-Cache', 'HIT_ERROR from GuzzleCache');
        } else {
            $response->addHeader('X-Cache', 'MISS from GuzzleCache');
        }

        $freshness = Utils::getFreshness($response);

        if ($freshness !== null && $freshness <= 0) {
            $response->addHeader(
                'Warning',
                sprintf(
                    '%d GuzzleCache/' . ClientInterface::VERSION . ' "%s"',
                    110,
                    'Response is stale'
                )
            );
        }
    }
}
