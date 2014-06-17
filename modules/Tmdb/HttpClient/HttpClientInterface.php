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

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;

/**
 * Interface HttpClientInterface
 * @package Tmdb\HttpClient
 */
interface HttpClientInterface
{
    /**
     * Compose a GET request
     *
     * @param  string $path       Request path
     * @param  array  $parameters GET Parameters
     * @param  array  $headers    Reconfigure the request headers for this call only
     *
     * @return Response           Data
     */
    public function get($path, array $parameters = array(), array $headers = array());

    /**
     * Compose a POST request
     *
     * @param  string $path       Request path
     * @param  string $postBody   The post BODY
     * @param  array  $parameters POST Parameters
     * @param  array  $headers    Reconfigure the request headers for this call only
     *
     * @return Response              Data
     */
    public function post($path, $postBody, array $parameters = array(), array $headers = array());

    /**
     * Compose a POST request but json_encode the body
     *
     * @param  string $path       Request path
     * @param  array  $postBody   The post BODY
     * @param  array  $parameters POST Parameters
     * @param  array  $headers    Reconfigure the request headers for this call only
     *
     * @return Response              Data
     */
    public function postJson($path, $postBody, array $parameters = array(), array $headers = array());

    /**
     * Compose a PATCH request
     *
     * @param  string $path       Request path
     * @param  string $body       The body
     * @param  array  $parameters PATCH Parameters
     * @param  array  $headers    Reconfigure the request headers for this call only
     *
     * @return Response           Data
     */
    public function patch($path, $body = null, array $parameters = array(), array $headers = array());

    /**
     * Compose a PUT request
     *
     * @param  string $path       Request path
     * @param  string $body       The body
     * @param  array  $parameters PUT Parameters
     * @param  array  $headers    Reconfigure the request headers for this call only
     *
     * @return Response           Data
     */
    public function put($path, $body = null, array $parameters = array(), array $headers = array());

    /**
     * Compose a DELETE request
     *
     * @param  string $path       Request path
     * @param  string $body       The body
     * @param  array  $parameters DELETE Parameters
     * @param  array  $headers    Reconfigure the request headers for this call only
     *
     * @return Response           Data
     */
    public function delete($path, $body = null, array $parameters = array(), array $headers = array());

    /**
     * Send a request to the server, receive a response,
     * decode the response and returns an associative array
     * @param  $request RequestInterface
     * @return Response
     */
    public function request(RequestInterface $request);

}
