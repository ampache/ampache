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
namespace Tmdb\Common;

use Tmdb\Exception\RuntimeException;
use Tmdb\Model\AbstractModel;

/**
 * Utilisation class to hydrate objects.
 *
 * Class ObjectHydrator
 * @package Tmdb\Common
 */
class ObjectHydrator
{
    /**
     * Hydrate the object with data
     *
     * @param  AbstractModel    $object
     * @param  array            $data
     * @return AbstractModel
     * @throws RuntimeException
     */
    public function hydrate(AbstractModel $object, $data = array())
    {
        if (!empty($data)) {
            foreach ($data as $k => $v) {

                if (in_array($k, $object::$properties)) {

                    $method = $this->camelize(
                        sprintf('set_%s', $k)
                    );

                    if (!is_callable(array($object, $method))) {
                        throw new RuntimeException(sprintf(
                            'Trying to call method "%s" on "%s" but it does not exist or is private.',
                            $method,
                            get_class($object)
                        ));
                    } else {
                        $object->$method($v);
                    }
                }
            }
        }

        return $object;
    }

    /**
     * Transforms an under_scored_string to a camelCasedOne
     *
     * @see https://gist.github.com/troelskn/751517
     *
     * @param  string $candidate
     * @return string
     */
    public function camelize($candidate)
    {
        return lcfirst(
            implode(
                '',
                array_map(
                    'ucfirst',
                    array_map(
                        'strtolower',
                        explode(
                            '_',
                            $candidate
                        )
                    )
                )
            )
        );
    }
}
