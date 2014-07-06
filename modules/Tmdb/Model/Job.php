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

/**
 * Class Job
 * @package Tmdb\Model
 */
class Job extends AbstractModel
{
    public static $properties = array(
        'department',
        'job_list'
    );

    /**
     * @var string
     */
    private $department;

    /**
     * @var array
     */
    private $jobList;

    /**
     * @param  string $department
     * @return $this
     */
    public function setDepartment($department)
    {
        $this->department = $department;
    }

    /**
     * @return string
     */
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * @param  array $jobList
     * @return $this
     */
    public function setJobList(array $jobList)
    {
        $this->jobList = $jobList;
    }

    /**
     * @return array
     */
    public function getJobList()
    {
        return $this->jobList;
    }
}
