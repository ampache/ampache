<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Playback\Localplay;

use Ampache\Module\Api\Ajax;
use Ampache\Config\AmpConfig;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;

class LocalPlay
{
    /* Base Variables */
    public string $type;
    public ?string $f_name        = null;
    public ?string $f_description = null;
    public ?string $f_version     = null;

    /* Built Variables */
    private ?localplay_controller $_player;

    /**
     * Constructor
     * This must be called with a Localplay type, it then loads the config
     * file for the specified type and attempts to load in the function
     * map, the preferences and the template
     * @param string $type
     */
    public function __construct($type)
    {
        $this->type = $type;

        $this->_load_player();
    }

    /**
     * player_loaded
     * This returns true / false if the player load
     * failed / worked
     */
    public function player_loaded(): bool
    {
        return $this->_player instanceof localplay_controller;
    }

    /**
     * format
     * This makes the Localplay/plugin information human readable
     */
    public function format(): void
    {
        if ($this->_player instanceof localplay_controller) {
            $this->f_name        = ucfirst($this->type);
            $this->f_description = $this->_player->get_description();
            $this->f_version     = $this->_player->get_version();
        }
    }

    /**
     * _load_player
     * This function attempts to load the player class that Localplay
     * Will interface with in order to make all this magical stuff work
     * all LocalPlay modules should be located in /modules/<name>/<name>.class.php
     */
    private function _load_player(): void
    {
        if (!$this->type) {
            return;
        }

        $controller = LocalPlayTypeEnum::TYPE_MAPPING[$this->type] ?? null;
        if ($controller === null) {
            debug_event(self::class, 'Unable to load ' . $this->type . ' controller', 2);

            return;
        }

        $this->_player = new $controller();
        if (!($this->_player instanceof localplay_controller)) {
            debug_event(self::class, $this->type . ' not an instance of controller abstract, unable to load', 1);
            $this->_player = null;
        }
    }

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
    }

    /**
     * is_enabled
     * This returns true or false depending on if the specified controller is currently enabled
     */
    public static function is_enabled(string $controller): bool
    {
        // Load the controller and then check for its preferences
        $localplay = new LocalPlay($controller);
        // If we can't even load it no sense in going on
        if (!isset($localplay->_player)) {
            return false;
        }

        return $localplay->_player->is_installed();
    }

    /**
     * install
     * This runs the install for the Localplay controller we've
     * currently got pimped out
     */
    public function install(): bool
    {
        // Run the player's installer
        return (
            $this->_player instanceof localplay_controller &&
            $this->_player->install()
        );
    }

    /**
     * uninstall
     * This runs the uninstall for the Localplay controller we've
     * currently pimped out
     */
    public function uninstall(): bool
    {
        if (!$this->_player instanceof localplay_controller) {
            return false;
        }

        // Run the players uninstaller
        $this->_player->uninstall();

        $user = Core::get_global('user');
        // If its our current player, reset player to nothing
        if ($user instanceof User && AmpConfig::get('localplay_controller') == $this->type) {
            Preference::update('localplay_controller', $user->getId(), '');
        }

        return true;
    }

    /**
     * connect
     * This function attempts to connect to the Localplay
     * player that we are using
     */
    public function connect(): bool
    {
        if (
            !$this->_player instanceof localplay_controller ||
            $this->_player->connect() === false
        ) {
            debug_event(self::class, 'Error Unable to connect, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    }

    /**
     * play
     * This function passes NULL and calls the play function of the player
     * object
     */
    public function play(): bool
    {
        if (
            !$this->_player instanceof localplay_controller ||
            !$this->_player->play()
        ) {
            debug_event(self::class, 'Error Unable to start playback, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    }

    /**
     * stop
     * This functions passes NULl and calls the stop function of the player
     * object, it should receive a true/false boolean value
     */
    public function stop(): bool
    {
        if (
            !$this->_player instanceof localplay_controller ||
            !$this->_player->stop()
        ) {
            debug_event(self::class, 'Error Unable to stop playback, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    }

    /**
     * add
     * @param $object
     */
    public function add($object): bool
    {
        debug_event(self::class, 'Deprecated add method called: ' . json_encode($object), 5);

        return false;
    }

    /**
     * add_url
     * Add a URL to the Localplay module
     */
    public function add_url(Stream_Url $url): bool
    {
        if (
            !$this->_player instanceof localplay_controller ||
            !$this->_player->add_url($url)
        ) {
            debug_event(self::class, 'Unable to add url ' . $url->url . ', check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    }

    /**
     * repeat
     * This turns the repeat feature of a Localplay method on or
     * off, takes a 0/1 value
     * @param bool $state
     */
    public function repeat(bool $state): bool
    {
        $data = ($this->_player instanceof localplay_controller)
            ? $this->_player->repeat($state)
            : false;

        if (!$data) {
            debug_event(self::class, "Error Unable to set Repeat to $state", 1);
        }

        return $data;
    }

    /**
     * random
     * This turns on the random feature of a Localplay method
     * It takes a 0/1 value
     * @param bool $state
     */
    public function random(bool $state): bool
    {
        $data = ($this->_player instanceof localplay_controller)
            ? $this->_player->random($state)
            : false;

        if (!$data) {
            debug_event(self::class, "Error Unable to set Random to $state", 1);
        }

        return $data;
    }

    /**
     * status
     * This returns current information about the state of the player
     * There is an expected array format
     */
    public function status(): array
    {
        $data = ($this->_player instanceof localplay_controller)
            ? $this->_player->status()
            : false;

        if (empty($data) || !is_array($data)) {
            debug_event(self::class, 'Error Unable to get status, check ' . $this->type . ' controller', 1);

            return [];
        }

        return $data;
    }

    /**
     * get
     * This calls the get function of the player and then returns
     * the array of current songs for display or whatever
     * an empty array is passed on failure
     */
    public function get(): array
    {
        $data = ($this->_player instanceof localplay_controller)
            ? $this->_player->get()
            : false;

        if (empty($data) || !is_array($data)) {
            debug_event(self::class, 'Error Unable to get song info, check ' . $this->type . ' controller', 1);

            return [];
        }

        return $data;
    }

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

        if (
            !$this->_player instanceof localplay_controller ||
            !$this->_player->volume($value)
        ) {
            debug_event(self::class, 'Error: Unable to set volume, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    }

    /**
     * volume_up
     * This function isn't required. It tells the daemon to increase the volume
     * by a pre-defined amount controlled by the controller
     */
    public function volume_up(): bool
    {
        if (
            !$this->_player instanceof localplay_controller ||
            !$this->_player->volume_up()
        ) {
            debug_event(self::class, 'Error: Unable to increase volume, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    }

    /**
     * volume_down
     * This function isn't required. It tells the daemon to decrease the volume
     * by a pre-defined amount controlled by the controller.
     */
    public function volume_down(): bool
    {
        if (
            !$this->_player instanceof localplay_controller ||
            !$this->_player->volume_down()
        ) {
            debug_event(self::class, 'Error: Unable to decrese volume, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    }

    /**
     * volume_mute
     * This function isn't required, It tells the daemon to mute all output
     * It's up to the controller to decide what that actually entails
     */
    public function volume_mute(): bool
    {
        if (
            !$this->_player instanceof localplay_controller ||
            !$this->_player->volume(0)
        ) {
            debug_event(self::class, 'Error: Unable to mute volume, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    }

    /**
     * skip
     * This isn't a required function, it tells the daemon to skip to the specified song
     */
    public function skip(int $track_id): bool
    {
        if (
            !$this->_player instanceof localplay_controller ||
            !$this->_player->skip($track_id)
        ) {
            debug_event(self::class, 'Error: Unable to skip to next song, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    }

    /**
     * next
     * This isn't a required function, it tells the daemon to go to the next
     * song
     */
    public function next(): bool
    {
        if (
            !$this->_player instanceof localplay_controller ||
            !$this->_player->next()
        ) {
            debug_event(self::class, 'Error: Unable to skip to next song, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    }

    /**
     * prev
     * This isn't a required function, it tells the daemon to go the the previous
     * song
     */
    public function prev(): bool
    {
        if (
            !$this->_player instanceof localplay_controller ||
            !$this->_player->prev()
        ) {
            debug_event(self::class, 'Error: Unable to skip to previous song, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    }

    /**
     * pause
     * This isn't a required function, it tells the daemon to pause the
     * song
     */
    public function pause(): bool
    {
        if (
            !$this->_player instanceof localplay_controller ||
            !$this->_player->pause()
        ) {
            debug_event(self::class, 'Error: Unable to pause song, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    }

    /**
     * get_instances
     * This returns the instances of the current type
     */
    public function get_instances(): array
    {
        return ($this->_player instanceof localplay_controller)
            ? $this->_player->get_instances()
            : [];
    }

    /**
     * current_instance
     * This returns the UID of the current Instance
     */
    public function current_instance()
    {
        $data = ($this->_player instanceof localplay_controller)
            ? $this->_player->get_instance()
            : [];
        if (array_key_exists('id', $data)) {
            return $data['id'];
        }

        return false;
    }

    /**
     * get_instance
     * This returns the specified instance
     * @return array
     */
    public function get_instance(?string $instance_id): array
    {
        return ($this->_player instanceof localplay_controller)
            ? $this->_player->get_instance($instance_id)
            : [];
    }

    /**
     * update_instance
     * This updates the specified instance with a named array of data (_POST most likely)
     * @param int $uid
     * @param array $data
     */
    public function update_instance($uid, $data): void
    {
        if ($this->_player instanceof localplay_controller) {
            $this->_player->update_instance($uid, $data);
        }
    }

    /**
     * add_instance
     * This adds a new instance for the current controller type
     * @param array $data
     */
    public function add_instance($data): void
    {
        if ($this->_player instanceof localplay_controller) {
            $this->_player->add_instance($data);
        }
    }

    /**
     * delete_instance
     * This removes an instance (it actually calls the players function)
     * @param int $uid
     */
    public function delete_instance($uid): void
    {
        if ($this->_player instanceof localplay_controller) {
            $this->_player->delete_instance($uid);
        }
    }

    /**
     * set_active_instance
     * This sets the active instance of the Localplay controller
     */
    public function set_active_instance(int $uid): void
    {
        if ($this->_player instanceof localplay_controller) {
            $this->_player->set_active_instance($uid);
        }
    }

    /**
     * delete_track
     * This removes songs from the players playlist it takes a single ID as provided
     * by the get command
     * @param int $object_id
     */
    public function delete_track($object_id): bool
    {
        if (
            !$this->_player instanceof localplay_controller ||
            !$this->_player->delete_track($object_id)
        ) {
            debug_event(self::class, 'Error: Unable to remove songs, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    }

    /**
     * delete_all
     * This removes every song from the players playlist as defined by the delete_all function
     * map
     */
    public function delete_all(): bool
    {
        if (
            !$this->_player instanceof localplay_controller ||
            !$this->_player->clear_playlist()
        ) {
            debug_event(self::class, 'Error: Unable to delete entire playlist, check ' . $this->type . ' controller', 1);

            return false;
        }

        return true;
    }

    /**
     * get_instance_fields
     * This loads the fields from the Localplay
     * player and returns them
     */
    public function get_instance_fields(): array
    {
        return $this->_player?->instance_fields() ?? [];
    }

    /**
     * get_user_state
     * This function returns a user friendly version
     * of the current player state
     * @param string|null $state
     */
    public function get_user_state($state): string
    {
        return match ($state) {
            'play' => T_('Now Playing'),
            'stop' => T_('Stopped'),
            'pause' => T_('Paused'),
            default => T_('Unknown'),
        }; // switch on state
    }

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
    }
}
