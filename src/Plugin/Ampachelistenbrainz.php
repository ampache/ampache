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
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;

class Ampachelistenbrainz extends AmpachePlugin implements PluginSaveMediaplayInterface
{
    public string $name = 'ListenBrainz';

    public string $categories = 'scrobbling';

    public string $description = 'Records your played songs to your ListenBrainz Account';

    public string $url = '';

    public string $version = '000002';

    public string $min_ampache = '380004';

    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private string $token = '';

    private string $api_host = 'api.listenbrainz.org';

    private string $scheme = 'https';

    private string $host = 'listenbrainz.org';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Scrobble songs you play to your ListenBrainz Account');
        $this->url         = $this->scheme . '://' . $this->host;
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::insert('listenbrainz_token', T_('ListenBrainz User Token'), '', AccessLevelEnum::USER->value, 'string', 'plugins', $this->name)) {
            return false;
        }

        return Preference::insert('listenbrainz_api_url', T_('ListenBrainz API URL'), 'api.listenbrainz.org', AccessLevelEnum::USER->value, 'string', 'plugins', $this->name);
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return Preference::delete('listenbrainz_token');
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
            Preference::insert('listenbrainz_api_url', T_('ListenBrainz API URL'), 'api.listenbrainz.org', AccessLevelEnum::USER->value, 'string', 'plugins', $this->name);
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

        // Make sure there's actually a token before we keep going
        if (!$this->token) {
            debug_event('listenbrainz.plugin', 'Token missing', 5);

            return false;
        }

        if ($song->time < 30) {
            debug_event('listenbrainz.plugin', 'Song less then 30 seconds not queueing', 3);

            return false;
        }

        $album  = new Album($song->album);
        $artist = new Artist($song->artist);

        $additional_info = [];
        if ($song->mbid) {
            $additional_info['recording_mbid'] = $song->mbid;
        }

        if ($album->mbid) {
            $additional_info['release_mbid'] = $album->mbid;
        }

        if ($artist->mbid) {
            $additional_info['artist_mbid'] = $artist->mbid;
        }

        $track_metadata = [
            'additional_info' => $additional_info,
            'artist_name' => $artist->name,
            'track_name' => $song->title,
            'release_name' => $album->get_fullname(true),
        ];
        if ($additional_info === []) {
            $track_metadata = array_splice($track_metadata, 1);
        }

        $json = json_encode(
            [
                'listen_type' => 'single',
                'payload' => [
                    [
                        'listened_at' => time(),
                        'track_metadata' => $track_metadata
                    ]
                ]
            ]
        ) ?: '';
        debug_event('listenbrainz.plugin', 'Submission content: ' . $json, 5);
        $response = $this->post_json_url('/1/submit-listens', $json) ?: '';

        if (!$response || !strpos($response, "ok")) {
            debug_event('listenbrainz.plugin', "Submission Failed", 5);

            return false;
        }

        debug_event('listenbrainz.plugin', "Submission Successful", 5);

        return true;
    }

    /**
     * post_json_url
     * This is a generic poster for HTTP requests
     */
    private function post_json_url(string $url, string $content): ?string
    {
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Authorization: token ' . $this->token,
                    'Content-type: application/json; charset=utf-8',
                    'Content-length: ' . strlen($content)
                ],
                'content' => $content
            ]
        ];
        debug_event('listenbrainz.plugin', 'Submission option: ' . json_encode($opts), 5);
        $context = stream_context_create($opts);
        $target  = $this->scheme . '://' . $this->api_host . $url;

        return file_get_contents($target, false, $context) ?: null;
    }

    /**
     * set_flag
     * This takes care of spreading your love on ListenBrainz
     */
    public function set_flag(Song $song, bool $flagged): void
    {
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     */
    public function load(User $user): bool
    {
        $user->set_preferences();
        $data = $user->prefs;
        // check if user have a token
        if (strlen(trim((string) $data['listenbrainz_token'])) !== 0) {
            $this->token = trim((string) $data['listenbrainz_token']);
        } else {
            debug_event('listenbrainz.plugin', 'No token, not scrobbling (need to add your ListenBrainz api key to ampache)', 4);

            return false;
        }

        $this->api_host = $data['listenbrainz_api_url'] ?? 'api.listenbrainz.org';

        return true;
    }
}
