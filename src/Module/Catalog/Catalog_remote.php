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
use Ampache\Module\Api\Api;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\VaInfo;
use AmpacheApi\AmpacheApi;
use Exception;
use SimpleXMLElement;

/**
 * This class handles all actual work in regards to remote catalogs.
 */
class Catalog_remote extends Catalog
{
    private const CMD_ALBUM = 'album';

    private const CMD_ARTIST = 'artist';

    private const CMD_ARTISTS = 'artists';

    private const CMD_PING = 'ping';

    private const CMD_SONGS = 'songs';

    private const CMD_SONG = 'song';

    private const CMD_SONG_TAGS = 'song_tags';

    private const CMD_URL_TO_SONG = 'url_to_song';

    private string $version     = '000001';
    private string $type        = 'remote';
    private string $description = 'Ampache Remote Catalog';

    private int $catalog_id;

    // new servers support pulling tags from VaInfo
    private bool $song_tags = true;

    private ?AmpacheApi $remote_handle = null;

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
        $engine    = (AmpConfig::get('database_engine', 'InnoDB'));

        $sql = "CREATE TABLE `catalog_remote` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `uri` VARCHAR(255) COLLATE $collation NOT NULL, `username` VARCHAR(255) COLLATE $collation NOT NULL, `password` VARCHAR(255) COLLATE $collation NOT NULL, `catalog_id` INT(11) NOT NULL) ENGINE = $engine DEFAULT CHARSET=$charset COLLATE=$collation";
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
        $password = hash('sha256', $password);

        // Make sure this uri isn't already in use by an existing catalog
        $sql        = 'SELECT `id` FROM `catalog_remote` WHERE `uri` = ?';
        $db_results = Dba::read($sql, [$uri]);

        if (Dba::num_rows($db_results)) {
            debug_event('remote.catalog', 'Cannot add catalog with duplicate uri ' . $uri, 1);
            /* HINT: remote URI */
            AmpError::add('general', sprintf(T_('This path belongs to an existing remote Catalog: %s'), $uri));

            return false;
        }

        $sql = 'INSERT INTO `catalog_remote` (`uri`, `username`, `password`, `catalog_id`) VALUES (?, ?, ?, ?)';
        Dba::write($sql, [$uri, $username, $password, $catalog_id]);

        return true;
    }

    /**
     * add_to_catalog
     * @param null|array<string, string|bool> $options
     * @param null|Interactor $interactor
     * @return int
     * @throws Exception
     */
    public function add_to_catalog(?array $options = null, ?Interactor $interactor = null): int
    {
        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            Ui::show_box_top(T_('Running Remote Update'));
        }
        $songsadded = $this->_update_remote_catalog();
        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            Ui::show_box_bottom();
        }

        return $songsadded;
    }

    /**
     * _connect
     *
     * Connects to the remote catalog that we are.
     */
    private function _connect(): void
    {
        if ($this->remote_handle &&
            $this->remote_handle->state() === 'CONNECTED'
        ) {
            return;
        }

        try {
            $this->remote_handle = new AmpacheApi(
                [
                    'username' => $this->username,
                    'password' => $this->password,
                    'server' => $this->uri,
                    'debug' => false,
                    'debug_callback' => 'debug_event',
                    'api_secure' => (substr($this->uri, 0, 8) == 'https://'),
                    'api_format' => 'xml',
                    'server_version' => Api::DEFAULT_VERSION
                ]
            );
        } catch (Exception $error) {
            debug_event('remote.catalog', 'Connection error: ' . $error->getMessage(), 1);
            if (defined('CLI')) {
                echo T_('Failed to connect to the remote server') . "\n";
            }

            if (defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
                AmpError::add('general', $error->getMessage());
                echo AmpError::display('general');
                flush();
            }

            $this->remote_handle = null;
        }

        if (
            $this->remote_handle &&
            $this->remote_handle->state() != 'CONNECTED'
        ) {
            debug_event('remote.catalog', 'API client failed to connect', 1);
            if (defined('CLI')) {
                echo T_('Failed to connect to the remote server') . "\n";
            }

            if (defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
                AmpError::add('general', T_('Failed to connect to the remote server'));
                echo AmpError::display('general');
            }

            $this->remote_handle = null;
        }
    }

    /**
     * get_remote_tags
     * @return null|array<string, mixed>
     */
    public function get_remote_tags(Podcast_Episode|Video|Song $media): ?array
    {
        $this->_connect();
        if (!$this->remote_handle) {
            debug_event('remote.catalog', 'connection error', 1);

            return null;
        }

        $results   = [];
        $remote_id = (filter_var($media->file, FILTER_VALIDATE_URL))
            ? preg_replace('/^.*[?&]oid=([^&]+).*$/', '$1', html_entity_decode($media->file))
            : Song::get_song_map_object_id($media->getId(), 'remote_' . $this->catalog_id);
        if (!$remote_id) {
            return null;
        }

        $song = $this->remote_handle->send_command(self::CMD_SONG, ['filter' => $remote_id]);

        if (
            $song instanceof SimpleXMLElement &&
            $song->song &&
            ((int)$song->song->attributes()->id) > 0
        ) {
            $results = $this->_gather_tags($song->song);
        }

        return $results;
    }

    /**
     * _gather_tags
     *
     * Gathers tags from a remote song XML element
     * @return null|array<string, mixed>
     */
    private function _gather_tags(SimpleXMLElement $song): ?array
    {
        $this->_connect();
        if (!$this->remote_handle) {
            debug_event('remote.catalog', 'connection error', 1);

            return null;
        }

        $data      = null;
        $remote_id = (int)$song->attributes()->id;
        $tags      = ($this->song_tags)
            ? $this->remote_handle->send_command(self::CMD_SONG_TAGS, ['filter' => $remote_id])
            : null;

        // Iterate over the songs we retrieved and insert them
        if (
            $tags instanceof SimpleXMLElement &&
            isset($tags->song_tag)
        ) {
            $song_tags = $tags->song_tag;
            $data      = [];
            foreach ($song_tags->children() as $name => $value) {
                $key = (string)$name;
                if (count($song_tags->$name) > 1) {
                    // Key in XML multiple times so it's an array
                    if (!isset($data[$key]) || !$data[$key] || !is_array($data[$key])) {
                        $data[$key] = [];
                    }

                    if (!empty((string)$value)) {
                        $data[$key][] = (string)$value;
                    }
                } else {
                    // single value
                    $data[$key] = (!empty((string)$value))
                        ? (string)$value
                        : null;
                }
            }

            $data['catalog'] = $this->catalog_id;
            $data['file']    = (string)$song->filename;

            if (is_string($data['artists'])) {
                $data['artists'] = (!empty($data['artists']))
                    ? [$data['artists']]
                    : null;
            }
            if (is_string($data['genre'])) {
                $data['genre'] = (!empty($data['genre']))
                    ? [$data['genre']]
                    : null;
            }
            if (is_string($data['mb_albumartistid_array'])) {
                $data['mb_albumartistid_array'] = (!empty($data['mb_albumartistid_array']))
                    ? [$data['mb_albumartistid_array']]
                    : null;
            }
            if (is_string($data['mb_artistid_array'])) {
                $data['mb_artistid_array'] = (!empty($data['mb_artistid_array']))
                    ? [$data['mb_artistid_array']]
                    : null;
            }
        } else {
            // Older servers do not have access to song_tags function
            $this->song_tags = false;

            $genres = [];
            foreach ($song->genre as $genre) {
                $genres[] = (string)$genre->name;
            }

            $albumartistids = [];
            $albumartists   = [];
            foreach ($song->albumartist as $albumartist) {
                $albumartistids[] = (string)$albumartist->attributes()->id;
                $albumartists[]   = (string)$albumartist->name;
            }

            $artistids = [];
            $artists   = [];
            foreach ($song->artist as $artist) {
                $artistids[] = (string)$artist->attributes()->id;
                $artists[]   = (string)$artist->name;
            }

            $albumid = (isset($song->album)) ? (int)$song->album->attributes()->id : null;
            $album   = ($albumid) ? $this->remote_handle->send_command(self::CMD_ALBUM, ['filter' => $albumid]) : null;

            $album_data = (object)[];
            if (
                $album instanceof SimpleXMLElement &&
                $album->album->count() > 0
            ) {
                $albumartistids = [];
                $album_data     = $album->album;
                foreach ($album_data->albumartist as $albumartist) {
                    $albumartistids[] = (string)$albumartist->attributes()->id;
                    $albumartists[]   = (string)$albumartist->name;
                }
            }

            $mb_artistid       = null;
            $mb_artistid_array = [];
            foreach ($artistids as $artistid) {
                $artist = $this->remote_handle->send_command(self::CMD_ARTIST, ['filter' => $artistid]);
                if (
                    $artist instanceof SimpleXMLElement &&
                    $artist->artist->count() > 0
                ) {
                    $artist_data = $artist->artist;
                    $mb_artistid = (isset($artist_data[0])) ? $artist_data[0]->mbid : null;
                    foreach ($artist_data->artist as $artist) {
                        if (!empty($artist->mbid)) {
                            $mb_artistid_array[] = (string)$artist->mbid;
                        }
                    }
                }
            }

            $mb_albumartistid       = null;
            $mb_albumartistid_array = [];
            foreach ($albumartistids as $artistid) {
                $artist = $this->remote_handle->send_command(self::CMD_ARTIST, ['filter' => $artistid]);
                if (
                    $artist instanceof SimpleXMLElement &&
                    $artist->artist->count() > 0
                ) {
                    $artist_data      = $artist->artist;
                    $mb_albumartistid = (isset($artist_data[0])) ? $artist_data[0]->mbid : null;
                    foreach ($artist_data->artist as $artist) {
                        if (!empty($artist->mbid)) {
                            $mb_albumartistid_array[] = (string)$artist->mbid;
                        }
                    }
                }
            }

            /** @see VaInfo::DEFAULT_INFO */
            $data = [
                'albumartist' => (!empty($albumartists)) ? $albumartists[0] : null,
                'album' => (isset($song->album)) ? (string)$song->album->name : null,
                'artist' => (!empty($artists)) ? $artists[0] : null,
                'artists' => $artists,
                'art' => null,
                'audio_codec' => null,
                'barcode' => null,
                'bitrate' => (isset($song->bitrate)) ? (string)$song->bitrate : null,
                'catalog_number' => null,
                'catalog' => $this->catalog_id,
                'channels' => (isset($song->channels)) ? (string)$song->channels : null,
                'comment' => (isset($song->comment)) ? (string)$song->comment : null,
                'composer' => (isset($song->composer)) ? (string)$song->composer : null,
                'description' => null,
                'disk' => (isset($song->disk)) ? (string)$song->disk : null,
                'disksubtitle' => (isset($song->disksubtitle)) ? (string)$song->disksubtitle : null,
                'display_x' => null,
                'display_y' => null,
                'encoding' => null,
                'file' => (string)$song->filename,
                'frame_rate' => null,
                'genre' => $genres,
                'isrc' => null,
                'language' => null,
                'lyrics' => null,
                'mb_albumartistid' => $mb_albumartistid,
                'mb_albumartistid_array' => (!empty($mb_albumartistid_array)) ? $mb_albumartistid_array : null,
                'mb_albumid_group' => (isset($album_data->mbid_group)) ? (string)$album_data->mbid_group : null,
                'mb_albumid' => (isset($album_data->mbid)) ? (string)$album_data->mbid : null,
                'mb_artistid' => $mb_artistid,
                'mb_artistid_array' => (!empty($mb_artistid_array)) ? $mb_artistid_array : null,
                'mb_trackid' => (isset($song->mbid)) ? (string)$song->mbid : null,
                'mime' => (isset($song->mime)) ? (string)$song->mime : null,
                'mode' => (isset($song->mode)) ? (string)$song->mode : null,
                'original_name' => null,
                'original_year' => null,
                'publisher' => (isset($song->publisher)) ? (string)$song->publisher : null,
                'r128_album_gain' => (isset($song->r128_album_gain)) ? (string)$song->r128_album_gain : null,
                'r128_track_gain' => (isset($song->r128_track_gain)) ? (string)$song->r128_track_gain : null,
                'rate' => (isset($song->rate)) ? (string)$song->rate : null,
                'rating' => null,
                'release_date' => null,
                'release_status' => null,
                'release_type' => null,
                'replaygain_album_gain' => (isset($song->replaygain_album_gain)) ? (string)$song->replaygain_album_gain : null,
                'replaygain_album_peak' => (isset($song->replaygain_album_peak)) ? (string)$song->replaygain_album_peak : null,
                'replaygain_track_gain' => (isset($song->replaygain_track_gain)) ? (string)$song->replaygain_track_gain : null,
                'replaygain_track_peak' => (isset($song->replaygain_track_peak)) ? (string)$song->replaygain_track_peak : null,
                'resolution_x' => null,
                'resolution_y' => null,
                'size' => (isset($song->size)) ? (string)$song->size : null,
                'version' => null,
                'summary' => null,
                'time' => (isset($song->time)) ? (string)$song->time : null,
                'title' => (isset($song->title)) ? (string)$song->title : null,
                'totaldisks' => null,
                'totaltracks' => null,
                'track' => (isset($song->track)) ? (string)$song->track : null,
                'video_bitrate' => null,
                'video_codec' => null,
                'year' => (isset($song->year)) ? (string)$song->year : null,
            ];

            // If we don't have an album artist, use the artist
            if (empty($data['albumartist']) && !empty($data['artist'])) {
                $data['albumartist'] = $data['artist'];
            }

            // different name for the mbid
            if (empty($data['mb_trackid']) && isset($song->mbid) && !empty($song->mbid)) {
                $data['mb_trackid'] = $song->mbid;
            }
        }

        return $data;
    }

    /**
     * update_remote_catalog
     *
     * Pulls the data from a remote catalog and adds any missing songs to the database.
     */
    private function _update_remote_catalog(string $action = 'add'): int
    {
        set_time_limit(0);

        $this->_connect();
        if (!$this->remote_handle) {
            debug_event('remote.catalog', 'connection error', 1);

            return 0;
        }

        // Get the song count, etc.
        $remote_catalog_info = $this->remote_handle->info();
        if (!$remote_catalog_info instanceof SimpleXMLElement) {
            return 0;
        }

        $total = ($remote_catalog_info->songs > 0)
            ? $remote_catalog_info->songs
            : $remote_catalog_info->max_song;
        debug_event('remote.catalog', sprintf(nT_('%s song was found', '%s songs were found', $total), $total), 4);

        Ui::update_text(
            T_("Remote Catalog Updated"),
            /* HINT: count of songs found */
            sprintf(nT_('%s song was found', '%s songs were found', $total), $total)
        );

        $cache_path   = (string)AmpConfig::get('cache_path', '');
        $cache_target = (string)AmpConfig::get('cache_target', '');
        $web_path     = AmpConfig::get_web_path();

        if (empty($web_path) && !empty(AmpConfig::get('fallback_url'))) {
            $web_path = rtrim((string)AmpConfig::get('fallback_url'), '/');
        }

        $date = time();

        // Hardcoded for now
        $step       = 500;
        $current    = 0;
        $songsadded = 0;
        $songsFound = true;

        while (
            $total > $current &&
            $songsFound
        ) {
            $start = $current;
            $current += $step;
            try {
                $songs = $this->remote_handle->send_command(self::CMD_SONGS, ['offset' => $start, 'limit' => $step]);
                // Iterate over the songs we retrieved and insert them
                if (
                    $songs instanceof SimpleXMLElement &&
                    $songs->song->count() > 0
                ) {
                    foreach ($songs->song as $song) {
                        if (
                            !$song instanceof SimpleXMLElement ||
                            !$song->url
                        ) {
                            continue;
                        }

                        $song_id   = 0;
                        $remote_id = (string)$song->attributes()->id;

                        // Update URLS to the current format for remote catalogs
                        $old_url  = (string)preg_replace('/ssid=[0-9a-z]*&/', '', $song->url);
                        $db_url   = (string)preg_replace('/ssid=[0-9a-z]*&/', 'client=' . urlencode($web_path) . '&', $song->url);
                        $db_file  = (string)$song->filename;

                        if (empty($db_file)) {
                            continue;
                        }

                        $existing_song = false;
                        $song_id_check = $this->check_remote_song([$old_url, $db_url], $db_file, $remote_id);
                        if ($song_id_check) {
                            $existing_song = true;
                        }

                        // stop checking everything depending on the action taken
                        if (
                            $action === 'add' &&
                            $existing_song
                        ) {
                            debug_event('remote.catalog', 'Skip existing song: ' . $song_id_check, 5);
                            if (Song::get_song_map_object_id($song_id_check, 'remote_' . $this->catalog_id) !== $remote_id) {
                                Song::update_song_map([$remote_id], 'remote_' . $this->catalog_id, $song_id_check);
                            }

                            continue;
                        }
                        if (
                            $action === 'verify' &&
                            !$existing_song
                        ) {
                            continue;
                        }

                        $file_target  = ($song_id_check && !empty($cache_target) && $cache_target === (string)$song->stream_format)
                            ? Catalog::get_cache_path($song_id_check, $this->catalog_id, $cache_path, $cache_target)
                            : null;

                        if (
                            $action === 'verify' &&
                            $file_target !== null &&
                            is_file($file_target) &&
                            filemtime($file_target) > ($date - (60 * 60 * 24 * 30)) // 30 day
                        ) {
                            // get file tags directly from the cached file
                            $media = new Song($song_id_check);
                            $data  = $this->get_media_tags($media, ['music'], $this->sort_pattern ?? '', $this->rename_pattern ?? '', $file_target);
                            // don't overwtrite the database path
                            $data['file'] = $db_file;
                        } else {
                            $data = $this->_gather_tags($song->song);
                        }

                        //debug_event('remote.catalog', 'DATA ' . print_r($data, true), 1);
                        if (empty($data['title']) || empty($data['artist']) || empty($data['album'])) {
                            debug_event('remote.catalog', 'Skipping song with no title, artist or album: ' . $db_file, 5);

                            continue;
                        }

                        $song_id = null;
                        if ($action === 'add' && !$existing_song) {
                            $song_id = Song::insert($data);
                            if (!$song_id) {
                                debug_event('remote.catalog', 'Insert failed for ' . $old_url, 1);
                                if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
                                    /* HINT: Song Title */
                                    AmpError::add('general', T_(sprintf('Unable to insert song - %s', $old_url)));
                                    echo AmpError::display('general');
                                    flush();
                                }
                            } else {
                                $songsadded++;
                            }
                        } elseif ($action === 'verify' && $existing_song) {
                            // If we already have the song, update it
                            $song_id = Catalog::get_id_from_file($db_file, 'song');
                            if ($song_id) {
                                $current_song = new Song($song_id);
                                $current_song->fill_ext_info();

                                $info = ($current_song->id) ? self::update_song_from_tags($data, $current_song) : [];
                                if ($info['change']) {
                                    debug_event('remote.catalog', 'Updated existing song ' . $song_id, 5);
                                    $songsadded++;
                                }
                            }
                        }

                        if ($song_id) {
                            // Update the remote id for streaming / lookup
                            if (Song::get_song_map_object_id($song_id, 'remote_' . $this->catalog_id) !== $remote_id) {
                                Song::update_song_map([$remote_id], 'remote_' . $this->catalog_id, $song_id);
                            }

                            // update missing art
                            if (
                                (int)$song->has_art === 1 &&
                                $song->art
                            ) {
                                $current_song = new Song($song_id);
                                $art          = new Art($current_song->album, 'album');
                                if (!$art->has_db_info()) {
                                    $art->insert_url($song->art);
                                }
                            }
                        }
                    }
                } else {
                    $songsFound = false;
                }
            } catch (Exception $error) {
                debug_event('remote.catalog', 'Songs parsing error: ' . $error->getMessage(), 1);
                if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
                    AmpError::add('general', $error->getMessage());
                    echo AmpError::display('general');
                    flush();
                }
            }
        } // end while

        $total_artists = ($remote_catalog_info->artists > 0)
            ? $remote_catalog_info->artists
            : $remote_catalog_info->max_artist;

        // Hardcoded for now
        $current      = 0;
        $artistsFound = true;

        while (
            $total_artists > $current &&
            $artistsFound
        ) {
            $start = $current;
            $current += $step;
            try {
                $artists = $this->remote_handle->send_command(self::CMD_ARTISTS, ['offset' => $start, 'limit' => $step]);
                // Iterate over the songs we retrieved and insert them
                if (
                    $artists instanceof SimpleXMLElement &&
                    $artists->artist->count() > 0
                ) {
                    foreach ($artists->artist as $artist) {
                        if (
                            !$artist instanceof SimpleXMLElement ||
                            !$artist->art
                        ) {
                            continue;
                        }

                        $artist_id = Artist::check((string)$artist->name, (string)$artist->mbid, true);
                        if (
                            $artist_id &&
                            (int)$artist->has_art === 1 &&
                            $artist->art
                        ) {
                            $art = new Art($artist_id, 'artist');
                            if (!$art->has_db_info()) {
                                $art->insert_url($artist->art);
                            }
                        }
                    }
                } else {
                    $artistsFound = false;
                }
            } catch (Exception $error) {
                debug_event('remote.catalog', 'Artists parsing error: ' . $error->getMessage(), 1);
            }
        }

        Ui::update_text(T_("Updated"), T_("Completed updating remote Catalog(s)."));

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
     * clean_catalog_proc
     *
     * Removes remote songs that no longer exist.
     */
    public function clean_catalog_proc(?Interactor $interactor = null): int
    {
        $this->_connect();
        if (!$this->remote_handle) {
            debug_event('remote.catalog', 'Remote login failed', 1);

            return 0;
        }

        $dead       = 0;
        $sql        = 'SELECT `id`, `file` FROM `song` WHERE `catalog` = ?';
        $db_results = Dba::read($sql, [$this->catalog_id]);
        while ($row = Dba::fetch_assoc($db_results)) {
            debug_event('remote.catalog', 'Starting work on ' . $row['file'] . ' (' . $row['id'] . ')', 5);
            try {
                if (filter_var($row['file'], FILTER_VALIDATE_URL)) {
                    // lookup by url
                    $song = $this->remote_handle->send_command(self::CMD_URL_TO_SONG, ['url' => $row['file']]);
                } else {
                    // lookup by remote id
                    $remote_id = Song::get_song_map_object_id($row['id'], 'remote_' . $this->catalog_id);
                    $song      = ($remote_id)
                        ? $this->remote_handle->send_command(self::CMD_SONG, ['filter' => $remote_id])
                        : null;
                }

                if (
                    $song instanceof SimpleXMLElement &&
                    $song->song &&
                    ((int)$song->song->attributes()->id) > 0
                ) {
                    debug_event('remote.catalog', 'keeping song', 5);
                } else {
                    debug_event('remote.catalog', 'removing song', 5);
                    $dead++;
                    Dba::write('DELETE FROM `song` WHERE `id` = ?', [$row['id']]);
                }
            } catch (Exception $error) {
                // FIXME: What to do, what to do
                debug_event('remote.catalog', 'url_to_song parsing error: ' . $error->getMessage(), 1);
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

    public function cache_catalog_file(string $file_target, string $media_file): bool
    {
        return Catalog::cache_remote_file($file_target, $media_file);
    }

    /**
     * cache_catalog_proc
     */
    public function cache_catalog_proc(): bool
    {
        $this->_connect();

        // If we don't get anything back we failed and should bail now
        if (!$this->remote_handle) {
            debug_event('remote.catalog', 'Connection to remote server failed', 1);

            return false;
        }

        try {
            $handshake = $this->remote_handle->info();
        } catch (Exception) {
            return false;
        }

        if (!$handshake instanceof SimpleXMLElement) {
            return false;
        }

        $remote       = AmpConfig::get('cache_remote');
        $cache_path   = (string)AmpConfig::get('cache_path', '');
        $cache_target = (string)AmpConfig::get('cache_target', '');
        // need a destination, source and target format
        if (!is_dir($cache_path) || !$remote || !$cache_target) {
            debug_event('remote.catalog', 'Check your cache_path cache_target and cache_remote settings', 5);

            return false;
        }

        $max_bitrate   = (int)AmpConfig::get('max_bit_rate', 128);
        $user_bit_rate = (int)AmpConfig::get('transcode_bitrate', 128);

        // If the user's crazy, that's no skin off our back
        if ($user_bit_rate > $max_bitrate) {
            $max_bitrate = $user_bit_rate;
        }

        $sql        = "SELECT `id`, `file`, substring_index(file,'.',-1) AS `extension` FROM `song` WHERE `catalog` = ?;";
        $db_results = Dba::read($sql, [$this->catalog_id]);
        while ($row = Dba::fetch_assoc($db_results)) {
            $file_target = ($row['id'] && $cache_target === $row['extension'])
                ? Catalog::get_cache_path($row['id'], $this->catalog_id, $cache_path, $cache_target)
                : null;
            if (empty($file_target)) {
                debug_event('remote.catalog', 'Cache error: no target for ' . $row['id'], 5);
                continue;
            }

            if (!is_file($file_target) || Core::get_filesize($file_target) == 0) {
                $old_target_file = rtrim(trim($cache_path), '/') . '/' . $this->catalog_id . '/' . $row['id'] . '.' . $row['extension'];
                $old_file_exists = is_file($old_target_file);
                if ($old_file_exists) {
                    // check for the old path first
                    rename($old_target_file, $file_target);
                    debug_event('remote.catalog', 'Moved: ' . $row['id'] . ' from: {' . $old_target_file . '}' . ' to: {' . $file_target . '}', 5);
                } else {
                    $song       = new Song($row['id']);
                    $remote_url = $this->getRemoteStreamingUrl($song);
                    if (
                        !empty($remote_url) &&
                        Catalog::cache_remote_file($file_target, $remote_url)
                    ) {
                        debug_event('remote.catalog', 'Saved: ' . $row['id'] . ' to: {' . $file_target . '}', 5);
                    } else {
                        debug_event('remote.catalog', 'Cache error: ' . $row['id'], 5);
                    }
                }

                try {
                    // keep alive just in case
                    $this->remote_handle->send_command(self::CMD_PING);
                } catch (Exception $error) {
                    debug_event(self::class, 'PING error: ' . $error->getMessage(), 5);

                    return false;
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
     * @param string[] $song_urls
     */
    public function check_remote_song(array $song_urls, string $db_file, string $remote_id): ?int
    {
        if (empty($song_urls) || $db_file == '') {
            return null;
        }

        // Check by remote id urls first
        if (!empty($remote_id)) {
            $sql        = 'SELECT `id` FROM `song` WHERE `file` LIKE ?;';
            $db_results = Dba::read($sql, [$this->uri . '/play/index.php?%&type=song%&oid=' . $remote_id . '&%']);
            if ($results = Dba::fetch_assoc($db_results)) {
                Dba::write('UPDATE `song` SET `file` = ? WHERE `id` = ?', [$db_file, $results['id']]);
                Song::update_song_map([$remote_id], 'remote_' . $this->catalog_id, (int)$results['id']);

                return (int)$results['id'];
            }
        }

        // Update old urls to the new format if needed
        foreach ($song_urls as $old_url) {
            // Check for old formats and update the URL to the current version
            $sql        = 'SELECT `id` FROM `song` WHERE `file` = ?;';
            $db_results = Dba::read($sql, [$old_url]);
            if ($results = Dba::fetch_assoc($db_results)) {
                Dba::write('UPDATE `song` SET `file` = ? WHERE `id` = ?', [$db_file, $results['id']]);

                return (int)$results['id'];
            }
        }

        // Check current format
        $sql        = 'SELECT `id` FROM `song` WHERE `file` = ?';
        $db_results = Dba::read($sql, [$db_file]);

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
        $this->_connect();

        // If we don't get anything back we failed and should bail now
        if (!$this->remote_handle) {
            debug_event('remote.catalog', 'Connection to remote server failed', 1);

            return null;
        }

        if (filter_var($media->file, FILTER_VALIDATE_URL)) {
            $handshake = $this->remote_handle->info();
            if (!$handshake instanceof SimpleXMLElement) {
                debug_event('remote.catalog', 'Handshake with remote server failed', 1);

                return null;
            }

            return $media->file . '&ssid=' . $handshake->auth;
        }

        $remote_id = Song::get_song_map_object_id($media->id, 'remote_' . $this->catalog_id);
        if (!$remote_id) {
            debug_event('remote.catalog', 'Unable to identify remote id ' . $media->id . '. Update the catalog.', 1);

            return null;
        }

        $songs = $this->remote_handle->send_command(self::CMD_SONG, ['filter' => $remote_id]);
        if (
            $songs instanceof SimpleXMLElement &&
            $songs->song->count() > 0
        ) {
            foreach ($songs->song as $song) {
                if (
                    $song instanceof SimpleXMLElement &&
                    $song->url
                ) {
                    return (string)$song->url;
                }
            }
        }

        debug_event('remote.catalog', 'Unable to find song ' . $remote_id, 1);

        return null;
    }
}
