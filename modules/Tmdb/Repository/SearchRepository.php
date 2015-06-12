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
namespace Tmdb\Repository;

use Tmdb\Client;
use Tmdb\Exception\NotImplementedException;
use Tmdb\Factory\CollectionFactory;
use Tmdb\Factory\CompanyFactory;
use Tmdb\Factory\KeywordFactory;
use Tmdb\Factory\Movie\ListItemFactory;
use Tmdb\Factory\MovieFactory;
use Tmdb\Factory\PeopleFactory;
use Tmdb\Factory\TvFactory;
use Tmdb\Model\Collection\ResultCollection;
use Tmdb\Model\Collection;
use Tmdb\Model\Company;
use Tmdb\Model\Keyword;
use Tmdb\Model\Movie;
use Tmdb\Model\Person;
use Tmdb\Model\Search\SearchQuery\CollectionSearchQuery;
use Tmdb\Model\Search\SearchQuery\CompanySearchQuery;
use Tmdb\Model\Search\SearchQuery\KeywordSearchQuery;
use Tmdb\Model\Search\SearchQuery\ListSearchQuery;
use Tmdb\Model\Search\SearchQuery\MovieSearchQuery;
use Tmdb\Model\Search\SearchQuery\PersonSearchQuery;
use Tmdb\Model\Search\SearchQuery\TvSearchQuery;
use Tmdb\Model\Search\SearchQuery;
use Tmdb\Model\Tv;

/**
 * Class SearchRepository
 * @package Tmdb\Repository
 * @see http://docs.themoviedb.apiary.io/#search
 */
class SearchRepository extends AbstractRepository
{
    /**
     * @var MovieFactory
     */
    private $movieFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var TvFactory
     */
    private $tvFactory;

    /**
     * @var PeopleFactory
     */
    private $peopleFactory;

    /**
     * @var ListItemFactory
     */
    private $listItemFactory;

    /**
     * @var CompanyFactory
     */
    private $companyFactory;

    /**
     * @var KeywordFactory
     */
    private $keywordFactory;

    public function __construct(Client $client)
    {
        parent::__construct($client);

        $this->movieFactory      = new MovieFactory();
        $this->collectionFactory = new CollectionFactory();
        $this->tvFactory         = new TvFactory();
        $this->peopleFactory     = new PeopleFactory();
        $this->listItemFactory   = new ListItemFactory();
        $this->companyFactory    = new CompanyFactory();
        $this->keywordFactory    = new KeywordFactory();
    }

    /**
     * @param string           $query
     * @param MovieSearchQuery $parameters
     * @param array            $headers
     *
     * @return ResultCollection|Movie[]
     */
    public function searchMovie($query, MovieSearchQuery $parameters, array $headers = array())
    {
        $data = $this->getApi()->searchMovies($query, $this->getParameters($parameters), $headers);

        return $this->getMovieFactory()->createResultCollection($data);
    }

    /**
     * @param string                $query
     * @param CollectionSearchQuery $parameters
     * @param array                 $headers
     *
     * @return ResultCollection|Collection[]
     */
    public function searchCollection($query, CollectionSearchQuery $parameters, array $headers = array())
    {
        $data = $this->getApi()->searchCollection($query, $this->getParameters($parameters), $headers);

        return $this->getCollectionFactory()->createResultCollection($data);
    }

    /**
     * @param string        $query
     * @param TvSearchQuery $parameters
     * @param array         $headers
     *
     * @return ResultCollection|Tv[]
     */
    public function searchTv($query, TvSearchQuery $parameters, array $headers = array())
    {
        $data = $this->getApi()->searchTv($query, $this->getParameters($parameters), $headers);

        return $this->getTvFactory()->createResultCollection($data);
    }

    /**
     * @param string            $query
     * @param PersonSearchQuery $parameters
     * @param array             $headers
     *
     * @return ResultCollection|Person[]
     */
    public function searchPerson($query, PersonSearchQuery $parameters, array $headers = array())
    {
        $data = $this->getApi()->searchPersons($query, $this->getParameters($parameters), $headers);

        return $this->getPeopleFactory()->createResultCollection($data);
    }

    /**
     * @param string          $query
     * @param ListSearchQuery $parameters
     * @param array           $headers
     *
     * @return ResultCollection
     */
    public function searchList($query, ListSearchQuery $parameters, array $headers = array())
    {
        $data = $this->getApi()->searchList($query, $this->getParameters($parameters), $headers);

        return $this->getListitemFactory()->createResultCollection($data);
    }

    /**
     * @param string             $query
     * @param CompanySearchQuery $parameters
     * @param array              $headers
     *
     * @return ResultCollection|Company[]
     */
    public function searchCompany($query, CompanySearchQuery $parameters, array $headers = array())
    {
        $data = $this->getApi()->searchTv($query, $this->getParameters($parameters), $headers);

        return $this->getCompanyFactory()->createResultCollection($data);
    }

    /**
     * @param string             $query
     * @param KeywordSearchQuery $parameters
     * @param array              $headers
     *
     * @return ResultCollection|Keyword[]
     */
    public function searchKeyword($query, KeywordSearchQuery $parameters, array $headers = array())
    {
        $data = $this->getApi()->searchKeyword($query, $this->getParameters($parameters), $headers);

        return $this->getKeywordFactory()->createResultCollection($data);
    }

    /**
     * Convert parameters back to an array
     *
     * @param  array $parameters
     * @return array
     */
    private function getParameters($parameters = array())
    {
        if ($parameters instanceof SearchQuery) {
            return $parameters->toArray();
        }

        return $parameters;
    }

    /**
     * Return the related API class
     *
     * @return \Tmdb\Api\Search
     */
    public function getApi()
    {
        return $this->getClient()->getSearchApi();
    }

    /**
     * SearchRepository does not support a generic factory
     *
     * @throws NotImplementedException
     */
    public function getFactory()
    {
        throw new NotImplementedException('SearchRepository does not support a generic factory.');
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
     * @param  \Tmdb\Factory\CollectionFactory $collectionFactory
     * @return $this
     */
    public function setCollectionFactory($collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;

        return $this;
    }

    /**
     * @return \Tmdb\Factory\CollectionFactory
     */
    public function getCollectionFactory()
    {
        return $this->collectionFactory;
    }

    /**
     * @param  \Tmdb\Factory\CompanyFactory $companyFactory
     * @return $this
     */
    public function setCompanyFactory($companyFactory)
    {
        $this->companyFactory = $companyFactory;

        return $this;
    }

    /**
     * @return \Tmdb\Factory\CompanyFactory
     */
    public function getCompanyFactory()
    {
        return $this->companyFactory;
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
