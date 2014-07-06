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
use Tmdb\Exception\MissingSessionTokenException;
use Tmdb\SessionToken;

/**
 * Class GuestSession
 * @package Tmdb\Api
 * @see http://docs.themoviedb.apiary.io/#guestsessions
 */
class GuestSession extends AbstractApi
{
    /**
     * Get a list of rated movies for a specific guest session id.
     *
     * @param  array                        $parameters
     * @param  array                        $headers
     * @throws MissingSessionTokenException when the guest session token was not set on the client.
     * @return mixed
     */
    public function getRatedMovies(array $parameters = array(), array $headers = array())
    {
        $sessionToken = $this->client->getSessionToken();

        if (!$sessionToken instanceof SessionToken) {
            throw new MissingSessionTokenException('The guest session token was not set on the client.');
        }

        return $this->get('guest_session/' . $sessionToken->getToken() . '/rated_movies', $parameters, $headers);
    }
}
