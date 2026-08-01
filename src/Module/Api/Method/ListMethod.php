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

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\System\Preference;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns a list of id/name pairs for a single object type.
 *
 * The parameters and checks are identical for api versions 6 and 8, so a single method serves
 * both. Only the output data is version specific and that is resolved by the ApiOutputInterface.
 */
final class ListMethod implements MethodInterface
{
    public const string ACTION = 'list';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private ModelFactoryInterface $modelFactory,
    ) {}

    /**
     * MINIMUM_API_VERSION=6.0.0
     *
     * This takes a named array of objects and returns the id and name for the object type
     *
     * type        = (string) 'song', 'album', 'artist', 'album_artist', 'song_artist', 'playlist', 'podcast', 'podcast_episode', 'share', 'video', 'live_stream'
     * filter      = (string) Alpha-numeric search term //optional
     * hide_search = (integer) 0,1, if true do not include searches/smartlists in the result //optional
     * exact       = (integer) 0,1, if true filter is exact rather than fuzzy //optional
     * add         = $browse->set_api_filter(date) //optional
     * update      = $browse->set_api_filter(date) //optional
     * offset      = (integer) //optional
     * limit       = (integer) //optional
     * cond        = (string) Apply additional filters to the browse using ';' separated comma string pairs //optional
     * sort        = (string) sort name or comma separated key pair. Order default 'ASC' (name, ASC) //optional
     *
     * @param array{
     *     type?: string,
     *     filter?: string,
     *     hide_search?: int,
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
        if (!array_key_exists('type', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'type')
            );
        }

        // the type is matched case insensitively, so the gate, the browse and the empty response
        // all work on the normalized name
        $requestedType = (string) $input['type'];
        $type          = strtolower($requestedType);

        $gate = ObjectTypeGate::check($this->configContainer, $type);
        if ($gate !== null) {
            $response->getBody()->write(
                $output->error($apiVersion, ErrorCodeEnum::ACCESS_DENIED, $gate, self::ACTION, 'system')
            );

            return $response;
        }

        if (!in_array($type, ObjectTypeGate::indexTypes($apiVersion))) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $requestedType),
                    self::ACTION,
                    'type'
                )
            );

            return $response;
        }

        $hide = (array_key_exists('hide_search', $input) && (int) $input['hide_search'] == 1)
            || AmpConfig::get('hide_search', false);

        $browse = $this->modelFactory->createBrowse(null, false);

        $browse->set_user_id($user);

        $nameType = $type;
        if ($type === 'playlist' && $hide === false) {
            $nameType = 'playlist_search';
            $browse->set_type('playlist_search');
        } else {
            $browse->set_type($type);
        }

        // hide playlists starting with the user string (if enabled)
        $hideString = ($type === 'playlist')
            ? str_replace('%', '\%', str_replace('_', '\_', (string) Preference::get_by_user($user->id, 'api_hidden_playlists')))
            : '';

        if ($hideString !== '') {
            $browse->set_filter('not_starts_with', $hideString);
        }

        $browse->set_sort_order(html_entity_decode((string) ($input['sort'] ?? '')), ['name', 'ASC']);

        $method = (array_key_exists('exact', $input) && (int) $input['exact'] == 1)
            ? 'exact_match'
            : 'alpha_match';

        $browse->set_api_filter($method, $input['filter'] ?? '');
        $browse->set_api_filter('add', $input['add'] ?? '');
        $browse->set_api_filter('update', $input['update'] ?? '');

        if ($type === 'playlist') {
            $browse->set_filter('playlist_open', $user->getId());
            if (
                $hide === false
                && (bool) Preference::get_by_user($user->getId(), 'api_hide_dupe_searches') === true
            ) {
                $browse->set_filter('hide_dupe_smartlist', 1);
            }
        }

        $browse->set_conditions(html_entity_decode((string) ($input['cond'] ?? '')));

        $objects = $browse->get_objects();
        if ($objects === []) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, $type)
            );

            return $response;
        }

        $sort    = $browse->get_sort();
        $results = Catalog::get_name_array($objects, $nameType, $sort['name'] ?? 'name', $sort['order'] ?? 'ASC');
        if ($results === []) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, 'list')
            );

            return $response;
        }

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        $response->getBody()->write(
            $output->lists($apiVersion, $results)
        );

        return $response;
    }
}
