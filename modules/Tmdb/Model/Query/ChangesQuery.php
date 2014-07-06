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
namespace Tmdb\Model\Query;

use Tmdb\Model\Collection\QueryParametersCollection;

/**
 * Class ChangesQuery
 * @package Tmdb\Model\Query
 */
class ChangesQuery extends QueryParametersCollection
{
    /**
     * Set the from parameter
     *
     * @param  \DateTime $date
     * @return $this
     */
    public function from(\DateTime $date)
    {
        $this->set('from', $date->format('Y-m-d'));

        return $this;
    }

    /**
     * Set the to parameter
     *
     * @param  \DateTime $date
     * @return $this
     */
    public function to(\DateTime $date)
    {
        $this->set('to', $date->format('Y-m-d'));

        return $this;
    }

    /**
     * Set the page parameter
     *
     * @param  int   $page
     * @return $this
     */
    public function page($page = 1)
    {
        $this->set('page', (int) $page);

        return $this;
    }
}
