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
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Class Albums5Method
 * @package Lib\Api5Methods
 */
final class Albums5Method implements MethodInterface
{
    public const string ACTION = 'albums';

    public function __construct(
        private ModelFactoryInterface $modelFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This returns albums based on the provided search filters
     *
     * filter = (string) Alpha-numeric search term //optional
     * exact = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * add = $browse->set_api_filter(date) //optional
     * update = $browse->set_api_filter(date) //optional
     * offset = (integer) //optional
     * limit = (integer) //optional
     * include = (array|string) 'songs' //optional
     *
     * @param array{
     *     filter?: string,
     *     include?: string|string[],
     *     exact?: int,
     *     add?: string,
     *     update?: string,
     *     offset?: int,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
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
        $browse = $this->modelFactory->createBrowse(null, false);
        $browse->set_user_id($user);
        $browse->set_type('album');
        $browse->set_sort('name', 'ASC', false);
        $method = (array_key_exists('exact', $input) && (int) $input['exact'] == 1) ? 'exact_match' : 'alpha_match';
        $browse->set_api_filter($method, $input['filter'] ?? '');
        $browse->set_api_filter('add', $input['add'] ?? '');
        $browse->set_api_filter('update', $input['update'] ?? '');

        $results = $browse->get_objects();
        if ($results === []) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, 'album')
            );

            return $response;
        }

        ob_end_clean();

        $include = [];
        if (array_key_exists('include', $input)) {
            if (is_array($input['include'])) {
                foreach ($input['include'] as $item) {
                    if ($item === 'songs' || $item == '1') {
                        $include[] = 'songs';
                    }
                }
            } elseif ($input['include'] === 'songs' || $input['include'] == '1') {
                $include[] = 'songs';
            }
        }

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        $result = $output->albums(
            $apiVersion,
            $results,
            $include,
            $user,
            $input['auth'],
        );

        return $response->withBody(
            $this->streamFactory->createStream(
                $result
            )
        );
    }
}
