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
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class ArtistMethod implements MethodInterface
{
    public const ACTION = 'artist';

    private StreamFactoryInterface $streamFactory;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ModelFactoryInterface $modelFactory
    ) {
        $this->streamFactory = $streamFactory;
        $this->modelFactory  = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This returns a single artist based on the UID of said artist
     *
     * @param GatekeeperInterface$gatekeeper
     * @param ResponseInterface $response,
     * @param ApiOutputInterface $output
     * @param array $input
     * filter  = (string) Alpha-numeric search term
     * include = (array|string) 'albums', 'songs' //optional
     *
     * @return ResponseInterface
     *
     * @throws RequestParamMissingException
     * @throws ResultEmptyException
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

        $include = (is_array($input['include'])) ? $input['include'] : explode(',', (string) $input['include']);

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->artists([$objectId], $include, $gatekeeper->getUser()->getId())
            )
        );
    }
}
