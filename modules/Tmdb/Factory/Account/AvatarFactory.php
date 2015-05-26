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
namespace Tmdb\Factory\Account;

use Tmdb\Exception\InvalidArgumentException;
use Tmdb\Factory\AbstractFactory;
use Tmdb\Model\Account\Avatar\Gravatar;
use Tmdb\Model\Common\GenericCollection;

/**
 * Class AvatarFactory
 * @package Tmdb\Factory\Account
 */
class AvatarFactory extends AbstractFactory
{
    /**
     * @param array $data
     *
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    public function create(array $data = [])
    {
        foreach ($data as $type => $content) {
            switch ($type) {
                case "gravatar":
                    return $this->hydrate(new Gravatar(), $content);

                default:
                    throw new InvalidArgumentException(sprintf(
                        'The avatar type "%s" has not been defined in the factory "%s".',
                        $type,
                        __CLASS__
                    ));
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function createCollection(array $data = [])
    {
        $collection = new GenericCollection();
        $collection->add(null, $this->create($data));

        return $collection;
    }
}
