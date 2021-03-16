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
use Ampache\Repository\ShareRepositoryInterface;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class ShareDeleteMethod implements MethodInterface
{
    public const ACTION = 'share_delete';

    private StreamFactoryInterface $streamFactory;

    private ShareRepositoryInterface $shareRepository;

    private UpdateInfoRepositoryInterface $updateInfoRepository;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ShareRepositoryInterface $shareRepository,
        UpdateInfoRepositoryInterface $updateInfoRepository,
        ConfigContainerInterface $configContainer
    ) {
        $this->streamFactory        = $streamFactory;
        $this->shareRepository      = $shareRepository;
        $this->updateInfoRepository = $updateInfoRepository;
        $this->configContainer      = $configContainer;
    }

    /**
     * MINIMUM_API_VERSION=420000
     *
     * Delete an existing share.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (string) UID of share to delete
     *
     * @return ResponseInterface
     *
     * @throws FunctionDisabledException
     * @throws RequestParamMissingException
     * @throws ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE) === false) {
            throw new FunctionDisabledException(
                T_('Enable: share')
            );
        }

        $objectId = (int) ($input['filter'] ?? 0);

        if ($objectId === 0) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'filter')
            );
        }

        $user = $gatekeeper->getUser();

        if (in_array($objectId, $this->shareRepository->getList($user))) {
            if ($this->shareRepository->delete($objectId, $user)) {
                $this->updateInfoRepository->updateCountByTableName('share');

                return $response->withBody(
                    $this->streamFactory->createStream(
                        $output->success(
                            sprintf('share %d deleted', $objectId)
                        )
                    )
                );
            } else {
                throw new RequestParamMissingException(
                    sprintf(T_('Bad Request: %s'), $objectId)
                );
            }
        } else {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %d'), $objectId)
            );
        }
    }
}
