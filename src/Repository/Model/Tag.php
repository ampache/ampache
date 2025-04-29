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
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\InterfaceImplementationChecker;

/**
 * Tag Class
 *
 * This class handles all of the genre related operations
 *
 */
class Tag extends database_object implements library_item, GarbageCollectibleInterface
{
    protected const DB_TABLENAME = 'tag';

    public int $id = 0;

    public ?string $name = null;

    public int $is_hidden = 0;

    public int $artist = 0;

    public int $album = 0;

    public int $song = 0;

    public int $video = 0;

    /**
     * constructor
     * This takes a tag id and returns all of the relevant information
     */
    public function __construct(?int $tag_id = 0)
    {
        if (!$tag_id) {
            return;
        }

        $info = $this->get_info($tag_id, static::DB_TABLENAME);
        if ($info === []) {
            return;
        }

        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
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
     * construct_from_name
     * This attempts to construct the tag from a name, rather then the ID
     */
    public static function construct_from_name(string $name): Tag
    {
        $tag_id = self::tag_exists($name);

        return new Tag($tag_id);
    }

    /**
     * build_cache
     * This takes an array of object ids and caches all of their information
     * in a single query, cuts down on the connections
     * @param int[]|string[] $ids
     */
    public static function build_cache(array $ids): bool
    {
        if (empty($ids)) {
            return false;
        }

        $idlist     = '(' . implode(',', $ids) . ')';
        $sql        = 'SELECT * FROM `tag` WHERE `id` IN ' . $idlist;
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('tag', (int)$row['id'], $row);
        }

        return true;
    }

    /**
     * build_map_cache
     * This builds a cache of the mappings for the specified object, no limit is given
     * @param string $type
     * @param int[]|string[] $ids
     * @return bool
     */
    public static function build_map_cache(string $type, array $ids): bool
    {
        if (empty($ids)) {
            return false;
        }

        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return false;
        }

        $tags    = [];
        $tag_map = [];

        $idlist     = '(' . implode(',', $ids) . ')';
        $sql        = sprintf('SELECT `tag_map`.`id`, `tag_map`.`tag_id`, `tag`.`name`, `tag_map`.`object_id`, `tag_map`.`user` FROM `tag` LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` WHERE `tag`.`is_hidden` = false AND `tag_map`.`object_type`=\'%s\' AND `tag_map`.`object_id` IN %s', $type, $idlist);
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $tags[$row['object_id']][$row['tag_id']] = [
                'user' => $row['user'],
                'id' => $row['tag_id'],
                'name' => $row['name'],
            ];

            $tag_map[$row['object_id']] = [
                'id' => $row['id'],
                'tag_id' => $row['tag_id'],
                'user' => $row['user'],
                'object_type' => $type,
                'object_id' => $row['object_id'],
            ];
        }

        // Run through our original ids as we also want to cache NULL
        // results
        foreach ($ids as $tagid) {
            if (!isset($tags[$tagid])) {
                $tags[$tagid]    = null;
                $tag_map[$tagid] = null;
            }

            parent::add_to_cache('tag_top_' . $type, $tagid, [$tags[$tagid]]);
            parent::add_to_cache('tag_map_' . $type, $tagid, [$tag_map[$tagid]]);
        }

        return true;
    }

    /**
     * add
     * This is a wrapper function, it figures out what we need to add, be it a tag
     * and map, or just the mapping
     */
    public static function add(string $type, int $object_id, string $value): int
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return 0;
        }

        if (!is_numeric($object_id)) {
            return 0;
        }

        $cleaned_value = str_replace('Folk, World, & Country', 'Folk World & Country', $value);
        if ((string)$cleaned_value === '') {
            return 0;
        }

        // Check and see if the tag exists, if not create it, we need the tag id from this
        if (($tag_id = self::tag_exists($cleaned_value)) === 0) {
            debug_event(self::class, 'Adding new tag {' . $cleaned_value . '}', 5);
            $tag_id = self::add_tag($cleaned_value);
        }

        if (!$tag_id) {
            debug_event(self::class, 'Error unable to create tag value:' . $cleaned_value . ' unknown error', 1);

            return 0;
        }

        // We've got the tag id, let's see if it's already got a map, if not then create the map and return the value
        if (!self::tag_map_exists($type, $object_id, $tag_id)) {
            return self::add_tag_map($type, $object_id, $tag_id);
        }

        return 0;
    }

    /**
     * add_tag
     * This function adds a new tag, for now we're going to limit the tagging a bit
     */
    public static function add_tag(string $value): ?int
    {
        if ((string)$value === '') {
            return null;
        }

        $sql = "REPLACE INTO `tag` SET `name` = ?";
        Dba::write($sql, [$value]);
        $insert_id = (int)Dba::insert_id();

        parent::add_to_cache('tag_name', $value, [$insert_id]);

        return $insert_id;
    }

    /**
     * update
     * Update the name of the tag
     */
    public function update(array $data): ?int
    {
        if ((string)$data['name'] === '') {
            return null;
        }

        $name      = $data['name'] ?? $this->name;
        $is_hidden = (array_key_exists('is_hidden', $data))
            ? (int)$data['is_hidden']
            : 0;

        if ($name != $this->name) {
            debug_event(self::class, 'Updating tag {' . $this->id . '} with name {' . $data['name'] . '}...', 5);
            $sql = 'UPDATE `tag` SET `name` = ? WHERE `id` = ?';
            Dba::write($sql, [$name, $this->id]);
        }

        if ($is_hidden !== $this->is_hidden) {
            debug_event(self::class, 'Hidden tag {' . $this->id . '} with status {' . $is_hidden . '}...', 5);
            $sql = ($is_hidden == 1 && $this->is_hidden == 0)
                ? 'UPDATE `tag` SET `is_hidden` = ?, `artist` = 0, `album` = 0, `song` = 0 WHERE `id` = ?'
                : 'UPDATE `tag` SET `is_hidden` = ? WHERE `id` = ?';
            Dba::write($sql, [$is_hidden, $this->id]);
            // if you had previously hidden this tag then remove the merges too
            if ($is_hidden == 0 && $this->is_hidden == 1) {
                debug_event(self::class, 'Unhiding tag {' . $this->id . '} removing all previous merges', 5);
                $this->remove_merges();
            }

            $this->is_hidden = $is_hidden;
        }

        if (array_key_exists('edit_tags', $data) && $data['edit_tags']) {
            $filterfolk  = str_replace('Folk, World, & Country', 'Folk World & Country', (string) $data['edit_tags']);
            $filterunder = str_replace('_', ', ', $filterfolk);
            $filter      = str_replace(';', ', ', $filterunder);
            $filter_list = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $filter);
            $tag_names   = (is_array($filter_list)) ? array_unique($filter_list) : [];

            // remove merges that don't exist before adding new ones
            $this->remove_merges();

            // apply the new merge list
            foreach ($tag_names as $tag) {
                $merge_to = self::construct_from_name($tag);
                if ($merge_to->id == 0) {
                    self::add_tag($tag);
                    $merge_to = self::construct_from_name($tag);
                }

                $this->merge($merge_to->id, array_key_exists('merge_persist', $data));
            }

            if (!array_key_exists('keep_existing', $data)) {
                $sql = "DELETE FROM `tag_map` WHERE `tag_map`.`tag_id` = ? ";
                Dba::write($sql, [$this->id]);
                if (!array_key_exists('merge_persist', $data)) {
                    $this->delete();
                } else {
                    $sql = "UPDATE `tag` SET `is_hidden` = 1 WHERE `tag`.`id` = ? ";
                    Dba::write($sql, [$this->id]);
                }
            }
        }

        return $this->id;
    }

    /**
     * merge
     * merges this tag to another one.
     */
    public function merge(int $merge_to, bool $is_persistent): void
    {
        if ($this->id != $merge_to) {
            debug_event(self::class, 'Merging tag ' . $this->id . ' into ' . $merge_to . ')...', 5);

            $sql = "REPLACE INTO `tag_map` (`tag_id`, `user`, `object_type`, `object_id`) SELECT " . $merge_to . ",`user`, `object_type`, `object_id` FROM `tag_map` AS `tm` WHERE `tm`.`tag_id` = " . $this->id . " AND NOT EXISTS (SELECT 1 FROM `tag_map` WHERE `tag_map`.`tag_id` = " . $merge_to . " AND `tag_map`.`object_id` = `tm`.`object_id` AND `tag_map`.`object_type` = `tm`.`object_type` AND `tag_map`.`user` = `tm`.`user`)";
            Dba::write($sql);
            if ($is_persistent) {
                $sql = "REPLACE INTO `tag_merge` (`tag_id`, `merged_to`) VALUES (?, ?)";
                Dba::write($sql, [$this->id, $merge_to]);
            }
        }
    }

    /**
     * get_merged_tags
     * Get merged tags to this tag.
     * @return list<array{id: int, name: string, is_hidden: int, count: int}>
     */
    public function get_merged_tags(): array
    {
        $sql = "SELECT `tag`.`id`, `tag`.`name`, `tag`.`is_hidden`, 0 AS `count` FROM `tag_merge` INNER JOIN `tag` ON `tag`.`id` = `tag_merge`.`merged_to` WHERE `tag_merge`.`tag_id` = ? ORDER BY `tag`.`name`;";

        $db_results = Dba::read($sql, [$this->id]);

        $results = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['id']] = ['id' => $row['id'], 'name' => $row['name'], 'is_hidden' => $row['is_hidden'], 'count' => $row['count']];
        }

        return $results;
    }

    /**
     * get_merged_count
     */
    public static function get_merged_count(): int
    {
        $results    = 0;
        $sql        = "SELECT COUNT(DISTINCT `tag_id`) AS `tag_count` FROM `tag_merge`;";
        $db_results = Dba::read($sql);

        if ($row = Dba::fetch_assoc($db_results)) {
            $results = (int)$row['tag_count'];
        }

        return $results;
    }

    /**
     * has_merge
     * Get merged tags to this tag.
     */
    public function has_merge(string $name): bool
    {
        $sql        = "SELECT `tag`.`name` FROM `tag_merge` INNER JOIN `tag` ON `tag`.`id` = `tag_merge`.`merged_to` WHERE `tag_merge`.`tag_id` = ? ORDER BY `tag`.`name` ";
        $db_results = Dba::read($sql, [$this->id]);
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($name == $row['name']) {
                return true;
            }
        }

        return false;
    }

    /**
     * remove_merges
     * Remove merged tags from this tag.
     */
    public function remove_merges(): void
    {
        $sql = "DELETE FROM `tag_merge` WHERE `tag_merge`.`tag_id` = ?;";
        Dba::write($sql, [$this->id]);
    }

    /**
     * add_tag_map
     * This adds a specific tag to the map for specified object
     */
    public static function add_tag_map(string $type, int|string $object_id, int|string $tag_id): int
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            debug_event(self::class, $type . " is not a library item.", 3);

            return 0;
        }

        $tag_id  = (int)($tag_id);
        $item_id = (int)($object_id);

        if (!$tag_id || !$item_id) {
            return 0;
        }

        // If tag merged to another one, add reference to the merge destination
        $parent = new Tag($tag_id);
        $merges = $parent->get_merged_tags();
        if ($parent->is_hidden === 0) {
            $merges[] = ['id' => $parent->id, 'name' => $parent->name];
        }

        $insert_id = 0;
        foreach ($merges as $tag) {
            $sql = "INSERT IGNORE INTO `tag_map` (`tag_id`, `user`, `object_type`, `object_id`) VALUES (?, ?, ?, ?)";
            Dba::write($sql, [$tag['id'], 0, $type, $item_id]);

            $insert_id = (int)Dba::insert_id();
            parent::add_to_cache('tag_map_' . $type, $insert_id, ['tag_id' => $tag_id, 'user' => 0, 'object_type' => $type, 'object_id' => $item_id]);

            switch ($type) {
                case 'album':
                    Dba::write("UPDATE `tag` SET `album` = `album` + 1 WHERE `id` = ?", [$tag['id']]);
                    break;
                case 'artist':
                    Dba::write("UPDATE `tag` SET `artist` = `artist` + 1 WHERE `id` = ?", [$tag['id']]);
                    break;
                case 'song':
                    Dba::write("UPDATE `tag` SET `song` = `song` + 1 WHERE `id` = ?", [$tag['id']]);
                    break;
                case 'video':
                    Dba::write("UPDATE `tag` SET `video` = `video` + 1 WHERE `id` = ?", [$tag['id']]);
                    break;
            }
        }

        return $insert_id;
    }

    /**
     * garbage_collection
     *
     * This cleans out tag_maps that are obsolete and then removes tags that
     * have no maps.
     */
    public static function garbage_collection(): void
    {
        // Remove maps for objects that no longer exist
        Dba::write("DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `song` ON `song`.`id`=`tag_map`.`object_id` WHERE `tag_map`.`object_type`='song' AND `song`.`id` IS NULL;");
        Dba::write("DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `album` ON `album`.`id`=`tag_map`.`object_id` WHERE `tag_map`.`object_type`='album' AND `album`.`id` IS NULL;");
        Dba::write("DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `artist` ON `artist`.`id`=`tag_map`.`object_id` WHERE `tag_map`.`object_type`='artist' AND `artist`.`id` IS NULL;");
        Dba::write("DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `video` ON `video`.`id`=`tag_map`.`object_id` WHERE `tag_map`.`object_type`='video' AND `video`.`id` IS NULL;");
        // Hidden tags are not associated with an object anymore
        Dba::write("DELETE FROM `tag_map` WHERE `tag_id` IN (SELECT `id` FROM `tag` WHERE `is_hidden` = 1)");

        // Now nuke the empty tags (Keep hidden tags)
        Dba::write("DELETE FROM `tag` USING `tag` LEFT JOIN `tag_map` ON `tag`.`id`=`tag_map`.`tag_id` WHERE `tag_map`.`id` IS NULL AND `is_hidden` = 0 AND NOT EXISTS (SELECT 1 FROM `tag_merge` WHERE `tag_merge`.`tag_id` = `tag`.`id`);");

        // delete duplicates
        Dba::write("DELETE `b` FROM `tag_map` AS `a`, `tag_map` AS `b` WHERE `a`.`id` < `b`.`id` AND `a`.`tag_id` <=> `b`.`tag_id` AND `a`.`object_id` <=> `b`.`object_id` AND `a`.`object_type` <=> `b`.`object_type`;");

        // recount all the (currently) valid object types
        Dba::write("UPDATE `tag`, (SELECT `tag_id`, COUNT(`tag_id`) AS `tag_count` FROM `tag_map` WHERE `object_type` = 'album' GROUP BY `tag_id`) AS `tag_count` SET `tag`.`album` = `tag_count`.`tag_count` WHERE `tag`.`album` != `tag_count`.`tag_count` AND `tag_count`.`tag_id` = `tag`.`id`;");
        Dba::write("UPDATE `tag`, (SELECT `tag_id`, COUNT(`tag_id`) AS `tag_count` FROM `tag_map` WHERE `object_type` = 'artist' GROUP BY `tag_id`) AS `tag_count` SET `tag`.`artist` = `tag_count`.`tag_count` WHERE `tag`.`artist` != `tag_count`.`tag_count` AND `tag_count`.`tag_id` = `tag`.`id`;");
        Dba::write("UPDATE `tag`, (SELECT `tag_id`, COUNT(`tag_id`) AS `tag_count` FROM `tag_map` WHERE `object_type` = 'song' GROUP BY `tag_id`) AS `tag_count` SET `tag`.`song` = `tag_count`.`tag_count` WHERE `tag`.`song` != `tag_count`.`tag_count` AND `tag_count`.`tag_id` = `tag`.`id`;");
        Dba::write("UPDATE `tag`, (SELECT `tag_id`, COUNT(`tag_id`) AS `tag_count` FROM `tag_map` WHERE `object_type` = 'video' GROUP BY `tag_id`) AS `tag_count` SET `tag`.`video` = `tag_count`.`tag_count` WHERE `tag`.`video` != `tag_count`.`tag_count` AND `tag_count`.`tag_id` = `tag`.`id`;");
        // reset tags without an object in tag_map
        Dba::write("UPDATE `tag` SET `tag`.`artist` = 0 WHERE `tag`.`artist` != 0 AND `tag`.`id` NOT IN (SELECT `tag_map`.`tag_id` FROM `tag_map` WHERE `tag_map`.`object_type` = 'artist');");
        Dba::write("UPDATE `tag` SET `tag`.`album` = 0 WHERE `tag`.`album` != 0 AND `tag`.`id` NOT IN (SELECT `tag_map`.`tag_id` FROM `tag_map` WHERE `tag_map`.`object_type` = 'album');");
        Dba::write("UPDATE `tag` SET `tag`.`song` = 0 WHERE `tag`.`song` != 0 AND `tag`.`id` NOT IN (SELECT `tag_map`.`tag_id` FROM `tag_map` WHERE `tag_map`.`object_type` = 'song');");
        Dba::write("UPDATE `tag` SET `tag`.`video` = 0 WHERE `tag`.`video` != 0 AND `tag`.`id` NOT IN (SELECT `tag_map`.`tag_id` FROM `tag_map` WHERE `tag_map`.`object_type` = 'video');");
    }

    /**
     * delete
     *
     * Delete the tag and all maps
     */
    public function delete(): void
    {
        $sql = "DELETE FROM `tag_map` WHERE `tag_map`.`tag_id` = ?";
        Dba::write($sql, [$this->id]);

        $sql = "DELETE FROM `tag_merge` WHERE `tag_merge`.`tag_id` = ?";
        Dba::write($sql, [$this->id]);

        $sql = "DELETE FROM `tag` WHERE `tag`.`id` = ? ";
        Dba::write($sql, [$this->id]);

        // Call the garbage collector to clean everything
        self::garbage_collection();

        parent::clear_cache();
    }

    /**
     * tag_exists
     * This checks to see if a tag exists, this has nothing to do with objects or maps
     */
    public static function tag_exists(string $value): int
    {
        if (parent::is_cached('tag_name', $value)) {
            return (int)(parent::get_from_cache('tag_name', $value))[0];
        }

        $sql        = "SELECT `id` FROM `tag` WHERE `name` = ?";
        $db_results = Dba::read($sql, [$value]);
        $results    = Dba::fetch_assoc($db_results);

        if (array_key_exists('id', $results)) {
            parent::add_to_cache('tag_name', $value, [$results['id']]);

            return (int)$results['id'];
        }

        return 0;
    }

    /**
     * tag_map_exists
     * This looks to see if the current mapping of the current object exists
     */
    public static function tag_map_exists(string $type, int $object_id, int $tag_id): bool
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            debug_event(self::class, 'Requested type is not a library item.', 3);

            return false;
        }

        $sql        = "SELECT * FROM `tag_map` LEFT JOIN `tag` ON `tag`.`id` = `tag_map`.`tag_id` LEFT JOIN `tag_merge` ON `tag`.`id`=`tag_merge`.`tag_id` WHERE (`tag_map`.`tag_id` = ? OR `tag_map`.`tag_id` = `tag_merge`.`merged_to`) AND `tag_map`.`user` = ? AND `tag_map`.`object_id` = ? AND `tag_map`.`object_type` = ?";
        $db_results = Dba::read($sql, [$tag_id, 0, $object_id, $type]);
        $results    = Dba::fetch_assoc($db_results);

        if (array_key_exists('id', $results)) {
            return true;
        }

        return false;
    }

    /**
     * get_top_tags
     * This gets the top tags for the specified object using limit
     * @return list<array{id: int, name: string, is_hidden: int, count: int}>
     */
    public static function get_top_tags(string $type, int $object_id, ?int $limit = 10): array
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return [];
        }

        $object_id  = (int)($object_id);
        $limit_text = ($limit == 0)
            ? ''
            : 'LIMIT ' . $limit;
        $sql   = (in_array($type, ['artist', 'album', 'song', 'video']))
            ? 'SELECT `tag`.`id`, `tag`.`name`, `tag`.`is_hidden`, `tag`.`' . $type . '` AS `count` FROM `tag` LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` WHERE `tag`.`is_hidden` = false AND `tag_map`.`object_type` = ? AND `tag_map`.`object_id` = ? ORDER BY `' . $type . '` DESC ' . $limit_text
            : 'SELECT `tag`.`id`, `tag`.`name`, `tag`.`is_hidden`, (SUM(`tag`.`artist`)+SUM(`tag`.`album`)+SUM(`tag`.`song`)) AS `count` FROM `tag` LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` WHERE `tag`.`is_hidden` = false AND `tag_map`.`object_type` = ? AND `tag_map`.`object_id` = ? ORDER BY `count` DESC ' . $limit_text;

        $db_results = Dba::read($sql, [$type, $object_id]);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = ['id' => $row['id'], 'name' => $row['name'], 'is_hidden' => $row['is_hidden'], 'count' => $row['count']];
        }

        return $results;
    }

    /**
     * get_object_tags
     * Display all tags that apply to matching target type of the specified id
     * @return list<array{id: int, name: string, is_hidden: int, user: int}>
     */
    public static function get_object_tags(string $type, ?int $object_id = null): array
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return [];
        }

        $params = [$type];
        $sql    = "SELECT `tag_map`.`id`, `tag`.`name`, `tag`.`is_hidden`, `tag_map`.`user` FROM `tag` LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` WHERE `tag`.`is_hidden` = false AND `tag_map`.`object_type` = ?";
        if ($object_id !== null) {
            $sql .= " AND `tag_map`.`object_id` = ?";
            $params[] = $object_id;
        }

        $db_results = Dba::read($sql, $params);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'is_hidden' => (int)$row['is_hidden'],
                'user' => (int)$row['user'],
            ];
        }

        return $results;
    }

    /**
     * get_tag_objects
     * This gets the objects from a specified tag and returns an array of object ids, nothing more
     * @return int[]
     */
    public static function get_tag_objects(string $type, int $tag_id, int $count = 0, int $offset = 0): array
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return [];
        }

        $tag_sql   = ($tag_id === 0) ? "" : "`tag_map`.`tag_id` = ? AND";
        $sql_param = ($tag_sql === "") ? [$type] : [$tag_id, $type];
        $limit_sql = "";
        if ($count) {
            $limit_sql = " LIMIT ";
            if ($offset) {
                $limit_sql .= $offset . ', ';
            }

            $limit_sql .= (string)($count);
        }

        $sql = sprintf('SELECT DISTINCT `tag_map`.`object_id` FROM `tag_map` WHERE %s `tag_map`.`object_type` = ?', $tag_sql);
        if (AmpConfig::get('catalog_disable') && in_array($type, ['artist', 'album', 'album_disk', 'song', 'video'])) {
            $sql .= "AND " . Catalog::get_enable_filter($type, '`tag_map`.`object_id`');
        }

        $sql .= $limit_sql;
        $db_results = Dba::read($sql, $sql_param);

        $results = [];

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['object_id'];
        }

        return $results;
    }

    /**
     * get_tag_ids
     * This gets the objects from a specified tag and returns an array of object ids, nothing more
     * @return int[]
     */
    public static function get_tag_ids(string $type, ?string $count = '', ?string $offset = ''): array
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return [];
        }

        $limit_sql = "";
        if ($count) {
            $limit_sql = " LIMIT ";
            if ($offset) {
                $limit_sql .= $offset . ', ';
            }

            $limit_sql .= $count;
        }

        $sql = "SELECT DISTINCT `tag_map`.`tag_id` FROM `tag_map` WHERE `tag_map`.`object_type` = ? ";
        if (AmpConfig::get('catalog_disable') && in_array($type, ['artist', 'album', 'album_disk', 'song', 'video'])) {
            $sql .= "AND " . Catalog::get_enable_filter($type, '`tag_map`.`object_id`');
        }

        $sql .= $limit_sql;
        $db_results = Dba::read($sql, [$type]);

        $results = [];

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['tag_id'];
        }

        return $results;
    }

    /**
     * get_tags
     * This is a non-object non type dependent function that just returns tags
     * we've got, it can take filters (this is used by the tag cloud)
     * @return list<array{id: int, name: string, is_hidden: int, count: int}>
     */
    public static function get_tags(?string $type = '', ?int $limit = 0, ?string $order = 'count'): array
    {
        $cacheType = (empty($type)) ? 'all' : $type;
        if (parent::is_cached('tags_list', $cacheType)) {
            //debug_event(self::class, 'Tags list found into cache memory!', 5);
            return parent::get_from_cache('tags_list', $cacheType);
        }

        $results = [];
        if ($type == 'tag_hidden') {
            $sql       = "SELECT `tag`.`id`, `tag`.`name`, `tag`.`is_hidden`, 0 AS `count` FROM `tag` WHERE (`tag`.`is_hidden` = 1 OR (`tag`.`album` = 0 AND `tag`.`artist` = 0 AND `tag`.`song` = 0 AND `tag`.`video` = 0 )) ";
        } else {
            $type_select = (empty($type) || $type == 'all_hidden')
                ? ', (SUM(`tag`.`artist`)+SUM(`tag`.`album`)+SUM(`tag`.`song`)) AS `count`'
                : sprintf(', `tag`.`%s` AS `count`', scrub_in($type));
            $type_where = match ($type) {
                'album', 'song', 'video', 'artist' => " AND `tag`.`" . scrub_in($type) . "` != 0 ",
                default => " ",
            };

            $hidden_where = ($type == 'all_hidden')
                ? '`tag`.`is_hidden` IN (0,1)'
                : '`tag`.`is_hidden` = 0';

            $sql = (AmpConfig::get('catalog_filter') && Core::get_global('user') instanceof User && Core::get_global('user')->id > 0)
                ? sprintf('SELECT `tag`.`id` AS `id`, `tag`.`name`, `tag`.`is_hidden`%s FROM `tag` WHERE %s%sAND %s ', $type_select, $hidden_where, $type_where, Catalog::get_user_filter('tag', Core::get_global('user')->id))
                : sprintf('SELECT `tag`.`id` AS `id`, `tag`.`name`, `tag`.`is_hidden`%s FROM `tag` WHERE %s%s', $type_select, $hidden_where, $type_where);

            $sql .= (empty($type) || $type == 'all_hidden')
                ? "GROUP BY `tag`.`id`, `tag`.`name`, `tag`.`is_hidden`, `tag`.`artist`, `tag`.`album`, `tag`.`song` "
                : "GROUP BY `tag`.`id`, `tag`.`name`, `tag`.`is_hidden`, `count` ";
        }

        $order = ($order == 'count')
            ? "`" . $order . "` DESC"
            : "`" . $order . "`";

        $sql .= "ORDER BY " . $order;

        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }

        //debug_event(self::class, 'get_tags ' . $sql, 5);

        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['id']] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'is_hidden' => $row['is_hidden'],
                'count' => $row['count'] ?? 0
            ];
        }

        parent::add_to_cache('tags_list', $cacheType, $results);

        return $results;
    }

    /**
     * get_display
     * This returns a csv formatted version of the tags that we are given
     * it also takes a type so that it knows how to return it, this is used
     * by the formatting functions of the different objects
     * @param list<array{id: int, name: string, is_hidden: int, count: int}> $tags
     * @param bool $link
     * @param string|null $filter_type
     * @return string
     */
    public static function get_display(array $tags, ?bool $link = false, ?string $filter_type = ''): string
    {
        //debug_event(self::class, 'Get display tags called...', 5);
        if (empty($tags)) {
            return '';
        }

        $web_path = AmpConfig::get_web_path('/client');
        $results  = '';

        // Iterate through the tags, format them according to type and element id
        foreach ($tags as $value) {
            if ($link) {
                $results .= '<a href="' . $web_path . '/browse.php?action=tag&show_tag=' . $value['id'] . (empty($filter_type) ? '' : '&type=' . $filter_type) . '" title="' . scrub_out($value['name']) . '">';
            }

            $results .= $value['name'];
            if ($link) {
                $results .= '</a>';
            }

            $results .= ', ';
        }

        return rtrim($results, ', ');
    }

    /**
     * update_tag_list
     * Update the tags list based on a comma-separated list
     *  (ex. tag1,tag2,tag3,..)
     */
    public static function update_tag_list(string $tags_comma, string $object_type, int $object_id, bool $overwrite): bool
    {
        if (!strlen((string) $tags_comma) > 0) {
            return self::remove_all_maps($object_type, $object_id);
        }

        debug_event(self::class, sprintf('update_tag_list %s {%d}', $object_type, $object_id), 5);
        // tags from your file can be in a terrible format
        $filterfolk  = str_replace('Folk, World, & Country', 'Folk World & Country', $tags_comma);
        $filterunder = str_replace('_', ', ', $filterfolk);
        $filter      = str_replace(';', ', ', $filterunder);
        $filter_list = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $filter);
        $editedTags  = (is_array($filter_list)) ? array_unique($filter_list) : [];

        $change       = false;
        $current_tags = self::get_top_tags($object_type, $object_id, 0);
        foreach ($current_tags as $ctv) {
            $found = false;
            if ($ctv['id'] != '') {
                $ctag = new Tag($ctv['id']);
                if ($ctag->isNew()) {
                    continue;
                }

                //debug_event(self::class, 'update_tag_list ' . $object_type . ' current_tag ' . print_r($ctv, true), 5);
                foreach ($editedTags as $tag_name) {
                    if (strtolower((string)$ctag->name) === strtolower($tag_name)) {
                        $found = true;
                        break;
                    }

                    // check if this thing has been renamed into something else
                    $merged = self::construct_from_name($tag_name);
                    if ($merged->id && $merged->is_hidden && $merged->has_merge((string)$ctag->name)) {
                        $found = true;
                        break;
                    }
                }

                if ($found) {
                    //debug_event(self::class, 'update_tag_list ' . $object_type . ' matched {' . $ctag->id . '} to ' . $tag_name, 5);
                    if (($key = array_search((string)$ctag->name, $editedTags)) !== false) {
                        unset($editedTags[$key]);
                    }
                }

                if (
                    !$found &&
                    $overwrite
                ) {
                    debug_event(self::class, 'update_tag_list ' . $object_type . ' delete {' . $ctag->name . '}', 5);
                    $ctag->remove_map($object_type, $object_id);
                    $change = true;
                }
            }
        }

        // Look if we need to add some new tags
        foreach ($editedTags as $tag_name) {
            if ($tag_name != '') {
                debug_event(self::class, 'update_tag_list ' . $object_type . ' add {' . $tag_name . '}', 5);
                self::add($object_type, $object_id, $tag_name);
                $change = true;
            }
        }

        return $change;
    }

    /**
     * clean_to_existing
     * Clean tag list to existing tag list only
     * @param string[]|string $tags
     * @return string[]|string
     */
    public static function clean_to_existing(array|string $tags): array|string
    {
        if (is_array($tags)) {
            $taglist = $tags;
        } else {
            $filterfolk  = str_replace('Folk, World, & Country', 'Folk World & Country', $tags);
            $filterunder = str_replace('_', ', ', $filterfolk);
            $filter      = str_replace(';', ', ', $filterunder);
            $filter_list = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $filter);
            $taglist     = (is_array($filter_list)) ? array_unique($filter_list) : [];
        }

        $ret = [];
        foreach ($taglist as $tag) {
            $tag = trim((string)$tag);
            if (
                $tag !== '' &&
                $tag !== '0' &&
                self::tag_exists($tag)
            ) {
                $ret[] = $tag;
            }
        }

        return (is_array($tags)
            ? $ret
            : implode(",", $ret));
    }

    /**
     * remove_map
     * This will only remove tag maps for the current user
     */
    public function remove_map(string $type, int $object_id): bool
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return false;
        }

        $sql = "DELETE FROM `tag_map` WHERE `tag_id` = ? AND `object_type` = ? AND `object_id` = ? AND `user` = ?";
        Dba::write($sql, [$this->id, $type, $object_id, 0]);

        switch ($type) {
            case 'album':
                Dba::write("UPDATE `tag` SET `album` = `album` - 1 WHERE `id` = ? AND `album` > 0;", [$this->id]);
                break;
            case 'artist':
                Dba::write("UPDATE `tag` SET `artist` = `artist` - 1 WHERE `id` = ? AND `artist` > 0;", [$this->id]);
                break;
            case 'song':
                Dba::write("UPDATE `tag` SET `song` = `song` - 1 WHERE `id` = ? AND `song` > 0;", [$this->id]);
                break;
            case 'video':
                Dba::write("UPDATE `tag` SET `video` = `video` - 1 WHERE `id` = ? AND `video` > 0;", [$this->id]);
                break;
        }

        return true;
    }

    /**
     * remove_all_maps
     * Clear all the tags from an object when there isn't anything there
     */
    public static function remove_all_maps(string $object_type, int $object_id): bool
    {
        if (!InterfaceImplementationChecker::is_library_item($object_type)) {
            return false;
        }

        $sql = "DELETE FROM `tag_map` WHERE `object_type` = ? AND `object_id` = ?";
        Dba::write($sql, [$object_type, $object_id]);

        switch ($object_type) {
            case 'album':
                Dba::write("UPDATE `tag`, (SELECT `tag_id`, COUNT(`tag_id`) AS `tag_count` FROM `tag_map` WHERE `object_type` = 'album' GROUP BY `tag_id`) AS `tag_count` SET `tag`.`album` = `tag_count`.`tag_count` WHERE `tag`.`album` != `tag_count`.`tag_count` AND `tag_count`.`tag_id` = `tag`.`id`;");
                break;
            case 'artist':
                Dba::write("UPDATE `tag`, (SELECT `tag_id`, COUNT(`tag_id`) AS `tag_count` FROM `tag_map` WHERE `object_type` = 'artist' GROUP BY `tag_id`) AS `tag_count` SET `tag`.`artist` = `tag_count`.`tag_count` WHERE `tag`.`artist` != `tag_count`.`tag_count` AND `tag_count`.`tag_id` = `tag`.`id`;");
                break;
            case 'song':
                Dba::write("UPDATE `tag`, (SELECT `tag_id`, COUNT(`tag_id`) AS `tag_count` FROM `tag_map` WHERE `object_type` = 'song' GROUP BY `tag_id`) AS `tag_count` SET `tag`.`song` = `tag_count`.`tag_count` WHERE `tag`.`song` != `tag_count`.`tag_count` AND `tag_count`.`tag_id` = `tag`.`id`;");
                break;
            case 'video':
                Dba::write("UPDATE `tag`, (SELECT `tag_id`, COUNT(`tag_id`) AS `tag_count` FROM `tag_map` WHERE `object_type` = 'video' GROUP BY `tag_id`) AS `tag_count` SET `tag`.`video` = `tag_count`.`tag_count` WHERE `tag`.`video` != `tag_count`.`tag_count` AND `tag_count`.`tag_id` = `tag`.`id`;");
                break;
        }

        return true;
    }

    /**
     * Get item keywords for metadata searches.
     * @return array{tag: array{important: true, label: string, value: string}}
     */
    public function get_keywords(): array
    {
        return [
            'tag' => [
                'important' => true,
                'label' => T_('Genre'),
                'value' => (string)$this->name,
            ]
        ];
    }

    /**
     * get_fullname
     */
    public function get_fullname(): ?string
    {
        return $this->name;
    }

    /**
     * Get item link.
     */
    public function get_link(): string
    {
        return '';
    }

    /**
     * Get item f_link.
     */
    public function get_f_link(): string
    {
        return '';
    }

    /**
     * Return a formatted link to the parent object (if appliccable)
     */
    public function get_f_parent_link(): ?string
    {
        return null;
    }

    /**
     * Get item f_time or f_time_h.
     */
    public function get_f_time(): string
    {
        return '';
    }

    /**
     * get_parent
     * Return parent `object_type`, `object_id`; null otherwise.
     */
    public function get_parent(): ?array
    {
        return null;
    }

    public function get_childrens(): array
    {
        return [];
    }

    /**
     * Search for direct children of an object
     * @param string $name
     */
    public function get_children($name): array
    {
        debug_event(self::class, 'get_children ' . $name, 5);

        return [];
    }

    /**
     * @return list<array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function get_medias(?string $filter_type = null): array
    {
        $medias = [];
        if ($filter_type) {
            $ids = self::get_tag_objects($filter_type, $this->id);
            foreach ($ids as $object_id) {
                $medias[] = ['object_type' => LibraryItemEnum::from($filter_type), 'object_id' => $object_id];
            }
        }

        return $medias;
    }

    public function get_user_owner(): ?int
    {
        return null;
    }

    /**
     * get_default_art_kind
     */
    public function get_default_art_kind(): string
    {
        return 'default';
    }

    /**
     * get_description
     */
    public function get_description(): string
    {
        return '';
    }

    /**
     * display_art
     * @param int $thumb
     * @param bool $force
     */
    public function display_art($thumb = 2, $force = false): void
    {
        if ($this->has_art() || $force) {
            Art::display('tag', $this->id, (string)$this->get_fullname(), $thumb);
        }
    }

    public function has_art(): bool
    {
        return Art::has_db($this->id, 'tag');
    }

    /**
     * Migrate an object associate stats to a new object
     */
    public static function migrate(string $object_type, int $old_object_id, int $new_object_id): void
    {
        $sql = "UPDATE IGNORE `tag_map` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";

        Dba::write($sql, [$new_object_id, $object_type, $old_object_id]);
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::TAG;
    }
}
