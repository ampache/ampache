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
 * Google Music Catalog Class
 *
 * This class handles all actual work in regards to remote Google Music catalogs.
 *
 */
class Catalog_googlemusic extends Catalog
{
    private $version        = '000001';
    private $type           = 'googlemusic';
    private $description    = 'Remote Google Music Catalog';

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
        $sql = "DESCRIBE `catalog_googlemusic`";
        $db_results = Dba::query($sql);

        return Dba::num_rows($db_results);


    } // is_installed

    /**
     * install
     * This function installs the remote catalog
     */
    public function install()
    {
        $sql = "CREATE TABLE `catalog_googlemusic` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , ".
            "`email` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
            "`password` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
            "`deviceid` VARCHAR( 255 ) COLLATE utf8_unicode_ci NULL , " .
            "`catalog_id` INT( 11 ) NOT NULL" .
            ") ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $db_results = Dba::query($sql);

        return true;

    } // install

    public function catalog_fields()
    {
        $fields['email']      = array('description' => T_('Email'),'type'=>'textbox');
        $fields['password']      = array('description' => T_('Password'),'type'=>'password');
        // Device ID not required for streaming access
        //$fields['deviceid']      = array('description' => T_('Device ID'),'type'=>'textbox');

        return $fields;

    }

    public $email;
    public $password;
    public $deviceid;

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

        require_once AmpConfig::get('prefix') . '/modules/GMApi/GMApi.php';
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
        $email = $data['email'];
        $password = $data['password'];
        $deviceid = $data['deviceid'];

        if (!strlen($email) OR !strlen($password)) {
            Error::add('general', T_('Error: Email and Password Required for Google Music Catalogs'));
            return false;
        }

        // Make sure this email isn't already in use by an existing catalog
        $sql = 'SELECT `id` FROM `catalog_googlemusic` WHERE `email` = ?';
        $db_results = Dba::read($sql, array($email));

        if (Dba::num_rows($db_results)) {
            debug_event('catalog', 'Cannot add catalog with duplicate email ' . $email, 1);
            Error::add('general', sprintf(T_('Error: Catalog with %s already exists'), $email));
            return false;
        }

        $sql = 'INSERT INTO `catalog_googlemusic` (`email`, `password`, `deviceid`, `catalog_id`) VALUES (?, ?, ?, ?)';
        Dba::write($sql, array($email, $password, $deviceid, $catalog_id));
        return true;
    }

    /**
     * add_to_catalog
     * this function adds new files to an
     * existing catalog
     */
    public function add_to_catalog($options = null)
    {
        UI::show_box_top(T_('Running Google Music Remote Update') . '. . .');
        $this->update_remote_catalog();
        UI::show_box_bottom();

        return true;
    } // add_to_catalog

    public function createClient()
    {
        $api = new GMApi();
        $api->setDebug(AmpConfig::get('debug'));
        $api->enableRestore(false);
        $api->enableMACAddressCheck(false);
        $api->enableSessionFile(false);

        if (!$api->login($this->email, $this->password, $this->deviceid)) {
            debug_event('googlemusic_catalog', 'Google Music authentication failed.', 1);
            return null;
        }

        return $api;
    }

    /**
     * update_remote_catalog
     *
     * Pulls the data from a remote catalog and adds any missing songs to the
     * database.
     */
    public function update_remote_catalog()
    {
        $api = $this->createClient();
        if ($api != null) {
            $songsadded = 0;
            // Get all artists
            $songs = $api->get_all_songs();
            if ($songs) {
                foreach ($songs as $song) {
                    $data = Array();
                    $data['artist'] = $song['artist'];
                    $data['album'] = $song['album'];
                    $data['title'] = $song['title'];
                    $data['year'] = $song['year'];
                    $data['time'] = intval($song['durationMillis'] / 1000);
                    $data['track'] = $song['track'];
                    $data['bitrate'] = $song['bitrate'];
                    $data['disk'] = $song['disc'];
                    $data['mode'] = 'vbr';
                    $data['genre'] = explode(' ', html_entity_decode($song['genre']));
                    $data['comment'] = $song['comment'];
                    // We cannot get a permanent stream url from Google Music, store the file id only
                    $data['file'] = $this->email . '/' . $song['id'];
                    if ($this->check_remote_song($data)) {
                        debug_event('googlemusic_catalog', 'Skipping existing song ' . $data['file'], 5);
                    } else {
                        $data['catalog'] = $this->id;
                        debug_event('googlemusic_catalog', 'Adding song ' . $song['file'], 5, 'ampache-catalog');
                        if (!Song::insert($data)) {
                            debug_event('googlemusic_catalog', 'Insert failed for ' . $song['file'], 1);
                            Error::add('general', T_('Unable to Insert Song - %s'), $song['file']);
                            Error::display('general');
                            flush();
                        } else {
                            $songsadded++;
                        }
                    }
                }

                echo "<p>" . T_('Completed updating Google Music catalog(s).') . " " . $songsadded . " " . T_('Songs added.') . "</p><hr />\n";
                flush();

                // Update the last update value
                $this->update_last_update();
            } else {
                echo "<p>" . T_('API Error: cannot connect song list.') . "</p><hr />\n";
                flush();
            }
        } else {
            echo "<p>" . T_('API Error: cannot connect to Google Music.') . "</p><hr />\n";
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
     * Removes songs that no longer exist.
     */
    public function clean_catalog_proc()
    {
        $api = $this->createClient();
        $dead = 0;
        if ($api != null) {
            $songs = $api->get_all_songs();
            if ($songs) {
                $files = array();
                foreach ($songs as $song) {
                    $files[] = $this->email . '/' . $song['id'];
                }

                $sql = 'SELECT `id`, `file` FROM `song` WHERE `catalog` = ?';
                $db_results = Dba::read($sql, array($this->id));
                while ($row = Dba::fetch_assoc($db_results)) {
                    debug_event('googlemusic-clean', 'Starting work on ' . $row['file'] . '(' . $row['id'] . ')', 5, 'ampache-catalog');
                    if (in_array($row['file'], $files)) {
                        debug_event('googlemusic-clean', 'keeping song', 5, 'ampache-catalog');
                    } else {
                        debug_event('googlemusic-clean', 'removing song', 5, 'ampache-catalog');
                        $dead++;
                        Dba::write('DELETE FROM `song` WHERE `id` = ?', array($row['id']));
                    }
                }
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
        $catalog_path = rtrim($info->email, "/");
        return( str_replace( $catalog_path . "/", "", $file_path ) );
    }

    public function url_to_songid($url)
    {
        $id = 0;
        $info = explode('/', $url);
        if (count($info) > 1) {
            $id = $info[1];
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
        $this->f_full_info = $this->email;
    }

    public function prepare_media($media)
    {
        $api = $this->createClient();
        if ($api != null) {
            $songid = $this->url_to_songid($media->file);

            $song = $api->get_stream_url($songid);
            if ($song) {
                header('Location: ' . $song);
                debug_event('play', 'Started remote stream - ' . $song, 5);
            } else {
                debug_event('play', 'Cannot get remote stream for song ' . $media->file, 5);
            }
        }

        return null;
    }

} // end of catalog class
