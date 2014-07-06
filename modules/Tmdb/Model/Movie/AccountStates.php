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
namespace Tmdb\Model\Movie;

use Tmdb\Model\AbstractModel;

/**
 * Class AccountStates
 * @package Tmdb\Model\Movie
 */
class AccountStates extends AbstractModel
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var boolean
     */
    private $favorite;

    /**
     * @var Rating
     */
    private $rated;

    /**
     * @var boolean
     */
    private $watchlist;

    public static $properties = array(
        'id',
        'favorite',
        'watchlist',
    );

    /**
     * @param  boolean $favorite
     * @return $this
     */
    public function setFavorite($favorite)
    {
        $this->favorite = $favorite;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getFavorite()
    {
        return $this->favorite;
    }

    /**
     * @param  int   $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  \Tmdb\Model\Movie\Rating $rated
     * @return $this
     */
    public function setRated($rated)
    {
        $this->rated = $rated;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Movie\Rating
     */
    public function getRated()
    {
        return $this->rated;
    }

    /**
     * @param  boolean $watchlist
     * @return $this
     */
    public function setWatchlist($watchlist)
    {
        $this->watchlist = $watchlist;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getWatchlist()
    {
        return $this->watchlist;
    }
}
