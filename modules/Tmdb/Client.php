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
namespace Tmdb;

use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Http\ClientInterface;
use Tmdb\HttpClient\HttpClient;
use Tmdb\HttpClient\HttpClientInterface;
use Tmdb\ApiToken as Token;
use Tmdb\HttpClient\Plugin\AcceptJsonHeaderPlugin;
use Tmdb\HttpClient\Plugin\ApiTokenPlugin;
use Tmdb\HttpClient\Plugin\SessionTokenPlugin;

/**
 * Client wrapper for TMDB
 * @package Tmdb
 */
class Client {
    /**
     * Base API URI
     */
    const TMDB_URI = '//api.themoviedb.org/3/';

    /**
     * Insecure schema
     */
    const SCHEME_INSECURE = 'http';

    /**
     * Secure schema
     */
    const SCHEME_SECURE = 'https';

    /**
     * Stores API authentication token
     *
     * @var Token
     */
    private $token;

    /**
     * Stores API user session token
     *
     * @var SessionToken
     */
    private $sessionToken;

    /**
     * Whether the request is supposed to use a secure schema
     *
     * @var bool
     */
    private $secure = false;

    /**
     * Stores the HTTP Client
     *
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * Construct our client
     *
     * @param ClientInterface|null $httpClient
     * @param ApiToken $token
     * @param boolean $secure
     */
    public function __construct(Token $token, ClientInterface $httpClient = null, $secure = false)
    {
        $this->setToken($token);
        $this->setSecure($secure);

        $httpClient = $httpClient ?: new GuzzleClient($this->getBaseUrl());

        if ($httpClient instanceof \Guzzle\Common\HasDispatcherInterface) {
            $apiTokenPlugin = new ApiTokenPlugin($token);
            $httpClient->addSubscriber($apiTokenPlugin);

            $acceptJsonHeaderPlugin = new AcceptJsonHeaderPlugin();
            $httpClient->addSubscriber($acceptJsonHeaderPlugin);
        }

        $this->httpClient = new HttpClient($this->getBaseUrl(), array(), $httpClient);
    }

    /**
     * Add the token subscriber
     *
     * @param Token $token
     * @return $this
     */
    public function setToken(Token $token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return Api\Configuration
     */
    public function getConfigurationApi()
    {
        return new Api\Configuration($this);
    }

    /**
     * @return Api\Authentication
     */
    public function getAuthenticationApi()
    {
        return new Api\Authentication($this);
    }

    /**
     * @return Api\Account
     */
    public function getAccountApi()
    {
        return new Api\Account($this);
    }

    /**
     * @return Api\Collections
     */
    public function getCollectionsApi()
    {
        return new Api\Collections($this);
    }

    /**
     * @return Api\Find
     */
    public function getFindApi()
    {
        return new Api\Find($this);
    }

    /**
     * @return Api\Movies
     */
    public function getMoviesApi()
    {
        return new Api\Movies($this);
    }

    /**
     * @return Api\Tv
     */
    public function getTvApi()
    {
        return new Api\Tv($this);
    }

    /**
     * @return Api\TvSeason
     */
    public function getTvSeasonApi()
    {
        return new Api\TvSeason($this);
    }

    /**
     * @return Api\TvEpisode
     */
    public function getTvEpisodeApi()
    {
        return new Api\TvEpisode($this);
    }

    /**
     * @return Api\People
     */
    public function getPeopleApi()
    {
        return new Api\People($this);
    }

    /**
     * @return Api\Lists
     */
    public function getListsApi()
    {
        return new Api\Lists($this);
    }

    /**
     * @return Api\Companies
     */
    public function getCompaniesApi()
    {
        return new Api\Companies($this);
    }

    /**
     * @return Api\Genres
     */
    public function getGenresApi()
    {
        return new Api\Genres($this);
    }

    /**
     * @return Api\Keywords
     */
    public function getKeywordsApi()
    {
        return new Api\Keywords($this);
    }

    /**
     * @return Api\Discover
     */
    public function getDiscoverApi()
    {
        return new Api\Discover($this);
    }

    /**
     * @return Api\Search
     */
    public function getSearchApi()
    {
        return new Api\Search($this);
    }

    /**
     * @return Api\Reviews
     */
    public function getReviewsApi()
    {
        return new Api\Reviews($this);
    }

    /**
     * @return Api\Changes
     */
    public function getChangesApi()
    {
        return new Api\Changes($this);
    }

    /**
     * @return Api\Jobs
     */
    public function getJobsApi()
    {
        return new Api\Jobs($this);
    }

    /**
     * @return Api\Networks
     */
    public function getNetworksApi()
    {
        return new Api\Networks($this);
    }

    /**
     * @return Api\Credits
     */
    public function getCreditsApi()
    {
        return new Api\Credits($this);
    }

    /**
     * @return Api\Certifications
     */
    public function getCertificationsApi()
    {
        return new Api\Certifications($this);
    }

    /**
     * @return HttpClientInterface
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * @param HttpClientInterface $httpClient
     */
    public function setHttpClient(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Return the base url with preferred schema
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return sprintf(
            '%s:%s',
            $this->getSecure() ? self::SCHEME_SECURE : self::SCHEME_INSECURE,
            self::TMDB_URI
        );
    }

    /**
     * @param boolean $secure
     * @return $this
     */
    public function setSecure($secure)
    {
        $this->secure = $secure;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getSecure()
    {
        return $this->secure;
    }

    /**
     * @param SessionToken $sessionToken
     * @return $this
     */
    public function setSessionToken($sessionToken)
    {
        if ($this->httpClient->getClient() instanceof \Guzzle\Common\HasDispatcherInterface) {
            $sessionTokenPlugin = new SessionTokenPlugin($sessionToken);
            $this->httpClient->getClient()->addSubscriber($sessionTokenPlugin);
        }

        $this->sessionToken = $sessionToken;
        return $this;
    }

    /**
     * @return SessionToken
     */
    public function getSessionToken()
    {
        return $this->sessionToken;
    }
}
