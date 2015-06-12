<?php

namespace Moinax\TvDb;

/**
 * Simple banner object
 *
 * @package TvDb
 * @author Jérôme Poskin <moinax@gmail.com>
 */
class Banner
{

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $path = '';

    /**
     * @var string
     */
    public $type = '';

    /**
     * @var string
     */
    public $type2 = '';

    /**
     * @var array
     */
    public $colors = array();

    /**
     * @var string
     */
    public $language = '';

    /**
     * @var string
     */
    public $rating = '';

    /**
     * @var int
     */
    public $ratingCount = 0;

    /**
     * @var string
     */
    public $seriesName = '';

    /**
     * @var string
     */
    public $thumbnailPath = '';

    /**
     * @var string
     */
    public $vignettePath = '';
    
    /**
     * @var int
     */
    public $season;

    /**
     * Constructor
     *
     * @access public
     * @param \SimpleXMLElement $data A simplexml element created from thetvdb.com's xml data for the tv serie banner
     * @return \Moinax\TvDb\Banner
     */
    public function __construct($data)
    {
        $this->id = (int)$data->id;
        $this->path = (string)$data->BannerPath;
        $this->type = (string)$data->BannerType;
        $this->type2 = (string)$data->BannerType2;
        $this->colors = (array)$data->Colors;
        $this->language = (string)$data->Language;
        $this->rating = (string)$data->Rating;
        $this->ratingCount = (int)$data->RatingCount;
        $this->seriesName = (string)$data->SeriesName;
        $this->thumbnailPath = (string)$data->ThumbnailPath;
        $this->vignettePath = (string)$data->VignettePath;
        $this->season = (int)$data->Season;
    }
}