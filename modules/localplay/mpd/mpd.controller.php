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
 * AmpacheMpd Class
 *
 * the Ampache Mpd Controller, this is the glue between
 * the MPD class and the Ampache Localplay class
 *
 */
class AmpacheMpd extends localplay_controller
{
    /* Variables */
    private $version        = '000003';
    private $description    = 'Controls an instance of MPD';

    private $_add_count = 0;

    /* Constructed variables */
    private $_mpd;

    /**
     * Constructor
     * This returns the array map for the Localplay object
     * REQUIRED for Localplay
     */
    public function __construct()
    {
        /* Do a Require Once On the needed Libraries */
        require_once AmpConfig::get('prefix') . '/modules/localplay/mpd/mpd.class.php';
    } // AmpacheMpd

    /**
     * get_description
     * Returns the description
     */
    public function get_description()
    {
        return $this->description;
    } // get_description

    /**
     * get_version
     * This returns the version information
     */
    public function get_version()
    {
        return $this->version;
    } // get_version

    /**
     * is_installed
     * This returns true or false if MPD controller is installed
     */
    public function is_installed()
    {
        $sql        = "SHOW TABLES LIKE 'localplay_mpd'";
        $db_results = Dba::read($sql);

        return (Dba::num_rows($db_results) > 0);
    } // is_installed

    /**
     * install
     * This function installs the MPD Localplay controller
     */
    public function install()
    {
        $collation = (AmpConfig::get('database_collation', 'utf8_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';
        /* We need to create the MPD table */
        $sql = "CREATE TABLE `localplay_mpd` ( `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , " .
                "`name` VARCHAR( 128 ) COLLATE $collation NOT NULL , " .
                "`owner` INT( 11 ) NOT NULL , " .
                "`host` VARCHAR( 255 ) COLLATE $collation NOT NULL , " .
                "`port` INT( 11 ) UNSIGNED NOT NULL DEFAULT '6600', " .
                "`password` VARCHAR( 255 ) COLLATE $collation NOT NULL , " .
                "`access` SMALLINT( 4 ) UNSIGNED NOT NULL DEFAULT '0'" .
                ") ENGINE = $engine DEFAULT CHARSET=$charset COLLATE=$collation";
        Dba::query($sql);

        // Add an internal preference for the users current active instance
        Preference::insert('mpd_active', T_('MPD Active Instance'), 0, 25, 'integer', 'internal', 'mpd');

        return true;
    } // install

    /**
     * uninstall
     * This removes the Localplay controller
     */
    public function uninstall()
    {
        $sql        = "DROP TABLE `localplay_mpd`";
        $db_results = Dba::write($sql);

        Preference::delete('mpd_active');

        return true;
    } // uninstall

    /**
     * add_instance
     * This takes key'd data and inserts a new MPD instance
     * @param array $data
     * @return PDOStatement|boolean
     */
    public function add_instance($data)
    {
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'name':
                case 'host':
                case 'port':
                case 'password':
                    ${$key} = Dba::escape($value);
                break;
                default:

                break;
            } // end switch
        } // end foreach

        $user_id = Dba::escape(Core::get_global('user')->id);

        $sql = "INSERT INTO `localplay_mpd` (`name`, `host`, `port`, `password`, `owner`) " .
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

        // Go ahead and delete this mofo!
        $sql        = "DELETE FROM `localplay_mpd` WHERE `id`='$uid'";
        $db_results = Dba::write($sql);

        return true;
    } // delete_instance

    /**
      * get_instances
     * This returns a key'd array of the instance information with
     * [UID]=>[NAME]
     */
    public function get_instances()
    {
        $sql        = "SELECT * FROM `localplay_mpd` ORDER BY `name`";
        $db_results = Dba::read($sql);

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['id']] = $row['name'];
        }

        return $results;
    } // get_instances

    /**
     * get_instance
     * This returns the specified instance and all it's pretty variables
     * If no instance is passed current is used
     * @param string $instance
     * @return array
     */
    public function get_instance($instance = '')
    {
        $instance   = is_numeric($instance) ? (int) $instance : (int) AmpConfig::get('mpd_active', 0);
        $sql        = ($instance > 1) ? "SELECT * FROM `localplay_mpd` WHERE `id`= ?" : "SELECT * FROM `localplay_mpd`";
        $db_results = Dba::query($sql, array($instance));

        return Dba::fetch_assoc($db_results);
    } // get_instance

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
        $host    = $data['host'] ? Dba::escape($data['host']) : '127.0.0.1';
        $port    = $data['port'] ? Dba::escape($data['port']) : '6600';
        $name    = Dba::escape($data['name']);
        $pass    = Dba::escape($data['password']);

        $sql        = "UPDATE `localplay_mpd` SET `host`='$host', `port`='$port', `name`='$name', `password`='$pass' WHERE `id`='$uid'";
        $db_results = Dba::write($sql);

        return true;
    } // update_instance

    /**
     * instance_fields
     * This returns a key'd array of [NAME]=>array([DESCRIPTION]=>VALUE,[TYPE]=>VALUE) for the
     * fields so that we can on-the-fly generate a form
     */
    public function instance_fields()
    {
        $fields['name']        = array('description' => T_('Instance Name'), 'type' => 'text');
        $fields['host']        = array('description' => T_('Hostname'), 'type' => 'text');
        $fields['port']        = array('description' => T_('Port'), 'type' => 'number');
        $fields['password']    = array('description' => T_('Password'), 'type' => 'password');

        return $fields;
    } // instance_fields

    /**
     * set_active_instance
     * This sets the specified instance as the 'active' one
     * @param string $uid
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

        Preference::update('mpd_active', $user_id, $uid);
        AmpConfig::set('mpd_active', $uid, true);
        debug_event('mdp.controller', 'set_active_instance: ' . $uid . ' ' . $user_id, 5);

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
        // If we haven't added anything then maybe we should clear the
        // playlist.
        if ($this->_add_count < 1) {
            $this->_mpd->RefreshInfo();
            if ($this->_mpd->status['state'] == mpd::STATE_STOPPED) {
                $this->clear_playlist();
            }
        }

        if (!$this->_mpd->PlAdd($url->url)) {
            debug_event('mdp.controller', 'add_url failed to add: ' . json_encode($url), 1);

            return false;
        }

        $this->_add_count++;

        return true;
    }

    /**
     * delete_track
     * This must take a single ID (as returned by the get function)
     * and delete it from the current playlist
     * @param integer $object_id
     * @return boolean|string
     */
    public function delete_track($object_id)
    {
        return $this->_mpd->PLRemove($object_id);
    } // delete_track

    /**
     * clear_playlist
     * This deletes the entire MPD playlist... nuff said
     */
    public function clear_playlist()
    {
        return $this->_mpd->PLClear();
    } // clear_playlist

    /**
     * play
     * This just tells MPD to start playing, it does not
     * take any arguments
     */
    public function play()
    {
        return $this->_mpd->Play();
    } // play

    /**
     * stop
     * This just tells MPD to stop playing, it does not take
     * any arguments
     */
    public function stop()
    {
        return $this->_mpd->Stop();
    } // stop

    /**
     * skip
     * This tells MPD to skip to the specified song
     * @param $song
     * @return boolean
     */
    public function skip($song)
    {
        if (!$this->_mpd->SkipTo($song)) {
            return false;
        }
        sleep(2);
        $this->stop();
        sleep(2);
        $this->play();

        return true;
    } // skip

    /**
     * This tells MPD to increase the volume by 5
     */
    public function volume_up()
    {
        return $this->_mpd->AdjustVolume('5');
    } // volume_up

    /**
     * This tells MPD to decrease the volume by 5
     */
    public function volume_down()
    {
        return $this->_mpd->AdjustVolume('-5');
    } // volume_down

    /**
     * next
     * This just tells MPD to skip to the next song
     */
    public function next()
    {
        return $this->_mpd->Next();
    } // next

    /**
     * prev
     * This just tells MPD to skip to the prev song
     */
    public function prev()
    {
        return $this->_mpd->Previous();
    } // prev

    /**
     * pause
     * This tells MPD to pause the current song
     */
    public function pause()
    {
        return $this->_mpd->Pause();
    } // pause


    /**
     * volume
     * This tells MPD to set the volume to the parameter
     * @param $volume
     * @return boolean|string
     */
    public function volume($volume)
    {
        return $this->_mpd->SetVolume($volume);
    } // volume

    /**
     * repeat
     * This tells MPD to set the repeating the playlist (i.e. loop) to either
     * on or off.
     * @param $state
     * @return boolean|string
     */
    public function repeat($state)
    {
        return $this->_mpd->SetRepeat($state);
    } // repeat

    /**
     * random
     * This tells MPD to turn on or off the playing of songs from the
     * playlist in random order.
     * @param $onoff
     * @return boolean|string
     */
    public function random($onoff)
    {
        return $this->_mpd->SetRandom($onoff);
    } // random

    /**
     * move
     * This tells MPD to move a song
     * @param $source
     * @param $destination
     * @return boolean|string
     */
    public function move($source, $destination)
    {
        return $this->_mpd->PLMoveTrack($source, $destination);
    } // move

    /**
     * get_songs
     * This functions returns an array containing information about
     * the songs that MPD currently has in its playlist. This must be
     * done in a standardized fashion
     * @return array
     */
    public function get()
    {
        // If we don't have the playlist yet, pull it
        if (!isset($this->_mpd->playlist)) {
            $this->_mpd->RefreshInfo();
        }

        /* Get the Current Playlist */
        $playlist = $this->_mpd->playlist;
        $results  = array();
        // if there isn't anything to return don't do it
        if (empty($playlist)) {
            return $results;
        }

        foreach ($playlist as $entry) {
            $data = array();

            /* Required Elements */
            $data['id']     = $entry['Pos'];
            $data['raw']    = $entry['file'];

            $url_data = $this->parse_url($entry['file']);

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
                        $title_string = ($entry['Title'] && $entry['Album'] && $entry['Artist']) ?
                                        $entry['Title'] . ' - ' . $entry['Album'] . ' - ' . $entry['Artist'] :
                                        T_('Unknown');
                        $data['name'] = $title_string;
                        $data['link'] = '';
                    }
                    break;
            } // end switch on primary key type

            /* Optional Elements */
            $data['track']    = $entry['Pos'] + 1;

            $results[] = $data;
        } // foreach playlist items

        return $results;
    } // get

    /**
     * get_status
     * This returns bool/int values for features, loop, repeat and any other
     * features that this Localplay method supports.
     * @return array
     */
    public function status()
    {
        $track = $this->_mpd->status['song'];
        $array = array();

        /* Construct the Array */
        $array['state']     = $this->_mpd->status['state'];
        $array['volume']    = $this->_mpd->status['volume'];
        $array['repeat']    = $this->_mpd->status['repeat'];
        $array['random']    = $this->_mpd->status['random'];
        $array['track']     = $track + 1;

        $playlist_item = $this->_mpd->playlist[$track];

        $url_data = $this->parse_url($playlist_item['file']);

        debug_event('mdp.controller', 'Status result. Current song (' . $track . ') info: ' . json_encode($playlist_item), 5);

        if (count($url_data) > 0 && !empty($url_data['oid'])) {
            $song                  = new Song($url_data['oid']);
            $array['track_title']  = $song->title;
            $array['track_artist'] = $song->get_artist_name();
            $array['track_album']  = $song->get_album_name();
        } else {
            if (!empty($playlist_item['Title'])) {
                $array['track_title'] = $playlist_item['Title'];
            } else {
                if (!empty($playlist_item['Name'])) {
                    $array['track_title'] = $playlist_item['Name'];
                } else {
                    $array['track_title'] = $playlist_item['file'];
                }
            }
        }

        return $array;
    } // get_status

    /**
     * connect
     * This functions creates the connection to MPD and returns
     * a boolean value for the status, to save time this handle
     * is stored in this class
     */
    public function connect()
    {
        // Look at the current instance and pull the options for said instance
        $options    = self::get_instance();
        $this->_mpd = new mpd($options['host'], $options['port'], $options['password'], 'debug_event');

        if ($this->_mpd->connected) {
            return true;
        }

        return false;
    } // connect
} // end mpd.controller
