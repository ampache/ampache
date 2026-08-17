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

use Ampache\Module\System\Core;
use Ampache\Repository\Model\User;
use Override;
use Throwable;
use WpOrg\Requests\Requests;

class AmpacheItunes extends AmpachePlugin implements PluginSongPreviewInterface
{
    private const int CONNECT_TIMEOUT = 3;

    private const int REQUEST_TIMEOUT = 7;

    #[Override]
    public string $categories = 'preview';

    #[Override]
    public string $description = 'Song preview from the iTunes Search API';

    #[Override]
    public string $max_ampache = '999999';

    #[Override]
    public string $min_ampache = '800000';

    #[Override]
    public string $name = 'iTunes';

    #[Override]
    public string $url = 'https://www.apple.com/itunes/';

    #[Override]
    public string $version = '000001';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Song preview from the iTunes Search API');
    }

    /**
     * get_song_preview
     *
     * Neither provider indexes MusicBrainz ids, so the recording is matched on artist and title and the
     * mbid is only used for logging; a wrong match is possible and the caller takes the first result.
     *
     * @return list<SongPreviewResult>
     */
    public function get_song_preview(string $track_mbid, string $artist_name, string $title): array
    {
        if (trim($artist_name) === '' || trim($title) === '') {
            return [];
        }

        $response = $this->_query_server('https://itunes.apple.com/search?media=music&entity=song&limit=5&term=' . rawurlencode($artist_name . ' ' . $title));
        if ($response === null) {
            return [];
        }

        $results = [];
        foreach ($response->results ?? [] as $row) {
            $file = (string) ($row->previewUrl ?? '');
            if ($file === '') {
                continue;
            }

            $results[] = new SongPreviewResult($file, (string) ($row->trackName ?? $title), (string) ($row->artistName ?? $artist_name));
        }

        return SongPreviewResult::rank($results, $artist_name, $title);
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        return true;
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     */
    public function load(User $user): bool
    {
        return true;
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return true;
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
     * Ask the provider for a track and hand back the decoded body, or null when anything goes wrong
     */
    private function _query_server(string $url): ?object
    {
        try {
            $request = Requests::get(
                $url,
                [],
                array_merge(
                    Core::requests_options(),
                    ['timeout' => self::REQUEST_TIMEOUT, 'connect_timeout' => self::CONNECT_TIMEOUT]
                )
            );

            if ($request->status_code !== 200) {
                debug_event(self::class, 'query failed with status ' . $request->status_code, 3);

                return null;
            }

            $body = json_decode($request->body);

            return is_object($body) ? $body : null;
        } catch (Throwable $throwable) {
            debug_event(self::class, 'query failed: ' . $throwable->getMessage(), 3);

            return null;
        }
    }
}
