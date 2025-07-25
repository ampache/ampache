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
use Ampache\Module\Util\OAuth\OAuthConsumer;
use Ampache\Module\Util\OAuth\OAuthRequest;
use Ampache\Module\Util\OAuth\OAuthSignatureMethod_HMAC_SHA1;

class Ampache7digital extends AmpachePlugin implements PluginSongPreviewInterface
{
    public string $name = '7digital';

    public string $categories = 'preview';

    public string $description = 'Song preview from 7digital';

    public string $url = 'http://www.7digital.com';

    public string $version = '000001';

    public string $min_ampache = '370015';

    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private string $api_key;

    private string $secret;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Song preview from 7digital');
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (Preference::exists('7digital_api_key') && !Preference::insert('7digital_api_key', T_('7digital consumer key'), '', AccessLevelEnum::MANAGER->value, 'string', 'plugins', $this->name)) {
            return false;
        }

        return !(Preference::exists('7digital_secret_api_key') && !Preference::insert('7digital_secret_api_key', T_('7digital secret'), '', AccessLevelEnum::MANAGER->value, 'string', 'plugins', $this->name));
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return (
            Preference::delete('7digital_api_key') &&
            Preference::delete('7digital_secret_api_key')
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
     * Get song preview.
     */
    public function get_song_preview(string $track_mbid, string $artist_name, string $title): array
    {
        return [];
    }

    /**
     * stream_song_preview
     */
    public function stream_song_preview(string $file): void
    {
        if (str_contains($file, "7digital")) {
            $consumer = new OAuthConsumer($this->api_key, $this->secret, null);
            $request  = OAuthRequest::from_consumer_and_token($consumer, null, 'GET', $file);
            $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, null);
            $url = $request->to_url();

            header("Location: " . $url);
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
        if (!strlen(trim((string) $data['7digital_api_key'])) || !strlen(trim((string) $data['7digital_secret_api_key']))) {
            $data                            = [];
            $data['7digital_api_key']        = Preference::get_by_user(-1, '7digital_api_key');
            $data['7digital_secret_api_key'] = Preference::get_by_user(-1, '7digital_secret_api_key');
        }

        if (strlen(trim((string) $data['7digital_api_key'])) !== 0) {
            $this->api_key = trim((string) $data['7digital_api_key']);
        } else {
            debug_event(self::class, 'No 7digital api key, song preview plugin skipped', 3);

            return false;
        }

        if (strlen(trim((string) $data['7digital_secret_api_key'])) !== 0) {
            $this->secret = trim((string) $data['7digital_secret_api_key']);
        } else {
            debug_event(self::class, 'No 7digital secret, song preview plugin skipped', 3);

            return false;
        }

        return true;
    }
}
