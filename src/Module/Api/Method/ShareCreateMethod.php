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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Share\ExpirationDateCalculatorInterface;
use Ampache\Module\Share\ShareCreatorInterface;
use Ampache\Module\User\PasswordGenerator;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class ShareCreateMethod implements MethodInterface
{
    public const ACTION = 'share_create';

    private StreamFactoryInterface $streamFactory;

    private ModelFactoryInterface $modelFactory;

    private FunctionCheckerInterface $functionChecker;

    private PasswordGeneratorInterface $passwordGenerator;

    private ExpirationDateCalculatorInterface $expirationDateCalculator;

    private UpdateInfoRepositoryInterface $updateInfoRepository;

    private ConfigContainerInterface $configContainer;

    private ShareCreatorInterface $shareCreator;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ModelFactoryInterface $modelFactory,
        FunctionCheckerInterface $functionChecker,
        PasswordGeneratorInterface $passwordGenerator,
        ExpirationDateCalculatorInterface $expirationDateCalculator,
        UpdateInfoRepositoryInterface $updateInfoRepository,
        ConfigContainerInterface $configContainer,
        ShareCreatorInterface $shareCreator
    ) {
        $this->streamFactory            = $streamFactory;
        $this->modelFactory             = $modelFactory;
        $this->functionChecker          = $functionChecker;
        $this->passwordGenerator        = $passwordGenerator;
        $this->expirationDateCalculator = $expirationDateCalculator;
        $this->updateInfoRepository     = $updateInfoRepository;
        $this->configContainer          = $configContainer;
        $this->shareCreator             = $shareCreator;
    }

    /**
     * MINIMUM_API_VERSION=420000
     * Create a public url that can be used by anyone to stream media.
     * Takes the file id with optional description and expires parameters.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter      = (string) object_id
     * type        = (string) object_type ('song', 'album', 'artist')
     * description = (string) description (will be filled for you if empty) //optional
     * expires     = (integer) days to keep active //optional
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
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE) === false) {
            throw new FunctionDisabledException(
                T_('Enable: share')
            );
        }

        foreach (['type', 'filter'] as $key) {
            if (!array_key_exists($key, $input)) {
                throw new RequestParamMissingException(
                    sprintf(T_('Bad Request: %s'), $key)
                );
            }
        }

        $description = ($input['description'] ?? '');
        $objectId    = (int) $input['filter'];
        $type        = $input['type'];

        // confirm the correct data
        if (!in_array($type, array('song', 'album', 'artist'))) {
            throw new RequestParamMissingException(
                T_('Bad Request')
            );
        }

        $item = $this->modelFactory->mapObjectType($type, $objectId);

        if (!$item->id) {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %s'), $objectId)
            );
        }

        $shareId = $this->shareCreator->create(
            $type,
            $objectId,
            true,
            $this->functionChecker->check(AccessLevelEnum::FUNCTION_DOWNLOAD),
            $this->expirationDateCalculator->calculate((int) ($input['expires'] ?? 0)),
            $this->passwordGenerator->generate(PasswordGenerator::DEFAULT_LENGTH),
            0,
            $description
        );
        if ($shareId === null) {
            throw new RequestParamMissingException(
                T_('Bad Request')
            );
        }

        $this->updateInfoRepository->updateCountByTableName('share');

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->shares([$shareId], false)
            )
        );
    }
}
