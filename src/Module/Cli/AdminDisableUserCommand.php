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
use Ampache\Module\User\UserStateTogglerInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Override;

final class AdminDisableUserCommand extends Command
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserStateTogglerInterface $userStateToggler,
    ) {
        parent::__construct('admin:disableUser', T_('Disable a User'));

        $this
            ->option('-u|--user', T_('User ID'), 'intval', 0)
            ->argument('[username]', T_('Username'))
            ->usage('<bold>  admin:disableUser some-user</end> <comment> ## ' . T_('Disable the User with the name `some-user`') . '</end><eol/>');
    }

    public function execute(
        ?string $username,
    ): void {
        if ($this->app() === null) {
            return;
        }

        $interactor = $this->io();
        $userId     = $this->values()['user'];
        $user       = ($username)
            ? $this->userRepository->findByUsername($username)
            : (($userId) ? $this->userRepository->findById($userId) : null);

        if (!$user instanceof User) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            $interactor->error(
                sprintf(T_('Missing: %s'), $username ?? (string) $userId),
                true
            );

            return;
        }

        if ($this->userStateToggler->disable($user)) {
            $interactor->ok(
                sprintf(T_('%s has been disabled'), $user->getUsername()),
                true
            );
        } else {
            $interactor->error(
                T_('You need at least one active Administrator account'),
                true
            );
        }
    }

    #[Override]
    protected function defaults(): self
    {
        $this->option('-h, --help', T_('Help'))->on($this->showHelp(...));

        $this->onExit(static fn($exitCode = 0) => exit($exitCode));

        return $this;
    }
}
