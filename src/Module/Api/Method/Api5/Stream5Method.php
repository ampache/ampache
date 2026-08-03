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

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Database\Query\Random;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Redirects to the stream url for a media file.
 *
 * Version 5 reports the object id as `id`, does not know about the `stats` parameter and never
 * caches the stream, so it keeps a method of its own.
 */
final class Stream5Method implements MethodInterface
{
    public const string ACTION = 'stream';

    public function __construct(
        private ModelFactoryInterface $modelFactory,
    ) {}

    /**
     * stream
     * MINIMUM_API_VERSION=400001
     *
     * Streams a given media file.
     * Takes the file id in parameter with optional max bit rate, file format, time offset, size and estimate content length option.
     * Search and Playlist will only stream a random object not the whole thing
     *
     * id = (string) $song_id|$podcast_episode_id
     * type = (string) 'song', 'podcast_episode', 'search', 'playlist', 'podcast'
     * bitrate = (integer) max bitrate for transcoding in bytes (e.g 192000=192Kb) // Song only
     * format = (string) 'mp3', 'ogg', etc use 'raw' to skip transcoding // Song only
     * offset = (integer) time offset in seconds
     * length = (integer) 0,1 // ask for an estimated Content-Length; unreliable unless the transcode is cached
     *
     * @param array{
     *     id: string,
     *     type: string,
     *     bitrate?: int,
     *     format?: string,
     *     offset?: int,
     *     length?: int,
     *     stats?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 5 $apiVersion
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
        foreach (['id', 'type'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $type      = (string) $input['type'];
        $object_id = (int) $input['id'];

        $maxBitRate    = (int) ($input['bitrate'] ?? 0);
        $format        = $input['format'] ?? null; // mp3, flv or raw
        $transcode_to  = $format && $format != 'raw';
        $timeOffset    = $input['offset'] ?? null;
        $contentLength = (int) ($input['length'] ?? 0); // Force content-length guessing if transcode

        $params = '&client=api';
        if ($contentLength == 1) {
            $params .= '&content_length=required';
        }

        if ($transcode_to && in_array($type, ['song', 'search', 'playlist'])) {
            $params .= '&format=' . $format;
        }

        if ($maxBitRate > 0 && in_array($type, ['song', 'search', 'playlist'])) {
            $params .= '&bitrate=' . $maxBitRate;
        }

        if ($timeOffset) {
            $params .= '&frame=' . $timeOffset;
        }

        $url = '';
        if ($type == 'song') {
            $media = $this->modelFactory->createSong($object_id);
            $url   = $media->play_url($params, 'api', false, $user->id, $user->streamtoken);
        }

        if ($type == 'podcast_episode' || $type == 'podcast') {
            $media = $this->modelFactory->createPodcastEpisode($object_id);
            $url   = $media->play_url($params, 'api', false, $user->id, $user->streamtoken);
        }

        if ($type == 'search' || $type == 'playlist') {
            $song_id = Random::get_single_song($type, $user, $object_id);
            $media   = $this->modelFactory->createSong($song_id);
            $url     = $media->play_url($params, 'api', false, $user->id, $user->streamtoken);
        }

        if (!empty($url)) {
            Session::extend($input['auth'], AccessTypeEnum::API->value);

            return $response
                ->withStatus(302)
                ->withHeader('Location', str_replace(':443/play', '/play', $url));
        }

        // stream not found
        throw new ResultEmptyException((string) $object_id, 'id');
    }
}
