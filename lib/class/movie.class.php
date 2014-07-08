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
    public $summary;
    public $year;
    public $video;

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
     * gc
     *
     * This cleans out unused movies
     */
    public static function gc()
    {
        $sql = "DELETE FROM `movie` USING `movie` LEFT JOIN `video` ON `video`.`id` = `movie`.`id` " .
            "WHERE `video`.`id` IS NULL";
        Dba::write($sql);
    }

    /**
     * create
     * This takes a key'd array of data as input and inserts a new movie entry, it returns the record id
     */
    public static function insert($data, $gtypes = array(), $options = array())
    {
        $sql = "INSERT INTO `movie` (`id`,`original_name`,`summary`, `year`) " .
            "VALUES (?, ?, ?, ?)";
        Dba::write($sql, array($data['id'], $data['original_name'], $data['summary'], $data['year']));

        return $data['id'];

    } // create

    /**
     * update
     * This takes a key'd array of data as input and updates a movie entry
     */
    public function update($data)
    {
        parent::update($data);

        $sql = "UPDATE `movie` SET `original_name` = ?, `summary` = ?, `year` = ? WHERE `id` = ?";
        Dba::write($sql, array($data['original_name'], $data['summary'], $data['year'], $this->id));

        return $this->id;

    } // update

    /**
     * format
     * this function takes the object and reformats some values
     */

    public function format()
    {
        parent::format();

        $this->f_title = ($this->original_name ?: $this->f_title);
        $this->f_full_title = $this->f_title;
        $this->f_link = '<a href="' . $this->link . '">' . $this->f_title . '</a>';

        return true;

    } //format

    public function get_keywords()
    {
        $keywords = parent::get_keywords();
        $keywords['type'] = array('important' => false,
            'label' => null,
            'value' => 'movie'
        );

        return $keywords;
    }

    public function get_default_art_kind()
    {
        return 'default';
    }

} // Movie class
