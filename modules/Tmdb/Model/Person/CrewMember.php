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
namespace Tmdb\Model\Person;

use Tmdb\Model\Collection\People\PersonInterface;

/**
 * Class CrewMember
 * @package Tmdb\Model\Person
 */
class CrewMember extends AbstractMember implements PersonInterface
{
    /**
     * @var string
     */
    private $department;

    /**
     * @var string
     */
    private $job;

    /**
     * @var mixed
     */
    private $creditId;

    public static $properties = array(
        'id',
        'credit_id',
        'name',
        'department',
        'job',
        'profile_path'
    );

    /**
     * @param  mixed $department
     * @return $this
     */
    public function setDepartment($department)
    {
        $this->department = $department;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * @param  mixed $job
     * @return $this
     */
    public function setJob($job)
    {
        $this->job = $job;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param  mixed $creditId
     * @return $this
     */
    public function setCreditId($creditId)
    {
        $this->creditId = $creditId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreditId()
    {
        return $this->creditId;
    }
}
