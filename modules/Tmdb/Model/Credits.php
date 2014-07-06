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

use Tmdb\Model\Credits\Media;

/**
 * Class Credits
 * @package Tmdb\Model
 */
class Credits extends AbstractModel
{
    /**
     * @var string
     */
    private $creditType;

    /**
     * @var string
     */
    private $department;

    /**
     * @var string
     */
    private $job;

    /**
     * @var Media
     */
    private $media;

    /**
     * @var string
     */
    private $mediaType;

    /**
     * @var string
     */
    private $id;

    /**
     * @var Person
     */
    private $person;

    /**
     * @var array
     */
    public static $properties = array(
        'credit_type',
        'department',
        'job',
        'media_type',
        'id',
    );

    public function __construct()
    {
        $this->media = new Media();
    }

    /**
     * @param  string $creditType
     * @return $this
     */
    public function setCreditType($creditType)
    {
        $this->creditType = $creditType;

        return $this;
    }

    /**
     * @return string
     */
    public function getCreditType()
    {
        return $this->creditType;
    }

    /**
     * @param  string $department
     * @return $this
     */
    public function setDepartment($department)
    {
        $this->department = $department;

        return $this;
    }

    /**
     * @return string
     */
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * @param  string $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  string $job
     * @return $this
     */
    public function setJob($job)
    {
        $this->job = $job;

        return $this;
    }

    /**
     * @return string
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param  \Tmdb\Model\Credits\Media $media
     * @return $this
     */
    public function setMedia($media)
    {
        $this->media = $media;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Credits\Media
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * @param  string $mediaType
     * @return $this
     */
    public function setMediaType($mediaType)
    {
        $this->mediaType = $mediaType;

        return $this;
    }

    /**
     * @return string
     */
    public function getMediaType()
    {
        return $this->mediaType;
    }

    /**
     * @param  \Tmdb\Model\Person $person
     * @return $this
     */
    public function setPerson($person)
    {
        $this->person = $person;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Person
     */
    public function getPerson()
    {
        return $this->person;
    }
}
