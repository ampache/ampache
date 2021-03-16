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
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Psr\Http\Message\ResponseInterface;
use Teapot\StatusCode;

final class DownloadMethod implements MethodInterface
{
    public const ACTION = 'download';

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory
    ) {
        $this->modelFactory = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Downloads a given media file. set format=raw to download the full file
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array<string, mixed> $input
     * id     = (string) $song_id| $podcast_episode_id
     * type   = (string) 'song', 'podcast_episode'
     * format = (string) 'mp3', 'ogg', etc //optional
     *
     * @return ResponseInterface
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        if (count(array_diff(['id', 'type'], array_keys($input))) !== 0) {
            return $response->withStatus(StatusCode::NOT_FOUND);
        }

        $type = (string) $input['type'];

        if (in_array($type, ['song', 'podcast_episode'])) {
            $format    = $input['format'] ?? '';
            $original  = $format && $format != 'raw';

            $params = '&action=download&client=api&cache=1';
            if ($original && $type === 'song') {
                $params .= '&transcode_to=' . $format;
            }
            if ($format && $type === 'song') {
                $params .= '&format=' . $format;
            }

            /** @var Song|Podcast_Episode $media */
            $media = $this->modelFactory->mapObjectType($type, (int) $input['id']);
            $url   = $media->play_url(
                $params,
                'api',
                function_exists('curl_version'),
                $gatekeeper->getUser()->getId()
            );

            if ($url !== '') {
                return $response->withStatus(StatusCode::FOUND)
                    ->withHeader(
                        'Location',
                        str_replace(':443/play', '/play', $url)
                    );
            }
        }

        return $response->withStatus(StatusCode::NOT_FOUND);
    }
}
