<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Cli;

use Ahc\Cli\Application;
use Ahc\Cli\Input\Command;
use Ahc\Cli\IO\Interactor;
use Ampache\Repository\UserRepositoryInterface;

final class AdminListUsersCommand extends Command
{
    private UserRepositoryInterface $userRepository;

    public function __construct(
        UserRepositoryInterface $userRepository
    ) {
        parent::__construct('admin:listUsers', T_('Users List'));

        $this->userRepository = $userRepository;

        $this
            ->option('-u|--user', T_('User ID'), 'intval', 0)
            ->argument('[username]', T_('Username'))
            ->usage('<bold>  admin:listUsers some-user</end> <comment> ## ' . T_('Find a User with the name `some-user`') . '</end><eol/>');
    }

    public function execute(
        ?string $username
    ): void {
        if ($this->app() === null) {
            return;
        }

        $interactor = $this->io();
        $user_id    = $this->values()['user'];
        $users      = $this->userRepository->getValidArray();

        foreach ($users as $userId => $userName) {
            if (
                $user_id === $userId ||
                $username === $userName
            ) {
                $interactor->ok(
                    sprintf(
                        T_('%s (%d)'),
                        $userName,
                        $userId
                    ),
                    true
                );

                return;
            } elseif (
                empty($username) &&
                $user_id === 0
            ) {
                $interactor->ok(
                    sprintf(
                        T_('%s (%d)'),
                        $userName,
                        $userId
                    ),
                    true
                );
            }
        }
    }
}
