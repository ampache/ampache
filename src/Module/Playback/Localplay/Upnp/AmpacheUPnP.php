<?php

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

namespace Ampache\Module\Playback\Localplay\Upnp;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Playback\Localplay\localplay_controller;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;

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
    private string $_version = '000001';

    private string $_description = 'Controls a UPnP instance';

    /** @var UPnPPlayer $object */
    private $_upnp;

    /**
     * get_description
     * This returns the description of this Localplay method
     */
    public function get_description(): string
    {
        return $this->_description;
    }

    /**
     * get_version
     * This returns the current version
     */
    public function get_version(): string
    {
        return $this->_version;
    }

    /**
     * is_installed
     * This returns true or false if UPnP controller is installed
     */
    public function is_installed(): bool
    {
        $sql        = "SHOW TABLES LIKE 'localplay_upnp'";
        $db_results = Dba::query($sql);

        return (Dba::num_rows($db_results) > 0);
    }

    /**
     * install
     * This function installs the UPnP Localplay controller
     */
    public function install(): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = (AmpConfig::get('database_engine', 'InnoDB'));

        $sql = "CREATE TABLE `localplay_upnp` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(128) COLLATE $collation NOT NULL, `owner` INT(11) NOT NULL, `url` VARCHAR(255) COLLATE $collation NOT NULL) ENGINE = $engine DEFAULT CHARSET=$charset COLLATE=$collation";
        Dba::query($sql);

        // Add an internal preference for the users current active instance
        Preference::insert('upnp_active', T_('UPnP Active Instance'), 0, AccessLevelEnum::USER->value, 'integer', 'internal', 'upnp');

        return true;
    }

    /**
     * uninstall
     * This removes the Localplay controller
     */
    public function uninstall(): bool
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
     */
    public function add_instance($data): void
    {
        $sql     = "INSERT INTO `localplay_upnp` (`name`, `url`, `owner`) VALUES (?, ?, ?)";
        $user_id = (Core::get_global('user') instanceof User)
            ? Core::get_global('user')->id
            : -1;

        Dba::query($sql, [$data['name'] ?? null, $data['url'] ?? null, $user_id]);
    }

    /**
     * delete_instance
     * This takes a UID and deletes the instance in question
     * @param int $uid
     */
    public function delete_instance($uid): void
    {
        $sql = "DELETE FROM `localplay_upnp` WHERE `id` = ?";
        Dba::query($sql, [$uid]);
    }

    /**
     * get_instances
     * This returns a key'd array of the instance information with
     * [UID]=>[NAME]
     */
    public function get_instances(): array
    {
        $sql        = "SELECT * FROM `localplay_upnp` ORDER BY `name`";
        $db_results = Dba::query($sql);
        $results    = [];

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['id']] = $row['name'];
        }

        return $results;
    }

    /**
     * update_instance
     * This takes an ID and an array of data and updates the instance specified
     * @param int $uid
     * @param array $data
     */
    public function update_instance($uid, $data): void
    {
        $sql = "UPDATE `localplay_upnp` SET `url` = ?, `name` = ? WHERE `id` = ?";
        Dba::query($sql, [$data['url'], $data['name'], $uid]);
    }

    /**
     * instance_fields
     * This returns a key'd array of [NAME]=>array([DESCRIPTION]=>VALUE,[TYPE]=>VALUE) for the
     * fields so that we can on-the-fly generate a form
     */
    public function instance_fields(): array
    {
        $fields         = [];
        $fields['name'] = ['description' => T_('Instance Name'), 'type' => 'text'];
        $fields['url']  = ['description' => T_('URL'), 'type' => 'url'];

        return $fields;
    }

    /**
     * get_instance
     * This returns a single instance and all it's variables
     * @return array
     */
    public function get_instance(?string $instance = ''): array
    {
        $instance   = (is_numeric($instance)) ? (int) $instance : (int) AmpConfig::get('upnp_active', 0);
        $sql        = ($instance > 0) ? "SELECT * FROM `localplay_upnp` WHERE `id` = ?" : "SELECT * FROM `localplay_upnp`";
        $db_results = ($instance > 0) ? Dba::query($sql, [$instance]) : Dba::query($sql);

        return Dba::fetch_assoc($db_results);
    }

    /**
     * set_active_instance
     * This sets the specified instance as the 'active' one
     */
    public function set_active_instance(int $uid): bool
    {
        $user = Core::get_global('user');
        if (!$user instanceof User) {
            return false;
        }
        Preference::update('upnp_active', $user->id, $uid);
        AmpConfig::set('upnp_active', $uid, true);
        debug_event('upnp.controller', 'set_active_instance userid: ' . $user->id, 5);

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
     * @param Stream_Url $url
     */
    public function add_url(Stream_Url $url): bool
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
     * @param $object_id
     */
    public function delete_track($object_id): bool
    {
        if (!$this->_upnp) {
            return false;
        }

        $this->_upnp->PlaylistRemove($object_id);

        return true;
    }

    /**
     * clear_playlist
     * This deletes the entire UPnP playlist.
     */
    public function clear_playlist(): bool
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
    public function play(): bool
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
    public function pause(): bool
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
    public function stop(): bool
    {
        if (!$this->_upnp) {
            return false;
        }

        return $this->_upnp->Stop();
    }

    /**
     * skip
     * This tells UPnP to skip to the specified song
     */
    public function skip(int $track_id): bool
    {
        if (!$this->_upnp) {
            return false;
        }

        $this->_upnp->Skip($track_id);

        return true;
    }

    /**
     * next
     * This just tells UPnP to skip to the next song
     */
    public function next(): bool
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
    public function prev(): bool
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
     */
    public function volume($volume): bool
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
    public function volume_up(): bool
    {
        if (!$this->_upnp) {
            return false;
        }

        return $this->_upnp->VolumeUp();
    }

    /**
     * This tells UPnP to decrease the volume
     */
    public function volume_down(): bool
    {
        if (!$this->_upnp) {
            return false;
        }

        return $this->_upnp->VolumeDown();
    }

    /**
     * repeat
     * This tells UPnP to set the repeating the playlist (i.e. loop) to either on or off
     */
    public function repeat(bool $state): bool
    {
        debug_event('upnp.controller', 'repeat: ' . $state, 5);

        if (!$this->_upnp) {
            return false;
        }

        $this->_upnp->Repeat($state);

        return true;
    }

    /**
     * random
     * This tells UPnP to turn on or off the playing of songs from the playlist in random order
     */
    public function random(bool $state): bool
    {
        debug_event('upnp.controller', 'random: ' . $state, 5);

        if (!$this->_upnp) {
            return false;
        }

        $this->_upnp->PlayShuffle($state);

        return true;
    }

    /**
     * get
     * This functions returns an array containing information about
     * The songs that UPnP currently has in it's playlist. This must be
     * done in a standardized fashion
     */
    public function get(): array
    {
        debug_event('upnp.controller', 'get', 5);

        if (!$this->_upnp) {
            return [];
        }

        $playlist = $this->_upnp->GetPlaylistItems();

        $results = [];
        $idx     = 1;
        foreach ($playlist as $item) {
            $data          = [];
            $data['name']  = null;
            $data['link']  = $item['link'] ?? '';
            $data['id']    = $idx;
            $data['track'] = $idx;

            $url_data = Stream_Url::parse($data['link']);
            if (array_key_exists('id', $url_data)) {
                $song = new Song($url_data['id']);
                if ($song->isNew() === false) {
                    $data['name'] = $song->get_artist_fullname() . ' - ' . $song->title;
                }
            }
            if (empty($data['name'])) {
                $data['name'] = $item['name'];
            }

            $results[] = $data;
            $idx++;
        }

        return $results;
    }

    /**
     * status
     * This returns bool/int values for features, loop, repeat and any other features that this Localplay method supports.
     * This works as in requesting the UPnP properties
     */
    public function status(): array
    {
        debug_event('upnp.controller', 'status', 5);
        $array = [];
        if (!$this->_upnp) {
            return $array;
        }

        $item = $this->_upnp->GetCurrentItem();

        $array['state']       = $this->_upnp->GetState();
        $array['volume']      = $this->_upnp->GetVolume();
        $array['repeat']      = false;
        $array['random']      = false;
        $array['track']       = $item['link'] ?? '';
        $array['track_title'] = $item['name'] ?? '';

        $url_data = Stream_Url::parse($array['track']);
        if (array_key_exists('id', $url_data)) {
            $song = new Song($url_data['id']);
            if ($song->isNew() === false) {
                $array['track_artist'] = $song->get_artist_fullname();
                $array['track_album']  = $song->get_album_fullname();
            }
        }

        return $array;
    }

    /**
     * connect
     * This functions creates the connection to UPnP and returns
     * a boolean value for the status, to save time this handle
     * is stored in this class
     */
    public function connect(): bool
    {
        $options = self::get_instance();
        if (isset($options['name']) && isset($options['url'])) {
            debug_event('upnp.controller', 'Trying to connect UPnP instance ' . $options['name'] . ' ( ' . $options['url'] . ' )', 5);
            $this->_upnp = new UPnPPlayer($options['name'], $options['url']);
            debug_event('upnp.controller', 'Connected.', 5);

            return true;
        }

        return false;
    }
}
