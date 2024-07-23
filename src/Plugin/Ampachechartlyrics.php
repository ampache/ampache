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

use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\System\Core;
use WpOrg\Requests\Requests;

class Ampachechartlyrics implements AmpachePluginInterface
{
    public string $name        = 'ChartLyrics';
    public string $categories  = 'lyrics';
    public string $description = 'Get lyrics from ChartLyrics';
    public string $url         = 'http://www.chartlyrics.com';
    public string $version     = '000001';
    public string $min_ampache = '360022';
    public string $max_ampache = '999999';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Get lyrics from ChartLyrics');
    }

    /**
     * install
     * This is a required plugin function
     */
    public function install(): bool
    {
        return true;
    }

    /**
     * uninstall
     * This is a required plugin function
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
     * load
     * This is a required plugin function; here it populates the prefs we
     * need for this object.
     * @param User $user
     */
    public function load($user): bool
    {
        $user->set_preferences();

        return true;
    }

    /**
     * get_lyrics
     * This will look web services for a song lyrics.
     * @param Song $song
     * @return array|false
     */
    public function get_lyrics($song)
    {
        $base    = 'http://api.chartlyrics.com/apiv1.asmx/';
        $uri     = $base . 'SearchLyricDirect?artist=' . urlencode((string)$song->get_artist_fullname()) . '&song=' . urlencode((string)$song->title);
        $request = Requests::get($uri, [], Core::requests_options());
        if ($request->status_code == 200) {
            $xml = simplexml_load_string($request->body);
            if ($xml) {
                if (!empty($xml->Lyric)) {
                    return [
                        'text' => nl2br($xml->Lyric),
                        'url' => $xml->LyricUrl
                    ];
                }
            }
        }

        return false;
    }
}
