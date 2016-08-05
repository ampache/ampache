<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
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

class TVShow_Season extends database_object implements library_item
{
    /* Variables from DB */
    public $id;
    public $season_number;
    public $tvshow;

    public $catalog_id;
    public $episodes;
    public $f_name;
    public $f_tvshow;
    public $f_tvshow_link;
    public $link;
    public $f_link;


    // Constructed vars
    private static $_mapcache = array();

    /**
     * TV Show
     * Takes the ID of the tv show season and pulls the info from the db
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
     * This cleans out unused tv shows seasons
     */
    public static function gc()
    {
        $sql = "DELETE FROM `tvshow_season` USING `tvshow_season` LEFT JOIN `tvshow_episode` ON `tvshow_episode`.`season` = `tvshow_season`.`id` " .
            "WHERE `tvshow_episode`.`id` IS NULL";
        Dba::write($sql);
    }

    /**
     * get_songs
     * gets all episodes for this tv show season
     */
    public function get_episodes()
    {
        $sql = "SELECT `tvshow_episode`.`id` FROM `tvshow_episode` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` ";
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `video`.`catalog` ";
        }
        $sql .= "WHERE `tvshow_episode`.`season`='" . Dba::escape($this->id) . "' ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND `catalog`.`enabled` = '1' ";
        }
        $sql .= "ORDER BY `tvshow_episode`.`episode_number`";
        $db_results = Dba::read($sql);

        $results = array();
        while ($r = Dba::fetch_assoc($db_results)) {
            $results[] = $r['id'];
        }

        return $results;
    } // get_episodes

    /**
     * _get_extra info
     * This returns the extra information for the tv show season, this means totals etc
     */
    private function _get_extra_info()
    {
        // Try to find it in the cache and save ourselves the trouble
        if (parent::is_cached('tvshow_extra', $this->id)) {
            $row = parent::get_from_cache('tvshow_extra', $this->id);
        } else {
            $sql = "SELECT COUNT(`tvshow_episode`.`id`) AS `episode_count`, `video`.`catalog` as `catalog_id` FROM `tvshow_episode` " .
                "LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` " .
                "WHERE `tvshow_episode`.`season` = ?";

            $db_results = Dba::read($sql, array($this->id));
            $row        = Dba::fetch_assoc($db_results);
            parent::add_to_cache('tvshow_extra', $this->id, $row);
        }

        /* Set Object Vars */
        $this->episodes   = $row['episode_count'];
        $this->catalog_id = $row['catalog_id'];

        return $row;
    } // _get_extra_info

    /**
     * format
     * this function takes the object and reformats some values
     */
    public function format($details = true)
    {
        $this->f_name = T_('Season') . ' ' . $this->season_number;

        $tvshow = new TVShow($this->tvshow);
        $tvshow->format($details);
        $this->f_tvshow      = $tvshow->f_name;
        $this->f_tvshow_link = $tvshow->f_link;

        $this->link   = AmpConfig::get('web_path') . '/tvshow_seasons.php?action=show&season=' . $this->id;
        $this->f_link = '<a href="' . $this->link . '" title="' . $tvshow->f_name . ' - ' . $this->f_name . '">' . $this->f_name . '</a>';

        if ($details) {
            $this->_get_extra_info();
        }

        return true;
    }

    public function get_keywords()
    {
        $keywords           = array();
        $keywords['tvshow'] = array('important' => true,
            'label' => T_('TV Show'),
            'value' => $this->f_tvshow);
        $keywords['tvshow_season'] = array('important' => false,
            'label' => T_('Season'),
            'value' => $this->season_number);
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
        return array('object_type' => 'tvshow', 'object_id' => $this->tvshow);
    }

    public function get_childrens()
    {
        return array('tvshow_episode' => $this->get_episodes());
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
        // No season description for now, always return tvshow description
        $tvshow = new TVShow($this->tvshow);
        return $tvshow->get_description();
    }

    public function display_art($thumb = 2, $force = false)
    {
        $id   = null;
        $type = null;

        if (Art::has_db($this->id, 'tvshow_season')) {
            $id   = $this->id;
            $type = 'tvshow_season';
        } else {
            if (Art::has_db($this->tvshow, 'tvshow') || $force) {
                $id   = $this->tvshow;
                $type = 'tvshow';
            }
        }

        if ($id !== null && $type !== null) {
            Art::display($type, $id, $this->get_fullname(), $thumb, $this->link);
        }
    }

    /**
     * check
     *
     * Checks for an existing tv show season; if none exists, insert one.
     */
    public static function check($tvshow, $season_number, $readonly = false)
    {
        $name = $tvshow . '_' . $season_number;
        // null because we don't have any unique id like mbid for now
        if (isset(self::$_mapcache[$name]['null'])) {
            return self::$_mapcache[$name]['null'];
        }

        $id     = 0;
        $exists = false;

        if (!$exists) {
            $sql        = 'SELECT `id` FROM `tvshow_season` WHERE `tvshow` = ? AND `season_number` = ?';
            $db_results = Dba::read($sql, array($tvshow, $season_number));

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

        $sql = 'INSERT INTO `tvshow_season` (`tvshow`, `season_number`) ' .
            'VALUES(?, ?)';

        $db_results = Dba::write($sql, array($tvshow, $season_number));
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
        $sql = 'UPDATE `tvshow_season` SET `season_number` = ?, `tvshow` = ? WHERE `id` = ?';
        Dba::write($sql, array($data['season_number'], $data['tvshow'], $this->id));

        return $this->id;
    } // update

    public function remove_from_disk()
    {
        $deleted   = true;
        $video_ids = $this->get_episodes();
        foreach ($video_ids as $id) {
            $video   = Video::create_from_id($id);
            $deleted = $video->remove_from_disk();
            if (!$deleted) {
                debug_event('tvshow_season', 'Error when deleting the video `' . $id . '`.', 1);
                break;
            }
        }

        if ($deleted) {
            $sql     = "DELETE FROM `tvshow_season` WHERE `id` = ?";
            $deleted = Dba::write($sql, array($this->id));
            if ($deleted) {
                Art::gc('tvshow_season', $this->id);
                Userflag::gc('tvshow_season', $this->id);
                Rating::gc('tvshow_season', $this->id);
                Shoutbox::gc('tvshow_season', $this->id);
                Useractivity::gc('tvshow_season', $this->id);
            }
        }

        return $deleted;
    }

    public static function update_tvshow($tvshow_id, $season_id)
    {
        $sql = "UPDATE `tvshow_season` SET `tvshow` = ? WHERE `id` = ?";
        return Dba::write($sql, array($tvshow_id, $season_id));
    }
} // end of tvshow_season class
