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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Core;
use Ampache\Module\System\Plugin\PluginManagerInterface;
use Ampache\Repository\Model\User;
use Override;

final class AdminInstallLocalplayCommand extends Command
{
    public function __construct(
        private readonly PluginManagerInterface $pluginManager,
    ) {
        parent::__construct('admin:installLocalplay', T_('Install a localplay controller'));

        $this
            ->option('-u|--user', T_('User ID to receive the localplay preferences') . ' (-1 = ' . T_('All') . ')', 'intval', -1)
            ->argument('<type>', T_('Localplay Type') . " ('mpd', 'upnp', 'httpq', ...)")
            ->usage('<bold>  admin:installLocalplay mpd</end> <comment> ## ' . T_('Enable the mpd localplay controller') . '</end><eol/>');
    }

    public function execute(
        string $type,
    ): void {
        if ($this->app() === null) {
            return;
        }

        $interactor = $this->io();

        // The localplay preference writes gate on the running user's access; the CLI is the system user so elevate it
        $user = Core::get_global('user');
        if ($user instanceof User) {
            $user->access = AccessLevelEnum::ADMIN->value;
        }

        if ($this->pluginManager->installLocalplay($type, (int) $this->values()['user'])) {
            $interactor->ok(sprintf(T_('%s has been enabled'), $type), true);
        } else {
            $interactor->error(sprintf('%s: %s', T_('Failed to enable the Localplay module'), $type), true);
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
