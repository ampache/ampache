<?php

/**
 * Simple PHP EchoNest API
 *
 * @tutorial  http://EchoNest.com/ornicar/php-EchoNest-api/blob/master/README.markdown
 * @version   2.6
 * @author    Brent Shaffer <bshafs at gmail dot com>
 * @license   MIT License
 *
 * Website: http://EchoNest.com/ornicar/php-EchoNest-api
 * Tickets: http://EchoNest.com/ornicar/php-EchoNest-api/issues
 */
class EchoNest_Client
{
    /**
     * The request instance used to communicate with EchoNest
     * @var EchoNest_HttpClient
     */
    protected $httpClient  = null;

    /**
     * The list of loaded API instances
     * @var array
     */
    protected $apis     = array();

    /**
     * Use debug mode (prints debug messages)
     * @var bool
     */
    protected $debug;

    /**
     * Instanciate a new EchoNest client
     *
     * @param  EchoNest_HttpClient_Interface $httpClient custom http client
     */
    public function __construct(EchoNest_HttpClientInterface $httpClient = null)
    {
        if (null === $httpClient) {
            $this->httpClient = new EchoNest_HttpClient_Curl();
        } else {
            $this->httpClient = $httpClient;
        }
    }

    /**
     * Authenticate a user for all next requests
     *
     * @param  string         $apiKey      EchoNest API key
     * @return EchoNestApi               fluent interface
     */
    public function authenticate($apiKey)
    {
        $this->getHttpClient()
            ->setOption('api_key', $apiKey);

        return $this;
    }

    /**
     * Deauthenticate a user for all next requests
     *
     * @return EchoNestApi               fluent interface
     */
    public function deAuthenticate()
    {
        return $this->authenticate(null);
    }

    /**
     * Call any route, GET method
     * Ex: $api->get('repos/show/my-username/my-repo')
     *
     * @param   string  $route            the EchoNest route
     * @param   array   $parameters       GET parameters
     * @param   array   $requestOptions   reconfigure the request
     * @return  array                     data returned
     */
    public function get($route, array $parameters = array(), $requestOptions = array())
    {
        return $this->getHttpClient()->get($route, $parameters, $requestOptions);
    }

    /**
     * Call any route, POST method
     * Ex: $api->post('repos/show/my-username', array('email' => 'my-new-email@provider.org'))
     *
     * @param   string  $route            the EchoNest route
     * @param   array   $parameters       POST parameters
     * @param   array   $requestOptions   reconfigure the request
     * @return  array                     data returned
     */
    public function post($route, array $parameters = array(), $requestOptions = array())
    {
        return $this->getHttpClient()->post($route, $parameters, $requestOptions);
    }

    /**
     * Get the httpClient
     *
     * @return  EchoNest_HttpClient_Interface   an httpClient instance
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * Inject another request
     *
     * @param   EchoNest_HttpClient_Interface   a httpClient instance
     * @return  EchoNestApi          fluent interface
     */
    public function setHttpClient(EchoNest_HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    /**
     * Get the artist API
     *
     * @return  EchoNest_Api_Artist    the artist API
     */
    public function getArtistApi($options = array())
    {
        if(!isset($this->apis['artist']))
        {
            $this->apis['artist'] = new EchoNest_Api_Artist($this, $options);
        }

        return $this->apis['artist'];
    }

    /**
     * Get the song API
     *
     * @return  EchoNest_Api_Song   the song API
     */
    public function getSongApi($options = array())
    {
        if(!isset($this->apis['song']))
        {
            $this->apis['song'] = new EchoNest_Api_Song($this, $options);
        }

        return $this->apis['song'];
    }

    /**
     * Get the track API
     *
     * @return  EchoNestApiTrack  the track API
     */
    public function getTrackApi($options = array())
    {
        if(!isset($this->apis['track']))
        {
            $this->apis['track'] = new EchoNest_Api_Track($this, $options);
        }

        return $this->apis['track'];
    }

    /**
     * Get the playlist API
     *
     * @return  EchoNestApiPlaylist  the playlist API
     */
    public function getPlaylistApi($options = array())
    {
        if(!isset($this->apis['playlist']))
        {
            $this->apis['playlist'] = new EchoNest_Api_Playlist($this, $options);
        }

        return $this->apis['playlist'];
    }

    /**
     * Get the catalog API
     *
     * @return  EchoNest_Api_Catalog  the catalog API
     */
    public function getCatalogApi($options = array())
    {
        if(!isset($this->apis['catalog']))
        {
            $this->apis['catalog'] = new EchoNest_Api_Catalog($this, $options);
        }

        return $this->apis['catalog'];
    }

    /**
     * Get the sandbox API
     *
     * @return  EchoNest_Api_Sandbox  the sandbox API
     */
    public function getSandboxApi($options = array())
    {
        if(!isset($this->apis['sandbox']))
        {
            $this->apis['sandbox'] = new EchoNest_Api_Sandbox($this, $options);
        }

        return $this->apis['sandbox'];
    }

    /**
     * Inject another API instance
     *
     * @param   string                $name the API name
     * @param   EchoNestApiAbstract   $api  the API instance
     * @return  EchoNest_Client       fluent interface
     */
    public function setApi($name, EchoNest_ApiInterface $instance)
    {
        $this->apis[$name] = $instance;

        return $this;
    }

    /**
     * Get any API
     *
     * @param   string                    $name the API name
     * @return  EchoNest_Api_Abstract     the API instance
     */
    public function getApi($name)
    {
        return $this->apis[$name];
    }
}
