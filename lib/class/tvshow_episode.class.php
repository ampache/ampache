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

class TVShow_Episode extends Video
{
    public $original_name;
    public $season;
    public $episode_number;
    public $summary;

    public $f_link;
    public $f_season;
    public $f_season_link;
    public $f_tvshow;
    public $f_tvshow_link;

    /**
     * Constructor
     * This pulls the tv show episode information from the database and returns
     * a constructed object
     * @param $episode_id
     */
    public function __construct($episode_id)
    {
        parent::__construct($episode_id);

        $info = $this->get_info($episode_id);
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        return true;
    }

    /**
     * garbage_collection
     *
     * This cleans out unused tv shows episodes
     */
    public static function garbage_collection()
    {
        $sql = "DELETE FROM `tvshow_episode` USING `tvshow_episode` LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` WHERE `video`.`id` IS NULL";
        Dba::write($sql);
    }

    /**
     * insert
     * Insert a new tv show episode and related entities.
     * @param array $data
     * @param array $gtypes
     * @param array $options
     * @return integer
     */
    public static function insert(array $data, $gtypes = array(), $options = array())
    {
        if (empty($data['tvshow'])) {
            $data['tvshow'] = T_('Unknown');
        }
        $tags = $data['genre'];

        $tvshow = TVShow::check($data['tvshow'], $data['year'], $data['tvshow_summary']);
        if ($options['gather_art'] && $tvshow && $data['tvshow_art'] && !Art::has_db((int) $tvshow, 'tvshow')) {
            $art = new Art((int) $tvshow, 'tvshow');
            $art->insert_url($data['tvshow_art']);
        }
        $tvshow_season = TVShow_Season::check($tvshow, $data['tvshow_season']);
        if ($options['gather_art'] && $tvshow_season && $data['tvshow_season_art'] && !Art::has_db($tvshow_season, 'tvshow_season')) {
            $art = new Art($tvshow_season, 'tvshow_season');
            $art->insert_url($data['tvshow_season_art']);
        }

        if (is_array($tags)) {
            foreach ($tags as $tag) {
                $tag = trim((string) $tag);
                if (!empty($tag)) {
                    Tag::add('tvshow_season', (int) $tvshow_season, $tag, false);
                    Tag::add('tvshow', (int) $tvshow, $tag, false);
                }
            }
        }

        $sdata = $data;
        // Replace relation name with db ids
        $sdata['tvshow']        = $tvshow;
        $sdata['tvshow_season'] = $tvshow_season;

        return self::create($sdata);
    }

    /**
     * create
     * This takes a key'd array of data as input and inserts a new tv show episode entry, it returns the record id
     * @param array $data
     * @return integer
     */
    public static function create($data)
    {
        $sql = "INSERT INTO `tvshow_episode` (`id`, `original_name`, `season`, `episode_number`, `summary`) " .
            "VALUES (?, ?, ?, ?, ?)";
        Dba::write($sql, array($data['id'], $data['original_name'], $data['tvshow_season'], $data['tvshow_episode'], $data['summary']));

        return $data['id'];
    }

    /**
     * update
     * This takes a key'd array of data as input and updates a tv show episode entry
     * @param array $data
     * @return integer
     */
    public function update(array $data)
    {
        parent::update($data);

        $original_name  = isset($data['original_name']) ? $data['original_name'] : $this->original_name;
        $tvshow_season  = isset($data['tvshow_season']) ? $data['tvshow_season'] : $this->season;
        $tvshow_episode = isset($data['tvshow_episode']) ? $data['tvshow_episode'] : $this->episode_number;
        $summary        = isset($data['summary']) ? $data['summary'] : $this->summary;

        $sql = "UPDATE `tvshow_episode` SET `original_name` = ?, `season` = ?, `episode_number` = ?, `summary` = ? WHERE `id` = ?";
        Dba::write($sql, array($original_name, $tvshow_season, $tvshow_episode, $summary, $this->id));

        $this->original_name  = $original_name;
        $this->season         = $tvshow_season;
        $this->episode_number = $tvshow_episode;
        $this->summary        = $summary;

        return $this->id;
    }

    /**
     * format
     * this function takes the object and reformats some values
     * @param boolean $details
     * @return boolean
     */
    public function format($details = true)
    {
        parent::format($details);

        $season = new TVShow_Season($this->season);
        $season->format($details);

        $this->f_title       = ($this->original_name ?: $this->f_title);
        $this->f_link        = '<a href="' . $this->link . '">' . $this->f_title . '</a>';
        $this->f_season      = $season->f_name;
        $this->f_season_link = $season->f_link;
        $this->f_tvshow      = $season->f_tvshow;
        $this->f_tvshow_link = $season->f_tvshow_link;

        $this->f_file = $this->f_tvshow;
        if ($this->episode_number) {
            $this->f_file .= ' - S' . sprintf('%02d', $season->season_number) . 'E' . sprintf('%02d', $this->episode_number);
        }
        $this->f_file .= ' - ' . $this->f_title;
        $this->f_full_title = $this->f_file;

        return true;
    }

    /**
     * get_keywords
     * @return array
     */
    public function get_keywords()
    {
        $keywords           = parent::get_keywords();
        $keywords['tvshow'] = array('important' => true,
            'label' => T_('TV Show'),
            'value' => $this->f_tvshow);
        $keywords['tvshow_season'] = array('important' => false,
            'label' => T_('Season'),
            'value' => $this->f_season);
        if ($this->episode_number) {
            $keywords['tvshow_episode'] = array('important' => false,
                'label' => T_('Episode'),
                'value' => $this->episode_number);
        }
        $keywords['type'] = array('important' => false,
            'label' => null,
            'value' => 'tvshow'
        );

        return $keywords;
    }

    /**
     * get_parent
     * @return array
     */
    public function get_parent()
    {
        return array('object_type' => 'tvshow_season', 'object_id' => $this->season);
    }

    /**
     * get_release_item_art
     * @return array
     */
    public function get_release_item_art()
    {
        return array('object_type' => 'tvshow_season',
            'object_id' => $this->season
        );
    }

    /**
     * @return string
     */
    public function get_description()
    {
        if (!empty($this->summary)) {
            return $this->summary;
        }

        $season = new TVShow_Season($this->season);

        return $season->get_description();
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        $episode_id = null;
        $type       = null;

        if (Art::has_db($this->id, 'video')) {
            $episode_id = $this->id;
            $type       = 'video';
        } else {
            if (Art::has_db($this->season, 'tvshow_season')) {
                $episode_id = $this->season;
                $type       = 'tvshow_season';
            } else {
                $season = new TVShow_Season($this->season);
                if (Art::has_db($season->tvshow, 'tvshow') || $force) {
                    $episode_id = $season->tvshow;
                    $type       = 'tvshow';
                }
            }
        }

        if ($episode_id !== null && $type !== null) {
            Art::display($type, $episode_id, $this->get_fullname(), $thumb, $this->link);
        }
    }

    /**
     * Remove the video from disk.
     */
    public function remove()
    {
        $deleted = parent::remove();
        if ($deleted) {
            $sql     = "DELETE FROM `tvshow_episode` WHERE `id` = ?";
            $deleted = Dba::write($sql, array($this->id));
        }

        return $deleted;
    }
} // end tvshow_episode.class
