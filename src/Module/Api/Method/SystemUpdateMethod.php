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
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\AutoUpdateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class SystemUpdateMethod implements MethodInterface
{
    public const ACTION = 'system_update';

    private StreamFactoryInterface $streamFactory;

    private ConfigContainerInterface $configContainer;

    private AutoUpdateInterface $autoUpdate;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer,
        AutoUpdateInterface $autoUpdate
    ) {
        $this->streamFactory   = $streamFactory;
        $this->configContainer = $configContainer;
        $this->autoUpdate      = $autoUpdate;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Check Ampache for updates and run the update if there is one.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     *
     * @return ResponseInterface
     * @throws RequestParamMissingException
     * @throws AccessDeniedException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN) === false) {
            throw new AccessDeniedException(
                T_('Require: 100')
            );
        }

        if ($this->autoUpdate->isUpdateAvailable(true)) {
            // run the update
            $this->autoUpdate->updatefiles(true);
            $this->autoUpdate->updateDependencies(true);

            // check that the update completed or failed failed.
            if ($this->autoUpdate->isUpdateAvailable(true)) {
                throw new RequestParamMissingException(
                    T_('Bad Request')
                );
            }
            $result = $output->success(T_('update successful'));
        } else {
            $result = $output->success(T_('no update available'));
        }

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
