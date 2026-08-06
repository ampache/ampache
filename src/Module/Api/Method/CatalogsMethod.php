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
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns the catalogs of the server.
 *
 * The parameters and checks are identical for api versions 6 and 8, so a single method serves
 * both. Only the output data is version specific and that is resolved by the ApiOutputInterface.
 */
final class CatalogsMethod implements MethodInterface
{
    public const string ACTION = 'catalogs';

    /**
     * The catalog types a `filter` may narrow the browse down to
     */
    private const array CATALOG_TYPES = [
        'music',
        'clip',
        'tvshow',
        'movie',
        'personal_video',
        'video',
        'podcast',
    ];

    /**
     * The video catalog types which are all stored as a single `video` gather type
     */
    private const array VIDEO_TYPES = [
        'clip',
        'tvshow',
        'movie',
        'personal_video',
    ];

    public function __construct(
        private BrowseFactoryInterface $browseFactory,
    ) {}

    /**
     * MINIMUM_API_VERSION=420000
     *
     * Get information about catalogs this user is allowed to manage
     *
     * filter = (string) set $filter_type to a gather type ('music', 'podcast', 'video') //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * cond   = (string) Apply additional filters to the browse using ';' separated comma string pairs //optional
     *
     * @param array{
     *     filter?: string,
     *     offset?: int,
     *     limit?: int,
     *     cond?: string,
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
        $browse = $this->browseFactory->create(null, false);

        $browse->set_user_id($user);

        $browse->set_type('catalog');

        $browse->set_filter('user', $user->getId());

        $filter = (string) ($input['filter'] ?? '');
        if (in_array($filter, self::CATALOG_TYPES, true)) {
            // all the video types are stored as a single `video` gather type
            if (in_array($filter, self::VIDEO_TYPES, true)) {
                $filter = 'video';
            }

            // filter for specific catalog types
            $browse->set_filter('gather_type', $filter);
        }

        $browse->set_conditions(html_entity_decode((string) ($input['cond'] ?? '')));

        $results = $browse->get_objects();
        if ($results === []) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, 'catalog')
            );

            return $response;
        }

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        $response->getBody()->write(
            $output->catalogs($apiVersion, $results, $user)
        );

        return $response;
    }
}
