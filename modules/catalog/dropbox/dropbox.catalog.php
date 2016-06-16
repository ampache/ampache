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
 * Dropbox Catalog Class
 *
 * This class handles all actual work in regards to remote Dropbox catalogs.
 *
 */
class Catalog_dropbox extends Catalog
{
    private $version        = '000001';
    private $type           = 'dropbox';
    private $description    = 'Dropbox Remote Catalog';

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
        $help = "<ul><li>" . T_("Go to https://www.dropbox.com/developers/apps/create") . "</li>" .
            "<li>" . T_("Select 'Dropbox API app'") . "</li>" .
            "<li>" . T_("Select 'Files and datastores'") . "</li>" .
            "<li>" . T_("Select 'No' at 'Can your app be limited to its own, private folder?'") . "</li>" .
            "<li>" . T_("Select 'Specific file types' at 'What type of files does your app need access to?'") . "</li>" .
            "<li>" . T_("Check Videos and Audio files") . "</li>" .
            "<li>" . T_("Give a name to your application and create it") . "</li>" .
            //"<li>Add the following OAuth redirect URIs: <i>" . AmpConfig::get('web_path') . "/admin/catalog.php</i></li>" .
            "<li>" . T_("Copy your App key and App secret in the following fields.") . "</li>" .
            "<li>&rArr;&nbsp;" . T_("After preparing the catalog with pressing the 'Add catalog' button,<br /> you have to 'Make it ready' on the catalog table.") . "</li></ul>";
        return $help;
    } // get_create_help

    /**
     * is_installed
     * This returns true or false if remote catalog is installed
     */
    public function is_installed()
    {
        $sql        = "SHOW TABLES LIKE 'catalog_dropbox'";
        $db_results = Dba::query($sql);

        return (Dba::num_rows($db_results) > 0);
    } // is_installed

    /**
     * install
     * This function installs the remote catalog
     */
    public function install()
    {
        $sql = "CREATE TABLE `catalog_dropbox` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , " .
            "`apikey` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
            "`secret` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
            "`path` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
            "`authtoken` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
            "`getchunk` TINYINT(1) NOT NULL, " .
            "`catalog_id` INT( 11 ) NOT NULL" .
            ") ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $db_results = Dba::query($sql);

        return true;
    } // install

    public function catalog_fields()
    {
        $fields['apikey']        = array('description' => T_('API Key'), 'type'=>'textbox');
        $fields['secret']        = array('description' => T_('Secret'), 'type'=>'password');
        $fields['path']          = array('description' => T_('Path'), 'type'=>'textbox', 'value' => '/');
        $fields['getchunk']      = array('description' => T_('Get chunked files on analyze'), 'type'=>'checkbox', 'value' => true);

        return $fields;
    }

    public function isReady()
    {
        return (!empty($this->authtoken));
    }

    public function show_ready_process()
    {
        $this->showAuthToken();
    }

    public function perform_ready()
    {
        $this->authcode = $_REQUEST['authcode'];
        $this->completeAuthToken();
    }

    public $apikey;
    public $secret;
    public $path;
    public $authtoken;
    public $getchunk;

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
        $apikey   = trim($data['apikey']);
        $secret   = trim($data['secret']);
        $path     = $data['path'];
        $getchunk = $data['getchunk'];

        if (!strlen($apikey) or !strlen($secret)) {
            AmpError::add('general', T_('Error: API Key and Secret Required for Dropbox Catalogs'));
            return false;
        }

        $pathError = Dropbox\Path::findError($path);
        if ($pathError !== null) {
            AmpError::add('general', T_('Invalid <dropbox-path>: ' . $pathError));
            return false;
        }

        // Make sure this app isn't already in use by an existing catalog
        $sql        = 'SELECT `id` FROM `catalog_dropbox` WHERE `apikey` = ?';
        $db_results = Dba::read($sql, array($apikey));

        if (Dba::num_rows($db_results)) {
            debug_event('catalog', 'Cannot add catalog with duplicate key ' . $apikey, 1);
            AmpError::add('general', sprintf(T_('Error: Catalog with %s already exists'), $apikey));
            return false;
        }

        $sql = 'INSERT INTO `catalog_dropbox` (`apikey`, `secret`, `path`, `getchunk`, `catalog_id`) VALUES (?, ?, ?, ?, ?)';
        Dba::write($sql, array($apikey, $secret, $path, ($getchunk ? 1 : 0), $catalog_id));
        return true;
    }

    protected function getWebAuth()
    {
        $appInfo = new Dropbox\AppInfo($this->apikey, $this->secret);
        $webAuth = new Dropbox\WebAuthNoRedirect($appInfo, "ampache", "en");

        return $webAuth;
    }

    protected function showAuthToken()
    {
        $webAuth = $this->getWebAuth();
        $authurl = $webAuth->start();
        printf('<br />' . T_('Go to %s to generate the authorization code, then enter it bellow.') . '<br />', '<strong><a href="' . $authurl . '"target="_blank">' . $authurl . '</a></strong>');
        echo "<form action='" . get_current_path() . "' method='post' enctype='multipart/form-data'>";
        if ($_REQUEST['action']) {
            echo "<input type='hidden' name='action' value='" . scrub_in($_REQUEST['action']) . "' />";
            echo "<input type='hidden' name='catalogs[]' value='" . $this->id . "' />";
        }
        echo "<input type='hidden' name='perform_ready' value='true' />";
        echo "<input type='text' name='authcode' />&nbsp;";
        echo "<input type='submit' value='Ok' />";
        echo "</form>";
        echo "<br />";
    }

    protected function completeAuthToken()
    {
        $webAuth                    = $this->getWebAuth();
        list($accessToken, $userId) = $webAuth->finish($this->authcode);
        debug_event('dropbox_catalog', 'Dropbox authentication token generated for user ' . $userId . '.', 1);
        $this->authtoken = $accessToken;

        $sql = 'UPDATE `catalog_dropbox` SET `authtoken` = ? WHERE `catalog_id` = ?';
        Dba::write($sql, array($this->authtoken, $this->catalog_id));
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

        if ($options != null) {
            $this->authcode = $options['authcode'];
        }

        if (!defined('SSE_OUTPUT')) {
            UI::show_box_top(T_('Running Dropbox Remote Update') . '. . .');
        }
        $this->update_remote_catalog();
        if (!defined('SSE_OUTPUT')) {
            UI::show_box_bottom();
        }

        return true;
    } // add_to_catalog

    public function createClient()
    {
        if ($this->authcode) {
            $this->completeAuthToken();
        }
        if (!$this->authtoken) {
            $this->showAuthToken();
            return null;
        }

        try {
            return new Dropbox\Client($this->authtoken, "ampache", "en");
        } catch (Dropbox\Exception $e) {
            debug_event('dropbox_catalog', 'Dropbox authentication error: ' . $ex->getMessage(), 1);
            $this->showAuthToken();
            return null;
        }
    }

    /**
     * update_remote_catalog
     *
     * Pulls the data from a remote catalog and adds any missing songs to the
     * database.
     */
    public function update_remote_catalog()
    {
        $client = $this->createClient();
        if ($client != null) {
            $this->count = 0;
            $this->add_files($client, $this->path);

            UI::update_text('', sprintf(T_('Catalog Update Finished.  Total Media: [%s]'), $this->count));
            if ($this->count == 0) {
                AmpError::add('general', T_('No media updated, do you respect the patterns?'));
            }
        } else {
            AmpError::add('general', T_('API Error: cannot connect to Dropbox.'));
        }

        return true;
    }

    /**
     * add_files
     *
     * Recurses through directories and pulls out all media files
     */
    public function add_files($client, $path)
    {
        $metadata = $client->getMetadataWithChildren($path);
        if ($metadata != null) {
            // If it's a folder, remove the 'contents' list from $metadata; print that stuff out after.
            $children = null;
            if ($metadata['is_dir']) {
                $children = $metadata['contents'];
                if ($children !== null && count($children) > 0) {
                    foreach ($children as $child) {
                        if ($child['is_dir']) {
                            $this->add_files($client, $child['path']);
                        } else {
                            $this->add_file($client, $child);
                        }
                    }
                }
            } else {
                $this->add_file($client, $metadata);
            }
        } else {
            AmpError::add('general', T_('API Error: Cannot access file/folder at ' . $this->path . '.'));
        }
    }

    public function add_file($client, $data)
    {
        $file     = $data['path'];
        $filesize = $data['bytes'];
        if ($filesize > 0) {
            $is_audio_file = Catalog::is_audio_file($file);

            if ($is_audio_file) {
                if (count($this->get_gather_types('music')) > 0) {
                    $this->insert_song($client, $file, $filesize);
                } else {
                    debug_event('read', $data['path'] . " ignored, bad media type for this catalog.", 5);
                }
            } else {
                debug_event('read', $data['path'] . " ignored, unknown media file type", 5);
            }
        } else {
            debug_event('read', $data['path'] . " ignored, 0 bytes", 5);
        }
    }

    /**
     * _insert_local_song
     *
     * Insert a song that isn't already in the database.
     */
    private function insert_song($client, $file, $filesize)
    {
        if ($this->check_remote_song($this->get_virtual_path($file))) {
            debug_event('dropbox_catalog', 'Skipping existing song ' . $file, 5);
        } else {
            $origin  = $file;
            $islocal = false;
            $fpchunk = 0;
            // Get temporary chunked file from Dropbox to (hope) read metadata
            if ($this->getchunk) {
                $fpchunk  = tmpfile();
                $metadata = $client->getFile($file, $fpchunk, null, 40960);
                if ($metadata == null) {
                    debug_event('dropbox_catalog', 'Cannot get Dropbox file: ' . $file, 5);
                }
                $streammeta = stream_get_meta_data($fpchunk);
                $file       = $streammeta['uri'];
                $islocal    = true;
            }

            $vainfo = new vainfo($file, $this->get_gather_types('music'), '', '', '', $this->sort_pattern, $this->rename_pattern, $islocal);
            $vainfo->forceSize($filesize);
            $vainfo->get_info();

            $key     = vainfo::get_tag_type($vainfo->tags);
            $results = vainfo::clean_tag_info($vainfo->tags, $key, $file);

            // Remove temp file
            if ($fpchunk) {
                fclose($fpchunk);
            }

            // Set the remote path
            $results['file']    = $origin;
            $results['catalog'] = $this->id;

            if (!empty($results['artist']) && !empty($results['album'])) {
                $results['file'] = $this->get_virtual_path($results['file']);

                $this->count++;
                return Song::insert($results);
            } else {
                debug_event('results', $results['file'] . " ignored because it is an orphan songs. Please check your catalog patterns.", 5);
            }
        }

        return false;
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
        $dead = 0;

        $client = $this->createClient();
        if ($client != null) {
            $sql        = 'SELECT `id`, `file` FROM `song` WHERE `catalog` = ?';
            $db_results = Dba::read($sql, array($this->id));
            while ($row = Dba::fetch_assoc($db_results)) {
                debug_event('dropbox-clean', 'Starting work on ' . $row['file'] . '(' . $row['id'] . ')', 5, 'ampache-catalog');
                $file     = $this->get_rel_path($row['file']);
                $metadata = $client->getMetadata($file);
                if ($metadata) {
                    debug_event('dropbox-clean', 'keeping song', 5, 'ampache-catalog');
                } else {
                    debug_event('dropbox-clean', 'removing song', 5, 'ampache-catalog');
                    $dead++;
                    Dba::write('DELETE FROM `song` WHERE `id` = ?', array($row['id']));
                }
            }
        } else {
            AmpError::add('general', T_('API Error: cannot connect to Dropbox.'));
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

    public function get_virtual_path($file)
    {
        return $this->apikey . '|' . $file;
    }

    public function get_rel_path($file_path)
    {
        $p = strpos($file_path, "|");
        if ($p !== false) {
            $p++;
        }
        return substr($file_path, $p);
    }

    /**
     * format
     *
     * This makes the object human-readable.
     */
    public function format()
    {
        parent::format();
        $this->f_info      = $this->apikey;
        $this->f_full_info = $this->apikey;
    }

    public function prepare_media($media)
    {
        $client = $this->createClient();
        if ($client != null) {
            set_time_limit(0);

            // Generate browser class for sending headers
            $browser    = new Horde_Browser();
            $media_name = $media->f_artist_full . " - " . $media->title . "." . $media->type;
            $browser->downloadHeaders($media_name, $media->mime, false, $media->size);
            $file = $this->get_rel_path($media->file);

            $output   = fopen('php://output', 'w');
            $metadata = $client->getFile($file, $output);
            if ($metadata == null) {
                debug_event('play', 'File not found on Dropbox: ' . $file, 5);
            }
            fclose($output);
        }

        return null;
    }
} // end of catalog class
