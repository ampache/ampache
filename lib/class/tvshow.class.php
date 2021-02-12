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
     * @param $show_id
     */
    public function __construct($show_id)
    {
        /* Get the information from the db */
        $info = $this->get_info($show_id);

        foreach ($info as $key => $value) {
            $this->$key = $value;
        } // foreach info

        return true;
    } // constructor

    /**
     * garbage_collection
     *
     * This cleans out unused tv shows
     */
    public static function garbage_collection()
    {
        $sql = "DELETE FROM `tvshow` USING `tvshow` LEFT JOIN `tvshow_season` ON `tvshow_season`.`tvshow` = `tvshow`.`id` " .
            "WHERE `tvshow_season`.`id` IS NULL";
        Dba::write($sql);
    }

    /**
     * get_from_name
     * This gets a tv show object based on the tv show name
     * @param string $name
     * @return TVShow
     */
    public static function get_from_name($name)
    {
        $sql        = "SELECT `id` FROM `tvshow` WHERE `name` = ?'";
        $db_results = Dba::read($sql, array($name));

        $row = Dba::fetch_assoc($db_results);

        return new TVShow($row['id']);
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
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
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
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    } // get_episodes

    /**
     * _get_extra info
     * This returns the extra information for the tv show, this means totals etc
     * @return array
     */
    private function _get_extra_info()
    {
        // Try to find it in the cache and save ourselves the trouble
        if (parent::is_cached('tvshow_extra', $this->id)) {
            $row = parent::get_from_cache('tvshow_extra', $this->id);
        } else {
            $sql = "SELECT COUNT(`tvshow_episode`.`id`) AS `episode_count`, `video`.`catalog` as `catalog_id` FROM `tvshow_season` " .
                "LEFT JOIN `tvshow_episode` ON `tvshow_episode`.`season` = `tvshow_season`.`id` " .
                "LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` " .
                "WHERE `tvshow_season`.`tvshow` = ? " .
                "GROUP BY `catalog_id`";
            $db_results = Dba::read($sql, array($this->id));
            $row        = Dba::fetch_assoc($db_results);

            $sql = "SELECT COUNT(`tvshow_season`.`id`) AS `season_count` FROM `tvshow_season` " .
                "WHERE `tvshow_season`.`tvshow` = ?";
            $db_results          = Dba::read($sql, array($this->id));
            $row2                = Dba::fetch_assoc($db_results);
            $row['season_count'] = $row2['season_count'];

            parent::add_to_cache('tvshow_extra', $this->id, $row);
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
     * @param boolean $details
     * @return boolean
     */
    public function format($details = true)
    {
        $this->f_name = trim((string) $this->prefix . " " . $this->name);
        $this->link   = AmpConfig::get('web_path') . '/tvshows.php?action=show&tvshow=' . $this->id;
        $this->f_link = '<a href="' . $this->link . '" title="' . $this->f_name . '">' . $this->f_name . '</a>';

        if ($details) {
            $this->_get_extra_info();
            $this->tags   = Tag::get_top_tags('tvshow', $this->id);
            $this->f_tags = Tag::get_display($this->tags, true, 'tvshow');
        }

        return true;
    }

    /**
     * get_keywords
     * @return array|mixed
     */
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

    /**
     * @return string
     */
    public function get_fullname()
    {
        return $this->f_name;
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
        return array('tvshow_season' => $this->get_seasons());
    }

    /**
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        debug_event(self::class, 'search_childrens ' . $name, 5);

        return array();
    }

    /**
     * @param string $filter_type
     * @return array|mixed
     */
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if ($filter_type === null || $filter_type == 'video') {
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
     * @return integer[]
     */
    public function get_catalogs()
    {
        return array($this->catalog_id);
    }

    /**
     * @return mixed|null
     */
    public function get_user_owner()
    {
        return null;
    }

    /**
     * @return string
     */
    public function get_default_art_kind()
    {
        return 'default';
    }

    /**
     * @return mixed
     */
    public function get_description()
    {
        return $this->summary;
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
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
     * @param string $name
     * @param $year
     * @param $tvshow_summary
     * @param boolean $readonly
     * @return integer|string|null
     */
    public static function check($name, $year, $tvshow_summary, $readonly = false)
    {
        // null because we don't have any unique id like mbid for now
        if (isset(self::$_mapcache[$name]['null'])) {
            return self::$_mapcache[$name]['null'];
        }

        $tvshow_id  = 0;
        $exists     = false;
        $trimmed    = Catalog::trim_prefix(trim((string) $name));
        $name       = $trimmed['string'];
        $prefix     = $trimmed['prefix'];
        $sql        = 'SELECT `id` FROM `tvshow` WHERE `name` LIKE ? AND `year` = ?';
        $db_results = Dba::read($sql, array($name, $year));
        $id_array   = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $key            = 'null';
            $id_array[$key] = $row['id'];
        }

        if (count($id_array)) {
            $tvshow_id = array_shift($id_array);
            $exists    = true;
        }

        if ($exists && (int) $tvshow_id > 0) {
            self::$_mapcache[$name]['null'] = $tvshow_id;

            return $tvshow_id;
        }

        if ($readonly) {
            return null;
        }

        $sql        = 'INSERT INTO `tvshow` (`name`, `prefix`, `year`, `summary`) VALUES(?, ?, ?, ?)';
        $db_results = Dba::write($sql, array($name, $prefix, $year, $tvshow_summary));
        if (!$db_results) {
            return null;
        }
        $tvshow_id = Dba::insert_id();

        self::$_mapcache[$name]['null'] = $tvshow_id;

        return $tvshow_id;
    }

    /**
     * update
     * This takes a key'd array of data and updates the current tv show
     * @param array $data
     * @return integer|string|null
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
                    TVShow_Season::update_tvshow($tvshow_id, $season_id);
                }
                $current_id = $tvshow_id;
                Stats::migrate('tvshow', $this->id, (int) $tvshow_id);
                UserActivity::migrate('tvshow', $this->id, (int) $tvshow_id);
                Recommendation::migrate('tvshow', $this->id, (int) $tvshow_id);
                Share::migrate('tvshow', $this->id, (int) $tvshow_id);
                Shoutbox::migrate('tvshow', $this->id, (int) $tvshow_id);
                Tag::migrate('tvshow', $this->id, (int) $tvshow_id);
                Userflag::migrate('tvshow', $this->id, (int) $tvshow_id);
                Rating::migrate('tvshow', $this->id, (int) $tvshow_id);
                Art::migrate('tvshow', $this->id, (int) $tvshow_id);
                if (!AmpConfig::get('cron_cache')) {
                    self::garbage_collection();
                }
            } // end if it changed
        }

        $trimmed = Catalog::trim_prefix(trim((string) $name));
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
            $this->update_tags($data['edit_tags'], $override_childs, $add_to_childs, true);
        }

        return $current_id;
    } // update

    /**
     * update_tags
     *
     * Update tags of tv shows
     * @param string $tags_comma
     * @param boolean $override_childs
     * @param boolean $add_to_childs
     * @param boolean $force_update
     */
    public function update_tags($tags_comma, $override_childs, $add_to_childs, $force_update = false)
    {
        Tag::update_tag_list($tags_comma, 'tvshow', $this->id, $force_update ? true : $override_childs);

        if ($override_childs || $add_to_childs) {
            $episodes = $this->get_episodes();
            foreach ($episodes as $ep_id) {
                Tag::update_tag_list($tags_comma, 'episode', $ep_id, $override_childs);
            }
        }
    }

    /**
     * @return PDOStatement|boolean
     */
    public function remove()
    {
        $deleted    = true;
        $season_ids = $this->get_seasons();
        foreach ($season_ids as $season_object) {
            $season  = new TVShow_Season($season_object);
            $deleted = $season->remove();
            if (!$deleted) {
                debug_event(self::class, 'Error when deleting the season `' . (string) $season_object . '`.', 1);
                break;
            }
        }

        if ($deleted) {
            $sql     = "DELETE FROM `tvshow` WHERE `id` = ?";
            $deleted = Dba::write($sql, array($this->id));
            if ($deleted) {
                Art::garbage_collection('tvshow', $this->id);
                Userflag::garbage_collection('tvshow', $this->id);
                Rating::garbage_collection('tvshow', $this->id);
                Shoutbox::garbage_collection('tvshow', $this->id);
                Useractivity::garbage_collection('tvshow', $this->id);
            }
        }

        return $deleted;
    }
} // end tvshow.class
