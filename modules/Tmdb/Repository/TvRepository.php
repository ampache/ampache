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

use Tmdb\Factory\TvFactory;
use Tmdb\Model\Collection\Videos;
use Tmdb\Model\Common\Video;
use Tmdb\Model\Tv;
use Tmdb\Model\Tv\QueryParameter\AppendToResponse;

/**
 * Class TvRepository
 * @package Tmdb\Repository
 * @see http://docs.themoviedb.apiary.io/#tv
 */
class TvRepository extends AbstractRepository
{
    /**
     * Load a tv with the given identifier
     *
     * If you want to optimize the result set/bandwidth you should
     * define the AppendToResponse parameter
     *
     * @param  integer                        $id
     * @param $parameters
     * @param $headers
     * @return null|\Tmdb\Model\AbstractModel
     */
    public function load($id, array $parameters = array(), array $headers = array())
    {

        if (empty($parameters)) {
            $parameters = array(
                new AppendToResponse(array(
                    AppendToResponse::CREDITS,
                    AppendToResponse::EXTERNAL_IDS,
                    AppendToResponse::IMAGES,
                    AppendToResponse::TRANSLATIONS
                ))
            );
        }

        $data = $this->getApi()->getTvshow($id, $this->parseQueryParameters($parameters), $headers);

        return $this->getFactory()->create($data);
    }

    /**
     * Get the cast & crew information about a TV series.
     *
     * Just like the website, we pull this information from the last season of the series.
     *
     * @param $id
     * @param $parameters
     * @param $headers
     * @return null|\Tmdb\Model\AbstractModel
     */
    public function getCredits($id, array $parameters = array(), array $headers = array())
    {
        $data = $this->getApi()->getCredits($id, $this->parseQueryParameters($parameters), $headers);
        $tv   = $this->getFactory()->create(array('credits' => $data));

        return $tv->getCredits();
    }

    /**
     * Get the external ids that we have stored for a TV series.
     *
     * @param $id
     * @param $parameters
     * @param $headers
     * @return null|\Tmdb\Model\AbstractModel
     */
    public function getExternalIds($id, array $parameters = array(), array $headers = array())
    {
        $data = $this->getApi()->getExternalIds($id, $this->parseQueryParameters($parameters), $headers);
        $tv   = $this->getFactory()->create(array('external_ids' => $data));

        return $tv->getExternalIds();
    }

    /**
     * Get the images (posters and backdrops) for a TV series.
     *
     * @param $id
     * @param $parameters
     * @param $headers
     * @return null|\Tmdb\Model\AbstractModel
     */
    public function getImages($id, array $parameters = array(), array $headers = array())
    {
        $data = $this->getApi()->getImages($id, $this->parseQueryParameters($parameters), $headers);
        $tv   = $this->getFactory()->create(array('images' => $data));

        return $tv->getImages();
    }

    /**
     * Get the list of translations that exist for a TV series.
     *
     * These translations cascade down to the episode level.
     *
     * @param $id
     * @param $parameters
     * @param $headers
     * @return null|\Tmdb\Model\AbstractModel
     */
    public function getTranslations($id, array $parameters = array(), array $headers = array())
    {
        $data = $this->getApi()->getTranslations($id, $this->parseQueryParameters($parameters), $headers);
        $tv   = $this->getFactory()->create(array('translations' => $data));

        return $tv->getTranslations();
    }

    /**
     * Get the images (posters and backdrops) for a TV series.
     *
     * @param $id
     * @param $parameters
     * @param $headers
     * @return Videos|Video[]
     */
    public function getVideos($id, array $parameters = array(), array $headers = array())
    {
        $data = $this->getApi()->getVideos($id, $this->parseQueryParameters($parameters), $headers);
        $tv   = $this->getFactory()->create(array('videos' => $data));

        return $tv->getVideos();
    }

    /**
     * Return the Tvs API Class
     *
     * @return \Tmdb\Api\Tv
     */
    public function getApi()
    {
        return $this->getClient()->getTvApi();
    }

    /**
     * @return TvFactory
     */
    public function getFactory()
    {
        return new TvFactory();
    }

    /**
     * Get the list of popular tvs on The Tv Database. This list refreshes every day.
     *
     * @param  array $options
     * @param  array $headers
     * @return Tv[]
     */
    public function getPopular(array $options = array(), array $headers = array())
    {
        return $this->getFactory()->createResultCollection(
            $this->getApi()->getPopular($options, $headers)
        );
    }

    /**
     * Get the list of top rated tvs. By default, this list will only include tvs that have 10 or more votes.
     * This list refreshes every day.
     *
     * @param  array $options
     * @param  array $headers
     * @return Tv[]
     */
    public function getTopRated(array $options = array(), array $headers = array())
    {
        return $this->getFactory()->createResultCollection(
            $this->getApi()->getTopRated($options, $headers)
        );
    }

    /**
     * Get the list of top rated tvs. By default, this list will only include tvs that have 10 or more votes.
     * This list refreshes every day.
     *
     * @param  array $options
     * @param  array $headers
     * @return Tv[]
     */
    public function getOnTheAir(array $options = array(), array $headers = array())
    {
        return $this->getFactory()->createResultCollection(
            $this->getApi()->getOnTheAir($options, $headers)
        );
    }

    /**
     * Get the list of TV shows that air today.
     *
     * Without a specified timezone, this query defaults to EST (Eastern Time UTC-05:00).
     *
     * @param  array $options
     * @param  array $headers
     * @return Tv[]
     */
    public function getAiringToday(array $options = array(), array $headers = array())
    {
        return $this->getFactory()->createResultCollection(
            $this->getApi()->getAiringToday($options, $headers)
        );
    }
}
