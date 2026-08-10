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
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\System\Preference;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Returns the playlists and smartlists visible to the user.
 */
final class Playlists4Method implements MethodInterface
{
    public const string ACTION = 'playlists';

    public function __construct(
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * @param array<string, mixed> $input
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
        $hide = (array_key_exists('hide_search', $input) && (int) $input['hide_search'] == 1)
            || AmpConfig::get('hide_search', false);

        $show_dupes = (array_key_exists('show_dupes', $input))
            ? make_bool($input['show_dupes'])
            : (bool) Preference::get_by_user($user->getId(), 'api_hide_dupe_searches') === false;

        $browse = Api::getBrowse($user);
        $browse->set_type(($hide === false) ? 'playlist_search' : 'playlist');
        $browse->set_sort_order(html_entity_decode((string) ($input['sort'] ?? '')), ['name', 'ASC']);

        $method = (array_key_exists('exact', $input) && (int) $input['exact'] == 1)
            ? 'exact_match'
            : 'alpha_match';
        $browse->set_api_filter($method, $input['filter'] ?? '');
        $browse->set_filter('playlist_open', $user->getId());

        if (
            $hide === false
            && $show_dupes === false
        ) {
            $browse->set_filter('hide_dupe_smartlist', 1);
        }

        $browse->set_conditions(html_entity_decode((string) ($input['cond'] ?? '')));
        $results = $browse->get_objects();

        $output->setOffset($apiVersion, (int) ($input['offset'] ?? 0));
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->playlists($apiVersion, $results, $user, $input['auth'])
            )
        );
    }
}
