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

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\System\Core;
use Exception;
use WpOrg\Requests\Requests;

class AmpacheBitly extends AmpachePlugin implements PluginShortenerInterface
{
    public string $name        = 'Bit.ly';

    public string $categories  = 'shortener';

    public string $description = 'URL shorteners on shared links with Bit.ly';

    public string $url         = 'http://bitly.com';

    public string $version     = '000003';

    public string $min_ampache = '360037';

    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $bitly_token;

    private $bitly_group_guid;

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
        if (!Preference::insert('bitly_token', T_('Bit.ly Token'), '', AccessLevelEnum::MANAGER->value, 'string', 'plugins', $this->name)) {
            return false;
        }

        return Preference::insert('bitly_group_guid', T_('Bit.ly Group GUID'), '', AccessLevelEnum::MANAGER->value, 'string', 'plugins', $this->name);
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return (
            Preference::delete('bitly_username') &&
            Preference::delete('bitly_api_key') &&
            Preference::delete('bitly_token') &&
            Preference::delete('bitly_group_guid')
        );
    }

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade(): bool
    {
        // Remove v3 preferences
        Preference::delete('bitly_username');
        Preference::delete('bitly_api_key');

        $this->install();

        return true;
    }

    /**
     *shortener
     */
    public function shortener(string $url): ?string
    {
        if (empty($this->bitly_token) || empty($this->bitly_group_guid)) {
            debug_event('bitly.plugin', 'Bit.ly Token or Group GUID missing', 3);

            return '';
        }

        $headers = [
            'Authorization' => 'Bearer ' . $this->bitly_token,
            'Content-Type' => 'application/json'
        ];
        $data = [
            'group_guid' => $this->bitly_group_guid,
            'long_url' => $url,
        ];
        $apiurl = 'https://api-ssl.bitly.com/v4/shorten';

        try {
            debug_event('bitly.plugin', 'Bit.ly api call made', 4);
            $request = Requests::post($apiurl, $headers, json_encode($data), Core::requests_options());

            $result = json_decode($request->body);

            if ($result->errors) {
                if ($result->message === "INVALID_ARG_LONG_URL") {
                    debug_event('bitly.plugin', 'Bit.ly does not like that URL (if it is a localhost/127.0.0.1 URL that could be why)', 4);
                } else {
                    debug_event('bitly.plugin', 'Bit.ly returned an error: ' . $result->message, 4);
                }
            }

            if ($result->link) {
                debug_event('bitly.plugin', 'Bit.ly success: ' . $result->link, 4);

                return $result->link;
            }

            return null;
        } catch (Exception $exception) {
            debug_event('bitly.plugin', 'Bit.ly api http exception: ' . $exception->getMessage(), 1);

            return null;
        }
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     */
    public function load(User $user): bool
    {
        $user->set_preferences();
        $data = $user->prefs;
        // load system when nothing is given
        if (!strlen(trim((string) $data['bitly_token'])) || !strlen(trim((string) $data['bitly_group_guid']))) {
            $data                     = [];
            $data['bitly_token']      = Preference::get_by_user(-1, 'bitly_token');
            $data['bitly_group_guid'] = Preference::get_by_user(-1, 'bitly_group_guid');
        }

        if (strlen(trim((string) $data['bitly_token'])) !== 0) {
            $this->bitly_token = trim((string) $data['bitly_token']);
        } else {
            debug_event('bitly.plugin', 'No Bit.ly Token, shortener skipped', 3);

            return false;
        }

        if (strlen(trim((string) $data['bitly_group_guid'])) !== 0) {
            $this->bitly_group_guid = trim((string) $data['bitly_group_guid']);
        } else {
            debug_event('bitly.plugin', 'No Bit.ly Group GUID, shortener skipped', 3);

            return false;
        }

        return true;
    }
}
