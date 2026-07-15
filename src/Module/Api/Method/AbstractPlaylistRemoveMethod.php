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
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Removes an item from a playlist, by its id or by its track number
 *
 * `playlist_remove_song` and its replacement `playlist_remove` only differ in what they name the
 * item (`song` vs `id`), whether the lookup is type aware, and the wording of the cleared message.
 * The concrete classes supply those; everything else is shared.
 */
abstract class AbstractPlaylistRemoveMethod implements MethodInterface
{
    // the action reported in errors; overridden per method
    public const string ACTION = 'playlist_remove_song';

    // what the response calls the items when the whole playlist is cleared; overridden per method
    protected const string CLEARED_MESSAGE = 'all songs removed from playlist';
    // the parameter carrying the item to remove; overridden per method
    protected const string ITEM_KEY = 'song';

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory,
    ) {
        $this->modelFactory = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=400001
     * CHANGED_IN_API_VERSION=420000
     *
     * Removes an item from a playlist using its id or its track number in the list.
     * 420000+: added clear to allow you to clear a playlist without getting all the tracks.
     *
     * filter = (string) UID of playlist
     * track  = (string) track number to remove from the playlist //optional
     * clear  = (integer) 0,1 Clear the whole playlist //optional, default = 0
     *
     * @param array{
     *     filter?: string,
     *     song?: string,
     *     id?: string,
     *     type?: string,
     *     track?: string,
     *     clear?: int,
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

        $playlist = $this->modelFactory->createPlaylist((int) $input['filter']);
        if (!$playlist->has_collaborate($user)) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::ADMIN->value)
            );
        }

        if (array_key_exists('clear', $input) && (int) $input['clear'] === 1) {
            $playlist->delete_all();

            $response->getBody()->write(
                $output->success($apiVersion, static::CLEARED_MESSAGE)
            );

            return $response;
        }

        if (array_key_exists(static::ITEM_KEY, $input)) {
            $track = (int) scrub_in((string) $input[static::ITEM_KEY]);
            if (!$this->hasItem($playlist, $track, $input)) {
                return $this->writeNotFound($response, $output, $apiVersion, 'song');
            }

            $playlist->delete_song($track);

            return $this->writeRemoved($response, $output, $apiVersion, $playlist);
        }

        if (array_key_exists('track', $input)) {
            $track = (int) scrub_in((string) $input['track']);
            if (!$playlist->has_item(null, $track)) {
                return $this->writeNotFound($response, $output, $apiVersion, 'track');
            }

            $playlist->delete_track_number($track);

            return $this->writeRemoved($response, $output, $apiVersion, $playlist);
        }

        return $response;
    }

    /**
     * Whether the playlist holds the item; the newer method also matches on the object type
     *
     * @param array<string, mixed> $input
     */
    protected function hasItem(Playlist $playlist, int $track, array $input): bool
    {
        return $playlist->has_item($track);
    }

    private function writeNotFound(
        ResponseInterface $response,
        ApiOutputInterface $output,
        int $apiVersion,
        string $type,
    ): ResponseInterface {
        $response->getBody()->write(
            $output->error(
                $apiVersion,
                ErrorCodeEnum::NOT_FOUND,
                'Not Found',
                static::ACTION,
                $type
            )
        );

        return $response;
    }

    private function writeRemoved(
        ResponseInterface $response,
        ApiOutputInterface $output,
        int $apiVersion,
        Playlist $playlist,
    ): ResponseInterface {
        $playlist->regenerate_track_numbers();

        $response->getBody()->write(
            $output->success($apiVersion, 'song removed from playlist')
        );

        return $response;
    }
}
