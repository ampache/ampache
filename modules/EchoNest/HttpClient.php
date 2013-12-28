<?php

/**
 * Performs requests on EchoNest API. API documentation should be self-explanatory.
 *
 * @author    Brent Shaffer <bshafs at gmail dot com>
 * @license   MIT License
 */
abstract class EchoNest_HttpClient implements EchoNest_HttpClientInterface
{
    /**
     * The request options
     * @var array
     */
    protected $options = array(
        'protocol'    => 'http',
        'api_version' => 'v4',
        'url'         => ':protocol://developer.echonest.com/api/:api_version/:path',
        'user_agent'  => 'php-echonest-api (http://github.com/bshaffer/php-echonest-api)',
        'http_port'   => 80,
        'timeout'     => 20,
        'api_key'     => null,
        'format'      => 'json',
        'limit'       => false,
        'debug'       => false
    );

    /**
     * Instanciate a new request
     *
     * @param  array   $options  Request options
     */
    public function __construct(array $options = array())
    {
        $this->configure($options);
    }

    /**
     * Configure the request
     *
     * @param   array               $options  Request options
     * @return  EchoNestApiRequest $this     Fluent interface
     */
    public function configure(array $options)
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Send a request to the server, receive a response
     *
     * @param  string   $url           Request url
     * @param  array    $parameters    Parameters
     * @param  string   $httpMethod    HTTP method to use
     * @param  array    $options        Request options
     *
     * @return string   HTTP response
     */
    abstract protected function doRequest($url, array $parameters = array(), $httpMethod = 'GET', array $options = array());

    /**
     * Send a GET request
     * @see send
     */
    public function get($path, array $parameters = array(), array $options = array())
    {
        return $this->request($path, $parameters, 'GET', $options);
    }

    /**
     * Send a POST request
     * @see send
     */
    public function post($path, array $parameters = array(), array $options = array())
    {
        return $this->request($path, $parameters, 'POST', $options);
    }

    /**
     * Send a request to the server, receive a response,
     * decode the response and returns an associative array
     *
     * @param  string   $path            Request API path
     * @param  array    $parameters     Parameters
     * @param  string   $httpMethod     HTTP method to use
     * @param  array    $options        Request options
     *
     * @return array                    Data
     */
    public function request($path, array $parameters = array(), $httpMethod = 'GET', array $options = array())
    {
        $options = array_merge($this->options, $options);

        // create full url
        $url = strtr($options['url'], array(
          ':api_version' => $this->options['api_version'],
          ':protocol'    => $this->options['protocol'],
          ':path'        => trim($path, '/')
        ));

        // get encoded response
        $response = $this->doRequest($url, $parameters, $httpMethod, $options);

        // decode response
        $response = $this->decodeResponse($response, $options);

        if (isset($response['response']['status']['code']) && !in_array($response['response']['status']['code'], array(0, 200, 201))) {
            throw new EchoNest_HttpClient_Exception($response['response']['status']['message'], (int) $response['response']['status']['code']);
        }

        return $response['response'];
    }

    /**
     * Change an option value.
     *
     * @param string $name   The option name
     * @param mixed  $value  The value
     *
     * @return dmConfigurable The current object instance
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Get an option value.
     *
     * @param  string $name The option name
     *
     * @return mixed  The option value
     */
    public function getOption($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }


    /**
     * Get a JSON response and transform it to a PHP array
     *
     * @return  array   the response
     */
    protected function decodeResponse($response)
    {
        switch ($this->options['format'])
        {
            case 'json':
                return json_decode($response, true);

            case 'jsonp':
                throw new LogicException("format 'jsonp' not yet supported by this library");

            case 'xml':
                throw new LogicException("format 'xml' not yet supported by this library");

            case 'xspf':
                throw new LogicException("format 'xspf' not yet supported by this library");
        }

        throw new LogicException(__CLASS__.' only supports json, json, xml, and xspf formats, '.$this->options['format'].' given.');
    }
}