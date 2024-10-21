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

use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Wanted;
use Exception;
use WpOrg\Requests\Requests;

class AmpacheHeadphones implements AmpachePluginInterface
{
    public string $name        = 'Headphones';
    public string $categories  = 'wanted';
    public string $description = 'Automatically download accepted Wanted List albums with Headphones';
    public string $url         = 'https://github.com/rembo10/headphones/';
    public string $version     = '000001';
    public string $min_ampache = '360030';
    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $api_url;
    private $api_key;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Automatically download accepted Wanted List albums with Headphones');
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::insert('headphones_api_url', T_('Headphones URL'), '', 25, 'string', 'plugins', $this->name)) {
            return false;
        }
        if (!Preference::insert('headphones_api_key', T_('Headphones API key'), '', 25, 'string', 'plugins', $this->name)) {
            return false;
        }

        return true;
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return (
            Preference::delete('headphones_api_url') &&
            Preference::delete('headphones_api_key')
        );
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
     * process_wanted
     * This takes care of auto-download accepted Wanted List albums
     * @param Wanted $wanted
     */
    public function process_wanted($wanted): bool
    {
        set_time_limit(0);

        $headartist = json_decode(
            $this->headphones_call('getArtist', ['id' => $wanted->artist_mbid])
        );

        // No artist info, need to add artist to Headphones first. Can be long!
        if (!$headartist->artist) {
            $this->headphones_call('addArtist', ['id' => $wanted->artist_mbid]);
        }

        return ($this->headphones_call('queueAlbum', ['id' => $wanted->mbid]) == 'OK');
    }

    /**
     * @param string $command
     * @param array $params
     */
    protected function headphones_call($command, $params): string
    {
        if (empty($this->api_url) || empty($this->api_key)) {
            debug_event(self::class, 'Headphones url or api key missing', 3);

            return '';
        }

        $url = $this->api_url . '/api?apikey=' . $this->api_key . '&cmd=' . $command;
        foreach ($params as $key => $value) {
            $url .= '&' . $key . '=' . urlencode($value);
        }

        debug_event(self::class, 'Headphones api call: ' . $url, 5);
        try {
            // We assume Headphone server is local, don't use proxy here
            $request = Requests::get($url, [], [
                'timeout' => 600
            ]);
        } catch (Exception $error) {
            debug_event(self::class, 'Headphones api http exception: ' . $error->getMessage(), 1);

            return '';
        }

        return $request->body;
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
        if (!strlen(trim($data['headphones_api_url'])) || !strlen(trim($data['headphones_api_key']))) {
            $data                       = [];
            $data['headphones_api_url'] = Preference::get_by_user(-1, 'headphones_api_url');
            $data['headphones_api_key'] = Preference::get_by_user(-1, 'headphones_api_key');
        }

        if (strlen(trim($data['headphones_api_url']))) {
            $this->api_url = rtrim(trim($data['headphones_api_url']), '/');
        } else {
            debug_event(self::class, 'No Headphones url, auto download skipped', 3);

            return false;
        }
        if (strlen(trim($data['headphones_api_key']))) {
            $this->api_key = trim($data['headphones_api_key']);
        } else {
            debug_event(self::class, 'No Headphones api key, auto download skipped', 3);

            return false;
        }

        return true;
    }
}
