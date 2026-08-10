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
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserActivityRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class FriendsTimeline4Method implements MethodInterface
{
    public const string ACTION = 'friends_timeline';

    public function __construct(
        private UserActivityRepositoryInterface $useractivityRepository,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * friends_timeline
     * MINIMUM_API_VERSION=380001
     *
     * This get current user friends timeline
     *
     * limit = (integer) //optional
     * since = (integer) UNIXTIME() //optional
     *
     * @param array{
     *     limit?: int,
     *     since?: int,
     *     api_format: string,
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
        if (AmpConfig::get('sociable')) {
            $limit = (int) ($input['limit'] ?? 0);
            $since = (int) ($input['since'] ?? 0);

            if ($user->id > 0) {
                $results = $this->useractivityRepository->getActivities($user->id, $limit, $since);
                ob_end_clean();

                return $response->withBody(
                    $this->streamFactory->createStream(
                        $output->timeline($apiVersion, $results)
                    )
                );
            }
        } else {
            debug_event(self::class, 'Sociable feature is not enabled.', 3);
        }

        return $response;
    }
}
