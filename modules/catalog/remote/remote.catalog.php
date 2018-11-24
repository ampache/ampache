<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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

namespace Modules\Catalog\Remote;

use App\Models\Song;
use App\Classes\Catalog;
use AmpacheApi\AmpacheApi;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Exception;

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
        return Schema::hasTable('catalog_remote');
    } // is_installed

    /**
     * install
     * This function installs the remote catalog
     */
    public function install()
    {
        Schema::create('catalog_remote', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uri', 255);
            $table->string('username', 255);
            $table->string('password', 255);
            $table->integer('catalog_id');
            $table->engine = 'MYISAM';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
        });
        
        return true;
    } // install

    public function catalog_fields()
    {
        $fields['uri']           = array('description' => __('Uri'),'type' => 'url');
        $fields['username']      = array('description' => __('Username'),'type' => 'text');
        $fields['password']      = array('description' => __('Password'),'type' => 'password');

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
            
            foreach ($info as $key => $value) {
                $items = get_object_vars($value);
                $keys  = array_keys($items);
                foreach ($keys as $item) {
                    $this->$item = $items[$item];
                }
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

        if (substr($uri, 0, 7) != 'http://' && substr($uri, 0, 8) != 'https://') {
            Log::error(__('Error: Remote selected, but path is not a URL'));

            return false;
        }

        if (!strlen($username) or !strlen($password)) {
            Log::error(__('Error: Username and Password Required for Remote Catalogs'));

            return false;
        }
        $password = hash('sha256', $password);

        // Make sure this uri isn't already in use by an existing catalog
        $db_results = DB::table('Catalog_remote')->select('id')->where('url', "=", $url)->get();
        if (count($db_results)) {
            //debug_event('catalog', 'Cannot add catalog with duplicate path ' . $path, 1);
            Log::error(sprintf('Error: Catalog with %s already exists', $path));

            return false;
        }
        
        DB::table('catalog_remote')->insert(
            ['uri' => $uri, 'username' => $username, 'password' => $password, 'catalog_id' => $catalog_id]
            );

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
            //            UI::show_box_top(T_('Running Remote Update') . '. . .');
        }
        $this->update_remote_catalog();
        if (!defined('SSE_OUTPUT')) {
            //            UI::show_box_bottom();
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
            $remote_handle = new AmpacheApi(array(
                'username' => $this->username,
                'password' => $this->password,
                'server' => $this->uri,
                'debug_callback' => 'debug_event',
                'api_secure' => (substr($this->uri, 0, 8) == 'https://')
            ));
        } catch (Exception $e) {
            //            debug_event('catalog', 'Connection error: ' . $e->getMessage(), 1);
            Log::error($e->getMessage());
            Log::error('general');
            flush();

            return false;
        }

        if ($remote_handle->state() != 'CONNECTED') {
            Log::debug('catalog', 'API client failed to connect', 1);
            Log::error(__('Error connecting to remote server'));
 
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
//        UI::update_text('', sprintf(T_('%u remote catalog(s) found (%u songs)'), $remote_catalog_info['catalogs'], $remote_catalog_info['songs']));

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
                //                debug_event('catalog', 'Songs parsing error: ' . $e->getMessage(), 1);
                LOg::error($e->getMessage());
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
                        Log::debug('Insert failed for ' . $data['song']['self']['id'], 1);
                        Log::error(__('Unable to Insert Song - %s'), $data['song']['title']);
                        flush();
                    }
                }
            }
        } // end while

//        UI::update_text('', T_('Completed updating remote catalog(s).'));

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
            //            debug_event('remote-clean', 'Remote login failed', 1, 'ampache-catalog');

            return false;
        }

        $dead = 0;

        $db_results = DB::table('song')->select('id', 'file')->where('catalog', '=', $this->id)->get();
        foreach ($db_results as $result) {
            try {
                $song = $remote_handle->send_command('url_to_song', array('url' => $row['file']));
            } catch (Exception $e) {
                // FIXME: What to do, what to do
                    //                debug_event('catalog', 'url_to_song parsing error: ' . $e->getMessage(), 1);
            }
         
            if (count($song) == 1) {
                //                debug_event('remote-clean', 'keeping song', 5, 'ampache-catalog');
            } else {
                //               debug_event('remote-clean', 'removing song', 5, 'ampache-catalog');
                $dead++;
                DB::table('song')->whereIn('id', array($row['id']))->delete();
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

        $db_results = DB::table('song')->select('id')->where('file', '=', $url)->get();
        if (count($db_results)) {
            return $db_results->id;
        }

        return false;
    }

    public function get_rel_path($file_path)
    {
        $catalog_path = rtrim($this->uri, "/");

        return(str_replace($catalog_path . "/", "", $file_path));
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
            //           debug_event('play', 'Connection to remote server failed', 1);
            exit;
        }

        $handshake = $remote_handle->info();
        $url       = $media->file . '&ssid=' . $handshake['auth'];

        header('Location: ' . $url);
//        debug_event('play', 'Started remote stream - ' . $url, 5);

        return null;
    }
} // end of catalog class
