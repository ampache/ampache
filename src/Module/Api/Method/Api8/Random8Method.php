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

namespace Ampache\Module\Api\Method\Api8;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Database\Query\Random;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\VideoRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Picks a random song, podcast episode or video and redirects to its stream url.
 */
final class Random8Method implements MethodInterface
{
    public const string ACTION = 'random';

    public function __construct(
        private ModelFactoryInterface $modelFactory,
        private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        private VideoRepositoryInterface $videoRepository,
    ) {}

    /**
     * MINIMUM_API_VERSION=800000
     *
     * API8 only. API6 is shared with Ampache7, which does not serve this action, so adding it there
     * would make the two servers disagree about the API6 surface.
     *
     * Picks a random song, podcast episode or video from the whole library and redirects (302) to its stream url.
     *
     * type = (string) 'album', 'album_artist', 'album_disk', 'artist', 'catalog', 'favorite', 'genre', 'label', 'playlist', 'podcast_episode', 'rating', 'search', 'song', 'song_artist', 'video' (default: song)
     * NOTE 'favorite' and 'rating' read the filter as a flag/star value rather than an object id:
     *      favorite: 1 (or omitted) = flagged, 0 = not flagged
     *      rating:   1-5 = that many stars or more, 0 = unrated, omitted = any rated song
     * filter = (string) $object_id of the album, artist, playlist, search or podcast to pick from //optional
     * bitrate = (integer) max bitrate for transcoding in bytes (e.g 192000=192Kb) //optional SONG ONLY
     * format = (string) 'mp3', 'ogg', etc use 'raw' to skip transcoding //optional SONG ONLY
     * offset = (integer) time offset in seconds //optional
     * stats = (integer) 0,1, if false disable stat recording when playing the object (default: 1) //optional
     *
     * @param array{
     *     filter?: string,
     *     type?: string,
     *     bitrate?: int,
     *     format?: string,
     *     offset?: int,
     *     stats?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws RequestParamMissingException|ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        $object_type = (string) ($input['type'] ?? 'song');
        if (!in_array($object_type, ['album', 'album_artist', 'album_disk', 'artist', 'catalog', 'favorite', 'genre', 'label', 'playlist', 'podcast_episode', 'rating', 'search', 'song', 'song_artist', 'video'], true)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'type')
            );
        }

        $object_id = $input['filter'] ?? null;
        if ($object_type === 'playlist' && ((int) $object_id) === 0) {
            $object_id   = str_replace('smart_', '', (string) $object_id);
            $object_type = 'search';
        }

        $objectId = match ($object_type) {
            'album', 'album_artist', 'album_disk', 'artist', 'catalog', 'favorite', 'genre', 'label', 'playlist', 'rating', 'search', 'song', 'song_artist' => Random::get_single_song($object_type, $user, ($object_id === null) ? null : (int) $object_id),
            // a filter picks a random episode from that single podcast, otherwise the whole library is used
            'podcast_episode' => (((int) $object_id) > 0)
                ? $this->podcastEpisodeRepository->getRandomByPodcast((int) $object_id, $user->getId(), 1)[0] ?? 0
                : $this->podcastEpisodeRepository->getRandom($user->getId(), 1)[0] ?? 0,
            'video' => $this->videoRepository->getRandom($user->getId(), 1)[0] ?? 0,
        };

        if ($objectId === 0) {
            throw new ResultEmptyException($object_type);
        }

        $maxBitRate  = (int) ($input['bitrate'] ?? 0);
        $format      = $input['format'] ?? null; // mp3, flv or raw
        $timeOffset  = $input['offset'] ?? null;
        $recordStats = (int) ($input['stats'] ?? 1);

        $params = '&client=api';
        if (AmpConfig::get('api_always_download') || $recordStats == 0) {
            $params .= '&cache=1';
        }
        if ($object_type === 'song' && $format && $format !== 'raw') {
            $params .= '&format=' . $format;
        }
        if ($object_type === 'song' && $maxBitRate > 0) {
            $params .= '&bitrate=' . $maxBitRate;
        }
        if ($timeOffset) {
            $params .= '&frame=' . $timeOffset;
        }

        // every container type resolves to a random song from within it; `artist` accepts either artist credit,
        // where `song_artist` and `album_artist` narrow it to one
        $media = match ($object_type) {
            'album', 'album_artist', 'album_disk', 'artist', 'catalog', 'favorite', 'genre', 'label', 'playlist', 'rating', 'search', 'song', 'song_artist' => $this->modelFactory->createSong($objectId),
            'podcast_episode' => $this->modelFactory->createPodcastEpisode($objectId),
            'video' => $this->modelFactory->createVideo($objectId),
        };

        $url = $media->play_url($params, 'api', false, $user->getId(), $user->streamtoken);
        if ($url === '') {
            throw new ResultEmptyException((string) $objectId);
        }

        return $response
            ->withStatus(302)
            ->withHeader('Location', str_replace(':443/play', '/play', $url));
    }
}
