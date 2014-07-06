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

use Tmdb\Model\Common\GenericCollection;

/**
 * Class Jobs
 * @package Tmdb\Model\Collection
 */
class Jobs extends GenericCollection
{
    /**
     * Filter by department
     *
     * @param  string $department
     * @return $this
     */
    public function filterByDepartment($department)
    {
        $result = $this->filter(
            function ($key, $value) use ($department) {
                if ($value->getDepartment() == $department) {
                    return true;
                }
            }
        );

        if ($result && 1 === count($result)) {
            $results = $result->toArray();

            return array_shift($results);
        }

        return $result;
    }

    /**
     * Filter by department and return the jobs collection
     *
     * @param  string $department
     * @return $this
     */
    public function filterByDepartmentAndReturnJobsList($department)
    {
        $result = $this->filter(
            function ($key, $value) use ($department) {
                if ($value->getDepartment() == $department) {
                    return true;
                }
            }
        );

        if ($result && 1 === count($result)) {
            $results = $result->toArray();
            $data =  array_shift($results);

            return $data->getJobList();
        }

        return $result;
    }

    /**
     * Filter by job
     *
     * @param  string $filterByJob
     * @return $this
     */
    public function filterByJob($filterByJob)
    {
        $result = $this->filter(
            function ($key, $value) use ($filterByJob) {
                $jobList = $value->getJobList();

                foreach ($jobList as $job) {
                    if ($filterByJob == $job) {
                        return true;
                    }
                }
            }
        );

        if ($result && 1 === count($result)) {
            $results = $result->toArray();

            return array_shift($results);
        }

        return $result;
    }
}
