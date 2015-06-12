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
namespace Tmdb\Model\Collection;

use Tmdb\Model\Collection\People\Cast;
use Tmdb\Model\Collection\People\Crew;
use Tmdb\Model\Common\GenericCollection;

/**
 * Class CreditsCollection
 * @package Tmdb\Model\Collection
 */
class CreditsCollection
{
    /**
     * @var Cast
     */
    public $cast;

    /**
     * @var Crew
     */
    private $crew;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cast = new Cast();
        $this->crew = new Crew();
    }

    /**
     * @param  Cast|GenericCollection $cast
     * @return $this
     */
    public function setCast(GenericCollection $cast)
    {
        $this->cast = $cast;

        return $this;
    }

    /**
     * @return Cast
     */
    public function getCast()
    {
        return $this->cast;
    }

    /**
     * @param  Crew|GenericCollection $crew
     * @return $this
     */
    public function setCrew(GenericCollection $crew)
    {
        $this->crew = $crew;

        return $this;
    }

    /**
     * @return Crew
     */
    public function getCrew()
    {
        return $this->crew;
    }
}
