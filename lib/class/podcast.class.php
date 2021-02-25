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

class Podcast extends database_object implements library_item
{
    /* Variables from DB */
    public $id;
    public $catalog;
    public $feed;
    public $title;
    public $website;
    public $description;
    public $language;
    public $copyright;
    public $generator;
    public $lastbuilddate;
    public $lastsync;

    public $episodes;
    public $f_title;
    public $f_website;
    public $f_description;
    public $f_language;
    public $f_copyright;
    public $f_generator;
    public $f_lastbuilddate;
    public $f_lastsync;
    public $link;
    public $f_link;

    /**
     * Podcast
     * Takes the ID of the podcast and pulls the info from the db
     * @param integer $podcast_id
     */
    public function __construct($podcast_id = 0)
    {
        /* If they failed to pass in an id, just run for it */
        if (!$podcast_id) {
            return false;
        }

        /* Get the information from the db */
        $info = $this->get_info($podcast_id);

        foreach ($info as $key => $value) {
            $this->$key = $value;
        } // foreach info

        return true;
    } // constructor

    /**
     * garbage_collection
     *
     * This cleans out unused podcasts
     */
    public static function garbage_collection()
    {
    }

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return integer[]
     */
    public function get_catalogs()
    {
        return array($this->catalog);
    }

    /**
     * get_episodes
     * gets all episodes for this podcast
     * @param string $state_filter
     * @return array
     */
    public function get_episodes($state_filter = '')
    {
        $params = array();
        $sql    = "SELECT `podcast_episode`.`id` FROM `podcast_episode` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `podcast` ON `podcast`.`id` = `podcast_episode`.`podcast` ";
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `podcast`.`catalog` ";
        }
        $sql .= "WHERE `podcast_episode`.`podcast`='" . Dba::escape($this->id) . "' ";
        if (!empty($state_filter)) {
            $sql .= "AND `podcast_episode`.`state` = ? ";
            $params[] = $state_filter;
        }
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND `catalog`.`enabled` = '1' ";
        }
        $sql .= "ORDER BY `podcast_episode`.`pubdate` DESC";
        $db_results = Dba::read($sql, $params);

        $results = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    } // get_episodes

    /**
     * _get_extra info
     * This returns the extra information for the podcast, this means totals etc
     * @return array
     */
    private function _get_extra_info()
    {
        // Try to find it in the cache and save ourselves the trouble
        if (parent::is_cached('podcast_extra', $this->id)) {
            $row = parent::get_from_cache('podcast_extra', $this->id);
        } else {
            $sql = "SELECT COUNT(`podcast_episode`.`id`) AS `episode_count` FROM `podcast_episode` " .
                "WHERE `podcast_episode`.`podcast` = ?";
            $db_results = Dba::read($sql, array($this->id));
            $row        = Dba::fetch_assoc($db_results);

            parent::add_to_cache('podcast_extra', $this->id, $row);
        }

        /* Set Object Vars */
        $this->episodes   = $row['episode_count'];

        return $row;
    } // _get_extra_info

    /**
     * format
     * this function takes the object and reformats some values
     * @param boolean $details
     * @return boolean
     */
    public function format($details = true)
    {
        $this->f_title         = scrub_out($this->title);
        $this->f_description   = scrub_out($this->description);
        $this->f_language      = scrub_out($this->language);
        $this->f_copyright     = scrub_out($this->copyright);
        $this->f_generator     = scrub_out($this->generator);
        $this->f_website       = scrub_out($this->website);
        $time_format           = AmpConfig::get('custom_datetime') ? (string) AmpConfig::get('custom_datetime') : 'm/d/Y H:i';
        $this->f_lastbuilddate = get_datetime($time_format, (int) $this->lastbuilddate);
        $this->f_lastsync      = get_datetime($time_format, (int) $this->lastsync);
        $this->link            = AmpConfig::get('web_path') . '/podcast.php?action=show&podcast=' . $this->id;
        $this->f_link          = '<a href="' . $this->link . '" title="' . $this->f_title . '">' . $this->f_title . '</a>';

        if ($details) {
            $this->_get_extra_info();
        }

        return true;
    }

    /**
     * get_keywords
     * @return array
     */
    public function get_keywords()
    {
        $keywords            = array();
        $keywords['podcast'] = array('important' => true,
            'label' => T_('Podcast'),
            'value' => $this->f_title);

        return $keywords;
    }

    /**
     * get_fullname
     *
     * @return string
     */
    public function get_fullname()
    {
        return $this->f_title;
    }

    /**
     * @return null
     */
    public function get_parent()
    {
        return null;
    }

    /**
     * @return array
     */
    public function get_childrens()
    {
        return array('podcast_episode' => $this->get_episodes());
    }

    /**
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        debug_event(self::class, 'search_childrens ' . $name, 5);

        return array();
    }

    /**
     * @param string $filter_type
     * @return array|mixed
     */
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if ($filter_type === null || $filter_type == 'podcast_episode') {
            $episodes = $this->get_episodes('completed');
            foreach ($episodes as $episode_id) {
                $medias[] = array(
                    'object_type' => 'podcast_episode',
                    'object_id' => $episode_id
                );
            }
        }

        return $medias;
    }

    /**
     * @return mixed|null
     */
    public function get_user_owner()
    {
        return null;
    }

    /**
     * @return string
     */
    public function get_default_art_kind()
    {
        return 'default';
    }

    /**
     * get_description
     * @return string
     */
    public function get_description()
    {
        return $this->f_description;
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        if (Art::has_db($this->id, 'podcast') || $force) {
            Art::display('podcast', $this->id, $this->get_fullname(), $thumb, $this->link);
        }
    }

    /**
     * update
     * This takes a key'd array of data and updates the current podcast
     * @param array $data
     * @return mixed
     */
    public function update(array $data)
    {
        $feed           = isset($data['feed']) ? $data['feed'] : $this->feed;
        $title          = isset($data['title']) ? scrub_in($data['title']) : $this->title;
        $website        = isset($data['website']) ? scrub_in($data['website']) : $this->website;
        $description    = isset($data['description']) ? scrub_in($data['description']) : $this->description;
        $generator      = isset($data['generator']) ? scrub_in($data['generator']) : $this->generator;
        $copyright      = isset($data['copyright']) ? scrub_in($data['copyright']) : $this->copyright;

        if (strpos($feed, "http://") !== 0 && strpos($feed, "https://") !== 0) {
            debug_event(self::class, 'Podcast update canceled, bad feed url.', 1);

            return $this->id;
        }

        $sql = 'UPDATE `podcast` SET `feed` = ?, `title` = ?, `website` = ?, `description` = ?, `generator` = ?, `copyright` = ? WHERE `id` = ?';
        Dba::write($sql, array($feed, $title, $website, $description, $generator, $copyright, $this->id));

        $this->feed        = $feed;
        $this->title       = $title;
        $this->website     = $website;
        $this->description = $description;
        $this->generator   = $generator;
        $this->copyright   = $copyright;

        return $this->id;
    }

    /**
     * create
     * @param array $data
     * @param boolean $return_id
     * @return boolean|integer
     */
    public static function create(array $data, $return_id = false)
    {
        $feed = (string) $data['feed'];
        // Feed must be http/https
        if (strpos($feed, "http://") !== 0 && strpos($feed, "https://") !== 0) {
            AmpError::add('feed', T_('Feed URL is invalid'));
        }

        $catalog_id = (int) ($data['catalog']);
        if ($catalog_id < 1) {
            AmpError::add('catalog', T_('Target Catalog is required'));
        } else {
            $catalog = Catalog::create_from_id($catalog_id);
            if ($catalog->gather_types !== "podcast") {
                AmpError::add('catalog', T_('Wrong target Catalog type'));
            }
        }

        if (AmpError::occurred()) {
            return false;
        }

        $title         = T_('Unknown');
        $website       = null;
        $description   = null;
        $language      = null;
        $copyright     = null;
        $generator     = null;
        $lastbuilddate = 0;
        $episodes      = false;
        $arturl        = '';

        // don't allow duplicate podcasts
        $sql        = "SELECT `id` FROM `podcast` WHERE `feed`= '" . Dba::escape($feed) . "'";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results, false)) {
            if ((int) $row['id'] > 0) {
                return (int) $row['id'];
            }
        }

        $xmlstr = file_get_contents($feed, false, stream_context_create(Core::requests_options()));
        if ($xmlstr === false) {
            AmpError::add('feed', T_('Can not access the feed'));
        } else {
            $xml = simplexml_load_string($xmlstr);
            if ($xml === false) {
                AmpError::add('feed', T_('Can not read the feed'));
            } else {
                $title            = html_entity_decode((string) $xml->channel->title);
                $website          = (string) $xml->channel->link;
                $description      = html_entity_decode((string) $xml->channel->description);
                $language         = (string) $xml->channel->language;
                $copyright        = html_entity_decode((string) $xml->channel->copyright);
                $generator        = html_entity_decode((string) $xml->channel->generator);
                $lastbuilddatestr = (string) $xml->channel->lastBuildDate;
                if ($lastbuilddatestr) {
                    $lastbuilddate = strtotime($lastbuilddatestr);
                }

                if ($xml->channel->image) {
                    $arturl = (string) $xml->channel->image->url;
                }

                $episodes = $xml->channel->item;
            }
        }

        if (AmpError::occurred()) {
            return false;
        }

        $sql        = "INSERT INTO `podcast` (`feed`, `catalog`, `title`, `website`, `description`, `language`, `copyright`, `generator`, `lastbuilddate`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $db_results = Dba::write($sql, array($feed, $catalog_id, $title, $website, $description, $language, $copyright, $generator, $lastbuilddate));
        if ($db_results) {
            $podcast_id = (int) Dba::insert_id();
            $podcast    = new Podcast($podcast_id);
            $dirpath    = $podcast->get_root_path();
            if (!is_dir($dirpath)) {
                if (mkdir($dirpath) === false) {
                    debug_event(self::class, 'Cannot create directory ' . $dirpath, 1);
                }
            }
            if (!empty($arturl)) {
                $art = new Art((int) $podcast_id, 'podcast');
                $art->insert_url($arturl);
            }
            if ($episodes) {
                $podcast->add_episodes($episodes);
            }
            if ($return_id) {
                return (int) $podcast_id;
            }

            return true;
        }

        return false;
    }

    /**
     * add_episodes
     * @param SimpleXMLElement $episodes
     * @param integer $afterdate
     * @param boolean $gather
     */
    public function add_episodes($episodes, $afterdate = 0, $gather = false)
    {
        foreach ($episodes as $episode) {
            $this->add_episode($episode, $afterdate);
        }

        // Select episodes to download
        $dlnb = (int) AmpConfig::get('podcast_new_download');
        if ($dlnb <> 0) {
            $sql = "SELECT `podcast_episode`.`id` FROM `podcast_episode` INNER JOIN `podcast` ON `podcast`.`id` = `podcast_episode`.`podcast` " .
                    "WHERE `podcast`.`id` = ? AND `podcast_episode`.`addition_time` > `podcast`.`lastsync` " .
                    "ORDER BY `podcast_episode`.`pubdate` DESC";
            if ($dlnb > 0) {
                $sql .= " LIMIT " . (string) $dlnb;
            }
            $db_results = Dba::read($sql, array($this->id));
            while ($row = Dba::fetch_row($db_results)) {
                $episode = new Podcast_Episode($row[0]);
                $episode->change_state('pending');
                if ($gather) {
                    $episode->gather();
                }
            }
        }
        // Remove items outside limit
        $keepnb = AmpConfig::get('podcast_keep');
        if ($keepnb > 0) {
            $sql = "SELECT `podcast_episode`.`id` FROM `podcast_episode` WHERE `podcast_episode`.`podcast` = ? " .
                    "ORDER BY `podcast_episode`.`pubdate` DESC LIMIT " . $keepnb . ",18446744073709551615";
            $db_results = Dba::read($sql, array($this->id));
            while ($row = Dba::fetch_row($db_results)) {
                $episode = new Podcast_Episode($row[0]);
                $episode->remove();
            }
        }
        $this->update_lastsync(time());
    }

    /**
     * add_episode
     * @param SimpleXMLElement $episode
     * @param integer $afterdate
     * @return PDOStatement|boolean
     */
    private function add_episode(SimpleXMLElement $episode, $afterdate = 0)
    {
        debug_event(self::class, 'Adding new episode to podcast ' . $this->id . '...', 4);

        $title       = html_entity_decode((string) $episode->title);
        $website     = (string) $episode->link;
        $guid        = (string) $episode->guid;
        $description = html_entity_decode((string) $episode->description);
        $author      = html_entity_decode((string) $episode->author);
        $category    = html_entity_decode((string) $episode->category);
        $source      = null;
        $time        = 0;
        if ($episode->enclosure) {
            $source = $episode->enclosure['url'];
        }
        $itunes   = $episode->children('itunes', true);
        $duration = (string) $itunes->duration;
        // time is missing hour e.g. "15:23"
        if (preg_grep("/^[0-9][0-9]\:[0-9][0-9]$/", array($duration))) {
            $duration = '00:' . $duration;
        }
        // process a time string "03:23:01"
        $ptime = (preg_grep("/[0-9][0-9]\:[0-9][0-9]\:[0-9][0-9]/", array($duration)))
            ? date_parse((string)$duration)
            : $duration;
        // process "HH:MM:SS" time OR fall back to a seconds duration string e.g "24325"
        $time = (is_array($ptime))
            ? (int) $ptime['hour'] * 3600 + (int) $ptime['minute'] * 60 + (int) $ptime['second']
            : (int) $ptime;

        $pubdate    = 0;
        $pubdatestr = (string) $episode->pubDate;
        if ($pubdatestr) {
            $pubdate = strtotime($pubdatestr);
        }
        if ($pubdate < 1) {
            debug_event(self::class, 'Invalid episode publication date, skipped', 3);

            return false;
        }

        if ($pubdate > $afterdate) {
            $sql = "INSERT INTO `podcast_episode` (`title`, `guid`, `podcast`, `state`, `source`, `website`, `description`, `author`, `category`, `time`, `pubdate`, `addition_time`) " .
                    "VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?)";

            return Dba::write($sql, array($title, $guid, $this->id, $source, $website, $description, $author, $category, $time, $pubdate, time()));
        } else {
            debug_event(self::class, 'Episode published before ' . $afterdate . ' (' . $pubdate . '), skipped', 4);

            return true;
        }
    }

    /**
     * update_lastsync
     * @param integer $time
     * @return PDOStatement|boolean
     */
    private function update_lastsync($time)
    {
        $sql = "UPDATE `podcast` SET `lastsync` = ? WHERE `id` = ?";

        return Dba::write($sql, array($time, $this->id));
    }

    /**
     * sync_episodes
     * @param boolean $gather
     * @return PDOStatement|boolean
     */
    public function sync_episodes($gather = false)
    {
        debug_event(self::class, 'Syncing feed ' . $this->feed . ' ...', 4);

        $xmlstr = file_get_contents($this->feed, false, stream_context_create(Core::requests_options()));
        if ($xmlstr === false) {
            debug_event(self::class, 'Cannot access feed ' . $this->feed, 1);

            return false;
        }
        $xml = simplexml_load_string($xmlstr);
        if ($xml === false) {
            debug_event(self::class, 'Cannot read feed ' . $this->feed, 1);

            return false;
        }

        $this->add_episodes($xml->channel->item, $this->lastsync, $gather);

        return true;
    }

    /**
     * remove
     * @return PDOStatement|boolean
     */
    public function remove()
    {
        $episodes = $this->get_episodes();
        foreach ($episodes as $episode_id) {
            $episode = new Podcast_Episode($episode_id);
            $episode->remove();
        }

        $sql = "DELETE FROM `podcast` WHERE `id` = ?";

        return Dba::write($sql, array($this->id));
    }

    /**
     * get_root_path
     * @return string
     */
    public function get_root_path()
    {
        $catalog = Catalog::create_from_id($this->catalog);
        if (!$catalog->get_type() == 'local') {
            debug_event(self::class, 'Bad catalog type.', 1);

            return '';
        }

        $dirname = $this->title;

        // create path if it doesn't exist
        if (!is_dir($catalog->path . DIRECTORY_SEPARATOR . $dirname)) {
            Catalog::create_catalog_path($catalog->path . DIRECTORY_SEPARATOR . $dirname);
        }

        return $catalog->path . DIRECTORY_SEPARATOR . $dirname;
    }
} // end podcast.class
