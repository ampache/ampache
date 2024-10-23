<?php

declare(strict_types=0);

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

namespace Ampache\Plugin;

use Ampache\Repository\Model\User;

abstract class AmpachePlugin implements AmpachePluginInterface
{
    public string $name;

    public string $categories;

    public string $description;

    public string $url;

    public string $version;

    public string $min_ampache;

    public string $max_ampache;

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    abstract public function install(): bool;

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    abstract public function uninstall(): bool;

    /**
     * upgrade
     * This is a recommended plugin function
     */
    abstract public function upgrade(): bool;

    /**
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     */
    abstract public function load(User $user): bool;
}
