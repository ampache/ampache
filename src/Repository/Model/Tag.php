<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
use PDOStatement;

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
    public ?string $name;
    public int $is_hidden;

    public $f_name;

    /**
     * constructor
     * This takes a tag id and returns all of the relevant information
     * @param int|null $tag_id
     */
    public function __construct($tag_id = 0)
    {
        if (!$tag_id) {
            return;
        }

        $info = $this->get_info($tag_id, static::DB_TABLENAME);
        if (empty($info)) {
            return;
        }
        foreach ($info as $key => $value) {
            $this->$key = $value;
        } // end foreach

        // the ui is sometimes looking for a formatted name...
        $this->f_name = scrub_out($this->name);
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
     * @param string $name
     * @return Tag
     */
    public static function construct_from_name($name): Tag
    {
        $tag_id = self::tag_exists($name);

        return new Tag($tag_id);
    }

    /**
     * build_cache
     * This takes an array of object ids and caches all of their information
     * in a single query, cuts down on the connections
     * @param array $ids
     */
    public static function build_cache($ids): bool
    {
        if (empty($ids)) {
            return false;
        }
        $idlist     = '(' . implode(',', $ids) . ')';
        $sql        = "SELECT * FROM `tag` WHERE `id` IN $idlist";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('tag', $row['id'], $row);
        }

        return true;
    }

    /**
     * build_map_cache
     * This builds a cache of the mappings for the specified object, no limit is given
     * @param string $type
     * @param array $ids
     * @return bool
     * @params array $ids
     */
    public static function build_map_cache($type, $ids): bool
    {
        if (empty($ids)) {
            return false;
        }

        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return false;
        }
        $tags    = array();
        $tag_map = array();

        $idlist     = '(' . implode(',', $ids) . ')';
        $sql        = "SELECT `tag_map`.`id`, `tag_map`.`tag_id`, `tag`.`name`, `tag_map`.`object_id`, `tag_map`.`user` FROM `tag` LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` WHERE `tag`.`is_hidden` = false AND `tag_map`.`object_type`='$type' AND `tag_map`.`object_id` IN $idlist";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $tags[$row['object_id']][$row['tag_id']] = array(
                'user' => $row['user'],
                'id' => $row['tag_id'],
                'name' => $row['name']
            );
            $tag_map[$row['object_id']] = array(
                'id' => $row['id'],
                'tag_id' => $row['tag_id'],
                'user' => $row['user'],
                'object_type' => $type,
                'object_id' => $row['object_id']
            );
        }

        // Run through our original ids as we also want to cache NULL
        // results
        foreach ($ids as $tagid) {
            if (!isset($tags[$tagid])) {
                $tags[$tagid]    = null;
                $tag_map[$tagid] = null;
            }
            parent::add_to_cache('tag_top_' . $type, $tagid, array($tags[$tagid]));
            parent::add_to_cache('tag_map_' . $type, $tagid, array($tag_map[$tagid]));
        }

        return true;
    }

    /**
     * add
     * This is a wrapper function, it figures out what we need to add, be it a tag
     * and map, or just the mapping
     * @param string $type
     * @param int $object_id
     * @param string $value
     * @param bool $user
     * @return bool|int
     */
    public static function add($type, $object_id, $value, $user = true)
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return false;
        }

        if (!is_numeric($object_id)) {
            return false;
        }

        $cleaned_value = str_replace('Folk, World, & Country', 'Folk World & Country', $value);

        if (!strlen((string)$cleaned_value)) {
            return false;
        }

        if ($user === true) {
            $uid = (int)(Core::get_global('user')?->getId());
        } else {
            $uid = (int)($user);
        }

        // Check and see if the tag exists, if not create it, we need the tag id from this
        if (!$tag_id = self::tag_exists($cleaned_value)) {
            debug_event(self::class, 'Adding new tag {' . $cleaned_value . '}', 5);
            $tag_id = self::add_tag($cleaned_value);
        }

        if (!$tag_id) {
            debug_event(self::class, 'Error unable to create tag value:' . $cleaned_value . ' unknown error', 1);

            return false;
        }

        // We've got the tag id, let's see if it's already got a map, if not then create the map and return the value
        if (!$map_id = self::tag_map_exists($type, $object_id, (int)$tag_id, $uid)) {
            $map_id = self::add_tag_map($type, $object_id, (int)$tag_id, $user);
        }

        return (int)$map_id;
    }

    /**
     * add_tag
     * This function adds a new tag, for now we're going to limit the tagging a bit
     * @param string $value
     * @return int|null
     */
    public static function add_tag($value): ?int
    {
        if (!strlen((string)$value)) {
            return null;
        }

        $sql = "REPLACE INTO `tag` SET `name` = ?";
        Dba::write($sql, array($value));
        $insert_id = (int)Dba::insert_id();

        parent::add_to_cache('tag_name', $value, array($insert_id));

        return $insert_id;
    }

    /**
     * update
     * Update the name of the tag
     * @param array $data
     * @return int|false
     */
    public function update(array $data)
    {
        if (!strlen((string)$data['name'])) {
            return false;
        }
        $name      = $data['name'] ?? $this->name;
        $is_hidden = (array_key_exists('is_hidden', $data))
            ? (int)$data['is_hidden']
            : 0;

        if ($name != $this->name) {
            debug_event(self::class, 'Updating tag {' . $this->id . '} with name {' . $data['name'] . '}...', 5);
            $sql = 'UPDATE `tag` SET `name` = ? WHERE `id` = ?';
            Dba::write($sql, array($name, $this->id));
        }
        if ($is_hidden != (int)$this->is_hidden) {
            debug_event(self::class, 'Hidden tag {' . $this->id . '} with status {' . $is_hidden . '}...', 5);
            $sql = 'UPDATE `tag` SET `is_hidden` = ? WHERE `id` = ?';
            Dba::write($sql, array($is_hidden, $this->id));
            // if you had previously hidden this tag then remove the merges too
            if ($is_hidden == 0 && (int)$this->is_hidden == 1) {
                debug_event(self::class, 'Unhiding tag {' . $this->id . '} removing all previous merges', 5);
                $this->remove_merges();
            }
            $this->is_hidden = $is_hidden;
        }

        if (array_key_exists('edit_tags', $data) && $data['edit_tags']) {
            $filterfolk  = str_replace('Folk, World, & Country', 'Folk World & Country', $data['edit_tags']);
            $filterunder = str_replace('_', ', ', $filterfolk);
            $filter      = str_replace(';', ', ', $filterunder);
            $filter_list = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $filter);
            $tag_names   = (is_array($filter_list)) ? array_unique($filter_list) : array();

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
                Dba::write($sql, array($this->id));
                if (!array_key_exists('merge_persist', $data)) {
                    $this->delete();
                } else {
                    $sql = "UPDATE `tag` SET `is_hidden` = 1 WHERE `tag`.`id` = ? ";
                    Dba::write($sql, array($this->id));
                }
            }
        }

        return $this->id;
    }

    /**
     * merge
     * merges this tag to another one.
     * @param int $merge_to
     * @param bool $is_persistent
     */
    public function merge($merge_to, $is_persistent): void
    {
        if ($this->id != $merge_to) {
            debug_event(self::class, 'Merging tag ' . $this->id . ' into ' . $merge_to . ')...', 5);

            $sql = "REPLACE INTO `tag_map` (`tag_id`, `user`, `object_type`, `object_id`) SELECT " . $merge_to . ",`user`, `object_type`, `object_id` FROM `tag_map` AS `tm` WHERE `tm`.`tag_id` = " . $this->id . " AND NOT EXISTS (SELECT 1 FROM `tag_map` WHERE `tag_map`.`tag_id` = " . $merge_to . " AND `tag_map`.`object_id` = `tm`.`object_id` AND `tag_map`.`object_type` = `tm`.`object_type` AND `tag_map`.`user` = `tm`.`user`)";
            Dba::write($sql);
            if ($is_persistent) {
                $sql = "REPLACE INTO `tag_merge` (`tag_id`, `merged_to`) VALUES (?, ?)";
                Dba::write($sql, array($this->id, $merge_to));
            }
        }
    }

    /**
     * get_merged_tags
     * Get merged tags to this tag.
     * @return array
     */
    public function get_merged_tags(): array
    {
        $sql = "SELECT `tag`.`id`, `tag`.`name`FROM `tag_merge` INNER JOIN `tag` ON `tag`.`id` = `tag_merge`.`merged_to` WHERE `tag_merge`.`tag_id` = ? ORDER BY `tag`.`name` ";

        $db_results = Dba::read($sql, array($this->id));

        $results = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['id']] = array('id' => $row['id'], 'name' => $row['name']);
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
     * @param string $name
     */
    public function has_merge($name): bool
    {
        $sql        = "SELECT `tag`.`name` FROM `tag_merge` INNER JOIN `tag` ON `tag`.`id` = `tag_merge`.`merged_to` WHERE `tag_merge`.`tag_id` = ? ORDER BY `tag`.`name` ";
        $db_results = Dba::read($sql, array($this->id));
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
        Dba::write($sql, array($this->id));
    }

    /**
     * add_tag_map
     * This adds a specific tag to the map for specified object
     * @param string $type
     * @param int|string $object_id
     * @param int|string $tag_id
     * @param bool $user
     * @return bool|int
     */
    public static function add_tag_map($type, $object_id, $tag_id, $user = true)
    {
        if ($user === true) {
            $uid = (int)(Core::get_global('user')?->getId());
        } else {
            $uid = (int)($user);
        }

        if (!InterfaceImplementationChecker::is_library_item($type)) {
            debug_event(__CLASS__, $type . " is not a library item.", 3);

            return false;
        }
        $tag_id  = (int)($tag_id);
        $item_id = (int)($object_id);

        if (!$tag_id || !$item_id) {
            return false;
        }

        // If tag merged to another one, add reference to the merge destination
        $parent = new Tag($tag_id);
        $merges = $parent->get_merged_tags();
        if (!$parent->is_hidden) {
            $merges[] = array('id' => $parent->id, 'name' => $parent->name);
        }
        foreach ($merges as $tag) {
            $sql = "INSERT IGNORE INTO `tag_map` (`tag_id`, `user`, `object_type`, `object_id`) VALUES (?, ?, ?, ?)";
            Dba::write($sql, array($tag['id'], $uid, $type, $item_id));
        }
        $insert_id = (int)Dba::insert_id();

        parent::add_to_cache('tag_map_' . $type, $insert_id, array('tag_id' => $tag_id, 'user' => $uid, 'object_type' => $type, 'object_id' => $item_id));

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
        $sql = "DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `song` ON `song`.`id`=`tag_map`.`object_id` WHERE `tag_map`.`object_type`='song' AND `song`.`id` IS NULL";
        Dba::write($sql);

        $sql = "DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `album` ON `album`.`id`=`tag_map`.`object_id` WHERE `tag_map`.`object_type`='album' AND `album`.`id` IS NULL";
        Dba::write($sql);

        $sql = "DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `artist` ON `artist`.`id`=`tag_map`.`object_id` WHERE `tag_map`.`object_type`='artist' AND `artist`.`id` IS NULL";
        Dba::write($sql);

        // Now nuke the tags themselves
        $sql = "DELETE FROM `tag` USING `tag` LEFT JOIN `tag_map` ON `tag`.`id`=`tag_map`.`tag_id` WHERE `tag_map`.`id` IS NULL AND NOT EXISTS (SELECT 1 FROM `tag_merge` WHERE `tag_merge`.`tag_id` = `tag`.`id`)";
        Dba::write($sql);

        // delete duplicates
        $sql = "DELETE `b` FROM `tag_map` AS `a`, `tag_map` AS `b` WHERE `a`.`id` < `b`.`id` AND `a`.`tag_id` <=> `b`.`tag_id` AND `a`.`object_id` <=> `b`.`object_id` AND `a`.`object_type` <=> `b`.`object_type`";
        Dba::write($sql);
    }

    /**
     * delete
     *
     * Delete the tag and all maps
     */
    public function delete(): void
    {
        $sql = "DELETE FROM `tag_map` WHERE `tag_map`.`tag_id` = ?";
        Dba::write($sql, array($this->id));

        $sql = "DELETE FROM `tag_merge` WHERE `tag_merge`.`tag_id` = ?";
        Dba::write($sql, array($this->id));

        $sql = "DELETE FROM `tag` WHERE `tag`.`id` = ? ";
        Dba::write($sql, array($this->id));

        // Call the garbage collector to clean everything
        self::garbage_collection();

        parent::clear_cache();
    }

    /**
     * tag_exists
     * This checks to see if a tag exists, this has nothing to do with objects or maps
     * @param string $value
     */
    public static function tag_exists($value): int
    {
        if (parent::is_cached('tag_name', $value)) {
            return (int)(parent::get_from_cache('tag_name', $value))[0];
        }

        $sql        = "SELECT `id` FROM `tag` WHERE `name` = ?";
        $db_results = Dba::read($sql, array($value));
        $results    = Dba::fetch_assoc($db_results);

        if (array_key_exists('id', $results)) {
            parent::add_to_cache('tag_name', $value, array($results['id']));

            return (int)$results['id'];
        }

        return 0;
    }

    /**
     * tag_map_exists
     * This looks to see if the current mapping of the current object of the current tag of the current
     * user exists, lots of currents... taste good in scones.
     * @param string $type
     * @param int $object_id
     * @param int $tag_id
     * @param int $user
     * @return bool|int
     */
    public static function tag_map_exists($type, $object_id, $tag_id, $user)
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            debug_event(__CLASS__, 'Requested type is not a library item.', 3);

            return false;
        }

        $sql        = "SELECT * FROM `tag_map` LEFT JOIN `tag` ON `tag`.`id` = `tag_map`.`tag_id` LEFT JOIN `tag_merge` ON `tag`.`id`=`tag_merge`.`tag_id` WHERE (`tag_map`.`tag_id` = ? OR `tag_map`.`tag_id` = `tag_merge`.`merged_to`) AND `tag_map`.`user` = ? AND `tag_map`.`object_id` = ? AND `tag_map`.`object_type` = ?";
        $db_results = Dba::read($sql, array($tag_id, $user, $object_id, $type));
        $results    = Dba::fetch_assoc($db_results);

        if (array_key_exists('id', $results)) {
            return (int)$results['id'];
        }

        return false;
    }

    /**
     * get_top_tags
     * This gets the top tags for the specified object using limit
     * @param string $type
     * @param int $object_id
     * @param int $limit
     * @return array
     */
    public static function get_top_tags($type, $object_id, $limit = 10): array
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return array();
        }

        $object_id = (int)($object_id);

        $limit = (int)($limit);
        $sql   = "SELECT `tag_map`.`id`, `tag_map`.`tag_id`, `tag`.`name`, `tag_map`.`user` FROM `tag` LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` WHERE `tag`.`is_hidden` = false AND `tag_map`.`object_type` = ? AND `tag_map`.`object_id` = ? LIMIT $limit";

        $db_results = Dba::read($sql, array($type, $object_id));
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['id']] = array('user' => $row['user'], 'id' => $row['tag_id'], 'name' => $row['name']);
        }

        return $results;
    }

    /**
     * get_object_tags
     * Display all tags that apply to matching target type of the specified id
     * @param string $type
     * @param int $object_id
     * @return array|bool
     */
    public static function get_object_tags($type, $object_id = null)
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return false;
        }

        $params = array($type);
        $sql    = "SELECT `tag_map`.`id`, `tag`.`name`, `tag_map`.`user` FROM `tag` LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` WHERE `tag`.`is_hidden` = false AND `tag_map`.`object_type` = ?";
        if ($object_id !== null) {
            $sql .= " AND `tag_map`.`object_id` = ?";
            $params[] = $object_id;
        }
        $db_results = Dba::read($sql, $params);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row;
        }

        return $results;
    }

    /**
     * get_tag_objects
     * This gets the objects from a specified tag and returns an array of object ids, nothing more
     * @param string $type
     * @param int $tag_id
     * @param int $count
     * @param int $offset
     * @return int[]
     */
    public static function get_tag_objects($type, $tag_id, $count = 0, $offset = 0): array
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return array();
        }
        $tag_sql   = ($tag_id === 0) ? "" : "`tag_map`.`tag_id` = ? AND";
        $sql_param = ($tag_sql === "") ? array($type) : array($tag_id, $type);
        $limit_sql = "";
        if ($count) {
            $limit_sql = " LIMIT ";
            if ($offset) {
                $limit_sql .= (string)($offset) . ', ';
            }
            $limit_sql .= (string)($count);
        }

        $sql = "SELECT DISTINCT `tag_map`.`object_id` FROM `tag_map` WHERE $tag_sql `tag_map`.`object_type` = ?";
        if (AmpConfig::get('catalog_disable') && in_array($type, array('artist', 'album', 'album_disk', 'song', 'video'))) {
            $sql .= "AND " . Catalog::get_enable_filter($type, '`tag_map`.`object_id`');
        }
        $sql .= $limit_sql;
        $db_results = Dba::read($sql, $sql_param);

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['object_id'];
        }

        return $results;
    }

    /**
     * get_tag_ids
     * This gets the objects from a specified tag and returns an array of object ids, nothing more
     * @param string $type
     * @param string $count
     * @param string $offset
     * @return int[]
     */
    public static function get_tag_ids($type, $count = '', $offset = ''): array
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return array();
        }

        $limit_sql = "";
        if ($count) {
            $limit_sql = " LIMIT ";
            if ($offset) {
                $limit_sql .= (string)($offset) . ', ';
            }
            $limit_sql .= (string)($count);
        }

        $sql = "SELECT DISTINCT `tag_map`.`tag_id` FROM `tag_map` WHERE `tag_map`.`object_type` = ? ";
        if (AmpConfig::get('catalog_disable') && in_array($type, array('artist', 'album', 'album_disk', 'song', 'video'))) {
            $sql .= "AND " . Catalog::get_enable_filter($type, '`tag_map`.`object_id`');
        }
        $sql .= $limit_sql;
        $db_results = Dba::read($sql, array($type));

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['tag_id'];
        }

        return $results;
    }

    /**
     * get_tags
     * This is a non-object non type dependent function that just returns tags
     * we've got, it can take filters (this is used by the tag cloud)
     * @param string $type
     * @param int $limit
     * @param string $order
     * @return array
     */
    public static function get_tags($type = '', $limit = 0, $order = 'count'): array
    {
        if (parent::is_cached('tags_list', 'no_name')) {
            //debug_event(self::class, 'Tags list found into cache memory!', 5);
            return parent::get_from_cache('tags_list', 'no_name');
        }

        $results = array();
        if ($type == 'tag_hidden') {
            $sql = "SELECT `tag`.`id` AS `tag_id`, `tag`.`name`, `tag`.`is_hidden` FROM `tag` WHERE `tag`.`is_hidden` = true ";
        } else {
            $type_sql = (!empty($type))
                ? "AND `tag_map`.`object_type` = '" . (string)scrub_in($type) . "'"
                : "";

            $sql = (AmpConfig::get('catalog_filter') && Core::get_global('user') instanceof User && Core::get_global('user')->id > 0)
                ? "SELECT `tag`.`id`, `tag`.`name`, `tag`.`is_hidden`, COUNT(`tag_map`.`object_id`) AS `count` FROM `tag` LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` $type_sql WHERE" . Catalog::get_user_filter('tag', Core::get_global('user')->id) . " AND `tag_map`.`tag_id` IS NOT NULL AND `tag`.`is_hidden` = 0 "
                : "SELECT `tag`.`id`, `tag`.`name`, `tag`.`is_hidden`, COUNT(`tag_map`.`object_id`) AS `count` FROM `tag` LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` $type_sql WHERE `tag_map`.`tag_id` IS NOT NULL AND `tag`.`is_hidden` = 0 ";

            $sql .= "GROUP BY `tag_map`.`tag_id`, `tag`.`name`, `tag`.`is_hidden` ";
        }
        $order = "`" . $order . "`";
        if ($order == 'count') {
            $order .= " DESC";
        }
        $sql .= "ORDER BY " . $order;

        if ($limit > 0) {
            $sql .= " LIMIT $limit";
        }
        //debug_event(self::class, 'get_tags ' . $sql, 5);

        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['id']] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'is_hidden' => $row['is_hidden'],
                'count' => $row['count'] ?? 0
            );
        }

        parent::add_to_cache('tags_list', 'no_name', $results);

        return $results;
    }

    /**
     * get_display
     * This returns a csv formatted version of the tags that we are given
     * it also takes a type so that it knows how to return it, this is used
     * by the formatting functions of the different objects
     * @param array $tags
     * @param bool $link
     * @param string $filter_type
     */
    public static function get_display($tags, $link = false, $filter_type = ''): string
    {
        //debug_event(self::class, 'Get display tags called...', 5);
        if (!is_array($tags)) {
            return '';
        }

        $web_path = AmpConfig::get('web_path');
        $results  = '';

        // Iterate through the tags, format them according to type and element id
        foreach ($tags as $value) {
            if ($link) {
                $results .= '<a href="' . $web_path . '/browse.php?action=tag&show_tag=' . $value['id'] . (!empty($filter_type) ? '&type=' . $filter_type : '') . '" title="' . scrub_out($value['name']) . '">';
            }
            $results .= $value['name'];
            if ($link) {
                $results .= '</a>';
            }
            $results .= ', ';
        }

        $results = rtrim((string)$results, ', ');

        return $results;
    }

    /**
     * update_tag_list
     * Update the tags list based on a comma-separated list
     *  (ex. tag1,tag2,tag3,..)
     * @param string $tags_comma
     * @param string $object_type
     * @param int $object_id
     * @param bool $overwrite
     */
    public static function update_tag_list($tags_comma, $object_type, $object_id, $overwrite): bool
    {
        if (!strlen((string) $tags_comma) > 0) {
            return self::remove_all_map($object_type, $object_id);
        }
        debug_event(self::class, "update_tag_list $object_type: {{$object_id}}", 5);
        // tags from your file can be in a terrible format
        $filterfolk  = str_replace('Folk, World, & Country', 'Folk World & Country', $tags_comma);
        $filterunder = str_replace('_', ', ', $filterfolk);
        $filter      = str_replace(';', ', ', $filterunder);
        $filter_list = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $filter);
        $editedTags  = (is_array($filter_list)) ? array_unique($filter_list) : array();

        $ctags = self::get_top_tags($object_type, $object_id, 50);
        foreach ($ctags as $ctid => $ctv) {
            //debug_event(self::class, 'ctag {' . $ctid . '} = ' . print_r($ctv, true), 5);
            $found = false;
            if ($ctv['id'] != '') {
                $ctag = new Tag($ctv['id']);
                foreach ($editedTags as $tk => $tv) {
                    //debug_event(self::class, 'from_tags {' . $tk . '} = ' . $tv, 5);
                    if (strtolower((string)$ctag->name) == strtolower($tv)) {
                        $found = true;
                        break;
                    }
                    // check if this thing has been renamed into something else
                    $merged = self::construct_from_name($tv);
                    if ($merged->id && $merged->is_hidden && $merged->has_merge((string)$ctag->name)) {
                        $found = true;
                        break;
                    }
                }

                if ($found) {
                    unset($editedTags[$ctag->id]);
                }
                if (!$found && $overwrite && $ctv['user'] == 0) {
                    debug_event(self::class, 'update_tag_list {' . $ctag->name . '} not found. Delete it.', 5);
                    $ctag->remove_map($object_type, $object_id, false);
                }
            }
        }
        // Look if we need to add some new tags
        foreach ($editedTags as $tk => $tv) {
            if ($tv != '') {
                self::add($object_type, $object_id, $tv, false);
            }
        }

        return true;
    }

    /**
     * clean_to_existing
     * Clean tag list to existing tag list only
     * @param array|string $tags
     * @return array|string
     */
    public static function clean_to_existing($tags)
    {
        if (is_array($tags)) {
            $taglist = $tags;
        } else {
            $filterfolk  = str_replace('Folk, World, & Country', 'Folk World & Country', $tags);
            $filterunder = str_replace('_', ', ', $filterfolk);
            $filter      = str_replace(';', ', ', $filterunder);
            $filter_list = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $filter);
            $taglist     = (is_array($filter_list)) ? array_unique($filter_list) : array();
        }

        $ret = array();
        foreach ($taglist as $tag) {
            $tag = trim((string)$tag);
            if (!empty($tag)) {
                if (self::tag_exists($tag)) {
                    $ret[] = $tag;
                }
            }
        }

        return (is_array($tags) ? $ret : implode(",", $ret));
    }

    /**
     * count
     * This returns the count for the all objects associated with this tag
     * If a type is specific only counts for said type are returned
     * @param string $type
     * @param int $user_id
     * @return array
     */
    public function count($type = '', $user_id = 0): array
    {
        $params = array($this->id);

        $filter_sql = "";
        if ($user_id > 0) {
            $filter_sql = " AND `user` = ?";
            $params[]   = $user_id;
        }
        if ($type) {
            $filter_sql = " AND `object_type` = ?";
            $params[]   = $type;
        }

        $results    = array();
        $sql        = "SELECT DISTINCT(`object_type`), COUNT(`object_id`) AS `count` FROM `tag_map` WHERE `tag_id` = ?" . $filter_sql . " GROUP BY `object_type`";
        $db_results = Dba::read($sql, $params);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['object_type']] = (int)$row['count'];
        }

        return $results;
    }

    /**
     * remove_map
     * This will only remove tag maps for the current user
     * @param string $type
     * @param int $object_id
     * @param bool $user
     */
    public function remove_map($type, $object_id, $user = true): bool
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return false;
        }

        $uid = 0;
        if ($user === true) {
            $uid = (int)(Core::get_global('user')?->getId());
        }

        $sql = "DELETE FROM `tag_map` WHERE `tag_id` = ? AND `object_type` = ? AND `object_id` = ? AND `user` = ?";
        Dba::write($sql, array($this->id, $type, $object_id, $uid));

        return true;
    }

    /**
     * remove_all_map
     * Clear all the tags from an object when there isn't anything there
     * @param string $object_type
     * @param int $object_id
     */
    public static function remove_all_map($object_type, $object_id): bool
    {
        if (!InterfaceImplementationChecker::is_library_item($object_type)) {
            return false;
        }

        $sql = "DELETE FROM `tag_map` WHERE `object_type` = ? AND `object_id` = ?";
        Dba::write($sql, array($object_type, $object_id));

        return true;
    }

    /**
     * @param bool $details
     */
    public function format($details = true): void
    {
        unset($details); //dead code but called from other format calls
    }

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords(): array
    {
        $keywords        = [];
        $keywords['tag'] = [
            'important' => true,
            'label' => T_('Genre'),
            'value' => $this->name
        ];

        return $keywords;
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
     * get_parent
     * Return parent `object_type`, `object_id`; null otherwise.
     */
    public function get_parent(): ?array
    {
        return null;
    }

    /**
     * @return array
     */
    public function get_childrens(): array
    {
        return array();
    }

    /**
     * Search for direct children of an object
     * @param string $name
     * @return array
     */
    public function get_children($name): array
    {
        debug_event(self::class, 'get_children ' . $name, 5);

        return array();
    }

    /**
     * @return list<array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function get_medias(?string $filter_type = null): array
    {
        $medias = array();
        if ($filter_type) {
            $ids = self::get_tag_objects($filter_type, $this->id);
            foreach ($ids as $object_id) {
                $medias[] = array(
                    'object_type' => LibraryItemEnum::from($filter_type),
                    'object_id' => $object_id
                );
            }
        }

        return $medias;
    }

    /**
     * @return int|null
     */
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
     * @param string $object_type
     * @param int $old_object_id
     * @param int $new_object_id
     * @return PDOStatement|bool
     */
    public static function migrate($object_type, $old_object_id, $new_object_id)
    {
        $sql = "UPDATE IGNORE `tag_map` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";

        return Dba::write($sql, array($new_object_id, $object_type, $old_object_id));
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::TAG;
    }
}
