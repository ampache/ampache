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

/**
 * Shared, headless install/uninstall/upgrade operations for the three pluggable module kinds
 * (application plugins, catalog-type backends and localplay controllers) that the web admin,
 * CLI and API surfaces all drive through one path so behaviour cannot drift.
 */
interface PluginManagerInterface
{
    /**
     * All catalog-type backends keyed by type
     *
     * @return array<string, array{type: string, installed: bool, version: string, description: string}>
     */
    public function getCatalogTypes(): array;

    /**
     * All loadable localplay controllers keyed by type
     *
     * @return array<string, array{type: string, enabled: bool, version: string, description: string}>
     */
    public function getLocalplayTypes(): array;

    /**
     * All known application plugins keyed by name
     *
     * @return array<string, array{name: string, installedVersion: int, availableVersion: int, installed: bool, upgradeAvailable: bool}>
     */
    public function getPlugins(): array;

    /**
     * Install (enable) a catalog-type backend. Returns false when the type is unknown.
     */
    public function installCatalogType(string $type): bool;

    /**
     * Install (enable) a localplay controller and apply the same preference side effects as the web admin:
     * enable localplay playback, set the localplay level and controller for the given user.
     * Returns false when the controller cannot be loaded.
     */
    public function installLocalplay(string $type, int $userId): bool;

    /**
     * Install an application plugin and rebuild the preference tables so its preferences appear.
     * Returns false when the plugin name is unknown or the install fails.
     */
    public function installPlugin(string $name): bool;

    /**
     * Uninstall (disable) a catalog-type backend. Returns false when the type is unknown.
     */
    public function uninstallCatalogType(string $type): bool;

    /**
     * Uninstall (disable) a localplay controller.
     */
    public function uninstallLocalplay(string $type): bool;

    /**
     * Uninstall an application plugin and rebuild the preference tables.
     * Returns false when the plugin name is unknown.
     */
    public function uninstallPlugin(string $name): bool;

    /**
     * Upgrade an installed application plugin and rebuild the preference tables.
     * Returns false when the plugin name is unknown.
     */
    public function upgradePlugin(string $name): bool;
}
