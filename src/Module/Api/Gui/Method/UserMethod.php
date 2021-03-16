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
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class UserMethod implements MethodInterface
{
    public const ACTION = 'user';

    private StreamFactoryInterface $streamFactory;

    private UserRepositoryInterface $userRepository;

    private LoggerInterface $logger;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        UserRepositoryInterface $userRepository,
        LoggerInterface $logger,
        ModelFactoryInterface $modelFactory
    ) {
        $this->streamFactory  = $streamFactory;
        $this->userRepository = $userRepository;
        $this->logger         = $logger;
        $this->modelFactory   = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This get a user's public information
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * username = (string) $username
     *
     * @return ResponseInterface
     *
     * @throws ResultEmptyException
     * @throws RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $username = $input['username'] ?? null;

        if ($username === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'username')
            );
        }

        $userId = $this->userRepository->findByUsername((string) $input['username']);
        if (
            $userId === null ||
            in_array($userId, $this->userRepository->getValid(true)) === false
        ) {
            throw new ResultEmptyException(sprintf(T_('Not Found: %s'), $username));
        }

        $fullinfo  = false;
        // get full info when you're an admin or searching for yourself
        if (
            $userId === $gatekeeper->getUser()->getId() ||
            $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
        ) {
            $fullinfo = true;
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->user($this->modelFactory->createUser($userId), $fullinfo)
            )
        );
    }
}
