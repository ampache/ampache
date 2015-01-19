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
 * Class DiscoverMoviesQuery
 * @package Tmdb\Model\Query\Discover
 */
class DiscoverMoviesQuery extends QueryParametersCollection
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
     * Available options are vote_average.desc, vote_average.asc, release_date.desc,
     * release_date.asc, popularity.desc, popularity.asc
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
     * Toggle the inclusion of adult titles. Expected value is a boolean, true or false
     *
     * @param  boolean $allow
     * @return $this
     */
    public function includeAdult($allow = true)
    {
        $this->set('include_adult', (bool) $allow);

        return $this;
    }

    /**
     * Filter the results release dates to matches that include this value.
     * Expected value is a year.
     *
     * @param  \DateTime|integer $year
     * @return $this
     */
    public function year($year)
    {
        if ($year instanceof \DateTime) {
            $year = $year->format('Y');
        }

        $this->set('year', (int) $year);

        return $this;
    }

    /**
     * Filter the results so that only the primary release date year has this value.
     * Expected value is a year.
     *
     * @param  \DateTime|integer $year
     * @return $this
     */
    public function primaryReleaseYear($year)
    {
        if ($year instanceof \DateTime) {
            $year = $year->format('Y');
        }

        $this->set('primary_release_year', (int) $year);

        return $this;
    }

    /**
     * Only include movies that are equal to, or have a vote count higher than this value.
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
     * Only include movies that are equal to, or have a higher average rating than this value.
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
     * Only include movies with the specified genres.
     * Expected value is an integer (the id of a genre).
     *
     * Multiple values can be specified.
     *
     * Comma separated indicates an 'AND' query, while a pipe (|) separated value indicates an 'OR'.
     *
     * If an array is supplied this defaults to an AND query
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
     * Creates an or query for genres
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
     * Creates an and query for genres
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
     * The minimum release to include.
     *
     * @param  \DateTime|string $date
     * @return $this
     */
    public function releaseDateGte($date)
    {
        if ($date instanceof \DateTime) {
            $date = $date->format('Y-m-d');
        }

        $this->set('release_date.gte', $date);

        return $this;
    }

    /**
     * The maximum release to include.
     *
     * @param  \DateTime $date
     * @return $this
     */
    public function releaseDateLte(\DateTime $date)
    {
        if ($date instanceof \DateTime) {
            $date = $date->format('Y-m-d');
        }

        $this->set('release_date.gte', $date);

        return $this;
    }

    /**
     * Only include movies with certifications for a specific country.
     *
     * When this value is specified, 'certification.lte' is required.
     * A ISO 3166-1 is expected.
     *
     * @param  string $country
     * @return $this
     */
    public function certificationCountry($country)
    {
        $this->set('certification_country', $country);

        return $this;
    }

    /**
     * Only include movies with this certification and lower.
     *
     * Expected value is a valid certification for the specificed 'certification_country'.
     *
     * @param  mixed $value
     * @return $this
     */
    public function certificationLte($value)
    {
        $this->set('certification.lte', $value);

        return $this;
    }

    /**
     * Filter movies to include a specific company.
     *
     * Expected value is an integer (the id of a company).
     * They can be comma separated to indicate an 'AND' query.
     *
     * @param  array|string $companies
     * @return $this
     */
    public function withCompanies($companies)
    {
        if (is_array($companies)) {
            $companies = $this->withCompaniesAnd($companies);
        }

        $this->set('with_companies', $companies);

        return $this;
    }

    /**
     * Creates an and query for companies
     *
     * @param  array $companies
     * @return $this
     */
    public function withCompaniesAnd(array $companies = array())
    {
        return $this->withCompanies(
            implode(',', $companies)
        );
    }
}
