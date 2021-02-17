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
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\PlaylistRepositoryInterface;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PlaylistCreateMethod implements MethodInterface
{
    public const ACTION = 'playlist_create';

    private UpdateInfoRepositoryInterface $updateInfoRepository;

    private StreamFactoryInterface $streamFactory;

    private PlaylistRepositoryInterface $playlistRepository;

    public function __construct(
        UpdateInfoRepositoryInterface $updateInfoRepository,
        StreamFactoryInterface $streamFactory,
        PlaylistRepositoryInterface $playlistRepository
    ) {
        $this->updateInfoRepository = $updateInfoRepository;
        $this->streamFactory        = $streamFactory;
        $this->playlistRepository   = $playlistRepository;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This create a new playlist and return it
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * name = (string) Alpha-numeric Object name
     * type = (string) 'public', 'private'
     *
     * @return ResponseInterface
     *
     * @throws RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $name = $input['name'] ?? null;

        if ($name === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'name')
            );
        }

        $type = $input['type'] ?? 'private';
        if ($type != 'private') {
            $type = 'public';
        }

        $object_id = $this->playlistRepository->create($name, $type, $gatekeeper->getUser()->getId());
        if ($object_id === 0) {
            throw new RequestParamMissingException(
                T_('Bad Request')
            );
        }

        $this->updateInfoRepository->updateCountByTableName('playlist');

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->playlists([$object_id], false, false)
            )
        );
    }
}
