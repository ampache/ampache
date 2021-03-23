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
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class ArtistAlbumsMethod implements MethodInterface
{
    public const ACTION = 'artist_albums';

    private StreamFactoryInterface $streamFactory;

    private AlbumRepositoryInterface $albumRepository;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        AlbumRepositoryInterface $albumRepository,
        ModelFactoryInterface $modelFactory
    ) {
        $this->streamFactory   = $streamFactory;
        $this->albumRepository = $albumRepository;
        $this->modelFactory    = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This returns the albums of an artist
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (string) UID of artist
     * offset = (integer) //optional
     * limit  = (integer) //optional
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

        $artist = $this->modelFactory->createArtist((int) $objectId);

        if ($artist->isNew()) {
            throw new ResultEmptyException((string) $objectId);
        }

        $albums = $this->albumRepository->getByArtist($artist);
        if ($albums === []) {
            $result = $output->emptyResult('album');
        } else {
            $result = $output->albums(
                $albums,
                [],
                $gatekeeper->getUser()->getId(),
                true,
                true,
                (int)($input['limit'] ?? 0),
                (int)($input['offset'] ?? 0)
            );
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $result
            )
        );
    }
}
