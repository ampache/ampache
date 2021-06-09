<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 */

declare(strict_types=0);

namespace Ampache\Module\Catalog\PlaylistImport;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Stream\Url\StreamUrlParserInterface;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\PlaylistRepositoryInterface;
use Psr\Log\LoggerInterface;

final class PlaylistImporter implements PlaylistImporterInterface
{
    private StreamUrlParserInterface $streamUrlParser;

    private PlaylistRepositoryInterface $playlistRepository;

    private LoggerInterface $logger;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        StreamUrlParserInterface $streamUrlParser,
        PlaylistRepositoryInterface $playlistRepository,
        LoggerInterface $logger,
        ConfigContainerInterface $configContainer
    ) {
        $this->streamUrlParser    = $streamUrlParser;
        $this->playlistRepository = $playlistRepository;
        $this->logger             = $logger;
        $this->configContainer    = $configContainer;
    }

    /**
     * Attempts to create a Public Playlist based on the playlist file
     *
     * @return array{success: bool, id?: int, count?: int, error?: string}
     */
    public function import(string $playlist): array
    {
        $data = (string) file_get_contents($playlist);
        if (substr($playlist, -3, 3) == 'm3u' || substr($playlist, -4, 4) == 'm3u8') {
            $files = self::parse_m3u($data);
        } elseif (substr($playlist, -3, 3) == 'pls') {
            $files = self::parse_pls($data);
        } elseif (substr($playlist, -3, 3) == 'asx') {
            $files = self::parse_asx($data);
        } elseif (substr($playlist, -4, 4) == 'xspf') {
            $files = self::parse_xspf($data);
        }

        $webPath = $this->configContainer->getWebPath();

        $songs = array();
        $pinfo = pathinfo($playlist);
        if (isset($files)) {
            foreach ($files as $file) {
                $file = trim((string)$file);
                // Check to see if it's a url from this ampache instance
                if (substr($file, 0, strlen($webPath)) == $webPath) {
                    $data       = $this->streamUrlParser->parse($file);
                    $sql        = 'SELECT COUNT(*) FROM `song` WHERE `id` = ?';
                    $db_results = Dba::read($sql, array($data['id']));
                    if (Dba::num_rows($db_results)) {
                        $songs[] = $data['id'];
                    }
                } // end if it's an http url
                else {
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

                    $this->logger->debug(
                        sprintf(
                            'Add file %s to playlist.',
                            $file
                        ),
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );

                    // First, try to found the file as absolute path
                    $sql        = "SELECT `id` FROM `song` WHERE `file` = ?";
                    $db_results = Dba::read($sql, array($file));
                    $results    = Dba::fetch_assoc($db_results);

                    if (isset($results['id'])) {
                        $songs[] = $results['id'];
                    } else {
                        // Not found in absolute path, create it from relative path
                        $file = $pinfo['dirname'] . DIRECTORY_SEPARATOR . $file;
                        // Normalize the file path. realpath requires the files to exists.
                        $file = realpath($file);
                        if ($file) {
                            $sql        = "SELECT `id` FROM `song` WHERE `file` = ?";
                            $db_results = Dba::read($sql, array($file));
                            $results    = Dba::fetch_assoc($db_results);

                            if (isset($results['id'])) {
                                $songs[] = $results['id'];
                            }
                        }
                    }
                } // if it's a file
            }
        }

        $this->logger->debug(
            sprintf(
                'import_playlist Parsed %s, found %d songs',
                $playlist,
                count($songs)
            ),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        if (count($songs)) {
            $name = ($pinfo['extension'] ?? '') . " - " . $pinfo['filename'];
            // Search for existing playlist
            $playlist_search = $this->playlistRepository->getPlaylists(
                null,
                $name
            );
            if (empty($playlist_search)) {
                // New playlist
                $playlist_id = $this->playlistRepository->create(
                    $name,
                    'public',
                    Core::get_global('user')->getId()
                );
                $current_songs = array();
                $playlist      = ((int)$playlist_id > 0) ? new Playlist((int)$playlist_id) : null;
            } else {
                // Existing playlist
                $playlist_id   = $playlist_search[0];
                $playlist      = new Playlist($playlist_id);
                $current_songs = $playlist->get_songs();

                $this->logger->debug(
                    sprintf(
                        'import_playlist playlist has %s songs',
                        count($current_songs)
                    ),
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
            }

            if (!$playlist_id) {
                return [
                    'success' => false,
                    'error' => T_('Failed to create playlist'),
                ];
            }

            /* Recreate the Playlist; checking for current items. */
            $new_songs = $songs;
            if (count($current_songs)) {
                $new_songs = array_diff($songs, $current_songs);

                $this->logger->debug(
                    sprintf(
                        'import_playlist filtered existing playlist, found %d new songs',
                        count($new_songs)
                    ),
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
            }

            /** @var Playlist $playlist */
            $playlist->add_songs(
                $new_songs,
                $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::UNIQUE_PLAYLIST)
            );

            return [
                'success' => true,
                'id' => $playlist_id,
                'count' => count($new_songs)
            ];
        }

        return [
            'success' => false,
            'error' => T_('No valid songs found in playlist file')
        ];
    }

    /**
     * this takes m3u filename and then attempts to found song filenames listed in the m3u
     *
     * @return array<string>
     */
    private function parse_m3u(string $data): array
    {
        $files   = array();
        $results = explode("\n", $data);

        foreach ($results as $value) {
            $value = trim((string)$value);
            if (!empty($value) && substr($value, 0, 1) != '#') {
                $files[] = $value;
            }
        }

        return $files;
    }

    /**
     * this takes pls filename and then attempts to found song filenames listed in the pls
     *
     * @return array<string>
     */
    private function parse_pls(string $data): array
    {
        $files   = array();
        $results = explode("\n", $data);

        foreach ($results as $value) {
            $value = trim((string)$value);
            if (preg_match("/file[0-9]+[\s]*\=(.*)/i", $value, $matches)) {
                $file = trim((string) $matches[1]);
                if (!empty($file)) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }

    /**
     * this takes asx filename and then attempts to found song filenames listed in the asx
     *
     * @return array<string>
     */
    private function parse_asx(string $data): array
    {
        $files = [];
        $xml   = simplexml_load_string($data);

        if ($xml) {
            foreach ($xml->entry as $entry) {
                $file = trim((string) $entry->ref['href']);
                if (!empty($file)) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }

    /**
     * this takes xspf filename and then attempts to found song filenames listed in the xspf
     *
     * @return array<string>
     */
    private function parse_xspf(string $data): array
    {
        $files = array();
        $xml   = simplexml_load_string($data);
        if ($xml) {
            foreach ($xml->trackList->track as $track) {
                $file = trim((string)$track->location);
                if (!empty($file)) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }
}
