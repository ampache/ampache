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
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Adds a song to a playlist
 *
 * This method is deprecated and will be removed in **API7** (Use playlist_add). It only exists in
 * api version 6.
 */
final class PlaylistAddSong6Method implements MethodInterface
{
    public const string ACTION = 'playlist_add_song';

    public const string REST_ACTION = 'playlist_add_song_edit';

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
     * MINIMUM_API_VERSION=380001
     *
     * This adds a song to a playlist
     *
     * filter = (string) UID of playlist
     * song   = (string) UID of song to add to playlist
     * check  = (integer) 0,1 Check for duplicates //optional, default = 0
     *
     * @param array{
     *     filter?: string,
     *     song?: string,
     *     id?: string,
     *     check?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessFailedException|RequestParamMissingException
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

        $song = $input['id'] ?? $input['song'] ?? null;
        if ($song === null) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'song')
            );
        }

        $playlist = $this->modelFactory->createPlaylist((int) $input['filter']);
        $songId   = (int) $song;

        if (!$playlist->has_collaborate($user)) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::ADMIN->value)
            );
        }

        $checkDuplicates = $this->configContainer->get(ConfigurationKeyEnum::UNIQUE_PLAYLIST)
            || (array_key_exists('check', $input) && (int) $input['check'] === 1);

        if ($checkDuplicates && $playlist->has_item($songId)) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $songId),
                    self::ACTION,
                    'duplicate'
                )
            );

            return $response;
        }

        $playlist->add_songs([$songId]);

        $response->getBody()->write(
            $output->success($apiVersion, 'song added to playlist')
        );

        return $response;
    }
}
