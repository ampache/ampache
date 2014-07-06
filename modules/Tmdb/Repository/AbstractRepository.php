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
namespace Tmdb\Repository;

use Tmdb\Api\ApiInterface;
use Tmdb\Client;
use Tmdb\Factory\FactoryInterface;
use Tmdb\Model\Common\QueryParameter\QueryParameterInterface;

/**
 * Class AbstractRepository
 * @package Tmdb\Repository
 */
abstract class AbstractRepository
{
    protected $client  = null;
    protected $api     = null;

    /**
     * Constructor
     *
     *
     * @todo create an interface for the client
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client  = $client;
    }

    /**
     * Return the client
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Process query parameters
     *
     * @param  array $parameters
     * @return array
     */
    protected function parseQueryParameters(array $parameters = array())
    {
        foreach ($parameters as $key => $candidate) {
            if (is_a($candidate, 'Tmdb\Model\Common\QueryParameter\QueryParameterInterface')) {
                $interfaces = class_implements($candidate);

                if (array_key_exists('Tmdb\Model\Common\QueryParameter\QueryParameterInterface', $interfaces)) {
                    unset($parameters[$key]);

                    $parameters[$candidate->getKey()] = $candidate->getValue();
                }
            }
        }

        return $parameters;
    }

    /**
     * Return the API Class
     *
     * @return ApiInterface
     */
    abstract public function getApi();

    /**
     * Return the Factory Class
     *
     * @return FactoryInterface
     */
    abstract public function getFactory();
}
