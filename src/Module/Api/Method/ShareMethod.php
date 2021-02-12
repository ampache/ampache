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
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class ShareMethod implements MethodInterface
{
    public const ACTION = 'share';

    private StreamFactoryInterface $streamFactory;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer
    ) {
        $this->streamFactory   = $streamFactory;
        $this->configContainer = $configContainer;
    }

    /**
     * MINIMUM_API_VERSION=420000
     *
     * Get the share from it's id.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (integer) Share ID number
     *
     * @return ResponseInterface
     *
     * @throws RequestParamMissingException
     * @throws FunctionDisabledException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE) === false) {
            throw new FunctionDisabledException(T_('Enable: share'));
        }

        $shareId = $input['filter'] ?? null;

        if ($shareId === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'filter')
            );
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->shares([(int) $shareId], false)
            )
        );
    }
}
