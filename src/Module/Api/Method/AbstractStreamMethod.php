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
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Database\Query\Random;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Redirects to the stream url for a media file
 *
 * The two live api versions only differ in how they name the object id: version 6 reports it as
 * `id` and version 8 as `filter`, each accepting the other as an alias. The version classes supply
 * that pair of names; everything else is shared.
 */
abstract class AbstractStreamMethod implements MethodInterface
{
    public const string ACTION = 'stream';

    // the alias the version prefers when both names are supplied; overridden per version
    protected const string FILTER_ALIAS = 'id';

    // the name the version reports the object id under; overridden per version
    protected const string FILTER_KEY = 'filter';

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
     * Streams a given media file.
     * Takes the file id in parameter with optional max bit rate, file format, time offset, size and
     * estimate content length option.
     * Search and Playlist will only stream a random object not the whole thing
     *
     * id      = (string) $song_id|$podcast_episode_id|$search_id|$playlist_id
     * type    = (string) 'song', 'podcast_episode', 'search', 'playlist'
     * bitrate = (integer) max bitrate for transcoding in bytes // Song only
     * format  = (string) 'mp3', 'ogg', etc use 'raw' to skip transcoding // Song only
     * offset  = (integer) time offset in seconds
     * length  = (integer) 0,1 // ask for an estimated Content-Length; unreliable unless the transcode is cached
     * stats   = (integer) 0,1, if false disable stat recording (default: 1) //optional
     *
     * @param array{
     *     filter?: string,
     *     id?: string,
     *     type?: string,
     *     bitrate?: int,
     *     format?: string,
     *     offset?: int,
     *     length?: int,
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
        $filter = $input[static::FILTER_ALIAS] ?? $input[static::FILTER_KEY] ?? null;
        if ($filter === null) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', static::FILTER_KEY)
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

        $maxBitRate    = (int) ($input['bitrate'] ?? 0);
        $format        = $input['format'] ?? null; // mp3, flv or raw
        $transcodeTo   = ($format && $format !== 'raw');
        $timeOffset    = $input['offset'] ?? null;
        $contentLength = (int) ($input['length'] ?? 0); // Force content-length guessing if transcode
        $recordStats   = (int) ($input['stats'] ?? 1);

        $params = '&client=api';
        if ($this->configContainer->get('api_always_download') || $recordStats === 0) {
            $params .= '&cache=1';
        }

        if ($contentLength === 1) {
            $params .= '&content_length=required';
        }

        if ($transcodeTo && in_array($type, ['song', 'search', 'playlist'])) {
            $params .= '&format=' . $format;
        }

        if ($maxBitRate > 0 && in_array($type, ['song', 'search', 'playlist'])) {
            $params .= '&bitrate=' . $maxBitRate;
        }

        if ($timeOffset) {
            $params .= '&frame=' . $timeOffset;
        }

        $media = match ($type) {
            'song' => $this->modelFactory->createSong($objectId),
            'podcast_episode', 'podcast' => $this->modelFactory->createPodcastEpisode($objectId),
            'search', 'playlist' => $this->modelFactory->createSong(
                Random::get_single_song($type, $user, $objectId)
            ),
            default => null,
        };

        $url = $media?->play_url($params, 'api', false, $user->getId(), $user->streamtoken) ?? '';
        if ($url === '') {
            // stream not found
            throw new ResultEmptyException(
                (string) $objectId
            );
        }

        return $response
            ->withStatus(302)
            ->withHeader('Location', str_replace(':443/play', '/play', $url));
    }
}
