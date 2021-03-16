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

final class FlagMethod implements MethodInterface
{
    public const ACTION = 'flag';

    private StreamFactoryInterface $streamFactory;

    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory
    ) {
        $this->streamFactory   = $streamFactory;
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * This flags a library item as a favorite
     * Setting flag to true (1) will set the flag
     * Setting flag to false (0) will remove the flag
     *
     * @param array $input
     * type = (string) 'song', 'album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'video', 'tvshow', 'tvshow_season' $type
     * id   = (integer) $object_id
     * flag = (integer) 0,1 $flag
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
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::USER_FLAGS) === false) {
            throw new FunctionDisabledException(
                T_('Enable: userflags')
            );
        }

        foreach (['type', 'id', 'flag'] as $key) {
            if (!array_key_exists($key, $input)) {
                throw new RequestParamMissingException(
                   sprintf(T_('Bad Request: %s'), $key)
               );
            }
        }

        $type     = (string) $input['type'];
        $objectId = (int) $input['id'];
        $flag     = (bool) $input['flag'];

        // confirm the correct data
        if (!in_array($type, ['song', 'album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'video', 'tvshow', 'tvshow_season'])) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), $type)
            );
        }

        $item = $this->modelFactory->mapObjectType($type, $objectId);

        if (!$item->id) {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %d'), $objectId)
            );
        }

        $userflag = $this->modelFactory->createUserflag(
            $objectId,
            $type
        );
        if ($userflag->set_flag($flag, $gatekeeper->getUser()->getId())) {
            $message = ($flag) ? 'flag ADDED to ' : 'flag REMOVED from ';

            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->success($message . $objectId)
                )
            );
        }

        throw new RequestParamMissingException(
            sprintf(T_('flag failed %d'), $objectId)
        );
    }
}
