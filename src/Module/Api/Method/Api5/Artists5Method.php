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

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Returns the artists of the server.
 *
 * Version 5 sorts by name and ignores the `sort` and `cond` parameters that the later versions
 * understand, so it keeps a method of its own.
 */
final class Artists5Method implements MethodInterface
{
    public const string ACTION = 'artists';

    public function __construct(
        private BrowseFactoryInterface $browseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This takes a collection of inputs and returns artists that match
     *
     * filter       = (string) Alpha-numeric search term //optional
     * exact        = (integer) 0,1, if true filter is exact rather than fuzzy //optional
     * add          = $browse->set_api_filter(date) //optional
     * update       = $browse->set_api_filter(date) //optional
     * include      = (array|string) 'albums', 'songs' //optional
     * album_artist = (integer) 0,1, if true filter for album artists only //optional
     * offset       = (integer) //optional
     * limit        = (integer) //optional
     *
     * @param array{
     *     filter?: string,
     *     exact?: int,
     *     add?: string,
     *     update?: string,
     *     include?: string|string[],
     *     album_artist?: int,
     *     offset?: int,
     *     limit?: int,
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
        $album_artist = (array_key_exists('album_artist', $input) && (int) $input['album_artist'] == 1);

        $browse = $this->browseFactory->create(null, false);

        $browse->set_user_id($user);

        if ($album_artist) {
            $browse->set_type('album_artist');
        } else {
            $browse->set_type('artist');
        }

        $browse->set_sort('name', 'ASC', false);

        $method = (array_key_exists('exact', $input) && (int) $input['exact'] == 1)
            ? 'exact_match'
            : 'alpha_match';

        $browse->set_api_filter($method, $input['filter'] ?? '');
        $browse->set_api_filter('add', $input['add'] ?? '');
        $browse->set_api_filter('update', $input['update'] ?? '');

        $results = $browse->get_objects();
        if ($results === []) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->writeEmpty($apiVersion, 'artist')
                )
            );
        }

        $include = [];
        if (array_key_exists('include', $input)) {
            if (!is_array($input['include'])) {
                $input['include'] = explode(',', html_entity_decode((string) $input['include']));
            }

            foreach ($input['include'] as $item) {
                if ($item === 'songs' || $item == '1') {
                    $include[] = 'songs';
                }

                if ($item === 'albums' || $item == '1') {
                    $include[] = 'albums';
                }
            }
        }

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->artists($apiVersion, $results, $include, $user, $input['auth'])
            )
        );
    }
}
