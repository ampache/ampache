<?php

/**
 * Abstract class for EchoNest_Api classes
 *
 * @author    Brent Shaffer <bshafs at gmail dot com>
 * @license   MIT License
 */
abstract class EchoNest_Api implements EchoNest_ApiInterface
{
    /**
    * The core EchoNest Client
    * @var EchoNest_Client
    */
    protected
        $client,
        $options = array();

    public function __construct(EchoNest_Client $client, $options = array())
    {
        $this->client  = $client;
        $this->options = $options;
    }

    /**
     * Call any path, GET method
     * Ex: $api->get('artist/biographies', array('name' => 'More Hazards More Heroes'))
     *
     * @param   string  $path             the EchoNest path
     * @param   array   $parameters       GET parameters
     * @param   array   $requestOptions   reconfigure the request
     * @return  array                     data returned
     */
    protected function get($path, array $parameters = array(), $requestOptions = array())
    {
        return $this->client->get($path, $parameters, $requestOptions);
    }

    /**
     * Call any path, POST method
     * Ex: $api->post('catalog/create', array('type' => 'artist', 'name' => 'My Catalog'))
     *
     * @param   string  $path             the EchoNest path
     * @param   array   $parameters       POST parameters
     * @param   array   $requestOptions   reconfigure the request
     * @return  array                     data returned
     */
    protected function post($path, array $parameters = array(), $requestOptions = array())
    {
        return $this->client->post($path, $parameters, $requestOptions);
    }

    /**
    * Change an option value.
    *
    * @param string $name   The option name
    * @param mixed  $value  The value
    *
    * @return EchoNestApiAbstract the current object instance
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

    protected function returnResponse($response, $key = null)
    {
        if (!is_null($key) && !$this->getOption('raw')) {
            return $response[$key];
        }

        return $response;
    }
}
