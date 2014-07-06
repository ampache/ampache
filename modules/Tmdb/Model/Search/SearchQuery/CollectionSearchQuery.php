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
 * Class CollectionSearchQuery
 * @package Tmdb\Model\Search\SearchQuery
 */
class CollectionSearchQuery extends SearchQuery
{
    /**
     * ISO 639-1 code.
     *
     * @param string
     * @return $this
     */
    public function language($language)
    {
        $this->set('language', $language);

        return $this;
    }
}
