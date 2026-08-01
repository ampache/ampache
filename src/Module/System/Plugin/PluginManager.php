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

namespace Ampache\Module\System\Plugin;

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Localplay\LocalPlayTypeEnum;
use Ampache\Module\System\Preference;
use Ampache\Repository\Model\User;

/**
 * Wraps the static plugin/catalog-type/localplay model methods behind plain parameters, carrying the
 * post-install preference side effects so every surface applies them identically.
 */
final class PluginManager implements PluginManagerInterface
{
    public function getCatalogTypes(): array
    {
        $result = [];
        foreach (array_keys(Catalog::CATALOG_TYPES) as $type) {
            $catalog = Catalog::create_catalog_type((string) $type);
            if ($catalog === null) {
                continue;
            }

            $result[(string) $type] = [
                'type' => $catalog->get_type(),
                'installed' => $catalog->is_installed(),
                'version' => $catalog->get_version(),
                'description' => $catalog->get_description(),
            ];
        }

        return $result;
    }

    public function getLocalplayTypes(): array
    {
        $result = [];
        foreach (array_keys(LocalPlayTypeEnum::TYPE_MAPPING) as $type) {
            $localplay = new LocalPlay((string) $type);
            if (!$localplay->player_loaded()) {
                continue;
            }

            $result[(string) $type] = [
                'type' => $localplay->type,
                'enabled' => LocalPlay::is_enabled((string) $type),
                'version' => $localplay->get_f_version(),
                'description' => $localplay->get_f_description(),
            ];
        }

        return $result;
    }

    public function getPlugins(): array
    {
        $result = [];
        foreach (array_keys(Plugin::get_plugins()) as $name) {
            $plugin = new Plugin($name);
            if ($plugin->_plugin === null) {
                continue;
            }

            $installed = Plugin::get_plugin_version($plugin->_plugin->name);
            $available = (int) $plugin->_plugin->version;

            $result[$name] = [
                'name' => $name,
                'installedVersion' => $installed,
                'availableVersion' => $available,
                'installed' => $installed !== 0,
                'upgradeAvailable' => $installed !== 0 && $installed < $available,
            ];
        }

        return $result;
    }

    public function installCatalogType(string $type): bool
    {
        $catalog = Catalog::create_catalog_type($type);
        if ($catalog === null) {
            return false;
        }

        return $catalog->install();
    }

    public function installLocalplay(string $type, int $userId): bool
    {
        $localplay = new LocalPlay($type);
        if (!$localplay->player_loaded()) {
            return false;
        }

        $localplay->install();

        // Mirror the web admin: enable playback globally, then set this user's localplay level and controller
        Preference::update('allow_localplay_playback', -1, '1');
        Preference::update('localplay_level', $userId, AccessLevelEnum::ADMIN->value);
        Preference::update('localplay_controller', $userId, $localplay->type);

        return true;
    }

    public function installPlugin(string $name): bool
    {
        if (!array_key_exists($name, Plugin::get_plugins())) {
            return false;
        }

        $plugin = new Plugin($name);
        if ($plugin->_plugin === null || !$plugin->install()) {
            return false;
        }

        // Newly-installed preferences only surface once the per-user tables are rebuilt from the fresh definitions
        Preference::clear_from_session();
        Preference::rebuild_all_preferences();

        return true;
    }

    public function uninstallCatalogType(string $type): bool
    {
        $catalog = Catalog::create_catalog_type($type);
        if ($catalog === null) {
            return false;
        }

        $catalog->uninstall();

        return true;
    }

    public function uninstallLocalplay(string $type): bool
    {
        (new LocalPlay($type))->uninstall();

        return true;
    }

    public function uninstallPlugin(string $name): bool
    {
        if (!array_key_exists($name, Plugin::get_plugins())) {
            return false;
        }

        $result = (new Plugin($name))->uninstall();
        Preference::rebuild_all_preferences();

        return $result;
    }

    public function upgradePlugin(string $name): bool
    {
        if (!array_key_exists($name, Plugin::get_plugins())) {
            return false;
        }

        $result = (new Plugin($name))->upgrade();
        Preference::rebuild_all_preferences();

        return $result;
    }
}
