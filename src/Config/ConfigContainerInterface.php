<?php

declare(strict_types=1);

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

namespace Ampache\Config;

/**
 * The ConfigContainer is a containment for all of ampaches configuration data
 */
interface ConfigContainerInterface
{
    /**
     * Replaces the internal config container
     */
    public function updateConfig(array $configuration): ConfigContainerInterface;

    /**
     * Compatibility accessor for direct access to the config array
     *
     * @deprecated Use a single method for each config key
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
    public function getWebPath(): string;
}
