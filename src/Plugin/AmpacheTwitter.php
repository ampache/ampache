<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
declare(strict_types=0);

namespace Ampache\Plugin;

use Ampache\Repository\Model\User;

class AmpacheTwitter implements AmpachePluginInterface
{
    public string $name        = 'Twitter';
    public string $categories  = 'share';
    public string $description = 'Twitter share';
    public string $url         = 'https://twitter.com';
    public string $version     = '000001';
    public string $min_ampache = '370027';
    public string $max_ampache = '999999';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Twitter share');
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
     * external_share
     * @param string $url
     * @param string $text
     */
    public function external_share($url, $text): string
    {
        $share = "https://twitter.com/share";
        $share .= "?url=" . rawurlencode($url);
        if (!empty($text)) {
            $share .= "&text=" . rawurlencode($text);
        }

        return $share;
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     * @param User $user
     */
    public function load($user): bool
    {
        $user->set_preferences();

        return true;
    }
}
