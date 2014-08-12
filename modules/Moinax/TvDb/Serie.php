<?php

namespace Moinax\TvDb;

/**
 * Serie object
 *
 * @package TvDb
 * @author Jérôme Poskin <moinax@gmail.com>
 */
class Serie
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $language;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $banner;

    /**
     * @var string
     */
    public $overview;

    /**
     * @var \DateTime
     */
    public $firstAired;

    /**
     * @var string
     */
    public $imdbId;

    /**
     * @var array
     */
    public $actors = array();

    /**
     * @var string
     */
    public $airsDayOfWeek = '';

    /**
     * @var string
     */
    public $airsTime = '';

    /**
     * @var string
     */
    public $contentRating = '';

    /**
     * @var array
     */
    public $genres = array();

    /**
     * @var string
     */
    public $network = '';

    /**
     * @var string
     */
    public $rating = '';

    /**
     * @var int
     */
    public $ratingCount = 0;

    /**
     * @var int
     */
    public $runtime = 0;

    /**
     * @var string
     */
    public $status = '';

    /**
     * @var \DateTime
     */
    public $added;

    /**
     * @var int
     */
    public $addedBy;

    /**
     * @var string
     */
    public $fanArt = '';

    /**
     * @var \DateTime
     */
    public $lastUpdated;

    /**
     * @var string
     */
    public $poster = '';

    /**
     * @var string
     */
    public $zap2ItId = '';

    /**
     * Constructor
     *
     * @access public
     * @param \SimpleXMLElement $data A simplexml element created from thetvdb.com's xml data for the tv show
     * @return \Moinax\TvDb\Serie
     */
    public function __construct($data)
    {
        $this->id = (int)$data->id;
        $this->language = (string)$data->Language;
        $this->name = (string)$data->SeriesName;
        $this->banner = (string)$data->banner;
        $this->overview = (string)$data->Overview;
        $this->firstAired = new \DateTime((string)$data->FirstAired);
        $this->imdbId = (string)$data->IMDB_ID;
        $this->actors = (array)Client::removeEmptyIndexes(explode('|', (string)$data->Actors));
        $this->airsDayOfWeek = (string)$data->Airs_DayOfWeek;
        $this->airsTime = (string)$data->Airs_Time;
        $this->contentRating = (string)$data->ContentRating;
        $this->genres = (array)Client::removeEmptyIndexes(explode('|', (string)$data->Genre));
        $this->network = (string)$data->Network;
        $this->rating = (string)$data->Rating;
        $this->ratingCount = (string)$data->RatingCount;
        $this->runtime = (int)$data->Runtime;
        $this->status = (string)$data->Status;
        $this->added = new \DateTime((string)$data->added);
        $this->addedBy = (int)$data->addedBy;
        $this->fanArt = (string)$data->fanart;
        $this->lastUpdated = \DateTime::createFromFormat('U', (int)$data->lastupdated);
        $this->poster = (string)$data->poster;
        $this->zap2ItId = (string)$data->zap2it_id;

    }
}