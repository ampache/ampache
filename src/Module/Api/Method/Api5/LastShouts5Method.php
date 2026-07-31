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
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShoutRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Returns the latest posted shouts.
 *
 * Version 5 requires the `username`, only ever looks a user up by name and renders an empty list
 * rather than an empty result, so it keeps a method of its own.
 */
final class LastShouts5Method implements MethodInterface
{
    public const string ACTION = 'last_shouts';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private ShoutRepositoryInterface $shoutRepository,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * last_shouts
     * MINIMUM_API_VERSION=380001
     *
     * This get the latest posted shouts
     *
     * username = (string) $username //optional
     * limit = (integer) $limit Default: 10 (popular_threshold) //optional
     *
     * @param array{
     *     username?: string,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 5 $apiVersion
     * @throws AccessDeniedException
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

        $limit = (int) ($input['limit'] ?? 0);
        if ($limit < 1) {
            $limit = (int) ($this->configContainer->get(ConfigurationKeyEnum::POPULAR_THRESHOLD) ?? 10);
        }

        // without a username you get your own shouts
        $username = (!empty($input['username']))
            ? $input['username']
            : $user->username;

        $results = iterator_to_array($this->shoutRepository->getTop($limit, $username));

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->shouts($apiVersion, $results)
            )
        );
    }
}
