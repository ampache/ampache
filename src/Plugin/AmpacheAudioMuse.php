<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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
use Ampache\Module\Api\OpenSubsonic_Api;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Playback\Stream;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Override;
use WpOrg\Requests\Requests;

/**
 * Sonic similarity from an AudioMuse-AI instance, which analyses the audio itself and indexes each track as a
 * vector. This is what backs the OpenSubsonic `sonicSimilarity` extension; without a plugin of this type installed
 * the extension is not advertised at all.
 *
 * AudioMuse has no Ampache connector; it is pointed at Ampache using its **Navidrome** connector, which is a plain
 * Subsonic API client. That means AudioMuse indexes every track under the id Ampache hands Subsonic clients — the
 * prefixed `so-<id>` form, not the bare row id — so ids are translated in both directions here.
 *
 * It scores results as a **distance** (0 = identical, larger = less alike) while OpenSubsonic wants a normalised
 * similarity, so every score is inverted and clamped on the way out.
 *
 * https://github.com/NeptuneHub/AudioMuse-AI
 */
class AmpacheAudioMuse extends AmpachePlugin implements PluginSonicAnalysisInterface
{
    #[Override]
    public string $categories = 'sonic_analysis';

    #[Override]
    public string $description = 'Sonic similarity from an AudioMuse-AI server';

    #[Override]
    public string $max_ampache = '999999';

    #[Override]
    public string $min_ampache = '800000';

    #[Override]
    public string $name = 'AudioMuse';

    public string $site_url = '';

    #[Override]
    public string $url = 'https://github.com/NeptuneHub/AudioMuse-AI';

    public string $user_agent = 'Ampache-AudioMuse-Plugin/1.0';

    #[Override]
    public string $version = '000001';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Sonic similarity from an AudioMuse-AI server');
    }

    /**
     * get_sonic_path
     *
     * @return list<array{'id': int, 'similarity': float}>
     */
    public function get_sonic_path(Song $start, Song $end, int $limit): array
    {
        foreach ($this->_itemIdForms($start->id) as $index => $start_id) {
            $end_id   = $this->_itemIdForms($end->id)[$index];
            $response = $this->_query_server(
                '/api/find_path',
                'start_song_id=' . urlencode($start_id)
                . '&end_song_id=' . urlencode($end_id)
                . '&max_steps=' . $limit
            );

            // find_path wraps its hops in `path`, each carrying the `distance` key the similarity endpoint returns.
            $matches = (is_array($response['path'] ?? null))
                ? $this->_toMatches($response['path'])
                : [];
            if ($matches !== []) {
                return $matches;
            }
        }

        return [];
    }

    /**
     * get_sonic_similar_songs
     *
     * @return list<array{'id': int, 'similarity': float}>
     */
    public function get_sonic_similar_songs(Song $song, int $limit): array
    {
        foreach ($this->_itemIdForms($song->id) as $item_id) {
            $response = $this->_query_server(
                '/api/similar_tracks',
                'item_id=' . urlencode($item_id) . '&n=' . $limit
            );

            $matches = ($response === null) ? [] : $this->_toMatches($response);
            if ($matches !== []) {
                return $matches;
            }
        }

        return [];
    }

    /**
     * install
     * This is a required plugin function
     */
    public function install(): bool
    {
        return Preference::insert('audiomuse_site_url', T_('AudioMuse-AI server URL'), '', AccessLevelEnum::MANAGER->value, 'string', 'plugins', $this->name);
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
        if (trim((string) ($data['audiomuse_site_url'] ?? '')) === '') {
            $data                       = [];
            $data['audiomuse_site_url'] = Preference::get_by_user(-1, 'audiomuse_site_url');
        }

        $site_url = trim((string) ($data['audiomuse_site_url'] ?? ''));
        if ($site_url === '') {
            debug_event(self::class, 'No AudioMuse-AI server URL, sonic analysis plugin skipped', 3);

            return false;
        }

        $this->site_url   = rtrim($site_url, '/');
        $this->user_agent = 'Ampache/' . AmpConfig::get('version') . ' (' . Stream::get_base_url() . ')';

        return true;
    }

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall(): bool
    {
        return Preference::delete('audiomuse_site_url');
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
     * _itemIdForms
     *
     * The ids AudioMuse might have indexed this song under, most likely first.
     *
     * AudioMuse keys tracks by whatever id the connector it scanned with reported. Its Ampache connector reports
     * Ampache's own row id, while its Navidrome connector talks Subsonic and so reports the prefixed `so-<id>`
     * form. Nothing in the response says which was used, so both are tried; an unknown id simply returns nothing.
     *
     * @return list<string>
     */
    private function _itemIdForms(int $song_id): array
    {
        return [(string) $song_id, OpenSubsonic_Api::getSongSubId($song_id)];
    }

    /**
     * @return array<mixed>|null
     */
    private function _query_server(string $path_str, string $query_str = ''): ?array
    {
        if ($this->site_url === '') {
            return null;
        }

        $url = ($query_str === '')
            ? $this->site_url . $path_str
            : $this->site_url . $path_str . '?' . $query_str;

        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => $this->user_agent,
        ];

        debug_event(self::class, 'Querying AudioMuse-AI: ' . $url, 5);
        $request  = Requests::get($url, $headers);
        $response = json_decode($request->body, true);

        return ($request->success && is_array($response))
            ? $response
            : null;
    }

    /**
     * _songIdFromItemId
     *
     * Resolve an AudioMuse item_id back to an Ampache song id, accepting either connector's form, or null when it
     * is neither. A bare number cannot go through getAmpacheId(): the legacy old-style id ranges read a small
     * integer as a catalog, so the native form is taken at face value instead.
     */
    private function _songIdFromItemId(string $item_id): ?int
    {
        if ($item_id === '') {
            return null;
        }

        if (ctype_digit($item_id)) {
            $song_id = (int) $item_id;

            return ($song_id > 0) ? $song_id : null;
        }

        if (OpenSubsonic_Api::getAmpacheType($item_id) !== 'song') {
            return null;
        }

        $song_id = OpenSubsonic_Api::getAmpacheId($item_id);

        return ($song_id !== null && $song_id > 0) ? $song_id : null;
    }

    /**
     * _toMatches
     *
     * Turn AudioMuse rows into the normalised shape the sonic endpoints expect. A row whose item_id is not an
     * Ampache song id is dropped rather than guessed at, which is what happens when the index was built against a
     * different server.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return list<array{'id': int, 'similarity': float}>
     */
    private function _toMatches(array $rows): array
    {
        $matches = [];
        foreach ($rows as $row) {
            $song_id = $this->_songIdFromItemId((string) ($row['item_id'] ?? ''));
            if ($song_id === null) {
                continue;
            }

            // find_path scores the whole route rather than each hop, so a row with no distance has no similarity
            // to report and takes the -1 the spec reserves for that, instead of looking like a perfect match.
            if (!array_key_exists('distance', $row)) {
                $matches[] = ['id' => $song_id, 'similarity' => -1.0];
                continue;
            }

            // AudioMuse scores distance (0 is identical), inverted here, and clamped as angular distance can exceed 1.
            $distance  = (float) $row['distance'];
            $matches[] = [
                'id' => $song_id,
                'similarity' => round(max(0.0, min(1.0, 1.0 - $distance)), 4),
            ];
        }

        return $matches;
    }
}
