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
namespace Tmdb\Api;

/**
 * Class Credits
 * @package Tmdb\Api
 * @see http://docs.themoviedb.apiary.io/#credits
 */
class Credits extends AbstractApi
{
    /**
     * Get the detailed information about a particular credit record.
     *
     * This is currently only supported with the new credit model found in TV.
     * These ids can be found from any TV credit response as well as
     * the tv_credits and combined_credits methods for people.
     *
     * The episodes object returns a list of episodes and are generally going to be guest stars.
     * The season array will return a list of season numbers.
     *
     * Season credits are credits that were marked with the "add to every season" option in the editing interface
     * and are assumed to be "season regulars".
     *
     * @param $credit_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getCredit($credit_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('credit/' . $credit_id, $parameters, $headers);
    }
}
