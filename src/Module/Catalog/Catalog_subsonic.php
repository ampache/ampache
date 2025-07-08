<?php

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

namespace Ampache\Module\Catalog;

use Ahc\Cli\IO\Interactor;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\Ui;
use Exception;

/**
 * This class handles all actual work in regards to remote Subsonic catalogs.
 */
class Catalog_subsonic extends Catalog
{
    private string $version     = '000002';
    private string $type        = 'subsonic';
    private string $description = 'Subsonic Remote Catalog';

    private int $catalog_id;

    private ?SubsonicClient $subsonic = null;

    public string $uri = '';
    public string $username;
    public string $password;

    /**
     * get_description
     * This returns the description of this catalog
     */
    public function get_description(): string
    {
        return $this->description;
    }

    /**
     * get_version
     * This returns the current version
     */
    public function get_version(): string
    {
        return $this->version;
    }

    /**
     * get_path
     * This returns the current catalog path/uri
     */
    public function get_path(): string
    {
        return $this->uri;
    }

    /**
     * get_type
     * This returns the current catalog type
     */
    public function get_type(): string
    {
        return $this->type;
    }

    /**
     * get_create_help
     * This returns hints on catalog creation
     */
    public function get_create_help(): string
    {
        return "";
    }

    /**
     * is_installed
     * This returns true or false if remote catalog is installed
     */
    public function is_installed(): bool
    {
        $sql        = "SHOW TABLES LIKE 'catalog_subsonic'";
        $db_results = Dba::query($sql);

        return (Dba::num_rows($db_results) > 0);
    }

    /**
     * install
     * This function installs the remote catalog
     */
    public function install(): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = (AmpConfig::get('database_engine', 'InnoDB'));

        $sql = "CREATE TABLE `catalog_subsonic` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `uri` VARCHAR(255) COLLATE $collation NOT NULL, `username` VARCHAR(255) COLLATE $collation NOT NULL, `password` VARCHAR(255) COLLATE $collation NOT NULL, `catalog_id` INT(11) NOT NULL) ENGINE = $engine DEFAULT CHARSET=$charset COLLATE=$collation";
        Dba::query($sql);

        return true;
    }

    /**
     * @return array<
     *     string,
     *     array{description: string, type: string}
     * >
     */
    public function catalog_fields(): array
    {
        $fields = [];

        $fields['uri']      = ['description' => T_('URI'), 'type' => 'url'];
        $fields['username'] = ['description' => T_('Username'), 'type' => 'text'];
        $fields['password'] = ['description' => T_('Password'), 'type' => 'password'];

        return $fields;
    }

    /**
     * Constructor
     *
     * Catalog class constructor, pulls catalog information
     */
    public function __construct(?int $catalog_id = null)
    {
        if ($catalog_id) {
            $info = $this->get_info($catalog_id, static::DB_TABLENAME);
            foreach ($info as $key => $value) {
                $this->$key = $value;
            }
            $this->catalog_id = (int)$catalog_id;
        }
    }

    /**
     * create_type
     *
     * This creates a new catalog type entry for a catalog
     * It checks to make sure its parameters is not already used before creating
     * the catalog.
     * @param array{
     *     uri?: string,
     *     username?: ?string,
     *     password?: ?string,
     * } $data
     */
    public static function create_type(string $catalog_id, array $data): bool
    {
        $uri      = rtrim(trim($data['uri'] ?? ''), '/');
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (substr($uri, 0, 7) != 'http://' && substr($uri, 0, 8) != 'https://') {
            AmpError::add('general', T_('Remote Catalog type was selected, but the path is not a URL'));

            return false;
        }

        if (!strlen($username) || !strlen($password)) {
            AmpError::add('general', T_('No username or password was specified'));

            return false;
        }

        // Make sure this uri isn't already in use by an existing catalog
        $sql        = 'SELECT `id` FROM `catalog_subsonic` WHERE `uri` = ?';
        $db_results = Dba::read($sql, [$uri]);

        if (Dba::num_rows($db_results)) {
            debug_event('subsonic.catalog', 'Cannot add catalog with duplicate uri ' . $uri, 1);
            /* HINT: subsonic catalog URI */
            AmpError::add('general', sprintf(T_('This path belongs to an existing Subsonic Catalog: %s'), $uri));

            return false;
        }

        $sql = 'INSERT INTO `catalog_subsonic` (`uri`, `username`, `password`, `catalog_id`) VALUES (?, ?, ?, ?)';
        Dba::write($sql, [$uri, $username, $password, $catalog_id]);

        return true;
    }

    /**
     * add_to_catalog
     * @param null|array<string, string|bool> $options
     * @param null|Interactor $interactor
     * @return int
     */
    public function add_to_catalog(?array $options = null, ?Interactor $interactor = null): int
    {
        // Prevent the script from timing out
        set_time_limit(0);

        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            Ui::show_box_top(T_('Running Subsonic Remote Update'));
        }
        $songsadded = $this->_update_remote_catalog();
        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            Ui::show_box_bottom();
        }

        return $songsadded;
    }

    /**
     * createClient
     */
    private function _createClient(): void
    {
        $this->subsonic = (new SubsonicClient($this->username, $this->password, $this->uri));
    }

    /**
     * update_remote_catalog
     *
     * Pulls the data from a remote catalog and adds any missing songs to the database.
     */
    private function _update_remote_catalog(string $action = 'add'): int
    {
        debug_event('subsonic.catalog', 'Updating remote catalog...', 5);

        $this->_createClient();

        $date       = time();
        $songsadded = 0;
        $offset     = 0;
        // Get all albums
        while (true) {
            $albumList = $this->subsonic?->querySubsonic(
                'getAlbumList',
                [
                    'type' => 'alphabeticalByName',
                    'size' => 500,
                    'offset' => $offset
                ]
            );
            if (!is_array($albumList)) {
                break;
            }

            $offset += 500;
            if ($albumList['success']) {
                if (count($albumList['data']['albumList']) == 0) {
                    break;
                }
                foreach ($albumList['data']['albumList']['album'] as $anAlbum) {
                    $album = $this->subsonic?->querySubsonic('getMusicDirectory', ['id' => $anAlbum['id']]);
                    if (is_array($album) && $album['success']) {
                        foreach ($album['data']['directory']['child'] as $song) {
                            if (Catalog::is_audio_file($song['path'])) {
                                $db_url = $this->uri . '/rest/stream.view?id=' . $song['id'] . '&filename=' . urlencode($song['path']);

                                $existing_song = false;
                                $song_id_check = $this->check_remote_song($db_url);
                                if ($song_id_check) {
                                    $existing_song = true;
                                }
                                if (
                                    $action === 'add' &&
                                    $existing_song
                                ) {
                                    debug_event('subsonic.catalog', 'Skipping existing song ' . $song_id_check, 5);
                                    continue;
                                }

                                if (
                                    $action === 'verify' &&
                                    !$existing_song
                                ) {
                                    continue;
                                }

                                $artistInfo  = $this->subsonic?->querySubsonic('getArtistInfo', ['id' => $song['artistId']]);
                                $albumartist = $this->subsonic?->querySubsonic('getArtist', ['id' => $album['data']['directory']['parent']]);

                                $data   = [];
                                // album_artist isn't included in the song response
                                if (is_array($albumartist) && $albumartist['success']) {
                                    $data['albumartist'] = html_entity_decode($albumartist['data']['artist']['name']);
                                }
                                $data['artist'] = html_entity_decode($song['artist']);
                                $data['album']  = html_entity_decode($song['album']);
                                $data['title']  = html_entity_decode($song['title']);
                                if (
                                    is_array($artistInfo) &&
                                    isset($artistInfo['data']['artistInfo']['biography'])
                                ) {
                                    $data['comment'] = html_entity_decode($artistInfo['data']['artistInfo']['biography']);
                                }
                                $data['year']     = $song['year'];
                                $data['bitrate']  = $song['bitRate'] * 1000;
                                $data['size']     = $song['size'];
                                $data['time']     = $song['duration'];
                                $data['track']    = $song['track'];
                                $data['disk']     = $song['discNumber'];
                                $data['coverArt'] = $song['coverArt'];
                                $data['mode']     = 'vbr';
                                $data['genre']    = (!empty($song['genre']))
                                    ? explode(',', html_entity_decode($song['genre']))
                                    : [];
                                $data['file']    = $db_url;
                                $data['catalog'] = $this->catalog_id;

                                if ($action === 'add' && !$existing_song) {
                                    debug_event('subsonic.catalog', 'Adding song ' . $song['path'], 5);
                                    $song_id = Song::insert($data);
                                    if (!$song_id) {
                                        debug_event('subsonic.catalog', 'Insert failed for ' . $song['path'], 1);
                                        /* HINT: filename (file path) */
                                        AmpError::add('general', T_('Unable to insert song - %s'), $song['path']);
                                        continue;
                                    }

                                    if ($song['coverArt']) {
                                        $this->insertArt($song, $song_id);
                                    }
                                    $songsadded++;
                                } elseif ($action === 'verify' && $existing_song) {
                                    // If we already have the song, update it
                                    $song_id = Catalog::get_id_from_file($song['path'], 'song');
                                    if ($song_id) {
                                        $current_song = new Song($song_id);
                                        $current_song->fill_ext_info();

                                        $info = ($current_song->id) ? self::update_song_from_tags($data, $current_song) : [];
                                        if ($info['change']) {
                                            debug_event('subsonic.catalog', 'Updated existing song ' . $song_id, 5);
                                            $songsadded++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                break;
            }
        }

        Ui::update_text(
            T_("Updated"),
            T_('Completed updating Subsonic Catalog(s)') . " " . /* HINT: Number of songs */ sprintf(nT_(
                '%s Song added',
                '%s Songs added',
                $songsadded
            ), $songsadded)
        );

        debug_event('subsonic.catalog', 'Catalog updated.', 4);

        // Update the last update value based on the action
        if ($action === 'verify') {
            $this->update_last_update($date);
        } else {
            $this->update_last_add();
        }

        return $songsadded;
    }

    /**
     * verify_catalog_proc
     */
    public function verify_catalog_proc(?int $limit = 0, ?Interactor $interactor = null): int
    {
        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            Ui::show_box_top(T_('Running Remote Update'));
        }
        $songsupdated = $this->_update_remote_catalog('verify');
        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            Ui::show_box_bottom();
        }

        return $songsupdated;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insertArt(array $data, ?int $song_Id): bool
    {
        $this->_createClient();

        $song = new Song($song_Id);
        $art  = new Art($song->album, 'album');
        if (AmpConfig::get('album_art_max_height') && AmpConfig::get('album_art_max_width')) {
            $size = (int)max(AmpConfig::get('album_art_max_width'), AmpConfig::get('album_art_max_height'));
        } else {
            $size = 275;
        }

        $image = $this->subsonic?->querySubsonic('getCoverArt', ['id' => (string)$data['coverArt'], 'size' => $size], true);

        return (
            is_string($image) &&
            $art->insert($image) === true
        );
    }

    /**
     * clean_catalog_proc
     *
     * Removes subsonic songs that no longer exist.
     */
    public function clean_catalog_proc(?Interactor $interactor = null): int
    {
        $this->_createClient();

        $dead = 0;

        $sql        = 'SELECT `id`, `file` FROM `song` WHERE `catalog` = ?';
        $db_results = Dba::read($sql, [$this->catalog_id]);
        while ($row = Dba::fetch_assoc($db_results)) {
            debug_event('subsonic.catalog', 'Starting work on ' . $row['file'] . ' (' . $row['id'] . ')', 5);
            $remove = false;
            try {
                $songid = $this->url_to_songid($row['file']);
                $song   = $this->subsonic?->querySubsonic('getSong', ['id' => $songid]);
                if (!is_array($song) || !$song['success']) {
                    $remove = true;
                }
            } catch (Exception $error) {
                debug_event('subsonic.catalog', 'Clean error: ' . $error->getMessage(), 5);
            }

            if (!$remove) {
                debug_event('subsonic.catalog', 'keeping song', 5);
            } else {
                debug_event('subsonic.catalog', 'removing song', 5);
                $dead++;
                Dba::write('DELETE FROM `song` WHERE `id` = ?', [$row['id']]);
            }
        }

        return $dead;
    }

    /**
     * @return string[]
     */
    public function check_catalog_proc(?Interactor $interactor = null): array
    {
        return [];
    }

    /**
     * move_catalog_proc
     * This function updates the file path of the catalog to a new location (unsupported)
     */
    public function move_catalog_proc(string $new_path): bool
    {
        return false;
    }

    /**
     * cache_catalog_proc
     */
    public function cache_catalog_proc(): bool
    {
        $remote       = AmpConfig::get('cache_remote');
        $cache_path   = (string)AmpConfig::get('cache_path', '');
        $cache_target = (string)AmpConfig::get('cache_target', '');
        // need a destination, source and target format
        if (!$remote || !is_dir($cache_path) || !$cache_target) {
            debug_event('local.catalog', 'Check your cache_path cache_target and cache_remote settings', 5);

            return false;
        }

        $max_bitrate   = (int)AmpConfig::get('max_bit_rate', 128);
        $user_bit_rate = (int)AmpConfig::get('transcode_bitrate', 128);

        // If the user's crazy, that's no skin off our back
        if ($user_bit_rate > $max_bitrate) {
            $max_bitrate = $user_bit_rate;
        }
        $options = [
            'format' => $cache_target,
            'maxBitRate' => $max_bitrate,
        ];

        $this->_createClient();

        $sql          = "SELECT `id`, `file` FROM `song` WHERE `catalog` = ?;";
        $db_results   = Dba::read($sql, [$this->catalog_id]);
        while ($row = Dba::fetch_assoc($db_results)) {
            $file_target = ($row['id'] && $cache_target === $row['extension'])
                ? Catalog::get_cache_path($row['id'], $this->catalog_id, $cache_path, $cache_target)
                : null;
            if (empty($file_target)) {
                debug_event('subsonic.catalog', 'Cache error: no target for ' . $row['id'], 5);
                continue;
            }

            $file_exists = is_file($file_target);
            if (!$file_exists || (int)Core::get_filesize($file_target) == 0) {
                $old_target_file = rtrim(trim($cache_path), '/') . '/' . $this->catalog_id . '/' . $row['id'] . '.' . $cache_target;
                $old_file_exists = is_file($old_target_file);
                if ($old_file_exists) {
                    // check for the old path first
                    rename($old_target_file, $file_target);
                    debug_event('subsonic.catalog', 'Moved: ' . $row['id'] . ' from: {' . $old_target_file . '}' . ' to: {' . $file_target . '}', 5);
                } else {
                    try {
                        $filehandle = fopen($file_target, 'w');
                        if (!is_resource($filehandle)) {
                            debug_event('subsonic.catalog', 'Could not open file: ' . $file_target, 5);
                            continue;
                        }

                        $remote_url = $this->subsonic?->parameterize($row['file'] . '&', $options);

                        $curl = curl_init();
                        curl_setopt_array(
                            $curl,
                            [
                                CURLOPT_RETURNTRANSFER => 1,
                                CURLOPT_FILE => $filehandle,
                                CURLOPT_TIMEOUT => 0,
                                CURLOPT_PIPEWAIT => 1,
                                CURLOPT_URL => $remote_url,
                            ]
                        );
                        curl_exec($curl);
                        curl_close($curl);
                        fclose($filehandle);
                        debug_event('subsonic.catalog', 'Saved: ' . $row['id'] . ' to: {' . $file_target . '}', 5);
                    } catch (Exception $error) {
                        debug_event('subsonic.catalog', 'Cache error: ' . $row['id'] . ' ' . $error->getMessage(), 5);
                    }
                }
            }
        }

        return true;
    }

    /**
     * check_remote_song
     *
     * checks to see if a remote song exists in the database or not
     * if it find a song it returns the UID
     */
    public function check_remote_song(string $url): ?int
    {
        $sql        = 'SELECT `id` FROM `song` WHERE `file` = ?';
        $db_results = Dba::read($sql, [$url]);

        if ($results = Dba::fetch_assoc($db_results)) {
            return (int)$results['id'];
        }

        return null;
    }

    /**
     * get_rel_path
     */
    public function get_rel_path(string $file_path): string
    {
        $catalog_path = rtrim($this->uri, "/");

        return (str_replace($catalog_path . "/", "", $file_path));
    }

    public function url_to_songid(string $url): int
    {
        $song_id = 0;
        preg_match('/\?id=([0-9]*)&/', $url, $matches);
        if (count($matches)) {
            $song_id = $matches[1];
        }

        return (int)$song_id;
    }

    /**
     * get_f_info
     */
    public function get_f_info(): string
    {
        return $this->uri;
    }

    /**
     * @param Podcast_Episode|Song|Video $media
     * @return null|array{
     *     file_path: string,
     *     file_name: string,
     *     file_size: int,
     *     file_type: string
     * }
     */
    public function prepare_media(Podcast_Episode|Video|Song $media): ?array
    {
        return null;
    }

    /**
     * Returns the remote streaming-url if supported
     */
    public function getRemoteStreamingUrl(Podcast_Episode|Video|Song $media): ?string
    {
        $this->_createClient();

        return $this->subsonic?->parameterize($media->file . '&');
    }
}
