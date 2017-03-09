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

class Clip extends Video
{
    public $artist;
    public $song;
    public $video;

    public $f_artist;
    public $f_song;

    /**
     * Constructor
     * This pulls the clip information from the database and returns
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
     * This cleans out unused clips
     */
    public static function gc()
    {
        $sql = "DELETE FROM `clip` USING `clip` LEFT JOIN `video` ON `video`.`id` = `clip`.`id` " .
            "WHERE `video`.`id` IS NULL";
        Dba::write($sql);
    }

    /**
     * create
     * This takes a key'd array of data as input and inserts a new clip entry, it returns the record id
     */
    public static function insert(array $data, $gtypes = array(), $options = array())
    {
        $sql = "INSERT INTO `clip` (`id`,`artist`,`song`) " .
            "VALUES (?, ?, ?)";
        Dba::write($sql, array($data['id'], $data['artist'], $data['song']));

        return $data['id'];
    } // create

    /**
     * update
     * This takes a key'd array of data as input and updates a clip entry
     */
    public function update(array $data)
    {
        $sql = "UPDATE `clip` SET `artist` = ?, `song` = ? WHERE `id` = ?";
        Dba::write($sql, array($data['artist'], $data['song'], $this->id));

        return $this->id;
    } // update

    /**
     * format
     * this function takes the object and reformats some values
     */

    public function format($details = true)
    {
        parent::format($details);

        if ($details) {
            if ($this->artist) {
                $artist = new Artist($this->artist);
                $artist->format();
                $this->f_artist = $artist->link;
            }

            if ($this->song) {
                $song = new Song($this->song);
                $song->format();
                $this->f_song = $song->f_link;
            }
        }

        return true;
    } //format

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        $keywords = parent::get_keywords();
        if ($this->artist) {
            $keywords['artist'] = array('important' => true,
                'label' => T_('Artist'),
                'value' => $this->f_artist);
        }

        return $keywords;
    }

    public function get_parent()
    {
        if ($this->artist) {
            return array('object_type' => 'artist', 'object_id' => $this->artist);
        }

        return null;
    }
} // Clip class
