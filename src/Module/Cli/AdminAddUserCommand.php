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

namespace Ampache\Module\Cli;

use Ahc\Cli\Input\Command;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Model\User;

final class AdminAddUserCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        parent::__construct('admin:addUser', 'Adds a user');

        $this->configContainer = $configContainer;

        $this
            ->option('-p|--password', 'Password', 'strval', mt_rand())
            ->option('-e|--email', 'E-Mail', 'strval', '')
            ->option('-w|--website', 'Website', 'strval', '')
            ->option('-n|--name', 'Name', 'strval', '')
            ->option('-l|--level', 'Access Level', 'intval', $this->configContainer->get('auto_user') ?? 5)
            ->argument('<username>', 'The name of the new user')
            ->usage('<bold>  admin:addUser some-user</end> <comment> ## Add the user `some-user`</end><eol/>');
    }

    public function execute(
        string $username
    ): void {
        $values     = $this->values();
        $interactor = $this->io();

        $result = (int) User::create(
            $username,
            $values['name'],
            $values['email'],
            $values['website'],
            $values['password'],
            $values['level']
        );

        if ($result !== null) {
            $interactor->ok(
                sprintf(
                    T_('Created %s user %s with password %s'),
                    User::access_level_to_name((string) $values['level']),
                    $username,
                    $values['password']
                ),
                true
            );
            echo "\n";

            User::fix_preferences('-1');
        } else {
            $interactor->error(T_('User creation failed'), true);
        }
    }
}
