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

use SimpleXMLElement;
use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Playback\Scrobble\Scrobbler;

class Ampachelibrefm extends AmpachePlugin implements PluginSaveMediaplayInterface
{
    public string $name = 'Libre.FM';

    public string $categories = 'scrobbling';

    public string $description = 'Records your played songs to your Libre.FM Account';

    public string $url = '';

    public string $version = '000003';

    public string $min_ampache = '360003';

    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private int $user_id = 0;

    private $challenge;

    private string $api_key = '';

    private string $secret = '';

    private string $scheme = 'https';

    private string $host = 'libre.fm';

    private string $api_host = 'libre.fm';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Scrobble songs you play to your Libre.FM Account');
        $this->url         = $this->scheme . '://' . $this->host;
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::insert('librefm_challenge', T_('Libre.FM Submit Challenge'), '', AccessLevelEnum::USER->value, 'string', 'internal', $this->name)) {
            return false;
        }

        return Preference::insert('librefm_grant_link', T_('Libre.FM Grant URL'), '', AccessLevelEnum::USER->value, 'string', 'plugins', $this->name);
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return (
            Preference::delete('librefm_challenge') &&
            Preference::delete('librefm_grant_link') &&
            Preference::delete('librefm_pass') &&
            Preference::delete('librefm_md5_pass') &&
            Preference::delete('librefm_user') &&
            Preference::delete('librefm_url') &&
            Preference::delete('librefm_host') &&
            Preference::delete('librefm_port')
        );
    }

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade(): bool
    {
        $from_version = Plugin::get_plugin_version($this->name);
        if ($from_version == 0) {
            return false;
        }

        if ($from_version < 2) {
            Preference::rename('librefm_pass', 'librefm_md5_pass');
        }

        if ($from_version < (int)$this->version) {
            Preference::delete('librefm_md5_pass');
            Preference::delete('librefm_user');
            Preference::delete('librefm_url');
            Preference::delete('librefm_host');
            Preference::delete('librefm_port');
            Preference::insert('librefm_grant_link', T_('Libre.FM Grant URL'), '', AccessLevelEnum::USER->value, 'string', 'plugins', $this->name);
        }

        return true;
    }

    /**
     * save_mediaplay
     * This takes care of queueing and then submitting the tracks.
     */
    public function save_mediaplay(Song $song): bool
    {
        // Only support songs
        if ($song::class !== Song::class) {
            return false;
        }

        // Make sure there's actually a session before we keep going
        if (!$this->challenge) {
            debug_event(self::class, 'Session key missing', 5);

            return false;
        }

        if ($song->time < 30) {
            debug_event(self::class, 'Song less then 30 seconds not queueing', 3);

            return false;
        }

        // Create our scrobbler and then queue it
        $scrobbler = new Scrobbler($this->api_key, $this->scheme, $this->api_host, $this->challenge, $this->secret);

        // Check to see if the scrobbling works by queueing song
        if (!$scrobbler->queue_track($song->get_artist_fullname(), $song->get_album_fullname(), (string)$song->title, time(), $song->time, (int)$song->track)) {
            return false;
        }

        // Go ahead and submit it now
        if (!$scrobbler->submit_tracks()) {
            debug_event(self::class, 'Error Submit Failed: ' . $scrobbler->error_msg, 3);

            return false;
        }

        debug_event(self::class, 'Submission Successful', 5);

        return true;
    }

    /**
     * set_flag
     * This takes care of spreading your love on Libre.fm
     */
    public function set_flag(Song $song, bool $flagged): void
    {
        // Make sure there's actually a session before we keep going
        if (!$this->challenge) {
            debug_event(self::class, 'Session key missing', 5);

            return;
        }

        // Create our scrobbler and then queue it
        $scrobbler = new Scrobbler($this->api_key, $this->scheme, $this->api_host, $this->challenge, $this->secret);
        if (!in_array($song->get_artist_fullname(), ['', '0'], true) && !$scrobbler->love($flagged, $song->get_artist_fullname(), (string)$song->title)) {
            debug_event(self::class, 'Error Love Failed: ' . $scrobbler->error_msg, 3);

            return;
        }

        debug_event(self::class, 'Sent Love Successfully', 5);
    }

    /**
     * get_session
     * This call the getSession method and properly updates the preferences as needed.
     * This requires a userid so it knows whose crap to update.
     * @param string $token
     */
    public function get_session($token): bool
    {
        $scrobbler   = new Scrobbler($this->api_key, $this->scheme, $this->api_host, '', $this->secret);
        $session_key = $scrobbler->get_session_key($token);
        if (!$session_key instanceof SimpleXMLElement) {
            debug_event(self::class, 'getSession Failed: ' . $scrobbler->error_msg, 3);

            return false;
        }

        $this->challenge = $session_key;

        // Update the preferences
        Preference::update('librefm_challenge', $this->user_id, $session_key);
        debug_event(self::class, 'getSession Successful', 3);

        return true;
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     */
    public function load(User $user): bool
    {
        $this->api_key = AmpConfig::get('lastfm_api_key');
        $this->secret  = '';
        $user->set_preferences();
        $data          = $user->prefs;
        $this->user_id = $user->id;
        // check if user have a session key
        if (strlen(trim((string) $data['librefm_challenge'])) !== 0) {
            $this->challenge = trim((string) $data['librefm_challenge']);
        } else {
            debug_event(self::class, 'No session key, not scrobbling (need to grant Ampache to libre.fm)', 4);

            return false;
        }

        return true;
    }
}
