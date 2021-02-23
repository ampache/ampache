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
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Share\ExpirationDateCalculatorInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\ShareRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class ShareEditMethod implements MethodInterface
{
    public const ACTION = 'share_edit';

    private StreamFactoryInterface $streamFactory;

    private ShareRepositoryInterface $shareRepository;

    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    private ExpirationDateCalculatorInterface $expirationDateCalculator;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ShareRepositoryInterface $shareRepository,
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
        ExpirationDateCalculatorInterface $expirationDateCalculator
    ) {
        $this->streamFactory            = $streamFactory;
        $this->shareRepository          = $shareRepository;
        $this->configContainer          = $configContainer;
        $this->modelFactory             = $modelFactory;
        $this->expirationDateCalculator = $expirationDateCalculator;
    }

    /**
     * MINIMUM_API_VERSION=420000
     *
     * Update the description and/or expiration date for an existing share.
     * Takes the share id to update with optional description and expires parameters.
     *
     * @param array $input
     * filter      = (string) Alpha-numeric search term
     * stream      = (boolean) 0,1 //optional
     * download    = (boolean) 0,1 //optional
     * expires     = (integer) number of whole days before expiry //optional
     * description = (string) update description //optional
     *
     * @return ResponseInterface
     * @throws ResultEmptyException
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
            throw new FunctionDisabledException(
                T_('Enable: share')
            );
        }

        $shareId = $input['filter'] ?? null;

        if ($shareId === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'filter')
            );
        }

        $user = $gatekeeper->getUser();

        if (in_array((int) $shareId, $this->shareRepository->getList($user))) {
            $share = $this->modelFactory->createShare((int) $shareId);

            $description = $input['description'] ?? $share->description;
            $stream      = $input['stream'] ?? $share->allow_stream;
            $download    = $input['download'] ?? $share->allow_download;

            if (array_key_exists('expires', $input)) {
                $expires = $this->expirationDateCalculator->calculate((int) $input['expires']);
            } else {
                $expires = $share->expire_days;
            }

            $data = [
                'max_counter' => $share->max_counter,
                'expire' => $expires,
                'allow_stream' => $stream,
                'allow_download' => $download,
                'description' => $description
            ];
            if ($share->update($data, $user)) {
                return $response->withBody(
                    $this->streamFactory->createStream(
                        $output->success(
                            sprintf('share %d updated', $shareId)
                        )
                    )
                );
            } else {
                throw new RequestParamMissingException(
                    sprintf(T_('Bad Request: %d'), $shareId)
                );
            }
        } else {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %d'), $shareId)
            );
        }
    }
}
