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
namespace Tmdb\Model\Collection\People;

use Tmdb\Model\Collection\People;
use Tmdb\Model\Person;

/**
 * Class Crew
 * @package Tmdb\Model\Collection\People
 */
class Crew extends People
{
    /**
     * Returns all people
     *
     * @return Person[]
     */
    public function getCrew()
    {
        return parent::getPeople();
    }

    /**
     * Retrieve a crew member from the collection
     *
     * @param $id
     * @return Person
     */
    public function getCrewMember($id)
    {
        return parent::getPerson($id);
    }
}
