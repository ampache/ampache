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

final class AdminListModulesCommand extends Command
{
    public function __construct(
        private readonly PluginManagerInterface $pluginManager,
    ) {
        parent::__construct('admin:listModules', T_('List plugins, catalog types and localplay controllers'));

        $this
            ->usage('<bold>  admin:listModules</end> <comment> ## ' . T_('List all pluggable modules and their installed state') . '</end><eol/>');
    }

    public function execute(): void
    {
        if ($this->app() === null) {
            return;
        }

        $interactor = $this->io();

        $interactor->info(T_('Plugins'), true);
        foreach ($this->pluginManager->getPlugins() as $plugin) {
            $state = $plugin['installed']
                ? sprintf(T_('installed v%d'), $plugin['installedVersion']) . ($plugin['upgradeAvailable'] ? sprintf(' -> v%d', $plugin['availableVersion']) : '')
                : T_('not installed');
            $interactor->ok(sprintf('  %s (%s)', $plugin['name'], $state), true);
        }

        $interactor->info(T_('Catalog Types'), true);
        foreach ($this->pluginManager->getCatalogTypes() as $catalogType) {
            $interactor->ok(
                sprintf('  %s (%s)', $catalogType['type'], $catalogType['installed'] ? T_('installed') : T_('not installed')),
                true
            );
        }

        $interactor->info(T_('Localplay Controllers'), true);
        foreach ($this->pluginManager->getLocalplayTypes() as $localplay) {
            $interactor->ok(
                sprintf('  %s (%s)', $localplay['type'], $localplay['enabled'] ? T_('installed') : T_('not installed')),
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
