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
namespace Tmdb\Model\Search;

use Tmdb\Model\Collection\QueryParametersCollection;

/**
 * Class SearchQuery
 * @package Tmdb\Model\Search
 */
class SearchQuery extends QueryParametersCollection
{
    /**
     * CGI escaped string
     *
     * @param string
     * @return $this
     */
    public function query($query)
    {
        $this->set('query', $query);

        return $this;
    }

    /**
     * Minimum 1, maximum 1000.
     *
     * @param int
     * @return $this
     */
    public function page($page)
    {
        $this->set('page', $page);

        return $this;
    }
}
