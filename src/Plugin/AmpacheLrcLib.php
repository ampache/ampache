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
use Ampache\Module\Playback\Stream;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use WpOrg\Requests\Requests;

class AmpacheLrcLib extends AmpachePlugin implements PluginGetLyricsInterface
{
    public string $name = 'LrcLib';

    public string $categories = 'lyrics';

    public string $description = 'Get lyrics from an LrcLib compatible server';

    public string $url = 'https://lrclib.net/';

    public string $version = '000001';

    public string $min_ampache = '360022';

    public string $max_ampache = '999999';

    public string $site_url = 'https://lrclib.net';

    public string $user_agent = 'Ampache-LrcLib-Plugin/1.0';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Get lyrics from an LrcLib compatible server');
    }

    /**
     * @return null|array<int, array{
     *     id: int,
     *     name: string,
     *     trackName: string,
     *     artistName: string,
     *     albumName: string,
     *     duration: float,
     *     plainLyrics: string|null,
     *     syncedLyrics: string|null,
     * }>
     */
    private function _query_server(string $path_str, string $query_str = ''): ?array
    {
        $url = (!empty($query_str))
            ? $this->site_url . $path_str . '?' . $query_str
            : $this->site_url . $path_str;

        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => $this->user_agent
        ];

        $request = Requests::get($url, $headers);

        // sleep for 0.5s
        usleep(500000);

        $response = json_decode($request->body, true);

        return ($request->success && is_array($response))
            ? $response
            : null;
    }

    /**
     * install
     * This is a required plugin function
     */
    public function install(): bool
    {
        if (!Preference::insert('lrclib_site_url', T_('LrcLib site URL'), '', AccessLevelEnum::MANAGER->value, 'string', 'plugins', $this->name)) {
            return false;
        }

        return true;
    }

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall(): bool
    {
        return Preference::delete('lrclib_site_url');
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
     * load
     * This is a required plugin function; here it populates the prefs we
     * need for this object.
     */
    public function load(User $user): bool
    {
        $user->set_preferences();
        $data = $user->prefs;
        // load system when nothing is given
        if (!strlen(trim((string) $data['lrclib_site_url']))) {
            $data                    = [];
            $data['lrclib_site_url'] = Preference::get_by_user(-1, 'lrclib_site_url');
        }

        if (strlen(trim((string) $data['lrclib_site_url'])) !== 0) {
            $site_url = trim((string) $data['lrclib_site_url']);
        } else {
            debug_event(self::class, 'No LrcLib site URL, metadata plugin skipped', 3);

            return false;
        }

        $this->site_url   = rtrim($site_url, '/');
        $this->user_agent = 'Ampache/' . AmpConfig::get('version') . ' (' . Stream::get_base_url() . ')';

        return true;
    }

    /**
     * get_lyrics
     * This will look web services for a song lyrics.
     * @return null|array{'text': string, 'url': string}
     */
    public function get_lyrics(Song $song): ?array
    {
        debug_event(self::class, 'get_lyrics', 3);
        $response = $this->_query_server(
            '/api/search',
            'track_name=' . urlencode((string)$song->title) . '&artist_name=' . urlencode($song->get_artist_fullname()) . '&album_name=' . urlencode($song->get_album_fullname())
        );
        if (is_array($response)) {
            foreach ($response as $item) {
                if (
                    $item['duration'] &&
                    (int)$item['duration'] === $song->time &&
                    $item['trackName'] === $song->title &&
                    $item['artistName'] === $song->get_artist_fullname() &&
                    $item['albumName'] === $song->get_album_fullname() &&
                    !empty($item['plainLyrics'])
                ) {
                    return [
                        'text' => nl2br((string)$item['plainLyrics']),
                        'url' => $this->site_url . '/api/get/' . $item['id']
                    ];
                }
            }
        }

        return null;
    }
}
