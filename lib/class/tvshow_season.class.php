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
     * This cleans out unused tv shows seasons
     */
    public static function garbage_collection()
    {
        $sql = "DELETE FROM `tvshow_season` USING `tvshow_season` LEFT JOIN `tvshow_episode` ON `tvshow_episode`.`season` = `tvshow_season`.`id` " .
            "WHERE `tvshow_episode`.`id` IS NULL";
        Dba::write($sql);
    }

    /**
     * get_songs
     * gets all episodes for this tv show season
     * @return array
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
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    } // get_episodes

    /**
     * _get_extra info
     * This returns the extra information for the tv show season, this means totals etc
     * @return array
     */
    private function _get_extra_info()
    {
        // Try to find it in the cache and save ourselves the trouble
        if (parent::is_cached('tvshow_extra', $this->id)) {
            $row = parent::get_from_cache('tvshow_extra', $this->id);
        } else {
            $sql = "SELECT COUNT(`tvshow_episode`.`id`) AS `episode_count`, `video`.`catalog` as `catalog_id` FROM `tvshow_episode` " .
                "LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` " .
                "WHERE `tvshow_episode`.`season` = ?" .
                "GROUP BY `catalog_id`";

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
     * @param boolean $details
     * @return boolean
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

    /**
     * get_keywords
     * @return array|mixed
     */
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

    /**
     * @return string
     */
    public function get_fullname()
    {
        return $this->f_name;
    }

    /**
     * @return array
     */
    public function get_parent()
    {
        return array('object_type' => 'tvshow', 'object_id' => $this->tvshow);
    }

    /**
     * @return array
     */
    public function get_childrens()
    {
        return array('tvshow_episode' => $this->get_episodes());
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
     * get_medias
     * @param string $filter_type
     * @return array
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
        // No season description for now, always return tvshow description
        $tvshow = new TVShow($this->tvshow);

        return $tvshow->get_description();
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        $tvshow_id = null;
        $type      = null;

        if (Art::has_db($this->id, 'tvshow_season')) {
            $tvshow_id = $this->id;
            $type      = 'tvshow_season';
        } else {
            if (Art::has_db($this->tvshow, 'tvshow') || $force) {
                $tvshow_id = $this->tvshow;
                $type      = 'tvshow';
            }
        }

        if ($tvshow_id !== null && $type !== null) {
            Art::display($type, $tvshow_id, $this->get_fullname(), $thumb, $this->link);
        }
    }

    /**
     * check
     *
     * Checks for an existing tv show season; if none exists, insert one.
     * @param $tvshow
     * @param $season_number
     * @param boolean $readonly
     * @return string|null
     */
    public static function check($tvshow, $season_number, $readonly = false)
    {
        $name = $tvshow . '_' . $season_number;
        // null because we don't have any unique id like mbid for now
        if (isset(self::$_mapcache[$name]['null'])) {
            return self::$_mapcache[$name]['null'];
        }

        $object_id  = 0;
        $exists     = false;
        $sql        = 'SELECT `id` FROM `tvshow_season` WHERE `tvshow` = ? AND `season_number` = ?';
        $db_results = Dba::read($sql, array($tvshow, $season_number));
        $id_array   = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $key            = 'null';
            $id_array[$key] = $row['id'];
        }

        if (count($id_array)) {
            $object_id = array_shift($id_array);
            $exists    = true;
        }

        if ($exists && (int) $object_id > 0) {
            self::$_mapcache[$name]['null'] = $object_id;

            return $object_id;
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
        $object_id = Dba::insert_id();

        self::$_mapcache[$name]['null'] = $object_id;

        return $object_id;
    }

    /**
     * update
     * This takes a key'd array of data and updates the current tv show
     * @param array $data
     * @return mixed
     */
    public function update(array $data)
    {
        $sql = 'UPDATE `tvshow_season` SET `season_number` = ?, `tvshow` = ? WHERE `id` = ?';
        Dba::write($sql, array($data['season_number'], $data['tvshow'], $this->id));

        return $this->id;
    } // update

    /**
     * @return PDOStatement|boolean
     */
    public function remove()
    {
        $deleted = true;
        $videos  = $this->get_episodes();
        foreach ($videos as $video_id) {
            $video   = Video::create_from_id($video_id);
            $deleted = $video->remove();
            if (!$deleted) {
                debug_event(self::class, 'Error when deleting the video `' . $video_id . '`.', 1);
                break;
            }
        }

        if ($deleted) {
            $sql     = "DELETE FROM `tvshow_season` WHERE `id` = ?";
            $deleted = Dba::write($sql, array($this->id));
            if ($deleted) {
                Art::garbage_collection('tvshow_season', $this->id);
                Userflag::garbage_collection('tvshow_season', $this->id);
                Rating::garbage_collection('tvshow_season', $this->id);
                Shoutbox::garbage_collection('tvshow_season', $this->id);
                Useractivity::garbage_collection('tvshow_season', $this->id);
            }
        }

        return $deleted;
    }

    /**
     * @param $tvshow_id
     * @param $season_id
     * @return PDOStatement|boolean
     */
    public static function update_tvshow($tvshow_id, $season_id)
    {
        $sql = "UPDATE `tvshow_season` SET `tvshow` = ? WHERE `id` = ?";

        return Dba::write($sql, array($tvshow_id, $season_id));
    }
} // end tvshow_season.class
