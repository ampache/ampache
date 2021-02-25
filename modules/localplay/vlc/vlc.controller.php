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
 * AmpacheVlc Class
 *
 * This is the class for the VLC Localplay method to remote control
 * a VLC Instance
 *
 */
class AmpacheVlc extends localplay_controller
{
    /* Variables */
    private $version        = 'Beta 0.2';
    private $description    = 'Controls a VLC instance';


    /* Constructed variables */
    private $_vlc;

    /**
     * Constructor
     * This returns the array map for the Localplay object
     * REQUIRED for Localplay
     */
    public function __construct()
    {
        /* Do a Require Once On the needed Libraries */
        require_once AmpConfig::get('prefix') . '/modules/localplay/vlc/vlcplayer.class.php';
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
     * This returns true or false if VLC controller is installed
     */
    public function is_installed()
    {
        $sql        = "SHOW TABLES LIKE 'localplay_vlc'";
        $db_results = Dba::query($sql);

        return (Dba::num_rows($db_results) > 0);
    } // is_installed

    /**
     * install
     * This function installs the VLC Localplay controller
     */
    public function install()
    {
        $collation = (AmpConfig::get('database_collation', 'utf8_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE `localplay_vlc` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , " .
                "`name` VARCHAR( 128 ) COLLATE $collation NOT NULL , " .
                "`owner` INT( 11 ) NOT NULL, " .
                "`host` VARCHAR( 255 ) COLLATE $collation NOT NULL , " .
                "`port` INT( 11 ) UNSIGNED NOT NULL , " .
                "`password` VARCHAR( 255 ) COLLATE $collation NOT NULL , " .
                "`access` SMALLINT( 4 ) UNSIGNED NOT NULL DEFAULT '0'" .
                ") ENGINE = $engine DEFAULT CHARSET=$charset COLLATE=$collation";
        Dba::query($sql);

        // Add an internal preference for the users current active instance
        Preference::insert('vlc_active', T_('VLC Active Instance'), 0, 25, 'integer', 'internal', 'vlc');

        return true;
    } // install

    /**
     * uninstall
     * This removes the localplay controller
     */
    public function uninstall()
    {
        $sql = "DROP TABLE `localplay_vlc`";
        Dba::query($sql);

        // Remove the pref we added for this
        Preference::delete('vlc_active');

        return true;
    } // uninstall

    /**
     * add_instance
     * This takes key'd data and inserts a new VLC instance
     * @param array $data
     * @return PDOStatement|boolean
     */
    public function add_instance($data)
    {
        $sql        = "INSERT INTO `localplay_vlc` (`name`, `host`, `port`, `password`, `owner`) VALUES (?, ?, ?, ?, ?)";

        return Dba::query($sql, array($data['name'], $data['host'], $data['port'], $data['password'], Core::get_global('user')->id));
    } // add_instance

    /**
     * delete_instance
     * This takes a UID and deletes the instance in question
     * @param $uid
     * @return boolean
     */
    public function delete_instance($uid)
    {
        $sql = "DELETE FROM `localplay_vlc` WHERE `id` = ?";
        Dba::query($sql, array($uid));

        return true;
    } // delete_instance

    /**
     * get_instances
     * This returns a key'd array of the instance information with
     * [UID]=>[NAME]
     */
    public function get_instances()
    {
        $sql        = "SELECT * FROM `localplay_vlc` ORDER BY `name`";
        $db_results = Dba::query($sql);
        $results    = array();

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
        $sql = "UPDATE `localplay_vlc` SET `host` = ?, `port` = ?, `name` = ?, `password` = ? WHERE `id` = ?";
        Dba::query($sql, array($data['host'], $data['port'], $data['name'], $data['password'], $uid));

        return true;
    } // update_instance

    /**
     * instance_fields
     * This returns a key'd array of [NAME]=>array([DESCRIPTION]=>VALUE,[TYPE]=>VALUE) for the
     * fields so that we can on-the-fly generate a form
     */
    public function instance_fields()
    {
        $fields['name']     = array('description' => T_('Instance Name'), 'type' => 'text');
        $fields['host']     = array('description' => T_('Hostname'), 'type' => 'text');
        $fields['port']     = array('description' => T_('Port'), 'type' => 'number');
        $fields['password'] = array('description' => T_('Password'), 'type' => 'password');

        return $fields;
    } // instance_fields

    /**
     * get_instance
     * This returns a single instance and all it's variables
     * @param string $instance
     * @return array
     */
    public function get_instance($instance = '')
    {
        $instance   = is_numeric($instance) ? (int) $instance : (int) AmpConfig::get('vlc_active', 0);
        $sql        = ($instance > 1) ? "SELECT * FROM `localplay_vlc` WHERE `id` = ?" : "SELECT * FROM `localplay_vlc`";
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

        Preference::update('vlc_active', $user_id, $uid);
        AmpConfig::set('vlc_active', $uid, true);

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
     * @param Stream_URL $url
     * @return boolean|mixed
     */
    public function add_url(Stream_URL $url)
    {
        if ($this->_vlc->add($url->title, $url->url) === null) {
            debug_event(self::class, 'add_url failed to add: ' . json_encode($url), 1);

            return false;
        }

        return true;
    }

    /**
     * delete_track
     * This must take an array of ID's (as passed by get function) from Ampache
     * and delete them from VLC webinterface
     * @param integer $object_id
     * @return boolean
     */
    public function delete_track($object_id)
    {
        if ($this->_vlc->delete_pos($object_id) === null) {
            debug_event(self::class, 'ERROR Unable to delete ' . $object_id . ' from VLC', 1);

            return false;
        }

        return true;
    } // delete_track

    /**
     * clear_playlist
     * This deletes the entire VLC playlist... nuff said
     */
    public function clear_playlist()
    {
        if ($this->_vlc->clear() === null) {
            return false;
        }

        // If the clear worked we should stop it!
        $this->stop();

        return true;
    } // clear_playlist

    /**
     * play
     * This just tells VLC to start playing, it does not
     * take any arguments
     */
    public function play()
    {
        /* A play when it's already playing causes a track restart
         * which we don't want to doublecheck its state
         */
        if ($this->_vlc->state() == 'play') {
            return true;
        }

        if ($this->_vlc->play() === null) {
            return false;
        }

        return true;
    } // play

    /**
     * stop
     * This just tells VLC to stop playing, it does not take
     * any arguments
     */
    public function stop()
    {
        if ($this->_vlc->stop() === null) {
            return false;
        }

        return true;
    } // stop

    /**
     * skip
     * This tells VLC to skip to the specified song
     * @param $song
     * @return boolean
     */
    public function skip($song)
    {
        if ($this->_vlc->skip($song) === null) {
            return false;
        }

        return true;
    } // skip

    /**
     * This tells VLC to increase the volume by in vlcplayerclass set amount
     */
    public function volume_up()
    {
        return $this->_vlc->volume_up();
    } // volume_up

    /**
     * This tells VLC to decrease the volume by vlcplayerclass set amount
     */
    public function volume_down()
    {
        return $this->_vlc->volume_down();
    } // volume_down

    /**
     * next
     * This just tells VLC to skip to the next song, if you play a song by direct
     * clicking and hit next VLC will start with the first song , needs work.
     */
    public function next()
    {
        if ($this->_vlc->next() === null) {
            return false;
        }

        return true;
    } // next

    /**
     * prev
     * This just tells VLC to skip to the prev song
     */
    public function prev()
    {
        if ($this->_vlc->prev() === null) {
            return false;
        }

        return true;
    } // prev

    /**
     * pause
     * This tells VLC to pause the current song
     */
    public function pause()
    {
        if ($this->_vlc->pause() === null) {
            return false;
        }

        return true;
    } // pause

    /**
     * volume
     * This tells VLC to set the volume to the specified amount this
     * is 0-400 procent
     * @param $volume
     * @return boolean
     */
    public function volume($volume)
    {
        return $this->_vlc->set_volume($volume);
    } // volume

    /**
     * repeat
     * This tells VLC to set the repeating the playlist (i.e. loop) to either on or off
     * @param $state
     * @return boolean
     */
    public function repeat($state)
    {
        if ($this->_vlc->repeat($state) === null) {
            return false;
        }

        return true;
    } // repeat

    /**
     * random
     * This tells VLC to turn on or off the playing of songs from the playlist in random order
     * @param $onoff
     * @return boolean
     */
    public function random($onoff)
    {
        if ($this->_vlc->random($onoff) === null) {
            return false;
        }

        return true;
    } // random

    /**
     * get
     * This functions returns an array containing information about
     * The songs that VLC currently has in it's playlist. This must be
     * done in a standardized fashion
     * Warning ! if you got files in VLC medialibary those files will be sent to the php xml parser
     * to, not to your browser but still this can take a lot of work for your server.
     * The xml files of VLC need work, not much documentation on them....
     */
    public function get()
    {
        /* Get the Current Playlist */
        $list = $this->_vlc->get_tracks();

        if (!$list) {
            return array();
        }
        $songs   = array();
        $song_id = array();
        $results = array();
        $counter = 0;
        // here we look if there are song in the playlist when media libary is used
        if ($list['node']['node'][0]['leaf'][$counter]['attr']['uri']) {
            while ($list['node']['node'][0]['leaf'][$counter]) {
                $songs[]   = htmlspecialchars_decode($list['node']['node'][0]['leaf'][$counter]['attr']['uri'], ENT_NOQUOTES);
                $song_id[] = $list['node']['node'][0]['leaf'][$counter]['attr']['id'];
                $counter++;
            }
        } elseif ($list['node']['node'][0]['leaf']['attr']['uri']) {
            // if there is only one song look here,and media library is used
            $songs[]   = htmlspecialchars_decode($list['node']['node'][0]['leaf']['attr']['uri'], ENT_NOQUOTES);
            $song_id[] = $list['node']['node'][0]['leaf']['attr']['id'];
        } elseif ($list['node']['node']['leaf'][$counter]['attr']['uri']) {
            // look for songs when media library isn't used
            while ($list['node']['node']['leaf'][$counter]) {
                $songs[]   = htmlspecialchars_decode($list['node']['node']['leaf'][$counter]['attr']['uri'], ENT_NOQUOTES);
                $song_id[] = $list['node']['node']['leaf'][$counter]['attr']['id'];
                $counter++;
            }
        } elseif ($list['node']['node']['leaf']['attr']['uri']) {
            $songs[]   = htmlspecialchars_decode($list['node']['node']['leaf']['attr']['uri'], ENT_NOQUOTES);
            $song_id[] = $list['node']['node']['leaf']['attr']['id'];
        } else {
            return array();
        }

        $counter = 0;
        foreach ($songs as $key => $entry) {
            $data = array();

            /* Required Elements */
            $data['id']  = $song_id[$counter]; // id number of the files in the VLC playlist, needed for other operations
            $data['raw'] = $entry;

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
                    // If we don't know it, look up by filename
                    $filename = Dba::escape($entry);
                    $sql      = "SELECT `name` FROM `live_stream` WHERE `url`='$filename' ";

                    $db_results = Dba::read($sql);
                    if ($row = Dba::fetch_assoc($db_results)) {
                        // if stream is known just send name
                        $data['name'] = htmlspecialchars(substr($row['name'], 0, 50));
                    } elseif (strncmp($entry, 'http', 4) == 0) {
                        // if it's a http stream not in ampacha's database just show the url'
                        $data['name'] = htmlspecialchars("(VLC stream) " . substr($entry, 0, 50));
                    } else {
                        // it's a file get the last output after  and show that, hard to take every output possible in account
                        $getlast      = explode("/", $entry);
                        $lastis       = count($getlast) - 1;
                        $data['name'] = htmlspecialchars("(VLC local) " . substr($getlast[$lastis], 0, 50));
                    } // end if loop
                    break;
            } // end switch on primary key type

            $data['track']    = $key + 1;
            $counter++;
            $results[] = $data;
        } // foreach playlist items

        return $results;
    } // get

    /**
     * status
     * This returns bool/int values for features, loop, repeat and any other features
     * That this Localplay method supports. required function
     * This works as in requesting the status.xml file from VLC.
     * @return array
     */
    public function status()
    {
        $arrayholder = $this->_vlc->fullstate(); //get status.xml via parser xmltoarray
        /* Construct the Array */
        $currentstat = $arrayholder['root']['state']['value'];

        if ($currentstat == 'playing') {
            $state = 'play';
        } // change to something ampache understands
        if ($currentstat == 'stop') {
            $state = 'stop';
        }
        if ($currentstat == 'paused') {
            $state = 'pause';
        }

        $array['state']     = $state;
        $array['volume']    = (int) (((int) ($arrayholder['root']['volume']['value']) / 2.6));
        $array['repeat']    = $arrayholder['root']['repeat']['value'];
        $array['random']    = $arrayholder['root']['random']['value'];
        $array['track']     = htmlspecialchars_decode($arrayholder['root']['information']['meta-information']['title']['value'], ENT_NOQUOTES);

        $url_data = $this->parse_url($array['track']);
        $song     = new Song($url_data['oid']);
        if ($song->title || $song->get_artist_name() || $song->get_album_name()) {
            $array['track_title']      = $song->title;
            $array['track_artist']     = $song->get_artist_name();
            $array['track_album']      = $song->get_album_name();
        }
        // if not a known format
        else {
            $array['track_title']  = htmlspecialchars(substr($arrayholder['root']['information']['meta-information']['title']['value'], 0, 25));
            $array['track_artist'] = htmlspecialchars(substr($arrayholder['root']['information']['meta-information']['artist']['value'], 0, 20));
        }

        return $array;
    } // status

    /**
     * connect
     * This functions creates the connection to VLC and returns
     * a boolean value for the status, to save time this handle
     * is stored in this class
     */
    public function connect()
    {
        $options    = self::get_instance();
        $this->_vlc = new VlcPlayer($options['host'], $options['password'], $options['port']);

        // Test our connection by retriving the version, no version in status file, just need to see if returned
        // Not yet working all values returned are true for beta testing purpose
        if ($this->_vlc->version() !== null) {
            return true;
        }

        return false;
    } // connect
} // end vlc.controller
