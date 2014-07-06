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
namespace Tmdb\Factory\Lists;

use Tmdb\Factory\AbstractFactory;
use Tmdb\Factory\ImageFactory;
use Tmdb\Model\Common\GenericCollection;
use Tmdb\Model\Lists\ListItem;

/**
 * Class ListItemFactory
 * @package Tmdb\Factory\Lists
 */
class ListItemFactory extends AbstractFactory
{
    /**
     * @var ImageFactory
     */
    private $imageFactory;

    public function __construct()
    {
        $this->imageFactory = new ImageFactory();
    }

    /**
     * @param array $data
     *
     * @return ListItem
     */
    public function create(array $data = array())
    {
        $listItem = new ListItem();

        /** Images */
        if (array_key_exists('backdrop_path', $data)) {
            $listItem->setBackdropImage(
                $this->getImageFactory()->createFromPath($data['backdrop_path'], 'backdrop_path')
            );
        }

        if (array_key_exists('poster_path', $data)) {
            $listItem->setPosterImage(
                $this->getImageFactory()->createFromPath($data['poster_path'], 'poster_path')
            );
        }

        return $this->hydrate($listItem, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function createCollection(array $data = array())
    {
        $collection = new GenericCollection();

        if (array_key_exists('items', $data)) {
            $data = $data['items'];
        }

        foreach ($data as $item) {
            $collection->add(null, $this->create($item));
        }

        return $collection;
    }

    /**
     * @param  \Tmdb\Factory\ImageFactory $imageFactory
     * @return $this
     */
    public function setImageFactory($imageFactory)
    {
        $this->imageFactory = $imageFactory;

        return $this;
    }

    /**
     * @return \Tmdb\Factory\ImageFactory
     */
    public function getImageFactory()
    {
        return $this->imageFactory;
    }
}
