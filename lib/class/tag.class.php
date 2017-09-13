<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Tag Class
 *
 * This class hnadles all of the tag relation operations
 *
 */
class Tag extends database_object implements library_item
{
    public $id;
    public $name;
    public $is_hidden;

    /**
     * constructor
     * This takes a tag id and returns all of the relevent information
     */
    public function __construct($id)
    {
        if (!$id) {
            return false;
        }

        $info = $this->get_info($id);

        foreach ($info as $key => $value) {
            $this->$key = $value;
        } // end foreach
    } // constructor

    /**
     * construct_from_name
     * This attempts to construct the tag from a name, rather then the ID
     */
    public static function construct_from_name($name)
    {
        $tag_id = self::tag_exists($name);

        $tag = new Tag($tag_id);

        return $tag;
    } // construct_from_name

    /**
     * build_cache
     * This takes an array of object ids and caches all of their information
     * in a single query, cuts down on the connections
     */
    public static function build_cache($ids)
    {
        if (!is_array($ids) or !count($ids)) {
            return false;
        }

        $idlist = '(' . implode(',', $ids) . ')';

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
     */
    public static function build_map_cache($type, $ids)
    {
        if (!is_array($ids) or !count($ids)) {
            return false;
        }

        if (!Core::is_library_item($type)) {
            return false;
        }

        $idlist = '(' . implode(',', $ids) . ')';

        $sql = "SELECT `tag_map`.`id`,`tag_map`.`tag_id`, `tag`.`name`,`tag_map`.`object_id`,`tag_map`.`user` FROM `tag` " .
            "LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` " .
            "WHERE `tag_map`.`object_type`='$type' AND `tag_map`.`object_id` IN $idlist";

        $db_results = Dba::read($sql);

        $tags    = array();
        $tag_map = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $tags[$row['object_id']][$row['tag_id']] = array('user' => $row['user'], 'id' => $row['tag_id'], 'name' => $row['name']);
            $tag_map[$row['object_id']]              = array('id' => $row['id'],'tag_id' => $row['tag_id'],'user' => $row['user'],'object_type' => $type,'object_id' => $row['object_id']);
        }

        // Run through our original ids as we also want to cache NULL
        // results
        foreach ($ids as $id) {
            if (!isset($tags[$id])) {
                $tags[$id]    = null;
                $tag_map[$id] = null;
            }
            parent::add_to_cache('tag_top_' . $type, $id, $tags[$id]);
            parent::add_to_cache('tag_map_' . $type, $id, $tag_map[$id]);
        }

        return true;
    } // build_map_cache

    /**
     * add
     * This is a wrapper function, it figures out what we need to add, be it a tag
     * and map, or just the mapping
     */
    public static function add($type, $id, $value, $user=true)
    {
        if (!Core::is_library_item($type)) {
            return false;
        }

        if (!is_numeric($id)) {
            return false;
        }

        $cleaned_value = $value;

        if (!strlen($cleaned_value)) {
            return false;
        }

        if ($user === true) {
            $uid = intval($GLOBALS['user']->id);
        } elseif ($user === false) {
            $uid = 0;
        } else {
            $uid = intval($user);
        }

        // Check and see if the tag exists, if not create it, we need the tag id from this
        if (!$tag_id = self::tag_exists($cleaned_value)) {
            $tag_id = self::add_tag($cleaned_value);
        }

        if (!$tag_id) {
            debug_event('Error', 'Error unable to create tag value:' . $cleaned_value . ' unknown error', '1');

            return false;
        }

        // We've got the tag id, let's see if it's already got a map, if not then create the map and return the value
        if (!$map_id = self::tag_map_exists($type, $id, $tag_id, $uid)) {
            $map_id = self::add_tag_map($type, $id, $tag_id, $uid);
        }

        return $map_id;
    } // add

    /**
     * add_tag
     * This function adds a new tag, for now we're going to limit the tagging a bit
     */
    public static function add_tag($value)
    {
        if (!strlen($value)) {
            return false;
        }

        $sql = "REPLACE INTO `tag` SET `name` = ?";
        Dba::write($sql, array($value));
        $insert_id = Dba::insert_id();

        parent::add_to_cache('tag_name', $value, $insert_id);

        return $insert_id;
    } // add_tag

    /**
     * update
     * Update the name of the tag
     */
    public function update(array $data)
    {
        //debug_event('tag.class', 'Updating tag {'.$this->id.'} with name {'.$name.'}...', '5');
        if (!strlen($data['name'])) {
            return false;
        }

        $sql = 'UPDATE `tag` SET `name` = ? WHERE `id` = ?';
        Dba::write($sql, array($data[name], $this->id));

        if ($data['edit_tags']) {
            $tag_names = explode(',', $data['edit_tags']);
            foreach ($tag_names as $tag) {
                $merge_to = Tag::construct_from_name($tag);
                if ($merge_to->id == 0) {
                    Tag::add_tag($tag);
                    $merge_to = Tag::construct_from_name($tag);
                }
                $this->merge($merge_to->id, $data['merge_persist'] == '1');
            }
            if ($data['keep_existing'] != '1') {
                $sql = "DELETE FROM `tag_map` WHERE `tag_map`.`tag_id` = ? ";
                Dba::write($sql, array($this->id));
                if ($data['merge_persist'] != '1') {
                    $this->delete();
                } else {
                    $sql = "UPDATE `tag` SET `is_hidden` = true WHERE `tag`.`tag` = ? ";
                    Dba::write($sql, array($this->id));
                }
            }
        }

        return $this->id;
    } // add_tag

    /**
     * merge
     * merges this tag to another one.
     */
    public function merge($merge_to, $is_persistent)
    {
        if ($this->id != $merge_to) {
            debug_event('tag', 'Merging tag ' . $this->id . ' into ' . $merge_to . ')...', '5');

            $sql = "INSERT INTO `tag_map` (`tag_id`,`user`,`object_type`,`object_id`) " .
                   "SELECT ?,`user`,`object_type`,`object_id` " .
                   "FROM `tag_map` AS `tm`" .
                   "WHERE `tm`.`tag_id` = ? AND NOT EXISTS ( " .
                       "SELECT 1 FROM `tag_map` " .
                       "WHERE `tag_map`.`tag_id` = ? " .
                         "AND `tag_map`.`object_id` = `tm`.`object_id` " .
                         "AND `tag_map`.`object_type` = `tm`.`object_type` " .
                         "AND `tag_map`.`user` = `tm`.`user`" .
                   ")";
            Dba::write($sql, array($merge_to, $this->id, $merge_to));
            if ($is_persistent) {
                $sql = 'INSERT INTO `tag_merge` (`tag_id`, `merged_to`) VALUES (?, ?)';
                Dba::write($sql, array($this->id, $merge_to));
            }
        }
    }

    /**
     * get_merged_tags
     * Get merged tags to this tag.
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
     */
    public static function add_tag_map($type, $object_id, $tag_id, $user=true)
    {
        if ($user === true) {
            $uid = intval($GLOBALS['user']->id);
        } elseif ($user === false) {
            $uid = 0;
        } else {
            $uid = intval($user);
        }
        
        $tag_id = intval($tag_id);
        if (!Core::is_library_item($type)) {
            debug_event('tag.class', $type . " is not a library item.", 3);

            return false;
        }
        $id = intval($object_id);

        if (!$tag_id || !$id) {
            return false;
        }

        // If tag merged to another one, add reference to the merge destination
        $parent = new Tag($tag_id);
        $merges = $parent->get_merged_tags();
        if (!$parent->is_hidden) {
            $merges[] = array('id' => $parent->id, 'name' => $parent->name);
        }
        foreach ($merges as $tag) {
            $sql = "INSERT INTO `tag_map` (`tag_id`,`user`,`object_type`,`object_id`) " .
                "VALUES (?, ?, ?, ?)";
            Dba::write($sql, array($tag['id'], $uid, $type, $id));
        }
        $insert_id = Dba::insert_id();

        parent::add_to_cache('tag_map_' . $type, $insert_id, array('tag_id' => $tag_id, 'user' => $uid, 'object_type' => $type, 'object_id' => $id));

        return $insert_id;
    } // add_tag_map

    /**
     * gc
     *
     * This cleans out tag_maps that are obsolete and then removes tags that
     * have no maps.
     */
    public static function gc()
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

        $sql = "DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `video` ON `video`.`id`=`tag_map`.`object_id` " .
            "WHERE `tag_map`.`object_type`='video' AND `video`.`id` IS NULL";
        Dba::write($sql);

        $sql = "DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `tvshow` ON `tvshow`.`id`=`tag_map`.`object_id` " .
            "WHERE `tag_map`.`object_type`='tvshow' AND `tvshow`.`id` IS NULL";
        Dba::write($sql);

        $sql = "DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `tvshow_season` ON `tvshow_season`.`id`=`tag_map`.`object_id` " .
            "WHERE `tag_map`.`object_type`='tvshow_season' AND `tvshow_season`.`id` IS NULL";
        Dba::write($sql);

        // Now nuke the tags themselves
        $sql = "DELETE FROM `tag` USING `tag` LEFT JOIN `tag_map` ON `tag`.`id`=`tag_map`.`tag_id` " .
            "WHERE `tag_map`.`id` IS NULL " .
            "AND NOT EXISTS (SELECT 1 FROM `tag_merge` where `tag_merge`.`tag_id` = `tag`.`id`)";
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
        Tag::gc();

        parent::clear_cache();
    }

    /**
     * tag_exists
     * This checks to see if a tag exists, this has nothing to do with objects or maps
     */
    public static function tag_exists($value)
    {
        if (parent::is_cached('tag_name', $value)) {
            return parent::get_from_cache('tag_name', $value);
        }

        $sql        = "SELECT * FROM `tag` WHERE `name` = ?";
        $db_results = Dba::read($sql, array($value));

        $results = Dba::fetch_assoc($db_results);

        parent::add_to_cache('tag_name', $results['name'], $results['id']);

        return $results['id'];
    } // tag_exists

    /**
     * tag_map_exists
     * This looks to see if the current mapping of the current object of the current tag of the current
     * user exists, lots of currents... taste good in scones.
     */
    public static function tag_map_exists($type, $object_id, $tag_id, $user)
    {
        if (!Core::is_library_item($type)) {
            debug_event('tag', 'Requested type is not a library item.', 3);

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
     */
    public static function get_top_tags($type, $object_id, $limit = 10)
    {
        if (!Core::is_library_item($type)) {
            return array();
        }

        $object_id = intval($object_id);

        $limit = intval($limit);
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
     * Display all tags that apply to maching target type of the specified id
     *
     */
    public static function get_object_tags($type, $id)
    {
        if (!Core::is_library_item($type)) {
            return false;
        }

        $sql = "SELECT `tag_map`.`id`, `tag`.`name`, `tag_map`.`user` FROM `tag` " .
            "LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` " .
            "WHERE `tag_map`.`object_type` = ? AND `tag_map`.`object_id` = ?";

        $results    = array();
        $db_results = Dba::read($sql, array($type, $id));

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row;
        }

        return $results;
    } // get_object_tags

    /**
     * get_tag_objects
     * This gets the objects from a specified tag and returns an array of object ids, nothing more
     */
    public static function get_tag_objects($type, $tag_id, $count='', $offset='')
    {
        if (!Core::is_library_item($type)) {
            return false;
        }

        $limit_sql = "";
        if ($count) {
            $limit_sql = "LIMIT ";
            if ($offset) {
                $limit_sql .= intval($offset) . ',';
            }
            $limit_sql .= intval($count);
        }

        $sql = "SELECT DISTINCT `tag_map`.`object_id` FROM `tag_map` " .
            "WHERE `tag_map`.`tag_id` = ? AND `tag_map`.`object_type` = ? $limit_sql ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND " . Catalog::get_enable_filter($type, '`tag_map`.`object_id`');
        }
        $db_results = Dba::read($sql, array($tag_id, $type));

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['object_id'];
        }

        return $results;
    } // get_tag_objects

    /**
      * get_tags
     * This is a non-object non type dependent function that just returns tags
     * we've got, it can take filters (this is used by the tag cloud)
     */
    public static function get_tags($type = '', $limit = 0, $order = 'count')
    {
        //debug_event('tag.class.php', 'Get tags list called...', '5');
        if (parent::is_cached('tags_list', 'no_name')) {
            //debug_event('tag.class.php', 'Tags list found into cache memory!', '5');
            return parent::get_from_cache('tags_list', 'no_name');
        }

        $results = array();

        $sql = "SELECT `tag_map`.`tag_id`, `tag`.`name`, `tag`.`is_hidden`, COUNT(`tag_map`.`object_id`) AS `count` " .
            "FROM `tag_map` " .
            "LEFT JOIN `tag` ON `tag`.`id`=`tag_map`.`tag_id` " .
            "WHERE `tag`.`is_hidden` = false " .
            "GROUP BY `tag_map`.`tag_id`, `tag`.`name`, `tag`.`is_hidden` ";
        if (!empty($type)) {
            $sql .= "AND `tag_map`.`object_type` = '" . scrub_in($type) . "' ";
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
     */
    public static function get_display($tags, $link=false, $filter_type='')
    {
        //debug_event('tag.class.php', 'Get display tags called...', '5');
        if (!is_array($tags)) {
            return '';
        }

        $results = '';

        // Iterate through the tags, format them according to type and element id
        foreach ($tags as $tag_id => $value) {
            /*debug_event('tag.class.php', $tag_id, '5');
            foreach ($value as $vid=>$v) {
                debug_event('tag.class.php', $vid.' = {'.$v.'}', '5');
            }*/
            if ($link) {
                $results .= '<a href="' . AmpConfig::get('web_path') . '/browse.php?action=tag&show_tag=' . $value['id'] . (!empty($filter_type) ? '&type=' . $filter_type : '') . '" title="' . $value['name'] . '">';
            }
            $results .= $value['name'];
            if ($link) {
                $results .= '</a>';
            }
            $results .= ', ';
        }

        $results = rtrim($results, ', ');

        return $results;
    } // get_display

    /**
     * update_tag_list
     * Update the tags list based on commated list (ex. tag1,tag2,tag3,..)
     */
    public static function update_tag_list($tags_comma, $type, $object_id, $overwrite)
    {
        debug_event('tag.class', 'Updating tags for values {' . $tags_comma . '} type {' . $type . '} object_id {' . $object_id . '}', '5');

        $ctags      = Tag::get_top_tags($type, $object_id);
        $editedTags = explode(",", $tags_comma);

        if (is_array($ctags)) {
            foreach ($ctags as $ctid => $ctv) {
                if ($ctv['id'] != '') {
                    $ctag = new Tag($ctv['id']);
                    debug_event('tag.class', 'Processing tag {' . $ctag->name . '}...', '5');
                    $found = false;

                    foreach ($editedTags as  $tk => $tv) {
                        if ($ctag->name == $tv) {
                            $found = true;
                            break;
                        }
                    }

                    if ($found) {
                        debug_event('tag.class', 'Already found. Do nothing.', '5');
                        unset($editedTags[$tk]);
                    } else {
                        if ($overwrite) {
                            debug_event('tag.class', 'Not found in the new list. Delete it.', '5');
                            $ctag->remove_map($type, $object_id, false);
                        }
                    }
                }
            }
        }

        // Look if we need to add some new tags
        foreach ($editedTags as  $tk => $tv) {
            if ($tv != '') {
                debug_event('tag.class', 'Adding new tag {' . $tv . '}', '5');
                Tag::add($type, $object_id, $tv, false);
            }
        }
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
            $ar = $tags;
        } else {
            $ar = explode(",", $tags);
        }

        $ret = array();
        foreach ($ar as $tag) {
            $tag = trim($tag);
            if (!empty($tag)) {
                if (Tag::tag_exists($tag)) {
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
     */
    public function count($type='', $user_id = 0)
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
     */
    public function remove_map($type, $object_id, $user=true)
    {
        if (!Core::is_library_item($type)) {
            return false;
        }

        if ($user === true) {
            $uid = intval($GLOBALS['user']->id);
        } elseif ($user === false) {
            $uid = 0;
        } else {
            $uid = intval($user);
        }

        $sql = "DELETE FROM `tag_map` WHERE `tag_id` = ? AND `object_type` = ? AND `object_id` = ? AND `user` = ?";
        Dba::write($sql, array($this->id, $type, $object_id, $uid));

        return true;
    } // remove_map

    public function format($details = true)
    {
    }

    public function get_keywords()
    {
        $keywords        = array();
        $keywords['tag'] = array('important' => true,
            'label' => T_('Tag'),
            'value' => $this->name);

        return $keywords;
    }

    public function get_fullname()
    {
        return $this->name;
    }

    public function get_parent()
    {
        return null;
    }

    public function get_childrens()
    {
        return array();
    }

    public function search_childrens($name)
    {
        return array();
    }

    public function get_medias($filter_type = null)
    {
        $medias = array();
        if ($filter_type) {
            $ids = Tag::get_tag_objects($filter_type, $this->id);
            if ($ids) {
                foreach ($ids as $id) {
                    $medias[] = array(
                        'object_type' => $filter_type,
                        'object_id' => $id
                    );
                }
            }
        }

        return $medias;
    }

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return int[]
     */
    public function get_catalogs()
    {
        return array();
    }

    public function get_user_owner()
    {
        return null;
    }

    public function get_default_art_kind()
    {
        return 'default';
    }

    public function get_description()
    {
        return null;
    }

    public function display_art($thumb = 2, $force = false)
    {
        if (Art::has_db($this->id, 'tag') || $force) {
            Art::display('tag', $this->id, $this->get_fullname(), $thumb, $this->link);
        }
    }
    
    public static function can_edit_tag_map($object_type, $object_id, $user = true)
    {
        if ($user === true) {
            $uid = intval($GLOBALS['user']->id);
        } elseif ($user === false) {
            $uid = 0;
        } else {
            $uid = intval($user);
        }
        
        if ($uid > 0) {
            return Access::check('interface', '25');
        }
        
        if (Access::check('interface', '75')) {
            return true;
        }
        
        if (Core::is_library_item($object_type)) {
            $libitem = new $object_type($object_id);
            $owner   = $libitem->get_user_owner();

            return ($owner !== null && $owner == $uid);
        }
        
        return false;
    }
} // end of Tag class
