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
namespace Tmdb\Model\Search\SearchQuery;

use Tmdb\Model\Search\SearchQuery;

/**
 * Class ListSearchQuery
 * @package Tmdb\Model\Search\SearchQuery
 */
class ListSearchQuery extends SearchQuery
{
    /**
     * Toggle the inclusion of adult titles. Expected value is: true or false
     *
     * @param bool
     * @return $this
     */
    public function includeAdult($include_adult)
    {
        $this->set('include_adult', (bool) $include_adult);

        return $this;
    }
}
