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

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Inform the server about the playback state of a media object.
 *
 * The parameters and checks are identical for api versions 6 and 8, so a single method serves
 * both. Only the output data is version specific and that is resolved by the ApiOutputInterface.
 */
final class PlayerMethod implements MethodInterface
{
    public const string ACTION = 'player';

    public const string REST_ACTION = 'playback';

    public function __construct(
        private ModelFactoryInterface $modelFactory,
    ) {}

    /**
     * MINIMUM_API_VERSION=6.4.0
     *
     * Inform the server about the state of your client player
     *
     * filter = (integer) object_id
     * type   = (string) 'song', 'podcast_episode', 'video' (DEFAULT 'song') //optional
     * state  = (string) 'play', 'stop' (DEFAULT 'play') //optional
     * time   = (integer) current song time in whole seconds //optional
     * client = (string) agent/client name //optional
     *
     * @param array{
     *     filter?: string,
     *     type?: string,
     *     state?: string,
     *     time?: string,
     *     client?: string,
     *     offset?: int,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 6|8 $apiVersion
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
        if (!array_key_exists('filter', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        $objectId = (int) $input['filter'];

        // both are matched case insensitively, so everything below works on the normalized names
        $requestedType  = (string) ($input['type'] ?? 'song');
        $requestedState = (string) ($input['state'] ?? 'play');
        $type           = strtolower($requestedType);
        $state          = strtolower($requestedState);

        // confirm the correct data
        if (!in_array($type, ['song', 'podcast_episode', 'video'])) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $requestedType),
                    self::ACTION,
                    'type'
                )
            );

            return $response;
        }

        if (!in_array($state, ['play', 'stop'])) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $requestedState),
                    self::ACTION,
                    'state'
                )
            );

            return $response;
        }

        $className = ObjectTypeToClassNameMapper::map($type);
        if ($className === $type || !$objectId) {
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

        $media = match ($type) {
            'song' => $this->modelFactory->createSong($objectId),
            'podcast_episode' => $this->modelFactory->createPodcastEpisode($objectId),
            'video' => $this->modelFactory->createVideo($objectId),
        };

        if ($media->isNew()) {
            throw new ResultEmptyException((string) $objectId);
        }

        $time     = time();
        $position = (array_key_exists('time', $input))
            ? (int) scrub_in((string) $input['time'])
            : 0;

        // validate client string or fall back to 'api'
        $agent = scrub_in((string) ($input['client'] ?? 'api'));

        if ($state === 'play') {
            // make sure the now_playing state is set
            Stream::garbage_collection();
            Stream::insert_now_playing(
                $media->id,
                $user->getId(),
                ($media->time - $position),
                (string) $user->username,
                $type,
                ($time - $position)
            );

            // internal scrobbling (user_activity and object_count tables)
            if (
                $media instanceof Song
                && $media->set_played($user->id, $agent, [], ($time - $position))
            ) {
                // scrobble plugins
                User::save_mediaplay($user, $media);
            }
        } else {
            // A stop/paused state isn't playing. Remove it.
            Stream::delete_now_playing((string) $user->username, $media->id, $type, $user->getId());
        }

        // return the now playing state for that user
        $results = Stream::get_now_playing($user->getId());
        if ($results === []) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, 'now_playing')
            );

            return $response;
        }

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        $response->getBody()->write(
            $output->nowPlaying($apiVersion, $results)
        );

        return $response;
    }
}
