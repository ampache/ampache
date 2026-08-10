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
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Session;
use Ampache\Module\User\Tracking\UserTrackerInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Reports the server status and the api version it is compatible with
 *
 * This is a public method: it can be called without being authenticated, so it must not rely on the
 * user handed to it.
 */
final class PingMethod implements MethodInterface
{
    public const string ACTION = 'ping';

    private ConfigContainerInterface $configContainer;
    private UserRepositoryInterface $userRepository;
    private UserTrackerInterface $userTracker;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UserRepositoryInterface $userRepository,
        UserTrackerInterface $userTracker,
    ) {
        $this->configContainer = $configContainer;
        $this->userRepository  = $userRepository;
        $this->userTracker     = $userTracker;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This can be called without being authenticated, it is useful for determining what the status
     * of the server is, and what version it is running/compatible with
     *
     * auth    = (string) //optional
     * version = (string) $version //optional
     *
     * @param array{
     *     auth?: string,
     *     version?: string,
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
        $version      = (isset($input['version'])) ? $input['version'] : Api::$version;
        Api::$version = ((int) $version >= 350001) ? Api::$version_numeric : Api::$version;
        $dataVersion  = (int) substr((string) $version, 0, 1);

        $results = [
            'server' => $this->configContainer->get('version'),
            'version' => Api::$version,
            'compatible' => '350001',
        ];

        // Check and see if we should extend the api sessions (done if valid session is passed)
        if (
            array_key_exists('auth', $input)
            && Session::exists(AccessTypeEnum::API->value, $input['auth'])
        ) {
            Session::extend($input['auth'], AccessTypeEnum::API->value);

            // perpetual sessions do not expire
            $perpetual     = (bool) $this->configContainer->get(ConfigurationKeyEnum::PERPETUAL_API_SESSION);
            $sessionExpire = ($perpetual)
                ? 0
                : date('c', time() + (int) ($this->configContainer->get('session_length') ?? 3600) - 60);

            if (in_array($dataVersion, Api::API_VERSIONS)) {
                Session::write($input['auth'], $dataVersion, $perpetual);
            }

            $results = array_merge(
                ['session_expire' => $sessionExpire],
                $results,
                Api::server_details($input['auth'])
            );

            $pingUser = $this->userRepository->findByApiKey($input['auth']);

            // We're about to start. Record this user's IP.
            if ($this->configContainer->get('track_user_ip') && $pingUser instanceof User) {
                $this->userTracker->trackIpAddress($pingUser, 'ping');
            }
        }

        debug_event(
            self::class,
            'Ping' . $dataVersion . ' Received from: ' . filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP),
            5
        );

        $response->getBody()->write(
            $output->keyedArray($apiVersion, $results)
        );

        return $response;
    }
}
