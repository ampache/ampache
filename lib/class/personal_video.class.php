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

class Personal_Video extends Video
{
    public $location;
    public $summary;
    public $video;

    public $f_location;

    /**
     * Constructor
     * This pulls the personal video information from the database and returns
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
     * gc
     *
     * This cleans out unused personal videos
     */
    public static function gc()
    {
        $sql = "DELETE FROM `personal_video` USING `personal_video` LEFT JOIN `video` ON `video`.`id` = `personal_video`.`id` " .
            "WHERE `video`.`id` IS NULL";
        Dba::write($sql);
    }

    /**
     * create
     * This takes a key'd array of data as input and inserts a new personal video entry, it returns the record id
     */
    public static function insert(array $data, $gtypes = array(), $options = array())
    {
        $sql = "INSERT INTO `personal_video` (`id`,`location`,`summary`) " .
            "VALUES (?, ?, ?)";
        Dba::write($sql, array($data['id'], $data['location'], $data['summary']));

        return $data['id'];

    } // create

    /**
     * update
     * This takes a key'd array of data as input and updates a personal video entry
     */
    public function update(array $data)
    {
        parent::update($data);

        $sql = "UPDATE `personal_video` SET `location` = ?, `summary` = ? WHERE `id` = ?";
        Dba::write($sql, array($data['location'], $data['summary'], $this->id));

        return $this->id;

    } // update

    /**
     * format
     * this function takes the object and reformats some values
     */

    public function format()
    {
        parent::format();

        $this->f_location = $this->location;

        return true;

    } //format

} // Personal_Video class
