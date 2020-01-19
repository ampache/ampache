<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
    public function __construct($object_id)
    {
        parent::__construct($object_id);

        $info = $this->get_info($object_id);
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        return true;
    } // Constructor

    /**
     * garbage_collection
     *
     * This cleans out unused personal videos
     */
    public static function garbage_collection()
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
        $sql = "INSERT INTO `personal_video` (`id`, `location`, `summary`) " .
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

    public function format($details = true)
    {
        parent::format($details);

        $this->f_location = $this->location;

        return true;
    } //format

    /**
     * Remove the video from disk.
     */
    public function remove_from_disk()
    {
        $deleted = parent::remove_from_disk();
        if ($deleted) {
            $sql     = "DELETE FROM `personal_video` WHERE `id` = ?";
            $deleted = Dba::write($sql, array($this->id));
        }

        return $deleted;
    }
} // Personal_Video class
