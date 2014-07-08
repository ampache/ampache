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

use Tmdb\Factory\ConfigurationFactory;
use Tmdb\Model\Configuration;

/**
 * Class ConfigurationRepository
 * @package Tmdb\Repository
 * @see http://docs.themoviedb.apiary.io/#configuration
 */
class ConfigurationRepository extends AbstractRepository
{
    /**
     * Load up TMDB Configuration
     *
     * @param  array         $headers
     * @return Configuration
     */
    public function load(array $headers = array())
    {
        $data = $this->getApi()->getConfiguration($headers);

        return $this->getFactory()->create($data);
    }

    /**
     * Return the Movies API Class
     *
     * @return \Tmdb\Api\Configuration
     */
    public function getApi()
    {
        return $this->getClient()->getConfigurationApi();
    }

    /**
     * @return ConfigurationFactory
     */
    public function getFactory()
    {
        return new ConfigurationFactory();
    }
}
