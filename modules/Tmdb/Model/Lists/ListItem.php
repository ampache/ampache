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
namespace Tmdb\Model\Lists;

use Tmdb\Model\AbstractModel;
use Tmdb\Model\Image\BackdropImage;
use Tmdb\Model\Image\PosterImage;

/**
 * Class ListItem
 * @package Tmdb\Model\Lists
 */
class ListItem extends AbstractModel
{
    /**
     * @var string
     */
    private $backdropPath;

    /**
     * @var BackdropImage
     */
    private $backdropImage;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $originalTitle;

    /**
     * @var \DateTime
     */
    private $releaseDate;

    /**
     * @var string
     */
    private $posterPath;

    /**
     * @var PosterImage
     */
    private $posterImage;

    /**
     * @var string
     */
    private $title;

    /**
     * @var float
     */
    private $voteAverage;

    /**
     * @var int
     */
    private $voteCount;

    /**
     * @var array
     */
    public static $properties = array(
        'backdrop_path',
        'id',
        'original_title',
        'release_date',
        'poster_path',
        'title',
        'vote_average',
        'vote_count'
    );

    /**
     * @param  \Tmdb\Model\Image\BackdropImage $backdropImage
     * @return $this
     */
    public function setBackdropImage($backdropImage)
    {
        $this->backdropImage = $backdropImage;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Image\BackdropImage
     */
    public function getBackdropImage()
    {
        return $this->backdropImage;
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
        $this->id = $id;

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
     * @param  string $originalTitle
     * @return $this
     */
    public function setOriginalTitle($originalTitle)
    {
        $this->originalTitle = $originalTitle;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalTitle()
    {
        return $this->originalTitle;
    }

    /**
     * @param  \Tmdb\Model\Image\PosterImage $posterImage
     * @return $this
     */
    public function setPosterImage($posterImage)
    {
        $this->posterImage = $posterImage;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Image\PosterImage
     */
    public function getPosterImage()
    {
        return $this->posterImage;
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

    /**
     * @param  \DateTime $releaseDate
     * @return $this
     */
    public function setReleaseDate($releaseDate)
    {
        $this->releaseDate = $releaseDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getReleaseDate()
    {
        return $this->releaseDate;
    }

    /**
     * @param  string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param  float $voteAverage
     * @return $this
     */
    public function setVoteAverage($voteAverage)
    {
        $this->voteAverage = $voteAverage;

        return $this;
    }

    /**
     * @return float
     */
    public function getVoteAverage()
    {
        return $this->voteAverage;
    }

    /**
     * @param  int   $voteCount
     * @return $this
     */
    public function setVoteCount($voteCount)
    {
        $this->voteCount = $voteCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getVoteCount()
    {
        return $this->voteCount;
    }
}
