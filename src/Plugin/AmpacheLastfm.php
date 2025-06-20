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

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Playback\Scrobble\Scrobbler;

class AmpacheLastfm extends AmpachePlugin implements PluginSaveMediaplayInterface
{
    public string $name = 'Last.FM';

    public string $categories = 'scrobbling';

    public string $description = 'Records your played songs to your Last.FM account';

    public string $url = '';

    public string $version = '000005';

    public string $min_ampache = '360003';

    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private int $user_id = 0;

    private $challenge;

    private ?string $api_key = null;

    private ?string $secret = null;

    private string $scheme = 'http';

    private string $host = 'www.last.fm';

    private string $api_host = 'ws.audioscrobbler.com';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Scrobble songs you play to your Last.FM account');
        $this->url         = $this->scheme . '://' . $this->host;
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::insert('lastfm_challenge', T_('Last.FM Submit Challenge'), '', AccessLevelEnum::USER->value, 'string', 'internal', $this->name)) {
            return false;
        }

        return Preference::insert('lastfm_grant_link', T_('Last.FM Grant URL'), '', AccessLevelEnum::USER->value, 'string', 'plugins', $this->name);
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return (
            Preference::delete('lastfm_challenge') &&
            Preference::delete('lastfm_grant_link') &&
            Preference::delete('lastfm_pass') &&
            Preference::delete('lastfm_md5_pass') &&
            Preference::delete('lastfm_user') &&
            Preference::delete('lastfm_url') &&
            Preference::delete('lastfm_host') &&
            Preference::delete('lastfm_port')
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

        if ($from_version < 4) {
            Preference::rename('lastfm_pass', 'lastfm_md5_pass');
        }

        if ($from_version < (int)$this->version) {
            Preference::delete('lastfm_md5_pass');
            Preference::delete('lastfm_user');
            Preference::delete('lastfm_url');
            Preference::delete('lastfm_host');
            Preference::delete('lastfm_port');
            Preference::insert('lastfm_grant_link', T_('Last.FM Grant URL'), '', AccessLevelEnum::USER->value, 'string', 'plugins', $this->name, true);
            Preference::insert('lastfm_challenge', T_('Last.FM Submit Challenge'), '', AccessLevelEnum::USER->value, 'string', 'internal', $this->name, true);
        }

        return true;
    }

    /**
     * save_mediaplay
     * This takes care of queueing and then submitting the tracks.
     */
    public function save_mediaplay(Song $song): bool
    {
        if (!$this->api_key) {
            return false;
        }

        // Only support songs
        if ($song::class !== Song::class) {
            return false;
        }

        // Make sure there's actually a session before we keep going
        if (!$this->challenge) {
            debug_event('lastfm.plugin', 'Session key missing', 5);

            return false;
        }

        if ($song->time < 30) {
            debug_event('lastfm.plugin', 'Song less then 30 seconds not queueing', 3);

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
            debug_event('lastfm.plugin', 'Error Submit Failed: ' . $scrobbler->error_msg, 3);

            return false;
        }

        debug_event('lastfm.plugin', 'Submission Successful', 5);

        return true;
    }

    /**
     * set_flag
     * This takes care of spreading your love on Last.fm
     */
    public function set_flag(Song $song, bool $flagged): void
    {
        if (!$this->api_key) {
            return;
        }

        // Make sure there's actually a session before we keep going
        if (!$this->challenge) {
            debug_event('lastfm.plugin', 'Session key missing', 5);

            return;
        }

        // Create our scrobbler and then queue it
        $scrobbler = new Scrobbler($this->api_key, $this->scheme, $this->api_host, $this->challenge, $this->secret);
        if (!empty($song->get_artist_fullname()) && !$scrobbler->love($flagged, $song->get_artist_fullname(), (string)$song->title)) {
            debug_event('lastfm.plugin', 'Error Love Failed: ' . $scrobbler->error_msg, 3);

            return;
        }

        debug_event('lastfm.plugin', 'Sent Love Successfully', 5);
    }

    /**
     * get_session
     * This call the getSession method and properly updates the preferences as needed.
     * This requires a userid so it knows whose crap to update.
     * @param string $token
     */
    public function get_session($token): bool
    {
        if (!$this->api_key) {
            return false;
        }

        $scrobbler   = new Scrobbler($this->api_key, $this->scheme, $this->api_host, '', $this->secret);
        $session_key = $scrobbler->get_session_key($token);
        if (!$session_key) {
            debug_event('lastfm.plugin', 'getSession Failed: ' . $scrobbler->error_msg, 3);

            return false;
        }

        $this->challenge = $session_key;

        // Update the preferences
        Preference::update('lastfm_challenge', $this->user_id, $session_key);
        debug_event('lastfm.plugin', 'getSession Successful', 3);

        return true;
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     */
    public function load(User $user): bool
    {
        $this->api_key = AmpConfig::get('lastfm_api_key');
        $this->secret  = AmpConfig::get('lastfm_api_secret');
        $user->set_preferences();
        $data          = $user->prefs;
        $this->user_id = $user->id;
        // check if user have a session key
        if (strlen(trim((string) $data['lastfm_challenge'])) !== 0) {
            $this->challenge = trim((string) $data['lastfm_challenge']);
        } else {
            debug_event('lastfm.plugin', 'No session key, not scrobbling (need to grant Ampache to last.fm)', 4);

            return false;
        }

        return true;
    }
}
