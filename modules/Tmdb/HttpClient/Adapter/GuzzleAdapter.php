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
namespace Tmdb\HttpClient\Adapter;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Subscriber\Retry\RetrySubscriber;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tmdb\Common\ParameterBag;
use Tmdb\Exception\NullResponseException;
use Tmdb\HttpClient\Request;
use Tmdb\HttpClient\Response;

class GuzzleAdapter extends AbstractAdapter
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var Request
     */
    protected $request;

    public function __construct(ClientInterface $client = null, array $options = [])
    {
        if (null === $client) {
            $client = new Client($options);
        }

        $this->client = $client;
    }

    /**
     * {@inheritDoc}
     */
    public function registerSubscribers(EventDispatcherInterface $eventDispatcher)
    {
        // Retry 500 and 503 responses that were sent as GET and HEAD requests.
        $filter = RetrySubscriber::createChainFilter([
            // Does early filter to force non-idempotent methods to NOT be retried.
            RetrySubscriber::createIdempotentFilter(),
            // Performs the last check, returning ``true`` or ``false`` based on
            // if the response received a 500 or 503 status code.
            RetrySubscriber::createStatusFilter([500, 503])
        ]);

        $retry = new RetrySubscriber(['filter' => $filter]);
        $this->client->getEmitter()->attach($retry);
    }

    /**
     * Format the request for Guzzle
     *
     * @param  Request $request
     * @return array
     */
    public function getConfiguration(Request $request)
    {
        $this->request = $request;

        return [
            'headers'  => $request->getHeaders()->all(),
            'query'    => $request->getParameters()->all()
        ];
    }

    /**
     * Create the response object
     *
     * @param  ResponseInterface         $adapterResponse
     * @return \Tmdb\HttpClient\Response
     */
    private function createResponse(ResponseInterface $adapterResponse = null)
    {
        $response = new Response();

        if ($adapterResponse !== null) {
            $response->setCode($adapterResponse->getStatusCode());
            $response->setHeaders(new ParameterBag($adapterResponse->getHeaders()));
            $response->setBody((string) $adapterResponse->getBody());
        }

        return $response;
    }

    /**
     * Create the request exception
     *
     * @param  Request                          $request
     * @param  RequestException|null            $previousException
     * @throws \Tmdb\Exception\TmdbApiException
     */
    protected function handleRequestException(Request $request, RequestException $previousException)
    {
        if (null !== $previousException && null == $previousException->getResponse()) {
            throw new NullResponseException($this->request, $previousException);
        }

        throw $this->createApiException(
            $request,
            $this->createResponse($previousException->getResponse()),
            $previousException
        );
    }

    /**
     * {@inheritDoc}
     */
    public function get(Request $request)
    {
        $response = null;

        try {
            $response = $this->client->get(
                $request->getPath(),
                $this->getConfiguration($request)
            );
        } catch (RequestException $e) {
            $this->handleRequestException($request, $e);
        }

        return $this->createResponse($response);
    }

    /**
     * {@inheritDoc}
     */
    public function post(Request $request)
    {
        $response = null;

        try {
            $response = $this->client->post(
                $request->getPath(),
                array_merge(
                    ['body' => $request->getBody()],
                    $this->getConfiguration($request)
                )
            );
        } catch (RequestException $e) {
            $this->handleRequestException($request, $e);
        }

        return $this->createResponse($response);
    }

    /**
     * {@inheritDoc}
     */
    public function put(Request $request)
    {
        $response = null;

        try {
            $response = $this->client->put(
                $request->getPath(),
                array_merge(
                    ['body' => $request->getBody()],
                    $this->getConfiguration($request)
                )
            );
        } catch (RequestException $e) {
            $this->handleRequestException($request, $e);
        }

        return $this->createResponse($response);
    }

    /**
     * {@inheritDoc}
     */
    public function patch(Request $request)
    {
        $response = null;

        try {
            $response = $this->client->patch(
                $request->getPath(),
                array_merge(
                    ['body' => $request->getBody()],
                    $this->getConfiguration($request)
                )
            );
        } catch (RequestException $e) {
            $this->handleRequestException($request, $e);
        }

        return $this->createResponse($response);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Request $request)
    {
        $response = null;

        try {
            $response = $this->client->delete(
                $request->getPath(),
                array_merge(
                    ['body' => $request->getBody()],
                    $this->getConfiguration($request)
                )
            );
        } catch (RequestException $e) {
            $this->handleRequestException($request, $e);
        }

        return $this->createResponse($response);
    }

    /**
     * {@inheritDoc}
     */
    public function head(Request $request)
    {
        $response = null;

        try {
            $response = $this->client->head(
                $request->getPath(),
                $this->getConfiguration($request)
            );
        } catch (RequestException $e) {
            $this->handleRequestException($request, $e);
        }

        return $this->createResponse($response);
    }

    /**
     * Retrieve the Guzzle Client
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param  ClientInterface $client
     * @return $this
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;

        return $this;
    }
}
