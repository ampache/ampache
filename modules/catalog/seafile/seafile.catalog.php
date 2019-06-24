<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
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
 * Seafile Catalog Class
 *
 * This class handles all actual work in regards to remote Seafile catalogs.
 *
 */

require_once('SeafileAdapter.php');

class Catalog_Seafile extends Catalog
{
    private static $version     = '000001';
    private static $type        = 'seafile';
    private static $description = 'Seafile Remote Catalog';
    private static $table_name  = 'catalog_seafile';

    private $seafile;

    /**
     * get_description
     * This returns the description of this catalog
     */
    public function get_description()
    {
        return self::$description;
    } // get_description

    /**
     * get_version
     * This returns the current version
     */
    public function get_version()
    {
        return self::$version;
    } // get_version

    /**
     * get_type
     * This returns the current catalog type
     */
    public function get_type()
    {
        return self::$type;
    } // get_type

    /**
     * get_create_help
     * This returns hints on catalog creation
     */
    public function get_create_help()
    {
        $help = "<ul><li>" . T_("Install a Seafile server as described in its documentation on %s") . "</li>" .
                "<li>" . T_("Enter url to server (e.g. 'https://seafile.example.com') and library name (e.g. 'Music').") . "</li>" .
                "<li>" . T_("'API Call Delay' is a delay inserted between repeated requests to Seafile (such as during an Add or Clean action) to accomodate Seafile's Rate Limiting. <br/>" .
                "The default is tuned towards Seafile's default rate limit settings; see %sthis forum post%s for more information.") . "</li>" .
                "<li>" . T_("After creating the catalog, you must 'Make it ready' on the catalog table.") . "</li></ul>";

        return sprintf($help, "<a target='_blank' href='https://www.seafile.com/'>https://www.seafile.com/</a>", "<a href='https://forum.syncwerk.com/t/too-many-requests-when-using-web-api-status-code-429/2330'>", "</a>");
    } // get_create_help

    /**
     * is_installed
     * This returns true or false if remote catalog is installed
     */
    public function is_installed()
    {
        $sql        = "SHOW TABLES LIKE '" . self::$table_name . "'";
        $db_results = Dba::query($sql);

        return (Dba::num_rows($db_results) > 0);
    } // is_installed

    /**
     * install
     * This function installs the remote catalog
     */
    public function install()
    {
        $sql = "CREATE TABLE `" . self::$table_name . "` (" .
            "`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , " .
            "`server_uri` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
            "`api_key` VARCHAR( 100 ) COLLATE utf8_unicode_ci NOT NULL , " .
            "`library_name` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
            "`api_call_delay` INT NOT NULL , " .
            "`catalog_id` INT( 11 ) NOT NULL" .
            ") ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        Dba::query($sql);

        return true;
    }

    /**
     * catalog_fields
     *
     * Return the necessary settings fields for creating a new Seafile catalog
     */
    public function catalog_fields()
    {
        $fields['server_uri']     = array('description' => T_('Server URI'), 'type' => 'text', 'value' => 'https://seafile.example.org/');
        $fields['library_name']   = array('description' => T_('Library Name'), 'type' => 'text', 'value' => 'Music');
        $fields['api_call_delay'] = array('description' => T_('API Call Delay'), 'type' => 'number', 'value' => '250');
        $fields['username']       = array('description' => T_('Seafile Username/Email'), 'type' => 'text', 'value' => '' );
        $fields['password']       = array('description' => T_('Seafile Password'), 'type' => 'password', 'value' => '' );

        return $fields;
    }

    /**
     * isReady
     *
     * Returns whether the catalog is ready for use.
     */
    public function isReady()
    {
        return $this->seafile->ready();
    }

    /**
     * create_type
     *
     * This creates a new catalog type entry for a catalog
     */
    public static function create_type($catalog_id, $data)
    {
        $server_uri     = rtrim(trim($data['server_uri']), '/');
        $library_name   = trim($data['library_name']);
        $api_call_delay = trim($data['api_call_delay']);
        $username       = trim($data['username']);
        $password       = trim($data['password']);

        if (!strlen($server_uri)) {
            AmpError::add('general', T_('Error: Seafile Server URL is required.'));

            return false;
        }

        if (!strlen($library_name)) {
            AmpError::add('general', T_('Error: Seafile Server Library Name is required.'));

            return false;
        }

        if (!strlen($username)) {
            AmpError::add('general', T_('Error: Seafile Username is required.'));

            return false;
        }

        if (!strlen($password)) {
            AmpError::add('general', T_('Error: Seafile Password is required.'));

            return false;
        }

        if (!is_numeric($api_call_delay)) {
            AmpError::add('general', T_('Error: API Call Delay must have a numeric value.'));

            return false;
        }

        try {
            $api_key = SeafileAdapter::request_api_key($server_uri, $username, $password);

            debug_event('seafile_catalog', 'Retrieved API token for user ' . $username . '.', 1);
        } catch (Exception $e) {
            AmpError::add('general', sprintf(T_('Error while authenticating against Seafile API: %s', $e->getMessage())));
            debug_event('seafile_catalog', 'Exception while Authenticating: ' . $e->getMessage(), 2);
        }

        if ($api_key == null) {
            return false;
        }

        $sql = "INSERT INTO `catalog_seafile` (`server_uri`, `api_key`, `library_name`, `api_call_delay`, `catalog_id`) VALUES (?, ?, ?, ?, ?)";
        Dba::write($sql, array($server_uri, $api_key, $library_name, intval($api_call_delay), $catalog_id));

        return true;
    }

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

            $this->seafile = new SeafileAdapter($info['server_uri'], $info['library_name'], $info['api_call_delay'], $info['api_key']);
        }
    }

    public function get_rel_path($file_path)
    {
        $arr = $this->seafile->from_virtual_path($file_path);

        return $arr['path'] . "/" . $arr['filename'];
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

        if (!defined('SSE_OUTPUT')) {
            UI::show_box_top(T_('Running Seafile Remote Update') . '. . .');
        }

        $success = false;

        if ($this->seafile->prepare()) {
            $count = $this->seafile->for_all_files(function ($file) {
                if ($file->size == 0) {
                    debug_event('read', $file->name . " ignored, 0 bytes", 5);

                    return 0;
                }

                $is_audio_file = Catalog::is_audio_file($file->name);
                $is_video_file = Catalog::is_video_file($file->name);

                if ($is_audio_file && count($this->get_gather_types('music')) > 0) {
                    if ($this->insert_song($file)) {
                        return 1;
                    }
                } elseif ($is_video_file && count($this->get_gather_types('video')) > 0) {
                    // TODO $this->insert_video()
                } elseif (!$is_audio_file && !$is_video_file) {
                    debug_event('read', $file->name . " ignored, unknown media file type", 5);
                } else {
                    debug_event('read', $file->name . " ignored, bad media type for this catalog.", 5);
                }

                return 0;
            });

            UI::update_text('', sprintf(T_('Catalog Update Finished.  Total Media: [%s]'), $count));

            if ($count <= 0) {
                AmpError::add('general', T_('No media updated, do you respect the patterns?'));
            } else {
                $success = true;
            }
        }

        if (!defined('SSE_OUTPUT')) {
            UI::show_box_bottom();
        }

        $this->update_last_add();

        return $success;
    }

    /**
     * _insert_local_song
     *
     * Insert a song that isn't already in the database.
     */
    private function insert_song($file)
    {
        if ($this->check_remote_song($this->seafile->to_virtual_path($file))) {
            debug_event('seafile_catalog', 'Skipping existing song ' . $file->name, 5);
            UI::update_text('', sprintf(T_('Skipping existing song "%s"'), $file->name));
        } else {
            debug_event('seafile_catalog', 'Adding song ' . $file->name, 5);
            try {
                $results = $this->download_metadata($file);
                UI::update_text('', sprintf(T_('Adding song "%s"'), $file->name));
                $added =  Song::insert($results);

                if ($added) {
                    $this->count++;
                }

                return $added;
            } catch (Exception $e) {
                debug_event('seafile_add', sprintf('Could not add song "%s": %s', $file->name, $e->getMessage()), 1);
                UI::update_text('', sprintf(T_('Could not add song "%s"'), $file->name));
            }
        }

        return false;
    }

    private function download_metadata($file, $sort_pattern = '', $rename_pattern = '', $gather_types = null)
    {
        // Check for patterns
        if (!$sort_pattern or !$rename_pattern) {
            $sort_pattern   = $this->sort_pattern;
            $rename_pattern = $this->rename_pattern;
        }

        debug_event('seafile_catalog', 'Downloading partial song ' . $file->name, 5);

        $tempfilename = $this->seafile->download($file, true);

        if ($gather_types === null) {
            $gather_types = $this->get_gather_types('music');
        }

        $vainfo = new vainfo($tempfilename, $gather_types, '', '', '', $sort_pattern, $rename_pattern, true);
        $vainfo->forceSize($file->size);
        $vainfo->get_info();

        $key = vainfo::get_tag_type($vainfo->tags);

        // maybe fix stat-ing-nonexistent-file bug?
        $vainfo->tags['general']['size'] = intval($file->size);

        $results = vainfo::clean_tag_info($vainfo->tags, $key, $file->name);

        // Set the remote path
        $results['catalog'] = $this->id;

        $results['file'] = $this->seafile->to_virtual_path($file);

        return $results;
    }

    public function verify_catalog_proc()
    {
        $results = array('total' => 0, 'updated' => 0);

        set_time_limit(0);

        if ($this->seafile->prepare()) {
            $sql        = 'SELECT `id`, `file`, `title` FROM `song` WHERE `catalog` = ?';
            $db_results = Dba::read($sql, array($this->id));
            while ($row = Dba::fetch_assoc($db_results)) {
                $results['total']++;
                debug_event('seafile-verify', 'Starting work on ' . $row['file'] . '(' . $row['id'] . ')', 5, 'ampache-catalog');
                $fileinfo = $this->seafile->from_virtual_path($row['file']);

                $file = $this->seafile->get_file($fileinfo['path'], $fileinfo['filename']);

                $metadata = null;

                if ($file !== null) {
                    $metadata = $this->download_metadata($file);
                }

                if ($metadata !== null) {
                    debug_event('seafile-verify', 'updating song', 5, 'ampache-catalog');
                    $song = new Song($row['id']);
                    $info = self::update_song_from_tags($metadata, $song);
                    if ($info['change']) {
                        UI::update_text('', sprintf(T_('Updated song "%s"'), $row['title']));
                        $results['updated']++;
                    } else {
                        UI::update_text('', sprintf(T_('Song up to date: "%s"'), $row['title']));
                    }
                } else {
                    debug_event('seafile-verify', 'removing song', 5, 'ampache-catalog');
                    UI::update_text('', sprintf(T_('Removing song "%s"'), $row['title']));
                    //$dead++;
                    Dba::write('DELETE FROM `song` WHERE `id` = ?', array($row['id']));
                }
            }

            $this->update_last_update();
        }

        return $results;
    }

    public function get_media_tags($media, $gather_types, $sort_pattern, $rename_pattern)
    {
        if ($this->seafile->prepare()) {
            $fileinfo = $this->seafile->from_virtual_path($media->file);

            $file = $this->seafile->get_file($fileinfo['path'], $fileinfo['filename']);

            if ($file !== null) {
                return $this->download_metadata($file, $sort_pattern, $rename_pattern, $gather_types);
            }
        }

        return null;
    }

    /**
     * clean_catalog_proc
     *
     * Removes songs that no longer exist.
     */
    public function clean_catalog_proc()
    {
        $dead = 0;

        set_time_limit(0);

        if ($this->seafile->prepare()) {
            $sql        = 'SELECT `id`, `file` FROM `song` WHERE `catalog` = ?';
            $db_results = Dba::read($sql, array($this->id));
            while ($row = Dba::fetch_assoc($db_results)) {
                debug_event('seafile-clean', 'Starting work on ' . $row['file'] . '(' . $row['id'] . ')', 5);
                $file     = $this->seafile->from_virtual_path($row['file']);

                try {
                    $exists = $this->seafile->get_file($file['path'], $file['filename']) !== null;
                } catch (Exception $e) {
                    UI::update_text('', sprintf(T_('Error checking song "%s": %s'), $file['filename'], $e->getMessage()));
                    debug_event('seafile-clean', 'Exception: ' . $e->getMessage(), 2);

                    continue;
                }

                if ($exists) {
                    debug_event('seafile-clean', 'keeping song', 5);
                    UI::update_text('', sprintf(T_('Keeping song "%s"'), $file['filename']));
                } else {
                    UI::update_text('', sprintf(T_('Removing song "%s"'), $file['filename']));
                    debug_event('seafile-clean', 'removing song', 5);
                    $dead++;
                    Dba::write('DELETE FROM `song` WHERE `id` = ?', array($row['id']));
                }
            }

            $this->update_last_clean();
        }

        return $dead;
    }

    /**
     * check_remote_song
     *
     * checks to see if a remote song exists in the database or not
     * if it find a song it returns the UID
     */
    public function check_remote_song($file)
    {
        $sql        = 'SELECT `id` FROM `song` WHERE `file` = ?';
        $db_results = Dba::read($sql, array($file));

        if ($results = Dba::fetch_assoc($db_results)) {
            return $results['id'];
        }

        return false;
    }


    /**
     * format
     *
     * This makes the object human-readable.
     */
    public function format()
    {
        parent::format();
        
        if ($this->seafile != null) {
            $this->f_info      = $this->seafile->get_format_string();
            $this->f_full_info = $this->seafile->get_format_string();
        } else {
            $this->f_info      = "Seafile Catalog";
            $this->f_full_info = "Seafile Catalog";
        }
    }

    public function prepare_media($media)
    {
        if ($this->seafile->prepare()) {
            set_time_limit(0);

            $fileinfo = $this->seafile->from_virtual_path($media->file);

            $file = $this->seafile->get_file($fileinfo['path'], $fileinfo['filename']);

            $tempfile = $this->seafile->download($file);

            $media->file   = $tempfile;
            $media->f_file = $fileinfo['filename'];

            // in case this didn't get set for some reason
            if ($media->size == 0) {
                $media->size = Core::get_filesize($tempfile);
            }
        }

        return $media;
    }
}
