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
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Util\RecommendationInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class GetSimilarMethod implements MethodInterface
{
    public const ACTION = 'get_similar';

    private StreamFactoryInterface $streamFactory;

    private RecommendationInterface $recommendation;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        RecommendationInterface $recommendation
    ) {
        $this->streamFactory  = $streamFactory;
        $this->recommendation = $recommendation;
    }

    /**
     * MINIMUM_API_VERSION=420000
     *
     * Return similar artist id's or similar song ids compared to the input filter
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * type   = (string) 'song', 'artist'
     * filter = (integer) artist id or song id
     * offset = (integer) //optional
     * limit  = (integer) //optional
     *
     * @return ResponseInterface
     * @throws RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        foreach (['type', 'filter'] as $key) {
            if (!array_key_exists($key, $input)) {
                throw new RequestParamMissingException(
                    sprintf(T_('Bad Request: %s'), $key)
                );
            }
        }

        $type     = (string) $input['type'];
        $objectId = (int) $input['filter'];

        // confirm the correct data
        if (!in_array($type, ['song', 'artist'])) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), $type)
            );
        }

        $similar = [];
        switch ($type) {
            case 'artist':
                $similar = $this->recommendation->getArtistsLike($objectId);
                break;
            case 'song':
                $similar = $this->recommendation->getSongsLike($objectId);
                break;
        }

        if ($similar === []) {
            $result = $output->emptyResult($type);
        } else {
            $result = $output->indexes(
                array_map(static function (array $item): int {
                    return (int) $item['child'];
                }, $similar),
                $type,
                $gatekeeper->getUser()->getId(),
                false,
                false,
                (int) ($input['limit'] ?? 0),
                (int) ($input['offset'] ?? 0)
            );
        }

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
