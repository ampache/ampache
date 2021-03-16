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
use Ampache\Module\Catalog\SingleItemUpdaterInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class UpdateFromTagsMethod implements MethodInterface
{
    public const ACTION = 'update_from_tags';

    private StreamFactoryInterface $streamFactory;

    private SingleItemUpdaterInterface $singleItemUpdater;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        SingleItemUpdaterInterface $singleItemUpdater,
        ModelFactoryInterface $modelFactory
    ) {
        $this->streamFactory     = $streamFactory;
        $this->singleItemUpdater = $singleItemUpdater;
        $this->modelFactory      = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * updates a single album, artist, song from the tag data
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * type = (string) 'artist', 'album', 'song'
     * id   = (integer) $artist_id, $album_id, $song_id)
     *
     * @return ResponseInterface
     * @throws RequestParamMissingException
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

        $type      = (string) $input['type'];
        $objectId  = (int) $input['id'];

        // confirm the correct data
        if (!in_array($type, ['artist', 'album', 'song'])) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), $type)
            );
        }

        $item = $this->modelFactory->mapObjectType($type, $objectId);

        if (!$item->id) {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %s'), $objectId)
            );
        }
        // update your object
        $this->singleItemUpdater->update($type, $objectId, true);

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->success(
                    sprintf('Updated tags for: %d (%s)', $objectId, $type)
                )
            )
        );
    }
}
