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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\MethodInterface;
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
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Verifies a new handshake and hands back a session token.
 *
 * Version 5 keeps its own version gate and does not report a stream token, so it keeps a method
 * of its own.
 */
final class Handshake5Method implements MethodInterface
{
    public const string ACTION = 'handshake';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private NetworkCheckerInterface $networkChecker,
        private StreamFactoryInterface $streamFactory,
        private UserRepositoryInterface $userRepository,
        private UserTrackerInterface $userTracker,
    ) {}

    /**
     * handshake
     * MINIMUM_API_VERSION=380001
     *
     * This is the function that handles verifying a new handshake
     * Takes a timestamp, auth key, and username.
     *
     * auth = (string) $passphrase
     * user = (string) $username //optional
     * timestamp = (integer) UNIXTIME() //Required if login/password authentication
     * version = (string) $version //optional
     *
     * @param array{
     *     user?: string,
     *     timestamp?: int,
     *     version?: string,
     *     client?: string,
     *     geo_latitude?: float,
     *     geo_longitude?: float,
     *     geo_name?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 5 $apiVersion
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        $now_time   = time();
        $timestamp  = (int) preg_replace('/[^0-9]/', '', (string) ($input['timestamp'] ?? $now_time));
        $passphrase = $input['auth'];
        if (empty($passphrase)) {
            $passphrase = Core::get_post('auth');
        }

        $username = trim((string) ($input['user'] ?? Session::username($passphrase)));
        $user_ip  = Core::get_user_ip();

        // set the version to the old string for old api clients
        $version       = (isset($input['version'])) ? $input['version'] : Api5::$version;
        Api5::$version = ((int) $version >= 350001) ? Api5::$version_numeric : Api5::$version;
        $data_version  = (int) substr((string) $version, 0, 1);

        // Version check shouldn't be soo restrictive... only check with initial version to not break clients compatibility
        if ((int) ($version) < Api5::$auth_version && $data_version !== 5) {
            debug_event(self::class, 'Login Failed: Version too old', 1);
            AmpError::add('api', 'Login failed, API version is too old');

            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->error(
                        $apiVersion,
                        ErrorCodeEnum::INVALID_HANDSHAKE,
                        'Received Invalid Handshake' . ' - ' . 'Login failed, API version is too old',
                        self::ACTION,
                        'version'
                    )
                )
            );
        }

        $exists       = false;
        $usePassword  = false;
        $user_id      = -1;

        // Grab the correct userid
        if (!$username) {
            $client = $this->userRepository->findByApiKey(trim($passphrase));
        } elseif (Session::exists('api', $input['auth'])) {
            $client = User::get_from_username($username);
            $exists = true;
        } else {
            $client      = User::get_from_username($username);
            $usePassword = true;
        }

        if ($client instanceof User) {
            $user_id = $client->id;
        }

        // Log this attempt
        debug_event(self::class, "Login$data_version Attempt, IP: $user_ip Time: $timestamp User: " . ($client->username ?? '') . " ($user_id)", 1);

        if (
            $user_id > 0 && $this->networkChecker->check(AccessTypeEnum::API, $user_id, AccessLevelEnum::GUEST)
        ) {
            // Authentication with user/password, we still need to check the password
            if ($usePassword) {
                // If the timestamp isn't within 30 minutes sucks to be them
                if (
                    ($timestamp < ($now_time - 1800))
                    || ($timestamp > ($now_time + 1800))
                ) {
                    debug_event(self::class, 'Login Failed: timestamp out of range ' . $timestamp . '/' . $now_time, 1);
                    AmpError::add('api', 'Login failed, timestamp is out of range');

                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $apiVersion,
                                ErrorCodeEnum::INVALID_HANDSHAKE,
                                'Received Invalid Handshake' . ' - ' . 'Login failed, timestamp is out of range' . ' (timestamp: ' . $timestamp . ' ' . 'Server' . ': ' . $now_time . ')',
                                self::ACTION,
                                'account'
                            )
                        )
                    );
                }

                // Now we're sure that there is an ACL line that matches this user or ALL USERS, pull the user's password and then see what we come out with
                $realpwd = $this->userRepository->retrievePasswordFromUser($client?->getId() ?? 0);

                if (!$realpwd) {
                    debug_event(self::class, 'Unable to find user with userid of ' . $user_id, 1);
                    AmpError::add('api', 'Incorrect username or password');

                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $apiVersion,
                                ErrorCodeEnum::INVALID_HANDSHAKE,
                                'Received Invalid Handshake' . ' - ' . 'Incorrect username or password',
                                self::ACTION,
                                'account'
                            )
                        )
                    );
                }

                $sha1pass = hash('sha256', $timestamp . $realpwd);

                if ($sha1pass !== $passphrase) {
                    $client = null;
                }
            }

            if ($client instanceof User) {
                if ($exists) {
                    Session::extend($input['auth'], AccessTypeEnum::API->value);
                    $token = $input['auth'];
                } else {
                    $token = $this->createSession($client, $data_version, $input);
                }

                // We're about to start. Record this user's IP.
                if ($this->configContainer->get(ConfigurationKeyEnum::TRACK_USER_IP)) {
                    $this->userTracker->trackIpAddress($client, 'handshake');
                }

                debug_event(self::class, 'Login Success, passphrase matched', 1);

                return $response->withBody(
                    $this->streamFactory->createStream(
                        $output->keyedArray($apiVersion, Api5::server_details($token))
                    )
                );
            } // match
        }

        debug_event(self::class, 'Login Failed, unable to match passphrase', 1);

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::INVALID_HANDSHAKE,
                    'Received Invalid Handshake' . ' - ' . 'Incorrect username or password',
                    self::ACTION,
                    'account'
                )
            )
        );
    }

    /**
     * Create the session
     *
     * @param array<string, mixed> $input
     */
    private function createSession(User $client, int $dataVersion, array $input): string
    {
        $data             = [];
        $data['username'] = (string) $client->username;
        $data['type']     = 'api';
        $data['apikey']   = (string) $client->apikey;
        $data['value']    = $dataVersion;
        if (isset($input['client'])) {
            $data['agent'] = scrub_in((string) $input['client']);
        }

        if (isset($input['geo_latitude'])) {
            $data['geo_latitude'] = $input['geo_latitude'];
        }

        if (isset($input['geo_longitude'])) {
            $data['geo_longitude'] = $input['geo_longitude'];
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
}
