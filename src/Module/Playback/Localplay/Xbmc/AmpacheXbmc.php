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

namespace Ampache\Module\Playback\Localplay\Xbmc;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Playback\Localplay\localplay_controller;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use XBMC_RPC_ConnectionException;
use XBMC_RPC_Exception;
use XBMC_RPC_HTTPClient;

/**
 * This is the class for the XBMC Localplay method to remote control
 * a XBMC Instance
 */
class AmpacheXbmc extends localplay_controller
{
    /* Variables */
    private string $version     = '000001';
    private string $description = 'Controls a XBMC instance';

    /* Constructed variables */
    private $_xbmc;
    // Always use player 0 for now
    private $_playerId = 0;
    // Always use playlist 0 for now
    private $_playlistId = 0;

    /**
     * get_description
     * This returns the description of this Localplay method
     */
    public function get_description(): string
    {
        return $this->description;
    }

    /**
     * get_version
     * This returns the current version
     */
    public function get_version(): string
    {
        return $this->version;
    }

    /**
     * is_installed
     * This returns true or false if xbmc controller is installed
     */
    public function is_installed(): bool
    {
        $sql        = "SHOW TABLES LIKE 'localplay_xbmc'";
        $db_results = Dba::query($sql);

        return (Dba::num_rows($db_results) > 0);
    }

    /**
     * install
     * This function installs the XBMC Localplay controller
     */
    public function install(): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = (AmpConfig::get('database_engine', 'InnoDB'));

        $sql = "CREATE TABLE `localplay_xbmc` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(128) COLLATE $collation NOT NULL, `owner` INT(11) NOT NULL, `host` VARCHAR(255) COLLATE $collation NOT NULL, `port` INT(11) UNSIGNED NOT NULL, `user` VARCHAR(255) COLLATE $collation NOT NULL, `pass` VARCHAR(255) COLLATE $collation NOT NULL) ENGINE = $engine DEFAULT CHARSET=$charset COLLATE=$collation";
        Dba::query($sql);

        // Add an internal preference for the users current active instance
        Preference::insert('xbmc_active', T_('XBMC Active Instance'), 0, AccessLevelEnum::USER->value, 'integer', 'internal', 'xbmc');

        return true;
    }

    /**
     * uninstall
     * This removes the Localplay controller
     */
    public function uninstall(): bool
    {
        $sql = "DROP TABLE `localplay_xbmc`";
        Dba::query($sql);

        // Remove the pref we added for this
        Preference::delete('xbmc_active');

        return true;
    }

    /**
     * add_instance
     * This takes key'd data and inserts a new xbmc instance
     * @param array $data
     */
    public function add_instance($data): void
    {
        $sql     = "INSERT INTO `localplay_xbmc` (`name`, `host`, `port`, `user`, `pass`, `owner`) VALUES (?, ?, ?, ?, ?, ?)";
        $user_id = Core::get_global('user') instanceof User
            ? Core::get_global('user')->id
            : -1;

        Dba::query($sql, [$data['name'] ?? null, $data['host'] ?? null, $data['port'] ?? null, $data['user'] ?? null, $data['pass'] ?? null, $user_id]);
    }

    /**
     * delete_instance
     * This takes a UID and deletes the instance in question
     * @param int $uid
     */
    public function delete_instance($uid): void
    {
        $sql = "DELETE FROM `localplay_xbmc` WHERE `id` = ?";
        Dba::query($sql, [$uid]);
    }

    /**
     * get_instances
     * This returns a key'd array of the instance information with
     * [UID]=>[NAME]
     */
    public function get_instances(): array
    {
        $sql        = "SELECT * FROM `localplay_xbmc` ORDER BY `name`";
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
        $sql = "UPDATE `localplay_xbmc` SET `host` = ?, `port` = ?, `name` = ?, `user` = ?, `pass` = ? WHERE `id` = ?";
        Dba::query($sql, [$data['host'], $data['port'], $data['name'], $data['user'], $data['pass'], $uid]);
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
        $fields['host'] = ['description' => T_('Hostname'), 'type' => 'text'];
        $fields['port'] = ['description' => T_('Port'), 'type' => 'number'];
        $fields['user'] = ['description' => T_('Username'), 'type' => 'text'];
        $fields['pass'] = ['description' => T_('Password'), 'type' => 'password'];

        return $fields;
    }

    /**
     * get_instance
     * This returns a single instance and all it's variables
     */
    public function get_instance(?string $instance = ''): array
    {
        $instance   = (is_numeric($instance)) ? (int) $instance : (int) AmpConfig::get('xbmc_active', 0);
        $sql        = ($instance > 0) ? "SELECT * FROM `localplay_xbmc` WHERE `id` = ?" : "SELECT * FROM `localplay_xbmc`";
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
        Preference::update('xbmc_active', $user->id, $uid);
        AmpConfig::set('xbmc_active', $uid, true);
        debug_event('xbmc.controller', 'set_active_instance: ' . $uid . ' ' . $user->id, 5);

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
        if (!$this->_xbmc) {
            return false;
        }

        try {
            $this->_xbmc->Playlist->Add(
                [
                    'playlistid' => $this->_playlistId,
                    'item' => ['file' => $url->url]
                ]
            );

            return true;
        } catch (XBMC_RPC_Exception $error) {
            debug_event(self::class, 'add_url failed: ' . $error->getMessage(), 1);

            return false;
        }
    }

    /**
     * delete_track
     * Delete a track from the xbmc playlist
     * @param $object_id
     */
    public function delete_track($object_id): bool
    {
        if (!$this->_xbmc) {
            return false;
        }

        try {
            $this->_xbmc->Playlist->Remove(
                [
                    'playlistid' => $this->_playlistId,
                    'position' => $object_id
                ]
            );

            return true;
        } catch (XBMC_RPC_Exception $error) {
            debug_event(self::class, 'delete_track failed: ' . $error->getMessage(), 1);

            return false;
        }
    }

    /**
     * clear_playlist
     * This deletes the entire xbmc playlist.
     */
    public function clear_playlist(): bool
    {
        if (!$this->_xbmc) {
            return false;
        }

        try {
            $this->stop();

            $clear = $this->_xbmc->Playlist->Clear(
                ['playlistid' => $this->_playlistId]
            );

            //we have a delay between the stop/clear playlist in kodi and kodi notify it in the status, so, we add a mininal sleep
            sleep(1);

            return true;
        } catch (XBMC_RPC_Exception $error) {
            debug_event(self::class, 'clear_playlist failed: ' . $error->getMessage(), 1);

            return false;
        }
    }

    /**
     * play
     * This just tells xbmc to start playing, it does not
     * take any arguments
     */
    public function play(): bool
    {
        if (!$this->_xbmc) {
            return false;
        }

        try {
            // XBMC requires to load a playlist to play. We don't know if this play is after a new playlist or after pause
            // So we get current status
            $status = $this->status();

            if ($status['state'] == 'pause') {
                $this->_xbmc->Player->PlayPause(
                    [
                        'playerid' => $this->_playerId,
                        'play' => true
                    ]
                );
            } elseif ($status['state'] == 'stop') {
                $this->_xbmc->Player->Open(['item' => ['playlistid' => $this->_playlistId]]);

            }

            return true;
        } catch (XBMC_RPC_Exception $error) {
            debug_event(self::class, 'play failed: ' . $error->getMessage(), 1);

            return false;
        }
    }

    /**
     * pause
     * This tells XBMC to pause the current song
     */
    public function pause(): bool
    {
        if (!$this->_xbmc) {
            return false;
        }

        $play = false;

        $status = $this->status();
        // stop if is playing, restart if pausing
        if ($status['state'] == 'pause') {
            $play = true;
        }

        try {
            $this->_xbmc->Player->PlayPause(
                [
                    'playerid' => $this->_playerId,
                    'play' => $play
                ]
            );

            return true;
        } catch (XBMC_RPC_Exception $error) {
            debug_event(self::class, 'pause failed, is the player started? ' . $error->getMessage(), 1);

            return false;
        }
    }

    /**
     * stop
     * This just tells XBMC to stop playing, it does not take
     * any arguments
     */
    public function stop(): bool
    {
        if (!$this->_xbmc) {
            return false;
        }

        try {
            $this->_xbmc->Player->Stop(
                ['playerid' => $this->_playerId]
            );

            return true;
        } catch (XBMC_RPC_Exception $error) {
            debug_event(self::class, 'stop failed, is the player started? ' . $error->getMessage(), 1);

            return false;
        }
    }

    /**
     * skip
     * This tells XBMC to skip to the specified song
     */
    public function skip(int $track_id): bool
    {
        if (!$this->_xbmc) {
            return false;
        }

        // force integer, some apps sends string (subsonic jukebox)
        $track_id = (int)$track_id;

        try {
            $this->_xbmc->Player->GoTo(
                [
                    'playerid' => $this->_playerId,
                    'to' => $track_id
                ]
            );

            return true;
        } catch (XBMC_RPC_Exception $error) {
            debug_event(self::class, 'skip failed, is the player started?: ' . $error->getMessage(), 1);

            return false;
        }
    }

    /**
     * This tells XBMC to increase the volume
     */
    public function volume_up(): bool
    {
        if (!$this->_xbmc) {
            return false;
        }

        try {
            $this->_xbmc->Application->SetVolume(
                ['volume' => 'increment']
            );

            return true;
        } catch (XBMC_RPC_Exception $error) {
            debug_event(self::class, 'volume_up failed: ' . $error->getMessage(), 1);

            return false;
        }
    }

    /**
     * This tells XBMC to decrease the volume
     */
    public function volume_down(): bool
    {
        if (!$this->_xbmc) {
            return false;
        }

        try {
            $this->_xbmc->Application->SetVolume(
                ['volume' => 'decrement']
            );

            return true;
        } catch (XBMC_RPC_Exception $error) {
            debug_event(self::class, 'volume_down failed: ' . $error->getMessage(), 1);

            return false;
        }
    }

    /**
     * next
     * This just tells xbmc to skip to the next song
     */
    public function next(): bool
    {
        if (!$this->_xbmc) {
            return false;
        }

        try {
            $this->_xbmc->Player->GoTo(
                [
                    'playerid' => $this->_playerId,
                    'to' => 'next'
                ]
            );

            return true;
        } catch (XBMC_RPC_Exception $error) {
            debug_event(self::class, 'next failed, is the player started? ' . $error->getMessage(), 1);

            return false;
        }
    }

    /**
     * prev
     * This just tells xbmc to skip to the prev song
     */
    public function prev(): bool
    {
        if (!$this->_xbmc) {
            return false;
        }

        try {
            $this->_xbmc->Player->GoTo(
                [
                    'playerid' => $this->_playerId,
                    'to' => 'previous'
                ]
            );

            return true;
        } catch (XBMC_RPC_Exception $error) {
            debug_event(self::class, 'prev failed, is the player started? ' . $error->getMessage(), 1);

            return false;
        }
    }

    /**
     * volume
     * This tells XBMC to set the volume to the specified amount
     * @param $volume
     */
    public function volume($volume): bool
    {
        if (!$this->_xbmc) {
            return false;
        }

        try {
            $this->_xbmc->Application->SetVolume(
                ['volume' => $volume]
            );

            return true;
        } catch (XBMC_RPC_Exception $error) {
            debug_event(self::class, 'volume failed: ' . $error->getMessage(), 1);

            return false;
        }
    }

    /**
     * repeat
     * This tells XBMC to set the repeating the playlist (i.e. loop) to either on or off
     */
    public function repeat(bool $state): bool
    {
        if (!$this->_xbmc) {
            return false;
        }

        try {
            $this->_xbmc->Player->SetRepeat(
                [
                    'playerid' => $this->_playerId,
                    'repeat' => ($state ? 'all' : 'off')
                ]
            );

            return true;
        } catch (XBMC_RPC_Exception $error) {
            debug_event(self::class, 'repeat failed, is the player started? ' . $error->getMessage(), 1);

            return false;
        }
    }

    /**
     * random
     * This tells XBMC to turn on or off the playing of songs from the playlist in random order
     */
    public function random(bool $state): bool
    {
        if (!$this->_xbmc) {
            return false;
        }

        try {
            $this->_xbmc->Player->SetShuffle(
                [
                    'playerid' => $this->_playerId,
                    'shuffle' => $state
                ]
            );

            return true;
        } catch (XBMC_RPC_Exception $error) {
            debug_event(self::class, 'random failed, is the player started? ' . $error->getMessage(), 1);

            return false;
        }
    }

    /**
     * get
     * This functions returns an array containing information about
     * The songs that XBMC currently has in it's playlist. This must be
     * done in a standardized fashion
     */
    public function get(): array
    {
        $results = [];
        if (!$this->_xbmc) {
            return $results;
        }

        try {
            $playlist = $this->_xbmc->Playlist->GetItems(
                [
                    'playlistid' => $this->_playlistId,
                    'properties' => ['file']
                ]
            );

            for ($i = $playlist['limits']['start']; $i < $playlist['limits']['end']; ++$i) {
                $item = $playlist['items'][$i];

                $data          = [];
                $data['link']  = $item['file'];
                $data['id']    = $i;
                $data['track'] = $i + 1;

                $url_data = $this->parse_url(rawurldecode($data['link']));
                if ($url_data != null) {
                    $data['oid'] = $url_data['oid'];
                    $song        = new Song($data['oid']);
                    if ($song->isNew() === false) {
                        $data['name'] = $song->get_artist_fullname() . ' - ' . $song->title;
                    }
                }
                if (!isset($data['name'])) {
                    $data['name'] = $item['label'];
                }
                $results[] = $data;
            }
        } catch (XBMC_RPC_Exception $error) {
            debug_event(self::class, 'get failed: ' . $error->getMessage(), 1);
        }

        return $results;
    }

    /**
     * status
     * This returns bool/int values for features, loop, repeat and any other features that this Localplay method supports.
     * This works as in requesting the xbmc properties
     */
    public function status(): array
    {
        $array = [];
        if (!$this->_xbmc) {
            return $array;
        }

        try {
            $appprop = $this->_xbmc->Application->GetProperties(
                ['properties' => ['volume']]
            );
            $array['volume']       = (int)($appprop['volume']);
            $array['track_title']  = '';
            $array['track_artist'] = '';
            $array['track_album']  = '';

            try {
                // We assume it's playing. Pause detection with player speed
                $array['state'] = 'play';

                $speed = $this->_xbmc->Player->GetProperties(
                    [
                        'playerid' => $this->_playerId,
                        'properties' => ['speed']
                    ]
                );

                //speed == 0, pause
                if ($speed['speed'] == 0) {
                    $array['state'] = 'pause';
                }

                // So we get active players, if no exists active player, set the status to stop and return
                // stop has to check afret pause, cause in stop status speed = 0
                $xbmc_players = $this->_xbmc->Player->GetActivePlayers();
                if (empty($xbmc_players)) {
                    $array['state'] = 'stop';
                }

                $currentplay = $this->_xbmc->Player->GetItem(
                    [
                        'playerid' => $this->_playerId,
                        'properties' => ['file']
                    ]
                );

                $playprop = $this->_xbmc->Player->GetProperties(
                    [
                        'playerid' => $this->_playerId,
                        'properties' => ['repeat', 'shuffled']
                    ]
                );
                $array['repeat'] = ($playprop['repeat'] != "off");
                $array['random'] = (strtolower($playprop['shuffled']) == 1);

                $playposition = $this->_xbmc->Player->GetProperties(
                    [
                        'playerid' => $this->_playerId,
                        'properties' => ['position']
                    ]
                );

                $array['track'] = $playposition['position'] + 1;

                $playlist_item  = rawurldecode($currentplay['item']['file']);

                $url_data = $this->parse_url($playlist_item);
                $oid      = array_key_exists('oid', $url_data) ? $url_data['oid'] : '';
                if (!empty($oid)) {
                    $song = new Song($oid);
                    if ($song->isNew() === false) {
                        $array['track_title']  = $song->title;
                        $array['track_artist'] = $song->get_artist_fullname();
                        $array['track_album']  = $song->get_album_fullname();
                    }
                }
            } catch (XBMC_RPC_Exception $error) {
                debug_event(self::class, 'get current item failed, player probably stopped. ' . $error->getMessage(), 1);
                $array['state'] = 'stop';
            }
        } catch (XBMC_RPC_Exception $error) {
            debug_event(self::class, 'status failed: ' . $error->getMessage(), 1);
        }

        return $array;
    }

    /**
     * connect
     * This functions creates the connection to XBMC and returns
     * a boolean value for the status, to save time this handle
     * is stored in this class
     */
    public function connect(): bool
    {
        $options = self::get_instance();
        try {
            debug_event(self::class, 'Trying to connect xbmc instance ' . $options['host'] . ':' . $options['port'] . '.', 5);
            $this->_xbmc = new XBMC_RPC_HTTPClient($options);
            debug_event(self::class, 'Connected.', 5);

            return true;
        } catch (XBMC_RPC_ConnectionException $error) {
            debug_event(self::class, 'xbmc connection failed: ' . $error->getMessage(), 1);

            return false;
        }
    }
}
