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

use Tmdb\Factory\NetworkFactory;
use Tmdb\Model\Network;

/**
 * Class NetworkRepository
 * @package Tmdb\Repository
 * @see http://docs.themoviedb.apiary.io/#networks
 */
class NetworkRepository extends AbstractRepository
{
    /**
     * This method is used to retrieve the basic information about a TV network.
     *
     * You can use this ID to search for TV shows with the discover.
     * At this time we don't have much but this will be fleshed out over time.
     *
     * @param $id
     * @param  array   $parameters
     * @param  array   $headers
     * @return Network
     */
    public function load($id, array $parameters = array(), array $headers = array())
    {
        return $this->getFactory()->create(
            $this->getApi()->getNetwork($id, $parameters, $headers)
        );
    }

    /**
     * Return the related API class
     *
     * @return \Tmdb\Api\Networks
     */
    public function getApi()
    {
        return $this->getClient()->getNetworksApi();
    }

    /**
     * @return NetworkFactory
     */
    public function getFactory()
    {
        return new NetworkFactory();
    }
}
