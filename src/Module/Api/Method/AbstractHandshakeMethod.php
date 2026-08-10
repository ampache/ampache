<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\System\Session;
use Ampache\Module\User\Tracking\UserTrackerInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Verifies a new handshake and hands back a session token
 *
 * This is a public method: it can be called without being authenticated, so it must not rely on the
 * user handed to it.
 *
 * The two live api versions only differ in which client versions they will shake hands with, so the
 * version classes supply that gate; everything else is shared.
 */
abstract class AbstractHandshakeMethod implements MethodInterface
{
    public const string ACTION = 'handshake';

    private ConfigContainerInterface $configContainer;
    private NetworkCheckerInterface $networkChecker;
    private UserRepositoryInterface $userRepository;
    private UserTrackerInterface $userTracker;

    public function __construct(
        ConfigContainerInterface $configContainer,
        NetworkCheckerInterface $networkChecker,
        UserRepositoryInterface $userRepository,
        UserTrackerInterface $userTracker,
    ) {
        $this->configContainer = $configContainer;
        $this->networkChecker  = $networkChecker;
        $this->userRepository  = $userRepository;
        $this->userTracker     = $userTracker;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This is the function that handles verifying a new handshake.
     * Takes a timestamp, auth key, and username.
     *
     * auth      = (string) $passphrase
     * user      = (string) $username //optional
     * timestamp = (integer) UNIXTIME() //Required if login/password authentication
     * version   = (string) $version //optional
     *
     * @param array{
     *     auth?: string,
     *     user?: string,
     *     timestamp?: int,
     *     version?: string,
     *     client?: string,
     *     geo_latitude?: float,
     *     geo_longitude?: float,
     *     geo_name?: string,
     *     api_format: string,
     * } $input
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        $nowTime    = time();
        $timestamp  = (int) preg_replace('/[^0-9]/', '', (string) ($input['timestamp'] ?? $nowTime));
        $passphrase = (string) ($input['auth'] ?? '');
        if (empty($passphrase)) {
            $passphrase = Core::get_post('auth');
        }

        $username = trim((string) ($input['user'] ?? Session::username($passphrase)));
        $userIp   = Core::get_user_ip();

        // set the version to the old string for old api clients
        $version      = (isset($input['version'])) ? $input['version'] : Api::$version;
        Api::$version = ((int) $version >= 350001) ? Api::$version_numeric : Api::$version;
        $dataVersion  = $this->normalizeDataVersion((int) substr((string) $version, 0, 1));

        if (!$this->isSupportedVersion((string) $version, $dataVersion)) {
            debug_event(static::class, 'Login Failed: Version too old', 1);

            return $this->writeInvalidHandshake(
                $response,
                $output,
                $apiVersion,
                'Received Invalid Handshake',
                'version'
            );
        }

        $exists = false;
        $userId = -1;

        // Grab the correct userid
        if (!$username) {
            $client   = $this->userRepository->findByApiKey(trim($passphrase));
            $username = false;
        } elseif (Session::exists('api', (string) ($input['auth'] ?? ''))) {
            $client   = User::get_from_username($username);
            $username = false;
            $exists   = true;
        } else {
            $client = User::get_from_username($username);
        }

        if ($client instanceof User) {
            $userId = $client->id;
        }

        // Log this attempt
        debug_event(
            static::class,
            'Login' . $dataVersion . ' Attempt, IP: ' . $userIp . ' Time: ' . $timestamp . ' User: ' . ($client->username ?? '') . ' (' . $userId . ')',
            1
        );

        if (
            $userId > 0
            && $this->networkChecker->check(AccessTypeEnum::API, $userId, AccessLevelEnum::GUEST)
        ) {
            // Authentication with user/password, we still need to check the password
            if ($username) {
                // If the timestamp isn't within 30 minutes sucks to be them
                if (
                    ($timestamp < ($nowTime - 1800))
                    || ($timestamp > ($nowTime + 1800))
                ) {
                    debug_event(static::class, 'Login Failed: timestamp out of range ' . $timestamp . '/' . $nowTime, 1);
                    AmpError::add('api', 'Login failed, timestamp is out of range');

                    return $this->writeInvalidHandshake(
                        $response,
                        $output,
                        $apiVersion,
                        'Received Invalid Handshake - Login failed, timestamp is out of range (timestamp: ' . $timestamp . ' Server: ' . $nowTime . ')',
                        'account'
                    );
                }

                // Now we're sure there is an ACL line matching this user, pull their password
                $realpwd = $this->userRepository->retrievePasswordFromUser($client?->getId() ?? 0);
                if (!$realpwd) {
                    debug_event(static::class, 'Unable to find user with userid of ' . $userId, 1);
                    AmpError::add('api', 'Incorrect username or password');

                    return $this->writeInvalidHandshake(
                        $response,
                        $output,
                        $apiVersion,
                        'Received Invalid Handshake - Login failed, timestamp is out of range',
                        'account'
                    );
                }

                if (hash('sha256', $timestamp . $realpwd) !== $passphrase) {
                    $client = null;
                }
            }

            if ($client instanceof User) {
                $token = ($exists)
                    ? $this->extendSession((string) ($input['auth'] ?? ''))
                    : $this->createSession($client, $dataVersion, $input);

                // We're about to start. Record this user's IP.
                if ($this->configContainer->get('track_user_ip')) {
                    $this->userTracker->trackIpAddress($client, 'handshake');
                }

                debug_event(static::class, 'Login Success, passphrase matched', 1);

                $response->getBody()->write(
                    $output->keyedArray($apiVersion, Api::server_details($token))
                );

                return $response;
            }
        }

        debug_event(static::class, 'Login Failed, unable to match passphrase', 1);

        return $this->writeInvalidHandshake(
            $response,
            $output,
            $apiVersion,
            'Received Invalid Handshake - Incorrect username or password',
            'account'
        );
    }

    /**
     * Whether this api version will shake hands with a client on the given version
     */
    abstract protected function isSupportedVersion(string $version, int $dataVersion): bool;

    /**
     * Lets a version fold an unsupported client version onto one it does serve
     */
    protected function normalizeDataVersion(int $dataVersion): int
    {
        return $dataVersion;
    }

    /**
     * @param array<string, mixed> $input
     */
    private function createSession(User $client, int $dataVersion, array $input): string
    {
        $data = [
            'username' => (string) $client->username,
            'type' => 'api',
            'apikey' => (string) $client->apikey,
            'streamtoken' => (string) $client->streamtoken,
            'value' => $dataVersion,
        ];

        if (isset($input['client'])) {
            $data['agent'] = scrub_in((string) $input['client']);
        }

        if (isset($input['geo_latitude'])) {
            $data['geo_latitude'] = (float) $input['geo_latitude'];
        }

        if (isset($input['geo_longitude'])) {
            $data['geo_longitude'] = (float) $input['geo_longitude'];
        }

        if (isset($input['geo_name'])) {
            $data['geo_name'] = $input['geo_name'];
        }

        // Session might not exist or has expired
        if (!Session::read($data['apikey'])) {
            Session::destroy($data['apikey']);

            return Session::create($data);
        }

        Session::extend($data['apikey'], AccessTypeEnum::API->value);

        return $data['apikey'];
    }

    private function extendSession(string $auth): string
    {
        Session::extend($auth, AccessTypeEnum::API->value);

        return $auth;
    }

    private function writeInvalidHandshake(
        ResponseInterface $response,
        ApiOutputInterface $output,
        int $apiVersion,
        string $message,
        string $type,
    ): ResponseInterface {
        $response->getBody()->write(
            $output->error(
                $apiVersion,
                ErrorCodeEnum::INVALID_HANDSHAKE,
                $message,
                static::ACTION,
                $type
            )
        );

        return $response;
    }
}
