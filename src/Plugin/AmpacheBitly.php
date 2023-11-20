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

use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\System\Core;
use Exception;
use WpOrg\Requests\Requests;

class AmpacheBitly implements AmpachePluginInterface
{
    public string $name        = 'Bit.ly';
    public string $categories  = 'shortener';
    public string $description = 'URL shorteners on shared links with Bit.ly';
    public string $url         = 'http://bitly.com';
    public string $version     = '000002';
    public string $min_ampache = '360037';
    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $bitly_username;
    private $bitly_api_key;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('URL shorteners on shared links with Bit.ly');
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::exists('bitly_username') && !Preference::insert('bitly_username', T_('Bit.ly Username'), '', 75, 'string', 'plugins', $this->name)) {
            return false;
        }
        if (!Preference::exists('bitly_api_key') && !Preference::insert('bitly_api_key', T_('Bit.ly API key'), '', 75, 'string', 'plugins', $this->name)) {
            return false;
        }

        return true;
    } // install

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return (
            Preference::delete('bitly_username') &&
            Preference::delete('bitly_api_key')
        );
    } // uninstall

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade(): bool
    {
        return true;
    } // upgrade

    /**
     * @param string $url
     * @return string|false
     */
    public function shortener($url)
    {
        if (empty($this->bitly_username) || empty($this->bitly_api_key)) {
            debug_event('bitly.plugin', 'Bit.ly username or api key missing', 3);

            return '';
        }

        $apiurl = 'http://api.bit.ly/v3/shorten?login=' . $this->bitly_username . '&apiKey=' . $this->bitly_api_key . '&longUrl=' . urlencode($url) . '&format=json';
        try {
            debug_event('bitly.plugin', 'Bit.ly api call: ' . $apiurl, 5);
            $request = Requests::get($apiurl, array(), Core::requests_options());

            return json_decode($request->body)->data->url;
        } catch (Exception $error) {
            debug_event('bitly.plugin', 'Bit.ly api http exception: ' . $error->getMessage(), 1);

            return false;
        }
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     * @param User $user
     */
    public function load($user): bool
    {
        $user->set_preferences();
        $data = $user->prefs;
        // load system when nothing is given
        if (!strlen(trim($data['bitly_username'])) || !strlen(trim($data['bitly_api_key']))) {
            $data                   = array();
            $data['bitly_username'] = Preference::get_by_user(-1, 'bitly_username');
            $data['bitly_api_key']  = Preference::get_by_user(-1, 'bitly_api_key');
        }

        if (strlen(trim($data['bitly_username']))) {
            $this->bitly_username = trim($data['bitly_username']);
        } else {
            debug_event('bitly.plugin', 'No Bit.ly username, shortener skipped', 3);

            return false;
        }
        if (strlen(trim($data['bitly_api_key']))) {
            $this->bitly_api_key = trim($data['bitly_api_key']);
        } else {
            debug_event('bitly.plugin', 'No Bit.ly api key, shortener skipped', 3);

            return false;
        }

        return true;
    } // load
}
