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
 * SoundCloud Catalog Class
 *
 * This class handles all actual work in regards to SoundCloud.
 *
 */
class Catalog_soundcloud extends Catalog
{
    private $version        = '000001';
    private $type           = 'soundcloud';
    private $description    = 'SoundCloud Remote Catalog';

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
        $help = "<ul><li>Go to http://soundcloud.com/you/apps/new</li>" .
            "<li>Give a name to your application and click Register</li>" .
            "<li>Add the following OAuth redirect URIs: <i>" . $this->getRedirectUri() . "</i></li>" .
            "<li>Copy your Client ID and Secret here, and Save the app</li></ul>";

        return $help;
    } // get_create_help

    /**
     * is_installed
     * This returns true or false if remote catalog is installed
     */
    public function is_installed()
    {
        $sql        = "SHOW TABLES LIKE 'catalog_soundcloud'";
        $db_results = Dba::query($sql);

        return (Dba::num_rows($db_results) > 0);
    } // is_installed

    /**
     * install
     * This function installs the remote catalog
     */
    public function install()
    {
        $sql = "CREATE TABLE `catalog_soundcloud` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , " .
            "`userid` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
            "`secret` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
            "`authtoken` VARCHAR( 255 ) COLLATE utf8_unicode_ci NULL , " .
            "`catalog_id` INT( 11 ) NOT NULL" .
            ") ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $db_results = Dba::query($sql);

        return true;
    } // install

    public function catalog_fields()
    {
        $fields['userid']      = array('description' => T_('User ID'),'type' => 'text');
        $fields['secret']      = array('description' => T_('Secret'),'type' => 'password');

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

    public $userid;
    public $secret;
    public $authtoken;

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
                $this->$key = $value;
            }
        }

        if (!@include_once(AmpConfig::get('prefix') . '/lib/vendor/mptre/php-soundcloud/Services/Soundcloud.php')) {
            throw new Exception('Missing php-soundcloud dependency.');
        }
    }

    protected function getRedirectUri()
    {
        return AmpConfig::get('web_path') . "/show_get.php?param_name=code";
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
        $userid = $data['userid'];
        $secret = $data['secret'];

        if (!strlen($userid) or !strlen($secret)) {
            AmpError::add('general', T_('Error: UserID and Secret Required for SoundCloud Catalogs'));

            return false;
        }

        // Make sure this email isn't already in use by an existing catalog
        $sql        = 'SELECT `id` FROM `catalog_soundcloud` WHERE `userid` = ?';
        $db_results = Dba::read($sql, array($userid));

        if (Dba::num_rows($db_results)) {
            debug_event('catalog', 'Cannot add catalog with duplicate user id ' . $userid, 1);
            AmpError::add('general', sprintf(T_('Error: Catalog with %s already exists'), $userid));

            return false;
        }

        $sql = 'INSERT INTO `catalog_soundcloud` (`userid`, `secret`, `catalog_id`) VALUES (?, ?, ?)';
        Dba::write($sql, array($userid, $secret, $catalog_id));

        return true;
    }

    protected function showAuthToken()
    {
        $api     = new Services_Soundcloud($this->userid, $this->secret, $this->getRedirectUri());
        $authurl = $api->getAuthorizeUrl(array('scope' => 'non-expiring'));
        echo "<br />Go to <strong><a href='" . $authurl . "' target='_blank'>" . $authurl . "</a></strong> to generate the authorization code, then enter it bellow.<br />";
        echo "<form action='" . get_current_path() . "' method='post' enctype='multipart/form-data'>";
        if ($_REQUEST['action']) {
            echo "<input type='hidden' name='action' value='" . scrub_in($_REQUEST['action']) . "' />";
            echo "<input type='hidden' name='catalogs[]' value='" . $this->id . "' />";
        }
        echo "<input type='hidden' name='perform_ready' value='true' />";
        echo "<input type='text' name='authcode' />";
        echo "<input type='submit' value='Ok' />";
        echo "</form>";
        echo "<br />";
    }

    protected function completeAuthToken()
    {
        $api             = new Services_Soundcloud($this->userid, $this->secret, $this->getRedirectUri());
        $token           = $api->accessToken($this->authcode);
        $this->authtoken = $token['access_token'];

        debug_event('soundcloud_catalog', 'SoundCloud authentication token generated for userid ' . $this->userid . '.', 1);

        $sql = 'UPDATE `catalog_soundcloud` SET `authtoken` = ? WHERE `catalog_id` = ?';
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
            UI::show_box_top(T_('Running SoundCloud Remote Update') . '. . .');
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

        $api = new Services_Soundcloud($this->userid, $this->secret);
        $api->setAccessToken($this->authtoken);

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
        $songsadded = 0;
        try {
            $api = $this->createClient();
            if ($api != null) {
                // Get all liked songs
                $songs = json_decode($api->get('me/favorites'));
                if ($songs) {
                    foreach ($songs as $song) {
                        if ($song->streamable == true && $song->kind == 'track') {
                            $data            = array();
                            $data['artist']  = $song->user->username;
                            $data['album']   = $data['artist'];
                            $data['title']   = $song->title;
                            $data['year']    = $song->release_year;
                            $data['mode']    = 'vbr';
                            $data['genre']   = explode(' ', $song->genre);
                            $data['comment'] = $song->description;
                            $data['file']    = $song->stream_url . '.mp3'; // Always stream as mp3, if evolve => $song->original_format;
                            $data['size']    = $song->original_content_size;
                            $data['time']    = intval($song->duration / 1000);
                            if ($this->check_remote_song($data)) {
                                debug_event('soundcloud_catalog', 'Skipping existing song ' . $data['file'], 5);
                            } else {
                                $data['catalog'] = $this->id;
                                debug_event('soundcloud_catalog', 'Adding song ' . $data['file'], 5, 'ampache-catalog');
                                if (!Song::insert($data)) {
                                    debug_event('soundcloud_catalog', 'Insert failed for ' . $data['file'], 1);
                                    AmpError::add('general', T_('Unable to Insert Song - %s'), $data['file']);
                                } else {
                                    $songsadded++;
                                }
                            }
                        }
                    }

                    UI::update_text('', T_('Completed updating SoundCloud catalog(s).') . " " . $songsadded . " " . T_('Songs added.'));

                    // Update the last update value
                    $this->update_last_update();
                } else {
                    AmpError::add('general', T_('API Error: cannot get song list.'));
                    debug_event('soundcloud_catalog', 'API Error: cannot get song list.', 1);
                }
            } else {
                AmpError::add('general', T_('API Error: cannot connect to SoundCloud.'));
                debug_event('soundcloud_catalog', 'API Error: cannot connect to SoundCloud.', 1);
            }
        } catch (Exception $ex) {
            AmpError::add('general', T_('SoundCloud exception: ') . $ex->getMessage());
            debug_event('soundcloud_catalog', 'SoundCloud exception: ' . $ex->getMessage(), 1);
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
        $dead = 0;

        try {
            $api = $this->createClient();
            if ($api != null) {
                $sql        = 'SELECT `id`, `file` FROM `song` WHERE `catalog` = ?';
                $db_results = Dba::read($sql, array($this->id));
                while ($row = Dba::fetch_assoc($db_results)) {
                    debug_event('soundcloud-clean', 'Starting work on ' . $row['file'] . '(' . $row['id'] . ')', 5, 'ampache-catalog');
                    $remove = false;
                    try {
                        $track = $this->url_to_track($row['file']);
                        $song  = json_decode($api->get('tracks/' . $track));
                        if ($song->user_favorite != true) {
                            $remove = true;
                        }
                    } catch (Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
                        if ($e->getHttpCode() == '404') {
                            $remove = true;
                        } else {
                            debug_event('soundcloud-clean', 'Clean error: ' . $e->getMessage(), 5, 'ampache-catalog');
                        }
                    } catch (Exception $e) {
                        debug_event('soundcloud-clean', 'Clean error: ' . $e->getMessage(), 5, 'ampache-catalog');
                    }

                    if (!$remove) {
                        debug_event('soundcloud-clean', 'keeping song', 5, 'ampache-catalog');
                    } else {
                        debug_event('soundcloud-clean', 'removing song', 5, 'ampache-catalog');
                        $dead++;
                        Dba::write('DELETE FROM `song` WHERE `id` = ?', array($row['id']));
                    }
                }
            } else {
                echo "<p>" . T_('API Error: cannot connect to SoundCloud.') . "</p><hr />\n";
                flush();
            }
        } catch (Exception $ex) {
            echo "<p>" . T_('SoundCloud exception: ') . $ex->getMessage() . "</p><hr />\n";
        }

        return $dead;
    }

    public function url_to_track($url)
    {
        $track = 0;
        preg_match('/tracks\/([0-9]*)\/stream/', $url, $matches);
        if (count($matches)) {
            $track = $matches[1];
        }

        return $track;
    }

    public function get_rel_path($file_path)
    {
        return $file_path;
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

        $sql        = 'SELECT `id` FROM `song` WHERE `file` = ?';
        $db_results = Dba::read($sql, array($url));

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
        $this->f_info      = $this->userid;
        $this->f_full_info = $this->userid;
    }

    public function prepare_media($media)
    {
        try {
            $api = $this->createClient();
            if ($api != null) {
                $track = $this->url_to_track($media->file);
                debug_event('play', 'Starting stream - ' . $track, 5);

                $headers = $api->stream($track);
                if (isset($headers['Location'])) {
                    debug_event('play', 'Started remote stream - ' . $headers['Location'], 5);
                    header('Location: ' . $headers['Location']);
                } else {
                    debug_event('play', 'Cannot get remote stream for song ' . $media->file, 5);
                }
            } else {
                debug_event('play', 'API Error: cannot connect to SoundCloud.', 1);
                echo "<p>" . T_('API Error: cannot connect to SoundCloud.') . "</p><hr />\n";
                flush();
            }
        } catch (Exception $ex) {
            debug_event('play', 'SoundCloud exception: ' . $ex->getMessage(), 1);
            echo "<p>" . T_('SoundCloud exception: ') . $ex->getMessage() . "</p><hr />\n";
        }

        return null;
    }
} // end of catalog class
