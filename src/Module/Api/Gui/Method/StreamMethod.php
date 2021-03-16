<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Module\Api\Gui\Method;

use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Teapot\StatusCode;

final class StreamMethod implements MethodInterface
{
    public const ACTION = 'stream';

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory
    ) {
        $this->modelFactory  = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Streams a given media file.
     * Takes the file id in parameter with optional max bit rate, file format, time offset, size and estimate content length option.
     *
     * @param array $input
     * id      = (string) $song_id|$podcast_episode_id
     * type    = (string) 'song', 'podcast'
     * bitrate = (integer) max bitrate for transcoding // Song only
     * format  = (string) 'mp3', 'ogg', etc use 'raw' to skip transcoding // Song only
     * offset  = (integer) time offset in seconds
     * length  = (integer) 0,1
     * @return boolean
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $type     = (string) ($input['type'] ?? '');
        $objectId = (int) ($input['id'] ?? 0);

        if ($type === '' || $objectId === 0) {
            return $response->withStatus(StatusCode::NOT_FOUND);
        }
        $userId = $gatekeeper->getUser()->getId();

        $maxBitRate    = (int) ($input['bitrate'] ?? 0);
        $format        = (string) ($input['format'] ?? ''); // mp3, flv or raw
        $original      = $format && $format != 'raw';
        $timeOffset    = (int) ($input['offset'] ?? 0);
        $contentLength = (int) ($input['length'] ?? 0); // Force content-length guessing if transcode

        $params = '&client=api';
        if ($contentLength === 1) {
            $params .= '&content_length=required';
        }
        if ($original && $type == 'song') {
            $params .= '&transcode_to=' . $format;
        }
        if ($maxBitRate > 0 && $type == 'song') {
            $params .= '&bitrate=' . $maxBitRate;
        }
        if ($timeOffset) {
            $params .= '&frame=' . $timeOffset;
        }

        $url = '';
        if ($type == 'song') {
            $media = $this->modelFactory->createSong($objectId);
            $url   = $media->play_url($params, 'api', function_exists('curl_version'), $userId);
        }
        if ($type == 'podcast') {
            $media = $this->modelFactory->createPodcastEpisode($objectId);
            $url   = $media->play_url($params, 'api', function_exists('curl_version'), $userId);
        }
        if ($url !== '') {
            return $response
                ->withStatus(StatusCode::FOUND)
                ->withHeader(
                    'Location',
                    str_replace(':443/play', '/play', $url)
                );
        }

        return $response->withStatus(StatusCode::NOT_FOUND);
    }
}
