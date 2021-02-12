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

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Catalog\MediaDeletionCheckerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PlaylistDeleteMethod implements MethodInterface
{
    public const ACTION = 'playlist_delete';

    private StreamFactoryInterface $streamFactory;

    private ModelFactoryInterface $modelFactory;

    private MediaDeletionCheckerInterface $mediaDeletionChecker;

    private UpdateInfoRepositoryInterface $updateInfoRepository;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ModelFactoryInterface $modelFactory,
        MediaDeletionCheckerInterface $mediaDeletionChecker,
        UpdateInfoRepositoryInterface $updateInfoRepository
    ) {
        $this->streamFactory        = $streamFactory;
        $this->modelFactory         = $modelFactory;
        $this->mediaDeletionChecker = $mediaDeletionChecker;
        $this->updateInfoRepository = $updateInfoRepository;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This deletes a playlist
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (string) UID of playlist
     *
     * @return ResponseInterface
     *
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
        $playlist = $this->modelFactory->createPlaylist((int) $objectId);

        if (
            !$playlist->has_access($gatekeeper->getUser()->getId()) &&
            !$gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
        ) {
            throw new AccessDeniedException(T_('Require: 100'));
        }

        $playlist->delete();

        $this->updateInfoRepository->updateCountByTableName('playlist');

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->success('playlist deleted')
            )
        );
    }
}
