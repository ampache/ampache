<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Remote Catalog Class
 *
 * This class handles all actual work in regards to remote catalogs.
 *
 */
class Catalog_remote extends Catalog
{
    private $version        = '000001';
    private $type           = 'remote';
    private $description    = 'Ampache Remote Catalog';

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
        $sql        = "SHOW TABLES LIKE 'catalog_remote'";
        $db_results = Dba::query($sql);

        return (Dba::num_rows($db_results) > 0);
    } // is_installed

    /**
     * install
     * This function installs the remote catalog
     */
    public function install()
    {
        $sql = "CREATE TABLE `catalog_remote` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , " .
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
        $fields['uri']           = array('description' => T_('Uri'),'type'=>'textbox');
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
            $info     = $this->get_info($catalog_id);

            foreach ($info as $key=>$value) {
                $this->$key = $value;
            }
        }
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
        $uri      = $data['uri'];
        $username = $data['username'];
        $password = $data['password'];

        if (substr($uri,0,7) != 'http://' && substr($uri,0,8) != 'https://') {
            AmpError::add('general', T_('Error: Remote selected, but path is not a URL'));
            return false;
        }

        if (!strlen($username) or !strlen($password)) {
            AmpError::add('general', T_('Error: Username and Password Required for Remote Catalogs'));
            return false;
        }
        $password = hash('sha256', $password);

        // Make sure this uri isn't already in use by an existing catalog
        $sql        = 'SELECT `id` FROM `catalog_remote` WHERE `uri` = ?';
        $db_results = Dba::read($sql, array($uri));

        if (Dba::num_rows($db_results)) {
            debug_event('catalog', 'Cannot add catalog with duplicate uri ' . $uri, 1);
            AmpError::add('general', sprintf(T_('Error: Catalog with %s already exists'), $uri));
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
     */
    public function add_to_catalog($options = null)
    {
        if (!defined('SSE_OUTPUT')) {
            UI::show_box_top(T_('Running Remote Update') . '. . .');
        }
        $this->update_remote_catalog();
        if (!defined('SSE_OUTPUT')) {
            UI::show_box_bottom();
        }

        return true;
    } // add_to_catalog

    /**
     * connect
     *
     * Connects to the remote catalog that we are.
     */
    public function connect()
    {
        try {
            $remote_handle = new AmpacheApi\AmpacheApi(array(
                'username' => $this->username,
                'password' => $this->password,
                'server' => $this->uri,
                'debug_callback' => 'debug_event',
                'api_secure' => (substr($this->uri, 0, 8) == 'https://')
            ));
        } catch (Exception $e) {
            debug_event('catalog', 'Connection error: ' . $e->getMessage(), 1);
            AmpError::add('general', $e->getMessage());
            AmpError::display('general');
            flush();
            return false;
        }

        if ($remote_handle->state() != 'CONNECTED') {
            debug_event('catalog', 'API client failed to connect', 1);
            AmpError::add('general', T_('Error connecting to remote server'));
            AmpError::display('general');
            return false;
        }

        return $remote_handle;
    }

    /**
     * update_remote_catalog
     *
     * Pulls the data from a remote catalog and adds any missing songs to the
     * database.
     */
    public function update_remote_catalog($type = 0)
    {
        set_time_limit(0);

        $remote_handle = $this->connect();
        if (!$remote_handle) {
            return false;
        }

        // Get the song count, etc.
        $remote_catalog_info = $remote_handle->info();

        // Tell 'em what we've found, Johnny!
        UI::update_text('', sprintf(T_('%u remote catalog(s) found (%u songs)'), $remote_catalog_info['catalogs'], $remote_catalog_info['songs']));

        // Hardcoded for now
        $step    = 500;
        $current = 0;
        $total   = $remote_catalog_info['songs'];

        while ($total > $current) {
            $start = $current;
            $current += $step;
            try {
                $songs = $remote_handle->send_command('songs', array('offset' => $start, 'limit' => $step));
            } catch (Exception $e) {
                debug_event('catalog', 'Songs parsing error: ' . $e->getMessage(), 1);
                AmpError::add('general',$e->getMessage());
                AmpError::display('general');
                flush();
            }

            // Iterate over the songs we retrieved and insert them
            foreach ($songs as $data) {
                if ($this->check_remote_song($data['song'])) {
                    debug_event('remote_catalog', 'Skipping existing song ' . $data['song']['url'], 5);
                } else {
                    $data['song']['catalog'] = $this->id;
                    $data['song']['file']    = preg_replace('/ssid=.*?&/', '', $data['song']['url']);
                    if (!Song::insert($data['song'])) {
                        debug_event('remote_catalog', 'Insert failed for ' . $data['song']['self']['id'], 1);
                        AmpError::add('general', T_('Unable to Insert Song - %s'), $data['song']['title']);
                        AmpError::display('general');
                        flush();
                    }
                }
            }
        } // end while

        UI::update_text('', T_('Completed updating remote catalog(s).'));

        // Update the last update value
        $this->update_last_update();

        return true;
    }

    public function verify_catalog_proc()
    {
        return array('total' => 0, 'updated' => 0);
    }

    /**
     * clean_catalog_proc
     *
     * Removes remote songs that no longer exist.
     */
    public function clean_catalog_proc()
    {
        $remote_handle = $this->connect();
        if (!$remote_handle) {
            debug_event('remote-clean', 'Remote login failed', 1, 'ampache-catalog');
            return false;
        }

        $dead = 0;

        $sql        = 'SELECT `id`, `file` FROM `song` WHERE `catalog` = ?';
        $db_results = Dba::read($sql, array($this->id));
        while ($row = Dba::fetch_assoc($db_results)) {
            debug_event('remote-clean', 'Starting work on ' . $row['file'] . '(' . $row['id'] . ')', 5, 'ampache-catalog');
            try {
                $song = $remote_handle->send_command('url_to_song', array('url' => $row['file']));
            } catch (Exception $e) {
                // FIXME: What to do, what to do
                debug_event('catalog', 'url_to_song parsing error: ' . $e->getMessage(), 1);
            }

            if (count($song) == 1) {
                debug_event('remote-clean', 'keeping song', 5, 'ampache-catalog');
            } else {
                debug_event('remote-clean', 'removing song', 5, 'ampache-catalog');
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
        $url = preg_replace('/ssid=.*&/', '', $song['url']);

        $sql        = 'SELECT `id` FROM `song` WHERE `file` = ?';
        $db_results = Dba::read($sql, array($url));

        if ($results = Dba::fetch_assoc($db_results)) {
            return $results['id'];
        }

        return false;
    }

    public function get_rel_path($file_path)
    {
        $info         = $this->_get_info();
        $catalog_path = rtrim($info->uri, "/");
        return( str_replace( $catalog_path . "/", "", $file_path ) );
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

    public function prepare_media($media)
    {
        $remote_handle = $this->connect();

        // If we don't get anything back we failed and should bail now
        if (!$remote_handle) {
            debug_event('play', 'Connection to remote server failed', 1);
            exit;
        }

        $handshake = $remote_handle->info();
        $url       = $media->file . '&ssid=' . $handshake['auth'];

        header('Location: ' . $url);
        debug_event('play', 'Started remote stream - ' . $url, 5);

        return null;
    }
} // end of catalog class

