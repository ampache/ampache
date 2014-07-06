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
use Tmdb\Model\Common\Translation;
use Tmdb\Model\Person\CastMember;
use Tmdb\Model\Person\CrewMember;
use Tmdb\Model\Common\ExternalIds;
use Tmdb\Model\Tv;

/**
 * Class TvFactory
 * @package Tmdb\Factory
 */
class TvFactory extends AbstractFactory
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
     * @var GenreFactory
     */
    private $genreFactory;

    /**
     * @var ImageFactory
     */
    private $imageFactory;

    /**
     * @var TvSeasonFactory
     */
    private $tvSeasonFactory;

    /**
     * @var NetworkFactory
     */
    private $networkFactory;

    /**
     * @var Common\VideoFactory
     */
    private $videoFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->castFactory     = new CastFactory();
        $this->crewFactory     = new CrewFactory();
        $this->genreFactory    = new GenreFactory();
        $this->imageFactory    = new ImageFactory();
        $this->tvSeasonFactory = new TvSeasonFactory();
        $this->networkFactory  = new NetworkFactory();
        $this->videoFactory    = new VideoFactory();
    }

    /**
     * @param array $data
     *
     * @return Tv
     */
    public function create(array $data = array())
    {
        if (!$data) {
            return null;
        }

        $tvShow = new Tv();

        if (array_key_exists('credits', $data)) {
            if (array_key_exists('cast', $data['credits'])) {
                $tvShow->getCredits()->setCast(
                    $this->getCastFactory()->createCollection(
                        $data['credits']['cast'],
                        new CastMember()
                    )
                );
            }

            if (array_key_exists('crew', $data['credits'])) {
                $tvShow->getCredits()->setCrew(
                    $this->getCrewFactory()->createCollection(
                        $data['credits']['crew'],
                        new CrewMember()
                    )
                );
            }
        }

        /** External ids */
        if (array_key_exists('external_ids', $data)) {
            $tvShow->setExternalIds(
                $this->hydrate(new ExternalIds(), $data['external_ids'])
            );
        }

        /** Genres */
        if (array_key_exists('genres', $data)) {
            $tvShow->setGenres($this->getGenreFactory()->createCollection($data['genres']));
        }

        /** Images */
        if (array_key_exists('images', $data)) {
            $tvShow->setImages(
                $this->getImageFactory()->createCollectionFromTv($data['images'])
            );
        }

        if (array_key_exists('backdrop_path', $data)) {
            $tvShow->setBackdropImage(
                $this->getImageFactory()->createFromPath($data['backdrop_path'], 'backdrop_path')
            );
        }

        if (array_key_exists('poster_path', $data)) {
            $tvShow->setPosterImage(
                $this->getImageFactory()->createFromPath($data['poster_path'], 'poster_path')
            );
        }

        /** Translations */
        if (array_key_exists('translations', $data) && null !== $data['translations']) {

            if (array_key_exists('translations', $data['translations'])) {
                $translations = $data['translations']['translations'];
            } else {
                $translations = $data['translations'];
            }

            $tvShow->setTranslations(
                $this->createGenericCollection($translations, new Translation())
            );
        }

        /** Seasons */
        if (array_key_exists('seasons', $data)) {
            $tvShow->setSeasons($this->getTvSeasonFactory()->createCollection($data['seasons']));
        }

        /** Networks */
        if (array_key_exists('networks', $data)) {
            $tvShow->setNetworks($this->getNetworkFactory()->createCollection($data['networks']));
        }

        if (array_key_exists('videos', $data)) {
            $tvShow->setVideos($this->getVideoFactory()->createCollection($data['videos']));
        }

        return $this->hydrate($tvShow, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function createCollection(array $data = array())
    {
        $collection = new GenericCollection();

        if (array_key_exists('results', $data)) {
            $data = $data['results'];
        }

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
     * @param  \Tmdb\Factory\GenreFactory $genreFactory
     * @return $this
     */
    public function setGenreFactory($genreFactory)
    {
        $this->genreFactory = $genreFactory;

        return $this;
    }

    /**
     * @return \Tmdb\Factory\GenreFactory
     */
    public function getGenreFactory()
    {
        return $this->genreFactory;
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
     * @param  \Tmdb\Factory\TvSeasonFactory $tvSeasonFactory
     * @return $this
     */
    public function setTvSeasonFactory($tvSeasonFactory)
    {
        $this->tvSeasonFactory = $tvSeasonFactory;

        return $this;
    }

    /**
     * @return \Tmdb\Factory\TvSeasonFactory
     */
    public function getTvSeasonFactory()
    {
        return $this->tvSeasonFactory;
    }

    /**
     * @param  \Tmdb\Factory\NetworkFactory $networkFactory
     * @return $this
     */
    public function setNetworkFactory($networkFactory)
    {
        $this->networkFactory = $networkFactory;

        return $this;
    }

    /**
     * @return \Tmdb\Factory\NetworkFactory
     */
    public function getNetworkFactory()
    {
        return $this->networkFactory;
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
