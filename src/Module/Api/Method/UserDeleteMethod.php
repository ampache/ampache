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

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class UserDeleteMethod implements MethodInterface
{
    public const ACTION = 'user_delete';

    private StreamFactoryInterface $streamFactory;

    private UserRepositoryInterface $userRepository;

    private PrivilegeCheckerInterface $privilegeChecker;

    private ModelFactoryInterface $modelFactory;

    private UpdateInfoRepositoryInterface $updateInfoRepository;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        UserRepositoryInterface $userRepository,
        PrivilegeCheckerInterface $privilegeChecker,
        ModelFactoryInterface $modelFactory,
        UpdateInfoRepositoryInterface $updateInfoRepository
    ) {
        $this->streamFactory        = $streamFactory;
        $this->userRepository       = $userRepository;
        $this->privilegeChecker     = $privilegeChecker;
        $this->modelFactory         = $modelFactory;
        $this->updateInfoRepository = $updateInfoRepository;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Delete an existing user.
     * Takes the username in parameter.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * username = (string) $username)
     *
     * @return ResponseInterface
     *
     * @throws AccessDeniedException
     * @throws RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN) === false) {
            throw new AccessDeniedException(T_('Require: 100'));
        }

        $username = $input['username'] ?? null;

        if ($username === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'username')
            );
        }

        $userId = $this->userRepository->findByUsername($username);

        // don't delete yourself or admins
        if (
            $userId !== null &&
            $userId !== $gatekeeper->getUser()->getId() &&
            $this->privilegeChecker->check(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN, $userId) === false
        ) {
            $user = $this->modelFactory->createUser($userId);
            $user->delete();

            $this->updateInfoRepository->updateCountByTableName('user');

            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->success(
                        sprintf('successfully deleted: %s', $username)
                    )
                )
            );
        }

        throw new RequestParamMissingException(
            sprintf(T_('Bad Request: %s'), $username)
        );
    }
}
