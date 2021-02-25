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
 * AmpacheUPnp Class
 *
 * This is the class for the UPnP Localplay method to remote control
 * a UPnP player Instance
 *
 */
class AmpacheUPnP extends localplay_controller
{
    /* Variables */
    private $_version = '000001';

    private $_description = 'Controls a UPnP instance';

    /* @var UPnPPlayer $object */
    private $_upnp;


    /**
     * Constructor
     * This returns the array map for the Localplay object
     * REQUIRED for Localplay
     */
    public function __construct()
    {
        /* Do a Require Once On the needed Libraries */
        require_once AmpConfig::get('prefix') . '/modules/localplay/upnp/upnpplayer.class.php';
    }

    /**
     * get_description
     * This returns the description of this Localplay method
     */
    public function get_description()
    {
        return $this->_description;
    }

    /**
     * get_version
     * This returns the current version
     */
    public function get_version()
    {
        return $this->_version;
    }

    /**
     * is_installed
     * This returns true or false if UPnP controller is installed
     */
    public function is_installed()
    {
        $sql        = "SHOW TABLES LIKE 'localplay_upnp'";
        $db_results = Dba::query($sql);

        return (Dba::num_rows($db_results) > 0);
    }

    /**
     * install
     * This function installs the UPnP Localplay controller
     */
    public function install()
    {
        $collation = (AmpConfig::get('database_collation', 'utf8_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE `localplay_upnp` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , " .
            "`name` VARCHAR( 128 ) COLLATE $collation NOT NULL , " .
            "`owner` INT( 11 ) NOT NULL, " .
            "`url` VARCHAR( 255 ) COLLATE $collation NOT NULL  " .
            ") ENGINE = $engine DEFAULT CHARSET=$charset COLLATE=$collation";
        Dba::query($sql);

        // Add an internal preference for the users current active instance
        Preference::insert('upnp_active', T_('UPnP Active Instance'), 0, 25, 'integer', 'internal', 'upnp');

        return true;
    }

    /**
     * uninstall
     * This removes the Localplay controller
     */
    public function uninstall()
    {
        $sql = "DROP TABLE `localplay_upnp`";
        Dba::query($sql);

        // Remove the pref we added for this
        Preference::delete('upnp_active');

        return true;
    }

    /**
     * add_instance
     * This takes key'd data and inserts a new UPnP instance
     * @param array $data
     * @return PDOStatement|boolean
     */
    public function add_instance($data)
    {
        $sql = "INSERT INTO `localplay_upnp` (`name`, `url`, `owner`) " .
            "VALUES (?, ?, ?)";

        return Dba::query($sql, array($data['name'], $data['url'], Core::get_global('user')->id));
    }

    /**
     * delete_instance
     * This takes a UID and deletes the instance in question
     * @param $uid
     * @return boolean
     */
    public function delete_instance($uid)
    {
        $sql = "DELETE FROM `localplay_upnp` WHERE `id` = ?";
        Dba::query($sql, array($uid));

        return true;
    }

    /**
     * get_instances
     * This returns a key'd array of the instance information with
     * [UID]=>[NAME]
     */
    public function get_instances()
    {
        $sql        = "SELECT * FROM `localplay_upnp` ORDER BY `name`";
        $db_results = Dba::query($sql);
        $results    = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['id']] = $row['name'];
        }

        return $results;
    }

    /**
     * update_instance
     * This takes an ID and an array of data and updates the instance specified
     * @param $uid
     * @param array $data
     * @return boolean
     */
    public function update_instance($uid, $data)
    {
        $sql = "UPDATE `localplay_upnp` SET `url` = ?, `name` = ?  WHERE `id` = ?";
        Dba::query($sql, array($data['url'], $data['name'], $uid));

        return true;
    }

    /**
     * instance_fields
     * This returns a key'd array of [NAME]=>array([DESCRIPTION]=>VALUE,[TYPE]=>VALUE) for the
     * fields so that we can on-the-fly generate a form
     */
    public function instance_fields()
    {
        $fields['name'] = array('description' => T_('Instance Name'), 'type' => 'text');
        $fields['url']  = array('description' => T_('URL'), 'type' => 'url');

        return $fields;
    }

    /**
     * get_instance
     * This returns a single instance and all it's variables
     * @param string $instance
     * @return array
     */
    public function get_instance($instance = '')
    {
        $instance   = is_numeric($instance) ? (int) $instance : (int) AmpConfig::get('upnp_active', 0);
        $sql        = ($instance > 1) ? "SELECT * FROM `localplay_upnp` WHERE `id` = ?" : "SELECT * FROM `localplay_upnp`";
        $db_results = Dba::query($sql, array($instance));

        return Dba::fetch_assoc($db_results);
    }

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
        debug_event('upnp.controller', 'set_active_instance userid: ' . $user_id, 5);

        Preference::update('upnp_active', $user_id, $uid);
        AmpConfig::set('upnp_active', $uid, true);

        return true;
    }

    /**
     * get_active_instance
     * This returns the UID of the current active instance
     * false if none are active
     */
    public function get_active_instance()
    {
    }

    /**
     * @param Stream_URL $url
     * @return boolean|mixed
     */
    public function add_url(Stream_URL $url)
    {
        debug_event('upnp.controller', 'add_url: ' . $url->title . " | " . $url->url, 5);

        if (!$this->_upnp) {
            return false;
        }

        $this->_upnp->PlaylistAdd($url->title, $url->url);

        return true;
    }

    /**
     * delete_track
     * Delete a track from the UPnP playlist
     * @param $track
     * @return boolean
     */
    public function delete_track($track)
    {
        if (!$this->_upnp) {
            return false;
        }

        $this->_upnp->PlaylistRemove($track);

        return true;
    }

    /**
     * clear_playlist
     * This deletes the entire UPnP playlist.
     */
    public function clear_playlist()
    {
        if (!$this->_upnp) {
            return false;
        }

        $this->_upnp->PlaylistClear();

        return true;
    }

    /**
     * play
     * This just tells UPnP to start playing, it does not
     * take any arguments
     */
    public function play()
    {
        if (!$this->_upnp) {
            return false;
        }

        return $this->_upnp->Play();
    }

    /**
     * pause
     * This tells UPnP to pause the current song
     */
    public function pause()
    {
        if (!$this->_upnp) {
            return false;
        }

        return $this->_upnp->Pause();
    }

    /**
     * stop
     * This just tells UPnP to stop playing, it does not take
     * any arguments
     */
    public function stop()
    {
        if (!$this->_upnp) {
            return false;
        }

        return $this->_upnp->Stop();
    }

    /**
     * skip
     * This tells UPnP to skip to the specified song
     * @param $pos
     * @return boolean
     */
    public function skip($pos)
    {
        if (!$this->_upnp) {
            return false;
        }

        $this->_upnp->Skip($pos);

        return true;
    }

    /**
     * next
     * This just tells UPnP to skip to the next song
     */
    public function next()
    {
        if (!$this->_upnp) {
            return false;
        }

        $this->_upnp->Next();

        return true;
    }

    /**
     * prev
     * This just tells UPnP to skip to the prev song
     */
    public function prev()
    {
        if (!$this->_upnp) {
            return false;
        }

        $this->_upnp->Prev();

        return true;
    }

    /**
     * volume
     * This tells UPnP to set the volume to the specified amount
     * @param $volume
     * @return boolean
     */
    public function volume($volume)
    {
        if (!$this->_upnp) {
            return false;
        }

        $this->_upnp->SetVolume($volume);

        return true;
    }

    /**
     * This tells UPnP to increase the volume
     */
    public function volume_up()
    {
        if (!$this->_upnp) {
            return false;
        }

        return $this->_upnp->VolumeUp();
    }

    /**
     * This tells UPnP to decrease the volume
     */
    public function volume_down()
    {
        if (!$this->_upnp) {
            return false;
        }

        return $this->_upnp->VolumeDown();
    }

    /**
     * repeat
     * This tells UPnP to set the repeating the playlist (i.e. loop) to either on or off
     * @param $state
     * @return boolean
     */
    public function repeat($state)
    {
        debug_event('upnp.controller', 'repeat: ' . $state, 5);

        if (!$this->_upnp) {
            return false;
        }

        $this->_upnp->Repeat(array(
            'repeat' => ($state ? 'all' : 'off')
        ));

        return true;
    }

    /**
     * random
     * This tells UPnP to turn on or off the playing of songs from the playlist in random order
     * @param $onoff
     * @return boolean
     */
    public function random($onoff)
    {
        debug_event('upnp.controller', 'random: ' . $onoff, 5);

        if (!$this->_upnp) {
            return false;
        }

        $this->_upnp->PlayShuffle($onoff);

        return true;
    }

    /**
     * get
     * This functions returns an array containing information about
     * The songs that UPnP currently has in it's playlist. This must be
     * done in a standardized fashion
     */
    public function get()
    {
        debug_event('upnp.controller', 'get', 5);

        if (!$this->_upnp) {
            return false;
        }

        $playlist = $this->_upnp->GetPlaylistItems();

        $results = array();
        $idx     = 1;
        foreach ($playlist as $key => $item) {
            $data          = array();
            $data['link']  = $item['link'];
            $data['id']    = $idx;
            $data['track'] = $idx;

            $url_data = Stream_URL::parse($item['link']);
            if ($url_data != null) {
                $song = new Song($url_data['id']);
                if ($song != null) {
                    $data['name'] = $song->get_artist_name() . ' - ' . $song->title;
                }
            }
            if (!$data['name']) {
                $data['name'] = $item['name'];
            }

            $results[] = $data;
            $idx++;
        }

        return $results;
    }

    /**
     * status
     * This returns bool/int values for features, loop, repeat and any other features
     * that this Localplay method supports.
     * This works as in requesting the UPnP properties
     * @return array
     */
    public function status()
    {
        debug_event('upnp.controller', 'status', 5);
        $status = array();
        if (!$this->_upnp) {
            return $status;
        }

        $item = $this->_upnp->GetCurrentItem();


        $status['state']       = $this->_upnp->GetState();
        $status['volume']      = $this->_upnp->GetVolume();
        $status['repeat']      = false;
        $status['random']      = false;
        $status['track']       = $item['link'];
        $status['track_title'] = $item['name'];

        $url_data = Stream_URL::parse($item['link']);
        if ($url_data != null) {
            $song = new Song($url_data['id']);
            if ($song != null) {
                $status['track_artist'] = $song->get_artist_name();
                $status['track_album']  = $song->get_album_name();
            }
        }

        return $status;
    }

    /**
     * connect
     * This functions creates the connection to UPnP and returns
     * a boolean value for the status, to save time this handle
     * is stored in this class
     */
    public function connect()
    {
        $options = self::get_instance();
        debug_event('upnp.controller', 'Trying to connect UPnP instance ' . $options['name'] . ' ( ' . $options['url'] . ' )', 5);
        $this->_upnp = new UPnPPlayer($options['name'], $options['url']);
        debug_event('upnp.controller', 'Connected.', 5);

        return true;
    }
}
