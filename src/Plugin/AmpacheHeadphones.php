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
use Ampache\Repository\Model\Wanted;
use Exception;
use WpOrg\Requests\Requests;

class AmpacheHeadphones extends AmpachePlugin implements PluginProcessWantedInterface
{
    public string $name = 'Headphones';

    public string $categories = 'wanted';

    public string $description = 'Automatically download accepted Wanted List albums with Headphones';

    public string $url = 'https://github.com/rembo10/headphones/';

    public string $version = '000001';

    public string $min_ampache = '360030';

    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private string $api_url;

    private string $api_key;

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
        if (!Preference::insert('headphones_api_url', T_('Headphones URL'), '', AccessLevelEnum::USER->value, 'string', 'plugins', $this->name)) {
            return false;
        }

        return Preference::insert('headphones_api_key', T_('Headphones API key'), '', AccessLevelEnum::USER->value, 'string', 'plugins', $this->name);
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
     */
    public function process_wanted(Wanted $wanted): bool
    {
        set_time_limit(0);

        $headartist = json_decode(
            $this->headphones_call('getArtist', ['id' => $wanted->artist_mbid])
        );

        // No artist info, need to add artist to Headphones first. Can be long!
        if (!$headartist->artist) {
            $this->headphones_call('addArtist', ['id' => $wanted->artist_mbid]);
        }

        return ($this->headphones_call('queueAlbum', ['id' => $wanted->mbid]) === 'OK');
    }

    /**
     * @param array<string, null|string> $params
     */
    protected function headphones_call(string $command, array $params): string
    {
        if (
            (!isset($this->api_url) || ($this->api_url === '' || $this->api_url === '0')) ||
            (!isset($this->api_key) || ($this->api_key === '' || $this->api_key === '0'))
        ) {
            debug_event(self::class, 'Headphones url or api key missing', 3);

            return '';
        }

        $url = $this->api_url . '/api?apikey=' . $this->api_key . '&cmd=' . $command;
        foreach ($params as $key => $value) {
            $url .= '&' . $key . '=' . urlencode((string) $value);
        }

        debug_event(self::class, 'Headphones api call: ' . $url, 5);
        try {
            // We assume Headphone server is local, don't use proxy here
            $request = Requests::get($url, [], ['timeout' => 600]);
        } catch (Exception $exception) {
            debug_event(self::class, 'Headphones api http exception: ' . $exception->getMessage(), 1);

            return '';
        }

        return $request->body;
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
        if (!strlen(trim((string) $data['headphones_api_url'])) || !strlen(trim((string) $data['headphones_api_key']))) {
            $data                       = [];
            $data['headphones_api_url'] = Preference::get_by_user(-1, 'headphones_api_url');
            $data['headphones_api_key'] = Preference::get_by_user(-1, 'headphones_api_key');
        }

        if (strlen(trim((string) $data['headphones_api_url'])) !== 0) {
            $this->api_url = rtrim(trim((string) $data['headphones_api_url']), '/');
        } else {
            debug_event(self::class, 'No Headphones url, auto download skipped', 3);

            return false;
        }

        if (strlen(trim((string) $data['headphones_api_key'])) !== 0) {
            $this->api_key = trim((string) $data['headphones_api_key']);
        } else {
            debug_event(self::class, 'No Headphones api key, auto download skipped', 3);

            return false;
        }

        return true;
    }
}
