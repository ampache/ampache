<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

declare(strict_types=0);

namespace Ampache\Module\Playback\Localplay;

use Ampache\Module\Api\Ajax;
use Ampache\Config\AmpConfig;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Preference;

class LocalPlay
{
    /* Base Variables */
    public $type;
    public $f_name;
    public $f_description;
    public $f_version;

    /* Built Variables */
    private $_player;

    /**
     * Constructor
     * This must be called with a Localplay type, it then loads the config
     * file for the specified type and attempts to load in the function
     * map, the preferences and the template
     * @param $type
     */
    public function __construct($type)
    {
        $this->type = $type;

        $this->has_info();
    } // Localplay

    /**
     * has_info
     * This functions takes the type and attempts to get all the
     * information needed to load it. Will log errors if there are
     * any failures, fatal errors will actually return something to the
     * gui
     */
    private function has_info()
    {
        $this->_load_player();
    } // has_info

    /**
     * player_loaded
     * This returns true / false if the player load
     * failed / worked
     */
    public function player_loaded(): bool
    {
        if (is_object($this->_player)) {
            return true;
        } else {
            return false;
        }
    } // player_loaded

    /**
     * format
     * This makes the Localplay/plugin information
     * human readable
     */
    public function format()
    {
        if (is_object($this->_player)) {
            $this->f_name        = ucfirst($this->type);
            $this->f_description = $this->_player->get_description();
            $this->f_version     = $this->_player->get_version();
        }
    } // format

    /**
     * _load_player
     * This function attempts to load the player class that Localplay
     * Will interface with in order to make all this magical stuff work
     * all LocalPlay modules should be located in /modules/<name>/<name>.class.php
     */
    private function _load_player(): bool
    {
        if (!$this->type) {
            return false;
        }

        $controller = LocalPlayTypeEnum::TYPE_MAPPING[$this->type] ?? null;

        if ($controller === null) {
            /* Throw Error Here */
            debug_event(self::class, 'Unable to load ' . $this->type . ' controller', 2);

            return false;
        }

        $this->_player = new $controller();
        if (!($this->_player instanceof localplay_controller)) {
            debug_event(__CLASS__, $this->type . ' not an instance of controller abstract, unable to load', 1);
            unset($this->_player);

            return false;
        }

        return true;
    } // _load_player

    /**
     * format_name
     * This function takes the track name and checks to see if 'skip'
     * is supported in the current player, if so it returns a 'skip to'
     * link, otherwise it returns just the text
     * @param string $name
     * @param int $object_id
     */
    public function format_name($name, $object_id): string
    {
        return Ajax::text('?page=localplay&action=command&command=skip&id=' . $object_id, scrub_out($name), 'localplay_skip_' . $object_id);
    } // format_name

    /**
     * is_enabled
     * This returns true or false depending on if the specified controller
     * is currently enabled
     * @param $controller
     */
    public static function is_enabled($controller): bool
    {
        // Load the controller and then check for its preferences
        $localplay = new LocalPlay($controller);
        // If we can't even load it no sense in going on
        if (!isset($localplay->_player)) {
            return false;
        }

        return $localplay->_player->is_installed();
    } // is_enabled

    /**
     * install
     * This runs the install for the Localplay controller we've
     * currently got pimped out
     */
    public function install()
    {
        // Run the player's installer
        return $this->_player->install();
    } // install

    /**
     * uninstall
     * This runs the uninstall for the Localplay controller we've
     * currently pimped out
     */
    public function uninstall(): bool
    {
        // Run the players uninstaller
        $this->_player->uninstall();

        // If its our current player, reset player to nothing
        if (AmpConfig::get('localplay_controller') == $this->type) {
            Preference::update('localplay_controller', Core::get_global('user')->id, '');
        }

        return true;
    } // uninstall

    /**
     * connect
     * This function attempts to connect to the Localplay
     * player that we are using
     */
    public function connect(): bool
    {
        if (!$this->_player->connect()) {
            debug_event(self::class, 'Error Unable to connect, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    } // connect

    /**
     * play
     * This function passes NULL and calls the play function of the player
     * object
     */
    public function play(): bool
    {
        if (!$this->_player->play()) {
            debug_event(self::class, 'Error Unable to start playback, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    } // play

    /**
     * stop
     * This functions passes NULl and calls the stop function of the player
     * object, it should receive a true/false boolean value
     */
    public function stop(): bool
    {
        if (!$this->_player->stop()) {
            debug_event(self::class, 'Error Unable to stop playback, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    } // stop

    /**
     * add
     * @param $object
     */
    public function add($object): bool
    {
        debug_event(self::class, 'Deprecated add method called: ' . json_encode($object), 5);

        return false;
    } // add

    /**
     * add_url
     * Add a URL to the Localplay module
     */
    public function add_url(Stream_Url $url): bool
    {
        if (!$this->_player->add_url($url)) {
            debug_event(self::class, 'Unable to add url ' . $url->url . ', check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    } // add_url

    /**
     * repeat
     * This turns the repeat feature of a Localplay method on or
     * off, takes a 0/1 value
     * @param bool $state
     */
    public function repeat($state): bool
    {
        $data = $this->_player->repeat($state);

        if (!$data) {
            debug_event(self::class, "Error Unable to set Repeat to $state", 1);
        }

        return $data;
    } // repeat

    /**
     * random
     * This turns on the random feature of a Localplay method
     * It takes a 0/1 value
     * @param bool $state
     */
    public function random($state): bool
    {
        $data = $this->_player->random($state);

        if (!$data) {
            debug_event(self::class, "Error Unable to set Random to $state", 1);
        }

        return $data;
    } // random

    /**
     * status
     * This returns current information about the state of the player
     * There is an expected array format
     */
    public function status(): array
    {
        $data = $this->_player->status();

        if (empty($data) || !is_array($data)) {
            debug_event(self::class, 'Error Unable to get status, check ' . $this->type . ' controller', 1);

            return array();
        }

        return $data;
    } // status

    /**
     * get
     * This calls the get function of the player and then returns
     * the array of current songs for display or whatever
     * an empty array is passed on failure
     */
    public function get()
    {
        $data = $this->_player->get();

        if (empty($data) || !is_array($data)) {
            debug_event(self::class, 'Error Unable to get song info, check ' . $this->type . ' controller', 1);

            return array();
        }

        return $data;
    } // get

    /**
     * volume_set
     * This isn't a required function, it sets the volume to a specified value
     * as passed in the variable it is a 0 - 100 scale the controller is
     * responsible for adjusting the scale if necessary
     * @param $value
     */
    public function volume_set($value): bool
    {
        /* Make sure it's int and 0 - 100 */
        $value = (int)$value;

        /* Make sure that it's between 0 and 100 */
        if ($value > 100 || $value < 0) {
            return false;
        }

        if (!$this->_player->volume($value)) {
            debug_event(self::class, 'Error: Unable to set volume, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    } // volume_set

    /**
     * volume_up
     * This function isn't required. It tells the daemon to increase the volume
     * by a pre-defined amount controlled by the controller
     */
    public function volume_up(): bool
    {
        if (!$this->_player->volume_up()) {
            debug_event(self::class, 'Error: Unable to increase volume, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    } // volume_up

    /**
     * volume_down
     * This function isn't required. It tells the daemon to decrease the volume
     * by a pre-defined amount controlled by the controller.
     */
    public function volume_down(): bool
    {
        if (!$this->_player->volume_down()) {
            debug_event(self::class, 'Error: Unable to decrese volume, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    } // volume_down

    /**
     * volume_mute
     * This function isn't required, It tells the daemon to mute all output
     * It's up to the controller to decide what that actually entails
     */
    public function volume_mute(): bool
    {
        if (!$this->_player->volume(0)) {
            debug_event(self::class, 'Error: Unable to mute volume, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    } // volume_mute

    /**
     * skip
     * This isn't a required function, it tells the daemon to skip to the specified song
     * @param $track_id
     */
    public function skip($track_id): bool
    {
        if (!$this->_player->skip($track_id)) {
            debug_event(self::class, 'Error: Unable to skip to next song, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    } // skip

    /**
     * next
     * This isn't a required function, it tells the daemon to go to the next
     * song
     */
    public function next(): bool
    {
        if (!$this->_player->next()) {
            debug_event(self::class, 'Error: Unable to skip to next song, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    } // next

    /**
     * prev
     * This isn't a required function, it tells the daemon to go the the previous
     * song
     */
    public function prev(): bool
    {
        if (!$this->_player->prev()) {
            debug_event(self::class, 'Error: Unable to skip to previous song, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    } // prev

    /**
     * pause
     * This isn't a required function, it tells the daemon to pause the
     * song
     */
    public function pause(): bool
    {
        if (!$this->_player->pause()) {
            debug_event(self::class, 'Error: Unable to pause song, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    } // pause

    /**
     * get_instances
     * This returns the instances of the current type
     */
    public function get_instances()
    {
        return $this->_player->get_instances();
    } // get_instances

    /**
     * current_instance
     * This returns the UID of the current Instance
     */
    public function current_instance()
    {
        $data = $this->_player->get_instance();
        if (array_key_exists('id', $data)) {
            return $data['id'];
        }

        return false;
    } // current_instance

    /**
     * get_instance
     * This returns the specified instance
     * @param int $uid
     * @return array
     */
    public function get_instance($uid)
    {
        return $this->_player->get_instance($uid);
    } // get_instance

    /**
     * update_instance
     * This updates the specified instance with a named array of data (_POST most likely)
     * @param $uid
     * @param array $data
     */
    public function update_instance($uid, $data): bool
    {
        return $this->_player->update_instance($uid, $data);
    } // update_instance

    /**
     * add_instance
     * This adds a new instance for the current controller type
     * @param array $data
     */
    public function add_instance($data)
    {
        $this->_player->add_instance($data);
    } // add_instance

    /**
     * delete_instance
     * This removes an instance (it actually calls the players function)
     * @param $instance_uid
     */
    public function delete_instance($instance_uid)
    {
        $this->_player->delete_instance($instance_uid);
    } // delete_instance

    /**
     * set_active_instance
     * This sets the active instance of the Localplay controller
     * @param $instance_id
     */
    public function set_active_instance($instance_id)
    {
        $this->_player->set_active_instance($instance_id);
    } // set_active_instance

    /**
     * delete_track
     * This removes songs from the players playlist it takes a single ID as provided
     * by the get command
     * @param int $object_id
     */
    public function delete_track($object_id): bool
    {
        if (!$this->_player->delete_track($object_id)) {
            debug_event(self::class, 'Error: Unable to remove songs, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    } // delete

    /**
     * delete_all
     * This removes every song from the players playlist as defined by the delete_all function
     * map
     */
    public function delete_all(): bool
    {
        if (!$this->_player->clear_playlist()) {
            debug_event(self::class, 'Error: Unable to delete entire playlist, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    } // delete_all

    /**
     * get_instance_fields
     * This loads the fields from the Localplay
     * player and returns them
     */
    public function get_instance_fields(): array
    {
        return $this->_player->instance_fields();
    } // get_instance_fields

    /**
     * get_user_state
     * This function returns a user friendly version
     * of the current player state
     * @param $state
     */
    public function get_user_state($state): string
    {
        switch ($state) {
            case 'play':
                return T_('Now Playing');
            case 'stop':
                return T_('Stopped');
            case 'pause':
                return T_('Paused');
            default:
                return T_('Unknown');
        } // switch on state
    } // get_user_state

    /**
     * get_user_playing
     * This attempts to return a nice user friendly
     * currently playing string
     */
    public function get_user_playing(): string
    {
        $status = $this->status();

        /* Format the track name */
        $track_name = $status['track_artist'] . ' - ' . $status['track_album'] . ' - ' . $status['track_title'];

        // Hacky fix for when we were unable to find an artist/album (or one wasn't provided)
        $track_name = ltrim(ltrim((string)$track_name, ' - '), ' - ');

        $track_name = "[" . $status['track'] . "] - " . $track_name;

        return $track_name;
    } // get_user_playing
}
