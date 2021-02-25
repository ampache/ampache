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
 * AmpacheHttpq Class
 *
 * This is the class for the httpQ Localplay method to remotely control
 * a WinAmp Instance
 *
 */

class AmpacheHttpq extends localplay_controller
{
    /* Variables */
    private $version        = '000002';
    private $description    = "Controls an httpQ instance, requires Ampache's httpQ version";

    /* Constructed variables */
    private $_httpq;

    /**
     * Constructor
     * This returns the array map for the Localplay object
     * REQUIRED for Localplay
     */
    public function __construct()
    {
        /* Do a Require Once On the needed Libraries */
        require_once AmpConfig::get('prefix') . '/modules/localplay/httpq/httpqplayer.class.php';
    } // Constructor

    /**
     * get_description
     * This returns the description of this Localplay method
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
     * is_installed
     * This returns true or false if this controller is installed
     */
    public function is_installed()
    {
        $sql        = "SHOW TABLES LIKE 'localplay_httpq'";
        $db_results = Dba::read($sql);

        return (Dba::num_rows($db_results) > 0);
    } // is_installed

    /**
     * install
     * This function installs the controller
     */
    public function install()
    {
        $collation = (AmpConfig::get('database_collation', 'utf8_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE `localplay_httpq` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , " .
            "`name` VARCHAR( 128 ) COLLATE $collation NOT NULL , " .
            "`owner` INT( 11 ) NOT NULL, " .
            "`host` VARCHAR( 255 ) COLLATE $collation NOT NULL , " .
            "`port` INT( 11 ) UNSIGNED NOT NULL , " .
            "`password` VARCHAR( 255 ) COLLATE $collation NOT NULL , " .
            "`access` SMALLINT( 4 ) UNSIGNED NOT NULL DEFAULT '0'" .
            ") ENGINE = $engine DEFAULT CHARSET=$charset COLLATE=$collation";
        Dba::query($sql);

        // Add an internal preference for the users current active instance
        Preference::insert('httpq_active', T_('HTTPQ Active Instance'), 0, 25, 'integer', 'internal', 'httpq');

        return true;
    } // install

    /**
     * uninstall
     * This removes the Localplay controller
     */
    public function uninstall()
    {
        $sql        = "DROP TABLE `localplay_httpq`";
        $db_results = Dba::write($sql);

        // Remove the pref we added for this
        Preference::delete('httpq_active');

        return true;
    } // uninstall

    /**
     * add_instance
     * This takes keyed data and inserts a new httpQ instance
     * @param array $data
     * @return PDOStatement|boolean
     */
    public function add_instance($data)
    {
        $name     = Dba::escape($data['name']);
        $host     = Dba::escape($data['host']);
        $port     = Dba::escape($data['port']);
        $password = Dba::escape($data['password']);
        $user_id  = Dba::escape(Core::get_global('user')->id);

        $sql = "INSERT INTO `localplay_httpq` (`name`, `host`, `port`, `password`, `owner`) " .
            "VALUES ('$name', '$host', '$port', '$password', '$user_id')";

        return Dba::write($sql);
    } // add_instance

    /**
     * delete_instance
     * This takes a UID and deletes the instance in question
     * @param $uid
     * @return boolean
     */
    public function delete_instance($uid)
    {
        $uid = Dba::escape($uid);

        $sql        = "DELETE FROM `localplay_httpq` WHERE `id`='$uid'";
        $db_results = Dba::write($sql);

        return true;
    } // delete_instance

    /**
     * get_instances
     * This returns a keyed array of the instance information with
     * [UID]=>[NAME]
     */
    public function get_instances()
    {
        $sql        = "SELECT * FROM `localplay_httpq` ORDER BY `name`";
        $db_results = Dba::read($sql);

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['id']] = $row['name'];
        }

        return $results;
    } // get_instances

    /**
     * update_instance
     * This takes an ID and an array of data and updates the instance specified
     * @param $uid
     * @param array $data
     * @return boolean
     */
    public function update_instance($uid, $data)
    {
        $uid     = Dba::escape($uid);
        $port    = Dba::escape($data['port']);
        $host    = Dba::escape($data['host']);
        $name    = Dba::escape($data['name']);
        $pass    = Dba::escape($data['password']);

        $sql        = "UPDATE `localplay_httpq` SET `host`='$host', `port`='$port', `name`='$name', `password`='$pass' WHERE `id`='$uid'";
        $db_results = Dba::write($sql);

        return true;
    } // update_instance

    /**
     * instance_fields
     * This returns a keyed array of [NAME]=>array([DESCRIPTION]=>VALUE,[TYPE]=>VALUE) for the
     * fields so that we can on-the-fly generate a form
     */
    public function instance_fields()
    {
        $fields['name']         = array('description' => T_('Instance Name'), 'type' => 'text');
        $fields['host']         = array('description' => T_('Hostname'), 'type' => 'text');
        $fields['port']         = array('description' => T_('Port'), 'type' => 'number');
        $fields['password']     = array('description' => T_('Password'), 'type' => 'password');

        return $fields;
    } // instance_fields

    /**
     * get_instance
     * This returns a single instance and all its variables
     * @param string $instance
     * @return array
     */
    public function get_instance($instance = '')
    {
        $instance   = is_numeric($instance) ? (int) $instance : (int) AmpConfig::get('httpq_active', 0);
        $sql        = ($instance > 1) ? "SELECT * FROM `localplay_httpq` WHERE `id` = ?" : "SELECT * FROM `localplay_httpq`";
        $db_results = Dba::query($sql, array($instance));

        return Dba::fetch_assoc($db_results);
    } // get_instance

    /**
     * set_active_instance
     * This sets the specified instance as the 'active' one
     * @param $uid
     * @param string $user_id
     * @return boolean
     */
    public function set_active_instance($uid, $user_id = '')
    {
        // Not an admin? bubkiss!
        if (!Core::get_global('user')->has_access('100')) {
            $user_id = Core::get_global('user')->id;
        }

        $user_id = $user_id ? $user_id : Core::get_global('user')->id;

        Preference::update('httpq_active', $user_id, $uid);
        AmpConfig::set('httpq_active', $uid, true);

        return true;
    } // set_active_instance

    /**
     * get_active_instance
     * This returns the UID of the current active instance
     * false if none are active
     */
    public function get_active_instance()
    {
    } // get_active_instance

    /**
     * add_url
     * This is the new hotness
     * @param Stream_URL $url
     * @return boolean
     */
    public function add_url(Stream_URL $url)
    {
        if ($this->_httpq->add($url->title, $url->url) === null) {
            debug_event('httpq.controller', 'add_url failed to add ' . $url->url, 1);

            return false;
        }

        return true;
    }

    /**
     * delete_track
     * This must take an ID (as returned by our get function)
     * and delete it from httpQ
     * @param integer $object_id
     * @return boolean
     */
    public function delete_track($object_id)
    {
        if ($this->_httpq->delete_pos($object_id) === null) {
            debug_event('httpq.controller', 'Unable to delete ' . $object_id . ' from httpQ', 1);

            return false;
        }

        return true;
    } // delete_track

    /**
     * clear_playlist
     */
    public function clear_playlist()
    {
        if ($this->_httpq->clear() === null) {
            return false;
        }

        // If the clear worked we should stop it!
        $this->stop();

        return true;
    } // clear_playlist

    /**
     * play
     * This just tells httpQ to start playing, it does not
     * take any arguments
     */
    public function play()
    {
        // A play when it's already playing causes a track restart,
        // so doublecheck its state
        if ($this->_httpq->state() == 'play') {
            return true;
        }

        if ($this->_httpq->play() === null) {
            return false;
        }

        return true;
    } // play

    /**
     * stop
     * This just tells httpQ to stop playing, it does not take
     * any arguments
     */
    public function stop()
    {
        if ($this->_httpq->stop() === null) {
            return false;
        }

        return true;
    } // stop

    /**
     * skip
     * This tells httpQ to skip to the specified song
     * @param $song
     * @return boolean
     */
    public function skip($song)
    {
        if ($this->_httpq->skip($song) === null) {
            return false;
        }

        return true;
    } // skip

    /**
     * This tells httpQ to increase the volume by WinAmps default amount
     */
    public function volume_up()
    {
        return $this->_httpq->volume_up();
    } // volume_up

    /**
     * This tells httpQ to decrease the volume by Winamp's default amount
     */
    public function volume_down()
    {
        return $this->_httpq->volume_down();
    } // volume_down

    /**
     * next
     * This just tells httpQ to skip to the next song
     */
    public function next()
    {
        if ($this->_httpq->next() === null) {
            return false;
        }

        return true;
    } // next

    /**
     * prev
     * This just tells httpQ to skip to the prev song
     */
    public function prev()
    {
        if ($this->_httpq->prev() === null) {
            return false;
        }

        return true;
    } // prev

    /**
     * pause
     * This tells httpQ to pause the current song
     */
    public function pause()
    {
        if ($this->_httpq->pause() === null) {
            return false;
        }

        return true;
    } // pause

    /**
     * volume
     * This tells httpQ to set the volume to the specified amount this
     * is 0-100
     * @param $volume
     * @return boolean
     */
    public function volume($volume)
    {
        return $this->_httpq->set_volume($volume);
    } // volume

    /**
     * repeat
     * This tells httpQ to set the repeating the playlist (i.e. loop) to
     * either on or off
     * @param $state
     * @return boolean
     */
    public function repeat($state)
    {
        if ($this->_httpq->repeat($state) === null) {
            return false;
        }

        return true;
    } // repeat

    /**
     * random
     * This tells httpQ to turn on or off the playing of songs from the
     * playlist in random order
     * @param $onoff
     * @return boolean
     */
    public function random($onoff)
    {
        if ($this->_httpq->random($onoff) === null) {
            return false;
        }

        return true;
    } // random

    /**
     * get
     * This functions returns an array containing information about
     * The songs that httpQ currently has in its playlist. This must be
     * done in a standardized fashion
     */
    public function get()
    {
        /* Get the Current Playlist */
        $list = $this->_httpq->get_tracks();

        if (!$list) {
            return array();
        }

        $songs   = explode("::", $list);
        $results = array();

        foreach ($songs as $key => $entry) {
            $data = array();

            /* Required Elements */
            $data['id']     = $key;
            $data['raw']    = $entry;

            $url_data = $this->parse_url($entry);
            switch ($url_data['primary_key']) {
                                case 'oid':
                                        $data['oid'] = $url_data['oid'];
                                        $song        = new Song($data['oid']);
                                        $song->format();
                                        $data['name']   = $song->f_title . ' - ' . $song->f_album . ' - ' . $song->f_artist;
                                        $data['link']   = $song->f_link;
                                break;
                                case 'demo_id':
                                        $democratic     = new Democratic($url_data['demo_id']);
                                        $data['name']   = T_('Democratic') . ' - ' . $democratic->name;
                                        $data['link']   = '';
                                break;
                case 'random':
                    $data['name'] = T_('Random') . ' - ' . scrub_out(ucfirst($url_data['type']));
                    $data['link'] = '';
                break;
                                default:
                                        /* If we don't know it, look up by filename */
                                        $filename = Dba::escape($entry['file']);
                                        $sql      = "SELECT `id`, 'song' AS `type` FROM `song` WHERE `file` LIKE '%$filename' " .
                                                "UNION ALL " .
                                                "SELECT `id`, 'live_stream' AS `type` FROM `live_stream` WHERE `url`='$filename' ";

                                        $db_results = Dba::read($sql);
                                        if ($row = Dba::fetch_assoc($db_results)) {
                                            $media = new $row['type']($row['id']);
                                            $media->format();
                                            switch ($row['type']) {
                                                        case 'song':
                                                                $data['name'] = $media->f_title . ' - ' . $media->f_album . ' - ' . $media->f_artist;
                                                                $data['link'] = $media->f_link;
                                                        break;
                                                        case 'live_stream':
                                                                $frequency    = $media->frequency ? '[' . $media->frequency . ']' : '';
                                                                $site_url     = $media->site_url ? '(' . $media->site_url . ')' : '';
                                                                $data['name'] = "$media->name $frequency $site_url";
                                                                $data['link'] = $media->site_url;
                                                        break;
                                                } // end switch on type
                                        } // end if results
                    else {
                        $data['name'] = basename($data['raw']);
                        $data['link'] = basename($data['raw']);
                    }

                                break;
                        } // end switch on primary key type

            $data['track']    = $key + 1;

            $results[] = $data;
        } // foreach playlist items

        return $results;
    } // get

    /**
     * status
     * This returns bool/int values for features, loop, repeat and any other features
     * That this Localplay method supports. required function
     * @return array
     */
    public function status()
    {
        /* Construct the Array */
        $array['state']  = $this->_httpq->state();
        $array['volume'] = $this->_httpq->get_volume();
        $array['repeat'] = $this->_httpq->get_repeat();
        $array['random'] = $this->_httpq->get_random();
        $array['track']  = $this->_httpq->get_now_playing();
        $url_data        = $this->parse_url($array['track']);

        if (isset($url_data['oid'])) {
            $song                  = new Song($url_data['oid']);
            $array['track_title']  = $song->title;
            $array['track_artist'] = $song->get_artist_name();
            $array['track_album']  = $song->get_album_name();
        } else {
            $array['track_title'] = basename($array['track']);
        }

        return $array;
    } // status

    /**
     * connect
     * This functions creates the connection to httpQ and returns
     * a boolean value for the status, to save time this handle
     * is stored in this class
     */
    public function connect()
    {
        $options      = self::get_instance();
        $this->_httpq = new HttpQPlayer($options['host'], $options['password'], $options['port']);

        // Test our connection by retriving the version
        if ($this->_httpq->version() !== null) {
            return true;
        }

        return false;
    } // connect
} // end httpq.controller
