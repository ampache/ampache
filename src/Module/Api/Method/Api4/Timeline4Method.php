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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\System\Preference;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserActivityRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class Timeline4Method implements MethodInterface
{
    public const string ACTION = 'timeline';

    public function __construct(
        private UserActivityRepositoryInterface $useractivityRepository,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * timeline
     * MINIMUM_API_VERSION=380001
     *
     * This gets a user timeline from their username
     *
     * username = (string)
     * limit = (integer) //optional
     * since = (integer) UNIXTIME() //optional
     *
     * @param array{
     *     username: string,
     *     limit?: int,
     *     since?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 4 $apiVersion
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        unset($user);
        if (AmpConfig::get('sociable')) {
            if (!Api4::check_parameter($input, ['username'], self::ACTION)) {
                return $response;
            }
            $username = $input['username'];
            $limit    = (int) ($input['limit'] ?? 0);
            $since    = (int) ($input['since'] ?? 0);

            if (!empty($username)) {
                $user = User::get_from_username($username);
                if (
                    $user instanceof User
                    && Preference::get_by_user($user->id, 'allow_personal_info_recent')
                ) {
                    $results = $this->useractivityRepository->getActivities(
                        $user->id,
                        $limit,
                        $since
                    );
                    ob_end_clean();

                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->timeline($apiVersion, $results)
                        )
                    );
                }
            }
        } else {
            debug_event(self::class, 'Sociable feature is not enabled.', 3);
        }

        return $response;
    }
}
