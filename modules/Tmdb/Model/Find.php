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
namespace Tmdb\Model;

use Tmdb\Model\Collection\People;
use Tmdb\Model\Common\GenericCollection;

/**
 * Class Find
 * @package Tmdb\Model
 */
class Find extends AbstractModel
{
    /**
     * @var GenericCollection
     */
    private $movieResults;

    /**
     * @var People
     */
    private $personResults;

    /**
     * @var GenericCollection
     */
    private $tvResults;

    /**
     * @param  \Tmdb\Model\Common\GenericCollection $movieResults
     * @return $this
     */
    public function setMovieResults($movieResults)
    {
        $this->movieResults = $movieResults;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Common\GenericCollection
     */
    public function getMovieResults()
    {
        return $this->movieResults;
    }

    /**
     * @param  \Tmdb\Model\Collection\People $personResults
     * @return $this
     */
    public function setPersonResults($personResults)
    {
        $this->personResults = $personResults;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Collection\People
     */
    public function getPersonResults()
    {
        return $this->personResults;
    }

    /**
     * @param  \Tmdb\Model\Common\GenericCollection $tvResults
     * @return $this
     */
    public function setTvResults($tvResults)
    {
        $this->tvResults = $tvResults;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Common\GenericCollection
     */
    public function getTvResults()
    {
        return $this->tvResults;
    }
}
