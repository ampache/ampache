<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Catalog;

use Ampache\Config\AmpConfig;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Playlist;
use Generator;

final class PlaylistImporter
{
    /**
     * Attempts to create a Public Playlist based on the playlist file
     *
     * @return null|array{
     *  count: int,
     *  id: int,
     *  results: list<array{
     *    track: int,
     *    file: string,
     *    found: int
     *  }>
     * }
     */
    public static function import_playlist(string $playlist_file, int $user_id, string $playlist_type): ?array
    {
        $data = (string) file_get_contents($playlist_file);
        if (substr($playlist_file, -3, 3) === 'm3u' || substr($playlist_file, -4, 4) === 'm3u8') {
            $files = self::parse_m3u($data);
        } elseif (substr($playlist_file, -3, 3) === 'pls') {
            $files = self::parse_pls($data);
        } elseif (substr($playlist_file, -3, 3) === 'asx') {
            $files = self::parse_asx($data);
        } elseif (substr($playlist_file, -4, 4) === 'xspf') {
            $files = self::parse_xspf($data);
        }

        $songs    = array();
        $import   = array();
        $pinfo    = pathinfo($playlist_file);
        $track    = 1;
        $web_path = AmpConfig::get('web_path');
        if (isset($files)) {
            foreach ($files as $file) {
                $found    = false;
                $file     = trim((string)$file);
                $orig     = $file;
                $url_data = Stream_Url::parse($file);
                // Check to see if it's a url from this ampache instance
                if (array_key_exists('id', $url_data) && !empty($web_path) && substr($file, 0, strlen($web_path)) == $web_path) {
                    $sql        = 'SELECT COUNT(*) FROM `song` WHERE `id` = ?';
                    $db_results = Dba::read($sql, array($url_data['id']));
                    if (Dba::num_rows($db_results) && (int)$url_data['id'] > 0) {
                        debug_event(__CLASS__, "import_playlist identified: {" . $url_data['id'] . "}", 5);
                        $songs[$track] = $url_data['id'];
                        $track++;
                        $found = true;
                    }
                } else {
                    // Remove file:// prefix if any
                    if (strpos($file, "file://") !== false) {
                        $file = urldecode(substr($file, 7));
                        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                            // Removing starting / on Windows OS.
                            if (substr($file, 0, 1) == '/') {
                                $file = substr($file, 1);
                            }
                            // Restore real directory separator
                            $file = str_replace("/", DIRECTORY_SEPARATOR, $file);
                        }
                    }

                    // First, try to find the file as absolute path
                    $sql        = "SELECT `id` FROM `song` WHERE `file` = ?";
                    $db_results = Dba::read($sql, array($file));
                    $results    = Dba::fetch_assoc($db_results);

                    if (array_key_exists('id', $results) && (int)($results['id'] ?? 0) > 0) {
                        debug_event(__CLASS__, "import_playlist identified: {" . (int)$results['id'] . "}", 5);
                        $songs[$track] = (int)$results['id'];
                        $track++;
                        $found = true;
                    } else {
                        // Not found in absolute path, create it from relative path
                        $file = $pinfo['dirname'] . DIRECTORY_SEPARATOR . $file;
                        // Normalize the file path. realpath requires the files to exists.
                        $file = realpath($file);
                        if ($file) {
                            $db_results = Dba::read($sql, array($file));
                            $results    = Dba::fetch_assoc($db_results);

                            if (array_key_exists('id', $results) && (int)($results['id'] ?? 0) > 0) {
                                debug_event(__CLASS__, "import_playlist identified: {" . (int)$results['id'] . "}", 5);
                                $songs[$track] = (int)$results['id'];
                                $track++;
                                $found = true;
                            }
                        }
                    }
                } // if it's a file
                if (!$found) {
                    debug_event(__CLASS__, "import_playlist skipped: {{$orig}}", 5);
                }
                // add the results to an array to display after
                $import[] = array(
                    'track' => $track - 1,
                    'file' => $orig,
                    'found' => (int)$found
                );
            }
        }

        debug_event(__CLASS__, "import_playlist Parsed " . $playlist_file . ", found " . count($songs) . " songs", 5);

        if (count($songs)) {
            $name        = $pinfo['filename'];
            $playlist_id = (int)Playlist::create($name, $playlist_type, $user_id);

            if ($playlist_id < 1) {
                return null;
            }

            $playlist = new Playlist($playlist_id);
            $playlist->delete_all();
            $playlist->add_songs($songs);

            return array(
                'id' => $playlist_id,
                'count' => count($songs),
                'results' => $import
            );
        }

        return null;
    }

    /**
     * this takes m3u filename and then attempts to found song filenames listed in the m3u
     *
     * @return Generator<string>
     */
    private static function parse_m3u(string $data): Generator
    {
        $results = explode("\n", $data);

        foreach ($results as $value) {
            $value = trim($value);
            if (!empty($value) && substr($value, 0, 1) != '#') {
                yield $value;
            }
        }
    }

    /**
     * this takes pls filename and then attempts to found song filenames listed in the pls
     *
     * @return Generator<string>
     */
    private static function parse_pls(string $data): Generator
    {
        $results = explode("\n", $data);

        foreach ($results as $value) {
            $value = trim($value);
            if (preg_match("/file[0-9]+[\s]*\=(.*)/i", $value, $matches)) {
                $file = trim($matches[1]);
                if (!empty($file)) {
                    yield $file;
                }
            }
        }
    }

    /**
     * this takes asx filename and then attempts to found song filenames listed in the asx
     *
     * @return Generator<string>
     */
    private static function parse_asx(string $data): Generator
    {
        $xml   = simplexml_load_string($data);

        if ($xml) {
            foreach ($xml->entry as $entry) {
                $file = trim((string)$entry->ref['href']);
                if (!empty($file)) {
                    yield $file;
                }
            }
        }
    }

    /**
     * parse_xspf
     * this takes xspf filename and then attempts to found song filenames listed in the xspf
     *
     * @return Generator<string>
     */
    private static function parse_xspf(string $data): Generator
    {
        $xml   = simplexml_load_string($data);
        if ($xml) {
            foreach ($xml->trackList->track as $track) {
                $file = trim((string)$track->location);
                if (!empty($file)) {
                    yield $file;
                }
            }
        }
    }
}
