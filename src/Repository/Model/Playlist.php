<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Catalog\CatalogCounterInterface;
use Ampache\Module\Catalog\CountableTableEnum;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\PlaylistRepositoryInterface;

/**
 * This class handles playlists in ampache. it references the playlist* tables
 */
class Playlist extends playlist_object
{
    protected const string DB_TABLENAME = 'playlist';

    /**
     * @var array<int, array{
     *     object_type: LibraryItemEnum,
     *     object_id: int,
     *     track: int,
     *     track_id: int,
     *     time: int
     * }>
     */
    public array $items = [];

    /**
     * Constructor
     * This takes a playlist_id as an optional argument and gathers the information
     * if not playlist_id is passed returns false (or if it isn't found
     */
    public function __construct(?int $object_id = 0)
    {
        if (!$object_id) {
            return;
        }

        $info                = $this->get_info($object_id, static::DB_TABLENAME);
        $this->id            = (int) ($info['id'] ?? 0);
        $this->name          = $info['name'] ?? null;
        $this->user          = isset($info['user']) ? (int) $info['user'] : null;
        $this->username      = $info['username'] ?? null;
        $this->type          = $info['type'] ?? null;
        $this->date          = (int) ($info['date'] ?? 0);
        $this->last_update   = isset($info['last_update']) ? (int) $info['last_update'] : 0;
        $this->last_count    = isset($info['last_count']) ? (int) $info['last_count'] : 0;
        $this->last_duration = isset($info['last_duration']) ? (int) $info['last_duration'] : 0;
        $this->collaborate   = $info['collaborate'] ?? '';
    }

    /**
     * build_cache
     * This is what builds the cache from the objects
     * @param array<int|string> $ids
     */
    public static function build_cache(array $ids): bool
    {
        if (empty($ids)) {
            return false;
        }

        $idlist     = '(' . implode(',', $ids) . ')';
        $sql        = 'SELECT * FROM `playlist` WHERE `id` IN ' . $idlist;
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('playlist', $row['id'], $row);
        }

        return true;
    }

    /**
     * check
     * This function creates an empty playlist, gives it a name and type
     */
    public static function check(string $name, string $type, ?int $user_id = null): int
    {
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? -1;
        }

        $results    = [];
        $sql        = "SELECT `id` FROM `playlist` WHERE `name` = ? AND `user` = ? AND `type` = ?";
        $db_results = Dba::read($sql, [$name, $user_id, $type]);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        // return the duplicate ID
        if ($results !== []) {
            return $results[0];
        }

        return 0;
    }

    /**
     * create
     * This function creates an empty playlist, gives it a name and type
     */
    public static function create(string $name, string $type, ?int $user_id = null, bool $existing = true): ?int
    {
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? -1;
        }

        // check for duplicates
        $existing_id = self::check($name, $type, $user_id);
        if ($existing_id > 0) {
            if (!$existing) {
                return null;
            }

            return $existing_id;
        }

        // get the public_name/username
        $username = User::get_username($user_id);

        $date = time();
        $sql  = "INSERT INTO `playlist` (`name`, `user`, `username`, `type`, `date`, `last_update`) VALUES (?, ?, ?, ?, ?, ?)";
        Dba::write($sql, [$name, $user_id, $username, $type, $date, $date]);
        $insert_id = Dba::insert_id();
        if (empty($insert_id)) {
            return null;
        }

        self::getCatalogCounter()->count(CountableTableEnum::PLAYLIST);

        return (int) $insert_id;
    }

    /**
     * garbage_collection
     *
     * Clean dead items out of playlists
     */
    public static function garbage_collection(): void
    {
        self::getPlaylistRepository()->collectGarbage();
    }

    /**
     * get_playlist_array
     * Returns a list of playlists accessible by the user with formatted name.
     * @return string[]
     */
    public static function get_playlist_array(?int $user_id = null): array
    {
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }

        $key = 'playlistarray';
        if ($user_id > 0 && parent::is_cached($key, $user_id)) {
            return parent::get_from_cache($key, $user_id);
        }

        $is_admin = (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN, $user_id) || $user_id == -1);
        $sql      = "SELECT `id`, IF(`user` = ?, `name`, CONCAT(`name`, ' (', `username`, ')')) AS `name` FROM `playlist` ";
        $params   = [$user_id];

        if (!$is_admin) {
            $sql .= "WHERE (`user` = ? OR `type` = 'public') ";
            $params[] = $user_id;
        }

        $sql .= "ORDER BY `name`";
        //debug_event(self::class, 'get_playlists query: ' . $sql . ' ' . print_r($params, true), 5);

        $db_results = Dba::read($sql, $params);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['id']] = $row['name'];
        }

        parent::add_to_cache($key, $user_id, $results);

        return $results;
    }

    /**
     * get_playlists
     * Returns a list of playlists accessible by the user.
     * @return int[]
     */
    public static function get_playlists(
        ?int $user_id = null,
        ?string $playlist_name = '',
        ?bool $like = true,
        ?bool $includePublic = true,
        ?bool $includeHidden = true,
        ?bool $userOnly = false,
    ): array {
        if (!$user_id) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }

        $key = ($includePublic)
            ? 'playlistids'
            : 'accessibleplaylistids';
        if (empty($playlist_name) && ($user_id > 0 && parent::is_cached($key, $user_id))) {
            return parent::get_from_cache($key, $user_id);
        }

        $is_admin = (
            $userOnly === false
            || (
                Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN, $user_id)
                || $user_id == -1
            )
        );
        $sql    = "SELECT `id` FROM `playlist` ";
        $params = [];
        $join   = 'WHERE';

        if (!$is_admin) {
            $sql .= ($includePublic)
                ? $join . ' (`user` = ? OR `type` = \'public\') '
                : $join . ' (`user` = ?) ';
            $params[] = $user_id;
            $join     = 'AND';
        }

        if ($playlist_name !== '') {
            $playlist_name = ($like) ? "LIKE '%" . $playlist_name . "%' " : "= '" . $playlist_name . "'";
            $sql .= $join . ' `name` ' . $playlist_name;
            $join = 'AND';
        }

        if (!$includeHidden) {
            $hide_string = str_replace('%', '\%', str_replace('_', '\_', (string) Preference::get_by_user($user_id, 'api_hidden_playlists')));
            if (!empty($hide_string)) {
                $sql .= $join . ' `name` NOT LIKE \'' . Dba::escape($hide_string) . "%' ";
            }
        }

        $sql .= "ORDER BY `name`";
        //debug_event(self::class, 'get_playlists query: ' . $sql . ' ' . print_r($params, true), 5);

        $db_results = Dba::read($sql, $params);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        if (
            $playlist_name === ''
            || $playlist_name === '0'
        ) {
            parent::add_to_cache($key, $user_id, $results);
        }

        return $results;
    }

    /**
     * Migrate an object associate stats to a new object
     */
    public static function migrate(string $object_type, int $old_object_id, int $new_object_id): void
    {
        self::getPlaylistRepository()->migrateObject($object_type, $old_object_id, $new_object_id);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getCatalogCounter(): CatalogCounterInterface
    {
        global $dic;

        return $dic->get(CatalogCounterInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getPlaylistRepository(): PlaylistRepositoryInterface
    {
        global $dic;

        return $dic->get(PlaylistRepositoryInterface::class);
    }

    /**
     * add_medias
     * @param array<array{object_type: LibraryItemEnum|string, object_id: int}> $medias
     */
    public function add_medias(array $medias): bool
    {
        if ($medias === []) {
            return false;
        }

        debug_event(self::class, "add_medias to: " . $this->id, 5);
        $unique     = (bool) AmpConfig::get('unique_playlist', false);
        $track_data = ($unique)
            ? $this->get_songs()
            : [];
        $base_track = self::getPlaylistRepository()->getLastTrackNumber($this);
        $count      = 0;
        $rows       = [];
        foreach ($medias as $data) {
            $object_type = (is_string($data['object_type']))
                ? LibraryItemEnum::tryFrom((string) $data['object_type'])
                : $data['object_type'];
            if ($unique && in_array($data['object_id'], $track_data)) {
                debug_event(self::class, "Can't add a duplicate " . $object_type?->value . " (" . $data['object_id'] . ") when unique_playlist is enabled", 3);
            } else {
                ++$count;
                $rows[] = [(int) $data['object_id'], $object_type?->value, $base_track + $count];
            } // if valid id
        }

        if ($rows !== []) {
            self::getPlaylistRepository()->addTracks($this, $rows);
            debug_event(self::class, sprintf('Added %d tracks to playlist: ', $count) . $this->id, 5);
            $this->_update_last();

            return true;
        }

        return false;
    }

    /**
     * @param int[]|string[] $song_ids
     * This takes an array of song_ids and then adds it to the playlist
     */
    public function add_songs(iterable $song_ids = []): bool
    {
        $medias = [];
        foreach ($song_ids as $song_id) {
            $medias[] = [
                'object_type' => LibraryItemEnum::SONG,
                'object_id' => (int) $song_id,
            ];
        }

        if ($this->add_medias($medias)) {
            Catalog::update_mapping('playlist');

            return true;
        }

        return false;
    }

    /**
     * delete
     * This deletes the current playlist and all associated data
     */
    public function delete(): bool
    {
        self::getPlaylistRepository()->delete($this);

        $this->getPlaylistObjectRepository()->deleteCollaborators($this);

        return true;
    }

    /**
     * delete_all
     *
     * this deletes all tracks from a playlist, you specify the playlist.id here
     */
    public function delete_all(): bool
    {
        self::getPlaylistRepository()->deleteAllTracks($this);
        debug_event(self::class, 'Delete all tracks from: ' . $this->id, 5);

        $this->_update_last();

        return true;
    }

    /**
     * delete_song
     * this deletes a single track, you specify the playlist_data.id here
     */
    public function delete_song(int $object_id): bool
    {
        self::getPlaylistRepository()->deleteTrackByObjectId($this, $object_id);
        debug_event(self::class, 'Delete object_id: ' . $object_id . ' from ' . $this->id, 5);

        $this->_update_last();

        return true;
    }

    /**
     * delete_track
     * this deletes a single track, you specify the playlist_data.id here
     */
    public function delete_track(int $object_id): bool
    {
        self::getPlaylistRepository()->deleteTrackById($this, $object_id);
        debug_event(self::class, 'Delete item_id: ' . $object_id . ' from ' . $this->id, 5);

        $this->_update_last();

        return true;
    }

    /**
     * delete_track_number
     * this deletes a single track by it's track #, you specify the playlist_data.track here
     */
    public function delete_track_number(int $track): bool
    {
        self::getPlaylistRepository()->deleteTrackByNumber($this, $track);
        debug_event(self::class, 'Delete track: ' . $track . ' from ' . $this->id, 5);

        $this->_update_last();

        return true;
    }

    /**
     * get_items
     * This returns an array of playlist medias that are in this playlist.
     * Because the same media can be on the same playlist twice they are
     * keyed by the uid from playlist_data
     * @return array<int, array{
     *     object_type: LibraryItemEnum,
     *     object_id: int,
     *     track_id: int,
     *     track: int,
     *     time: int
     * }>
     */
    public function get_items(): array
    {
        if ($this->isNew()) {
            return [];
        }

        $results = [];
        $user    = Core::get_global('user');
        $user_id = $user->id ?? -1;

        $sql             = 'SELECT DISTINCT `object_type` FROM `playlist_data` WHERE `playlist` = ?';
        $db_object_types = Dba::read($sql, [$this->id]);

        while ($row = Dba::fetch_assoc($db_object_types)) {
            $object_type = LibraryItemEnum::from($row['object_type']);
            $params      = [$this->id];
            $system      = ($user_id < 0);

            switch ($object_type) {
                case LibraryItemEnum::SONG:
                    $sql = 'SELECT `playlist_data`.`id`, `object_id`, `object_type`, `playlist_data`.`track`, `song`.`time` FROM `playlist_data` INNER JOIN `song` ON `playlist_data`.`object_id` = `song`.`id` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_type`="song" AND `object_id` IS NOT NULL ';
                    if (AmpConfig::get('catalog_filter')) {
                        if ($system) {
                            $sql .= 'AND `playlist_data`.`object_type`="song" AND `song`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) ';
                        } else {
                            $sql .= 'AND `playlist_data`.`object_type`="song" AND `song`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = ? AND `catalog_filter_group_map`.`enabled`=1) ';
                            $params[] = $user_id;
                        }
                    }

                    $sql .= 'ORDER BY `playlist_data`.`track`';
                    break;
                case LibraryItemEnum::PODCAST_EPISODE:
                    $sql = 'SELECT `playlist_data`.`id`, `object_id`, `object_type`, `playlist_data`.`track`, `podcast_episode`.`time` FROM `playlist_data` INNER JOIN `podcast_episode` ON `playlist_data`.`object_id` = `podcast_episode`.`id` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_type`="podcast_episode" AND `object_id` IS NOT NULL ';
                    if (AmpConfig::get('catalog_filter')) {
                        if ($system) {
                            $sql .= 'AND `playlist_data`.`object_type`="podcast_episode" AND `podcast_episode`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) ';
                        } else {
                            $sql .= 'AND `playlist_data`.`object_type`="podcast_episode" AND `podcast_episode`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = ? AND `catalog_filter_group_map`.`enabled`=1) ';
                            $params[] = $user_id;
                        }
                    }

                    $sql .= 'ORDER BY `playlist_data`.`track`';
                    break;
                case LibraryItemEnum::VIDEO:
                    $sql = 'SELECT `playlist_data`.`id`, `object_id`, `object_type`, `playlist_data`.`track`, `video`.`time` FROM `playlist_data` INNER JOIN `video` ON `playlist_data`.`object_id` = `video`.`id` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_type`="video" AND `object_id` IS NOT NULL ';
                    if (AmpConfig::get('catalog_filter')) {
                        if ($system) {
                            $sql .= 'AND `playlist_data`.`object_type`="video" AND `video`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) ';
                        } else {
                            $sql .= 'AND `playlist_data`.`object_type`="video" AND `video`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = ? AND `catalog_filter_group_map`.`enabled`=1) ';
                            $params[] = $user_id;
                        }
                    }

                    $sql .= 'ORDER BY `playlist_data`.`track`';
                    break;
                default:
                    $sql      = "SELECT `id`, `object_id`, `object_type`, `track`, 0 AS `time` FROM `playlist_data` WHERE `playlist` = ? AND `object_type` = ? ORDER BY `track`";
                    $params[] = $object_type->value;
                    debug_event(self::class, sprintf('get_items(): %s not handled', $object_type->value), 5);
            }

            //debug_event(self::class, "get_items(): Results:\n" . print_r($results,true), 5);
            $db_results = Dba::read($sql, $params);

            while ($row = Dba::fetch_assoc($db_results)) {
                $results[] = [
                    'object_type' => LibraryItemEnum::from($row['object_type']),
                    'object_id' => (int) $row['object_id'],
                    'track_id' => $row['id'],
                    'track' => (int) $row['track'],
                    'time' => (int) $row['time'],
                ];
            }
        }

        // sort these object types by the track column
        $tracks = array_column($results, 'track');
        array_multisort($tracks, SORT_ASC, $results);

        return $results;
    }

    /**
     * get_media_count
     * This simply returns a int of how many media elements exist in this playlist
     * For now let's consider a dyn_media a single entry
     */
    public function get_media_count(string $type = ''): int
    {
        $user      = Core::get_global('user');
        $user_id   = $user->id ?? -1;
        $params    = [$this->id];
        $all_media = empty($type) || !in_array($type, ['broadcast', 'democratic', 'live_stream', 'podcast_episode', 'song', 'song_preview', 'video']);

        if ($all_media) {
            // empty or invalid type so check for all media types
            $sql = "SELECT COUNT(`playlist_data`.`id`) AS `list_count` FROM `playlist_data` "
                . "LEFT JOIN `broadcast` ON `playlist_data`.`object_id` = `broadcast`.`id` AND `playlist_data`.`object_type` = 'broadcast' "
                . "LEFT JOIN `democratic` ON `playlist_data`.`object_id` = `democratic`.`id` AND `playlist_data`.`object_type` = 'democratic' "
                . "LEFT JOIN `live_stream` ON `playlist_data`.`object_id` = `live_stream`.`id` AND `playlist_data`.`object_type` = 'live_stream' "
                . "LEFT JOIN `podcast_episode` ON `playlist_data`.`object_id` = `podcast_episode`.`id` AND `playlist_data`.`object_type` = 'podcast_episode' "
                . "LEFT JOIN `song` ON `playlist_data`.`object_id` = `song`.`id` AND `playlist_data`.`object_type` = 'song' "
                . "LEFT JOIN `song_preview` ON `playlist_data`.`object_id` = `song_preview`.`id` AND `playlist_data`.`object_type` = 'song_preview' "
                . "LEFT JOIN `video` ON `playlist_data`.`object_id` = `video`.`id` AND `playlist_data`.`object_type` = 'video' "
                . "WHERE `playlist_data`.`playlist` = ?  AND `playlist_data`.`object_type` IS NOT NULL ";
        } else {
            // check for a specific type of object
            $sql = 'SELECT COUNT(`playlist_data`.`id`) AS `list_count` FROM `playlist_data` INNER JOIN `' . $type . '` ON `playlist_data`.`object_id` = `' . $type . '`.`id` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_type` = \'' . $type . '\' AND `object_id` IS NOT NULL ';
        }

        if (AmpConfig::get('catalog_filter')) {
            if ($user_id < 0) {
                if ($all_media) {
                    $sql .= "AND (`playlist_data`.`object_type` = 'live_stream' AND `live_stream`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) "
                        . "OR `playlist_data`.`object_type` = 'podcast_episode' AND `podcast_episode`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) "
                        . "OR `playlist_data`.`object_type` = 'song' AND `song`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) "
                        . "OR `playlist_data`.`object_type` = 'video' AND `video`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1)) ";
                } else {
                    $sql .= "AND `playlist_data`.`object_type` = '$type' AND `$type`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) ";
                }
            } elseif ($all_media) {
                $sql .= "AND (`playlist_data`.`object_type` = 'live_stream' AND `live_stream`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = ? AND `catalog_filter_group_map`.`enabled`=1) "
                    . "OR `playlist_data`.`object_type` = 'podcast_episode' AND `podcast_episode`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = ? AND `catalog_filter_group_map`.`enabled`=1) "
                    . "OR `playlist_data`.`object_type` = 'song' AND `song`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = ? AND `catalog_filter_group_map`.`enabled`=1) "
                    . "OR `playlist_data`.`object_type` = 'video' AND `video`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = ? AND `catalog_filter_group_map`.`enabled`=1)) ";
                $params = [$this->id, $user_id, $user_id, $user_id, $user_id];
            } else {
                $sql .= "AND `playlist_data`.`object_type` = '$type' AND `$type`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = ? AND `catalog_filter_group_map`.`enabled`=1) ";
                $params[] = $user_id;
            }
        }

        $sql .= "GROUP BY `playlist_data`.`playlist`;";

        //debug_event(self::class, "get_media_count(): " . $sql . ' ' . print_r($params, true), 5);

        $db_results = Dba::read($sql, $params);
        $row        = Dba::fetch_assoc($db_results);
        if ($row === []) {
            return 0;
        }

        return (int) $row['list_count'];
    }

    /**
     * get_random_items
     * This is the same as before but we randomize the buggers!
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int, track: int, track_id: int}>
     */
    public function get_random_items(?string $limit = ''): array
    {
        $results = [];
        $user    = Core::get_global('user');
        $user_id = $user->id ?? -1;

        $sql             = 'SELECT DISTINCT `object_type` FROM `playlist_data` WHERE `playlist` = ?';
        $db_object_types = Dba::read($sql, [$this->id]);

        while ($row = Dba::fetch_assoc($db_object_types)) {
            $object_type = $row['object_type'];
            $params      = [$this->id];

            switch ($object_type) {
                case 'song':
                case 'live_stream':
                case 'podcast_episode':
                case 'video':
                    $sql = sprintf('SELECT `playlist_data`.`id`, `object_id`, `object_type`, `playlist_data`.`track` FROM `playlist_data` INNER JOIN `%s` ON `playlist_data`.`object_id` = `%s`.`id` WHERE `playlist_data`.`playlist` = ? AND `object_type` = \'%s\' ', $object_type, $object_type, $object_type);
                    if (AmpConfig::get('catalog_filter')) {
                        if ($user_id < 0) {
                            $sql .= sprintf('AND `playlist_data`.`object_type`=\'%s\' AND `%s`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) ', $object_type, $object_type);
                        } else {
                            $sql .= sprintf('AND `playlist_data`.`object_type`=\'%s\' AND `%s`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = ? AND `catalog_filter_group_map`.`enabled`=1) ', $object_type, $object_type);
                            $params[] = $user_id;
                        }
                    }

                    $sql .= 'ORDER BY RAND()';
                    break;
                default:
                    $sql      = "SELECT `id`, `object_id`, `object_type`, `track` FROM `playlist_data` WHERE `playlist` = ? AND `object_type` = ? ORDER BY RAND()";
                    $params[] = $object_type;
                    debug_event(self::class, sprintf('get_random_items(): %s not handled', $object_type), 5);
            }

            $sql .= (empty($limit))
                ? ''
                : ' LIMIT ' . $limit;

            //debug_event(self::class, "get_random_items(): " . $sql . $limit_sql, 5);
            $db_results = Dba::read($sql, $params);
            while ($row = Dba::fetch_assoc($db_results)) {
                $results[] = [
                    'object_type' => LibraryItemEnum::from($row['object_type']),
                    'object_id' => (int) $row['object_id'],
                    'track' => (int) $row['track'],
                    'track_id' => $row['id']
                ];
            }
        }

        shuffle($results);

        return $results;
    }

    /**
     * get_songs
     * This is called by the batch script, because we can't pass in Dynamic objects they pulled once and then their
     * target song.id is pushed into the array
     * @return int[]
     */
    public function get_songs(): array
    {
        $results = [];
        $user    = Core::get_global('user');
        $user_id = $user->id ?? -1;
        $params  = [$this->id];

        $sql = 'SELECT `playlist_data`.`id`, `object_id`, `object_type`, `playlist_data`.`track` FROM `playlist_data` INNER JOIN `song` ON `playlist_data`.`object_id` = `song`.`id` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_type`="song" AND `object_id` IS NOT NULL ';
        if (AmpConfig::get('catalog_filter')) {
            if ($user_id < 0) {
                $sql .= 'AND `playlist_data`.`object_type`="song" AND `song`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) ';
            } else {
                $sql .= 'AND `playlist_data`.`object_type`="song" AND `song`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = ? AND `catalog_filter_group_map`.`enabled`=1) ';
                $params[] = $user_id;
            }
            $sql .= 'AND `playlist_data`.`object_type`="song" AND `song`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = ? AND `catalog_filter_group_map`.`enabled`=1) ';
            $params[] = $user_id;
        }

        $sql .= "ORDER BY `playlist_data`.`track`";
        $db_results = Dba::read($sql, $params);
        //debug_event(self::class, "get_songs(): " . $sql . ' ' . print_r($params, true), 5);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['object_id'];
        }

        return $results;
    }

    /**
     * get_total_duration
     * Get the total duration of all songs.
     */
    public function get_total_duration(): int
    {
        $songs  = $this->get_songs();
        $idlist = '(' . implode(',', $songs) . ')';
        if ($idlist == '()') {
            return 0;
        }

        $sql        = 'SELECT SUM(`time`) FROM `song` WHERE `id` IN ' . $idlist;
        $db_results = Dba::read($sql);
        $row        = Dba::fetch_row($db_results);
        if ($row === []) {
            return 0;
        }

        //debug_event(self::class, "get_total_duration(): " . $sql, 5);

        return (int) $row[0];
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::PLAYLIST;
    }

    /**
     * has_item
     * look for the track id or the object id in a playlist
     */
    public function has_item(?int $object = null, ?int $track = null, string $object_type = 'song'): bool
    {
        if (!$object && !$track) {
            return false;
        }

        if (!$object && $track > 0) {
            // searching by track
            $sql        = "SELECT `track` FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_type` = ? AND `playlist_data`.`track` = ? LIMIT 1";
            $db_results = Dba::read($sql, [$this->id, $object_type, $track]);
        } elseif ($track > 0) {
            $sql        = "SELECT `object_id` FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_type` = ? AND `track` <= ? AND `playlist_data`.`object_id` = ? LIMIT 1";
            $db_results = Dba::read($sql, [$this->id, $object_type, $track, $object]);
        } else {
            // Search object and optionally check by track
            $sql        = "SELECT `object_id` FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_type` = ? AND `playlist_data`.`object_id` = ? LIMIT 1";
            $db_results = Dba::read($sql, [$this->id, $object_type, $object]);
        }

        $results = Dba::fetch_assoc($db_results);
        if (isset($results['object_id']) || isset($results['track'])) {
            debug_event(self::class, $this->id . ' has_item: ' . $object_type . ' ' . ($results['object_id'] ?? $results['track']), 5);

            return true;
        }

        return false;
    }

    /**
     * has_search
     * Look for a saved smartlist with the same name as this playlist that the user can access
     */
    public function has_search(int $playlist_user): int
    {
        // search for your own playlist
        $sql        = "SELECT `id`, `name` FROM `search` WHERE `user` = ?";
        $db_results = Dba::read($sql, [$playlist_user]);
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($row['name'] == $this->name) {
                return (int) $row['id'];
            }
        }

        // look for public ones
        $user_id    = (int) (Core::get_global('user')?->getId());
        $sql        = "SELECT `id`, `name` FROM `search` WHERE (`type`='public' OR `user` = ?)";
        $db_results = Dba::read($sql, [$user_id]);
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($row['name'] == $this->name) {
                return (int) $row['id'];
            }
        }

        return 0;
    }

    /**
     * Regenerate track numbers to fill gaps.
     */
    public function regenerate_track_numbers(): void
    {
        $index = 1;
        foreach (self::getPlaylistRepository()->getTrackIdsInOrder($this) as $trackId) {
            $this->update_track_number($trackId, $index);
            ++$index;
        }

        $this->_update_last();
    }

    /**
     * set_by_track_number
     * resort a playlist by track number and update
     */
    public function set_by_track_number(int $object_id, int $track): bool
    {
        if (AmpConfig::get('unique_playlist') && $this->has_item($object_id, $track)) {
            return false;
        }

        self::getPlaylistRepository()->replaceTrackAtNumber($this, $object_id, $track);

        debug_event(self::class, $this->id . ' set track: ' . $track . ' to ' . $object_id, 5);

        $this->_update_last();

        return true;
    }

    /**
     * set_items
     * This calls the get_items function and sets it to $this->items which is an array in this object
     */
    public function set_items(): void
    {
        $this->items = $this->get_items();
    }

    /**
     * Sort the tracks and save the new position
     */
    public function sort_tracks(): bool
    {
        $repository = self::getPlaylistRepository();

        $track  = 1;
        $tracks = [];
        foreach ($repository->getTrackIdsSorted($this) as $trackId) {
            $tracks[$trackId] = $track;
            ++$track;
        }

        $repository->setTrackNumbers($tracks);

        $this->_update_last();

        return true;
    }

    /**
     * update_track_number
     * This takes a playlist_data.id and a track (int) and updates the track value
     */
    public function update_track_number(int $track_id, int $index): void
    {
        self::getPlaylistRepository()->setTrackNumber($track_id, $index);
    }

    /**
     * _update_last
     * This updates the playlist last update along with the cached totals
     */
    private function _update_last(): void
    {
        $this->last_update = time();

        self::getPlaylistRepository()->setLastUpdate($this, $this->last_update);

        $this->set_last($this->get_total_duration(), 'last_duration');
        $this->set_last($this->get_media_count(), 'last_count');
    }
}
