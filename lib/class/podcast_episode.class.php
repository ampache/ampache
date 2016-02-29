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

class Podcast_Episode extends database_object implements media, library_item
{
    public $id;
    public $title;
    public $guid;
    public $podcast;
    public $state;
    public $file;
    public $source;
    public $size;
    public $time;
    public $website;
    public $description;
    public $author;
    public $category;
    public $pubdate;
    
    public $catalog;
    public $f_title;
    public $f_file;
    public $f_size;
    public $f_time;
    public $f_time_h;
    public $f_description;
    public $f_author;
    public $f_category;
    public $f_website;
    public $f_pubdate;
    public $f_state;
    public $link;
    public $f_link;
    public $f_podcast;
    public $f_podcast_link;
    
    /**
     * Constructor
     *
     * Podcast Episode class
     * @param int|null $id
     */
    public function __construct($id = null)
    {
        if (!$id) {
            return false;
        }

        $this->id = intval($id);

        if ($info = $this->get_info($this->id)) {
            foreach ($info as $key => $value) {
                $this->$key = $value;
            }
            if (!empty($this->file)) {
                $data       = pathinfo($this->file);
                $this->type = strtolower($data['extension']);
                $this->mime = Song::type_to_mime($this->type);
            }
        } else {
            $this->id = null;
            return false;
        }

        return true;
    } // constructor

    /**
     * gc
     *
     * Cleans up the podcast_episode table
     */
    public static function gc()
    {
        Dba::write('DELETE FROM `podcast_episode` USING `podcast_episode` LEFT JOIN `podcast` ON `podcast`.`id` = `podcast_episode`.`podcast` WHERE `podcast`.`id` IS NULL');
    }
    
    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return int[]
     */
    public function get_catalogs()
    {
        return array($this->catalog);
    }
    
    /**
     * format
     * this function takes the object and reformats some values
     */
    public function format($details = true)
    {
        $this->f_title       = scrub_out($this->title);
        $this->f_description = scrub_out($this->description);
        $this->f_category    = scrub_out($this->category);
        $this->f_author      = scrub_out($this->author);
        $this->f_website     = scrub_out($this->website);
        $this->f_pubdate     = date("m\/d\/Y - H:i", $this->pubdate);
        $this->f_state       = ucfirst($this->state);
        
        // Format the Time
        $min            = floor($this->time/60);
        $sec            = sprintf("%02d", ($this->time%60));
        $this->f_time   = $min . ":" . $sec;
        $hour           = sprintf("%02d", floor($min/60));
        $min_h          = sprintf("%02d", ($min%60));
        $this->f_time_h = $hour . ":" . $min_h . ":" . $sec;
        // Format the Size
        $this->f_size = UI::format_bytes($this->size);
        $this->f_file = $this->f_title . '.' . $this->type;
        
        $this->link   = AmpConfig::get('web_path') . '/podcast_episode.php?action=show&podcast_episode=' . $this->id;
        $this->f_link = '<a href="' . $this->link . '" title="' . $this->f_title . '">' . $this->f_title . '</a>';
        
        if ($details) {
            $podcast = new Podcast($this->podcast);
            $podcast->format();
            $this->catalog        = $podcast->catalog;
            $this->f_podcast      = $podcast->f_title;
            $this->f_podcast_link = $podcast->f_link;
            $this->f_file         = $this->f_podcast . ' - ' . $this->f_file;
        }
        
        return true;
    }
    
    public function get_keywords()
    {
        $keywords            = array();
        $keywords['podcast'] = array('important' => true,
            'label' => T_('Podcast'),
            'value' => $this->f_podcast);
        $keywords['title'] = array('important' => true,
            'label' => T_('Title'),
            'value' => $this->f_title);

        return $keywords;
    }
    
    public function get_fullname()
    {
        return $this->f_title;
    }

    public function get_parent()
    {
        return array('object_type' => 'podcast', 'object_id' => $this->podcast);
    }
    
    public function get_childrens()
    {
        return array();
    }

    public function search_childrens($name)
    {
        return array();
    }
    
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if (!$filter_type || $filter_type == 'podcast_episode') {
            $medias[] = array(
                'object_type' => 'podcast_episode',
                'object_id' => $this->id
            );
        }
        return $medias;
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
        return $this->f_description;
    }
    
    public function display_art($thumb = 2, $force = false)
    {
        $id   = null;
        $type = null;

        if (Art::has_db($this->id, 'podcast_episode')) {
            $id   = $this->id;
            $type = 'podcast_episode';
        } else {
            if (Art::has_db($this->podcast, 'podcast') || $force) {
                $id   = $this->podcast;
                $type = 'podcast';
            }
        }

        if ($id !== null && $type !== null) {
            Art::display($type, $id, $this->get_fullname(), $thumb, $this->link);
        }
    }
    
    /**
     * update
     * This takes a key'd array of data and updates the current podcast episode
     */
    public function update(array $data)
    {
        $title          = isset($data['title']) ? $data['title'] : $this->title;
        $website        = isset($data['website']) ? $data['website'] : $this->website;
        $description    = isset($data['description']) ? $data['description'] : $this->description;
        $author         = isset($data['author']) ? $data['author'] : $this->author;
        $category       = isset($data['category']) ? $data['category'] : $this->category;
        
        $sql = 'UPDATE `podcast_episode` SET `title` = ?, `website` = ?, `description` = ?, `author` = ?, `category` = ? WHERE `id` = ?';
        Dba::write($sql, array($title, $website, $description, $author, $category, $this->id));

        $this->title       = $title;
        $this->website     = $website;
        $this->description = $description;
        $this->author      = $author;
        $this->category    = $category;
        
        return $this->id;
    }
    
    /**
     * set_played
     * this checks to see if the current object has been played
     * if not then it sets it to played. In any case it updates stats.
     * @param int $user
     * @param string $agent
     * @param array $location
     * @return boolean
     */
    public function set_played($user, $agent, $location)
    {
        Stats::insert('podcast', $this->podcast, $user, $agent, $location);
        Stats::insert('podcast_episode', $this->id, $user, $agent, $location);

        if ($this->played) {
            return true;
        }

        /* If it hasn't been played, set it! */
        Podcast_Episode::update_played(true, $this->id);

        return true;
    } // set_played

    /**
     * update_played
     * sets the played flag
     * @param boolean $new_played
     * @param int $id
     */
    public static function update_played($new_played, $id)
    {
        self::_update_item('played', ($new_played ? 1 : 0),$id,'25');
    } // update_played

    /**
     * _update_item
     * This is a private function that should only be called from within the podcast episode class.
     * It takes a field, value video id and level. first and foremost it checks the level
     * against $GLOBALS['user'] to make sure they are allowed to update this record
     * it then updates it and sets $this->{$field} to the new value
     * @param string $field
     * @param mixed $value
     * @param int $song_id
     * @param int $level
     * @return boolean
     */
    private static function _update_item($field, $value, $song_id, $level)
    {
        /* Check them Rights! */
        if (!Access::check('interface',$level)) {
            return false;
        }

        /* Can't update to blank */
        if (!strlen(trim($value))) {
            return false;
        }

        $sql = "UPDATE `podcast_episode` SET `$field` = ? WHERE `id` = ?";
        Dba::write($sql, array($value, $song_id));

        return true;
    } // _update_item

    /**
     * Get stream name.
     * @return string
     */
    public function get_stream_name()
    {
        return $this->f_podcast . " - " . $this->f_title;
    }
    
    /**
     * Get transcode settings.
     * @param string $target
     * @param array $options
     * @return array|boolean
     */
    public function get_transcode_settings($target = null, $player = null, $options=array())
    {
        return Song::get_transcode_settings_for_media($this->type, $target, $player, 'song', $options);
    }
    
    /**
     * play_url
     * This function takes all the song information and correctly formats a
     * a stream URL taking into account the downsmapling mojo and everything
     * else, this is the true function
     * @param int $oid
     * @param string $additional_params
     * @param boolean $local
     * @return string
     */
    public static function play_url($oid, $additional_params='', $player=null, $local=false)
    {
        return Song::generic_play_url('podcast_episode', $oid, $additional_params, $player, $local);
    }
    
    /**
     * Get stream types.
     * @return array
     */
    public function get_stream_types($player = null)
    {
        return Song::get_stream_types_for_type($this->type, $player);
    }
    
    public function remove()
    {
        debug_event('podcast_episode', 'Removing podcast episode ' . $this->id, 5);
        
        if (AmpConfig::get('delete_from_disk') && !empty($this->file)) {
            if (!unlink($this->file)) {
                debug_event('podcast_episode', 'Cannot delete file ' . $this->file, 3);
            }
        }
        
        $sql = "DELETE FROM `podcast_episode` WHERE `id` = ?";
        return Dba::write($sql, array($this->id));
    }
    
    public function change_state($state)
    {
        $sql = "UPDATE `podcast_episode` SET `state` = ? WHERE `id` = ?";
        return Dba::write($sql, array($state, $this->id));
    }
    
    public function gather()
    {
        if (!empty($this->source)) {
            $podcast = new Podcast($this->podcast);
            $file    = $podcast->get_root_path();
            if (!empty($file)) {
                $pinfo = pathinfo($this->source);
                $file .= DIRECTORY_SEPARATOR . $this->id . '-' . strtok($pinfo['basename'], '?');
                debug_event('podcast_episode', 'Downloading ' . $this->source . ' to ' . $file . ' ...', 5);
                if (file_put_contents($file, fopen($this->source, 'r')) !== false) {
                    debug_event('podcast_episode', 'Download completed.', 5);
                    $this->file = $file;
                    
                    $vainfo = new vainfo($this->file);
                    $vainfo->get_info();
                    $key   = vainfo::get_tag_type($vainfo->tags);
                    $infos = vainfo::clean_tag_info($vainfo->tags, $key, $file);
                    // No time information, get it from file
                    if ($this->time <= 0) {
                        $this->time = $infos['time'];
                    }
                    $this->size = $infos['size'];

                    $sql = "UPDATE `podcast_episode` SET `file` = ?, `size` = ?, `time` = ?, `state` = 'completed' WHERE `id` = ?";
                    Dba::write($sql, array($this->file, $this->size, $this->time, $this->id));
                } else {
                    debug_event('podcast_episode', 'Error when downloading podcast episode.', 1);
                }
            }
        } else {
            debug_event('podcast_episode', 'Cannot download podcast episode ' . $this->id . ', empty source.', 3);
        }
    }
    
    /**
     * type_to_mime
     *
     * Returns the mime type for the specified file extension/type
     * @param string $type
     * @return string
     */
    public static function type_to_mime($type)
    {
        return Song::type_to_mime($type);
    }
}
