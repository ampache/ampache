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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Module\Api\Api;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class Artists3Method implements MethodInterface
{
    public const string ACTION = 'artists';

    public function __construct(
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * artists
     * This takes a collection of inputs and returns
     * artist objects. This function is deprecated!
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
     *     cond?: string,
     *     sort?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 3 $apiVersion
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        $browse = Api::getBrowse($user);
        $browse->set_type('artist');
        $browse->set_sort('name', 'ASC', false);

        $method = (array_key_exists('exact', $input) && (int) $input['exact'] == 1) ? 'exact_match' : 'alpha_match';
        $browse->set_api_filter($method, $input['filter'] ?? '');
        $browse->set_api_filter('add', $input['add'] ?? '');
        $browse->set_api_filter('update', $input['update'] ?? '');

        // Set the offset
        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        $results = $browse->get_objects();
        $include = [];
        if (array_key_exists('include', $input)) {
            if (!is_array($input['include'])) {
                $input['include'] = explode(',', html_entity_decode((string) ($input['include'])));
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
        // echo out the resulting xml document
        ob_end_clean();

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->artists($apiVersion, $results, $include, $user, $input['auth'])
            )
        );
    }
}
