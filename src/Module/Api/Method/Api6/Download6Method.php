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

namespace Ampache\Module\Api\Method\Api6;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Database\Query\Random;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Redirects to the play url for a media file
 *
 * Api version 8 replaced this with a download that can also serve zip archives, so this is the
 * version 6 shape only: it hands back a redirect to the stream.
 */
final class Download6Method implements MethodInterface
{
    public const string ACTION = 'download';

    private ConfigContainerInterface $configContainer;
    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
    ) {
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Downloads a given media file. set format=raw to download the full file
     * Search and Playlist will only stream a random object not the whole thing
     *
     * id      = (string) $song_id|$podcast_episode_id|$search_id|$playlist_id
     * type    = (string) 'song', 'podcast_episode', 'search', 'playlist'
     * bitrate = (integer) max bitrate for transcoding in bytes //optional SONG ONLY
     * format  = (string) 'mp3', 'ogg', etc use 'raw' to skip transcoding //optional SONG ONLY
     * stats   = (integer) 0,1, if false disable stat recording (default: 1) //optional
     *
     * @param array{
     *     filter?: string,
     *     id?: string,
     *     type?: string,
     *     bitrate?: int,
     *     format?: string,
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
        $filter = $input['filter'] ?? $input['id'] ?? null;
        if ($filter === null) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'id')
            );
        }

        if (!array_key_exists('type', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'type')
            );
        }

        $objectId = (int) $filter;
        $type     = (string) $input['type'];

        // The API can use searches as playlists so check for those too
        if (
            $objectId === 0
            && ($type === 'playlist' || $type === 'search')
        ) {
            $objectId = (int) str_replace('smart_', '', (string) $filter);
            $type     = 'search';
        }

        $maxBitRate  = (int) ($input['bitrate'] ?? 0);
        $format      = $input['format'] ?? null; // mp3, flv or raw
        $recordStats = (int) ($input['stats'] ?? 1);
        $params      = '&client=api&action=download';

        if ($this->configContainer->get('api_always_download') || $recordStats === 0) {
            $params .= '&cache=1';
        }

        if ($format && in_array($type, ['song', 'search', 'playlist'])) {
            $params .= '&format=' . $format;
        }

        if ($format !== 'raw' && $maxBitRate > 0 && in_array($type, ['song', 'search', 'playlist'])) {
            $params .= '&bitrate=' . $maxBitRate;
        }

        $media = match ($type) {
            'song' => $this->modelFactory->createSong($objectId),
            'podcast_episode', 'podcast' => $this->modelFactory->createPodcastEpisode($objectId),
            'search', 'playlist' => $this->modelFactory->createSong(
                Random::get_single_song($type, $user, $objectId)
            ),
            default => null,
        };

        $url = ($media !== null)
            ? $media->play_url($params, 'api', false, $user->getId(), $user->streamtoken)
            : '';

        if (empty($url)) {
            // download not found
            throw new ResultEmptyException(
                (string) $objectId
            );
        }

        // ApiHandler extends the session for every MethodInterface handler, so it is not done here

        return $response
            ->withStatus(302)
            ->withHeader('Location', str_replace(':443/play', '/play', $url));
    }
}
