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

use Tmdb\Exception\NotImplementedException;
use Tmdb\Model\Find;

/**
 * Class FindFactory
 * @package Tmdb\Factory
 */
class FindFactory extends AbstractFactory
{
    /**
     * @var MovieFactory
     */
    private $movieFactory;

    /**
     * @var PeopleFactory
     */
    private $peopleFactory;

    /**
     * @var TvFactory
     */
    private $tvFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->movieFactory  = new MovieFactory();
        $this->peopleFactory = new PeopleFactory();
        $this->tvFactory     = new TvFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data = array())
    {
        $find = new Find();

        if (array_key_exists('movie_results', $data)) {
            $find->setMovieResults($this->getMovieFactory()->createCollection($data['movie_results']));
        }

        if (array_key_exists('person_results', $data)) {
            $find->setPersonResults($this->getPeopleFactory()->createCollection($data['person_results']));
        }

        if (array_key_exists('tv_results', $data)) {
            $find->setTvResults($this->getTvFactory()->createCollection($data['tv_results']));
        }

        return $find;
    }

    /**
     * {@inheritdoc}
     */
    public function createCollection(array $data = array())
    {
        throw new NotImplementedException(sprintf('Method "%s" is not implemented.', __METHOD__));
    }

    /**
     * @param  \Tmdb\Factory\MovieFactory $movieFactory
     * @return $this
     */
    public function setMovieFactory($movieFactory)
    {
        $this->movieFactory = $movieFactory;

        return $this;
    }

    /**
     * @return \Tmdb\Factory\MovieFactory
     */
    public function getMovieFactory()
    {
        return $this->movieFactory;
    }

    /**
     * @param  \Tmdb\Factory\PeopleFactory $peopleFactory
     * @return $this
     */
    public function setPeopleFactory($peopleFactory)
    {
        $this->peopleFactory = $peopleFactory;

        return $this;
    }

    /**
     * @return \Tmdb\Factory\PeopleFactory
     */
    public function getPeopleFactory()
    {
        return $this->peopleFactory;
    }

    /**
     * @param  \Tmdb\Factory\TvFactory $tvFactory
     * @return $this
     */
    public function setTvFactory($tvFactory)
    {
        $this->tvFactory = $tvFactory;

        return $this;
    }

    /**
     * @return \Tmdb\Factory\TvFactory
     */
    public function getTvFactory()
    {
        return $this->tvFactory;
    }
}
