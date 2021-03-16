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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class RateMethod implements MethodInterface
{
    public const ACTION = 'rate';

    private ConfigContainerInterface $configContainer;

    private StreamFactoryInterface $streamFactory;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        StreamFactoryInterface $streamFactory,
        ModelFactoryInterface $modelFactory
    ) {
        $this->configContainer = $configContainer;
        $this->streamFactory   = $streamFactory;
        $this->modelFactory    = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This rates a library item
     *
     * @param array $input
     * type   = (string) 'song', 'album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'video', 'tvshow', 'tvshow_season' $type
     * id     = (integer) $object_id
     * rating = (integer) 0,1|2|3|4|5 $rating
     *
     * @return ResponseInterface
     *
     * @throws RequestParamMissingException
     * @throws ResultEmptyException
     * @throws FunctionDisabledException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::RATINGS) === false) {
            throw new FunctionDisabledException(
                T_('Enable: ratings')
            );
        }

        foreach (['type', 'id', 'rating'] as $key) {
            if (!array_key_exists($key, $input)) {
                throw new RequestParamMissingException(
                    sprintf(T_('Bad Request: %s'), $key)
                );
            }
        }

        $type     = (string) $input['type'];
        $objectId = (int) $input['id'];
        $rating   = (int) $input['rating'];

        // confirm the correct data
        if (!in_array($type, ['song', 'album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'video', 'tvshow', 'tvshow_season'])) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), $type)
            );
        }
        if (!in_array($rating, [0, 1, 2, 3, 4, 5])) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %d'), $rating)
            );
        }

        $item = $this->modelFactory->mapObjectType($type, $objectId);

        if (!$item->id) {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %s'), $objectId)
            );
        }
        $rate = $this->modelFactory->createRating($objectId, $type);
        $rate->set_rating($rating, $gatekeeper->getUser()->getId());

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->success(
                    sprintf(T_('rating set to %s for %d'), $rating, $objectId)
                )
            )
        );
    }
}
