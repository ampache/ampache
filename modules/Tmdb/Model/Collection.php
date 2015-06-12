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
namespace Tmdb\Model;

use Tmdb\Model\Collection\Images;
use Tmdb\Model\Common\GenericCollection;
use Tmdb\Model\Image\BackdropImage;
use Tmdb\Model\Image\PosterImage;

/**
 * Class Collection
 * @package Tmdb\Model
 */
class Collection extends AbstractModel
{
    /**
     * @var string
     */
    private $backdropPath;

    /**
     * @var BackdropImage
     */
    private $backdrop;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var Images
     */
    private $images;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $overview;

    /**
     * @var Common\GenericCollection
     */
    private $parts;

    /**
     * @var string
     */
    private $posterPath;

    /**
     * @var PosterImage
     */
    private $poster;

    public static $properties = array(
        'backdrop_path',
        'id',
        'name',
        'overview',
        'poster_path',
    );

    public function __construct()
    {
        $this->parts  = new GenericCollection();
        $this->images = new Images();
    }

    /**
     * @param  \Tmdb\Model\Image\BackdropImage $backdrop
     * @return $this
     */
    public function setBackdropImage(BackdropImage $backdrop)
    {
        $this->backdrop = $backdrop;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Image\BackdropImage
     */
    public function getBackdropImage()
    {
        return $this->backdrop;
    }

    /**
     * @param  string $backdropPath
     * @return $this
     */
    public function setBackdropPath($backdropPath)
    {
        $this->backdropPath = $backdropPath;

        return $this;
    }

    /**
     * @return string
     */
    public function getBackdropPath()
    {
        return $this->backdropPath;
    }

    /**
     * @param  int   $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  \Tmdb\Model\Collection\Images $images
     * @return $this
     */
    public function setImages(Images $images)
    {
        $this->images = $images;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Collection\Images
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param  string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param  string $overview
     * @return $this
     */
    public function setOverview($overview)
    {
        $this->overview = $overview;

        return $this;
    }

    /**
     * @return string
     */
    public function getOverview()
    {
        return $this->overview;
    }

    /**
     * @param  GenericCollection $parts
     * @return $this
     */
    public function setParts($parts)
    {
        $this->parts = $parts;

        return $this;
    }

    /**
     * @return GenericCollection
     */
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * @param  \Tmdb\Model\Image\PosterImage $poster
     * @return $this
     */
    public function setPosterImage(PosterImage $poster)
    {
        $this->poster = $poster;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Image\PosterImage
     */
    public function getPosterImage()
    {
        return $this->poster;
    }

    /**
     * @param  string $posterPath
     * @return $this
     */
    public function setPosterPath($posterPath)
    {
        $this->posterPath = $posterPath;

        return $this;
    }

    /**
     * @return string
     */
    public function getPosterPath()
    {
        return $this->posterPath;
    }
}
