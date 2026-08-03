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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\System\Preference;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Returns the playlists (and, unless hidden, the smartlists) visible to the user.
 *
 * Version 5 sorts by name and ignores the `sort` and `cond` parameters that the later versions
 * understand, so it keeps a method of its own.
 */
final class Playlists5Method implements MethodInterface
{
    public const string ACTION = 'playlists';

    public function __construct(
        private BrowseFactoryInterface $browseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * playlists
     * MINIMUM_API_VERSION=380001
     *
     * This returns playlists based on the specified filter
     *
     * filter = (string) Alpha-numeric search term (match all if missing) //optional
     * hide_search = (integer) 0,1, if true do not include searches/smartlists in the result //optional
     * show_dupes = (integer) 0,1, if true ignore 'api_hide_dupe_searches' setting //optional
     * include = (integer) 0,1, if true include playlist contents //optional
     * exact = (integer) 0,1, if true filter is exact rather than fuzzy //optional
     * add = $browse->set_api_filter(date) //optional
     * update = $browse->set_api_filter(date) //optional
     * offset = (integer) //optional
     * limit = (integer) //optional
     *
     * @param array{
     *     filter?: string,
     *     hide_search?: int,
     *     show_dupes?: int,
     *     include?: int,
     *     exact?: int,
     *     add?: string,
     *     update?: string,
     *     offset?: int,
     *     limit?: int,
     *     cond?: string,
     *     sort?: string,
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
        $hide       = (array_key_exists('hide_search', $input) && (int) $input['hide_search'] == 1) || AmpConfig::get('hide_search', false);
        $show_dupes = (array_key_exists('show_dupes', $input))
            ? make_bool($input['show_dupes'])
            : (bool) Preference::get_by_user($user->getId(), 'api_hide_dupe_searches') === false;

        $browse = $this->browseFactory->create(null, false);

        $browse->set_user_id($user);

        if ($hide === false) {
            $browse->set_type('playlist_search');
        } else {
            $browse->set_type('playlist');
        }

        // hide playlists starting with the user string (if enabled)
        $hide_string = str_replace('%', '\%', str_replace('_', '\_', (string) Preference::get_by_user($user->id, 'api_hidden_playlists')));
        if (!empty($hide_string)) {
            $browse->set_filter('not_starts_with', $hide_string);
        }

        $browse->set_sort('name', 'ASC', false);

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

        $results = $browse->get_objects();
        if ($results === []) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->writeEmpty($apiVersion, 'playlist')
                )
            );
        }

        $output->setOffset($apiVersion, (int) ($input['offset'] ?? 0));
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->playlists($apiVersion, $results, $user, $input['auth'])
            )
        );
    }
}
