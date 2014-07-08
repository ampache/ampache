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

use Tmdb\Exception\UnauthorizedRequestTokenException;
use Tmdb\RequestToken;

/**
 * Class Authentication
 * @package Tmdb\Api
 * @see http://docs.themoviedb.apiary.io/#authentication
 */
class Authentication extends AbstractApi
{
    const REQUEST_TOKEN_URI = 'https://www.themoviedb.org/authenticate';

    /**
     * This method is used to generate a valid request token for user based authentication.
     * A request token is required in order to request a session id.
     *
     * You can generate any number of request tokens but they will expire after 60 minutes.
     * As soon as a valid session id has been created the token will be destroyed.
     *
     * @return mixed
     */
    public function getNewToken()
    {
        return $this->get('authentication/token/new');
    }

    /**
     * Redirect the user to authenticate the request token
     *
     * @param $token
     */
    public function authenticateRequestToken($token)
    {
        //@codeCoverageIgnoreStart
        header(sprintf(
            'Location: %s/%s',
            self::REQUEST_TOKEN_URI,
            $token
        ));
        //@codeCoverageIgnoreEnd
    }

    /**
     * This method is used to generate a session id for user based authentication.
     * A session id is required in order to use any of the write methods.
     *
     * @param  string                            $requestToken
     * @throws UnauthorizedRequestTokenException
     * @return mixed
     */
    public function getNewSession($requestToken)
    {
        if ($requestToken instanceof RequestToken) {
            $requestToken = $requestToken->getToken();
        }

        try {
            return $this->get('authentication/session/new', array('request_token' => $requestToken));

            //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            if ($e->getCode() == 401) {
                throw new UnauthorizedRequestTokenException("The request token has not been validated yet.");
            }
            //@codeCoverageIgnoreEnd
        }
    }

    /**
     * Helper method to validate the request_token and obtain a session_token
     *
     * @param $requestToken
     * @param $username
     * @param $password
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function getSessionTokenWithLogin($requestToken, $username, $password)
    {
        if ($requestToken instanceof RequestToken) {
            $requestToken = $requestToken->getToken();
        }

        $validatedRequestToken = $this->validateRequestTokenWithLogin($requestToken, $username, $password);

        if (!$validatedRequestToken['success']) {
            throw new \InvalidArgumentException('Unable to validate the request_token, please check your credentials.');
        }

        return $this->getNewSession($validatedRequestToken['request_token']);
    }

    /**
     * This method is used to generate a session id for user based authentication.
     * A session id is required in order to use any of the write methods.
     *
     * @param  string                            $requestToken
     * @param  string                            $username
     * @param  string                            $password
     * @throws UnauthorizedRequestTokenException
     * @return mixed
     */
    public function validateRequestTokenWithLogin($requestToken, $username, $password)
    {
        if ($requestToken instanceof RequestToken) {
            $requestToken = $requestToken->getToken();
        }

        try {
            return $this->get('authentication/token/validate_with_login', array(
                'username'      => $username,
                'password'      => $password,
                'request_token' => $requestToken
            ));
            //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            if ($e->getCode() == 401) {
                throw new UnauthorizedRequestTokenException("The request token has not been validated yet.");
            }
            //@codeCoverageIgnoreEnd
        }
    }

    /**
     * This method is used to generate a guest session id.
     *
     * A guest session can be used to rate movies without having a registered TMDb user account.
     * You should only generate a single guest session per user (or device)
     * as you will be able to attach the ratings to a TMDb user account in the future.
     *
     * There is also IP limits in place so you should always make sure it's the end user
     * doing the guest session actions.
     *
     * If a guest session is not used for the first time within 24 hours, it will be automatically discarded.
     *
     * @return mixed
     */
    public function getNewGuestSession()
    {
        return $this->get('authentication/guest_session/new');
    }
}
