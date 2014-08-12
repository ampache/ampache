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
namespace Tmdb\Repository;

use Tmdb\Exception\UnauthorizedRequestTokenException;
use Tmdb\Factory\AuthenticationFactory;
use Tmdb\RequestToken;

/**
 * Class AuthenticationRepository
 * @package Tmdb\Repository
 * @see http://docs.themoviedb.apiary.io/#authentication
 */
class AuthenticationRepository extends AbstractRepository
{
    /**
     * This method is used to generate a valid request token for user based authentication.
     * A request token is required in order to request a session id.
     *
     * You can generate any number of request tokens but they will expire after 60 minutes.
     * As soon as a valid session id has been created the token will be destroyed.
     *
     * @return RequestToken
     */
    public function getRequestToken()
    {
        $data  = $this->getApi()->getNewToken();

        return $this->getFactory()->createRequestToken($data);
    }

    /**
     * This method is used to generate a session id for user based authentication.
     * A session id is required in order to use any of the write methods.
     *
     * @param  RequestToken $requestToken
     * @return RequestToken
     */
    public function getSessionToken(RequestToken $requestToken)
    {
        $data  = $this->getApi()->getNewSession($requestToken->getToken());

        return $this->getFactory()->createSessionToken($data);
    }

    /**
     * This method is used to validate a request_token for user based authentication.
     * A request_token is required in order to use any of the write methods.
     *
     * @param  RequestToken                      $requestToken
     * @param  string                            $username
     * @param  string                            $password
     * @throws UnauthorizedRequestTokenException
     * @return mixed
     */
    public function validateRequestTokenWithLogin(RequestToken $requestToken, $username, $password)
    {
        $data = $this->getApi()->validateRequestTokenWithLogin(
            $requestToken,
            $username,
            $password
        );

        return $this->getFactory()->createRequestToken($data);
    }

    /**
     * This method is used to generate a session id for user based authentication.
     * A session id is required in order to use any of the write methods.
     *
     * @param  RequestToken                      $requestToken
     * @param  string                            $username
     * @param  string                            $password
     * @throws UnauthorizedRequestTokenException
     * @return mixed
     */
    public function getSessionTokenWithLogin(RequestToken $requestToken, $username, $password)
    {
        $data = $this->getApi()->getSessionTokenWithLogin(
            $requestToken,
            $username,
            $password
        );

        return $this->getFactory()->createSessionToken($data);
    }

    /**
     * This method is used to generate a guest session id.
     *
     * A guest session can be used to rate movies without having a registered TMDb user account.
     * You should only generate a single guest session per user (or device)
     * as you will be able to attach the ratings to a TMDb user account in the future.
     *
     * There is also IP limits in place so you should always make sure it's
     * the end user doing the guest session actions.
     *
     * If a guest session is not used for the first time within 24 hours,
     * it will be automatically discarded.
     *
     * @return RequestToken
     */
    public function getGuestSessionToken()
    {
        $data  = $this->getApi()->getNewGuestSession();

        return $this->getFactory()->createGuestSessionToken($data);
    }

    /**
     * Authenticate request token, redirects the user
     *
     * @param  RequestToken $requestToken
     * @return void
     */
    public function authenticateRequestToken(RequestToken $requestToken)
    {
        $this->getApi()->authenticateRequestToken($requestToken->getToken());
    }

    /**
     * Return the Collection API Class
     *
     * @return \Tmdb\Api\Authentication
     */
    public function getApi()
    {
        return $this->getClient()->getAuthenticationApi();
    }

    /**
     * @return AuthenticationFactory
     */
    public function getFactory()
    {
        return new AuthenticationFactory();
    }
}
