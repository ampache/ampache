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
namespace Tmdb\Model\Query\Discover;

use Tmdb\Model\Collection\QueryParametersCollection;

/**
 * Class DiscoverTvQuery
 * @package Tmdb\Model\Query\Discover
 */
class DiscoverTvQuery extends QueryParametersCollection
{
    /**
     * Minimum value is 1, expected value is an integer.
     *
     * @param  integer $page
     * @return $this
     */
    public function page($page = 1)
    {
        $this->set('page', (int) $page);

        return $this;
    }

    /**
     * ISO 639-1 code.
     *
     * @param  string $language
     * @return $this
     */
    public function language($language)
    {
        $this->set('language', $language);

        return $this;
    }

    /**
     * Available options are vote_average.desc, vote_average.asc, first_air_date.desc,
     * first_air_date.asc, popularity.desc, popularity.asc
     *
     * @param  string $option
     * @return $this
     */
    public function sortBy($option)
    {
        $this->set('sort_by', $option);

        return $this;
    }

    /**
     * Filter the results release dates to matches that include this value.
     * Expected value is a year.
     *
     * @param  \DateTime|integer $year
     * @return $this
     */
    public function firstAirDateYear($year)
    {
        if ($year instanceof \DateTime) {
            $year = $year->format('Y');
        }

        $this->set('first_air_date_year', (int) $year);

        return $this;
    }

    /**
     * Only include TV shows that are equal to, or have a vote count higher than this value.
     * Expected value is an integer.
     *
     * @param  integer $count
     * @return $this
     */
    public function voteCountGte($count)
    {
        $this->set('vote_count.gte', (int) $count);

        return $this;
    }

    /**
     * Only include TV shows that are equal to, or have a higher average rating than this value.
     * Expected value is a float.
     *
     * @param  float $average
     * @return $this
     */
    public function voteAverageGte($average)
    {
        $this->set('vote_average.gte', (float) $average);

        return $this;
    }

    /**
     * Only include TV shows with the specified genres.
     * Expected value is an integer (the id of a genre).
     *
     * Multiple values can be specified.
     *
     * Comma separated indicates an 'AND' query,
     * while a pipe (|) separated value indicates an 'OR'.
     *
     * @param  array|string $genres
     * @return $this
     */
    public function withGenres($genres)
    {
        if (is_array($genres)) {
            $genres = $this->withGenresAnd($genres);
        }

        $this->set('with_genres', $genres);

        return $this;
    }

    /**
     * Creates an OR query for genres
     *
     * @param  array $genres
     * @return $this
     */
    public function withGenresOr(array $genres = array())
    {
        return $this->withGenres(
            implode('|', $genres)
        );
    }

    /**
     * Creates an AND query for genres
     *
     * @param  array $genres
     * @return $this
     */
    public function withGenresAnd(array $genres = array())
    {
        return $this->withGenres(
            implode(',', $genres)
        );
    }

    /**
     * The minimum release to include. Expected format is YYYY-MM-DD.
     *
     * @param  \DateTime|string $date
     * @return $this
     */
    public function firstAirDateGte($date)
    {
        if ($date instanceof \DateTime) {
            $date = $date->format('Y-m-d');
        }

        $this->set('first_air_date.gte', $date);

        return $this;
    }

    /**
     * The maximum release to include. Expected format is YYYY-MM-DD.
     *
     * @param  \DateTime|string $date
     * @return $this
     */
    public function firstAirDateLte($date)
    {
        if ($date instanceof \DateTime) {
            $date = $date->format('Y-m-d');
        }

        $this->set('first_air_date.lte', $date);

        return $this;
    }

    /**
     * Filter TV shows to include a specific network.
     *
     * Expected value is an integer (the id of a network).
     * They can be comma separated to indicate an 'AND' query.
     *
     * Expected value is an integer (the id of a company).
     * They can be comma separated to indicate an 'AND' query.
     *
     * @param  array|string $networks
     * @return $this
     */
    public function withNetworks($networks)
    {
        if (is_array($networks)) {
            $networks = $this->withNetworksAnd($networks);
        }

        $this->set('with_networks', $networks);

        return $this;
    }

    /**
     * Creates an and query for networks
     *
     * @param  array $networks
     * @return $this
     */
    public function withNetworksAnd(array $networks = array())
    {
        return $this->withNetworks(
            implode(',', $networks)
        );
    }
}
