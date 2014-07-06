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
namespace Tmdb\Api;

use Guzzle\Http\Message\Response;
use Tmdb\Client;

/**
 * Class AbstractApi
 * @package Tmdb\Api
 */
abstract class AbstractApi implements ApiInterface
{
    /**
     * The client
     *
     * @var Client
     */
    protected $client;

    /**
     * Constructor
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Send a GET request
     *
     * @param  string $path
     * @param  array  $parameters
     * @param  array  $headers
     * @return mixed
     */
    public function get($path, array $parameters = array(), $headers = array())
    {
        /**
         * @var Response $response
         */
        $response = $this->client->getHttpClient()->get($path, $parameters, $headers);

        return $response->json();
    }

    /**
     * Send a HEAD request
     *
     * @param $path
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function head($path, array $parameters = array(), $headers = array())
    {
        /**
         * @var Response $response
         */
        $response = $this->client->getHttpClient()->head($path, $parameters, $headers);

        return $response->json();
    }

    /**
     * Send a POST request
     *
     * @param $path
     * @param  null  $postBody
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function post($path, $postBody = null, array $parameters = array(), $headers = array())
    {
        /**
         * @var Response $response
         */
        $response = $this->client->getHttpClient()->post($path, $postBody, $parameters, $headers);

        return $response->json();
    }

    /**
     * Send a POST request but json_encode the post body in the request
     *
     * @param $path
     * @param  null  $postBody
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function postJson($path, $postBody = null, array $parameters = array(), $headers = array())
    {
        /**
         * @var Response $response
         */
        if (is_array($postBody)) {
            $postBody = json_encode($postBody);
        }

        $response = $this->client->getHttpClient()->postJson($path, $postBody, $parameters, $headers);

        return $response->json();
    }

    /**
     * Send a PUT request
     *
     * @param $path
     * @param  null  $body
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function put($path, $body = null, array $parameters = array(), $headers = array())
    {
        /**
         * @var Response $response
         */
        $response = $this->client->getHttpClient()->put($path, $body, $parameters, $headers);

        return $response->json();
    }

    /**
     * Send a DELETE request
     *
     * @param $path
     * @param  null  $body
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function delete($path, $body = null, array $parameters = array(), $headers = array())
    {
        /**
         * @var Response $response
         */
        $response = $this->client->getHttpClient()->delete($path, $body, $parameters, $headers);

        return $response->json();
    }

    /**
     * Send a PATCH request
     *
     * @param $path
     * @param  null  $body
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function patch($path, $body = null, array $parameters = array(), $headers = array())
    {
        /**
         * @var Response $response
         */
        $response = $this->client->getHttpClient()->patch($path, $body, $parameters, $headers);

        return $response->json();
    }
}
