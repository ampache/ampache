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

class Movie extends Video
{
    public $original_name;
    public $description;
    public $year;
    public $video;

    public $f_link;
    public $f_date;

    /**
     * Constructor
     * This pulls the movie information from the database and returns
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
     * This takes a key'd array of data as input and inserts a new movie entry, it returns the auto_inc id
     */
    public static function create($data)
    {
        $sql = "INSERT INTO `movie` (`id`, `name`,`original_name`,`description`, `year`, `release_date`) " .
            "VALUES (?, ?, ?, ?, ?, ?)";
        Dba::write($sql, array($data['id'], $data['name'], $data['original_name'], $data['description'], $data['year'], $data['release_date']));

        return $insert_id;

    } // create

    /**
     * update
     * This takes a key'd array of data as input and updates a movie entry
     */
    public static function update($data)
    {
        $sql = "UPDATE `movie` SET `name` = ?, `original_name` = ?, `description` = ?, `year` = ?, `release_date` = ? WHERE `id` = ?";
        Dba::write($sql, array($data['name'], $data['original_name'], $data['description'], $data['year'], $data['release_date'], $data['id']));

        return true;

    } // create

    /**
     * format
     * this function takes the object and reformats some values
     */

    public function format()
    {
        parent::format();
        return true;

    } //format

} // License class
