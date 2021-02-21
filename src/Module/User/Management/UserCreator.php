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

namespace Ampache\Module\User\Management;

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;

final class UserCreator implements UserCreatorInterface
{
    private UserRepositoryInterface $userRepository;

    private ModelFactoryInterface $modelFactory;

    private UpdateInfoRepositoryInterface $updateInfoRepository;

    public function __construct(
        UserRepositoryInterface $userRepository,
        ModelFactoryInterface $modelFactory,
        UpdateInfoRepositoryInterface $updateInfoRepository
    ) {
        $this->userRepository       = $userRepository;
        $this->modelFactory         = $modelFactory;
        $this->updateInfoRepository = $updateInfoRepository;
    }

    /**
     * Inserts a new user into Ampache
     *
     * @throws Exception\UserCreationFailedException
     */
    public function create(
        string $username,
        string $fullname,
        string $email,
        string $website,
        string $password,
        int $access,
        string $state = '',
        string $city = '',
        bool $disabled = false,
        bool $encrypted = false
    ): User {
        $website = rtrim((string) $website, '/');
        if ($encrypted === false) {
            $password = hash('sha256', $password);
        }

        $userId = $this->userRepository->create(
            $username,
            $fullname,
            $email,
            $website,
            $password,
            $access,
            $state,
            $city,
            $disabled,
        );

        if ($userId === null) {
            throw new Exception\UserCreationFailedException();
        }

        $this->updateInfoRepository->updateCountByTableName('user');

        $user = $this->modelFactory->createUser($userId);
        $user->fixPreferences();

        return $user;
    }
}
