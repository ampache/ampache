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
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Catalog\ArtItemGathererInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class UpdateArtMethod implements MethodInterface
{
    public const ACTION = 'update_art';

    private ArtItemGathererInterface $artItemGatherer;

    private ModelFactoryInterface $modelFactory;

    private StreamFactoryInterface $streamFactory;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ArtItemGathererInterface $artItemGatherer,
        ModelFactoryInterface $modelFactory,
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer
    ) {
        $this->artItemGatherer = $artItemGatherer;
        $this->modelFactory    = $modelFactory;
        $this->streamFactory   = $streamFactory;
        $this->configContainer = $configContainer;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * updates a single album, artist, song running the gather_art process
     * Doesn't overwrite existing art by default.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * type      = (string) 'artist', 'album'
     * id        = (integer) $artist_id, $album_id)
     * overwrite = (integer) 0,1 //optional
     *
     * @return ResponseInterface
     *
     * @throws RequestParamMissingException
     * @throws AccessDeniedException
     * @throws ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        foreach (['type', 'id'] as $key) {
            if (!array_key_exists($key, $input)) {
                throw new RequestParamMissingException(
                    sprintf(T_('Bad Request: %s'), $key)
                );
            }
        }

        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER) === false) {
            throw new AccessDeniedException(
                T_('Require: 75')
            );
        }

        $type = (string) $input['type'];

        // confirm the correct data
        if (!in_array($type, ['artist', 'album'])) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), $type)
            );
        }

        $objectId  = (int) $input['id'];
        $overwrite = (int) ($input['overwrite'] ?? 0) == 0;

        $item = $this->modelFactory->mapObjectType($type, $objectId);

        if (!$item->id) {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %d'), $objectId)
            );
        }

        if ($this->artItemGatherer->gather($type, $objectId, $overwrite, true)) {
            $art_url = sprintf(
                '%s/image.php?object_id=%d&object_type=artist&auth=%s',
                $this->configContainer->getWebPath(),
                $objectId,
                $input['auth']
            );

            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->success(
                        sprintf('Gathered new art for: %d (%s)', $objectId, $type),
                        ['art' => $art_url]
                    )
                )
            );
        }

        throw new RequestParamMissingException(
            sprintf(T_('Bad Request: %d'), $objectId)
        );
    }
}
