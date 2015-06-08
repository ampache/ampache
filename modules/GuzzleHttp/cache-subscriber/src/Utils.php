<?php
namespace GuzzleHttp\Subscriber\Cache;

use GuzzleHttp\Message\AbstractMessage;
use GuzzleHttp\Message\MessageInterface;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;

/**
 * Cache utility functions
 */
class Utils
{
    /**
     * Get a cache control directive from a message
     *
     * @param MessageInterface $message Message to retrieve
     * @param string           $part    Cache directive to retrieve
     *
     * @return mixed|bool|null
     */
    public static function getDirective(MessageInterface $message, $part)
    {
        $parts = AbstractMessage::parseHeader($message, 'Cache-Control');

        foreach ($parts as $line) {
            if (isset($line[$part])) {
                return $line[$part];
            } elseif (in_array($part, $line)) {
                return true;
            }
        }

        return null;
    }

    /**
     * Gets the age of a response in seconds.
     *
     * @param ResponseInterface $response
     *
     * @return int
     */
    public static function getResponseAge(ResponseInterface $response)
    {
        if ($response->hasHeader('Age')) {
            return (int) $response->getHeader('Age');
        }

        $date = strtotime($response->getHeader('Date') ?: 'now');

        return time() - $date;
    }

    /**
     * Gets the number of seconds from the current time in which a response
     * is still considered fresh.
     *
     * @param ResponseInterface $response
     *
     * @return int|null Returns the number of seconds
     */
    public static function getMaxAge(ResponseInterface $response)
    {
        $parts = AbstractMessage::parseHeader($response, 'Cache-Control');

        if (isset($parts['s-maxage'])) {
            return $parts['s-maxage'];
        } elseif (isset($parts['max-age'])) {
            return $parts['max-age'];
        } elseif ($response->hasHeader('Expires')) {
            return strtotime($response->getHeader('Expires')) - time();
        }

        return null;
    }

    /**
     * Get the freshness of a response by returning the difference of the
     * maximum lifetime of the response and the age of the response.
     *
     * Freshness values less than 0 mean that the response is no longer fresh
     * and is ABS(freshness) seconds expired. Freshness values of greater than
     * zero is the number of seconds until the response is no longer fresh.
     * A NULL result means that no freshness information is available.
     *
     * @param ResponseInterface $response Response to get freshness of
     *
     * @return int|null
     */
    public static function getFreshness(ResponseInterface $response)
    {
        $maxAge = self::getMaxAge($response);
        $age = self::getResponseAge($response);

        return $maxAge && $age ? ($maxAge - $age) : null;
    }

    /**
     * Default function used to determine if a request can be cached.
     *
     * @param RequestInterface $request Request to check
     *
     * @return bool
     */
    public static function canCacheRequest(RequestInterface $request)
    {
        $method = $request->getMethod();

        // Only GET and HEAD requests can be cached
        if ($method !== 'GET' && $method !== 'HEAD') {
            return false;
        }

        // Don't fool with Range requests for now
        if ($request->hasHeader('Range')) {
            return false;
        }

        return self::getDirective($request, 'no-store') === null;
    }

    /**
     * Determines if a response can be cached.
     *
     * @param ResponseInterface $response Response to check
     *
     * @return bool
     */
    public static function canCacheResponse(ResponseInterface $response)
    {
        static $cacheCodes = [200, 203, 300, 301, 410];

        // Check if the response is cacheable based on the code
        if (!in_array((int) $response->getStatusCode(), $cacheCodes)) {
            return false;
        }

        // Make sure a valid body was returned and can be cached
        $body = $response->getBody();
        if ($body && (!$body->isReadable() || !$body->isSeekable())) {
            return false;
        }

        // Never cache no-store resources (this is a private cache, so private
        // can be cached)
        if (self::getDirective($response, 'no-store')) {
            return false;
        }

        // Don't fool with Content-Range requests for now
        if ($response->hasHeader('Content-Range')) {
            return false;
        }

        $freshness = self::getFreshness($response);

        return $freshness === null                    // No freshness info.
            || $freshness >= 0                        // It's fresh
            || $response->hasHeader('ETag')           // Can validate
            || $response->hasHeader('Last-Modified'); // Can validate
    }

    public static function isResponseValid(
        RequestInterface $request,
        ResponseInterface $response
    ) {
        $responseAge = Utils::getResponseAge($response);
        $maxAge = Utils::getDirective($response, 'max-age');

        // Check the request's max-age header against the age of the response
        if ($maxAge !== null && $responseAge > $maxAge) {
            return false;
        }

        // Check the response's max-age header against the freshness level
        $freshness = Utils::getFreshness($response);

        if ($freshness !== null) {
            $maxStale = Utils::getDirective($request, 'max-stale');
            if ($maxStale !== null) {
                if ($freshness < (-1 * $maxStale)) {
                    return false;
                }
            } elseif ($maxAge !== null && $responseAge > $maxAge) {
                return false;
            }
        }

        return true;
    }
}
