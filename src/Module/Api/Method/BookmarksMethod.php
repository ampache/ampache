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

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns the bookmarks of the current user.
 *
 * The parameters and checks are identical for api versions 6 and 8, so a single method serves
 * both. Only the output data is version specific and that is resolved by the ApiOutputInterface.
 */
final class BookmarksMethod implements MethodInterface
{
    public const string ACTION = 'bookmarks';

    public function __construct(
        private BookmarkRepositoryInterface $bookmarkRepository,
    ) {}

    /**
     * MINIMUM_API_VERSION=5.0.0
     *
     * Get information about bookmarked media this user is allowed to manage
     *
     * client  = (string) filter by the agent/client name //optional
     * include = (integer) 0,1, if true include the object in the bookmark //optional
     *
     * @param array{
     *     client?: string,
     *     include?: int|bool,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 6|8 $apiVersion
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        $include = make_bool($input['include'] ?? false);

        $client = (string) ($input['client'] ?? '');

        $results = ($client !== '')
            ? $this->bookmarkRepository->getByUserAndComment($user, scrub_in($client))
            : $this->bookmarkRepository->getByUser($user);

        if ($results === []) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, 'bookmark')
            );

            return $response;
        }

        $response->getBody()->write(
            $output->bookmarks($apiVersion, $results, $input['auth'], $include)
        );

        return $response;
    }
}
