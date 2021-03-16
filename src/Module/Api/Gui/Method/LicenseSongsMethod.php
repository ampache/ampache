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
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class LicenseSongsMethod implements MethodInterface
{
    public const ACTION = 'license_songs';

    private StreamFactoryInterface $streamFactory;

    private UserRepositoryInterface $userRepository;

    private SongRepositoryInterface $songRepository;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        UserRepositoryInterface $userRepository,
        SongRepositoryInterface $songRepository,
        ConfigContainerInterface $configContainer
    ) {
        $this->streamFactory   = $streamFactory;
        $this->userRepository  = $userRepository;
        $this->songRepository  = $songRepository;
        $this->configContainer = $configContainer;
    }

    /**
     * MINIMUM_API_VERSION=420000
     *
     * This returns all songs attached to a license ID
     *
     * @param GatekeeperInterface $gatekeeper ,
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (string) UID of license
     *
     * @return ResponseInterface
     *
     * @throws FunctionDisabledException
     * @throws RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::LICENSING) === false) {
            throw new FunctionDisabledException(T_('Enable: licensing'));
        }

        $objectId = $input['filter'] ?? null;

        if ($objectId === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'filter')
            );
        }

        $songIds = $this->songRepository->getByLicense((int) $objectId);

        if ($songIds === []) {
            $result = $output->emptyResult('song');
        } else {
            $result = $output->songs(
                $songIds,
                $gatekeeper->getUser()->getId()
            );
        }

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
