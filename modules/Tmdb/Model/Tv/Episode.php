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
namespace Tmdb\Model\Tv;

use Tmdb\Model\AbstractModel;
use Tmdb\Model\Collection\CreditsCollection;
use Tmdb\Model\Collection\Images;
use Tmdb\Model\Collection\Videos;
use Tmdb\Model\Common\ExternalIds;
use Tmdb\Model\Common\Video;
use Tmdb\Model\Image\StillImage;

/**
 * Class Episode
 * @package Tmdb\Model\Tv
 */
class Episode extends AbstractModel
{
    /**
     * @var \DateTime
     */
    private $airDate;

    /**
     * @var integer
     */
    private $episodeNumber;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $overview;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $productionCode;

    /**
     * @var integer
     */
    private $seasonNumber;

    /**
     * @var string
     */
    private $stillPath;

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
     * @var Credits
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
     * @var StillImage
     */
    protected $still;

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
        'air_date',
        'episode_number',
        'name',
        'overview',
        'id',
        'production_code',
        'season_number',
        'still_path',
        'vote_average',
        'vote_count'
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->credits     = new CreditsCollection();
        $this->externalIds = new ExternalIds();
        $this->images      = new Images();
        $this->videos      = new Videos();
    }

    /**
     * @param  \DateTime $airDate
     * @return $this
     */
    public function setAirDate($airDate)
    {
        if (!$airDate instanceof \DateTime) {
            $airDate = new \DateTime($airDate);
        }

        $this->airDate = $airDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAirDate()
    {
        return $this->airDate;
    }

    /**
     * @param  int   $episodeNumber
     * @return $this
     */
    public function setEpisodeNumber($episodeNumber)
    {
        $this->episodeNumber = (int) $episodeNumber;

        return $this;
    }

    /**
     * @return int
     */
    public function getEpisodeNumber()
    {
        return $this->episodeNumber;
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
     * @param  string $productionCode
     * @return $this
     */
    public function setProductionCode($productionCode)
    {
        $this->productionCode = $productionCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getProductionCode()
    {
        return $this->productionCode;
    }

    /**
     * @param  int   $seasonNumber
     * @return $this
     */
    public function setSeasonNumber($seasonNumber)
    {
        $this->seasonNumber = (int) $seasonNumber;

        return $this;
    }

    /**
     * @return int
     */
    public function getSeasonNumber()
    {
        return $this->seasonNumber;
    }

    /**
     * @param  string $stillPath
     * @return $this
     */
    public function setStillPath($stillPath)
    {
        $this->stillPath = $stillPath;

        return $this;
    }

    /**
     * @return string
     */
    public function getStillPath()
    {
        return $this->stillPath;
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
     * @param  Credits $credits
     * @return $this
     */
    public function setCredits($credits)
    {
        $this->credits = $credits;

        return $this;
    }

    /**
     * @return Credits
     */
    public function getCredits()
    {
        return $this->credits;
    }

    /**
     * @param  ExternalIds $externalIds
     * @return $this
     */
    public function setExternalIds($externalIds)
    {
        $this->externalIds = $externalIds;

        return $this;
    }

    /**
     * @return ExternalIds
     */
    public function getExternalIds()
    {
        return $this->externalIds;
    }

    /**
     * @param  Images $images
     * @return $this
     */
    public function setImages($images)
    {
        $this->images = $images;

        return $this;
    }

    /**
     * @return Images
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param  StillImage $still
     * @return $this
     */
    public function setStillImage($still)
    {
        $this->still = $still;

        return $this;
    }

    /**
     * @return StillImage
     */
    public function getStillImage()
    {
        return $this->still;
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
     * @return Videos|Video[]
     */
    public function getVideos()
    {
        return $this->videos;
    }
}
