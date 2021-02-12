<?php
declare(strict_types=0);
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

/**
 * Tag Class
 *
 * This class handles all of the tag related operations
 *
 */
class Tag extends database_object implements library_item
{
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

        foreach ($info as $key => $value) {
            $this->$key = $value;
        } // end foreach

        // the ui is sometimes looking for a formatted name...
        $this->f_name = $this->name;

        return true;
    } // constructor

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

        if (!Core::is_library_item($type)) {
            return false;
        }

        $idlist = '(' . implode(',', $ids) . ')';

        $sql = "SELECT `tag_map`.`id`, `tag_map`.`tag_id`, `tag`.`name`, `tag_map`.`object_id`, `tag_map`.`user` FROM `tag` " .
            "LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` " .
            "WHERE `tag_map`.`object_type`='$type' AND `tag_map`.`object_id` IN $idlist";

        $db_results = Dba::read($sql);

        $tags    = array();
        $tag_map = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $tags[$row['object_id']][$row['tag_id']] = array('user' => $row['user'], 'id' => $row['tag_id'], 'name' => $row['name']);
            $tag_map[$row['object_id']]              = array('id' => $row['id'], 'tag_id' => $row['tag_id'], 'user' => $row['user'], 'object_type' => $type, 'object_id' => $row['object_id']);
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
     * @return boolean|mixed|string|null
     */
    public static function add($type, $object_id, $value, $user = true)
    {
        if (!Core::is_library_item($type)) {
            return false;
        }

        if (!is_numeric($object_id)) {
            return false;
        }

        $cleaned_value = str_replace('Folk, World, & Country', 'Folk World & Country', $value);

        if (!strlen((string) $cleaned_value)) {
            return false;
        }

        if ($user === true) {
            $uid = (int) (Core::get_global('user')->id);
        } else {
            $uid = (int) ($user);
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
        if (!$map_id = self::tag_map_exists($type, $object_id, (int) $tag_id, $uid)) {
            $map_id = self::add_tag_map($type, $object_id, (int) $tag_id, $user);
        }

        return (int) $map_id;
    } // add

    /**
     * add_tag
     * This function adds a new tag, for now we're going to limit the tagging a bit
     * @param string $value
     * @return string|null
     */
    public static function add_tag($value)
    {
        if (!strlen((string) $value)) {
            return null;
        }

        $sql = "REPLACE INTO `tag` SET `name` = ?";
        Dba::write($sql, array($value));
        $insert_id = (int) Dba::insert_id();

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
        if (!strlen((string) $data['name'])) {
            return false;
        }
        debug_event(self::class, 'Updating tag {' . $this->id . '} with name {' . $data['name'] . '}...', 5);

        $sql = 'UPDATE `tag` SET `name` = ? WHERE `id` = ?';
        Dba::write($sql, array($data['name'], $this->id));

        if ($data['edit_tags']) {
            $filterfolk  = str_replace('Folk, World, & Country', 'Folk World & Country', $data['edit_tags']);
            $filterunder = str_replace('_',', ', $filterfolk);
            $filter      = str_replace(';',', ', $filterunder);
            $filter_list = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $filter);
            $tag_names   = (is_array($filter_list)) ? array_unique($filter_list) : array();

            foreach ($tag_names as $tag) {
                $merge_to = self::construct_from_name($tag);
                if ($merge_to->id == 0) {
                    self::add_tag($tag);
                    $merge_to = self::construct_from_name($tag);
                }
                $this->merge($merge_to->id, $data['merge_persist'] == '1');
            }
            if ($data['keep_existing'] != '1') {
                $sql = "DELETE FROM `tag_map` WHERE `tag_map`.`tag_id` = ? ";
                Dba::write($sql, array($this->id));
                if ($data['merge_persist'] != '1') {
                    $this->delete();
                } else {
                    $sql = "UPDATE `tag` SET `is_hidden` = true WHERE `tag`.`id` = ? ";
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

            $sql = "INSERT IGNORE INTO `tag_map` (`tag_id`, `user`, `object_type`, `object_id`) " .
                   "SELECT " . $merge_to . ",`user`, `object_type`, `object_id` " .
                   "FROM `tag_map` AS `tm` " .
                   "WHERE `tm`.`tag_id` = " . $this->id . " AND NOT EXISTS (" .
                       "SELECT 1 FROM `tag_map` " .
                       "WHERE `tag_map`.`tag_id` = " . $merge_to . " " .
                         "AND `tag_map`.`object_id` = `tm`.`object_id` " .
                         "AND `tag_map`.`object_type` = `tm`.`object_type` " .
                         "AND `tag_map`.`user` = `tm`.`user`)";
            Dba::write($sql);
            if ($is_persistent) {
                $sql = 'INSERT INTO `tag_merge` (`tag_id`, `merged_to`) VALUES (?, ?)';
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
        $sql = "SELECT `tag`.`id`, `tag`.`name`" .
            "FROM `tag_merge` " .
            "INNER JOIN `tag` ON `tag`.`id` = `tag_merge`.`merged_to` " .
            "WHERE `tag_merge`.`tag_id` = ? " .
            "ORDER BY `tag`.`name` ";

        $db_results = Dba::read($sql, array($this->id));

        $results = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['id']] = array('id' => $row['id'], 'name' => $row['name']);
        }

        return $results;
    }

    /**
     * add_tag_map
     * This adds a specific tag to the map for specified object
     * @param string $type
     * @param integer|string $object_id
     * @param integer|string $tag_id
     * @param boolean $user
     * @return boolean|string|null
     */
    public static function add_tag_map($type, $object_id, $tag_id, $user = true)
    {
        if ($user === true) {
            $uid = (int) (Core::get_global('user')->id);
        } else {
            $uid = (int) ($user);
        }

        if (!Core::is_library_item($type)) {
            debug_event(self::class, $type . " is not a library item.", 3);

            return false;
        }
        $tag_id  = (int) ($tag_id);
        $item_id = (int) ($object_id);

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
            $sql = "INSERT INTO `tag_map` (`tag_id`, `user`, `object_type`, `object_id`) " .
                "VALUES (?, ?, ?, ?)";
            Dba::write($sql, array($tag['id'], $uid, $type, $item_id));
        }
        $insert_id = (int) Dba::insert_id();

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
        $sql = "DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `song` ON `song`.`id`=`tag_map`.`object_id` " .
            "WHERE `tag_map`.`object_type`='song' AND `song`.`id` IS NULL";
        Dba::write($sql);

        $sql = "DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `album` ON `album`.`id`=`tag_map`.`object_id` " .
            "WHERE `tag_map`.`object_type`='album' AND `album`.`id` IS NULL";
        Dba::write($sql);

        $sql = "DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `artist` ON `artist`.`id`=`tag_map`.`object_id` " .
            "WHERE `tag_map`.`object_type`='artist' AND `artist`.`id` IS NULL";
        Dba::write($sql);

        // Now nuke the tags themselves
        $sql = "DELETE FROM `tag` USING `tag` LEFT JOIN `tag_map` ON `tag`.`id`=`tag_map`.`tag_id` " .
            "WHERE `tag_map`.`id` IS NULL " .
            "AND NOT EXISTS (SELECT 1 FROM `tag_merge` WHERE `tag_merge`.`tag_id` = `tag`.`id`)";
        Dba::write($sql);

        // delete duplicates
        $sql = "DELETE `b` FROM `tag_map` AS `a`, `tag_map` AS `b` " .
               "WHERE `a`.`id` < `b`.`id` AND `a`.`tag_id` <=> `b`.`tag_id` AND " .
               "`a`.`object_id` <=> `b`.`object_id` AND `a`.`object_type` <=> `b`.`object_type`";
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

        $sql = "DELETE FROM `tag_merge` " .
               "WHERE `tag_merge`.`tag_id` = ?";
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
            return (int) (parent::get_from_cache('tag_name', $value))[0];
        }

        $sql        = "SELECT `id` FROM `tag` WHERE `name` = ?";
        $db_results = Dba::read($sql, array($value));

        $results = Dba::fetch_assoc($db_results);

        parent::add_to_cache('tag_name', $results['name'], $results);

        return (int) $results['id'];
    } // tag_exists

    /**
     * tag_map_exists
     * This looks to see if the current mapping of the current object of the current tag of the current
     * user exists, lots of currents... taste good in scones.
     * @param string $type
     * @param integer $object_id
     * @param integer $tag_id
     * @param integer $user
     * @return boolean|mixed
     */
    public static function tag_map_exists($type, $object_id, $tag_id, $user)
    {
        if (!Core::is_library_item($type)) {
            debug_event(self::class, 'Requested type is not a library item.', 3);

            return false;
        }

        $sql = "SELECT * FROM `tag_map` LEFT JOIN `tag` ON `tag`.`id` = `tag_map`.`tag_id` LEFT JOIN `tag_merge` ON `tag`.`id`=`tag_merge`.`tag_id` " .
            "WHERE (`tag_map`.`tag_id` = ? OR `tag_map`.`tag_id` = `tag_merge`.`merged_to`) AND `tag_map`.`user` = ? AND `tag_map`.`object_id` = ? AND `tag_map`.`object_type` = ?";
        $db_results = Dba::read($sql, array($tag_id, $user, $object_id, $type));

        $results = Dba::fetch_assoc($db_results);

        return $results['id'];
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
        if (!Core::is_library_item($type)) {
            return array();
        }

        $object_id = (int) ($object_id);

        $limit = (int) ($limit);
        $sql   = "SELECT `tag_map`.`id`, `tag_map`.`tag_id`, `tag`.`name`, `tag_map`.`user` FROM `tag` " .
            "LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` " .
            "WHERE `tag_map`.`object_type`='$type' AND `tag_map`.`object_id`='$object_id' " .
            "LIMIT $limit";

        $db_results = Dba::read($sql);

        $results = array();

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
        if (!Core::is_library_item($type)) {
            return false;
        }

        $params = array($type);
        $sql    = "SELECT `tag_map`.`id`, `tag`.`name`, `tag_map`.`user` FROM `tag` " .
            "LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` " .
            "WHERE `tag_map`.`object_type` = ?";
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
        if (!Core::is_library_item($type)) {
            return array();
        }
        $tag_sql   = ((int) $tag_id == 0) ? "" : "`tag_map`.`tag_id` = ? AND";
        $sql_param = ($tag_sql == "") ? array($type) : array($tag_id, $type);
        $limit_sql = "";
        if ($count) {
            $limit_sql = " LIMIT ";
            if ($offset) {
                $limit_sql .= (string) ($offset) . ', ';
            }
            $limit_sql .= (string) ($count);
        }

        $sql = "SELECT DISTINCT `tag_map`.`object_id` FROM `tag_map` " .
            "WHERE $tag_sql `tag_map`.`object_type` = ? ";
        if (AmpConfig::get('catalog_disable') && in_array($type, array('song', 'artist', 'album'))) {
            $sql .= "AND " . Catalog::get_enable_filter($type, '`tag_map`.`object_id`');
        }
        $sql .= $limit_sql;
        $db_results = Dba::read($sql, $sql_param);

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['object_id'];
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
        if (!Core::is_library_item($type)) {
            return array();
        }

        $limit_sql = "";
        if ($count) {
            $limit_sql = " LIMIT ";
            if ($offset) {
                $limit_sql .= (string) ($offset) . ', ';
            }
            $limit_sql .= (string) ($count);
        }

        $sql = "SELECT DISTINCT `tag_map`.`tag_id` FROM `tag_map` " .
            "WHERE `tag_map`.`object_type` = ? ";
        if (AmpConfig::get('catalog_disable') && in_array($type, array('song', 'artist', 'album'))) {
            $sql .= "AND " . Catalog::get_enable_filter($type, '`tag_map`.`object_id`');
        }
        $sql .= $limit_sql;
        $db_results = Dba::read($sql, array($type));

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['tag_id'];
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
        //debug_event(self::class, 'Get tags list called...', 5);
        if (parent::is_cached('tags_list', 'no_name')) {
            //debug_event(self::class, 'Tags list found into cache memory!', 5);
            return parent::get_from_cache('tags_list', 'no_name');
        }

        $results = array();

        $sql = "SELECT `tag_map`.`tag_id`, `tag`.`name`, `tag`.`is_hidden`, COUNT(`tag_map`.`object_id`) AS `count` " .
            "FROM `tag_map` " .
            "LEFT JOIN `tag` ON `tag`.`id`=`tag_map`.`tag_id` " .
            "WHERE `tag`.`is_hidden` = false " .
            "GROUP BY `tag_map`.`tag_id`, `tag`.`name`, `tag`.`is_hidden` ";
        if (!empty($type)) {
            $sql .= ", `tag_map`.`object_type` = '" . (string) scrub_in($type) . "' ";
        }
        $order = "`" . $order . "`";
        if ($order == 'count') {
            $order .= " DESC";
        }
        $sql .= "ORDER BY " . $order;

        if ($limit > 0) {
            $sql .= " LIMIT $limit";
        }

        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['tag_id']] = array('id' => $row['tag_id'], 'name' => $row['name'], 'is_hidden' => $row['is_hidden'], 'count' => $row['count']);
        }

        parent::add_to_cache('tags_list', 'no_name', $results);

        return $results;
    } // get_tags

    /**
     * get_display
     * This returns a csv formated version of the tags that we are given
     * it also takes a type so that it knows how to return it, this is used
     * by the formating functions of the different objects
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

        $results = '';

        // Iterate through the tags, format them according to type and element id
        foreach ($tags as $value) {
            if ($link) {
                $results .= '<a href="' . AmpConfig::get('web_path') . '/browse.php?action=tag&show_tag=' . $value['id'] . (!empty($filter_type) ? '&type=' . $filter_type : '') . '" title="' . $value['name'] . '">';
            }
            $results .= $value['name'];
            if ($link) {
                $results .= '</a>';
            }
            $results .= ', ';
        }

        $results = rtrim((string) $results, ', ');

        return $results;
    } // get_display

    /**
     * update_tag_list
     * Update the tags list based on a comma-separated list
     *  (ex. tag1,tag2,tag3,..)
     * @param string $tags_comma
     * @param string $type
     * @param integer $object_id
     * @param boolean $overwrite
     * @return boolean
     */
    public static function update_tag_list($tags_comma, $type, $object_id, $overwrite)
    {
        if (!strlen((string) $tags_comma) > 0) {
            return false;
        }
        debug_event(self::class, 'Updating tags for values {' . $tags_comma . '} type {' . $type . '} object_id {' . $object_id . '}', 5);

        $ctags       = self::get_top_tags($type, $object_id);
        $filterfolk  = str_replace('Folk, World, & Country', 'Folk World & Country', $tags_comma);
        $filterunder = str_replace('_',', ', $filterfolk);
        $filter      = str_replace(';',', ', $filterunder);
        $filter_list = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $filter);
        $editedTags  = (is_array($filter_list)) ? array_unique($filter_list) : array();

        foreach ($ctags as $ctid => $ctv) {
            if ($ctv['id'] != '') {
                $ctag  = new Tag($ctv['id']);
                $found = false;

                foreach ($editedTags as $tk => $tv) {
                    if ($ctag->name == $tv) {
                        $found = true;
                        break;
                    }
                }

                if ($found) {
                    unset($editedTags[$ctag->name]);
                } else {
                    if ($overwrite && $ctv['user'] == 0) {
                        debug_event(self::class, 'The tag {' . $ctag->name . '} was not found in the new list. Delete it.', 5);
                        $ctag->remove_map($type, $object_id, false);
                    }
                }
            }
        }
        // Look if we need to add some new tags
        foreach ($editedTags as $tk => $tv) {
            if ($tv != '') {
                self::add($type, $object_id, $tv, false);
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
            $filterunder = str_replace('_',', ', $filterfolk);
            $filter      = str_replace(';',', ', $filterunder);
            $filter_list = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $filter);
            $taglist     = (is_array($filter_list)) ? array_unique($filter_list) : array();
        }

        $ret = array();
        foreach ($taglist as $tag) {
            $tag = trim((string) $tag);
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
            $results[$row['object_type']] = $row['count'];
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
        if (!Core::is_library_item($type)) {
            return false;
        }

        if ($user === true) {
            $uid = (int) (Core::get_global('user')->id);
        } else {
            $uid = (int) ($user);
        }

        $sql = "DELETE FROM `tag_map` WHERE `tag_id` = ? AND `object_type` = ? AND `object_id` = ? AND `user` = ?";
        Dba::write($sql, array($this->id, $type, $object_id, $uid));

        return true;
    } // remove_map

    /**
     * @param boolean $details
     */
    public function format($details = true)
    {
        unset($details); //dead code but called from other format calls
    }

    /**
     * get_keywords
     * @return array
     */
    public function get_keywords()
    {
        $keywords        = array();
        $keywords['tag'] = array('important' => true,
            'label' => T_('Tag'),
            'value' => $this->name);

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
     * can_edit_tag_map
     * @param string $object_type
     * @param integer $object_id
     * @param string|boolean $user
     * @return boolean
     */
    public static function can_edit_tag_map($object_type, $object_id, $user = true)
    {
        if ($user === true) {
            $uid = (int) (Core::get_global('user')->id);
        } else {
            $uid = (int) ($user);
        }

        if ($uid > 0) {
            return Access::check('interface', 25);
        }

        if (Access::check('interface', 75)) {
            return true;
        }

        if (Core::is_library_item($object_type)) {
            $libitem = new $object_type($object_id);
            $owner   = $libitem->get_user_owner();

            return ($owner !== null && $owner == $uid);
        }

        return false;
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
} // end tag.class
