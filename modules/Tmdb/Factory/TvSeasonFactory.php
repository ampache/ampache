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

use Tmdb\Factory\Common\VideoFactory;
use Tmdb\Factory\People\CastFactory;
use Tmdb\Factory\People\CrewFactory;
use Tmdb\Model\Common\GenericCollection;
use Tmdb\Model\Person\CastMember;
use Tmdb\Model\Person\CrewMember;
use Tmdb\Model\Common\ExternalIds;
use Tmdb\Model\Tv\Season;

/**
 * Class TvSeasonFactory
 * @package Tmdb\Factory
 */
class TvSeasonFactory extends AbstractFactory
{
    /**
     * @var People\CastFactory
     */
    private $castFactory;

    /**
     * @var People\CrewFactory
     */
    private $crewFactory;

    /**
     * @var ImageFactory
     */
    private $imageFactory;

    /**
     * @var TvEpisodeFactory
     */
    private $tvEpisodeFactory;

    /**
     * @var Common\VideoFactory
     */
    private $videoFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->castFactory      = new CastFactory();
        $this->crewFactory      = new CrewFactory();
        $this->imageFactory     = new ImageFactory();
        $this->tvEpisodeFactory = new TvEpisodeFactory();
        $this->videoFactory     = new VideoFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data = array())
    {
        if (!$data) {
            return null;
        }

        $tvSeason = new Season();

        if (array_key_exists('credits', $data)) {
            if (array_key_exists('cast', $data['credits'])) {
                $tvSeason->getCredits()->setCast(
                    $this->getCastFactory()->createCollection(
                        $data['credits']['cast'],
                        new CastMember()
                    )
                );
            }

            if (array_key_exists('crew', $data['credits'])) {
                $tvSeason->getCredits()->setCrew(
                    $this->getCrewFactory()->createCollection(
                        $data['credits']['crew'],
                        new CrewMember()
                    )
                );
            }
        }

        /** External ids */
        if (array_key_exists('external_ids', $data)) {
            $tvSeason->setExternalIds(
                $this->hydrate(new ExternalIds(), $data['external_ids'])
            );
        }

        /** Images */
        if (array_key_exists('images', $data)) {
            $tvSeason->setImages($this->getImageFactory()->createCollectionFromTvSeason($data['images']));
        }

        if (array_key_exists('poster_path', $data)) {
            $tvSeason->setPosterImage($this->getImageFactory()->createFromPath($data['poster_path'], 'poster_path'));
        }

        /** Episodes */
        if (array_key_exists('episodes', $data)) {
            $tvSeason->setEpisodes($this->getTvEpisodeFactory()->createCollection($data['episodes']));
        }

        if (array_key_exists('videos', $data)) {
            $tvSeason->setVideos($this->getVideoFactory()->createCollection($data['videos']));
        }

        return $this->hydrate($tvSeason, $data);
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
     * @param  \Tmdb\Factory\People\CastFactory $castFactory
     * @return $this
     */
    public function setCastFactory($castFactory)
    {
        $this->castFactory = $castFactory;

        return $this;
    }

    /**
     * @return \Tmdb\Factory\People\CastFactory
     */
    public function getCastFactory()
    {
        return $this->castFactory;
    }

    /**
     * @param  \Tmdb\Factory\People\CrewFactory $crewFactory
     * @return $this
     */
    public function setCrewFactory($crewFactory)
    {
        $this->crewFactory = $crewFactory;

        return $this;
    }

    /**
     * @return \Tmdb\Factory\People\CrewFactory
     */
    public function getCrewFactory()
    {
        return $this->crewFactory;
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
     * @param  \Tmdb\Factory\TvEpisodeFactory $tvEpisodeFactory
     * @return $this
     */
    public function setTvEpisodeFactory($tvEpisodeFactory)
    {
        $this->tvEpisodeFactory = $tvEpisodeFactory;

        return $this;
    }

    /**
     * @return \Tmdb\Factory\TvEpisodeFactory
     */
    public function getTvEpisodeFactory()
    {
        return $this->tvEpisodeFactory;
    }

    /**
     * @param  \Tmdb\Factory\Common\VideoFactory $videoFactory
     * @return $this
     */
    public function setVideoFactory($videoFactory)
    {
        $this->videoFactory = $videoFactory;

        return $this;
    }

    /**
     * @return \Tmdb\Factory\Common\VideoFactory
     */
    public function getVideoFactory()
    {
        return $this->videoFactory;
    }
}
