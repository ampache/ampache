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

class Movie extends Video
{
    public $original_name;
    public $prefix;
    public $summary;
    public $year;
    public $video;

    public $f_original_name;

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
    public static function insert(array $data, $gtypes = array(), $options = array())
    {
        $trimmed = Catalog::trim_prefix(trim($data['original_name']));
        $name    = $trimmed['string'];
        $prefix  = $trimmed['prefix'];

        $sql = "INSERT INTO `movie` (`id`, `original_name`, `prefix`, `summary`, `year`) " .
            "VALUES (?, ?, ?, ?, ?)";
        Dba::write($sql, array($data['id'], $name, $prefix, $data['summary'], $data['year']));

        return $data['id'];
    } // create

    /**
     * update
     * This takes a key'd array of data as input and updates a movie entry
     */
    public function update(array $data)
    {
        parent::update($data);

        if (isset($data['original_name'])) {
            $trimmed = Catalog::trim_prefix(trim($data['original_name']));
            $name    = $trimmed['string'];
            $prefix  = $trimmed['prefix'];
        } else {
            $name   = $this->original_name;
            $prefix = $this->prefix;
        }
        $summary = isset($data['summary']) ? $data['summary'] : $this->summary;
        $year    = Catalog::normalize_year(isset($data['year']) ? $data['year'] : $this->year);

        $sql = "UPDATE `movie` SET `original_name` = ?, `prefix` = ?, `summary` = ?, `year` = ? WHERE `id` = ?";
        Dba::write($sql, array($name, $prefix, $summary, $year, $this->id));

        $this->original_name = $name;
        $this->prefix        = $prefix;
        $this->summary       = $summary;
        $this->year          = $year;

        return $this->id;
    } // update

    /**
     * format
     * this function takes the object and reformats some values
     */

    public function format($details = true)
    {
        parent::format($details);

        $this->f_original_name = trim($this->prefix . " " . $this->f_title);
        $this->f_title         = ($this->f_original_name ?: $this->f_title);
        $this->f_full_title    = $this->f_title;
        $this->f_link          = '<a href="' . $this->link . '">' . $this->f_title . '</a>';

        return true;
    } //format

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        $keywords         = parent::get_keywords();
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

    /**
     * Remove the video from disk.
     */
    public function remove_from_disk()
    {
        $deleted = parent::remove_from_disk();
        if ($deleted) {
            $sql     = "DELETE FROM `movie` WHERE `id` = ?";
            $deleted = Dba::write($sql, array($this->id));
        }

        return $deleted;
    }
} // Movie class

