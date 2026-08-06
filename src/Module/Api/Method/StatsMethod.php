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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Database\Query\Random;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\System\Preference;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns items based on simple search types and filters (random by default)
 */
final class StatsMethod implements MethodInterface
{
    public const string ACTION = 'stats';

    /** @var string[] */
    private const array TYPES = ['song', 'album', 'album_disk', 'artist', 'video', 'playlist', 'podcast', 'podcast_episode'];

    private AlbumRepositoryInterface $albumRepository;
    private ArtistRepositoryInterface $artistRepository;
    private BrowseFactoryInterface $browseFactory;
    private ConfigContainerInterface $configContainer;
    private ModelFactoryInterface $modelFactory;

    public function __construct(
        AlbumRepositoryInterface $albumRepository,
        ArtistRepositoryInterface $artistRepository,
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
        BrowseFactoryInterface $browseFactory,
    ) {
        $this->albumRepository   = $albumRepository;
        $this->artistRepository  = $artistRepository;
        $this->configContainer   = $configContainer;
        $this->modelFactory      = $modelFactory;
        $this->browseFactory     = $browseFactory;
    }

    /**
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=5.0.0
     *
     * Get some items based on some simple search types and filters. (Random by default)
     *
     * type     = (string) 'song', 'album', 'artist', 'video', 'playlist', 'podcast', 'podcast_episode'
     * filter   = (string) 'newest', 'highest', 'frequent', 'recent', 'forgotten', 'flagged', 'random' (Default: random) //optional
     * user_id  = (string) //optional
     * username = (string) //optional
     * offset   = (integer) //optional
     * limit    = (integer) Default: 10 (popular_threshold) //optional
     * cond     = (string) Apply additional filters to the browse using ';' separated comma string pairs //optional
     * sort     = (string) sort name or comma separated key pair. Order default 'ASC' //optional
     *
     * @param array{
     *     type?: string,
     *     filter?: string,
     *     user_id?: int,
     *     username?: string,
     *     offset?: int,
     *     limit?: int,
     *     cond?: string,
     *     sort?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessDeniedException|RequestParamMissingException
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

        $type   = (string) $input['type'];
        $offset = (int) ($input['offset'] ?? 0);
        $limit  = (int) ($input['limit'] ?? 0);
        if ($limit === 0) {
            $limit = (int) $this->configContainer->get(ConfigurationKeyEnum::POPULAR_THRESHOLD);
        }

        // do you allow video?
        if (
            $type === 'video'
            && !$this->configContainer->get(ConfigurationKeyEnum::ALLOW_VIDEO)
        ) {
            throw new AccessDeniedException(
                'Enable: video'
            );
        }

        if (
            ($type === 'podcast' || $type === 'podcast_episode')
            && !$this->configContainer->get(ConfigurationKeyEnum::PODCAST)
        ) {
            throw new AccessDeniedException(
                'Enable: podcast'
            );
        }

        // confirm the correct data (album_disk is api version 8 only)
        if (
            !in_array(strtolower($type), self::TYPES)
            || ($apiVersion < 8 && strtolower($type) === 'album_disk')
        ) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $type),
                    self::ACTION,
                    'type'
                )
            );

            return $response;
        }

        // override your user if you're looking at others
        if (array_key_exists('username', $input) && User::get_from_username($input['username'])) {
            $user = User::get_from_username($input['username']);
        } elseif (array_key_exists('user_id', $input)) {
            $userTwo = $this->modelFactory->createUser((int) $input['user_id']);
            if (!$userTwo->isNew()) {
                $user = $userTwo;
            }
        }

        if ($user->isNew()) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', 'user'),
                    self::ACTION,
                    'type'
                )
            );

            return $response;
        }

        $userId  = $user->getId();
        $filter  = $input['filter'] ?? '';
        $results = [];

        // the named filters apply the offset and limit to the query itself, so the output must not
        // page them a second time. the random lookups are the exception and stay paged by the output.
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
                $threshold = (int) $this->configContainer->get(ConfigurationKeyEnum::STATS_THRESHOLD);
                $results   = Stats::get_top($type, $limit, $threshold, $offset);
                $offset    = 0;
                $limit     = 0;
                break;
            case 'recent':
            case 'forgotten':
                $newest  = $filter === 'recent';
                $results = (array_key_exists('username', $input) || array_key_exists('user_id', $input))
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
                $results = $this->getRandom($type, $user, $userId, $limit, $input);
        }

        if ($results === []) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, $type)
            );

            return $response;
        }

        // allow sorting results
        if (isset($input['sort']) || isset($input['cond'])) {
            $outputBrowse = $this->browseFactory->create(null, false);
            $outputBrowse->set_user_id($user);
            $outputBrowse->set_type($type);
            $outputBrowse->set_filter('id', $results);

            if (isset($input['sort'])) {
                $outputBrowse->set_sort_order(html_entity_decode((string) $input['sort']), ['', '']);
            }

            if (isset($input['cond'])) {
                $outputBrowse->set_conditions(html_entity_decode((string) $input['cond']));
            }

            $results = $outputBrowse->get_objects();
        }

        $output->setOffset($apiVersion, $offset);
        $output->setLimit($apiVersion, $limit);

        $auth = $input['auth'];

        // the type guard above accepts any casing, but only the canonical names render a result
        $response->getBody()->write(
            match ($type) {
                'song' => $output->songs($apiVersion, $results, $user, $auth),
                'artist' => $output->artists($apiVersion, $results, [], $user, $auth),
                'album' => $output->albums($apiVersion, $results, [], $user, $auth),
                'album_disk' => $output->albumDisks($apiVersion, $results, [], $user, $auth),
                'playlist' => $output->playlists($apiVersion, $results, $user, $auth),
                'video' => $output->videos($apiVersion, $results, $user, $auth),
                'podcast' => $output->podcasts($apiVersion, $results, $user, $auth),
                'podcast_episode' => $output->podcastEpisodes($apiVersion, $results, $user, $auth),
                default => '',
            }
        );

        return $response;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<int|string>
     */
    private function getRandom(string $type, User $user, int $userId, int $limit, array $input): array
    {
        switch ($type) {
            case 'song':
                return Random::get_default($limit, $user);
            case 'artist':
                return $this->artistRepository->getRandom($userId, $limit);
            case 'album':
                return $this->albumRepository->getRandom($userId, $limit);
            case 'album_disk':
                return $this->albumRepository->getRandomAlbumDisk($userId, $limit);
            case 'playlist':
                $browse = $this->browseFactory->create(null, false);
                $browse->set_user_id($user);
                $browse->set_type('playlist_search');
                $browse->set_sort('rand', null, false);
                $browse->set_filter('playlist_open', $userId);

                $hideString = str_replace(
                    '%',
                    '\%',
                    str_replace('_', '\_', (string) Preference::get_by_user($userId, 'api_hidden_playlists'))
                );
                if (!empty($hideString)) {
                    $browse->set_filter('not_starts_with', $hideString);
                }

                return $browse->get_objects();
            default:
                $browse = $this->browseFactory->create(null, false);
                $browse->set_user_id($user);
                $browse->set_type($type);
                $browse->set_sort('rand', null, false);
                $browse->set_conditions(html_entity_decode((string) ($input['cond'] ?? '')));

                return $browse->get_objects();
        }
    }
}
