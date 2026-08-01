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
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Song\Deletion\SongDeleterInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Deletes an existing song
 */
final class SongDeleteMethod implements MethodInterface
{
    public const string ACTION = 'song_delete';

    public const string REST_ACTION = 'songs_delete';

    private ModelFactoryInterface $modelFactory;
    private SongDeleterInterface $songDeleter;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        SongDeleterInterface $songDeleter,
    ) {
        $this->modelFactory = $modelFactory;
        $this->songDeleter  = $songDeleter;
    }

    /**
     * MINIMUM_API_VERSION=5.0.0
     *
     * Delete an existing song.
     *
     * filter = (string) UID of song to delete
     *
     * @param array{
     *     filter?: string,
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

        $objectId = (int) $input['filter'];
        $song     = $this->modelFactory->createSong($objectId);

        if ($song->isNew()) {
            throw new ResultEmptyException(
                (string) $objectId
            );
        }

        if (!Catalog::can_remove($song, $user->getId())) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::MANAGER->value)
            );
        }

        if (!$this->songDeleter->delete($song)) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $objectId),
                    self::ACTION,
                    'system'
                )
            );

            return $response;
        }

        Catalog::count_table('song');

        $response->getBody()->write(
            $output->success($apiVersion, 'song ' . $objectId . ' deleted')
        );

        return $response;
    }
}
