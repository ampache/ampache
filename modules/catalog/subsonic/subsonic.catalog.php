<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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
 *
 */

/**
 * Subsonic Catalog Class
 *
 * This class handles all actual work in regards to remote Subsonic catalogs.
 *
 */
class Catalog_subsonic extends Catalog
{
    private $version        = '000002';
    private $type           = 'subsonic';
    private $description    = 'Subsonic Remote Catalog';

    /**
     * get_description
     * This returns the description of this catalog
     */
    public function get_description()
    {
        return $this->description;
    } // get_description

    /**
     * get_version
     * This returns the current version
     */
    public function get_version()
    {
        return $this->version;
    } // get_version

    /**
     * get_type
     * This returns the current catalog type
     */
    public function get_type()
    {
        return $this->type;
    } // get_type

    /**
     * get_create_help
     * This returns hints on catalog creation
     */
    public function get_create_help()
    {
        return "";
    } // get_create_help

    /**
     * is_installed
     * This returns true or false if remote catalog is installed
     */
    public function is_installed()
    {
        $sql        = "SHOW TABLES LIKE 'catalog_subsonic'";
        $db_results = Dba::query($sql);

        return (Dba::num_rows($db_results) > 0);
    } // is_installed

    /**
     * install
     * This function installs the remote catalog
     */
    public function install()
    {
        $collation = (AmpConfig::get('database_collation', 'utf8_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE `catalog_subsonic` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , " .
            "`uri` VARCHAR( 255 ) COLLATE $collation NOT NULL , " .
            "`username` VARCHAR( 255 ) COLLATE $collation NOT NULL , " .
            "`password` VARCHAR( 255 ) COLLATE $collation NOT NULL , " .
            "`catalog_id` INT( 11 ) NOT NULL" .
            ") ENGINE = $engine DEFAULT CHARSET=$charset COLLATE=$collation";
        Dba::query($sql);

        return true;
    } // install

    /**
     * @return array|mixed
     */
    public function catalog_fields()
    {
        $fields['uri']           = array('description' => T_('URI'), 'type' => 'url');
        $fields['username']      = array('description' => T_('Username'), 'type' => 'text');
        $fields['password']      = array('description' => T_('Password'), 'type' => 'password');

        return $fields;
    }

    public $uri;
    public $username;
    public $password;

    /**
     * Constructor
     *
     * Catalog class constructor, pulls catalog information
     * @param integer $catalog_id
     */
    public function __construct($catalog_id = null)
    {
        if ($catalog_id) {
            $this->id = (int) ($catalog_id);
            $info     = $this->get_info($catalog_id);

            foreach ($info as $key => $value) {
                $this->$key = $value;
            }
        }

        require_once AmpConfig::get('prefix') . '/modules/catalog/subsonic/subsonic.client.php';
    }

    /**
     * create_type
     *
     * This creates a new catalog type entry for a catalog
     * It checks to make sure its parameters is not already used before creating
     * the catalog.
     * @param $catalog_id
     * @param array $data
     * @return boolean
     */
    public static function create_type($catalog_id, $data)
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

        // Make sure this uri isn't already in use by an existing catalog
        $sql        = 'SELECT `id` FROM `catalog_subsonic` WHERE `uri` = ?';
        $db_results = Dba::read($sql, array($uri));

        if (Dba::num_rows($db_results)) {
            debug_event('subsonic.catalog', 'Cannot add catalog with duplicate uri ' . $uri, 1);
            /* HINT: subsonic catalog URI */
            AmpError::add('general', sprintf(T_('This path belongs to an existing Subsonic Catalog: %s'), $uri));

            return false;
        }

        $sql = 'INSERT INTO `catalog_subsonic` (`uri`, `username`, `password`, `catalog_id`) VALUES (?, ?, ?, ?)';
        Dba::write($sql, array($uri, $username, $password, $catalog_id));

        return true;
    }

    /**
     * add_to_catalog
     * this function adds new files to an
     * existing catalog
     * @param array $options
     * @return boolean
     */
    public function add_to_catalog($options = null)
    {
        // Prevent the script from timing out
        set_time_limit(0);

        if (!defined('SSE_OUTPUT')) {
            UI::show_box_top(T_('Running Subsonic Remote Update'));
        }
        $this->update_remote_catalog();
        if (!defined('SSE_OUTPUT')) {
            UI::show_box_bottom();
        }

        return true;
    } // add_to_catalog

    /**
     * @return SubsonicClient
     */
    public function createClient()
    {
        return (new SubsonicClient($this->username, $this->password, $this->uri, null));
    }

    /**
     * update_remote_catalog
     *
     * Pulls the data from a remote catalog and adds any missing songs to the
     * database.
     */
    public function update_remote_catalog()
    {
        debug_event('subsonic.catalog', 'Updating remote catalog...', 5);

        $subsonic = $this->createClient();

        $songsadded = 0;
        // Get all albums
        $offset = 0;
        while (true) {
            $albumList = $subsonic->querySubsonic('getAlbumList', ['type' => 'alphabeticalByName', 'size' => 500, 'offset' => $offset]);
            $offset += 500;
            if ($albumList['success']) {
                if (count($albumList['data']['albumList']) == 0) {
                    break;
                }
                foreach ($albumList['data']['albumList']['album'] as $anAlbum) {
                    $album = $subsonic->querySubsonic('getMusicDirectory', ['id' => $anAlbum['id']]);

                    if ($album['success']) {
                        foreach ($album['data']['directory']['child'] as $song) {
                            $artistInfo = $subsonic->querySubsonic('getArtistInfo', ['id' => $song['artistId']]);
                            if (Catalog::is_audio_file($song['path'])) {
                                $data            = array();
                                $data['artist']  = html_entity_decode($song['artist']);
                                $data['album']   = html_entity_decode($song['album']);
                                $data['title']   = html_entity_decode($song['title']);
                                if ($artistInfo['Success']) {
                                    $data['comment'] = html_entity_decode($artistInfo['data']['artistInfo']['biography']);
                                }
                                $data['year']       = $song['year'];
                                $data['bitrate']    = $song['bitRate'] * 1000;
                                $data['size']       = $song['size'];
                                $data['time']       = $song['duration'];
                                $data['track']      = $song['track'];
                                $data['disk']       = $song['discNumber'];
                                $data['coverArt']   = $song['coverArt'];
                                $data['mode']       = 'vbr';
                                $data['genre']      = explode(' ', html_entity_decode($song['genre']));
                                $data['file']       = $this->uri . '/rest/stream.view?id=' . $song['id'] . '&filename=' . urlencode($song['path']);
                                if ($this->check_remote_song($data)) {
                                    debug_event('subsonic.catalog', 'Skipping existing song ' . $data['path'], 5);
                                } else {
                                    $data['catalog'] = $this->id;
                                    debug_event('subsonic.catalog', 'Adding song ' . $song['path'], 5, 'ampache-catalog');
                                    $song_Id = Song::insert($data);
                                    if (!$song_Id) {
                                        debug_event('subsonic.catalog', 'Insert failed for ' . $song['path'], 1);
                                        /* HINT: filename (file path) */
                                        AmpError::add('general', T_('Unable to insert song - %s'), $song['path']);
                                    } else {
                                        if ($song['coverArt']) {
                                            $this->insertArt($song, $song_Id);
                                        }
                                    }
                                    $songsadded++;
                                }
                            }
                        }
                    }
                }
            } else {
                break;
            }
        }

        UI::update_text(T_("Updated"), T_('Completed updating Subsonic Catalog(s)') . " " .
            /* HINT: Number of songs */
            sprintf(nT_('%s Song added', '%s Songs added', $songsadded), $songsadded));

        // Update the last update value
        $this->update_last_update();

        debug_event('subsonic.catalog', 'Catalog updated.', 4);

        return true;
    }

    /**
     * @return array|mixed
     */
    public function verify_catalog_proc()
    {
        return array('total' => 0, 'updated' => 0);
    }

    /**
     * @param $data
     * @param $song_Id
     * @return boolean
     */
    public function insertArt($data, $song_Id)
    {
        $subsonic = $this->createClient();
        $song     = new Song($song_Id);
        $art      = new Art($song->album, 'album');
        if (AmpConfig::get('album_art_max_height') && AmpConfig::get('album_art_max_width')) {
            $size = array('width' => AmpConfig::get('album_art_max_width'), 'height' => AmpConfig::get('album_art_max_height'));
        } else {
            $size  = array('width' => 275, 'height' => 275);
        }
        $image = $subsonic->querySubsonic('getCoverArt', ['id' => $data['coverArt'], $size], true);

        return $art->insert($image, '');
    }
    /**
     * clean_catalog_proc
     *
     * Removes subsonic songs that no longer exist.
     */
    public function clean_catalog_proc()
    {
        $subsonic = $this->createClient();

        $dead = 0;

        $sql        = 'SELECT `id`, `file` FROM `song` WHERE `catalog` = ?';
        $db_results = Dba::read($sql, array($this->id));
        while ($row = Dba::fetch_assoc($db_results)) {
            debug_event('subsonic.catalog', 'Starting work on ' . $row['file'] . '(' . $row['id'] . ')', 5, 'ampache-catalog');
            $remove = false;
            try {
                $songid = $this->url_to_songid($row['file']);
                $song   = $subsonic->getSong(array('id' => $songid));
                if (!$song['success']) {
                    $remove = true;
                }
            } catch (Exception $error) {
                debug_event('subsonic.catalog', 'Clean error: ' . $error->getMessage(), 5, 'ampache-catalog');
            }

            if (!$remove) {
                debug_event('subsonic.catalog', 'keeping song', 5, 'ampache-catalog');
            } else {
                debug_event('subsonic.catalog', 'removing song', 5, 'ampache-catalog');
                $dead++;
                Dba::write('DELETE FROM `song` WHERE `id` = ?', array($row['id']));
            }
        }

        return $dead;
    }

    /**
     * move_catalog_proc
     * This function updates the file path of the catalog to a new location (unsupported)
     * @param string $new_path
     * @return boolean
     */
    public function move_catalog_proc($new_path)
    {
        return false;
    }

    /**
     * check_remote_song
     *
     * checks to see if a remote song exists in the database or not
     * if it find a song it returns the UID
     * @param array $song
     * @return boolean|mixed
     */
    public function check_remote_song($song)
    {
        $url = $song['file'];

        $sql        = 'SELECT `id` FROM `song` WHERE `file` = ?';
        $db_results = Dba::read($sql, array($url));

        if ($results = Dba::fetch_assoc($db_results)) {
            return $results['id'];
        }

        return false;
    }

    /**
     * @param string $file_path
     * @return string|string[]
     */
    public function get_rel_path($file_path)
    {
        $catalog_path = rtrim($this->uri, "/");

        return(str_replace($catalog_path . "/", "", $file_path));
    }

    /**
     * @param $url
     * @return integer
     */
    public function url_to_songid($url)
    {
        $song_id = 0;
        preg_match('/\?id=([0-9]*)&/', $url, $matches);
        if (count($matches)) {
            $song_id = $matches[1];
        }

        return $song_id;
    }

    /**
     * format
     *
     * This makes the object human-readable.
     */
    public function format()
    {
        parent::format();
        $this->f_info      = $this->uri;
        $this->f_full_info = $this->uri;
    }

    /**
     * @param Podcast_Episode|Song|Song_Preview|Video $media
     * @return media|null
     */
    public function prepare_media($media)
    {
        $subsonic = $this->createClient();
        $url      = $subsonic->parameterize($media->file . '&');

        header('Location: ' . $url);
        debug_event('subsonic.catalog', 'Started remote stream - ' . $url, 5);

        return null;
    }
} // end of subsonic.catalog class
