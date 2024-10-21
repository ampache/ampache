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

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;

/**
 * TempPlaylist Class
 *
 * This class handles the temporary playlists in Ampache. It handles the
 * tmp_playlist and tmp_playlist_data tables, and sneaks out at night to
 * visit user_vote from time to time.
 */
class Tmp_Playlist extends database_object
{
    protected const DB_TABLENAME = 'tmp_playlist';

    // Variables from the Database
    public int $id = 0;
    public ?string $session;
    public ?string $type;
    public ?string $object_type;

    // Generated Elements
    public $items = [];

    /**
     * Constructor
     * This takes a playlist_id as an optional argument and gathers the
     * information.  If no playlist_id is passed or the requested one isn't
     * found, return false.
     * @param int|null $playlist_id
     */
    public function __construct($playlist_id = 0)
    {
        if (!$playlist_id) {
            return;
        }

        $info = $this->has_info($playlist_id);
        if (!$info) {
            return;
        }
        $this->id = (int)$playlist_id;
    }

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * has_info
     * This is an internal (private) function that gathers the information
     * for this object from the playlist_id that was passed in.
     * @param int $playlist_id
     */
    private function has_info($playlist_id): bool
    {
        $sql        = "SELECT * FROM `tmp_playlist` WHERE `id` = ?;";
        $db_results = Dba::read($sql, [$playlist_id]);
        $data       = Dba::fetch_assoc($db_results);
        if (empty($data)) {
            return false;
        }
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        return true;
    }

    /**
     * get_from_session
     * This returns a playlist object based on the session that is passed to
     * us.  This is used by the load_playlist on user for the most part.
     * @param string $session_id
     * @return Tmp_Playlist
     */
    public static function get_from_session($session_id): Tmp_Playlist
    {
        $sql        = "SELECT `id` FROM `tmp_playlist` WHERE `session` = ?";
        $db_results = Dba::read($sql, [$session_id]);
        $row        = Dba::fetch_row($db_results);

        if (empty($row)) {
            $row[0] = Tmp_Playlist::create([
                'session_id' => $session_id,
                'type' => 'user',
                'object_type' => 'song'
            ]);
        }

        return new Tmp_Playlist((int)$row[0]);
    }

    /**
     * get_from_username
     * This returns a tmp playlist object based on a userid passed
     * this is used for the user profiles page
     * @param string $username
     */
    public static function get_from_username($username): ?int
    {
        $sql        = "SELECT `tmp_playlist`.`id` FROM `tmp_playlist` LEFT JOIN `session` ON `session`.`id`=`tmp_playlist`.`session` WHERE `session`.`username` = ? ORDER BY `session`.`expire` DESC";
        $db_results = Dba::read($sql, [$username]);
        $results    = Dba::fetch_assoc($db_results);
        if (empty($results)) {
            return null;
        }

        return (int)$results['id'];
    }

    /**
     * get_items
     * Returns an array of all object_ids currently in this Tmp_Playlist.
     * @return array
     */
    public function get_items(): array
    {
        $session_name = AmpConfig::get('session_name');
        $sql          = "SELECT `tmp_playlist_data`.`object_type`, `tmp_playlist_data`.`id`, `tmp_playlist_data`.`object_id` FROM `tmp_playlist_data` ";
        if (isset($_COOKIE[$session_name])) {
            // Select all objects for this session
            $params = [$_COOKIE[$session_name]];
            $sql .= "LEFT JOIN `tmp_playlist` ON `tmp_playlist`.`id` = `tmp_playlist_data`.`tmp_playlist` WHERE `tmp_playlist`.`session` = ? ORDER BY `id`;";
            $db_results = Dba::read($sql, $params);
        } else {
            // try to guess
            $params = [$this->id];
            $sql .= "WHERE `tmp_playlist` = ? ORDER BY `id`;";
            $db_results = Dba::read($sql, $params);
        }
        //debug_event(self::class, 'get_items ' . $sql . ' ' . print_r($params, true), 5);

        // Define the array
        $items = [];
        $count = 1;
        while ($results = Dba::fetch_assoc($db_results)) {
            $items[] = [
                'object_type' => $results['object_type'],
                'object_id' => $results['object_id'],
                'track_id' => $results['id'],
                'track' => $count++,
            ];
        }

        return $items;
    }

    /**
     * get_next_object
     * This returns the next object in the tmp_playlist.
     */
    public function get_next_object(): ?int
    {
        $sql        = "SELECT `object_id` FROM `tmp_playlist_data` WHERE `tmp_playlist` = ? ORDER BY `id` LIMIT 1";
        $db_results = Dba::read($sql, [$this->id]);
        if (!$db_results) {
            return null;
        }
        $results = Dba::fetch_assoc($db_results);

        return (int)$results['object_id'];
    }

    /**
     * count_items
     * This returns a count of the total number of tracks that are in this
     * tmp playlist
     */
    public function count_items(): int
    {
        $sql        = "SELECT COUNT(`id`) FROM `tmp_playlist_data` WHERE `tmp_playlist` = ?;";
        $db_results = Dba::read($sql, [$this->id]);
        $row        = Dba::fetch_row($db_results);

        return (int)($row[0] ?? 0);
    }

    /**
     * clear
     * This clears all the objects out of a single playlist
     */
    public function clear(): bool
    {
        $sql = "DELETE FROM `tmp_playlist_data` WHERE `tmp_playlist` = ?";
        Dba::write($sql, [$this->id]);

        return true;
    }

    /**
     * create
     * This function initializes a new Tmp_Playlist. It is associated with
     * the current session rather than a user, as you could have the same
     * user logged in from multiple locations.
     * @param array $data
     */
    public static function create($data): ?string
    {
        $sql = "INSERT INTO `tmp_playlist` (`session`, `type`, `object_type`) VALUES (?, ?, ?)";
        Dba::write($sql, [$data['session_id'], $data['type'], $data['object_type']]);

        $tmp_id = Dba::insert_id();
        if (!$tmp_id) {
            return null;
        }

        /* Clean any other playlists associated with this session */
        self::session_clean($data['session_id'], $tmp_id);

        return $tmp_id;
    }

    /**
     * session_clean
     * This deletes any other tmp_playlists associated with this
     * session
     * @param $sessid
     * @param string|false $plist_id
     */
    public static function session_clean($sessid, $plist_id): void
    {
        $sql = "DELETE FROM `tmp_playlist` WHERE `session` = ? AND `id` != ?";
        Dba::write($sql, [$sessid, $plist_id]);

        /* Remove associated tracks */
        self::prune_tracks();
    }

    /**
     * garbage_collection
     * This cleans up old data
     */
    public static function garbage_collection(): void
    {
        self::prune_playlists();
        self::prune_tracks();
        // Ampache\Module\System\Dba::write("DELETE FROM `tmp_playlist_data` USING `tmp_playlist_data` LEFT JOIN `song` ON `tmp_playlist_data`.`object_id` = `song`.`id` WHERE `song`.`id` IS NULL;");
    }

    /**
     * prune_playlists
     * This deletes any playlists that don't have an associated session
     */
    public static function prune_playlists(): bool
    {
        /* Just delete if no matching session row */
        $sql = "DELETE FROM `tmp_playlist` USING `tmp_playlist` LEFT JOIN `session` ON `session`.`id`=`tmp_playlist`.`session` WHERE `session`.`id` IS NULL AND `tmp_playlist`.`type` != 'vote'";
        Dba::write($sql);

        return true;
    }

    /**
     * prune_tracks
     * This prunes tracks that don't have playlists or don't have votes
     */
    public static function prune_tracks(): void
    {
        // This prune is always run and clears data for playlists that don't exist anymore
        $sql = "DELETE FROM `tmp_playlist_data` USING `tmp_playlist_data` LEFT JOIN `tmp_playlist` ON `tmp_playlist_data`.`tmp_playlist`=`tmp_playlist`.`id` WHERE `tmp_playlist`.`id` IS NULL";
        Dba::write($sql);
    }

    /**
     * add_object
     * This adds the object of $this->object_type to this tmp playlist
     * it takes an optional type, default is song
     * @param int $object_id
     * @param string $object_type
     */
    public function add_object($object_id, $object_type): bool
    {
        $sql = "INSERT INTO `tmp_playlist_data` (`object_id`, `tmp_playlist`, `object_type`) VALUES (?, ?, ?)";
        Dba::write($sql, [$object_id, $this->id, $object_type]);

        return true;
    }

    /**
     * @param $medias
     */
    public function add_medias($medias): void
    {
        foreach ($medias as $media) {
            $this->add_object($media['object_id'], $media['object_type']);
        }
    }

    /**
     * delete_track
     * This deletes a track from the tmpplaylist
     * @param $object_id
     */
    public function delete_track($object_id): bool
    {
        /* delete the track its self */
        $sql = "DELETE FROM `tmp_playlist_data` WHERE `id` = ?";
        Dba::write($sql, [$object_id]);

        return true;
    }
}
