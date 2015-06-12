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
namespace Tmdb\Model\Common;

use Tmdb\Model\AbstractModel;

/**
 * Class ExternalIds
 * @package Tmdb\Model\Common
 */
class ExternalIds extends AbstractModel
{
    private $imdbId;
    private $freebaseId;
    private $freebaseMid;
    private $id;
    private $tvdbId;
    private $tvrageId;

    public static $properties = array(
        'imdb_id',
        'freebase_id',
        'freebase_mid',
        'id',
        'tvdb_id',
        'tvrage_id',
    );

    /**
     * @param  mixed $freebaseId
     * @return $this
     */
    public function setFreebaseId($freebaseId)
    {
        $this->freebaseId = $freebaseId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFreebaseId()
    {
        return $this->freebaseId;
    }

    /**
     * @param  mixed $freebaseMid
     * @return $this
     */
    public function setFreebaseMid($freebaseMid)
    {
        $this->freebaseMid = $freebaseMid;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFreebaseMid()
    {
        return $this->freebaseMid;
    }

    /**
     * @param  mixed $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  mixed $imdbId
     * @return $this
     */
    public function setImdbId($imdbId)
    {
        $this->imdbId = $imdbId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getImdbId()
    {
        return $this->imdbId;
    }

    /**
     * @param  mixed $tvdbId
     * @return $this
     */
    public function setTvdbId($tvdbId)
    {
        $this->tvdbId = $tvdbId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTvdbId()
    {
        return $this->tvdbId;
    }

    /**
     * @param  mixed $tvrageId
     * @return $this
     */
    public function setTvrageId($tvrageId)
    {
        $this->tvrageId = $tvrageId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTvrageId()
    {
        return $this->tvrageId;
    }
}
