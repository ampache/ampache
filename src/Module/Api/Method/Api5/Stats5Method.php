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
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Session;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Random;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Returns a list of objects based on a simple search type and filter. (Random by default)
 *
 * Version 5 sorts the random browses by `rand` and does not understand the `sort` and `cond`
 * parameters of the later versions, so it keeps a method of its own.
 */
final class Stats5Method implements MethodInterface
{
    public const string ACTION = 'stats';

    public function __construct(
        private AlbumRepositoryInterface $albumRepository,
        private ArtistRepositoryInterface $artistRepository,
        private ConfigContainerInterface $configContainer,
        private ModelFactoryInterface $modelFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * stats
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=5.0.0
     *
     * Get some items based on some simple search types and filters. (Random by default)
     * This method HAD partial backwards compatibility with older api versions but it has now been removed
     *
     * type = (string)  'song', 'album', 'artist', 'video', 'playlist', 'podcast', 'podcast_episode'
     * filter = (string)  'newest', 'highest', 'frequent', 'recent', 'forgotten', 'flagged', 'random' (Default: random) //optional
     * user_id = (integer) //optional
     * username = (string)  //optional
     * offset = (integer) //optional
     * limit = (integer) Default: 10 (popular_threshold) //optional
     *
     * @param array{
     *     type?: string,
     *     filter?: string,
     *     user_id?: int,
     *     username?: string,
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

        // the type is matched case insensitively, so everything below works on the normalized name
        $requested_type = (string) $input['type'];
        $type           = strtolower($requested_type);

        $offset = (int) ($input['offset'] ?? 0);
        $limit  = (int) ($input['limit'] ?? 0);
        if ($limit < 1) {
            $limit = (int) ($this->configContainer->get(ConfigurationKeyEnum::POPULAR_THRESHOLD) ?? 10);
        }

        // do you allow video?
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

        // confirm the correct data
        if (!in_array($type, ['song', 'album', 'artist', 'video', 'playlist', 'podcast', 'podcast_episode'])) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->error(
                        $apiVersion,
                        ErrorCodeEnum::BAD_REQUEST,
                        sprintf('Bad Request: %s', $requested_type),
                        self::ACTION,
                        'type'
                    )
                )
            );
        }

        $userId = $user->id;
        // override your user if you're looking at others
        if (
            array_key_exists('username', $input)
            && ($namedUser = User::get_from_username($input['username'])) instanceof User
        ) {
            $user   = $namedUser;
            $userId = $user->id;
        } elseif (array_key_exists('user_id', $input)) {
            $requestedUser = $this->modelFactory->createUser((int) $input['user_id']);
            if (!$requestedUser->isNew()) {
                $user   = $requestedUser;
                $userId = $requestedUser->id;
            }
        }

        $results = [];
        $filter  = $input['filter'] ?? '';
        switch ($filter) {
            case 'newest':
                $results = Stats::get_newest($type, $limit, $offset, 0, $user);
                $offset  = 0;
                $limit   = 0;
                break;
            case 'highest':
                $results = Rating::get_highest($type, $limit, $offset, $userId);
                $offset  = 0;
                $limit   = 0;
                break;
            case 'frequent':
                $threshold = (int) ($this->configContainer->get(ConfigurationKeyEnum::STATS_THRESHOLD) ?? 7);
                $results   = Stats::get_top($type, $limit, $threshold, $offset);
                $offset    = 0;
                $limit     = 0;
                break;
            case 'recent':
            case 'forgotten':
                $newest  = $filter == 'recent';
                $results = ($user->id)
                    ? $user->get_recently_played($type, $limit, $offset, $newest)
                    : Stats::get_recent($type, $limit, $offset, null, $newest);
                $offset = 0;
                $limit  = 0;
                break;
            case 'flagged':
                $results = Userflag::get_latest($type, $user, $limit, $offset);
                $offset  = 0;
                $limit   = 0;
                break;
            case 'random':
            default:
                switch ($type) {
                    case 'song':
                        $results = Random::get_default($limit, $user);
                        break;
                    case 'artist':
                        $results = $this->artistRepository->getRandom(
                            $userId,
                            $limit
                        );
                        break;
                    case 'album':
                        $results = $this->albumRepository->getRandom(
                            $userId,
                            $limit
                        );
                        break;
                    case 'playlist':
                        $browse = $this->modelFactory->createBrowse(null, false);
                        $browse->set_user_id($user);
                        $browse->set_type('playlist_search');
                        $browse->set_sort('rand', null, false);
                        $browse->set_filter('playlist_open', $user->getId());

                        $hideString = str_replace(
                            '%',
                            '\%',
                            str_replace('_', '\_', (string) Preference::get_by_user($user->getId(), 'api_hidden_playlists'))
                        );
                        if (!empty($hideString)) {
                            $browse->set_filter('not_starts_with', $hideString);
                        }

                        $results = $browse->get_objects();
                        break;
                    case 'video':
                    case 'podcast':
                    case 'podcast_episode':
                        $browse = $this->modelFactory->createBrowse(null, false);
                        $browse->set_user_id($user);
                        $browse->set_type($type);
                        $browse->set_sort('rand', null, false);
                        $results = $browse->get_objects();
                }
        }

        if (empty($results)) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->writeEmpty($apiVersion, $type)
                )
            );
        }

        $output->setOffset($apiVersion, $offset);
        $output->setLimit($apiVersion, $limit);

        $result = match ($type) {
            'song' => $output->songs($apiVersion, $results, $user, $input['auth']),
            'artist' => $output->artists($apiVersion, $results, [], $user, $input['auth']),
            'album' => $output->albums($apiVersion, $results, [], $user, $input['auth']),
            'playlist' => $output->playlists($apiVersion, $results, $user, $input['auth']),
            'video' => $output->videos($apiVersion, $results, $user, $input['auth']),
            'podcast' => $output->podcasts($apiVersion, $results, $user, $input['auth']),
            'podcast_episode' => $output->podcastEpisodes($apiVersion, $results, $user, $input['auth']),
        };

        if ($type === 'video') {
            Session::extend($input['auth'], AccessTypeEnum::API->value);
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $result
            )
        );
    }
}
