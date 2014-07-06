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

use Tmdb\Model\Collection;
use Tmdb\Model\Common\GenericCollection;

/**
 * Class CollectionFactory
 * @package Tmdb\Factory
 */
class CollectionFactory extends AbstractFactory
{
    /**
     * @var MovieFactory
     */
    private $movieFactory;

    /**
     * @var ImageFactory
     */
    private $imageFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->movieFactory = new MovieFactory();
        $this->imageFactory = new ImageFactory();
    }

    /**
     * {@inheritdoc}
     * @return \Tmdb\Model\Collection
     */
    public function create(array $data = array())
    {
        $collection = new Collection();

        if (array_key_exists('parts', $data)) {
            $collection->setParts(
                $this->getMovieFactory()->createCollection($data['parts'])
            );
        }

        if (array_key_exists('backdrop_path', $data)) {
            $collection->setBackdropImage(
                $this->getImageFactory()->createFromPath($data['backdrop_path'], 'backdrop_path')
            );
        }

        if (array_key_exists('images', $data)) {
            $collection->setImages(
                $this->getImageFactory()->createCollectionFromMovie($data['images'])
            );
        }

        if (array_key_exists('poster_path', $data)) {
            $collection->setPosterImage(
                $this->getImageFactory()->createFromPath($data['poster_path'], 'poster_path')
            );
        }

        return $this->hydrate($collection, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function createCollection(array $data = array())
    {
        $collection = new GenericCollection();

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

    /**
     * @param  \Tmdb\Factory\MovieFactory $movieFactory
     * @return $this
     */
    public function setMovieFactory($movieFactory)
    {
        $this->movieFactory = $movieFactory;

        return $this;
    }

    /**
     * @return \Tmdb\Factory\MovieFactory
     */
    public function getMovieFactory()
    {
        return $this->movieFactory;
    }
}
