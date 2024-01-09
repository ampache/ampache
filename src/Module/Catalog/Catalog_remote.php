<?php

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
use Ampache\Module\Api\Api;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\Ui;
use AmpacheApi;
use Exception;

/**
 * This class handles all actual work in regards to remote catalogs.
 */
class Catalog_remote extends Catalog
{
    private string $version     = '000001';
    private string $type        = 'remote';
    private string $description = 'Ampache Remote Catalog';

    private int $catalog_id;

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
        $sql        = "SHOW TABLES LIKE 'catalog_remote'";
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
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE `catalog_remote` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `uri` VARCHAR(255) COLLATE $collation NOT NULL, `username` VARCHAR(255) COLLATE $collation NOT NULL, `password` VARCHAR(255) COLLATE $collation NOT NULL, `catalog_id` INT(11) NOT NULL) ENGINE = $engine DEFAULT CHARSET=$charset COLLATE=$collation";
        Dba::query($sql);

        return true;
    }

    /**
     * @return array
     */
    public function catalog_fields()
    {
        $fields = array();

        $fields['uri']      = array('description' => T_('URI'), 'type' => 'url');
        $fields['username'] = array('description' => T_('Username'), 'type' => 'text');
        $fields['password'] = array('description' => T_('Password'), 'type' => 'password');

        return $fields;
    }

    /**
     * Constructor
     *
     * Catalog class constructor, pulls catalog information
     * @param int $catalog_id
     */
    public function __construct($catalog_id = null)
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
     * @param string $catalog_id
     * @param array $data
     */
    public static function create_type($catalog_id, $data): bool
    {
        $uri      = $data['uri'];
        $username = $data['username'];
        $password = $data['password'];

        if (substr($uri, 0, 7) != 'http://' && substr($uri, 0, 8) != 'https://') {
            AmpError::add('general', T_('Remote Catalog type was selected, but the path is not a URL'));

            return false;
        }

        if (!strlen($username) || !strlen($password)) {
            AmpError::add('general', T_('No username or password was specified'));

            return false;
        }
        $password = hash('sha256', $password);

        // Make sure this uri isn't already in use by an existing catalog
        $sql        = 'SELECT `id` FROM `catalog_remote` WHERE `uri` = ?';
        $db_results = Dba::read($sql, array($uri));

        if (Dba::num_rows($db_results)) {
            debug_event('remote.catalog', 'Cannot add catalog with duplicate uri ' . $uri, 1);
            /* HINT: remote URI */
            AmpError::add('general', sprintf(T_('This path belongs to an existing remote Catalog: %s'), $uri));

            return false;
        }

        $sql = 'INSERT INTO `catalog_remote` (`uri`, `username`, `password`, `catalog_id`) VALUES (?, ?, ?, ?)';
        Dba::write($sql, array($uri, $username, $password, $catalog_id));

        return true;
    }

    /**
     * add_to_catalog
     * this function adds new files to an
     * existing catalog
     * @param array $options
     * @return int
     * @throws Exception
     */
    public function add_to_catalog($options = null): int
    {
        if (!defined('SSE_OUTPUT') && !defined('API')) {
            Ui::show_box_top(T_('Running Remote Update'));
        }
        $songsadded = $this->update_remote_catalog();
        if (!defined('SSE_OUTPUT') && !defined('API')) {
            Ui::show_box_bottom();
        }

        return $songsadded;
    }

    /**
     * connect
     *
     * Connects to the remote catalog that we are.
     */
    public function connect()
    {
        try {
            $remote_handle = new AmpacheApi\AmpacheApi(
                array(
                    'username' => $this->username,
                    'password' => $this->password,
                    'server' => $this->uri,
                    'debug_callback' => 'debug_event',
                    'api_secure' => (substr($this->uri, 0, 8) == 'https://'),
                    'api_format' => 'xml',
                    'server_version' => Api::DEFAULT_VERSION
                )
            );
        } catch (Exception $error) {
            debug_event('remote.catalog', 'Connection error: ' . $error->getMessage(), 1);
            if (defined('SSE_OUTPUT') || defined('API')) {
                AmpError::add('general', $error->getMessage());
                echo AmpError::display('general');
                flush();
            }

            return false;
        }

        if ($remote_handle->state() != 'CONNECTED') {
            debug_event('remote.catalog', 'API client failed to connect', 1);
            if (defined('SSE_OUTPUT') || defined('API')) {
                AmpError::add('general', T_('Failed to connect to the remote server'));
                echo AmpError::display('general');
            }

            return false;
        }

        return $remote_handle;
    }

    /**
     * update_remote_catalog
     *
     * Pulls the data from a remote catalog and adds any missing songs to the database.
     * @return int
     * @throws Exception
     */
    public function update_remote_catalog(): int
    {
        set_time_limit(0);

        $remote_handle = $this->connect();
        if (!$remote_handle) {
            debug_event('remote.catalog', 'connection error', 1);

            return 0;
        }

        // Get the song count, etc.
        $remote_catalog_info = $remote_handle->info();

        Ui::update_text(
            T_("Remote Catalog Updated"),
            /* HINT: count of songs found*/
            sprintf(nT_('%s song was found', '%s songs were found', $remote_catalog_info->songs), $remote_catalog_info->songs)
        );

        $date = time();
        // Hardcoded for now
        $step       = 500;
        $current    = 0;
        $total      = $remote_catalog_info->songs;
        $songsadded = 0;

        while ($total > $current) {
            $start = $current;
            $current += $step;
            try {
                $songs = $remote_handle->send_command('songs', array('offset' => $start, 'limit' => $step));
                // Iterate over the songs we retrieved and insert them
                foreach ($songs as $song) {
                    if (!$song->url) {
                        continue;
                    }
                    if ($this->check_remote_song($song->url)) {
                        debug_event('remote.catalog', 'Skipping existing song ' . $song->url, 5);
                    } else {
                        $genres = array();
                        foreach ($song->genre as $genre) {
                            $genres[] = $genre->name;
                        }
                        $data = array(
                            'albumartist' => $song->albumartist->name,
                            'album' => $song->album->name,
                            'artist' => $song->artist->name,
                            'artists' => null,
                            'bitrate' => $song->bitrate ?? null,
                            'catalog' => $this->catalog_id,
                            'channels' => $song->channels ?? null,
                            'composer' => $song->composer ?? null,
                            'comment' => null,
                            'disk' => $song->disk ?? null,
                            'file' => preg_replace('/ssid=.*?&/', '', $song->url),
                            'genre' => $song->genre,
                            'mb_trackid' => $song->mbid ?? null,
                            'mime' => $song->mime ?? null,
                            'mode' => $song->mode ?? null,
                            'publisher' => $song->publisher ?? null,
                            'r128_album_gain' => null,
                            'r128_track_gain' => null,
                            'rate' => $song->bitrate ?? null,
                            'replaygain_album_gain' => null,
                            'replaygain_album_peak' => null,
                            'replaygain_track_gain' => null,
                            'replaygain_track_peak' => null,
                            'size' => $song->size ?? null,
                            'time' => $song->time ?? null,
                            'title' => $song->title ?? null,
                            'track' => $song->track ?? null,
                            'year' => $song->year ?? null
                        );
                        //debug_event('remote.catalog', 'DATA ' . print_r($data, true), 1);
                        if (!Song::insert($data)) {
                            debug_event('remote.catalog', 'Insert failed for ' . $song->url, 1);
                            /* HINT: Song Title */
                            AmpError::add('general', T_('Unable to insert song - %s'), $song->title);
                            echo AmpError::display('general');
                            flush();
                        } else {
                            $songsadded++;
                        }
                    }
                }
            } catch (Exception $error) {
                debug_event('remote.catalog', 'Songs parsing error: ' . $error->getMessage(), 1);
                AmpError::add('general', $error->getMessage());
                echo AmpError::display('general');
                flush();
            }
        } // end while

        Ui::update_text(T_("Updated"), T_("Completed updating remote Catalog(s)."));

        // Update the last update value
        $this->update_last_update($date);

        return $songsadded;
    }

    /**
     * verify_catalog_proc
     */
    public function verify_catalog_proc(): int
    {
        return 0;
    }

    /**
     * clean_catalog_proc
     *
     * Removes remote songs that no longer exist.
     */
    public function clean_catalog_proc(): int
    {
        $remote_handle = $this->connect();
        if (!$remote_handle) {
            debug_event('remote.catalog', 'Remote login failed', 1);

            return 0;
        }

        $dead       = 0;
        $sql        = 'SELECT `id`, `file` FROM `song` WHERE `catalog` = ?';
        $db_results = Dba::read($sql, array($this->catalog_id));
        while ($row = Dba::fetch_assoc($db_results)) {
            debug_event('remote.catalog', 'Starting work on ' . $row['file'] . ' (' . $row['id'] . ')', 5);
            try {
                $song = $remote_handle->send_command('url_to_song', array('url' => $row['file']));
                if (count($song) == 1) {
                    debug_event('remote.catalog', 'keeping song', 5);
                } else {
                    debug_event('remote.catalog', 'removing song', 5);
                    $dead++;
                    Dba::write('DELETE FROM `song` WHERE `id` = ?', array($row['id']));
                }
            } catch (Exception $error) {
                // FIXME: What to do, what to do
                debug_event('remote.catalog', 'url_to_song parsing error: ' . $error->getMessage(), 1);
            }
        }

        return $dead;
    }

    /**
     * @return array
     */
    public function check_catalog_proc()
    {
        return array();
    }

    /**
     * move_catalog_proc
     * This function updates the file path of the catalog to a new location (unsupported)
     * @param string $new_path
     */
    public function move_catalog_proc($new_path): bool
    {
        return false;
    }

    /**
     * cache_catalog_proc
     */
    public function cache_catalog_proc(): bool
    {
        $remote_handle = $this->connect();

        // If we don't get anything back we failed and should bail now
        if (!$remote_handle) {
            debug_event('remote.catalog', 'Connection to remote server failed', 1);

            return false;
        }

        $remote = AmpConfig::get('cache_remote');
        $path   = (string)AmpConfig::get('cache_path', '');
        $target = (string)AmpConfig::get('cache_target', '');
        // need a destination, source and target format
        if (!is_dir($path) || !$remote || !$target) {
            debug_event('remote.catalog', 'Check your cache_path cache_target and cache_remote settings', 5);

            return false;
        }
        $max_bitrate   = (int)AmpConfig::get('max_bit_rate', 128);
        $user_bit_rate = (int)AmpConfig::get('transcode_bitrate', 128);

        // If the user's crazy, that's no skin off our back
        if ($user_bit_rate > $max_bitrate) {
            $max_bitrate = $user_bit_rate;
        }
        $handshake  = $remote_handle->info();
        $sql        = "SELECT `id`, `file`, substring_index(file,'.',-1) AS `extension` FROM `song` WHERE `catalog` = ?;";
        $db_results = Dba::read($sql, array($this->catalog_id));
        while ($row = Dba::fetch_assoc($db_results)) {
            $target_file = rtrim(trim($path), '/') . '/' . $this->catalog_id . '/' . $row['id'] . '.' . $row['extension'];
            $remote_url  = $row['file'] . '&ssid=' . $handshake->auth . '&format=' . $target . '&bitrate=' . $max_bitrate;
            if (!is_file($target_file) || (int)Core::get_filesize($target_file) == 0) {
                debug_event('remote.catalog', 'Saving ' . $row['id'] . ' to (' . $target_file . ')', 5);
                try {
                    $filehandle = fopen($target_file, 'w');
                    $curl       = curl_init();
                    curl_setopt_array(
                        $curl,
                        array(
                            CURLOPT_RETURNTRANSFER => 1,
                            CURLOPT_FILE => $filehandle,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_PIPEWAIT => 1,
                            CURLOPT_URL => $remote_url,
                        )
                    );
                    curl_exec($curl);
                    curl_close($curl);
                    fclose($filehandle);
                    debug_event('remote.catalog', 'Saved: ' . $row['id'] . ' to: {' . $target_file . '}', 5);
                } catch (Exception $error) {
                    debug_event('remote.catalog', 'Cache error: ' . $row['id'] . ' ' . $error->getMessage(), 5);
                }
                // keep alive just in case
                $remote_handle->send_command('ping');
            }
        }

        return true;
    }

    /**
     * check_remote_song
     *
     * checks to see if a remote song exists in the database or not
     * if it find a song it returns the UID
     * @param string $song_url
     * @return int|bool
     */
    public function check_remote_song($song_url)
    {
        $url = preg_replace('/ssid=.*&/', '', $song_url);
        if (!$url) {
            return false;
        }
        $sql        = 'SELECT `id` FROM `song` WHERE `file` = ?';
        $db_results = Dba::read($sql, array($url));

        if ($results = Dba::fetch_assoc($db_results)) {
            return (int)$results['id'];
        }

        return false;
    }

    /**
     * @param string $file_path
     */
    public function get_rel_path($file_path): string
    {
        $catalog_path = rtrim($this->uri, "/");

        return (str_replace($catalog_path . "/", "", $file_path));
    }

    /**
     * format
     *
     * This makes the object human-readable.
     */
    public function format(): void
    {
        parent::format();
        $this->f_info      = $this->uri;
        $this->f_full_info = $this->uri;
    }

    /**
     * @param Song|Podcast_Episode|Video $media
     * @return null|array{
     *   file_path: string,
     *   file_name: string,
     *   file_size: int,
     *   file_type: string
     *  }
     */
    public function prepare_media($media): ?array
    {
        return null;
    }

    /**
     * Returns the remote streaming-url if supported
     *
     * @param Song|Podcast_Episode|Video $media
     */
    public function getRemoteStreamingUrl($media): ?string
    {
        $remote_handle = $this->connect();

        // If we don't get anything back we failed and should bail now
        if (!$remote_handle) {
            debug_event('remote.catalog', 'Connection to remote server failed', 1);

            return null;
        }

        $handshake = $remote_handle->info();

        return $media->file . '&ssid=' . $handshake->auth;
    }
}
