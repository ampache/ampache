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
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Searches for a song using text info and records a play if found
 */
final class ScrobbleMethod implements MethodInterface
{
    public const string ACTION = 'scrobble';

    private ConfigContainerInterface $configContainer;
    private ModelFactoryInterface $modelFactory;
    private UserRepositoryInterface $userRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
        UserRepositoryInterface $userRepository,
    ) {
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
        $this->userRepository  = $userRepository;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Search for a song using text info and then record a play if found.
     * This allows other sources to record play history to Ampache
     *
     * song       = (string) $song_name
     * artist     = (string) $artist_name
     * album      = (string) $album_name
     * songmbid   = (string) $song_mbid //optional
     * artistmbid = (string) $artist_mbid //optional
     * albummbid  = (string) $album_mbid //optional
     * date       = (integer) UNIXTIME() //optional
     * client     = (string) $agent //optional
     *
     * @param array{
     *     song?: string,
     *     artist?: string,
     *     album?: string,
     *     songmbid?: string,
     *     song_mbid?: string,
     *     artistmbid?: string,
     *     artist_mbid?: string,
     *     albummbid?: string,
     *     album_mbid?: string,
     *     date?: int,
     *     client?: string,
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
        foreach (['song', 'artist', 'album'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $charset    = (string) ($this->configContainer->get(ConfigurationKeyEnum::SITE_CHARSET) ?? 'UTF-8');
        $songName   = html_entity_decode(scrub_out((string) $input['song']), ENT_QUOTES, $charset);
        $artistName = html_entity_decode(scrub_out((string) $input['artist']), ENT_QUOTES, $charset);
        $albumName  = html_entity_decode(scrub_out((string) $input['album']), ENT_QUOTES, $charset);
        $songMbid   = html_entity_decode(scrub_out((string) ($input['song_mbid'] ?? $input['songmbid'] ?? '')), ENT_QUOTES, $charset);
        $artistMbid = html_entity_decode(scrub_out((string) ($input['artist_mbid'] ?? $input['artistmbid'] ?? '')), ENT_QUOTES, $charset);
        $albumMbid  = html_entity_decode(scrub_out((string) ($input['album_mbid'] ?? $input['albummbid'] ?? '')), ENT_QUOTES, $charset);
        $date       = (array_key_exists('date', $input)) ? (int) scrub_in((string) $input['date']) : time();
        $userId     = $user->getId();

        // validate supplied user
        if (!in_array($userId, $this->userRepository->getValid())) {
            throw new ResultEmptyException(
                (string) $userId,
                'empty'
            );
        }

        // validate minimum required options
        debug_event(self::class, 'scrobble searching for:' . $songName . ' - ' . $artistName . ' - ' . $albumName, 4);
        if (!$songName || !$albumName || !$artistName) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    'Bad Request',
                    self::ACTION,
                    'input'
                )
            );

            return $response;
        }

        // validate client string or fall back to 'api'
        $agent      = scrub_in((string) ($input['client'] ?? 'api'));
        $scrobbleId = Song::can_scrobble($songName, $artistName, $albumName, $songMbid, $artistMbid, $albumMbid);

        if ($scrobbleId === '') {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::NOT_FOUND,
                    'Not Found',
                    self::ACTION,
                    'song'
                )
            );

            return $response;
        }

        $media = $this->modelFactory->createSong((int) $scrobbleId);
        if ($media->isNew()) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::NOT_FOUND,
                    sprintf('Not Found: %s', $scrobbleId),
                    self::ACTION,
                    'song'
                )
            );

            return $response;
        }

        debug_event(self::class, 'scrobble: ' . $media->getId() . ' for ' . $user->username . ' using ' . $agent . ' ' . $date, 5);

        // internal scrobbling (user_activity and object_count tables)
        if ($media->set_played($userId, $agent, [], $date)) {
            // scrobble plugins
            User::save_mediaplay($user, $media);
        }

        $response->getBody()->write(
            $output->success($apiVersion, 'successfully scrobbled: ' . $scrobbleId)
        );

        return $response;
    }
}
