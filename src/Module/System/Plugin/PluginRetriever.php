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
 */

namespace Ampache\Module\System\Plugin;

use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\User;
use Generator;

/**
 * Provides access to available ampache plugins
 */
final class PluginRetriever implements PluginRetrieverInterface
{
    /**
     * Yields all loadable plugin of a certain type
     *
     * @todo migrate to php8+ enums
     *
     * @return Generator<Plugin>
     */
    public function retrieveByType(
        string $pluginType,
        User $user
    ): Generator {
        foreach (Plugin::get_plugins($pluginType) as $plugin_name) {
            $plugin = new Plugin($plugin_name);
            if (
                $plugin->_plugin !== null &&
                $plugin->load($user)
            ) {
                yield $plugin;
            }
        }
    }
}
