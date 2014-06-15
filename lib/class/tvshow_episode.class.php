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

class TVShow_Episode extends Video
{
    public $original_name;
    public $season;
    public $episode_number;
    public $description;

    public $f_link;
    public $f_season;
    public $f_tvshow;

    /**
     * Constructor
     * This pulls the tv show episode information from the database and returns
     * a constructed object
     */
    public function __construct($id)
    {
        parent::__construct($id);
        
        $info = $this->get_info($id);
        foreach ($info as $key=>$value) {
            $this->$key = $value;
        }
        
        return true;

    } // Constructor

    /**
     * create
     * This takes a key'd array of data as input and inserts a new tv show episode entry, it returns the auto_inc id
     */
    public static function create($data)
    {
        $sql = "INSERT INTO `tvshow_episode` (`id`, `season`,`episode_number`,`description`) " .
            "VALUES (?, ?, ?, ?)";
        Dba::write($sql, array($data['id'], $data['season'], $data['episode_number'], $data['description']));
        $insert_id = Dba::insert_id();

        return $insert_id;

    } // create

    /**
     * update
     * This takes a key'd array of data as input and updates a tv show episode entry
     */
    public static function update($data)
    {
        $sql = "UPDATE `tvshow_episode` SET `season` = ?, `episode_number` = ?, `description` = ? WHERE `id` = ?";
        Dba::write($sql, array($data['season'], $data['episode_number'], $data['description'], $data['id']));

        return true;

    } // create

    /**
     * format
     * this function takes the object and reformats some values
     */
    public function format()
    {
        parent::format();
        
        $season = new TVShow_Season($this->season);
        $season->format();
        $this->f_season = $season->f_link;
        $this->f_tvshow = $season->f_tvshow;
        
        return true;

    } //format

} // License class
