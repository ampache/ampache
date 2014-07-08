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

use Tmdb\Factory\PeopleFactory;
use Tmdb\Model\Person;
use Tmdb\Model\Person\QueryParameter\AppendToResponse;

/**
 * Class PeopleRepository
 * @package Tmdb\Repository
 * @see http://docs.themoviedb.apiary.io/#people
 *
 * @todo implement the new methods
 */
class PeopleRepository extends AbstractRepository
{
    /**
     * Load a person with the given identifier
     *
     * @param $id
     * @param  array  $parameters
     * @param  array  $headers
     * @return Person
     */
    public function load($id, array $parameters = array(), array $headers = array())
    {
        if (empty($parameters) && $parameters !== false) {
            // Load a no-nonsense default set
            $parameters = array(
                new AppendToResponse(array(
                    AppendToResponse::IMAGES,
                    AppendToResponse::CHANGES,
                    AppendToResponse::COMBINED_CREDITS,
                    AppendToResponse::MOVIE_CREDITS,
                    AppendToResponse::TV_CREDITS,
                    AppendToResponse::EXTERNAL_IDS
                ))
            );
        }

        $data = $this->getApi()->getPerson($id, $this->parseQueryParameters($parameters), $headers);

        return $this->getFactory()->create($data);
    }

    /**
     * Get the movie credits for a specific person id.
     *
     * @param $id
     * @param $parameters
     * @param $headers
     * @return null|\Tmdb\Model\AbstractModel
     */
    public function getMovieCredits($id, array $parameters = array(), array $headers = array())
    {
        $data   = $this->getApi()->getMovieCredits($id, $this->parseQueryParameters($parameters), $headers);
        $person = $this->getFactory()->create(array('movie_credits' => $data));

        return $person->getMovieCredits();
    }

    /**
     * Get the TV credits for a specific person id.
     *
     * To get the expanded details for each record,
     * call the /credit method with the provided credit_id.
     *
     * This will provide details about which episode and/or season the credit is for.
     *
     * @param $id
     * @param $parameters
     * @param $headers
     * @return null|\Tmdb\Model\AbstractModel
     */
    public function getTvCredits($id, array $parameters = array(), array $headers = array())
    {
        $data   = $this->getApi()->getTvCredits($id, $this->parseQueryParameters($parameters), $headers);
        $person = $this->getFactory()->create(array('tv_credits' => $data));

        return $person->getTvCredits();
    }

    /**
     * Get the combined (movie and TV) credits for a specific person id.
     *
     * To get the expanded details for each TV record,
     * call the /credit method with the provided credit_id.
     *
     * This will provide details about which episode and/or season the credit is for.
     *
     * @param $id
     * @param $parameters
     * @param $headers
     * @return null|\Tmdb\Model\AbstractModel
     */
    public function getCombinedCredits($id, array $parameters = array(), array $headers = array())
    {
        $data   = $this->getApi()->getCombinedCredits($id, $this->parseQueryParameters($parameters), $headers);
        $person = $this->getFactory()->create(array('combined_credits' => $data));

        return $person->getCombinedCredits();
    }

    /**
     * Get the external ids for a specific person id.
     *
     * @param $id
     * @return null|\Tmdb\Model\AbstractModel
     */
    public function getExternalIds($id)
    {
        $data   = $this->getApi()->getExternalIds($id);
        $person = $this->getFactory()->create(array('external_ids' => $data));

        return $person->getExternalIds();
    }

    /**
     * Get the images for a specific person id.
     *
     * @param $id
     * @return null|\Tmdb\Model\AbstractModel
     */
    public function getImages($id)
    {
        $data   = $this->getApi()->getCombinedCredits($id);
        $person = $this->getFactory()->create(array('images' => $data));

        return $person->getImages();
    }

    /**
     * Get the changes for a specific person id.
     *
     * Changes are grouped by key, and ordered by date in descending order.
     *
     * By default, only the last 24 hours of changes are returned.
     * The maximum number of days that can be returned in a single request is 14.
     * The language is present on fields that are translatable.
     *
     * @param $id
     * @param  array                          $parameters
     * @param  array                          $headers
     * @return null|\Tmdb\Model\AbstractModel
     */
    public function getChanges($id, array $parameters = array(), array $headers = array())
    {
        $data   = $this->getApi()->getChanges($id, $this->parseQueryParameters($parameters), $headers);
        $person = $this->getFactory()->create(array('changes' => $data));

        return $person->getChanges();
    }

    /**
     * Get the list of popular people on The Movie Database.
     *
     * This list refreshes every day.
     *
     * @param  array                          $parameters
     * @param  array                          $headers
     * @return null|\Tmdb\Model\AbstractModel
     */
    public function getPopular(array $parameters = array(), array $headers = array())
    {
        $data   = $this->getApi()->getPopular($parameters, $headers);

        return $this->getFactory()->createResultCollection($data);
    }

    /**
     * Get the latest person id.
     *
     * @return null|\Tmdb\Model\AbstractModel
     */
    public function getLatest()
    {
        $data   = $this->getApi()->getLatest();

        return $this->getFactory()->create($data);
    }

    /**
     * Return the related API class
     *
     * @return \Tmdb\Api\People
     */
    public function getApi()
    {
        return $this->getClient()->getPeopleApi();
    }

    /**
     * @return PeopleFactory
     */
    public function getFactory()
    {
        return new PeopleFactory();
    }
}
