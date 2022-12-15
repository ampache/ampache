<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

declare(strict_types=0);

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

    public $id;
    public $name;
    public $f_name;
    public $is_hidden;

    /**
     * constructor
     * This takes a tag id and returns all of the relevant information
     * @param $tag_id
     */
    public function __construct($tag_id)
    {
        if (!$tag_id) {
            return false;
        }

        $info = $this->get_info($tag_id);
        if (empty($info)) {
            return false;
        }

        foreach ($info as $key => $value) {
            $this->$key = $value;
        } // end foreach

        // the ui is sometimes looking for a formatted name...
        $this->f_name = scrub_out($this->name);

        return true;
    } // constructor

    public function getId(): int
    {
        return (int)$this->id;
    }

    /**
     * construct_from_name
     * This attempts to construct the tag from a name, rather then the ID
     * @param string $name
     * @return Tag
     */
    public static function construct_from_name($name)
    {
        $tag_id = self::tag_exists($name);

        return new Tag($tag_id);
    } // construct_from_name

    /**
     * build_cache
     * This takes an array of object ids and caches all of their information
     * in a single query, cuts down on the connections
     * @param array $ids
     * @return boolean
     */
    public static function build_cache($ids)
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
    } // build_cache

    /**
     * build_map_cache
     * This builds a cache of the mappings for the specified object, no limit is given
     * @param string $type
     * @param $ids
     * @return boolean
     * @params array $ids
     */
    public static function build_map_cache($type, $ids)
    {
        if (!is_array($ids) || !count($ids)) {
            return false;
        }

        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return false;
        }

        $idlist = '(' . implode(',', $ids) . ')';

        $sql = "SELECT `tag_map`.`id`, `tag_map`.`tag_id`, `tag`.`name`, `tag_map`.`object_id`, `tag_map`.`user` FROM `tag` LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` WHERE `tag`.`is_hidden` = false AND `tag_map`.`object_type`='$type' AND `tag_map`.`object_id` IN $idlist";

        $db_results = Dba::read($sql);

        $tags    = array();
        $tag_map = array();
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
    } // build_map_cache

    /**
     * add
     * This is a wrapper function, it figures out what we need to add, be it a tag
     * and map, or just the mapping
     * @param string $type
     * @param integer $object_id
     * @param string $value
     * @param boolean $user
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
            $uid = (int)(Core::get_global('user')->id);
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
    } // add

    /**
     * add_tag
     * This function adds a new tag, for now we're going to limit the tagging a bit
     * @param string $value
     * @return int|null
     */
    public static function add_tag($value)
    {
        if (!strlen((string)$value)) {
            return null;
        }

        $sql = "REPLACE INTO `tag` SET `name` = ?";
        Dba::write($sql, array($value));
        $insert_id = (int)Dba::insert_id();

        parent::add_to_cache('tag_name', $value, array($insert_id));

        return $insert_id;
    } // add_tag

    /**
     * update
     * Update the name of the tag
     * @param array $data
     * @return boolean
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
    } // add_tag

    /**
     * merge
     * merges this tag to another one.
     * @param integer $merge_to
     * @param boolean $is_persistent
     */
    public function merge($merge_to, $is_persistent)
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
    public function get_merged_tags()
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
     * @return int
     */
    public static function get_merged_count()
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
     * @return bool
     */
    public function has_merge($name)
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
    public function remove_merges()
    {
        $sql = "DELETE FROM `tag_merge` WHERE `tag_merge`.`tag_id` = ?;";
        Dba::write($sql, array($this->id));
    }

    /**
     * add_tag_map
     * This adds a specific tag to the map for specified object
     * @param string $type
     * @param integer|string $object_id
     * @param integer|string $tag_id
     * @param boolean $user
     * @return boolean|int
     */
    public static function add_tag_map($type, $object_id, $tag_id, $user = true)
    {
        if ($user === true) {
            $uid = (int)(Core::get_global('user')->id);
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
    } // add_tag_map

    /**
     * garbage_collection
     *
     * This cleans out tag_maps that are obsolete and then removes tags that
     * have no maps.
     */
    public static function garbage_collection()
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
    public function delete()
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
     * @return integer
     */
    public static function tag_exists($value)
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
    } // tag_exists

    /**
     * tag_map_exists
     * This looks to see if the current mapping of the current object of the current tag of the current
     * user exists, lots of currents... taste good in scones.
     * @param string $type
     * @param integer $object_id
     * @param integer $tag_id
     * @param integer $user
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
    } // tag_map_exists

    /**
     * get_top_tags
     * This gets the top tags for the specified object using limit
     * @param string $type
     * @param integer $object_id
     * @param integer $limit
     * @return array
     */
    public static function get_top_tags($type, $object_id, $limit = 10)
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
    } // get_top_tags

    /**
     * get_object_tags
     * Display all tags that apply to matching target type of the specified id
     * @param string $type
     * @param integer $object_id
     * @return array|boolean
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
        $results    = array();
        $db_results = Dba::read($sql, $params);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row;
        }

        return $results;
    } // get_object_tags

    /**
     * get_tag_objects
     * This gets the objects from a specified tag and returns an array of object ids, nothing more
     * @param string $type
     * @param $tag_id
     * @param string $count
     * @param string $offset
     * @return integer[]
     */
    public static function get_tag_objects($type, $tag_id, $count = '', $offset = '')
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return array();
        }
        $tag_sql   = ((int) $tag_id == 0) ? "" : "`tag_map`.`tag_id` = ? AND";
        $sql_param = ($tag_sql == "") ? array($type) : array($tag_id, $type);
        $limit_sql = "";
        if ($count) {
            $limit_sql = " LIMIT ";
            if ($offset) {
                $limit_sql .= (string)($offset) . ', ';
            }
            $limit_sql .= (string)($count);
        }

        $sql = ($type == 'album')
            ? "SELECT DISTINCT MIN(`tag_map`.`object_id`) AS `object_id` FROM `tag_map` LEFT JOIN `album` ON `tag_map`.`object_id` = `album`.`id` "
            : "SELECT DISTINCT `tag_map`.`object_id` FROM `tag_map` ";
        $sql .= "WHERE $tag_sql `tag_map`.`object_type` = ?";
        if (AmpConfig::get('catalog_disable') && in_array($type, array('song', 'artist', 'album'))) {
            $sql .= "AND " . Catalog::get_enable_filter($type, '`tag_map`.`object_id`');
        }
        if ($type == 'album') {
            if (AmpConfig::get('album_group')) {
                $sql .= " GROUP BY `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`release_status`, `album`.`mbid`, `album`.`year`, `album`.`original_year`, `album`.`mbid_group`";
            } else {
                $sql .= " GROUP BY `album`.`id`, `album`.`disk`";
            }
        }
        $sql .= $limit_sql;
        $db_results = Dba::read($sql, $sql_param);

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['object_id'];
        }

        return $results;
    } // get_tag_objects

    /**
     * get_tag_ids
     * This gets the objects from a specified tag and returns an array of object ids, nothing more
     * @param string $type
     * @param string $count
     * @param string $offset
     * @return integer[]
     */
    public static function get_tag_ids($type, $count = '', $offset = '')
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
        if (AmpConfig::get('catalog_disable') && in_array($type, array('song', 'artist', 'album'))) {
            $sql .= "AND " . Catalog::get_enable_filter($type, '`tag_map`.`object_id`');
        }
        $sql .= $limit_sql;
        $db_results = Dba::read($sql, array($type));

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['tag_id'];
        }

        return $results;
    } // get_tag_ids

    /**
     * get_tags
     * This is a non-object non type dependent function that just returns tags
     * we've got, it can take filters (this is used by the tag cloud)
     * @param string $type
     * @param integer $limit
     * @param string $order
     * @return array
     */
    public static function get_tags($type = '', $limit = 0, $order = 'count')
    {
        if (parent::is_cached('tags_list', 'no_name')) {
            //debug_event(self::class, 'Tags list found into cache memory!', 5);
            return parent::get_from_cache('tags_list', 'no_name');
        }

        $results  = array();
        if ($type == 'tag_hidden') {
            $sql = "SELECT `tag`.`id` AS `tag_id`, `tag`.`name`, `tag`.`is_hidden` FROM `tag` WHERE `tag`.`is_hidden` = true ";
        } else {
            $type_sql = (!empty($type))
                ? "AND `tag_map`.`object_type` = '" . (string)scrub_in($type) . "'"
                : "";
            $sql = (AmpConfig::get('catalog_filter') && !empty(Core::get_global('user')))
                ? "SELECT `tag_map`.`tag_id`, `tag`.`name`, `tag`.`is_hidden`, COUNT(`tag_map`.`object_id`) AS `count` FROM `tag_map` LEFT JOIN `tag` ON `tag`.`id`=`tag_map`.`tag_id` $type_sql AND `tag`.`is_hidden` = false WHERE" . Catalog::get_user_filter('tag', Core::get_global('user')->id) . " AND `name` IS NOT NULL "
                : "SELECT `tag_map`.`tag_id`, `tag`.`name`, `tag`.`is_hidden`, COUNT(`tag_map`.`object_id`) AS `count` FROM `tag_map` LEFT JOIN `tag` ON `tag`.`id`=`tag_map`.`tag_id` $type_sql AND `tag`.`is_hidden` = false WHERE `name` IS NOT NULL ";

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
            $results[$row['tag_id']] = array(
                'id' => $row['tag_id'],
                'name' => $row['name'],
                'is_hidden' => $row['is_hidden'],
                'count' => $row['count'] ?? 0
            );
        }

        parent::add_to_cache('tags_list', 'no_name', $results);

        return $results;
    } // get_tags

    /**
     * get_display
     * This returns a csv formatted version of the tags that we are given
     * it also takes a type so that it knows how to return it, this is used
     * by the formatting functions of the different objects
     * @param array $tags
     * @param boolean $link
     * @param string $filter_type
     * @return string
     */
    public static function get_display($tags, $link = false, $filter_type = '')
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
    } // get_display

    /**
     * update_tag_list
     * Update the tags list based on a comma-separated list
     *  (ex. tag1,tag2,tag3,..)
     * @param string $tags_comma
     * @param string $object_type
     * @param integer $object_id
     * @param boolean $overwrite
     * @return boolean
     */
    public static function update_tag_list($tags_comma, $object_type, $object_id, $overwrite)
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
                $ctag  = new Tag($ctv['id']);
                foreach ($editedTags as $tk => $tv) {
                    //debug_event(self::class, 'from_tags {' . $tk . '} = ' . $tv, 5);
                    if (strtolower($ctag->name) == strtolower($tv)) {
                        $found = true;
                        break;
                    }
                    // check if this thing has been renamed into something else
                    $merged = self::construct_from_name($tv);
                    if ($merged && $merged->is_hidden && $merged->has_merge($ctag->name)) {
                        $found = true;
                        break;
                    }
                }

                if ($found) {
                    unset($editedTags[$ctag->name]);
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
    } // update_tag_list

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
     * @param integer $user_id
     * @return array
     */
    public function count($type = '', $user_id = 0)
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

        $results = array();

        $sql        = "SELECT DISTINCT(`object_type`), COUNT(`object_id`) AS `count` FROM `tag_map` WHERE `tag_id` = ?" . $filter_sql . " GROUP BY `object_type`";
        $db_results = Dba::read($sql, $params);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['object_type']] = (int)$row['count'];
        }

        return $results;
    } // count

    /**
     * remove_map
     * This will only remove tag maps for the current user
     * @param string $type
     * @param integer $object_id
     * @param boolean $user
     * @return boolean
     */
    public function remove_map($type, $object_id, $user = true)
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return false;
        }

        $uid = 0;
        if ($user === true) {
            $uid = (int)(Core::get_global('user')->id);
        }

        $sql = "DELETE FROM `tag_map` WHERE `tag_id` = ? AND `object_type` = ? AND `object_id` = ? AND `user` = ?";
        Dba::write($sql, array($this->id, $type, $object_id, $uid));

        return true;
    } // remove_map

    /**
     * remove_all_map
     * Clear all the tags from an object when there isn't anything there
     * @param string $object_type
     * @param integer $object_id
     * @return boolean
     */
    public static function remove_all_map($object_type, $object_id)
    {
        if (!InterfaceImplementationChecker::is_library_item($object_type)) {
            return false;
        }

        $sql = "DELETE FROM `tag_map` WHERE `object_type` = ? AND `object_id` = ?";
        Dba::write($sql, array($object_type, $object_id));

        return true;
    } // remove_all_map

    /**
     * @param boolean $details
     */
    public function format($details = true)
    {
        unset($details); //dead code but called from other format calls
    }

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
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
     * @return string
     */
    public function get_fullname()
    {
        return $this->name;
    }

    /**
     * Get item link.
     * @return string
     */
    public function get_link()
    {
        return '';
    }

    /**
     * Get item f_link.
     * @return string
     */
    public function get_f_link()
    {
        return '';
    }

    /**
     * @return null
     */
    public function get_parent()
    {
        return null;
    }

    /**
     * @return array
     */
    public function get_childrens()
    {
        return array();
    }

    /**
     * search_childrens
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        debug_event(self::class, 'search_childrens ' . $name, 5);

        return array();
    }

    /**
     * get_medias
     * @param string $filter_type
     * @return array
     */
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if ($filter_type) {
            $ids = self::get_tag_objects($filter_type, $this->id);
            foreach ($ids as $object_id) {
                $medias[] = array(
                    'object_type' => $filter_type,
                    'object_id' => $object_id
                );
            }
        }

        return $medias;
    }

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return integer[]
     */
    public function get_catalogs()
    {
        return array();
    }

    /**
     * @return mixed|null
     */
    public function get_user_owner()
    {
        return null;
    }

    /**
     * get_default_art_kind
     * @return string
     */
    public function get_default_art_kind()
    {
        return 'default';
    }

    /**
     * get_description
     * @return string
     */
    public function get_description()
    {
        return '';
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        if (Art::has_db($this->id, 'tag') || $force) {
            Art::display('tag', $this->id, $this->get_fullname(), $thumb);
        }
    }

    /**
     * Migrate an object associate stats to a new object
     * @param string $object_type
     * @param integer $old_object_id
     * @param integer $new_object_id
     * @return PDOStatement|boolean
     */
    public static function migrate($object_type, $old_object_id, $new_object_id)
    {
        $sql = "UPDATE IGNORE `tag_map` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";

        return Dba::write($sql, array($new_object_id, $object_type, $old_object_id));
    }
}
