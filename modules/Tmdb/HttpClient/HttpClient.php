<?php
/**
 * This file is part of the Tmdb PHP API created by Michael Roterman.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Tmdb
 * @author Michael Roterman <michael@wtfz.net>
 * @copyright (c) 2013, Michael Roterman
 * @version 0.0.1
 */
namespace Tmdb\HttpClient;

use Guzzle\Http\ClientInterface;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Common\Exception\RuntimeException;
use Guzzle\Log\MessageFormatter;
use Guzzle\Log\PsrLogAdapter;
use Guzzle\Plugin\Backoff\BackoffPlugin;
use Guzzle\Plugin\Cache\CachePlugin;
use Guzzle\Plugin\Cache\DefaultCacheStorage;
use Guzzle\Plugin\Log\LogPlugin;
use Tmdb\ApiToken;
use Tmdb\Exception\TmdbApiException;
use Tmdb\HttpClient\Plugin\AcceptJsonHeaderPlugin;
use Tmdb\HttpClient\Plugin\ApiTokenPlugin;
use Tmdb\HttpClient\Plugin\SessionTokenPlugin;
use Tmdb\SessionToken;

/**
 * Class HttpClient
 * @package Tmdb\HttpClient
 */
class HttpClient implements HttpClientInterface
{
    /**
     * @var \Guzzle\Http\ClientInterface
     */
    private $client;

    protected $options  = array();
    protected $base_url = null;

    /**
     * @var Response
     */
    private $lastResponse;

    /**
     * @var Request
     */
    private $lastRequest;

    /**
     * Constructor
     *
     * @param string          $baseUrl
     * @param array           $options
     * @param ClientInterface $client
     */
    public function __construct($baseUrl, array $options, ClientInterface $client)
    {
        $this->base_url = $baseUrl;
        $this->options  = $options;
        $this->client   = $client;

        $this->registerGuzzleSubscribers($options);
    }

    /**
     * Add a subscriber
     *
     * @param EventSubscriberInterface $subscriber
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->client->addSubscriber($subscriber);
    }

    /**
     * Set the query parameters
     *
     * @param $queryParameters
     * @return array
     */
    protected function buildQueryParameters($queryParameters)
    {
        return array_merge($this->options, array('query' => $queryParameters));
    }

    /**
     * {@inheritDoc}
     */
    public function get($path, array $parameters = array(), array $headers = array())
    {
        $parameters = $this->buildQueryParameters($parameters);

        return $this->request(
            $this->client->get($path, $headers, $parameters)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function head($path, array $parameters = array(), array $headers = array())
    {
        $parameters = $this->buildQueryParameters($parameters);

        return $this->request(
            $this->client->head($path, $headers, $parameters)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function post($path, $postBody, array $parameters = array(), array $headers = array())
    {
        $parameters = $this->buildQueryParameters($parameters);

        return $this->request(
            $this->client->post($path, $headers, $postBody, $parameters)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function postJson($path, $postBody, array $parameters = array(), array $headers = array())
    {
        $parameters = $this->buildQueryParameters($parameters);
        $request    = $this->client->post($path, $headers, null, $parameters);
        $request->setBody($postBody, 'application/json');

        return $this->request($request);
    }

    /**
     * {@inheritDoc}
     */
    public function patch($path, $body = null, array $parameters = array(), array $headers = array())
    {
        $parameters = $this->buildQueryParameters($parameters);

        return $this->request(
            $this->client->patch($path, $headers, $body, $parameters)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function delete($path, $body = null, array $parameters = array(), array $headers = array())
    {
        $parameters = $this->buildQueryParameters($parameters);

        return $this->request(
            $this->client->delete($path, $headers, $body, $parameters)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function put($path, $body = null, array $parameters = array(), array $headers = array())
    {
        $parameters = $this->buildQueryParameters($parameters);

        return $this->request(
            $this->client->put($path, $headers, $body, $parameters)
        );
    }

    /**
     * @{inheritDoc}
     */
    public function request(RequestInterface $request)
    {
        $response = null;

        try {
            $response = $request->send();
        } catch (\Exception $e) {
            $error = $e->getResponse()->json();
            throw new TmdbApiException($error['status_message'], $error['status_code']);
        }

        $this->lastRequest  = $request;
        $this->lastResponse = $response;

        return $response;
    }

    /**
     * @param  \Guzzle\Http\ClientInterface $client
     * @return $this
     */
    public function setClient($client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return \Guzzle\Http\ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param  \Guzzle\Http\Message\Request $lastRequest
     * @return $this
     */
    public function setLastRequest($lastRequest)
    {
        $this->lastRequest = $lastRequest;

        return $this;
    }

    /**
     * @return \Guzzle\Http\Message\Request
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * @param  \Guzzle\Http\Message\Response $lastResponse
     * @return $this
     */
    public function setLastResponse($lastResponse)
    {
        $this->lastResponse = $lastResponse;

        return $this;
    }

    /**
     * @return \Guzzle\Http\Message\Response
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Get the current base url
     *
     * @return null|string
     */
    public function getBaseUrl()
    {
        return $this->getClient()->getBaseUrl();
    }

    /**
     * Set the base url secure / insecure
     *
     * @param $url
     * @return ClientInterface
     */
    public function setBaseUrl($url)
    {
        return $this->getClient()->setBaseUrl($url);
    }

    /**
     * Register the default subscribers for Guzzle
     *
     * @param array $options
     */
    public function registerGuzzleSubscribers(array $options)
    {
        $acceptJsonHeaderPlugin = new AcceptJsonHeaderPlugin();
        $this->addSubscriber($acceptJsonHeaderPlugin);

        $backoffPlugin = BackoffPlugin::getExponentialBackoff(5);
        $this->addSubscriber($backoffPlugin);

        if (array_key_exists('token', $options) && $options['token'] instanceof ApiToken) {
            $apiTokenPlugin = new ApiTokenPlugin($options['token']);
            $this->addSubscriber($apiTokenPlugin);
        }
    }

    /**
     * Add an subscriber to enable caching.
     *
     * @param  array                                     $parameters
     * @throws \Guzzle\Common\Exception\RuntimeException
     */
    public function setCaching(array $parameters = array())
    {
        if (!class_exists('Doctrine\Common\Cache\FilesystemCache')) {
            //@codeCoverageIgnoreStart
            throw new RuntimeException(
                'Could not find the doctrine cache library,
                have you added doctrine-cache to your composer.json?'
            );
            //@codeCoverageIgnoreEnd
        }

        $cachePlugin = new CachePlugin(array(
                'storage' => new DefaultCacheStorage(
                        new DoctrineCacheAdapter(
                            new \Doctrine\Common\Cache\FilesystemCache($parameters['cache_path'])
                        )
                    )
            )
        );

        $this->addSubscriber($cachePlugin);
    }

    /**
     * Add an subscriber to enable logging.
     *
     * @param  array                                     $parameters
     * @throws \Guzzle\Common\Exception\RuntimeException
     */
    public function setLogging(array $parameters = array())
    {
        if (!array_key_exists('logger', $parameters) && !class_exists('\Monolog\Logger')) {
            //@codeCoverageIgnoreStart
            throw new RuntimeException(
                'Could not find any logger set and the monolog logger library was not found
                to provide a default, you have to  set a custom logger on the client or
                have you forgot adding monolog to your composer.json?'
            );
            //@codeCoverageIgnoreEnd
        } else {
            $logger = new \Monolog\Logger('php-tmdb-api');
            $logger->pushHandler(
                new \Monolog\Handler\StreamHandler(
                    $parameters['log_path'],
                    \Monolog\Logger::DEBUG
                )
            );
        }

        if (array_key_exists('logger', $parameters)) {
            $logger = $parameters['logger'];
        }

        $logPlugin = null;

        if ($logger instanceof \Psr\Log\LoggerInterface) {
            $logPlugin = new LogPlugin(
                new PsrLogAdapter($logger),
                MessageFormatter::SHORT_FORMAT
            );
        }

        if (null !== $logPlugin) {
            $this->addSubscriber($logPlugin);
        }
    }

    /**
     * Add an subscriber to append the session_token to the query parameters.
     *
     * @param SessionToken $sessionToken
     */
    public function setSessionToken(SessionToken $sessionToken)
    {
        $sessionTokenPlugin = new SessionTokenPlugin($sessionToken);
        $this->addSubscriber($sessionTokenPlugin);
    }
}
