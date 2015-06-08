=======================
Guzzle Cache Subscriber
=======================

Provides a private transparent proxy cache for caching HTTP responses.

Here's a simple example of how it's used:

.. code-block:: php

    use GuzzleHttp\Client;
    use GuzzleHttp\Subscriber\Cache\CacheSubscriber;

    $client = new Client(['defaults' => ['debug' => true]]);

    // Use the helper method to attach a cache to the client.
    CacheSubscriber::attach($client);

    // Send the first request
    $a = $client->get('http://en.wikipedia.org/wiki/Main_Page');

    // Send the second request. This will find a cache hit which must be
    // validated. The validation request returns a 304, which yields the original
    // cached response.
    $b = $client->get('http://en.wikipedia.org/wiki/Main_Page');

Running the above code sample should output verbose cURL information that looks
something like this:

::

    > GET /wiki/Main_Page HTTP/1.1
    Host: en.wikipedia.org
    User-Agent: Guzzle/4.2.1 curl/7.37.0 PHP/5.5.13
    Via: 1.1 GuzzleCache/4.2.1

    < HTTP/1.1 200 OK
    < Server: Apache
    < X-Content-Type-Options: nosniff
    < Content-language: en
    < X-UA-Compatible: IE=Edge
    < Vary: Accept-Encoding,Cookie
    < Last-Modified: Thu, 21 Aug 2014 01:51:49 GMT
    < Content-Type: text/html; charset=UTF-8
    < X-Varnish: 2345493325, 1998949714 1994269567
    < Via: 1.1 varnish, 1.1 varnish
    < Transfer-Encoding: chunked
    < Date: Thu, 21 Aug 2014 02:34:12 GMT
    < Age: 2541
    < Connection: keep-alive
    < X-Cache: cp1055 hit (1), cp1068 frontend hit (25353)
    < Cache-Control: private, s-maxage=0, max-age=0, must-revalidate
    < Set-Cookie: GeoIP=US:Seattle:47.6062:-122.3321:v4; Path=/; Domain=.wikipedia.org
    <
    * Connection #0 to host en.wikipedia.org left intact
    * Re-using existing connection! (#0) with host en.wikipedia.org
    > GET /wiki/Main_Page HTTP/1.1
    Host: en.wikipedia.org
    User-Agent: Guzzle/4.2.1 curl/7.37.0 PHP/5.5.13
    Via: 1.1 GuzzleCache/4.2.1, 1.1 GuzzleCache/4.2.1
    If-Modified-Since: Thu, 21 Aug 2014 01:51:49 GMT

    < HTTP/1.1 304 Not Modified
    < Server: Apache
    < X-Content-Type-Options: nosniff
    < Content-language: en
    < X-UA-Compatible: IE=Edge
    < Vary: Accept-Encoding,Cookie
    < Last-Modified: Thu, 21 Aug 2014 01:51:49 GMT
    < Content-Type: text/html; charset=UTF-8
    < X-Varnish: 2345493325, 1998950450 1994269567
    < Via: 1.1 varnish, 1.1 varnish
    < Date: Thu, 21 Aug 2014 02:34:12 GMT
    < Age: 2541
    < Connection: keep-alive
    < X-Cache: cp1055 hit (1), cp1068 frontend hit (25360)
    < Cache-Control: private, s-maxage=0, max-age=0, must-revalidate
    < Set-Cookie: GeoIP=US:Seattle:47.6062:-122.3321:v4; Path=/; Domain=.wikipedia.org
    <
    * Connection #0 to host en.wikipedia.org left intact

Installing
----------

Add the following to your composer.json:

.. code-block:: javascript

    {
        "require": {
            "guzzlehttp/cache-subscriber": "0.1.*@dev"
        }
    }

Creating a CacheSubscriber
--------------------------

The easiest way to create a CacheSubscriber is using the ``attach()`` helper
method of ``GuzzleHttp\Subscriber\Cache\CacheSubscriber``. This method accepts
a request or client object and attaches the necessary subscribers used to
perform cache lookups, validation requests, and automatic purging of resources.

The ``attach()`` method accepts the following options:

storage
    A ``GuzzleHttp\Subscriber\Cache\CacheStorageInterface`` object used to
    store cached responses. If no value is not provided, an in-memory array
    cache will be used.
validate
    A Boolean value that determines if cached response are ever validated
    against the origin server. This setting defaults to ``true`` but can be
    disabled by passing ``false``.
purge
    A Boolean value that determines if cached responses are purged when
    non-idempotent requests are sent to their URI. This setting defaults to
    ``true`` but can be disabled by passing ``false``.
can_cache
    An optional callable used to determine if a request can be cached. The
    callable accepts a ``GuzzleHttp\Message\RequestInterface`` and returns a
    Boolean value. If no value is provided, the default behavior is utilized.

.. warning::

    This is a WIP update for Guzzle 4+. It hasn't been tested and is in
    active development. Expect bugs and breaks.
