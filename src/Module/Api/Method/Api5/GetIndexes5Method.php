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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\System\Preference;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Returns an index of ids and names for the requested object type.
 *
 * Version 5 sorts by name and ignores the `sort` and `cond` parameters that the later versions
 * understand, so it keeps a method of its own.
 */
final class GetIndexes5Method implements MethodInterface
{
    public const string ACTION = 'get_indexes';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private ModelFactoryInterface $modelFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * get_indexes
     * MINIMUM_API_VERSION=400001
     * CHANGED_IN_API_VERSION=5.0.0
     *
     * This takes a collection of inputs and returns ID + name for the object type
     * Add 'include' to allow indexing all song tracks (enabled for xml by default)
     *
     * type = (string) 'song', 'album', 'artist', 'album_artist', 'playlist', 'podcast', 'podcast_episode', 'share', 'video', 'live_stream'
     * filter = (string) //optional
     * hide_search = (integer) 0,1, if true do not include searches/smartlists in the result //optional
     * exact = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * add = $browse->set_api_filter(date) //optional
     * update = $browse->set_api_filter(date) //optional
     * include = (integer) 0,1 include songs if available for that object //optional
     * offset = (integer) //optional
     * limit = (integer) //optional
     *
     * @param array{
     *     type?: string,
     *     filter?: string,
     *     hide_search?: int,
     *     exact?: int,
     *     add?: string,
     *     update?: string,
     *     include?: int,
     *     offset?: int,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 5 $apiVersion
     *
     * @throws AccessDeniedException
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

        $albumArtist = ((string) $input['type'] == 'album_artist');
        $type        = ($albumArtist) ? 'artist' : (string) $input['type'];

        if (
            !$this->configContainer->get(ConfigurationKeyEnum::ALLOW_VIDEO)
            && $type == 'video'
        ) {
            throw new AccessDeniedException(
                'Enable: video'
            );
        }

        if (
            !$this->configContainer->get(ConfigurationKeyEnum::PODCAST)
            && ($type == 'podcast' || $type == 'podcast_episode')
        ) {
            throw new AccessDeniedException(
                'Enable: podcast'
            );
        }

        if (
            !$this->configContainer->get(ConfigurationKeyEnum::SHARE)
            && $type == 'share'
        ) {
            throw new AccessDeniedException(
                'Enable: share'
            );
        }

        if (
            !$this->configContainer->get(ConfigurationKeyEnum::RADIO)
            && $type == 'live_stream'
        ) {
            throw new AccessDeniedException(
                'Enable: live_stream'
            );
        }

        $include = (array_key_exists('include', $input) && (int) $input['include'] == 1);
        $hide    = (array_key_exists('hide_search', $input) && (int) $input['hide_search'] == 1)
            || (bool) $this->configContainer->get('hide_search');

        // confirm the correct data
        if (!in_array(strtolower($type), ['song', 'album', 'artist', 'album_artist', 'playlist', 'podcast', 'podcast_episode', 'share', 'video', 'live_stream'])) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->error(
                        $apiVersion,
                        ErrorCodeEnum::BAD_REQUEST,
                        sprintf('Bad Request: %s', $type),
                        self::ACTION,
                        'type'
                    )
                )
            );
        }

        $browse = $this->modelFactory->createBrowse(null, false);
        $browse->set_user_id($user);

        if (
            $type === 'playlist'
            && $hide === false
        ) {
            $browse->set_type('playlist_search');
        } elseif ($albumArtist) {
            $browse->set_type('album_artist');
        } else {
            $browse->set_type($type);
        }

        // hide playlists starting with the user string (if enabled)
        $hideString = ($type === 'playlist')
            ? str_replace(
                '%',
                '\%',
                str_replace('_', '\_', (string) Preference::get_by_user($user->id, 'api_hidden_playlists'))
            )
            : '';
        if (!empty($hideString)) {
            $browse->set_filter('not_starts_with', $hideString);
        }

        $browse->set_sort('name', 'ASC', false);

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

        $results = $browse->get_objects();
        if ($results === []) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->writeEmpty($apiVersion, $type)
                )
            );
        }

        $output->setOffset($apiVersion, (int) ($input['offset'] ?? 0));
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->indexes($apiVersion, $results, $type, $user, $input['auth'], true, $include)
            )
        );
    }
}
