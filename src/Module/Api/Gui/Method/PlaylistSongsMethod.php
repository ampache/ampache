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
use Ampache\Module\Api\Gui\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class PlaylistSongsMethod implements MethodInterface
{
    public const ACTION = 'playlist_songs';

    private StreamFactoryInterface $streamFactory;

    private ModelFactoryInterface $modelFactory;

    private LoggerInterface $logger;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ModelFactoryInterface $modelFactory,
        LoggerInterface $logger
    ) {
        $this->streamFactory = $streamFactory;
        $this->modelFactory  = $modelFactory;
        $this->logger        = $logger;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This returns the songs for a playlist
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (string) UID of playlist
     * offset = (integer) //optional
     * limit  = (integer) //optional
     *
     * @return ResponseInterface
     *
     * @throws ResultEmptyException
     * @throws AccessDeniedException
     * @throws RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $objectId = $input['filter'] ?? null;

        if ($objectId === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'filter')
            );
        }

        $user   = $gatekeeper->getUser();
        $userId = $user->getId();

        $this->logger->debug(
            sprintf(
                'User %d loading playlist: %s',
                $userId,
                $objectId
            ),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        if ((int) $objectId === 0) {
            $playlist = $this->modelFactory->createSearch(
               (int) str_replace('smart_', '', $objectId),
               'song',
               $user
            );
        } else {
            $playlist = $this->modelFactory->createPlaylist((int) $objectId);
        }

        if ($playlist->isNew() === true) {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %s'), $objectId)
            );
        }

        if (
            $playlist->type !== 'public' && (
                !$playlist->has_access($userId) &&
                $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN) === false
            )
        ) {
            throw new AccessDeniedException(
                T_('Require: 100')
            );
        }

        $items = $playlist->get_items();
        if ($items === []) {
            $result = $output->emptyResult('song');
        } else {
            $songs = [];
            foreach ($items as $object) {
                if ($object['object_type'] == 'song') {
                    $songs[] = (int) $object['object_id'];
                }
            }

            $result = $output->songs(
                $songs,
                $userId,
                true,
                true,
                true,
                (int) ($input['limit'] ?? 0),
                (int) ($input['offset'] ?? 0)
            );
        }

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
