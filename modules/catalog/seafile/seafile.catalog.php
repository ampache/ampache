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
 * Seafile Catalog Class
 *
 * This class handles all actual work in regards to remote Seafile catalogs.
 *
 */

require_once('SeafileAdapter.php');

/**
 * Class Catalog_Seafile
 */
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
        $help = "<ul><li>" . T_("Install a Seafile server as described in the documentation") . "</li><li>" .
                T_("Enter URL to server (e.g. 'https://seafile.example.com') and library name (e.g. 'Music').") . "</li><li>" .
                T_("API Call Delay is the delay inserted between repeated requests to Seafile (such as during an Add or Clean action) to accommodate Seafile's Rate Limiting.") . "<br/>" .
                T_("The default is tuned towards Seafile's default rate limit settings.") . "</li><li>" .
                T_("After creating the Catalog, you must 'Make it ready' on the Catalog table.") . "</li></ul>";

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
        $collation = (AmpConfig::get('database_collation', 'utf8_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE `" . self::$table_name . "` (" .
            "`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , " .
            "`server_uri` VARCHAR( 255 ) COLLATE $collation NOT NULL , " .
            "`api_key` VARCHAR( 100 ) COLLATE $collation NOT NULL , " .
            "`library_name` VARCHAR( 255 ) COLLATE $collation NOT NULL , " .
            "`api_call_delay` INT NOT NULL , " .
            "`catalog_id` INT( 11 ) NOT NULL" .
            ") ENGINE = $engine DEFAULT CHARSET=$charset COLLATE=$collation";
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
     * @param $catalog_id
     * @param array $data
     * @return boolean
     */
    public static function create_type($catalog_id, $data)
    {
        $server_uri     = rtrim(trim($data['server_uri']), '/');
        $library_name   = trim($data['library_name']);
        $api_call_delay = trim($data['api_call_delay']);
        $username       = trim($data['username']);
        $password       = trim($data['password']);

        if (!strlen($server_uri)) {
            AmpError::add('general', T_('Seafile server URL is required'));

            return false;
        }

        if (!strlen($library_name)) {
            AmpError::add('general', T_('Seafile server library name is required'));

            return false;
        }

        if (!strlen($username)) {
            AmpError::add('general', T_('Seafile username is required'));

            return false;
        }

        if (!strlen($password)) {
            AmpError::add('general', T_('Seafile password is required'));

            return false;
        }

        if (!is_numeric($api_call_delay)) {
            AmpError::add('general', T_('API call delay must have a numeric value'));

            return false;
        }

        try {
            $api_key = SeafileAdapter::request_api_key($server_uri, $username, $password);

            debug_event('seafile_catalog', 'Retrieved API token for user ' . $username . '.', 1);
        } catch (Exception $error) {
            /* HINT: exception error message */
            AmpError::add('general', sprintf(T_('There was a problem authenticating against the Seafile API: %s'), $error->getMessage()));
            debug_event('seafile_catalog', 'Exception while Authenticating: ' . $error->getMessage(), 2);
        }

        if ($api_key == null) {
            return false;
        }

        $sql = "INSERT INTO `catalog_seafile` (`server_uri`, `api_key`, `library_name`, `api_call_delay`, `catalog_id`) VALUES (?, ?, ?, ?, ?)";
        Dba::write($sql, array($server_uri, $api_key, $library_name, (int) ($api_call_delay), $catalog_id));

        return true;
    }

    /**
     * Constructor
     *
     * Catalog class constructor, pulls catalog information
     * @param integer $catalog_id
     */
    public function __construct($catalog_id = null)
    {
        if ($catalog_id) {
            $this->id = (int) $catalog_id;
            $info     = $this->get_info($catalog_id);

            $this->seafile = new SeafileAdapter($info['server_uri'], $info['library_name'], $info['api_call_delay'], $info['api_key']);
        }
    }

    /**
     * @param string $file_path
     * @return string
     */
    public function get_rel_path($file_path)
    {
        $arr = $this->seafile->from_virtual_path($file_path);

        return $arr['path'] . "/" . $arr['filename'];
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
            UI::show_box_top(T_('Running Seafile Remote Update'));
        }

        $success = false;

        if ($this->seafile->prepare()) {
            $count = $this->seafile->for_all_files(function ($file) {
                if ($file->size == 0) {
                    debug_event('seafile_catalog', 'read ' . $file->name . " ignored, 0 bytes", 5);

                    return 0;
                }

                $is_audio_file = Catalog::is_audio_file($file->name);
                $is_video_file = Catalog::is_video_file($file->name);

                if ($is_audio_file && count($this->get_gather_types('music')) > 0) {
                    if ($this->insert_song($file)) {
                        return 1;
                    }
                    //} elseif ($is_video_file && count($this->get_gather_types('video')) > 0) {
                //    // TODO $this->insert_video()
                } elseif (!$is_audio_file && !$is_video_file) {
                    debug_event('seafile_catalog', 'read ' . $file->name . " ignored, unknown media file type", 5);
                } else {
                    debug_event('seafile_catalog', 'read ' . $file->name . " ignored, bad media type for this catalog.", 5);
                }

                return 0;
            });

            UI::update_text(T_('Catalog Updated'),
                    /* HINT: count of songs updated */
                    sprintf(T_('Total Media: [%s]'), $count));

            if ($count < 1) {
                AmpError::add('general', T_('No media was updated, did you respect the patterns?'));
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
     * @param $file
     * @return boolean|int
     */
    private function insert_song($file)
    {
        if ($this->check_remote_song($this->seafile->to_virtual_path($file))) {
            debug_event('seafile_catalog', 'Skipping existing song ' . $file->name, 5);
            /* HINT: filename (File path) */
            UI::update_text('', sprintf(T_('Skipping existing song: %s'), $file->name));
        } else {
            debug_event('seafile_catalog', 'Adding song ' . $file->name, 5);
            try {
                $results = $this->download_metadata($file);
                /* HINT: filename (File path) */
                UI::update_text('', sprintf(T_('Adding a new song: %s'), $file->name));
                $added = Song::insert($results);

                if ($added) {
                    $this->count++;
                }

                return $added;
            } catch (Exception $error) {
                /* HINT: %1 filename (File path), %2 error message */
                debug_event('seafile_catalog', sprintf('Could not add song "%1$s": %2$s', $file->name, $error->getMessage()), 1);
                /* HINT: filename (File path) */
                UI::update_text('', sprintf(T_('Could not add song: %s'), $file->name));
            }
        }

        return false;
    }

    /**
     * @param $file
     * @param string $sort_pattern
     * @param string $rename_pattern
     * @param array $gather_types
     * @return array
     * @throws Exception
     */
    private function download_metadata($file, $sort_pattern = '', $rename_pattern = '', $gather_types = null)
    {
        // Check for patterns
        if (!$sort_pattern || !$rename_pattern) {
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
        $vainfo->tags['general']['size'] = (int) ($file->size);

        $results = vainfo::clean_tag_info($vainfo->tags, $key, $file->name);

        // Set the remote path
        $results['catalog'] = $this->id;

        $results['file'] = $this->seafile->to_virtual_path($file);

        return $results;
    }

    /**
     * @return array|mixed
     * @throws ReflectionException
     */
    public function verify_catalog_proc()
    {
        $results = array('total' => 0, 'updated' => 0);

        set_time_limit(0);

        if ($this->seafile->prepare()) {
            $sql        = 'SELECT `id`, `file`, `title` FROM `song` WHERE `catalog` = ?';
            $db_results = Dba::read($sql, array($this->id));
            while ($row = Dba::fetch_assoc($db_results)) {
                $results['total']++;
                debug_event('seafile_catalog', 'Verify starting work on ' . $row['file'] . '(' . $row['id'] . ')', 5, 'ampache-catalog');
                $fileinfo = $this->seafile->from_virtual_path($row['file']);

                $file = $this->seafile->get_file($fileinfo['path'], $fileinfo['filename']);

                $metadata = null;

                if ($file !== null) {
                    $metadata = $this->download_metadata($file);
                }

                if ($metadata !== null) {
                    debug_event('seafile_catalog', 'Verify updating song', 5, 'ampache-catalog');
                    $song = new Song($row['id']);
                    $info = ($song->id) ? self::update_song_from_tags($metadata, $song) : array();
                    if ($info['change']) {
                        UI::update_text('', sprintf(T_('Updated song: %s'), $row['title']));
                        $results['updated']++;
                    } else {
                        UI::update_text('', sprintf(T_('Song up to date: %s'), $row['title']));
                    }
                } else {
                    debug_event('seafile_catalog', 'Verify removing song', 5, 'ampache-catalog');
                    UI::update_text('', sprintf(T_('Removing song: %s'), $row['title']));
                    //$dead++;
                    Dba::write('DELETE FROM `song` WHERE `id` = ?', array($row['id']));
                }
            }

            $this->update_last_update();
        }

        return $results;
    }

    /**
     * @param media $media
     * @param array $gather_types
     * @param string $sort_pattern
     * @param string $rename_pattern
     * @return array|null
     * @throws Exception
     */
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
                debug_event('seafile_catalog', 'Clean starting work on ' . $row['file'] . '(' . $row['id'] . ')', 5);
                $file     = $this->seafile->from_virtual_path($row['file']);

                try {
                    $exists = $this->seafile->get_file($file['path'], $file['filename']) !== null;
                } catch (Exception $error) {
                    UI::update_text(T_("There Was a Problem"),
                            /* HINT: %1 filename (File path), %2 Error Message */
                            sprintf(T_('There was an error while checking this song "%1$s": %2$s'), $file['filename'], $error->getMessage()));
                    debug_event('seafile_catalog', 'Clean Exception: ' . $error->getMessage(), 2);

                    continue;
                }

                if ($exists) {
                    debug_event('seafile_catalog', 'Clean keeping song', 5);
                    /* HINT: filename (File path) */
                    UI::update_text('', sprintf(T_('Keeping song: %s'), $file['filename']));
                } else {
                    /* HINT: filename (File path) */
                    UI::update_text('', sprintf(T_('Removing song: %s'), $file['filename']));
                    debug_event('seafile_catalog', 'Clean removing song', 5);
                    $dead++;
                    Dba::write('DELETE FROM `song` WHERE `id` = ?', array($row['id']));
                }
            }

            $this->update_last_clean();
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
     * @param $file
     * @return boolean|mixed
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

    /**
     * @param Podcast_Episode|Song|Song_Preview|Video $media
     * @return media|Podcast_Episode|Song|Song_Preview|Video|null
     */
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
