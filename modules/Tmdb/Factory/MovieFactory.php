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

use Tmdb\Factory\Common\ChangeFactory;
use Tmdb\Factory\Common\VideoFactory;
use Tmdb\Factory\Movie\ListItemFactory;
use Tmdb\Factory\People\CastFactory;
use Tmdb\Factory\People\CrewFactory;
use Tmdb\Model\Common\Country;
use Tmdb\Model\Common\GenericCollection;
use Tmdb\Model\Common\Trailer\Youtube;
use Tmdb\Model\Common\Translation;
use Tmdb\Model\Company;
use Tmdb\Model\Lists\Result;
use Tmdb\Model\Movie;

/**
 * Class MovieFactory
 * @package Tmdb\Factory
 */
class MovieFactory extends AbstractFactory
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
     * @var ChangeFactory
     */
    private $changeFactory;

    /**
     * @var ReviewFactory
     */
    private $reviewFactory;

    /**
     * @var ListItemFactory
     */
    private $listItemFactory;

    /**
     * @var KeywordFactory
     */
    private $keywordFactory;

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
        $this->changeFactory   = new ChangeFactory();
        $this->reviewFactory   = new ReviewFactory();
        $this->listItemFactory = new ListItemFactory();
        $this->keywordFactory  = new KeywordFactory();
        $this->videoFactory    = new VideoFactory();
    }

    /**
     * @param  array $data
     * @return Movie
     */
    public function create(array $data = array())
    {
        if (!$data) {
            return null;
        }

        $movie = new Movie();

        if (array_key_exists('alternative_titles', $data) && array_key_exists('titles', $data['alternative_titles'])) {
            $movie->setAlternativeTitles(
                $this->createGenericCollection($data['alternative_titles']['titles'], new Movie\AlternativeTitle())
            );
        }

        if (array_key_exists('credits', $data)) {
            if (array_key_exists('cast', $data['credits'])) {
                $movie->getCredits()->setCast($this->getCastFactory()->createCollection($data['credits']['cast']));
            }

            if (array_key_exists('crew', $data['credits'])) {
                $movie->getCredits()->setCrew($this->getCrewFactory()->createCollection($data['credits']['crew']));
            }
        }

        /** Genres */
        if (array_key_exists('genres', $data)) {
            $movie->setGenres($this->getGenreFactory()->createCollection($data['genres']));
        }

        /** Images */
        if (array_key_exists('backdrop_path', $data)) {
            $movie->setBackdropImage($this->getImageFactory()->createFromPath($data['backdrop_path'], 'backdrop_path'));
        }

        if (array_key_exists('images', $data)) {
            $movie->setImages($this->getImageFactory()->createCollectionFromMovie($data['images']));
        }

        if (array_key_exists('poster_path', $data)) {
            $movie->setPosterImage($this->getImageFactory()->createFromPath($data['poster_path'], 'poster_path'));
        }

        /** Keywords */
        if (array_key_exists('keywords', $data)) {
            $movie->setKeywords($this->getKeywordFactory()->createCollection($data['keywords']));
        }

        if (array_key_exists('releases', $data) && array_key_exists('countries', $data['releases'])) {
            $movie->setReleases($this->createGenericCollection($data['releases']['countries'], new Movie\Release()));
        }

        /**
         * @TODO actually implement more providers?
         * ( Can't seem to find any quicktime related trailers anyways? ). For now KISS
         */
        if (array_key_exists('trailers', $data) && array_key_exists('youtube', $data['trailers'])) {
            $movie->setTrailers($this->createGenericCollection($data['trailers']['youtube'], new Youtube()));
        }

        if (array_key_exists('videos', $data)) {
            $movie->setVideos($this->getVideoFactory()->createCollection($data['videos']));
        }

        if (array_key_exists('translations', $data) && array_key_exists('translations', $data['translations'])) {
            $movie->setTranslations(
                $this->createGenericCollection(
                    $data['translations']['translations'],
                    new Translation()
                )
            );
        }

        if (array_key_exists('similar_movies', $data)) {
            $movie->setSimilarMovies($this->createResultCollection($data['similar_movies']));
        }

        if (array_key_exists('reviews', $data)) {
            $movie->setReviews($this->getReviewFactory()->createResultCollection($data['reviews']));
        }

        if (array_key_exists('lists', $data)) {
            $movie->setLists($this->getListItemFactory()->createResultCollection($data['lists']));
        }

        if (array_key_exists('changes', $data)) {
            $movie->setChanges($this->getChangeFactory()->createCollection($data['changes']));
        }

        if (array_key_exists('production_companies', $data)) {
            $movie->setProductionCompanies(
                $this->createGenericCollection($data['production_companies'], new Company())
            );
        }

        if (array_key_exists('production_countries', $data)) {
            $movie->setProductionCountries(
                $this->createGenericCollection($data['production_countries'], new Country())
            );
        }

        return $this->hydrate($movie, $data);
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
     * Create result
     *
     * @param  array                     $data
     * @return \Tmdb\Model\AbstractModel
     */
    public function createResult(array $data = array())
    {
        return $this->hydrate(new Result(), $data);
    }

    /**
     * Create rating
     *
     * @param  array                     $data
     * @return \Tmdb\Model\AbstractModel
     */
    public function createRating(array $data = array())
    {
        return $this->hydrate(new Movie\Rating(), $data);
    }

    /**
     * Create the account states
     *
     * @param  array                     $data
     * @return \Tmdb\Model\AbstractModel
     */
    public function createAccountStates(array $data = array())
    {
        $accountStates = new Movie\AccountStates();

        if (array_key_exists('rated', $data)) {
            $rating = new Movie\Rating();

            $accountStates->setRated($this->hydrate($rating, $data['rated']));
        }

        return $this->hydrate($accountStates, $data);
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
     * @param  \Tmdb\Factory\Common\ChangeFactory $changeFactory
     * @return $this
     */
    public function setChangeFactory($changeFactory)
    {
        $this->changeFactory = $changeFactory;

        return $this;
    }

    /**
     * @return \Tmdb\Factory\Common\ChangeFactory
     */
    public function getChangeFactory()
    {
        return $this->changeFactory;
    }

    /**
     * @param  \Tmdb\Factory\ReviewFactory $reviewFactory
     * @return $this
     */
    public function setReviewFactory($reviewFactory)
    {
        $this->reviewFactory = $reviewFactory;

        return $this;
    }

    /**
     * @return \Tmdb\Factory\ReviewFactory
     */
    public function getReviewFactory()
    {
        return $this->reviewFactory;
    }

    /**
     * @param  \Tmdb\Factory\Movie\ListItemFactory $listItemFactory
     * @return $this
     */
    public function setListItemFactory($listItemFactory)
    {
        $this->listItemFactory = $listItemFactory;

        return $this;
    }

    /**
     * @return \Tmdb\Factory\Movie\ListItemFactory
     */
    public function getListItemFactory()
    {
        return $this->listItemFactory;
    }

    /**
     * @param  \Tmdb\Factory\KeywordFactory $keywordFactory
     * @return $this
     */
    public function setKeywordFactory($keywordFactory)
    {
        $this->keywordFactory = $keywordFactory;

        return $this;
    }

    /**
     * @return \Tmdb\Factory\KeywordFactory
     */
    public function getKeywordFactory()
    {
        return $this->keywordFactory;
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
