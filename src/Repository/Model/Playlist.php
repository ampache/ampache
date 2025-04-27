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
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;

/**
 * This class handles playlists in ampache. it references the playlist* tables
 */
class Playlist extends playlist_object
{
    protected const DB_TABLENAME = 'playlist';

    public ?string $collaborate = '';

    /**
     * @var array<int, array{
     *     object_type: LibraryItemEnum,
     *     object_id: int,
     *     track: int,
     *     track_id: int
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

        $info = $this->get_info($object_id, static::DB_TABLENAME);
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * garbage_collection
     *
     * Clean dead items out of playlists
     */
    public static function garbage_collection(): void
    {
        foreach (['song', 'podcast_episode', 'video'] as $object_type) {
            Dba::write("DELETE FROM `playlist_data` USING `playlist_data` LEFT JOIN `" . $object_type . "` ON `" . $object_type . "`.`id` = `playlist_data`.`object_id` WHERE `" . $object_type . "`.`file` IS NULL AND `playlist_data`.`object_type`='" . $object_type . "';");
        }

        Dba::write("DELETE FROM `playlist_data` USING `playlist_data` LEFT JOIN `live_stream` ON `live_stream`.`id` = `playlist_data`.`object_id` WHERE `live_stream`.`id` IS NULL AND `playlist_data`.`object_type`='live_stream';");
        Dba::write("DELETE FROM `playlist` USING `playlist` LEFT JOIN `playlist_data` ON `playlist_data`.`playlist` = `playlist`.`id` WHERE `playlist_data`.`object_id` IS NULL;");
    }

    /**
     * build_cache
     * This is what builds the cache from the objects
     * @param int[]|string[] $ids
     * @return bool
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
        ?bool $userOnly = false
    ): array {
        if (!$user_id) {
            $user    = Core::get_global('user');
            $user_id = $user?->id ?? 0;
        }

        $key = ($includePublic)
            ? 'playlistids'
            : 'accessibleplaylistids';
        if (empty($playlist_name) && ($user_id > 0 && parent::is_cached($key, $user_id))) {
            return parent::get_from_cache($key, $user_id);
        }

        $is_admin = (
            $userOnly === false ||
            (
                Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN, $user_id) ||
                $user_id == -1
            )
        );
        $sql      = "SELECT `id` FROM `playlist` ";
        $params   = [];
        $join     = 'WHERE';

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
            $hide_string = str_replace('%', '\%', str_replace('_', '\_', (string)Preference::get_by_user($user_id, 'api_hidden_playlists')));
            if (!empty($hide_string)) {
                $sql .= $join . ' `name` NOT LIKE \'' . Dba::escape($hide_string) . "%' ";
            }
        }

        $sql .= "ORDER BY `name`";
        //debug_event(self::class, 'get_playlists query: ' . $sql . ' ' . print_r($params, true), 5);

        $db_results = Dba::read($sql, $params);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        if (
            $playlist_name === '' ||
            $playlist_name === '0'
        ) {
            parent::add_to_cache($key, $user_id, $results);
        }

        return $results;
    }

    /**
     * get_playlist_array
     * Returns a list of playlists accessible by the user with formatted name.
     * @param int|null $user_id
     * @return array<string>
     */
    public static function get_playlist_array(?int $user_id = null): array
    {
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user?->id ?? 0;
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

    public function format(): void
    {
    }

    /**
     * get_items
     * This returns an array of playlist medias that are in this playlist.
     * Because the same media can be on the same playlist twice they are
     * keyed by the uid from playlist_data
     * @return list<array{
     *     object_type: LibraryItemEnum,
     *     object_id: int,
     *     track_id: int,
     *     track: int
     * }>
     */
    public function get_items(): array
    {
        if ($this->isNew()) {
            return [];
        }

        $results = [];
        $user    = Core::get_global('user');
        $user_id = $user?->id ?? -1;

        // Iterate over the object types
        $sql             = 'SELECT DISTINCT `object_type` FROM `playlist_data`';
        $db_object_types = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_object_types)) {
            $object_type = LibraryItemEnum::from($row['object_type']);
            $params      = [$this->id];
            $system      = ($user_id < 0);

            switch ($object_type) {
                case LibraryItemEnum::SONG:
                    $sql = 'SELECT `playlist_data`.`id`, `object_id`, `object_type`, `playlist_data`.`track` FROM `playlist_data` INNER JOIN `song` ON `playlist_data`.`object_id` = `song`.`id` WHERE `playlist_data`.`playlist` = ? AND `object_id` IS NOT NULL ';
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
                    $sql = 'SELECT `playlist_data`.`id`, `object_id`, `object_type`, `playlist_data`.`track` FROM `playlist_data` INNER JOIN `podcast_episode` ON `playlist_data`.`object_id` = `podcast_episode`.`id` WHERE `playlist_data`.`playlist` = ? AND `object_id` IS NOT NULL ';
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
                default:
                    $sql = "SELECT `id`, `object_id`, `object_type`, `track` FROM `playlist_data` WHERE `playlist` = ? AND `playlist_data`.`object_type` != 'song' AND `playlist_data`.`object_type` != 'podcast_episode' ORDER BY `track`";
                    debug_event(self::class, sprintf('get_items(): %s not handled', $object_type->value), 5);
            }

            //debug_event(self::class, "get_items(): Results:\n" . print_r($results,true), 5);
            $db_results = Dba::read($sql, $params);

            while ($row = Dba::fetch_assoc($db_results)) {
                $results[] = [
                    'object_type' => LibraryItemEnum::from($row['object_type']),
                    'object_id' => (int)$row['object_id'],
                    'track_id' => $row['id'],
                    'track' => (int)$row['track']
                ];
            }
        }

        // sort these object types by the track column
        $tracks = array_column($results, 'track');
        array_multisort($tracks, SORT_ASC, $results);

        return $results;
    }

    /**
     * get_random_items
     * This is the same as before but we randomize the buggers!
     * @return list<array{object_type: LibraryItemEnum, object_id: int, track: int, track_id: int}>
     */
    public function get_random_items(?string $limit = ''): array
    {
        $results = [];
        $user    = Core::get_global('user');
        $user_id = $user?->id ?? -1;

        // Iterate over the object types
        $sql             = 'SELECT DISTINCT `object_type` FROM `playlist_data`';
        $db_object_types = Dba::read($sql);

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
                    $sql = "SELECT `id`, `object_id`, `object_type`, `track` FROM `playlist_data` WHERE `playlist` = ? AND `playlist_data`.`object_type` != 'song' AND `playlist_data`.`object_type` != 'podcast_episode' AND `playlist_data`.`object_type` != 'live_stream' ORDER BY RAND()";
                    debug_event(self::class, sprintf('get_items(): %s not handled', $object_type), 5);
            }

            $sql .= (empty($limit))
                ? ''
                : ' LIMIT ' . $limit;

            //debug_event(self::class, "get_random_items(): " . $sql . $limit_sql, 5);
            $db_results = Dba::read($sql, $params);
            while ($row = Dba::fetch_assoc($db_results)) {
                $results[] = [
                    'object_type' => LibraryItemEnum::from($row['object_type']),
                    'object_id' => (int)$row['object_id'],
                    'track' => (int)$row['track'],
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
        $user_id = $user?->id ?? -1;
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
            $results[] = (int)$row['object_id'];
        }

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
        $user_id   = $user?->id ?? -1;
        $params    = [$this->id];
        $all_media = empty($type) || !in_array($type, ['broadcast', 'democratic', 'live_stream', 'podcast_episode', 'song', 'song_preview', 'video']);

        if ($all_media) {
            // empty or invalid type so check for all media types
            $sql = "SELECT COUNT(`playlist_data`.`id`) AS `list_count` FROM `playlist_data` " .
                "LEFT JOIN `broadcast` ON `playlist_data`.`object_id` = `broadcast`.`id` AND `playlist_data`.`object_type` = 'broadcast' " .
                "LEFT JOIN `democratic` ON `playlist_data`.`object_id` = `democratic`.`id` AND `playlist_data`.`object_type` = 'democratic' " .
                "LEFT JOIN `live_stream` ON `playlist_data`.`object_id` = `live_stream`.`id` AND `playlist_data`.`object_type` = 'live_stream' " .
                "LEFT JOIN `podcast_episode` ON `playlist_data`.`object_id` = `podcast_episode`.`id` AND `playlist_data`.`object_type` = 'podcast_episode' " .
                "LEFT JOIN `song` ON `playlist_data`.`object_id` = `song`.`id` AND `playlist_data`.`object_type` = 'song' " .
                "LEFT JOIN `song_preview` ON `playlist_data`.`object_id` = `song_preview`.`id` AND `playlist_data`.`object_type` = 'song_preview' " .
                "LEFT JOIN `video` ON `playlist_data`.`object_id` = `video`.`id` AND `playlist_data`.`object_type` = 'video' " .
                "WHERE `playlist_data`.`playlist` = ?  AND `playlist_data`.`object_type` IS NOT NULL ";
        } else {
            // check for a specific type of object
            $sql = 'SELECT COUNT(`playlist_data`.`id`) AS `list_count` FROM `playlist_data` INNER JOIN `' . $type . '` ON `playlist_data`.`object_id` = `' . $type . '`.`id` WHERE `playlist_data`.`playlist` = ? AND `object_id` IS NOT NULL ';
        }

        if (AmpConfig::get('catalog_filter')) {
            if ($user_id < 0) {
                if ($all_media) {
                    $sql .= "AND (`playlist_data`.`object_type` = 'live_stream' AND `live_stream`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) " .
                        "OR `playlist_data`.`object_type` = 'podcast_episode' AND `podcast_episode`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) " .
                        "OR `playlist_data`.`object_type` = 'song' AND `song`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) " .
                        "OR `playlist_data`.`object_type` = 'video' AND `video`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1)) ";
                } else {
                    $sql .= "AND `playlist_data`.`object_type` = '$type' AND `$type`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) ";
                }
            } elseif ($all_media) {
                $sql .= "AND (`playlist_data`.`object_type` = 'live_stream' AND `live_stream`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = ? AND `catalog_filter_group_map`.`enabled`=1) " .
                    "OR `playlist_data`.`object_type` = 'podcast_episode' AND `podcast_episode`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = ? AND `catalog_filter_group_map`.`enabled`=1) " .
                    "OR `playlist_data`.`object_type` = 'song' AND `song`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = ? AND `catalog_filter_group_map`.`enabled`=1) " .
                    "OR `playlist_data`.`object_type` = 'video' AND `video`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = ? AND `catalog_filter_group_map`.`enabled`=1)) ";
                $params  = [$this->id, $user_id, $user_id, $user_id, $user_id];
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

        return (int)$row['list_count'];
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

    /**
     * update
     * This function takes a key'd array of data and runs updates
     * @param array{
     *     name?: ?string,
     *     pl_type?: ?string,
     *     pl_user?: ?int,
     *     collaborate?: null|list<string>,
     *     last_count?: ?int,
     *     last_duration?: ?int,
     * } $data
     */
    public function update(array $data): int
    {
        if (isset($data['name']) && $data['name'] != $this->name) {
            $this->_update_name($data['name']);
        }

        if (isset($data['pl_type']) && $data['pl_type'] != $this->type) {
            $this->_update_type($data['pl_type']);
        }

        if (isset($data['pl_user']) && $data['pl_user'] != $this->user) {
            $this->_update_user($data['pl_user']);
        }

        if (isset($data['collaborate']) && $data['collaborate'] != $this->collaborate) {
            $this->_update_collaborate($data['collaborate']);
        }

        if (isset($data['last_count']) && $data['last_count'] != $this->last_count) {
            $this->_set_last($data['last_count'], 'last_count');
        }

        if (isset($data['last_duration']) && $data['last_duration'] != $this->last_duration) {
            $this->_set_last($data['last_duration'], 'last_duration');
        }

        // reformat after an update
        $this->format();

        return $this->id;
    }

    /**
     * update_type
     * This updates the playlist type, it calls the generic update_item function
     */
    private function _update_type(string $new_type): void
    {
        if ($this->_update_item('type', $new_type)) {
            $this->type = $new_type;
        }
    }

    /**
     * update_user
     * This updates the playlist type, it calls the generic update_item function
     */
    private function _update_user(int $new_user): void
    {
        if ($this->_update_item('user', $new_user)) {
            $this->user     = $new_user;
            $this->username = User::get_username($new_user);
            $sql            = "UPDATE `playlist` SET `user` = ?, `username` = ? WHERE `playlist`.`user` = ?;";
            Dba::write($sql, [$this->user, $this->username, $this->user]);
        }
    }

    /**
     * update_name
     * This updates the playlist name, it calls the generic update_item function
     */
    private function _update_name(string $new_name): void
    {
        if ($this->_update_item('name', $new_name)) {
            $this->name = $new_name;
        }
    }

    /**
     * _update_collaborate
     * This updates playlist collaborators, it calls the generic update_item function
     * @param string[] $new_list
     */
    private function _update_collaborate(array $new_list): void
    {
        $collaborate = implode(',', $new_list);
        if ($this->_update_item('collaborate', $collaborate)) {
            $this->collaborate = $collaborate;
        }

        $sql = "DELETE FROM `user_playlist_map` WHERE `playlist_id` = ? AND `user_id` NOT IN (" . $collaborate . ");";
        Dba::write($sql, [$this->id]);

        foreach ($new_list as $user_id) {
            $sql = "INSERT IGNORE INTO `user_playlist_map` (`playlist_id`, `user_id`) VALUES (?, ?);";
            Dba::write($sql, [$this->id, $user_id]);
        }
    }

    /**
     * _update_last
     * This updates the playlist last update, it calls the generic update_item function
     */
    private function _update_last(): void
    {
        $last_update = time();
        if ($this->_update_item('last_update', $last_update)) {
            $this->last_update = $last_update;
        }

        $this->_set_last($this->get_total_duration(), 'last_duration');
        $this->_set_last($this->get_media_count(), 'last_count');
    }

    /**
     * _update_item
     * This is the generic update function, it does the escaping and error checking
     */
    private function _update_item(string $field, int|string $value): bool
    {
        if (Core::get_global('user')?->getId() != $this->user && !Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) {
            return false;
        }

        $sql = sprintf('UPDATE `playlist` SET `%s` = ? WHERE `id` = ?', $field);

        return (Dba::write($sql, [$value, $this->id]) !== false);
    }

    /**
     * update_track_number
     * This takes a playlist_data.id and a track (int) and updates the track value
     */
    public function update_track_number(int $track_id, int $index): void
    {
        $sql = "UPDATE `playlist_data` SET `track` = ? WHERE `id` = ?";
        Dba::write($sql, [$index, $track_id]);
    }

    /**
     * Regenerate track numbers to fill gaps.
     */
    public function regenerate_track_numbers(): void
    {
        $index  = 1;
        $sql    = 'SELECT `id` FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? ORDER BY `track`, `id`;';
        $tracks = Dba::read($sql, [$this->id]);

        while ($row = Dba::fetch_assoc($tracks)) {
            $this->update_track_number((int)$row['id'], $index);
            ++$index;
        }

        $this->_update_last();
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
                'object_id' => (int)$song_id,
            ];
        }

        if ($this->add_medias($medias)) {
            Catalog::update_mapping('playlist');

            return true;
        }

        return false;
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
        $sql        = "SELECT MAX(`track`) AS `track` FROM `playlist_data` WHERE `playlist` = ? ";
        $db_results = Dba::read($sql, [$this->id]);
        $row        = Dba::fetch_assoc($db_results);
        $base_track = (int)($row['track'] ?? 0);
        $count      = 0;
        $sql        = "REPLACE INTO `playlist_data` (`playlist`, `object_id`, `object_type`, `track`) VALUES ";
        $values     = [];
        foreach ($medias as $data) {
            $object_type = (is_string($data['object_type']))
                ? LibraryItemEnum::tryFrom((string)$data['object_type'])
                : $data['object_type'];
            if ($unique && in_array($data['object_id'], $track_data)) {
                debug_event(self::class, "Can't add a duplicate " . $object_type?->value . " (" . $data['object_id'] . ") when unique_playlist is enabled", 3);
            } else {
                ++$count;
                $track = $base_track + $count;
                $sql .= "(?, ?, ?, ?), ";
                $values[] = $this->id;
                $values[] = $data['object_id'];
                $values[] = $object_type?->value;
                $values[] = $track;
            } // if valid id
        }

        if ($count !== 0 || $values !== []) {
            Dba::write(rtrim($sql, ', '), $values);
            debug_event(self::class, sprintf('Added %d tracks to playlist: ', $count) . $this->id, 5);
            $this->_update_last();

            return true;
        }

        return false;
    }

    /**
     * check
     * This function creates an empty playlist, gives it a name and type
     */
    public static function check(string $name, string $type, ?int $user_id = null): int
    {
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user?->id ?? -1;
        }

        $results    = [];
        $sql        = "SELECT `id` FROM `playlist` WHERE `name` = ? AND `user` = ? AND `type` = ?";
        $db_results = Dba::read($sql, [$name, $user_id, $type]);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
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
            $user_id = $user?->id ?? -1;
        }

        // check for duplicates
        $existing_id = self::check($name, $type, $user_id);
        if ($existing_id > 0) {
            if (!$existing) {
                return null;
            } else {
                return $existing_id;
            }
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

        Catalog::count_table('playlist');

        return (int)$insert_id;
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
     * set_last
     */
    private function _set_last(int $count, string $column): void
    {
        if (
            $this->id &&
            in_array($column, ['last_count', 'last_duration']) &&
            $count >= 0
        ) {
            $sql = "UPDATE `playlist` SET `" . Dba::escape($column) . "` = " . $count . " WHERE `id` = ?;";
            Dba::write($sql, [$this->id]);
        }
    }

    /**
     * delete_all
     *
     * this deletes all tracks from a playlist, you specify the playlist.id here
     */
    public function delete_all(): bool
    {
        $sql = "DELETE FROM `playlist_data` WHERE `playlist_data`.`playlist` = ?";
        Dba::write($sql, [$this->id]);
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
        $sql = "DELETE FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_id` = ? LIMIT 1";
        Dba::write($sql, [$this->id, $object_id]);
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
        $sql = "DELETE FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`id` = ? LIMIT 1";
        Dba::write($sql, [$this->id, $object_id]);
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
        $sql = "DELETE FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`track` = ? LIMIT 1";
        Dba::write($sql, [$this->id, $track]);
        debug_event(self::class, 'Delete track: ' . $track . ' from ' . $this->id, 5);

        $this->_update_last();

        return true;
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

        $sql = "DELETE FROM `playlist_data` WHERE `playlist` = ? AND `track` = ?;";
        Dba::write($sql, [$this->id, $track]);

        $sql = "INSERT INTO `playlist_data` (`playlist`, `object_type`, `object_id`, `track`) VALUES (?, ?, ?, ?);";
        Dba::write($sql, [$this->id, 'song', $object_id, $track]);

        debug_event(self::class, $this->id . ' set track: ' . $track . ' to ' . $object_id, 5);

        $this->_update_last();

        return true;
    }

    /**
     * has_item
     * look for the track id or the object id in a playlist (TODO song only so extend this to other types)
     */
    public function has_item(?int $object = null, ?int $track = null): bool
    {
        if ($object === null && $track === null) {
            return false;
        }

        if ($object === null && $track !== null) {
            // searching by track
            $sql        = "SELECT `track` FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`track` = ? AND `playlist_data`.`object_type` = 'song' LIMIT 1";
            $db_results = Dba::read($sql, [$this->id, $track]);
        } else {
            if ($track !== null) {
                $sql        = "SELECT `object_id` FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_id` = ? AND `playlist_data`.`object_type` = 'song' AND `track` <= ? LIMIT 1";
                $db_results = Dba::read($sql, [$this->id, $object, $track]);
            } else {
                // Search object and optionally check by track
                $sql        = "SELECT `object_id` FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_id` = ? AND `playlist_data`.`object_type` = 'song' LIMIT 1";
                $db_results = Dba::read($sql, [$this->id, $object]);
            }
        }

        $results = Dba::fetch_assoc($db_results);
        if (isset($results['object_id']) || isset($results['track'])) {
            debug_event(self::class, $this->id . ' has_item: ' . ($results['object_id'] ?? $results['track']), 5);

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
                return (int)$row['id'];
            }
        }

        // look for public ones
        $user_id    = (int)(Core::get_global('user')?->getId());
        $sql        = "SELECT `id`, `name` FROM `search` WHERE (`type`='public' OR `user` = ?)";
        $db_results = Dba::read($sql, [$user_id]);
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($row['name'] == $this->name) {
                return (int)$row['id'];
            }
        }

        return 0;
    }

    /**
     * delete
     * This deletes the current playlist and all associated data
     */
    public function delete(): bool
    {
        $sql = "DELETE FROM `playlist_data` WHERE `playlist` = ?";
        Dba::write($sql, [$this->id]);

        $sql = "DELETE FROM `playlist` WHERE `id` = ?";
        Dba::write($sql, [$this->id]);

        $sql = "DELETE FROM `object_count` WHERE `object_type`='playlist' AND `object_id` = ?";
        Dba::write($sql, [$this->id]);
        Catalog::count_table('playlist');

        return true;
    }

    /**
     * Sort the tracks and save the new position
     */
    public function sort_tracks(): bool
    {
        /* First get all of the songs in order of their tracks */
        $sql = "SELECT `list`.`id` FROM `playlist_data` AS `list` LEFT JOIN `song` ON `list`.`object_id` = `song`.`id` LEFT JOIN `album` ON `song`.`album` = `album`.`id` LEFT JOIN `artist` ON `album`.`album_artist` = `artist`.`id` WHERE `list`.`playlist` = ? ORDER BY `artist`.`name`, `album`.`name`, `album`.`year`, `song`.`disk`, `song`.`track`, `song`.`title`";

        $count      = 1;
        $db_results = Dba::query($sql, [$this->id]);
        $results    = [];

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = [
                'id' => $row['id'],
                'track' => $count
            ];
            ++$count;
        }

        if ($results !== []) {
            $sql = "INSERT INTO `playlist_data` (`id`, `track`) VALUES ";
            foreach ($results as $data) {
                $sql .= "(" . Dba::escape($data['id']) . ", " . Dba::escape($data['track']) . "), ";
            } // foreach re-ordered results

            //replace the last comma
            $sql = substr_replace($sql, "", -2);
            $sql .= "ON DUPLICATE KEY UPDATE `track`=VALUES(`track`)";

            // do this in one go
            Dba::write($sql);
        }

        $this->_update_last();

        return true;
    }

    /**
     * Migrate an object associate stats to a new object
     */
    public static function migrate(string $object_type, int $old_object_id, int $new_object_id): void
    {
        $sql    = "UPDATE `playlist_data` SET `object_id` = ? WHERE `object_id` = ? AND `object_type` = ?;";
        $params = [$new_object_id, $old_object_id, $object_type];

        Dba::write($sql, $params);
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::PLAYLIST;
    }
}
