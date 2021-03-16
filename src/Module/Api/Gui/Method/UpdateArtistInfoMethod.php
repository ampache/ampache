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
use Ampache\Module\Util\RecommendationInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class UpdateArtistInfoMethod implements MethodInterface
{
    public const ACTION = 'update_artist_info';

    private StreamFactoryInterface $streamFactory;

    private ModelFactoryInterface $modelFactory;

    private RecommendationInterface $recommendation;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ModelFactoryInterface $modelFactory,
        RecommendationInterface $recommendation
    ) {
        $this->streamFactory  = $streamFactory;
        $this->modelFactory   = $modelFactory;
        $this->recommendation = $recommendation;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Update artist information and fetch similar artists from last.fm
     * Make sure lastfm_api_key is set in your configuration file
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * id = (integer) $artist_id)
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
        $objectId = $input['id'] ?? null;

        if ($objectId === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'id')
            );
        }

        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER) === false) {
            throw new AccessDeniedException(
                T_('Require: 75')
            );
        }

        $objectId = (int) $input['id'];

        $artist = $this->modelFactory->createArtist($objectId);

        if ($artist->isNew()) {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %d'), $objectId)
            );
        }

        // update your object, you need at least catalog_manager access to the db
        if (
            $this->recommendation->getArtistInfo($objectId) !== [] ||
            $this->recommendation->getArtistsLike($objectId) !== []
        ) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->success(sprintf('Updated artist info: %d', $objectId))
                )
            );
        }
        throw new RequestParamMissingException(
            sprintf(T_('Bad Request: %d'), $objectId)
        );
    }
}
