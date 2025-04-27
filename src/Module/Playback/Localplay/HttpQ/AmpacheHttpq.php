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

namespace Ampache\Module\Playback\Localplay\HttpQ;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\Democratic;
use Ampache\Module\Playback\Localplay\localplay_controller;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;

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
    private string $version     = '000002';
    private string $description = "Controls an httpQ instance, requires Ampache's httpQ version";

    /* Constructed variables */
    private $_httpq;

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
     * This returns true or false if this controller is installed
     */
    public function is_installed(): bool
    {
        $sql        = "SHOW TABLES LIKE 'localplay_httpq'";
        $db_results = Dba::read($sql);

        return (Dba::num_rows($db_results) > 0);
    }

    /**
     * install
     * This function installs the controller
     */
    public function install(): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = (AmpConfig::get('database_engine', 'InnoDB'));

        $sql = <<<SQL
        CREATE TABLE `localplay_httpq` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(128) COLLATE $collation NOT NULL,
            `owner` INT(11) NOT NULL,
            `host` VARCHAR(255) COLLATE $collation NOT NULL,
            `port` INT(11) UNSIGNED NOT NULL,
            `password` VARCHAR(255) COLLATE $collation NOT NULL,
            `access` SMALLINT(4) UNSIGNED NOT NULL DEFAULT '0'
        ) ENGINE = $engine DEFAULT CHARSET=$charset COLLATE=$collation
        SQL;
        Dba::query($sql);

        // Add an internal preference for the users current active instance
        Preference::insert('httpq_active', T_('HTTPQ Active Instance'), 0, AccessLevelEnum::USER->value, 'integer', 'internal', 'httpq');

        return true;
    }

    /**
     * uninstall
     * This removes the Localplay controller
     */
    public function uninstall(): bool
    {
        $sql = "DROP TABLE `localplay_httpq`";
        Dba::write($sql);

        // Remove the pref we added for this
        Preference::delete('httpq_active');

        return true;
    }

    /**
     * add_instance
     * This takes keyed data and inserts a new httpQ instance
     * @param array{
     *     name?: string,
     *     host?: string,
     *     port?: string,
     *     password?: string,
     * } $data
     */
    public function add_instance(array $data): void
    {
        $sql     = "INSERT INTO `localplay_httpq` (`name`, `host`, `port`, `password`, `owner`) VALUES (?, ?, ?, ?, ?)";
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
        $uid = Dba::escape($uid);
        $sql = "DELETE FROM `localplay_httpq` WHERE `id`='$uid'";
        Dba::write($sql);
    }

    /**
     * get_instances
     * This returns a keyed array of the instance information with
     * [UID]=>[NAME]
     * @return string[]
     */
    public function get_instances(): array
    {
        $sql = "SELECT * FROM `localplay_httpq` ORDER BY `name`";

        $db_results = Dba::read($sql);
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
     * @param array{
     *     host: string,
     *     port: string,
     *     name: string,
     *     password: string,
     * } $data
     */
    public function update_instance(int $uid, array $data): void
    {
        $sql = "UPDATE `localplay_httpq` SET `host` = ?, `port` = ?, `name` = ?, `password` = ? WHERE `id` = ?;";
        Dba::write($sql, [$data['host'], $data['port'], $data['name'], $data['password'], $uid]);
    }

    /**
     * instance_fields
     * This returns a keyed array of [NAME]=>array([DESCRIPTION]=>VALUE,[TYPE]=>VALUE) for the
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
     * get_instance
     * This returns a single instance and all its variables
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
        $instance   = (is_numeric($instance)) ? (int) $instance : (int) AmpConfig::get('httpq_active', 0);
        $sql        = ($instance > 0) ? "SELECT * FROM `localplay_httpq` WHERE `id` = ?" : "SELECT * FROM `localplay_httpq`";
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
     * set_active_instance
     * This sets the specified instance as the 'active' one
     */
    public function set_active_instance(int $uid): bool
    {
        $user = Core::get_global('user');
        if (!$user instanceof User) {
            return false;
        }
        Preference::update('httpq_active', $user->id, $uid);
        AmpConfig::set('httpq_active', $uid, true);
        debug_event('httpq.controller', 'set_active_instance: ' . $uid . ' ' . $user->id, 5);

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
        if ($this->_httpq->add($url->title, $url->url) === null) {
            debug_event('httpq.controller', 'add_url failed to add ' . (string)$url->url, 1);

            return false;
        }

        return true;
    }

    /**
     * delete_track
     * This must take an ID (as returned by our get function)
     * and delete it from httpQ
     */
    public function delete_track(int $object_id): bool
    {
        if ($this->_httpq->delete_pos($object_id) === null) {
            debug_event('httpq.controller', 'Unable to delete ' . $object_id . ' from httpQ', 1);

            return false;
        }

        return true;
    }

    /**
     * clear_playlist
     */
    public function clear_playlist(): bool
    {
        if ($this->_httpq->clear() === null) {
            return false;
        }

        // If the clear worked we should stop it!
        $this->stop();

        return true;
    }

    /**
     * play
     * This just tells httpQ to start playing, it does not
     * take any arguments
     */
    public function play(): bool
    {
        // A play when it's already playing causes a track restart, so double check its state
        if ($this->_httpq->state() == 'play') {
            return true;
        }

        if ($this->_httpq->play() === null) {
            return false;
        }

        return true;
    }

    /**
     * stop
     * This just tells httpQ to stop playing, it does not take
     * any arguments
     */
    public function stop(): bool
    {
        if ($this->_httpq->stop() === null) {
            return false;
        }

        return true;
    }

    /**
     * skip
     * This tells httpQ to skip to the specified song
     */
    public function skip(int $track_id): bool
    {
        if ($this->_httpq->skip($track_id) === null) {
            return false;
        }

        return true;
    }

    /**
     * This tells httpQ to increase the volume by WinAmps default amount
     */
    public function volume_up(): bool
    {
        return $this->_httpq->volume_up();
    }

    /**
     * This tells httpQ to decrease the volume by Winamp's default amount
     */
    public function volume_down(): bool
    {
        return $this->_httpq->volume_down();
    }

    /**
     * next
     * This just tells httpQ to skip to the next song
     */
    public function next(): bool
    {
        if ($this->_httpq->next() === null) {
            return false;
        }

        return true;
    }

    /**
     * prev
     * This just tells httpQ to skip to the prev song
     */
    public function prev(): bool
    {
        if ($this->_httpq->prev() === null) {
            return false;
        }

        return true;
    }

    /**
     * pause
     * This tells httpQ to pause the current song
     */
    public function pause(): bool
    {
        if ($this->_httpq->pause() === null) {
            return false;
        }

        return true;
    }

    /**
     * volume
     * This tells httpQ to set the volume to the specified amount this
     * is 0-100
     */
    public function volume($volume): bool
    {
        return $this->_httpq->set_volume($volume);
    }

    /**
     * repeat
     * This tells httpQ to set the repeating the playlist (i.e. loop) to
     * either on or off
     */
    public function repeat(bool $state): bool
    {
        if ($this->_httpq->repeat($state) === null) {
            return false;
        }

        return true;
    }

    /**
     * random
     * This tells httpQ to turn on or off the playing of songs from the
     * playlist in random order
     */
    public function random(bool $state): bool
    {
        if ($this->_httpq->random($state) === null) {
            return false;
        }

        return true;
    }

    /**
     * get
     * This functions returns an array containing information about
     * The songs that httpQ currently has in its playlist. This must be
     * done in a standardized fashion
     */
    public function get(): array
    {
        /* Get the Current Playlist */
        $list = $this->_httpq->get_tracks();

        if (!$list) {
            return [];
        }

        $songs   = explode("::", $list);
        $results = [];

        foreach ($songs as $key => $entry) {
            $data = [];

            /* Required Elements */
            $data['id']  = $key;
            $data['raw'] = $entry;

            $url_data = $this->parse_url($entry);
            switch ($url_data['primary_key']) {
                case 'oid':
                    $data['oid'] = $url_data['oid'];
                    $song        = new Song($data['oid']);
                    $song->format();
                    $data['name'] = $song->get_fullname() . ' - ' . $song->get_album_fullname($song->album, true) . ' - ' . $song->get_artist_fullname();
                    $data['link'] = $song->get_f_link();
                    break;
                case 'demo_id':
                    $democratic   = new Democratic($url_data['demo_id']);
                    $data['name'] = T_('Democratic') . ' - ' . $democratic->name;
                    $data['link'] = '';
                    break;
                case 'random':
                    $data['name'] = T_('Random') . ' - ' . scrub_out(ucfirst($url_data['type']));
                    $data['link'] = '';
                    break;
                default:
                    // If we don't know it, look up by filename
                    $filename          = Dba::escape($url_data['file']);
                    $sql               = "SELECT `id`, 'song' AS `type` FROM `song` WHERE `file` LIKE '%$filename' UNION ALL SELECT `id`, 'live_stream' AS `type` FROM `live_stream` WHERE `url`='$filename' ";
                    $libraryItemLoader = $this->getLibraryItemLoader();

                    $db_results = Dba::read($sql);
                    if ($row = Dba::fetch_assoc($db_results)) {
                        $media = $libraryItemLoader->load(
                            LibraryItemEnum::from($row['type']),
                            $row['id'],
                            [Song::class, Live_Stream::class]
                        );

                        if ($media !== null) {
                            $media->format();
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
                                    $data['link'] = $media->site_url;
                                    break;
                            } // end switch on type
                        }
                    } else {
                        $data['name'] = basename($data['raw']);
                        $data['link'] = basename($data['raw']);
                    }

                    break;
            } // end switch on primary key type

            $data['track'] = $key + 1;

            $results[] = $data;
        } // foreach playlist items

        return $results;
    }

    /**
     * status
     * This returns bool/int values for features, loop, repeat and any other features that this Localplay method supports.
     * required function
     */
    public function status(): array
    {
        $array = [];
        /* Construct the Array */
        $array['state']  = $this->_httpq->state();
        $array['volume'] = $this->_httpq->get_volume();
        $array['repeat'] = $this->_httpq->get_repeat();
        $array['random'] = $this->_httpq->get_random();
        $array['track']  = $this->_httpq->get_now_playing();

        $url_data = $this->parse_url($array['track']);
        if (array_key_exists('oid', $url_data) && !empty($url_data['oid'])) {
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
        } else {
            $array['track_title'] = basename($array['track'] ?? '');
        }

        return $array;
    }

    /**
     * connect
     * This functions creates the connection to httpQ and returns
     * a boolean value for the status, to save time this handle
     * is stored in this class
     */
    public function connect(): bool
    {
        $options = self::get_instance();
        if ($options === []) {
            return false;
        }

        $this->_httpq = new HttpQPlayer($options['host'], $options['password'], $options['port']);

        return ($this->_httpq->version() !== false); // Test our connection by retrieving the version
    }

    /**
     * @deprecated Inject dependency
     */
    private function getLibraryItemLoader(): LibraryItemLoaderInterface
    {
        global $dic;

        return $dic->get(LibraryItemLoaderInterface::class);
    }
}
