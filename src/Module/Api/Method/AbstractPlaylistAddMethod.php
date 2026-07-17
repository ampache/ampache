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
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Adds an object and its songs to a playlist
 *
 * The two live api versions only differ in whether `type` is mandatory: version 6 insists on it,
 * version 8 defaults it to `song`. The version classes supply that flag; everything else is shared.
 */
abstract class AbstractPlaylistAddMethod implements MethodInterface
{
    public const string ACTION = 'playlist_add';

    public const string REST_ACTION = 'playlist_add_edit';

    // whether the version insists on a `type`; overridden per version
    protected const bool TYPE_REQUIRED = false;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory,
    ) {
        $this->modelFactory = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=6.3.0
     *
     * This adds a song to a playlist, allowing different song parent types
     *
     * filter = (string) UID of playlist
     * id     = (string) $object_id
     * type   = (string) 'song', 'album', 'artist', 'playlist'
     *
     * @param array{
     *     filter?: string,
     *     id?: string,
     *     song?: string,
     *     type?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessFailedException|RequestParamMissingException|ResultEmptyException
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

        $objectId = $input['song'] ?? $input['id'] ?? null;
        if ($objectId === null) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'id')
            );
        }

        if (static::TYPE_REQUIRED && !array_key_exists('type', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'type')
            );
        }

        $playlist   = $this->modelFactory->createPlaylist((int) $input['filter']);
        $objectType = $input['type'] ?? 'song';

        // confirm the correct data
        if (!$playlist->has_collaborate($user)) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::ADMIN->value)
            );
        }

        if (!in_array(strtolower($objectType), ['song', 'album', 'artist', 'playlist'])) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $objectType),
                    static::ACTION,
                    'type'
                )
            );

            return $response;
        }

        if ($objectType === 'playlist' && ((int) $objectId) === 0) {
            $objectId   = str_replace('smart_', '', (string) $objectId);
            $objectType = 'search';
        }

        $className = ObjectTypeToClassNameMapper::map($objectType);

        /** @var Album|Artist|Playlist|Search|Song $item */
        $item = new $className((int) $objectId);
        if ($item->isNew()) {
            throw new ResultEmptyException(
                (string) $objectId,
                'id'
            );
        }

        $results = [];
        switch ($objectType) {
            case 'song':
                /** @var Song $item */
                $results = [$item->getId()];
                break;
            case 'album':
            case 'artist':
            case 'playlist':
            case 'search':
                /** @var Album|Artist|Playlist|Search $item */
                $results = $item->get_songs();
                break;
        }

        if ($results === []) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $objectId),
                    static::ACTION,
                    'system'
                )
            );

            return $response;
        }

        $message = ($playlist->add_songs($results))
            ? 'songs added to playlist'
            : 'nothing was added to the playlist';

        $response->getBody()->write(
            $output->success($apiVersion, $message)
        );

        return $response;
    }
}
