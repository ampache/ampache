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
namespace Tmdb\Factory;

use Tmdb\Model\Change;
use Tmdb\Model\Collection\Changes;

/**
 * Class ChangesFactory
 * @package Tmdb\Factory
 */
class ChangesFactory extends AbstractFactory
{
    /**
     * {@inheritdoc}
     * @return \Tmdb\Model\Change
     */
    public function create(array $data = array())
    {
        return $this->hydrate(new Change(), $data);
    }

    /**
     * {@inheritdoc}
     */
    public function createCollection(array $data = array())
    {
        $collection = new Changes();

        if (array_key_exists('page', $data)) {
            $collection->setPage($data['page']);
        }

        if (array_key_exists('total_pages', $data)) {
            $collection->setTotalPages($data['total_pages']);
        }

        if (array_key_exists('total_results', $data)) {
            $collection->setTotalResults($data['total_results']);
        }

        if (array_key_exists('results', $data)) {
            $data = $data['results'];
        }

        foreach ($data as $item) {
            $collection->add(null, $this->create($item));
        }

        return $collection;
    }
}
