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

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Class AlbumMethod
 * @package Lib\ApiMethods
 */
final class AlbumMethod implements MethodInterface
{
    public const ACTION = 'album';

    private ModelFactoryInterface $modelFactory;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->modelFactory  = $modelFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * album
     * MINIMUM_API_VERSION=380001
     *
     * This returns a single album based on the UID provided
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter  = (string) UID of Album
     * include = (array|string) 'songs' //optional
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

        if ($album->isNew()) {
            throw new ResultEmptyException((string) $objectId);
        }

        $include = [];
        if (array_key_exists('include', $input)) {
            $include = (is_array($input['include'])) ? $input['include'] : explode(',', (string) $input['include']);
        }

        $result = $output->albums(
            [$album->getId()],
            $include,
            $gatekeeper->getUser()->getId(),
            true,
            false
        );

        return $response->withBody(
            $this->streamFactory->createStream(
                $result
            )
        );
    }
}
