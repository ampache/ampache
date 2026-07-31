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
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns a single bookmark based on the UID of said bookmark.
 *
 * The parameters and checks are identical for api versions 6 and 8, so a single method serves
 * both. Only the output data is version specific and that is resolved by the ApiOutputInterface.
 */
final class BookmarkMethod implements MethodInterface
{
    public const string ACTION = 'bookmark';

    public function __construct(
        private BookmarkRepositoryInterface $bookmarkRepository,
    ) {}

    /**
     * MINIMUM_API_VERSION=6.6.0
     *
     * Get a single bookmark by bookmark_id
     *
     * filter  = (string) bookmark_id
     * include = (integer) 0,1, if true include the object in the bookmark //optional
     *
     * @param array{
     *     filter?: string,
     *     include?: int|bool,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 6|8 $apiVersion
     * @throws RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!array_key_exists('filter', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        $bookmark = $this->bookmarkRepository->findById((int) $input['filter']);
        if (
            $bookmark === null
            || !$bookmark->ownedByUser($user)
        ) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, null)
            );

            return $response;
        }

        $include = make_bool($input['include'] ?? false);

        $response->getBody()->write(
            $output->bookmarks($apiVersion, [$bookmark->getId()], $input['auth'], $include, false)
        );

        return $response;
    }
}
