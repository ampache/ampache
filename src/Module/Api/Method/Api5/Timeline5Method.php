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
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserActivityRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Returns a user's timeline.
 *
 * Version 5 requires the `username`, only ever looks a user up by name and does not exempt the
 * calling user from the privacy check, so it keeps a method of its own.
 */
final class Timeline5Method implements MethodInterface
{
    public const string ACTION = 'timeline';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private StreamFactoryInterface $streamFactory,
        private UserActivityRepositoryInterface $userActivityRepository,
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
     *     username?: string,
     *     limit?: int,
     *     since?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 5 $apiVersion
     * @throws AccessDeniedException|RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!$this->configContainer->get(ConfigurationKeyEnum::SOCIABLE)) {
            throw new AccessDeniedException(
                'Enable: sociable'
            );
        }

        if (!array_key_exists('username', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'username')
            );
        }

        $username = $input['username'];
        $limit    = (int) ($input['limit'] ?? 0);
        $since    = (int) ($input['since'] ?? 0);

        if (!empty($username)) {
            $leadUser = User::get_from_username($username);
            if (
                $leadUser instanceof User
                && (
                    // you can always see your own timeline, whatever the preference says
                    $leadUser->getId() === $user->getId()
                    || Preference::get_by_user($leadUser->id, 'allow_personal_info_recent')
                )
            ) {
                $results = $this->userActivityRepository->getActivities(
                    $leadUser->getId(),
                    $limit,
                    $since
                );

                return $response->withBody(
                    $this->streamFactory->createStream(
                        $output->timeline($apiVersion, $results)
                    )
                );
            }
        }

        return $response;
    }
}
