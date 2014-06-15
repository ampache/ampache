<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

class TVShow_Season extends database_object
{
    /* Variables from DB */
    public $id;
    public $season_number;
    public $tvshow;

    public $episodes;
    public $f_name;
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
        if (!$id) { return false; }

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
        if (parent::is_cached('tvshow_extra', $this->id) ) {
            $row = parent::get_from_cache('tvshow_extra', $this->id);
        } else {
            $sql = "SELECT COUNT(`tvshow_episode`.`id`) AS `episode_count` FROM `tvshow_episode` " .
                "WHERE `tvshow_episode`.`season` = ?";

            $db_results = Dba::read($sql, array($this->id));
            $row = Dba::fetch_assoc($db_results);
            parent::add_to_cache('tvshow_extra',$this->id,$row);
        }

        /* Set Object Vars */
        $this->episodes = $row['episode_count'];

        return $row;

    } // _get_extra_info
    
    /**
     * format
     * this function takes the object and reformats some values
     */
    public function format()
    {
        $this->f_name = T_('Season') . ' ' . $this->season_number;
        
        $tvshow = new TVShow($this->tvshow);
        $tvshow->format();
        $this->f_tvshow_link = $tvshow->f_link;
        
        $this->link = AmpConfig::get('web_path') . '/tvshow_seasons.php?action=show&season=' . $this->id;
        $this->f_link = '<a href="' . $this->link . '" title="' . $tvshow->f_name . ' - ' . $this->f_name . '">' . $this->f_name . '</a>';
        
        $this->_get_extra_info($this->catalog_id);
        
        return true;
    }

    /**
     * update
     * This takes a key'd array of data and updates the current tv show
     */
    public function update($data)
    {
        $sql = 'UPDATE `tvshow_season` SET `season_number` = ? WHERE `id` = ?';
        return Dba::write($sql, array($data['season_number'], $this->id));
    } // update
    
    public static function update_tvshow($tvshow_id, $season_id)
    {
        $sql = "UPDATE `tvshow_season` SET `tvshow` = ? WHERE `id` = ?";
        return Dba::write($sql, array($tvshow_id, $season_id));
    }

} // end of tvshow_season class
