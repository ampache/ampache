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
namespace Tmdb\Factory\Movie;

use Tmdb\Factory\AbstractFactory;
use Tmdb\Factory\ImageFactory;
use Tmdb\Model\Movie\ListItem;

/**
 * Class ListItemFactory
 * @package Tmdb\Factory\Movie
 */
class ListItemFactory extends AbstractFactory
{
    private $imageFactory;

    public function __construct()
    {
        $this->imageFactory = new ImageFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data = array())
    {
        $listItem = new ListItem();

        if (array_key_exists('poster_path', $data)) {
            $listItem->setPosterImage($this->getImageFactory()->createFromPath($data['poster_path'], 'poster_path'));
        }

        return $this->hydrate($listItem, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function createCollection(array $data = array())
    {
        return $this->createResultCollection($data);
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
