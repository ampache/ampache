<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

use Ahc\Cli\Input\Command;
use Ampache\Repository\UserRepositoryInterface;

final class AdminListUsersCommand extends Command
{
    private UserRepositoryInterface $userRepository;

    protected function defaults(): self
    {
        $this->option('-h, --help', T_('Help'))->on([$this, 'showHelp']);

        $this->onExit(static fn ($exitCode = 0) => exit($exitCode));

        return $this;
    }

    public function __construct(
        UserRepositoryInterface $userRepository
    ) {
        parent::__construct('admin:listUsers', T_('Users List'));

        $this->userRepository = $userRepository;

        $this
            ->option('-a|--apikey', T_('API key'), 'boolval', false)
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
        $apiKey     = $this->values()['apikey'] === true;
        $users      = [];
        $user       = null;
        if ($username) {
            $user = $this->userRepository->findByUsername($username);
        } elseif ($user_id) {
            $user = $this->userRepository->findById($user_id);
        } else {
            $users = $this->userRepository->getValidArray();
        }

        if ($user) {
            $outString = ($apiKey)
                ? $user->apikey ?? T_('Invalid API key')
                : sprintf(
                    T_('%s (%d)'),
                    $user->getusername(),
                    $user->getId()
                );

            $interactor->ok(
                $outString,
                true
            );

            return;
        }

        foreach ($users as $userId => $userName) {
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
