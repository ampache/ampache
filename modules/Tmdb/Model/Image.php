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

use Tmdb\Model\Filter\ImageFilter;
use Tmdb\Model\Filter\LanguageFilter;

/**
 * Class Image
 * @package Tmdb\Model
 */
class Image extends AbstractModel implements ImageFilter, LanguageFilter
{
    const FORMAT_POSTER   = 'poster';
    const FORMAT_BACKDROP = 'backdrop';
    const FORMAT_PROFILE  = 'profile';
    const FORMAT_LOGO     = 'logo';
    const FORMAT_STILL    = 'still';

    private $filePath;
    private $width;
    private $height;
    private $iso6391;
    private $aspectRatio;
    private $voteAverage;
    private $voteCount;

    protected $id;
    protected $type;

    public static $properties = array(
        'file_path',
        'width',
        'height',
        'iso_639_1',
        'aspect_ratio',
        'vote_average',
        'vote_count'
    );

    public static $formats = array(
        'posters'   => self::FORMAT_POSTER,
        'backdrops' => self::FORMAT_BACKDROP,
        'profiles'  => self::FORMAT_PROFILE,
        'logos'     => self::FORMAT_LOGO,
        'stills'    => self::FORMAT_STILL
    );

    /**
     * Get the singular type as defined in $_types
     *
     * @param $name
     * @return mixed
     */
    public static function getTypeFromCollectionName($name)
    {
        if (array_key_exists($name, self::$formats)) {
            return self::$formats[$name];
        }
    }

    /**
     * @param  float $aspectRatio
     * @return $this
     */
    public function setAspectRatio($aspectRatio)
    {
        $this->aspectRatio = (float) $aspectRatio;

        return $this;
    }

    /**
     * @return float
     */
    public function getAspectRatio()
    {
        return $this->aspectRatio;
    }

    /**
     * @param  mixed $filePath
     * @return $this
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @param  mixed $height
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = (int) $height;

        return $this;
    }

    /**
     * @return integer
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param  mixed $iso6391
     * @return $this
     */
    public function setIso6391($iso6391)
    {
        $this->iso6391 = $iso6391;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIso6391()
    {
        return $this->iso6391;
    }

    /**
     * @param  float $voteAverage
     * @return $this
     */
    public function setVoteAverage($voteAverage)
    {
        $this->voteAverage = (float) $voteAverage;

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
        $this->voteCount = (int) $voteCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getVoteCount()
    {
        return $this->voteCount;
    }

    /**
     * @param  int   $width
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = (int) $width;

        return $this;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Return the file path when casted to string
     *
     * @throws \Exception when the filepath is empty.
     * @return mixed
     */
    public function __toString()
    {
        $filePath = $this->getFilePath();

        if (empty($filePath)) {
            throw new \Exception(sprintf(
                'Trying to convert an instance of "%s" into an string, but there was no filePath found.',
                get_class($this)
            ));
        }

        return $this->getFilePath();
    }
}
