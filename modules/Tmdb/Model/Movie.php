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

use Tmdb\Model\Collection\ResultCollection;
use Tmdb\Model\Collection\Videos;
use Tmdb\Model\Common\GenericCollection;
use Tmdb\Model\Collection\CreditsCollection;
use Tmdb\Model\Collection\Genres;
use Tmdb\Model\Collection\Images;
use Tmdb\Model\Common\Country;
use Tmdb\Model\Common\SpokenLanguage;
use Tmdb\Model\Common\Translation;
use Tmdb\Model\Movie\AlternativeTitle;
use Tmdb\Model\Movie\Release;

/**
 * Class Movie
 * @package Tmdb\Model
 */
class Movie extends AbstractModel
{
    /**
     * @var bool
     */
    private $adult = false;

    /**
     * @var string
     */
    private $backdropPath;

    /**
     * @var Image
     */
    private $backdrop;

    /**
     * @var Collection
     */
    private $belongsToCollection = null;

    /**
     * @var int
     */
    private $budget;

    /**
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
     * @var string
     */
    private $imdbId;

    /**
     * @var string
     */
    private $originalTitle;

    /**
     * @var string
     */
    private $overview;

    /**
     * @var float
     */
    private $popularity;

    /**
     * @var Image
     */
    private $poster;

    /**
     * @var string
     */
    private $posterPath;

    /**
     * @var Collection
     */
    private $productionCompanies;

    /**
     * @var Collection
     */
    private $productionCountries;

    /**
     * @var \DateTime
     */
    private $releaseDate;

    /**
     * @var int
     */
    private $revenue;

    /**
     * @var int
     */
    private $runtime;

    /**
     * @var Collection
     */
    private $spokenLanguages;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $tagline;

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
     * @var Collection
     */
    protected $alternativeTitles;

    /**
     * @var Collection
     */
    protected $changes;

    /**
     * Credits
     *
     * @var Credits
     */
    protected $credits;

    /**
     * Images
     *
     * @var Images
     */
    protected $images;

    /**
     * @var Collection
     */
    protected $keywords;

    /**
     * @var Collection
     */
    protected $lists;

    /**
     * @var Collection
     */
    protected $releases;

    /**
     * @var Collection
     */
    protected $similarMovies;

    /**
     * @var Collection
     */
    protected $trailers;

    /**
     * @var Collection
     */
    protected $translations;

    /**
     * @var ResultCollection
     */
    protected $reviews;

    /**
     * @var Videos
     */
    protected $videos;

    /**
     * Properties that are available in the API
     *
     * These properties are hydrated by the ObjectHydrator, all the other properties are handled by the factory.
     *
     * @var array
     */
    public static $properties = array(
        'adult',
        'backdrop_path',
        'belongs_to_collection',
        'budget',
        'homepage',
        'id',
        'imdb_id',
        'original_title',
        'overview',
        'popularity',
        'poster_path',
        'release_date',
        'revenue',
        'runtime',
        'status',
        'tagline',
        'title',
        'vote_average',
        'vote_count',
    );

    /**
     * Constructor
     *
     * Set all default collections
     */
    public function __construct()
    {
        $this->genres              = new Genres();
        $this->productionCompanies = new GenericCollection();
        $this->productionCountries = new GenericCollection();
        $this->spokenLanguages     = new GenericCollection();
        $this->alternativeTitles   = new GenericCollection();
        $this->changes             = new GenericCollection();
        $this->credits             = new CreditsCollection();
        $this->images              = new Images();
        $this->keywords            = new GenericCollection();
        $this->lists               = new GenericCollection();
        $this->releases            = new GenericCollection();
        $this->similarMovies       = new GenericCollection();
        $this->trailers            = new GenericCollection();
        $this->translations        = new GenericCollection();
        $this->videos              = new Videos();
    }

    /**
     * @param  boolean $adult
     * @return $this
     */
    public function setAdult($adult)
    {
        $this->adult = (bool) $adult;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getAdult()
    {
        return $this->adult;
    }

    /**
     * @param  mixed $backdropPath
     * @return $this
     */
    public function setBackdropPath($backdropPath)
    {
        $this->backdropPath = $backdropPath;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBackdropPath()
    {
        return $this->backdropPath;
    }

    /**
     * @param  null  $belongsToCollection
     * @return $this
     */
    public function setBelongsToCollection($belongsToCollection)
    {
        $this->belongsToCollection = $belongsToCollection;

        return $this;
    }

    /**
     * @return Collection|null
     */
    public function getBelongsToCollection()
    {
        return $this->belongsToCollection;
    }

    /**
     * @param  GenericCollection $changes
     * @return $this
     */
    public function setChanges(GenericCollection $changes)
    {
        $this->changes = $changes;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * @param  Genres $genres
     * @return $this
     */
    public function setGenres(Genres $genres)
    {
        $this->genres = $genres;

        return $this;
    }

    /**
     * @return Genre[]
     */
    public function getGenres()
    {
        return $this->genres;
    }

    /**
     * @param  mixed $homepage
     * @return $this
     */
    public function setHomepage($homepage)
    {
        $this->homepage = $homepage;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getHomepage()
    {
        return $this->homepage;
    }

    /**
     * @param  mixed $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  Images $images
     * @return $this
     */
    public function setImages(Images $images)
    {
        $this->images = $images;

        return $this;
    }

    /**
     * @return Images Image[]
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param  mixed $imdbId
     * @return $this
     */
    public function setImdbId($imdbId)
    {
        $this->imdbId = $imdbId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getImdbId()
    {
        return $this->imdbId;
    }

    /**
     * @param  mixed $originalTitle
     * @return $this
     */
    public function setOriginalTitle($originalTitle)
    {
        $this->originalTitle = $originalTitle;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOriginalTitle()
    {
        return $this->originalTitle;
    }

    /**
     * @param  mixed $overview
     * @return $this
     */
    public function setOverview($overview)
    {
        $this->overview = $overview;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOverview()
    {
        return $this->overview;
    }

    /**
     * @param  mixed $popularity
     * @return $this
     */
    public function setPopularity($popularity)
    {
        $this->popularity = (float) $popularity;

        return $this;
    }

    /**
     * @return double
     */
    public function getPopularity()
    {
        return $this->popularity;
    }

    /**
     * @param  mixed $posterPath
     * @return $this
     */
    public function setPosterPath($posterPath)
    {
        $this->posterPath = $posterPath;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPosterPath()
    {
        return $this->posterPath;
    }

    /**
     * @param  GenericCollection $productionCompanies
     * @return $this
     */
    public function setProductionCompanies(GenericCollection $productionCompanies)
    {
        $this->productionCompanies = $productionCompanies;

        return $this;
    }

    /**
     * @return Company[]
     */
    public function getProductionCompanies()
    {
        return $this->productionCompanies;
    }

    /**
     * @param  GenericCollection $productionCountries
     * @return $this
     */
    public function setProductionCountries(GenericCollection $productionCountries)
    {
        $this->productionCountries = $productionCountries;

        return $this;
    }

    /**
     * @return Country
     */
    public function getProductionCountries()
    {
        return $this->productionCountries;
    }

    /**
     * @param  string $releaseDate
     * @return $this
     */
    public function setReleaseDate($releaseDate)
    {
        if (!$releaseDate instanceof \DateTime) {
            $releaseDate = new \DateTime($releaseDate);
        }

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
     * @param  mixed $revenue
     * @return $this
     */
    public function setRevenue($revenue)
    {
        $this->revenue = (int) $revenue;

        return $this;
    }

    /**
     * @return integer
     */
    public function getRevenue()
    {
        return $this->revenue;
    }

    /**
     * @param  mixed $runtime
     * @return $this
     */
    public function setRuntime($runtime)
    {
        $this->runtime = (int) $runtime;

        return $this;
    }

    /**
     * @return integer
     */
    public function getRuntime()
    {
        return $this->runtime;
    }

    /**
     * @param  GenericCollection $spokenLanguages
     * @return $this
     */
    public function setSpokenLanguages(GenericCollection $spokenLanguages)
    {
        $this->spokenLanguages = $spokenLanguages;

        return $this;
    }

    /**
     * @return SpokenLanguage[]
     */
    public function getSpokenLanguages()
    {
        return $this->spokenLanguages;
    }

    /**
     * @param  mixed $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param  mixed $tagline
     * @return $this
     */
    public function setTagline($tagline)
    {
        $this->tagline = $tagline;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTagline()
    {
        return $this->tagline;
    }

    /**
     * @param  mixed $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param  mixed $voteAverage
     * @return $this
     */
    public function setVoteAverage($voteAverage)
    {
        $this->voteAverage = (float) $voteAverage;

        return $this;
    }

    /**
     * @return double
     */
    public function getVoteAverage()
    {
        return $this->voteAverage;
    }

    /**
     * @param  mixed $voteCount
     * @return $this
     */
    public function setVoteCount($voteCount)
    {
        $this->voteCount = (int) $voteCount;

        return $this;
    }

    /**
     * @return integer
     */
    public function getVoteCount()
    {
        return $this->voteCount;
    }

    /**
     * @param  GenericCollection $alternativeTitles
     * @return $this
     */
    public function setAlternativeTitles($alternativeTitles)
    {
        $this->alternativeTitles = $alternativeTitles;

        return $this;
    }

    /**
     * @return GenericCollection|AlternativeTitle[]
     */
    public function getAlternativeTitles()
    {
        return $this->alternativeTitles;
    }

    /**
     * @param  int   $budget
     * @return $this
     */
    public function setBudget($budget)
    {
        $this->budget = $budget;

        return $this;
    }

    /**
     * @return int
     */
    public function getBudget()
    {
        return $this->budget;
    }

    /**
     * @param  CreditsCollection $credits
     * @return $this
     */
    public function setCredits(CreditsCollection $credits)
    {
        $this->credits = $credits;

        return $this;
    }

    /**
     * @return CreditsCollection
     */
    public function getCredits()
    {
        return $this->credits;
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
     * @return Keyword[]
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @param  GenericCollection $lists
     * @return $this
     */
    public function setLists($lists)
    {
        $this->lists = $lists;

        return $this;
    }

    /**
     * @return GenericCollection
     */
    public function getLists()
    {
        return $this->lists;
    }

    /**
     * @param  GenericCollection $releases
     * @return $this
     */
    public function setReleases(GenericCollection $releases)
    {
        $this->releases = $releases;

        return $this;
    }

    /**
     * @return Release[]
     */
    public function getReleases()
    {
        return $this->releases;
    }

    /**
     * @param  GenericCollection $similarMovies
     * @return $this
     */
    public function setSimilarMovies($similarMovies)
    {
        $this->similarMovies = $similarMovies;

        return $this;
    }

    /**
     * @return Movie[]
     */
    public function getSimilarMovies()
    {
        return $this->similarMovies;
    }

    /**
     * @param  GenericCollection $trailers
     * @return $this
     */
    public function setTrailers($trailers)
    {
        $this->trailers = $trailers;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Common\Trailer\Youtube[]
     */
    public function getTrailers()
    {
        return $this->trailers;
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
     * @return Translation[]
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param  \Tmdb\Model\Image $backdrop
     * @return $this
     */
    public function setBackdropImage($backdrop)
    {
        $this->backdrop = $backdrop;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Image
     */
    public function getBackdropImage()
    {
        return $this->backdrop;
    }

    /**
     * @param  \Tmdb\Model\Image $poster
     * @return $this
     */
    public function setPosterImage($poster)
    {
        $this->poster = $poster;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Image
     */
    public function getPosterImage()
    {
        return $this->poster;
    }

    /**
     * @param  \Tmdb\Model\Collection\ResultCollection $reviews
     * @return $this
     */
    public function setReviews($reviews)
    {
        $this->reviews = $reviews;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Collection\ResultCollection
     */
    public function getReviews()
    {
        return $this->reviews;
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
}
