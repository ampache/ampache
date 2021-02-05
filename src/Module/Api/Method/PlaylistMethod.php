<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method;

use Ampache\Model\ModelFactoryInterface;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PlaylistMethod implements MethodInterface
{
    public const ACTION = 'playlist';

    private StreamFactoryInterface $streamFactory;

    private ModelFactoryInterface $modelFactory;

    private UserRepositoryInterface $userRepository;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ModelFactoryInterface $modelFactory,
        UserRepositoryInterface $userRepository
    ) {
        $this->streamFactory  = $streamFactory;
        $this->modelFactory   = $modelFactory;
        $this->userRepository = $userRepository;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This returns a single playlist
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (string) UID of playlist
     *
     * @return ResponseInterface
     * @throws RequestParamMissingException
     * @throws ResultEmptyException
     * @throws AccessDeniedException
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

        if ((int) $objectId === 0) {
            $playlist = $this->modelFactory->createSearch(
                (int) str_replace('smart_', '', $objectId),
                'song',
                $user
            );
        } else {
            $playlist = $this->modelFactory->createPlaylist((int) $objectId);
        }

        if ($playlist->isNew()) {
            throw new ResultEmptyException($objectId);
        }

        $userId = $user->getId();

        if (
            $playlist->type !== 'public' && (
                !$playlist->has_access($userId) &&
                !$gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            )
        ) {
            throw new AccessDeniedException(T_('Require: 100'));
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->playlists(
                    [$playlist->getId()],
                    false,
                    false
                )
            )
        );
    }
}
