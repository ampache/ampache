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

namespace Ampache\Module\Playback\Localplay\Mpd;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\Democratic;
use Ampache\Module\Playback\Localplay\localplay_controller;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;

/**
 * AmpacheMpd Class
 *
 * the Ampache Mpd Controller, this is the glue between
 * the MPD class and the Ampache Localplay class
 */
class AmpacheMpd extends localplay_controller
{
    public bool $block_clear = false;

    private string $version = '000003';

    private string $description = 'Controls an instance of MPD';

    private int $_add_count = 0;

    /* Constructed variables */
    private $_mpd;

    /**
     * get_description
     * Returns the description
     */
    public function get_description(): string
    {
        return $this->description;
    }

    /**
     * get_version
     * This returns the version information
     */
    public function get_version(): string
    {
        return $this->version;
    }

    /**
     * is_installed
     * This returns true or false if MPD controller is installed
     */
    public function is_installed(): bool
    {
        $sql        = "SHOW TABLES LIKE 'localplay_mpd'";
        $db_results = Dba::read($sql);

        return (Dba::num_rows($db_results) > 0);
    }

    /**
     * install
     * This function installs the MPD Localplay controller
     */
    public function install(): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = (AmpConfig::get('database_engine', 'InnoDB'));
        /* We need to create the MPD table */
        $sql = <<<SQL
            CREATE TABLE `localplay_mpd` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(128) COLLATE $collation NOT NULL,
                `owner` INT(11) NOT NULL,
                `host` VARCHAR(255) COLLATE $collation NOT NULL,
                `port` INT(11) UNSIGNED NOT NULL DEFAULT '6600',
                `password` VARCHAR(255) COLLATE $collation NOT NULL,
                `access` SMALLINT(4) UNSIGNED NOT NULL DEFAULT '0'
            ) ENGINE = $engine DEFAULT CHARSET=$charset COLLATE=$collation
            SQL;
        Dba::query($sql);

        // Add an internal preference for the users current active instance
        Preference::insert('mpd_active', T_('MPD Active Instance'), 0, AccessLevelEnum::USER->value, 'integer', 'internal', 'mpd');

        return true;
    }

    /**
     * uninstall
     * This removes the Localplay controller
     */
    public function uninstall(): bool
    {
        $sql = "DROP TABLE `localplay_mpd`";
        Dba::write($sql);

        Preference::delete('mpd_active');

        return true;
    }

    /**
     * add_instance
     * This takes key'd data and inserts a new MPD instance
     * @param array{
     *     name?: string,
     *     host?: string,
     *     port?: string,
     *     password?: string,
     * } $data
     */
    public function add_instance(array $data): void
    {
        $sql     = "INSERT INTO `localplay_mpd` (`name`, `host`, `port`, `password`, `owner`) VALUES (?, ?, ?, ?, ?)";
        $user_id = (Core::get_global('user') instanceof User)
            ? Core::get_global('user')->id
            : -1;

        Dba::write($sql, [$data['name'] ?? null, $data['host'] ?? null, $data['port'] ?? null, $data['password'] ?? null, $user_id]);
    }

    /**
     * delete_instance
     * This takes a UID and deletes the instance in question
     */
    public function delete_instance(int $uid): void
    {
        $sql = "DELETE FROM `localplay_mpd` WHERE `id` = ?";
        Dba::write($sql, [$uid]);
    }

    /**
     * get_instances
     * This returns a key'd array of the instance information with
     * [UID]=>[NAME]
     * @return string[]
     */
    public function get_instances(): array
    {
        $sql = "SELECT * FROM `localplay_mpd` ORDER BY `name`";

        $db_results = Dba::read($sql);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[(int)$row['id']] = $row['name'];
        }

        return $results;
    }

    /**
     * get_instance
     * This returns the specified instance and all it's pretty variables
     * If no instance is passed current is used
     * @param string|null $instance
     * @return array{
     *     id?: int,
     *     name?: string,
     *     owner?: int,
     *     host?: string,
     *     port?: int,
     *     password?: string,
     *     access?: int
     * }
     */
    public function get_instance(?string $instance = ''): array
    {
        $instance   = (is_numeric($instance)) ? (int) $instance : (int) AmpConfig::get('mpd_active', 0);
        $sql        = ($instance > 0) ? "SELECT * FROM `localplay_mpd` WHERE `id` = ?" : "SELECT * FROM `localplay_mpd`";
        $db_results = ($instance > 0) ? Dba::query($sql, [$instance]) : Dba::query($sql);

        if ($row = Dba::fetch_assoc($db_results)) {
            return [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'owner' => (int)$row['owner'],
                'host' => $row['host'],
                'port' => (int)$row['port'],
                'password' => $row['password'],
                'access' => (int)$row['access'],
            ];
        }

        return [];
    }

    /**
     * update_instance
     * This takes an ID and an array of data and updates the instance specified
     * @param int $uid
     * @param array{
     *     host?: string,
     *     port?: string,
     *     name: string,
     *     password: string,
     * } $data
     */
    public function update_instance(int $uid, array $data): void
    {
        $sql  = "UPDATE `localplay_mpd` SET `host` = ?, `port` = ?, `name` = ?, `password` = ? WHERE `id` = ?;";
        Dba::write($sql, [$data['host'] ?? '127.0.0.1', $data['port'] ?? '6600', $data['name'], $data['password'], $uid]);
    }

    /**
     * instance_fields
     * This returns a key'd array of [NAME]=>array([DESCRIPTION]=>VALUE,[TYPE]=>VALUE) for the
     * fields so that we can on-the-fly generate a form
     * @return array<
     *     string,
     *     array{description: string, type: string}
     * >
     */
    public function instance_fields(): array
    {
        $fields             = [];
        $fields['name']     = ['description' => T_('Instance Name'), 'type' => 'text'];
        $fields['host']     = ['description' => T_('Hostname'), 'type' => 'text'];
        $fields['port']     = ['description' => T_('Port'), 'type' => 'number'];
        $fields['password'] = ['description' => T_('Password'), 'type' => 'password'];

        return $fields;
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
        Preference::update('mpd_active', $user->id, $uid);
        AmpConfig::set('mpd_active', $uid, true);
        debug_event('mdp.controller', 'set_active_instance: ' . $uid . ' ' . $user->id, 5);

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
     * add_url
     */
    public function add_url(Stream_Url $url): bool
    {
        // If we haven't added anything then maybe we should clear the playlist.
        if ($this->_add_count < 1) {
            $this->_mpd->RefreshInfo();
            if ($this->block_clear === false &&
                $this->_mpd->status['state'] == mpd::STATE_STOPPED
            ) {
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
     */
    public function delete_track(int $object_id): bool
    {
        return $this->_mpd->PLRemove($object_id) !== false;
    }

    /**
     * clear_playlist
     * This deletes the entire MPD playlist
     */
    public function clear_playlist(): bool
    {
        $this->_add_count = 0;

        return $this->_mpd->PLClear() !== false;
    }

    /**
     * play
     * This just tells MPD to start playing, it does not
     * take any arguments
     */
    public function play(): bool
    {
        return $this->_mpd->Play() !== false;
    }

    /**
     * stop
     * This just tells MPD to stop playing, it does not take
     * any arguments
     */
    public function stop(): bool
    {
        return $this->_mpd->Stop() !== false;
    }

    /**
     * skip
     * This tells MPD to skip to the specified song
     */
    public function skip(int $track_id): bool
    {
        if (!$this->_mpd->SkipTo($track_id)) {
            return false;
        }
        sleep(2);
        $this->stop();
        sleep(2);
        $this->play();

        return true;
    }

    /**
     * This tells MPD to increase the volume by 5
     */
    public function volume_up(): bool
    {
        return $this->_mpd->AdjustVolume('5') !== false;
    }

    /**
     * This tells MPD to decrease the volume by 5
     */
    public function volume_down(): bool
    {
        return $this->_mpd->AdjustVolume('-5') !== false;
    }

    /**
     * next
     * This just tells MPD to skip to the next song
     */
    public function next(): bool
    {
        return $this->_mpd->Next() !== false;
    }

    /**
     * prev
     * This just tells MPD to skip to the prev song
     */
    public function prev(): bool
    {
        return $this->_mpd->Previous() !== false;
    }

    /**
     * pause
     * This tells MPD to pause the current song
     */
    public function pause(): bool
    {
        return $this->_mpd->Pause() !== false;
    }

    /**
     * volume
     * This tells MPD to set the volume to the parameter
     */
    public function volume($volume): bool
    {
        return $this->_mpd->SetVolume($volume) !== false;
    }

    /**
     * repeat
     * This tells MPD to set the repeating the playlist (i.e. loop) to either on or off.
     */
    public function repeat(bool $state): bool
    {
        return $this->_mpd->SetRepeat($state) !== false;
    }

    /**
     * random
     * This tells MPD to turn on or off the playing of songs from the
     * playlist in random order.
     */
    public function random(bool $state): bool
    {
        return $this->_mpd->SetRandom($state) !== false;
    }

    /**
     * move
     * This tells MPD to move a song
     * @param $source
     * @param $destination
     * @return bool|string
     */
    public function move($source, $destination)
    {
        return $this->_mpd->PLMoveTrack($source, $destination);
    }

    /**
     * get_songs
     * This functions returns an array containing information about
     * the songs that MPD currently has in its playlist. This must be
     * done in a standardized fashion
     * @return array<int, array{
     *     id: int,
     *     raw: string,
     *     oid?: int,
     *     name?: string,
     *     link?: string,
     *     track: int,
     * }>
     */
    public function get(): array
    {
        if (!$this->_mpd || !$this->_mpd->status) {
            return [];
        }
        // If we don't have the playlist yet, pull it
        if (!isset($this->_mpd->playlist)) {
            $this->_mpd->RefreshInfo();
        }

        /* Get the Current Playlist */
        $playlist = $this->_mpd->playlist;
        $results  = [];
        // if there isn't anything to return don't do it
        if (empty($playlist)) {
            return $results;
        }

        foreach ($playlist as $entry) {
            $data = [];

            /* Required Elements */
            $data['id']  = $entry['Pos'];
            $data['raw'] = $entry['file'];

            $url_data = $this->parse_url($entry['file']);
            $url_key  = $url_data['primary_key'] ?? '';

            switch ($url_key) {
                case 'oid':
                    $data['oid']  = (int)$url_data['oid'];
                    $song         = new Song($data['oid']);
                    $data['name'] = $song->get_fullname() . ' - ' . $song->get_album_fullname($song->album, true) . ' - ' . $song->get_artist_fullname();
                    $data['link'] = $song->get_f_link();
                    break;
                case 'demo_id':
                    $democratic   = new Democratic($url_data['demo_id']);
                    $data['name'] = T_('Democratic') . ' - ' . $democratic->name;
                    $data['link'] = '';
                    break;
                case 'random':
                    $className    = ObjectTypeToClassNameMapper::map($url_data['random_type']);
                    /** @var library_item $random */
                    $random       = new $className($url_data['random_id']);
                    $data['name'] = T_('Random') . ' - ' . scrub_out($random->get_fullname());
                    $data['link'] = '';
                    break;
                default:
                    // If we don't know it, look up by filename
                    $filename = Dba::escape($entry['file']);
                    $sql      = "SELECT `id`, 'song' AS `type` FROM `song` WHERE `file` LIKE ? UNION ALL SELECT `id`, 'live_stream' AS `type` FROM `live_stream` WHERE `url` = ? ";

                    $db_results = Dba::read($sql, ['%' . $filename, $filename]);
                    if ($row = Dba::fetch_assoc($db_results)) {
                        $className = ObjectTypeToClassNameMapper::map($row['type']);
                        /** @var Song|Live_Stream $media */
                        $media = new $className($row['id']);
                        switch ($row['type']) {
                            case 'song':
                                /** @var Song $media */
                                $data['name'] = $media->get_fullname() . ' - ' . $media->get_album_fullname() . ' - ' . $media->get_artist_fullname();
                                $data['link'] = $media->get_f_link();
                                break;
                            case 'live_stream':
                                /** @var Live_Stream $media */
                                $site_url     = ($media->site_url) ? '(' . $media->site_url . ')' : '';
                                $data['name'] = "$media->name $site_url";
                                $data['link'] = (string)$media->site_url;
                                break;
                        } // end switch on type
                    } else {
                        $title_string = (isset($entry['Title']) && isset($entry['Album']) && isset($entry['Artist']))
                            ? $entry['Title'] . ' - ' . $entry['Album'] . ' - ' . $entry['Artist']
                            : T_('Unknown');
                        $data['name'] = $title_string;
                        $data['link'] = '';
                    }
                    break;
            } // end switch on primary key type

            /* Optional Elements */
            $data['track'] = $entry['Pos'] + 1;

            $results[] = $data;
        } // foreach playlist items

        return $results;
    }

    /**
     * get_status
     * This returns bool/int values for features, loop, repeat and any other features that this Localplay method supports.
     */
    public function status(): array
    {
        $array = [];
        if (!$this->_mpd || !$this->_mpd->status) {
            return $array;
        }
        $track = $this->_mpd->status['song'] ?? 0;

        /* Construct the Array */
        $array['state']        = $this->_mpd->status['state'];
        $array['volume']       = $this->_mpd->status['volume'];
        $array['repeat']       = $this->_mpd->status['repeat'];
        $array['random']       = $this->_mpd->status['random'];
        $array['track']        = $track + 1;
        $array['track_title']  = '';
        $array['track_artist'] = '';
        $array['track_album']  = '';

        $playlist_item = [];
        $url_data      = [];
        if (is_array($this->_mpd->playlist) && array_key_exists($track, $this->_mpd->playlist)) {
            $playlist_item = $this->_mpd->playlist[$track];
            $url_data      = $this->parse_url($playlist_item['file']);
        }

        debug_event('mdp.controller', 'Status result. Current song (' . $track . ') info: ' . json_encode($playlist_item), 5);

        if (count($url_data) > 0 && array_key_exists('oid', $url_data) && !empty($url_data['oid'])) {
            $song = new Song($url_data['oid']);
            if ($song->isNew()) {
                $array['track_title']  = T_('Unknown');
                $array['track_artist'] = T_('Unknown');
                $array['track_album']  = T_('Unknown');
            } else {
                $array['track_title']  = $song->title;
                $array['track_artist'] = $song->get_artist_fullname();
                $array['track_album']  = $song->get_album_fullname();
            }
        } elseif (!empty($playlist_item)) {
            if (!empty($playlist_item['Title'])) {
                $array['track_title'] = $playlist_item['Title'];
            } elseif (!empty($playlist_item['Name'])) {
                $array['track_title'] = $playlist_item['Name'];
            } else {
                $array['track_title'] = $playlist_item['file'] ?? '';
            }

            if (!empty($playlist_item['Artist'])) {
                $array['track_artist'] = $playlist_item['Artist'];
            }

            if (!empty($playlist_item['Album'])) {
                $array['track_album'] = $playlist_item['Album'];
            }
        }

        return $array;
    }

    /**
     * connect
     * This functions creates the connection to MPD and returns
     * a boolean value for the status, to save time this handle
     * is stored in this class
     */
    public function connect(): bool
    {
        // Look at the current instance and pull the options for said instance
        $options = self::get_instance();
        if ($options === []) {
            return false;
        }

        $this->_mpd = new mpd($options['host'], $options['port'], $options['password'] ?? null, 'debug_event');

        return $this->_mpd->connected;
    }
}
