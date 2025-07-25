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

namespace Ampache\Module\Playback\Localplay\Vlc;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\Democratic;
use Ampache\Module\Playback\Localplay\localplay_controller;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;

/**
 * This is the class for the VLC Localplay method to remote control
 * a VLC Instance
 */
class AmpacheVlc extends localplay_controller
{
    /* Variables */
    private string $version     = 'Beta 0.2';
    private string $description = 'Controls a VLC instance';

    /* Constructed variables */
    private $_vlc;

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
     * This returns true or false if VLC controller is installed
     */
    public function is_installed(): bool
    {
        $sql        = "SHOW TABLES LIKE 'localplay_vlc'";
        $db_results = Dba::query($sql);

        return (Dba::num_rows($db_results) > 0);
    }

    /**
     * install
     * This function installs the VLC Localplay controller
     */
    public function install(): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = (AmpConfig::get('database_engine', 'InnoDB'));

        $sql = <<<SQL
            CREATE TABLE `localplay_vlc` (
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
        Preference::insert('vlc_active', T_('VLC Active Instance'), 0, AccessLevelEnum::USER->value, 'integer', 'internal', 'vlc');

        return true;
    }

    /**
     * uninstall
     * This removes the localplay controller
     */
    public function uninstall(): bool
    {
        $sql = "DROP TABLE `localplay_vlc`";
        Dba::query($sql);

        // Remove the pref we added for this
        Preference::delete('vlc_active');

        return true;
    }

    /**
     * add_instance
     * This takes key'd data and inserts a new VLC instance
     * @param array{
     *     name?: string,
     *     host?: string,
     *     port?: string,
     *     password?: string,
     * } $data
     */
    public function add_instance(array $data): void
    {
        $sql     = "INSERT INTO `localplay_vlc` (`name`, `host`, `port`, `password`, `owner`) VALUES (?, ?, ?, ?, ?)";
        $user_id = (Core::get_global('user') instanceof User)
            ? Core::get_global('user')->id
            : -1;

        Dba::query($sql, [$data['name'] ?? null, $data['host'] ?? null, $data['port'] ?? null, $data['password'] ?? null, $user_id]);
    }

    /**
     * delete_instance
     * This takes a UID and deletes the instance in question
     */
    public function delete_instance(int $uid): void
    {
        $sql = "DELETE FROM `localplay_vlc` WHERE `id` = ?";
        Dba::query($sql, [$uid]);
    }

    /**
     * get_instances
     * This returns a key'd array of the instance information with
     * [UID]=>[NAME]
     * @return string[]
     */
    public function get_instances(): array
    {
        $sql        = "SELECT * FROM `localplay_vlc` ORDER BY `name`";
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
     * @param array{
     *     host: string,
     *     port: string,
     *     name: string,
     *     password: string,
     * } $data
     */
    public function update_instance(int $uid, array $data): void
    {
        $sql = "UPDATE `localplay_vlc` SET `host` = ?, `port` = ?, `name` = ?, `password` = ? WHERE `id` = ?";
        Dba::query($sql, [$data['host'], $data['port'], $data['name'], $data['password'], $uid]);
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
     * get_instance
     * This returns a single instance and all it's variables
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
        $instance   = (is_numeric($instance)) ? (int) $instance : (int) AmpConfig::get('vlc_active', 0);
        $sql        = ($instance > 0) ? "SELECT * FROM `localplay_vlc` WHERE `id` = ?" : "SELECT * FROM `localplay_vlc`";
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
        Preference::update('vlc_active', $user->id, $uid);
        AmpConfig::set('vlc_active', $uid, true);
        debug_event('vlc.controller', 'set_active_instance: ' . $uid . ' ' . $user->id, 5);

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
     */
    public function delete_track(int $object_id): bool
    {
        if ($this->_vlc->delete_pos($object_id) === null) {
            debug_event(self::class, 'ERROR Unable to delete ' . $object_id . ' from VLC', 1);

            return false;
        }

        return true;
    }

    /**
     * clear_playlist
     * This deletes the entire VLC playlist... nuff said
     */
    public function clear_playlist(): bool
    {
        if ($this->_vlc->clear() === null) {
            return false;
        }

        // If the clear worked we should stop it!
        $this->stop();

        return true;
    }

    /**
     * play
     * This just tells VLC to start playing, it does not
     * take any arguments
     */
    public function play(): bool
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
    }

    /**
     * stop
     * This just tells VLC to stop playing, it does not take
     * any arguments
     */
    public function stop(): bool
    {
        if ($this->_vlc->stop() === null) {
            return false;
        }

        return true;
    }

    /**
     * skip
     * This tells VLC to skip to the specified song
     */
    public function skip(int $track_id): bool
    {
        //vlc skip is based on his playlist track, we convert ampache localplay track to vlc
        //playlist id
        $listtracks = $this->get();
        foreach ($listtracks as $track) {
            if ($track['id'] == $track_id) {
                if ($this->_vlc->skip($track['vlid']) === null) {
                    return false;
                }
                break;
            }
        }

        return true;
    }

    /**
     * This tells VLC to increase the volume by in vlcplayerclass set amount
     */
    public function volume_up(): bool
    {
        return $this->_vlc->volume_up();
    }

    /**
     * This tells VLC to decrease the volume by vlcplayerclass set amount
     */
    public function volume_down(): bool
    {
        return $this->_vlc->volume_down();
    }

    /**
     * next
     * This just tells VLC to skip to the next song, if you play a song by direct
     * clicking and hit next VLC will start with the first song, needs work.
     */
    public function next(): bool
    {
        if ($this->_vlc->next() === null) {
            return false;
        }

        return true;
    }

    /**
     * prev
     * This just tells VLC to skip to the prev song
     */
    public function prev(): bool
    {
        if ($this->_vlc->prev() === null) {
            return false;
        }

        return true;
    }

    /**
     * pause
     * This tells VLC to pause the current song
     */
    public function pause(): bool
    {
        if ($this->_vlc->pause() === null) {
            return false;
        }

        return true;
    }

    /**
     * volume
     * This tells VLC to set the volume to the specified amount this
     * is 0-400 percent
     */
    public function volume(int $volume): bool
    {
        return $this->_vlc->set_volume($volume);
    }

    /**
     * repeat
     * This tells VLC to set the repeating the playlist (i.e. loop) to either on or off
     */
    public function repeat(bool $state): bool
    {
        if ($this->_vlc->repeat($state) === null) {
            return false;
        }

        return true;
    }

    /**
     * random
     * This tells VLC to turn on or off the playing of songs from the playlist in random order
     */
    public function random(bool $state): bool
    {
        if ($this->_vlc->random($state) === null) {
            return false;
        }

        return true;
    }

    /**
     * get
     * This functions returns an array containing information about
     * The songs that VLC currently has in it's playlist. This must be
     * done in a standardized fashion
     * Warning ! if you got files in VLC medialibary those files will be sent to the php xml parser
     * to, not to your browser but still this can take a lot of work for your server.
     * The xml files of VLC need work, not much documentation on them....
     */
    public function get(): array
    {
        /* Get the Current Playlist */
        $list = $this->_vlc->get_tracks();

        if (!$list) {
            return [];
        }

        $songs   = [];
        $song_id = [];
        $results = [];
        $counter = 0;
        // here we look if there are song in the playlist when media libary is used
        if (isset($list['node']['node'][0]['leaf'][$counter]['attr']['uri'])) {
            while (array_key_exists($counter, $list['node']['node'][0]['leaf'])) {
                $songs[] = htmlspecialchars_decode(
                    $list['node']['node'][0]['leaf'][$counter]['attr']['uri'],
                    ENT_NOQUOTES
                );
                $song_id[] = $list['node']['node'][0]['leaf'][$counter]['attr']['id'];
                $counter++;
            }
        } elseif (isset($list['node']['node'][0]['leaf']['attr']['uri'])) {
            // if there is only one song look here,and media library is used
            $songs[]   = htmlspecialchars_decode($list['node']['node'][0]['leaf']['attr']['uri'], ENT_NOQUOTES);
            $song_id[] = $list['node']['node'][0]['leaf']['attr']['id'];
        } elseif (isset($list['node']['node']['leaf'][$counter]['attr']['uri'])) {
            // look for songs when media library isn't used
            while ($list['node']['node']['leaf'][$counter]) {
                $songs[] = htmlspecialchars_decode(
                    $list['node']['node']['leaf'][$counter]['attr']['uri'],
                    ENT_NOQUOTES
                );
                $song_id[] = $list['node']['node']['leaf'][$counter]['attr']['id'];
                $counter++;
            }
        } elseif (isset($list['node']['node']['leaf']['attr']['uri'])) {
            $songs[]   = htmlspecialchars_decode($list['node']['node']['leaf']['attr']['uri'], ENT_NOQUOTES);
            $song_id[] = $list['node']['node']['leaf']['attr']['id'];
        } else {
            return [];
        }

        $counter = 0;
        foreach ($songs as $key => $entry) {
            $data = [];

            /* Required Elements */
            $data['id']   = $counter; // id follows localplay api
            $data['vlid'] = $song_id[$counter]; // vlid number of the files in the VLC playlist, needed for other operations
            $data['raw']  = $entry;

            $url_data = $this->parse_url($entry);
            switch ($url_data['primary_key']) {
                case 'oid':
                    $data['oid']  = $url_data['oid'];
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
                    $data['name'] = T_('Random') . ' - ' . scrub_out(ucfirst($url_data['type']));
                    $data['link'] = '';
                    break;
                default:
                    // If we don't know it, look up by filename
                    $filename = Dba::escape($entry);
                    $sql      = "SELECT `name` FROM `live_stream` WHERE `url` = ? ";

                    $db_results = Dba::read($sql, [$filename]);
                    if ($row = Dba::fetch_assoc($db_results)) {
                        // if stream is known just send name
                        $data['name'] = htmlspecialchars(substr($row['name'], 0, 50));
                    } elseif (strncmp($entry, 'http', 4) == 0) {
                        // if it's a http stream not in ampacha's database just show the url'
                        $data['name'] = htmlspecialchars("(VLC stream) " . substr($entry, 0, 50));
                    } else {
                        // it's a file get the last output after and show that, hard to take every output possible in account
                        $getlast      = explode('/', $entry);
                        $lastis       = count($getlast) - 1;
                        $data['name'] = htmlspecialchars("(VLC local) " . substr($getlast[$lastis], 0, 50));
                    } // end if loop
                    break;
            } // end switch on primary key type

            $data['track'] = $key + 1; //track follows localplay api, 'id' + 1
            $counter++;
            $results[] = $data;
        } // foreach playlist items

        return $results;
    }

    /**
     * status
     * This returns bool/int values for features, loop, repeat and any other features That this Localplay method supports.
     * This works as in requesting the status.xml file from VLC.
     * required function
     */
    public function status(): array
    {
        $arrayholder = $this->_vlc->fullstate(); //get status.xml via parser xmltoarray
        if (!$arrayholder) {
            return [];
        }

        /* Construct the Array */
        $currentstat = $arrayholder['root']['state']['value'];
        $listtracks  = $this->get();

        if ($currentstat == 'playing') {
            $state = 'play';
        } // change to something ampache understands
        if ($currentstat == 'stop') {
            $state = 'stop';
        }
        if ($currentstat == 'paused') {
            $state = 'pause';
        }

        $array          = [];
        $array['track'] = 0;
        $oid            = '';

        $array['track_title']  = '';
        $array['track_artist'] = '';
        $array['track_album']  = '';
        $array['state']        = $state ?? '';
        $array['volume']       = ($arrayholder['root']['volume']['value'] > 0)
            ? (int)(((int)($arrayholder['root']['volume']['value']) / 2.56))
            : 0;
        $array['repeat'] = $arrayholder['root']['repeat']['value'];
        $array['random'] = $arrayholder['root']['random']['value'];

        // api version 1
        if (isset($arrayholder['root']['information']['meta-information']['title']['value'])) {
            $ampurl = htmlspecialchars_decode(
                $arrayholder['root']['information']['meta-information']['title']['value'],
                ENT_NOQUOTES
            );
            $url_data = $this->parse_url($ampurl);
            $oid      = (array_key_exists('oid', $url_data)) ? $url_data['oid'] : '';

            foreach ($listtracks as $track) {
                if ($track['oid'] == $oid) {
                    $array['track'] = $track['track'];
                    break;
                }
            }
        }

        // api version 3
        if (isset($arrayholder['root']['currentplid'])) {
            $numtrack = (int)($arrayholder['root']['currentplid']['value'] ?? 0);

            foreach ($listtracks as $track) {
                if ($track['vlid'] == $numtrack) {
                    $array['track'] = $track['track'];
                    $oid            = $track['oid'];
                    break;
                }
            }
        }

        if (!empty($oid)) {
            $song = new Song($oid);
            if ($song->isNew()) {
                // if not a known format
                $array['track_title']  = htmlspecialchars(substr($arrayholder['root']['information']['meta-information']['title']['value'], 0, 25));
                $array['track_artist'] = htmlspecialchars(substr($arrayholder['root']['information']['meta-information']['artist']['value'], 0, 20));
            } else {
                $array['track_title']  = $song->title;
                $array['track_artist'] = $song->get_artist_fullname();
                $array['track_album']  = $song->get_album_fullname();
            }
        }

        return $array;
    }

    /**
     * connect
     * This functions creates the connection to VLC and returns
     * a boolean value for the status, to save time this handle
     * is stored in this class
     */
    public function connect(): bool
    {
        $options = self::get_instance();
        if ($options === []) {
            return false;
        }

        $this->_vlc = new VlcPlayer($options['host'], $options['password'], $options['port']);
        // Test our connection by retriving the version, no version in status file, just need to see if returned
        // Not yet working all values returned are true for beta testing purpose

        return ($this->_vlc->version() !== false);
    }
}
