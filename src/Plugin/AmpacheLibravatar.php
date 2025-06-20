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
use Ampache\Module\System\Core;

class AmpacheLibravatar extends AmpachePlugin implements PluginGetAvatarUrlInterface
{
    public string $name = 'Libravatar';

    public string $categories = 'avatar';

    public string $description = 'Users avatar\'s with Libravatar';

    public string $url = 'https://www.libravatar.org';

    public string $version = '000001';

    public string $min_ampache = '360040';

    public string $max_ampache = '999999';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_("Users avatar's with Libravatar");
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        return true;
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return true;
    }

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade(): bool
    {
        return true;
    }

    /**
     * get_avatar_url
     */
    public function get_avatar_url(User $user, ?int $size = 80): string
    {
        $url = "";
        if (
            $user->email !== null &&
            $user->email !== '' &&
            $user->email !== '0'
        ) {
            // Federated Servers are not supported here without libravatar.org. Should query DNS server first.
            if (isset($_SERVER['HTTPS']) && Core::get_server('HTTPS') !== 'off') {
                $url = "https://seccdn.libravatar.org";
            } else {
                $url = "http://cdn.libravatar.org";
            }

            $url .= "/avatar/";
            $url .= md5(strtolower(trim($user->email)));
            $url .= "?s=" . $size . "&r=g";
            $url .= "&d=identicon";
        }

        return $url;
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     */
    public function load(User $user): bool
    {
        unset($user);

        return true;
    }
}
