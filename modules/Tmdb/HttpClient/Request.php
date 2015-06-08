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
use Tmdb\Common\ParameterBag;

/**
 * Class Request
 * @package Tmdb\HttpClient
 */
class Request
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $method;

    /**
     * @var ParameterBag
     */
    private $parameters;

    /**
     * @var ParameterBag
     */
    private $headers;

    /**
     * @var ParameterBag
     */
    private $options;

    /**
     * @var null|string
     */
    private $body;

    /**
     * @param string       $path
     * @param string       $method
     * @param ParameterBag $parameters
     * @param ParameterBag $headers
     * @param string       $body
     * @param ParameterBag $options
     */
    public function __construct(
        $path = '/',
        $method = 'GET',
        ParameterBag $parameters = null,
        ParameterBag $headers = null,
        $body = null,
        ParameterBag $options = null
    ) {
        if (!$parameters) {
            $parameters = new ParameterBag();
        }

        if (!$headers) {
            $headers = new ParameterBag();
        }

        if (!$options) {
            $options = new ParameterBag();
        }

        $this->path       = $path;
        $this->method     = $method;
        $this->parameters = is_array($parameters) ? new ParameterBag($parameters) : $parameters;
        $this->headers    = is_array($headers) ? new ParameterBag($headers) : $headers;
        $this->body       = $body;
        $this->options    = is_array($options) ? new ParameterBag($options) : $options;
    }

    /**
     * @return ParameterBag
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param  ParameterBag $headers
     * @return $this
     */
    public function setHeaders(ParameterBag $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param  string $method
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return ParameterBag
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param  array|ParameterBag $parameters
     * @return $this
     */
    public function setParameters(ParameterBag $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param  string $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return ParameterBag
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param  ParameterBag $options
     * @return $this
     */
    public function setOptions($options)
    {
        if (is_array($options)) {
            $options = new ParameterBag($options);
        }

        $this->options = $options;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param  null|string $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }
}
