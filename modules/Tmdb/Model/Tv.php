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

use Tmdb\Model\Collection\Videos;
use Tmdb\Model\Common\GenericCollection;
use Tmdb\Model\Collection\CreditsCollection;
use Tmdb\Model\Collection\Genres;
use Tmdb\Model\Collection\Images;
use Tmdb\Model\Image\BackdropImage;
use Tmdb\Model\Image\PosterImage;
use Tmdb\Model\Common\ExternalIds;

/**
 * Class Tv
 * @package Tmdb\Model
 */
class Tv extends AbstractModel
{
    /**
     * @var string
     */
    private $backdropPath;

    /**
     * @var GenericCollection
     */
    private $createdBy = null;

    /**
     * @var GenericCollection
     */
    private $contentRatings;

    /**
     * @var array
     */
    private $episodeRunTime;

    /**
     * @var \DateTime
     */
    private $firstAirDate;

    /**
     * Genres
     *
     * @var Genres
     */
    private $genres;

    /**
     * @var string
     */
    private $homepage;

    /**
     * @var int
     */
    private $id;

    /**
     * @var boolean
     */
    private $inProduction;

    /**
     * @var array
     */
    private $languages;

    /**
     * @var \DateTime
     */
    private $lastAirDate;

    /**
     * @var string
     */
    private $name;

    /**
     * @var GenericCollection|Network[]
     */
    private $networks;

    /**
     * @var integer
     */
    private $numberOfEpisodes;

    /**
     * @var integer
     */
    private $numberOfSeasons;

    /**
     * @var string
     */
    private $originalName;

    /**
     * @var string
     */
    private $originalLanguage;

    /**
     * @var GenericCollection
     */
    private $originCountry;

    /**
     * @var string
     */
    private $overview;

    /**
     * @var float
     */
    private $popularity;

    /**
     * @var string
     */
    private $posterPath;

    /**
     * @var GenericCollection
     */
    private $seasons;

    /**
     * @var string
     */
    private $status;

    /**
     * @var float
     */
    private $voteAverage;

    /**
     * @var int
     */
    private $voteCount;

    /**
     * Credits
     *
     * @var CreditsCollection
     */
    protected $credits;

    /**
     * External Ids
     *
     * @var ExternalIds
     */
    protected $externalIds;

    /**
     * Images
     *
     * @var Images
     */
    protected $images;

    /**
     * @var GenericCollection
     */
    protected $translations;

    /**
     * @var BackdropImage
     */
    protected $backdrop;

    /**
     * @var PosterImage
     */
    protected $poster;

    /**
     * @var Videos
     */
    protected $videos;

    /**
     * @var GenericCollection
     */
    protected $changes;

    /**
     * @var GenericCollection
     */
    protected $keywords;

    /**
     * @var GenericCollection
     */
    protected $similar;

    /**
     * @var GenericCollection
     */
    protected $productionCompanies;

    /**
     * Properties that are available in the API
     *
     * These properties are hydrated by the ObjectHydrator, all the other properties are handled by the factory.
     *
     * @var array
     */
    public static $properties = [
        'backdrop_path',
        'episode_run_time',
        'first_air_date',
        'homepage',
        'id',
        'in_production',
        'last_air_date',
        'name',
        'number_of_episodes',
        'number_of_seasons',
        'original_name',
        'original_language',
        'overview',
        'popularity',
        'poster_path',
        'status',
        'vote_average',
        'vote_count',
    ];

    /**
     * Constructor
     *
     * Set all default collections
     */
    public function __construct()
    {
        $this->genres         = new Genres();
        $this->networks       = new GenericCollection();
        $this->originCountry  = new GenericCollection();
        $this->seasons        = new GenericCollection();
        $this->credits        = new CreditsCollection();
        $this->externalIds    = new ExternalIds();
        $this->images         = new Images();
        $this->translations   = new GenericCollection();
        $this->videos         = new Videos();
        $this->changes        = new GenericCollection();
        $this->keywords       = new GenericCollection();
        $this->similar        = new GenericCollection();
        $this->contentRatings = new GenericCollection();
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
     * @return GenericCollection
     */
    public function getContentRatings()
    {
        return $this->contentRatings;
    }

    /**
     * @param  GenericCollection $contentRatings
     * @return $this
     */
    public function setContentRatings($contentRatings)
    {
        $this->contentRatings = $contentRatings;

        return $this;
    }

    /**
     * @param  GenericCollection $createdBy
     * @return $this
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return GenericCollection
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param  array $episodeRunTime
     * @return $this
     */
    public function setEpisodeRunTime($episodeRunTime)
    {
        $this->episodeRunTime = $episodeRunTime;

        return $this;
    }

    /**
     * @return array
     */
    public function getEpisodeRunTime()
    {
        return $this->episodeRunTime;
    }

    /**
     * @param  \DateTime $firstAirDate
     * @return $this
     */
    public function setFirstAirDate($firstAirDate)
    {
        if (!$firstAirDate instanceof \DateTime) {
            $firstAirDate = new \DateTime($firstAirDate);
        }

        $this->firstAirDate = $firstAirDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getFirstAirDate()
    {
        return $this->firstAirDate;
    }

    /**
     * @param  \Tmdb\Model\Collection\Genres $genres
     * @return $this
     */
    public function setGenres($genres)
    {
        $this->genres = $genres;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Collection\Genres
     */
    public function getGenres()
    {
        return $this->genres;
    }

    /**
     * @param  string $homepage
     * @return $this
     */
    public function setHomepage($homepage)
    {
        $this->homepage = $homepage;

        return $this;
    }

    /**
     * @return string
     */
    public function getHomepage()
    {
        return $this->homepage;
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
     * @param  boolean $inProduction
     * @return $this
     */
    public function setInProduction($inProduction)
    {
        $this->inProduction = $inProduction;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getInProduction()
    {
        return $this->inProduction;
    }

    /**
     * @param  GenericCollection $languages
     * @return $this
     */
    public function setLanguages($languages)
    {
        $this->languages = $languages;

        return $this;
    }

    /**
     * @return GenericCollection
     */
    public function getLanguages()
    {
        return $this->languages;
    }

    /**
     * @param  string $lastAirDate
     * @return $this
     */
    public function setLastAirDate($lastAirDate)
    {
        if (!$lastAirDate instanceof \DateTime) {
            $lastAirDate = new \DateTime($lastAirDate);
        }

        $this->lastAirDate = $lastAirDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastAirDate()
    {
        return $this->lastAirDate;
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
     * @param  GenericCollection $networks
     * @return $this
     */
    public function setNetworks($networks)
    {
        $this->networks = $networks;

        return $this;
    }

    /**
     * @return Network[]
     */
    public function getNetworks()
    {
        return $this->networks;
    }

    /**
     * @param  int   $numberOfEpisodes
     * @return $this
     */
    public function setNumberOfEpisodes($numberOfEpisodes)
    {
        $this->numberOfEpisodes = (int) $numberOfEpisodes;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumberOfEpisodes()
    {
        return $this->numberOfEpisodes;
    }

    /**
     * @param  int   $numberOfSeasons
     * @return $this
     */
    public function setNumberOfSeasons($numberOfSeasons)
    {
        $this->numberOfSeasons = (int) $numberOfSeasons;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumberOfSeasons()
    {
        return $this->numberOfSeasons;
    }

    /**
     * @param  GenericCollection $originCountry
     * @return $this
     */
    public function setOriginCountry($originCountry)
    {
        $this->originCountry = $originCountry;

        return $this;
    }

    /**
     * @return GenericCollection
     */
    public function getOriginCountry()
    {
        return $this->originCountry;
    }

    /**
     * @param  string $originalName
     * @return $this
     */
    public function setOriginalName($originalName)
    {
        $this->originalName = $originalName;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * @param  string $originalLanguage
     * @return $this
     */
    public function setOriginalLanguage($originalLanguage)
    {
        $this->originalLanguage = $originalLanguage;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalLanguage()
    {
        return $this->originalLanguage;
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
     * @param  float $popularity
     * @return $this
     */
    public function setPopularity($popularity)
    {
        $this->popularity = (float) $popularity;

        return $this;
    }

    /**
     * @return float
     */
    public function getPopularity()
    {
        return $this->popularity;
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
     * @param  GenericCollection $seasons
     * @return $this
     */
    public function setSeasons($seasons)
    {
        $this->seasons = $seasons;

        return $this;
    }

    /**
     * @return GenericCollection
     */
    public function getSeasons()
    {
        return $this->seasons;
    }

    /**
     * @param  string $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
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
     * @param  GenericCollection $translations
     * @return $this
     */
    public function setTranslations($translations)
    {
        $this->translations = $translations;

        return $this;
    }

    /**
     * @return GenericCollection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param  \Tmdb\Model\Collection\Images $images
     * @return $this
     */
    public function setImages($images)
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
     * @param  \Tmdb\Model\Common\ExternalIds $externalIds
     * @return $this
     */
    public function setExternalIds($externalIds)
    {
        $this->externalIds = $externalIds;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Common\ExternalIds
     */
    public function getExternalIds()
    {
        return $this->externalIds;
    }

    /**
     * @param  \Tmdb\Model\Collection\CreditsCollection $credits
     * @return $this
     */
    public function setCredits($credits)
    {
        $this->credits = $credits;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Collection\CreditsCollection
     */
    public function getCredits()
    {
        return $this->credits;
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
     * @param  \Tmdb\Model\Collection\Videos $videos
     * @return $this
     */
    public function setVideos($videos)
    {
        $this->videos = $videos;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Collection\Videos
     */
    public function getVideos()
    {
        return $this->videos;
    }

    /**
     * @param  GenericCollection $changes
     * @return $this
     */
    public function setChanges($changes)
    {
        $this->changes = $changes;

        return $this;
    }

    /**
     * @return GenericCollection
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * @param  GenericCollection $keywords
     * @return $this
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;

        return $this;
    }

    /**
     * @return GenericCollection
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @param  GenericCollection $similar
     * @return $this
     */
    public function setSimilar($similar)
    {
        $this->similar = $similar;

        return $this;
    }

    /**
     * @return GenericCollection
     */
    public function getSimilar()
    {
        return $this->similar;
    }

    /**
     * @return GenericCollection
     */
    public function getProductionCompanies()
    {
        return $this->productionCompanies;
    }

    /**
     * @param  GenericCollection $productionCompanies
     * @return $this
     */
    public function setProductionCompanies($productionCompanies)
    {
        $this->productionCompanies = $productionCompanies;

        return $this;
    }
}
