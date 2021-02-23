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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class AlbumSongsMethod implements MethodInterface
{
    public const ACTION = 'album_songs';

    private ModelFactoryInterface $modelFactory;

    private SongRepositoryInterface $songRepository;

    private StreamFactoryInterface $streamFactory;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        SongRepositoryInterface $songRepository,
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer
    ) {
        $this->modelFactory    = $modelFactory;
        $this->songRepository  = $songRepository;
        $this->streamFactory   = $streamFactory;
        $this->configContainer = $configContainer;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This returns the songs of a specified album
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (string) UID of Album
     * exact  = (integer) 0,1, if true don't group songs from different disks //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     *
     * @return ResponseInterface
     *
     * @throws ResultEmptyException
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

        $album = $this->modelFactory->createAlbum((int) $objectId);
        if ($album->isNew() === true) {
            throw new ResultEmptyException((string) $objectId);
        }

        // songs for all disks
        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALBUM_GROUP) &&
            (int) ($input['exact'] ?? 0) === 0
        ) {
            $songs = [];

            $discIds = $album->get_group_disks_ids();
            foreach ($discIds as $discId) {
                $disc = $this->modelFactory->createAlbum((int) $discId);
                foreach ($this->songRepository->getByAlbum((int) $disc->id) as $songId) {
                    $songs[] = $songId;
                }
            }
        } else {
            // songs for just this disk
            $songs = $this->songRepository->getByAlbum($album->id);
        }

        if ($songs === []) {
            $result = $output->emptyResult('song');
        } else {
            $result = $output->songs(
                $songs,
                $gatekeeper->getUser()->getId(),
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
