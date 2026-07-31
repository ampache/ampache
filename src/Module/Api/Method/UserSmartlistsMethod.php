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
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns the smartlists owned by the current user.
 *
 * The parameters and checks are identical for api versions 6 and 8, so a single method serves
 * both. Only the output data is version specific and that is resolved by the ApiOutputInterface.
 */
final class UserSmartlistsMethod implements MethodInterface
{
    public const string ACTION = 'user_smartlists';

    public function __construct(
        private ModelFactoryInterface $modelFactory,
    ) {}

    /**
     * MINIMUM_API_VERSION=6.3.0
     *
     * Get the smartlists (searches) for the current user
     *
     * filter  = (string) Alpha-numeric search term //optional
     * exact   = (integer) 0,1, if true filter is exact rather than fuzzy //optional
     * offset  = (integer) //optional
     * limit   = (integer) //optional
     * include = (integer) 0,1 include the songs in the smartlist //optional
     * cond    = (string) Apply additional filters to the browse using ';' separated comma string pairs //optional
     * sort    = (string) sort name or comma separated key pair. Order default 'ASC' (name, ASC) //optional
     *
     * @param array{
     *     filter?: string,
     *     exact?: int,
     *     offset?: int,
     *     limit?: int,
     *     include?: int|string,
     *     cond?: string,
     *     sort?: string,
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
        $include = (isset($input['include']) && ((int) $input['include'] === 1 || $input['include'] === 'songs'));

        $browse = $this->modelFactory->createBrowse(null, false);

        $browse->set_user_id($user);

        $browse->set_type('smartplaylist');

        $browse->set_sort_order(html_entity_decode((string) ($input['sort'] ?? '')), ['name', 'ASC']);

        $method = (array_key_exists('exact', $input) && (int) $input['exact'] == 1)
            ? 'exact_match'
            : 'alpha_match';

        $browse->set_api_filter($method, $input['filter'] ?? '');

        $browse->set_filter('playlist_user', $user->getId());

        $browse->set_conditions(html_entity_decode((string) ($input['cond'] ?? '')));

        // smartlist ids are prefixed to keep them distinct from playlist ids
        $results = array_values(preg_filter('/^/', 'smart_', $browse->get_objects()));
        if ($results === []) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, 'playlist')
            );

            return $response;
        }

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        $response->getBody()->write(
            $output->playlists($apiVersion, $results, $user, $input['auth'], $include)
        );

        return $response;
    }
}
