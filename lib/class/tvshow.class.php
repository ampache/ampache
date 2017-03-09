<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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

class TVShow extends database_object implements library_item
{
    /* Variables from DB */
    public $id;
    public $name;
    public $prefix;
    public $summary;
    public $year;

    public $catalog_id;
    public $tags;
    public $f_tags;
    public $episodes;
    public $seasons;
    public $f_name;
    public $link;
    public $f_link;


    // Constructed vars
    private static $_mapcache = array();

    /**
     * TV Show
     * Takes the ID of the tv show and pulls the info from the db
     */
    public function __construct($id='')
    {
        /* If they failed to pass in an id, just run for it */
        if (!$id) {
            return false;
        }

        /* Get the information from the db */
        $info = $this->get_info($id);

        foreach ($info as $key=>$value) {
            $this->$key = $value;
        } // foreach info

        return true;
    } //constructor

    /**
     * gc
     *
     * This cleans out unused tv shows
     */
    public static function gc()
    {
        $sql = "DELETE FROM `tvshow` USING `tvshow` LEFT JOIN `tvshow_season` ON `tvshow_season`.`tvshow` = `tvshow`.`id` " .
            "WHERE `tvshow_season`.`id` IS NULL";
        Dba::write($sql);
    }

    /**
     * get_from_name
     * This gets a tv show object based on the tv show name
     */
    public static function get_from_name($name)
    {
        $sql        = "SELECT `id` FROM `tvshow` WHERE `name` = ?'";
        $db_results = Dba::read($sql, array($name));

        $row = Dba::fetch_assoc($db_results);

        $object = new TVShow($row['id']);
        return $object;
    } // get_from_name

    /**
     * get_seasons
     * gets the tv show seasons
     * of
     */
    public function get_seasons()
    {
        $sql        = "SELECT `id` FROM `tvshow_season` WHERE `tvshow` = ? ORDER BY `season_number`";
        $db_results = Dba::read($sql, array($this->id));
        $results    = array();
        while ($r = Dba::fetch_assoc($db_results)) {
            $results[] = $r['id'];
        }

        return $results;
    } // get_seasons

    /**
     * get_episodes
     * gets all episodes for this tv show
     */
    public function get_episodes()
    {
        $sql = "SELECT `tvshow_episode`.`id` FROM `tvshow_episode` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` ";
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `video`.`catalog` ";
        }
        $sql .= "LEFT JOIN `tvshow_season` ON `tvshow_season`.`id` = `tvshow_episode`.`season` ";
        $sql .= "WHERE `tvshow_season`.`tvshow`='" . Dba::escape($this->id) . "' ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND `catalog`.`enabled` = '1' ";
        }
        $sql .= "ORDER BY `tvshow_season`.`season_number`, `tvshow_episode`.`episode_number`";
        $db_results = Dba::read($sql);

        $results = array();
        while ($r = Dba::fetch_assoc($db_results)) {
            $results[] = $r['id'];
        }

        return $results;
    } // get_episodes

    /**
     * _get_extra info
     * This returns the extra information for the tv show, this means totals etc
     */
    private function _get_extra_info()
    {
        // Try to find it in the cache and save ourselves the trouble
        if (parent::is_cached('tvshow_extra', $this->id) ) {
            $row = parent::get_from_cache('tvshow_extra', $this->id);
        } else {
            $sql = "SELECT COUNT(`tvshow_episode`.`id`) AS `episode_count`, `video`.`catalog` as `catalog_id` FROM `tvshow_season` " .
                "LEFT JOIN `tvshow_episode` ON `tvshow_episode`.`season` = `tvshow_season`.`id` " .
                "LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` " .
                "WHERE `tvshow_season`.`tvshow` = ?";
            $db_results = Dba::read($sql, array($this->id));
            $row        = Dba::fetch_assoc($db_results);

            $sql = "SELECT COUNT(`tvshow_season`.`id`) AS `season_count` FROM `tvshow_season` " .
                "WHERE `tvshow_season`.`tvshow` = ?";
            $db_results          = Dba::read($sql, array($this->id));
            $row2                = Dba::fetch_assoc($db_results);
            $row['season_count'] = $row2['season_count'];

            parent::add_to_cache('tvshow_extra',$this->id,$row);
        }

        /* Set Object Vars */
        $this->episodes   = $row['episode_count'];
        $this->seasons    = $row['season_count'];
        $this->catalog_id = $row['catalog_id'];

        return $row;
    } // _get_extra_info

    /**
     * format
     * this function takes the object and reformats some values
     */
    public function format($details = true)
    {
        $this->f_name = trim($this->prefix . " " . $this->name);
        $this->link   = AmpConfig::get('web_path') . '/tvshows.php?action=show&tvshow=' . $this->id;
        $this->f_link = '<a href="' . $this->link . '" title="' . $this->f_name . '">' . $this->f_name . '</a>';

        if ($details) {
            $this->_get_extra_info();
            $this->tags   = Tag::get_top_tags('tvshow', $this->id);
            $this->f_tags = Tag::get_display($this->tags, true, 'tvshow');
        }

        return true;
    }

    public function get_keywords()
    {
        $keywords           = array();
        $keywords['tvshow'] = array('important' => true,
            'label' => T_('TV Show'),
            'value' => $this->f_name);
        $keywords['type'] = array('important' => false,
            'label' => null,
            'value' => 'tvshow'
        );

        return $keywords;
    }

    public function get_fullname()
    {
        return $this->f_name;
    }

    public function get_parent()
    {
        return null;
    }

    public function get_childrens()
    {
        return array('tvshow_season' => $this->get_seasons());
    }

    public function search_childrens($name)
    {
        return array();
    }

    public function get_medias($filter_type = null)
    {
        $medias = array();
        if (!$filter_type || $filter_type == 'video') {
            $episodes = $this->get_episodes();
            foreach ($episodes as $episode_id) {
                $medias[] = array(
                    'object_type' => 'video',
                    'object_id' => $episode_id
                );
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
        return array($this->catalog_id);
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
        return $this->summary;
    }

    public function display_art($thumb = 2, $force = false)
    {
        if (Art::has_db($this->id, 'tvshow') || $force) {
            Art::display('tvshow', $this->id, $this->get_fullname(), $thumb, $this->link);
        }
    }

    /**
     * check
     *
     * Checks for an existing tv show; if none exists, insert one.
     */
    public static function check($name, $year, $tvshow_summary, $readonly = false)
    {
        // null because we don't have any unique id like mbid for now
        if (isset(self::$_mapcache[$name]['null'])) {
            return self::$_mapcache[$name]['null'];
        }

        $id     = 0;
        $exists = false;

        $trimmed = Catalog::trim_prefix(trim($name));
        $name    = $trimmed['string'];
        $prefix  = $trimmed['prefix'];

        if (!$exists) {
            $sql        = 'SELECT `id` FROM `tvshow` WHERE `name` LIKE ? AND `year` = ?';
            $db_results = Dba::read($sql, array($name, $year));

            $id_array = array();
            while ($row = Dba::fetch_assoc($db_results)) {
                $key            = 'null';
                $id_array[$key] = $row['id'];
            }

            if (count($id_array)) {
                $id     = array_shift($id_array);
                $exists = true;
            }
        }

        if ($exists) {
            self::$_mapcache[$name]['null'] = $id;
            return $id;
        }

        if ($readonly) {
            return null;
        }

        $sql        = 'INSERT INTO `tvshow` (`name`, `prefix`, `year`, `summary`) VALUES(?, ?, ?, ?)';
        $db_results = Dba::write($sql, array($name, $prefix, $year, $tvshow_summary));
        if (!$db_results) {
            return null;
        }
        $id = Dba::insert_id();

        self::$_mapcache[$name]['null'] = $id;
        return $id;
    }

    /**
     * update
     * This takes a key'd array of data and updates the current tv show
     */
    public function update(array $data)
    {
        // Save our current ID
        $current_id = $this->id;
        $name       = isset($data['name']) ? $data['name'] : $this->name;
        $year       = isset($data['year']) ? $data['year'] : $this->year;
        $summary    = isset($data['summary']) ? $data['summary'] : $this->summary;

        // Check if name is different than current name
        if ($this->name != $name || $this->year != $year) {
            $tvshow_id = self::check($name, $year, true);

            // If it's changed we need to update
            if ($tvshow_id != $this->id && $tvshow_id != null) {
                $seasons = $this->get_seasons();
                foreach ($seasons as $season_id) {
                    Season::update_tvshow($tvshow_id, $season_id);
                }
                $current_id = $tvshow_id;
                Stats::migrate('tvshow', $this->id, $tvshow_id);
                Art::migrate('tvshow', $this->id, $tvshow_id);
                self::gc();
            } // end if it changed
        }

        $trimmed = Catalog::trim_prefix(trim($name));
        $name    = $trimmed['string'];
        $prefix  = $trimmed['prefix'];

        $sql = 'UPDATE `tvshow` SET `name` = ?, `prefix` = ?, `year` = ?, `summary` = ? WHERE `id` = ?';
        Dba::write($sql, array($name, $prefix, $year, $summary, $current_id));

        $this->name    = $name;
        $this->prefix  = $prefix;
        $this->year    = $year;
        $this->summary = $summary;

        $override_childs = false;
        if ($data['overwrite_childs'] == 'checked') {
            $override_childs = true;
        }

        $add_to_childs = false;
        if ($data['add_to_childs'] == 'checked') {
            $add_to_childs = true;
        }

        if (isset($data['edit_tags'])) {
            $this->update_tags($data['edit_tags'], $override_childs, $add_to_childs, $current_id, true);
        }

        return $current_id;
    } // update

    /**
     * update_tags
     *
     * Update tags of tv shows
     */
    public function update_tags($tags_comma, $override_childs, $add_to_childs, $current_id = null, $force_update = false)
    {
        if ($current_id == null) {
            $current_id = $this->id;
        }

        Tag::update_tag_list($tags_comma, 'tvshow', $current_id, $force_update ? true : $override_childs);

        if ($override_childs || $add_to_childs) {
            $episodes = $this->get_episodes();
            foreach ($episodes as $ep_id) {
                Tag::update_tag_list($tags_comma, 'episode', $ep_id, $override_childs);
            }
        }
    }

    public function remove_from_disk()
    {
        $deleted    = true;
        $season_ids = $this->get_seasons();
        foreach ($season_ids as $id) {
            $season  = new TVShow_Season($id);
            $deleted = $season->remove_from_disk();
            if (!$deleted) {
                debug_event('tvshow', 'Error when deleting the season `' . $id . '`.', 1);
                break;
            }
        }

        if ($deleted) {
            $sql     = "DELETE FROM `tvshow` WHERE `id` = ?";
            $deleted = Dba::write($sql, array($this->id));
            if ($deleted) {
                Art::gc('tvshow', $this->id);
                Userflag::gc('tvshow', $this->id);
                Rating::gc('tvshow', $this->id);
                Shoutbox::gc('tvshow', $this->id);
                Useractivity::gc('tvshow', $this->id);
            }
        }

        return $deleted;
    }
} // end of tvshow class
