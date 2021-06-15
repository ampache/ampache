<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Repository\TagRepositoryInterface;

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

        foreach ($info as $key => $value) {
            $this->$key = $value;
        } // end foreach

        // the ui is sometimes looking for a formatted name...
        $this->f_name = $this->name;

        return true;
    } // constructor

    public function getId(): int
    {
        return (int) $this->id;
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
    public static function construct_from_name($name)
    {
        $tag_id = static::getTagRepository()->findByName($name);

        return new Tag($tag_id);
    } // construct_from_name

    /**
     * add_tag
     * This function adds a new tag, for now we're going to limit the tagging a bit
     * @param string $value
     * @return string|null
     */
    public static function add_tag($value)
    {
        if (!strlen((string)$value)) {
            return null;
        }

        $sql = "REPLACE INTO `tag` SET `name` = ?";
        Dba::write($sql, array($value));
        $insert_id = (int)Dba::insert_id();

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
        debug_event(self::class, 'Updating tag {' . $this->id . '} with name {' . $data['name'] . '}...', 5);

        $sql = 'UPDATE `tag` SET `name` = ? WHERE `id` = ?';
        Dba::write($sql, array($data['name'], $this->id));

        if ($data['edit_tags']) {
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

            $sql = "REPLACE INTO `tag_map` (`tag_id`, `user`, `object_type`, `object_id`) " . "SELECT " . $merge_to . ",`user`, `object_type`, `object_id` " . "FROM `tag_map` AS `tm` " . "WHERE `tm`.`tag_id` = " . $this->id . " AND NOT EXISTS (" . "SELECT 1 FROM `tag_map` " . "WHERE `tag_map`.`tag_id` = " . $merge_to . " " . "AND `tag_map`.`object_id` = `tm`.`object_id` " . "AND `tag_map`.`object_type` = `tm`.`object_type` " . "AND `tag_map`.`user` = `tm`.`user`)";
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
        $sql = "SELECT `tag`.`id`, `tag`.`name`" . "FROM `tag_merge` " . "INNER JOIN `tag` ON `tag`.`id` = `tag_merge`.`merged_to` " . "WHERE `tag_merge`.`tag_id` = ? " . "ORDER BY `tag`.`name` ";

        $db_results = Dba::read($sql, array($this->id));

        $results = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['id']] = array('id' => $row['id'], 'name' => $row['name']);
        }

        return $results;
    }

    /**
     * garbage_collection
     *
     * This cleans out tag_maps that are obsolete and then removes tags that
     * have no maps.
     */
    public static function garbage_collection()
    {
        $sql = "DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `song` ON `song`.`id`=`tag_map`.`object_id` " . "WHERE `tag_map`.`object_type`='song' AND `song`.`id` IS NULL";
        Dba::write($sql);

        $sql = "DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `album` ON `album`.`id`=`tag_map`.`object_id` " . "WHERE `tag_map`.`object_type`='album' AND `album`.`id` IS NULL";
        Dba::write($sql);

        $sql = "DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `artist` ON `artist`.`id`=`tag_map`.`object_id` " . "WHERE `tag_map`.`object_type`='artist' AND `artist`.`id` IS NULL";
        Dba::write($sql);

        // Now nuke the tags themselves
        $sql = "DELETE FROM `tag` USING `tag` LEFT JOIN `tag_map` ON `tag`.`id`=`tag_map`.`tag_id` " . "WHERE `tag_map`.`id` IS NULL " . "AND NOT EXISTS (SELECT 1 FROM `tag_merge` WHERE `tag_merge`.`tag_id` = `tag`.`id`)";
        Dba::write($sql);

        // delete duplicates
        $sql = "DELETE `b` FROM `tag_map` AS `a`, `tag_map` AS `b` " . "WHERE `a`.`id` < `b`.`id` AND `a`.`tag_id` <=> `b`.`tag_id` AND " . "`a`.`object_id` <=> `b`.`object_id` AND `a`.`object_type` <=> `b`.`object_type`";
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

        $sql = "DELETE FROM `tag_merge` " . "WHERE `tag_merge`.`tag_id` = ?";
        Dba::write($sql, array($this->id));

        $sql = "DELETE FROM `tag` WHERE `tag`.`id` = ? ";
        Dba::write($sql, array($this->id));

        // Call the garbage collector to clean everything
        self::garbage_collection();
    }

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
        $sql    = "SELECT `tag_map`.`id`, `tag`.`name`, `tag_map`.`user` FROM `tag` " . "LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` " . "WHERE `tag_map`.`object_type` = ?";
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

        return rtrim((string)$results, ', ');
    } // get_display

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

        if ($user === true) {
            $uid = (int)(Core::get_global('user')->id);
        } else {
            $uid = (int)($user);
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
     * @return array{object_type: string, object_id: int}|null
     */
    public function get_parent(): ?array
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
            $ids = static::getTagRepository()->getTagObjectIds($filter_type, $this->getId());
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
            echo Art::display('tag', $this->id, $this->get_fullname(), $thumb);
        }
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getTagRepository(): TagRepositoryInterface
    {
        global $dic;

        return $dic->get(TagRepositoryInterface::class);
    }
}
