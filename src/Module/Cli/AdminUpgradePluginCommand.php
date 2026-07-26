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
use Ampache\Module\System\Plugin\PluginManagerInterface;
use Override;

final class AdminUpgradePluginCommand extends Command
{
    public function __construct(
        private readonly PluginManagerInterface $pluginManager,
    ) {
        parent::__construct('admin:upgradePlugin', T_('Upgrade a plugin'));

        $this
            ->argument('<name>', T_('Plugin name'))
            ->usage('<bold>  admin:upgradePlugin Last.FM</end> <comment> ## ' . T_('Upgrade the Last.FM plugin') . '</end><eol/>');
    }

    public function execute(
        string $name,
    ): void {
        if ($this->app() === null) {
            return;
        }

        $interactor = $this->io();

        if ($this->pluginManager->upgradePlugin($name)) {
            $interactor->ok(sprintf(T_('Upgraded plugin %s'), $name), true);
        } else {
            $interactor->error(sprintf(T_('Could not upgrade plugin %s'), $name), true);
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
