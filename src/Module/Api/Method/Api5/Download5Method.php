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
 * Redirects to the download url for a media file.
 *
 * Version 5 reports the object id as `id` and always caches the download, so it keeps a method
 * of its own.
 */
final class Download5Method implements MethodInterface
{
    public const string ACTION = 'download';

    public function __construct(
        private ModelFactoryInterface $modelFactory,
    ) {}

    /**
     * download
     * MINIMUM_API_VERSION=400001
     *
     * Downloads a given media file. set format=raw to download the full file
     *
     * id = (string) $song_id| $podcast_episode_id
     * type = (string) 'song', 'podcast_episode', 'search', 'playlist'
     * format = (string) 'mp3', 'ogg', etc //optional
     *
     * @param array{
     *     id: string,
     *     type: string,
     *     bitrate?: int,
     *     format?: string,
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

        $object_id = (int) $input['id'];
        $type      = (string) $input['type'];
        $format    = $input['format'] ?? null; // mp3, flv or raw

        $params = '&client=api&action=download&cache=1';
        if ($format && in_array($type, ['song', 'search', 'playlist'])) {
            $params .= '&format=' . $format;
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

        // download not found
        throw new ResultEmptyException((string) $object_id, 'id');
    }
}
