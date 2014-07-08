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
namespace Tmdb\Factory\Common;

use Tmdb\Common\ObjectHydrator;
use Tmdb\Model\Common\GenericCollection;

/**
 * @deprecated
 *
 * Class GenericCollectionFactory
 * @package Tmdb\Factory\Common
 */
class GenericCollectionFactory
{
    /**
     * @param  array             $data
     * @param $class
     * @return GenericCollection
     */
    public function create(array $data, $class)
    {
        return $this->createCollection($data, $class);
    }

    /**
     * @param  array             $data
     * @param $class
     * @return GenericCollection
     */
    public function createCollection(array $data, $class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $collection     = new GenericCollection();
        $objectHydrator = new ObjectHydrator();

        foreach ($data as $item) {
            $collection->add(null, $objectHydrator->hydrate(new $class(), $item));
        }

        return $collection;
    }
}
