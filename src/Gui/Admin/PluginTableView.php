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

namespace Ampache\Gui\Admin;

use Ampache\Module\System\Plugin\Plugin;
use Override;

/**
 * The plugins an install can turn on, and upgrade once their shipped version moves ahead.
 */
final class PluginTableView extends AbstractModuleTableView
{
    /**
     * @param array<string, string> $pluginNames
     */
    public function __construct(
        string $adminPath,
        private readonly array $pluginNames,
    ) {
        parent::__construct($adminPath);
    }

    #[Override]
    public function getColumns(): array
    {
        return [
            ['class' => 'cel_name', 'label' => T_('Name')],
            ['class' => 'cel_description', 'label' => T_('Description')],
            ['class' => 'cel_category', 'label' => T_('Category')],
            ['class' => 'cel_version', 'label' => T_('Version')],
            ['class' => 'cel_iversion', 'label' => T_('Installed Version')],
            ['class' => 'cel_action', 'label' => T_('Action')],
        ];
    }

    #[Override]
    protected function buildRows(): array
    {
        $rows = [];
        foreach ($this->pluginNames as $pluginName) {
            $plugin = new Plugin($pluginName);
            if ($plugin->_plugin === null) {
                continue;
            }

            $installedVersion = Plugin::get_plugin_version($plugin->_plugin->name);

            $rows[] = [
                $this->e(T_($plugin->_plugin->name)),
                $this->e($plugin->_plugin->description),
                $this->e($plugin->_plugin->categories),
                $this->e($plugin->_plugin->version),
                $this->e($installedVersion),
                $this->getActionLinks($pluginName, $installedVersion, (int) $plugin->_plugin->version),
            ];
        }

        return $rows;
    }

    /**
     * An installed plugin whose shipped version has moved ahead offers an upgrade beside the deactivate link.
     */
    private function getActionLinks(string $pluginName, int $installedVersion, int $shippedVersion): string
    {
        $link = fn(string $action, string $label): string => sprintf(
            '<a href="%s/modules.php?action=%s&plugin=%s">%s</a>',
            $this->e($this->getAdminPath()),
            $action,
            urlencode($pluginName),
            $this->e($label)
        );

        if ($installedVersion === 0) {
            return $link('confirm_install_plugin', T_('Activate'));
        }

        $links = $link('confirm_uninstall_plugin', T_('Deactivate'));
        if ($installedVersion < $shippedVersion) {
            $links .= '<br>' . $link('upgrade_plugin', T_('Upgrade'));
        }

        return $links;
    }
}
