<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
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
    private $version        = '000001';
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
        $sql = "SHOW TABLES LIKE 'catalog_subsonic'";
        $db_results = Dba::query($sql);

        return (Dba::num_rows($db_results) > 0);


    } // is_installed

    /**
     * install
     * This function installs the remote catalog
     */
    public function install()
    {
        $sql = "CREATE TABLE `catalog_subsonic` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , ".
            "`uri` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
            "`username` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
            "`password` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
            "`catalog_id` INT( 11 ) NOT NULL" .
            ") ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $db_results = Dba::query($sql);

        return true;

    } // install

    public function catalog_fields()
    {
        $fields['uri']      = array('description' => T_('URI'),'type'=>'textbox');
        $fields['username']      = array('description' => T_('Username'),'type'=>'textbox');
        $fields['password']      = array('description' => T_('Password'),'type'=>'password');

        return $fields;

    }

    public $uri;
    public $username;
    public $password;

    /**
     * Constructor
     *
     * Catalog class constructor, pulls catalog information
     */
    public function __construct($catalog_id = null)
    {
        if ($catalog_id) {
            $this->id = intval($catalog_id);
            $info = $this->get_info($catalog_id);

            foreach ($info as $key=>$value) {
                $this->$key = $value;
            }
        }

        require_once AmpConfig::get('prefix') . '/modules/subsonic/subsonic.client.php';
    }

    /**
     * create_type
     *
     * This creates a new catalog type entry for a catalog
     * It checks to make sure its parameters is not already used before creating
     * the catalog.
     */
    public static function create_type($catalog_id, $data)
    {
        $uri = $data['uri'];
        $username = $data['username'];
        $password = $data['password'];

        if (substr($uri,0,7) != 'http://' && substr($uri,0,8) != 'https://') {
            Error::add('general', T_('Error: Subsonic selected, but path is not a URL'));
            return false;
        }

        if (!strlen($username) OR !strlen($password)) {
            Error::add('general', T_('Error: Username and Password Required for Subsonic Catalogs'));
            return false;
        }

        // Make sure this uri isn't already in use by an existing catalog
        $sql = 'SELECT `id` FROM `catalog_subsonic` WHERE `uri` = ?';
        $db_results = Dba::read($sql, array($uri));

        if (Dba::num_rows($db_results)) {
            debug_event('catalog', 'Cannot add catalog with duplicate uri ' . $uri, 1);
            Error::add('general', sprintf(T_('Error: Catalog with %s already exists'), $uri));
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
     */
    public function add_to_catalog($options = null)
    {
        // Prevent the script from timing out
        set_time_limit(0);

        UI::show_box_top(T_('Running Subsonic Remote Update') . '. . .');
        $this->update_remote_catalog();
        UI::show_box_bottom();

        return true;
    } // add_to_catalog

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
        $subsonic = $this->createClient();

        $songsadded = 0;
        // Get all artists
        $artists = $subsonic->getIndexes();
        if ($artists['success']) {
            foreach ($artists['data']['indexes']['index'] as $index) {
                foreach ($index['artist'] as $artist) {
                    // Get albums for artist
                    $albums = $subsonic->getMusicDirectory(array('id' => $artist['id']));

                    if ($albums['success']) {
                        foreach ($albums['data']['directory']['child'] as $album) {
                            if (is_array($album)) {
                                $songs = $subsonic->getMusicDirectory(array('id' => $album['id']));
                                if ($songs['success']) {
                                    foreach ($songs['data']['directory']['child'] as $song) {
                                        if (is_array($song)) {
                                            $data = Array();
                                            $data['artist'] = html_entity_decode($song['artist']);
                                            $data['album'] = html_entity_decode($song['album']);
                                            $data['title'] = html_entity_decode($song['title']);
                                            $data['bitrate'] = $song['bitRate'] * 1000;
                                            $data['size'] = $song['size'];
                                            $data['time'] = $song['duration'];
                                            $data['track'] = $song['track'];
                                            $data['disk'] = $song['discNumber'];
                                            $data['mode'] = 'vbr';
                                            $data['genre'] = explode(' ', html_entity_decode($song['genre']));
                                            $data['file'] = $this->uri . '/rest/stream.view?id=' . $song['id'] . '&filename=' . urlencode($song['path']);
                                            if ($this->check_remote_song($data)) {
                                                debug_event('subsonic_catalog', 'Skipping existing song ' . $data['path'], 5);
                                            } else {
                                                $data['catalog'] = $this->id;
                                                debug_event('subsonic_catalog', 'Adding song ' . $song['path'], 5, 'ampache-catalog');
                                                if (!Song::insert($data)) {
                                                    debug_event('subsonic_catalog', 'Insert failed for ' . $song['path'], 1);
                                                    Error::add('general', T_('Unable to Insert Song - %s'), $song['path']);
                                                    Error::display('general');
                                                    flush();
                                                } else {
                                                    $songsadded++;
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    echo "<p>" . T_('Song Error.') . ": " . $songs['error'] . "</p><hr />\n";
                                    flush();
                                }
                            }
                        }
                    } else {
                        echo "<p>" . T_('Album Error.') . ": " . $albums['error'] . "</p><hr />\n";
                        flush();
                    }
                }
            }

            echo "<p>" . T_('Completed updating Subsonic catalog(s).') . " " . $songsadded . " " . T_('Songs added.') . "</p><hr />\n";
            flush();

            // Update the last update value
            $this->update_last_update();
        } else {
            echo "<p>" . T_('Artist Error.') . ": " . $artists['error'] . "</p><hr />\n";
            flush();
        }

        return true;

    }

    public function verify_catalog_proc()
    {
        return array('total' => 0, 'updated' => 0);
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

        $sql = 'SELECT `id`, `file` FROM `song` WHERE `catalog` = ?';
        $db_results = Dba::read($sql, array($this->id));
        while ($row = Dba::fetch_assoc($db_results)) {
            debug_event('subsonic-clean', 'Starting work on ' . $row['file'] . '(' . $row['id'] . ')', 5, 'ampache-catalog');
            $remove = false;
            try {
                $songid = $this->url_to_songid($row['file']);
                $song = $subsonic->getSong(array('id' => $songid));
                if (!$song['success']) {
                    $remove = true;
                }
            } catch (Exception $e) {
                debug_event('subsonic-clean', 'Clean error: ' . $e->getMessage(), 5, 'ampache-catalog');
            }

            if (!$remove) {
                debug_event('subsonic-clean', 'keeping song', 5, 'ampache-catalog');
            } else {
                debug_event('subsonic-clean', 'removing song', 5, 'ampache-catalog');
                $dead++;
                Dba::write('DELETE FROM `song` WHERE `id` = ?', array($row['id']));
            }
        }

        return $dead;
    }

    /**
     * check_remote_song
     *
     * checks to see if a remote song exists in the database or not
     * if it find a song it returns the UID
     */
    public function check_remote_song($song)
    {
        $url = $song['file'];

        $sql = 'SELECT `id` FROM `song` WHERE `file` = ?';
        $db_results = Dba::read($sql, array($url));

        if ($results = Dba::fetch_assoc($db_results)) {
            return $results['id'];
        }

        return false;
    }

    public function get_rel_path($file_path)
    {
        $info = $this->_get_info();
        $catalog_path = rtrim($info->uri, "/");
        return( str_replace( $catalog_path . "/", "", $file_path ) );
    }

    public function url_to_songid($url)
    {
        $id = 0;
        preg_match('/\?id=([0-9]*)&/', $url, $matches);
        if (count($matches)) {
            $id = $matches[1];
        }
        return $id;
    }

    /**
     * format
     *
     * This makes the object human-readable.
     */
    public function format()
    {
        parent::format();
        $this->f_info = $this->uri;
        $this->f_full_info = $this->uri;
    }

    public function prepare_media($media)
    {
        $subsonic = $this->createClient();
        $url = $subsonic->parameterize($media->file . '&');

        header('Location: ' . $url);
        debug_event('play', 'Started remote stream - ' . $url, 5);

        return null;
    }

} // end of catalog class
