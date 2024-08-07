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
 *
 */

namespace Ampache\Config;

/**
 * The ConfigContainer is a containment for all of Ampache's configuration data
 */
interface ConfigContainerInterface
{
    /**
     * Replaces the internal config container
     */
    public function updateConfig(array $configuration): ConfigContainerInterface;

    /**
     * Compatibility accessor for direct access to the config array
     * Please use single methods for common keys
     */
    public function get(string $configKey);

    /**
     * Returns the name of the PHP session
     */
    public function getSessionName(): string;

    /**
     * Returns the webdav config state
     */
    public function isWebDavBackendEnabled(): bool;

    /**
     * Returns the authentication config state
     */
    public function isAuthenticationEnabled(): bool;

    /**
     * Returns the raw web path
     */
    public function getRawWebPath(): string;

    /**
     * Returns the web path
     */
    public function getWebPath(?bool $subfolder): string;

    /**
     * Return a list of types which are zip-able
     *
     * @return list<string>
     */
    public function getTypesAllowedForZip(): array;

    /**
     * Return the path to the composer binary
     */
    public function getComposerBinaryPath(): string;

    /**
     * Return the path to the composer binary
     */
    public function getComposerParameters(): string;

    /**
     * Return the path to the npm binary
     */
    public function getNpmBinaryPath(): string;

    /**
     * Check if a certain feature is enabled
     */
    public function isFeatureEnabled(string $feature): bool;

    /**
     * Returns the path to the files of the selected theme
     */
    public function getThemePath(): string;

    /**
     * Returns the debug mode state
     */
    public function isDebugMode(): bool;

    /**
     * Returns the demo mode state
     */
    public function isDemoMode(): bool;

    /**
     * Returns the current Ampache version
     */
    public function getVersion(): string;

    /**
     * Returns the path to the ampache config file
     */
    public function getConfigFilePath(): string;
}
