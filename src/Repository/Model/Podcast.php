<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
 */

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Module\System\Dba;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use PDOStatement;
use SimpleXMLElement;

class Podcast extends database_object implements library_item
{
    protected const DB_TABLENAME = 'podcast';

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
    public $total_count;
    public $total_skip;
    public $episodes;
    public $has_art;

    public $f_name;
    public $f_website;
    public $f_description;
    public $f_language;
    public $f_copyright;
    public $f_generator;
    public $f_lastbuilddate;
    public $f_lastsync;
    public $link;
    public $f_link;
    public $f_website_link;

    /**
     * Podcast
     * Takes the ID of the podcast and pulls the info from the db
     * @param int $podcast_id
     */
    public function __construct($podcast_id = 0)
    {
        /* If they failed to pass in an id, just run for it */
        if (!$podcast_id) {
            return false;
        }

        $info = $this->get_info($podcast_id, static::DB_TABLENAME);
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        return true;
    } // constructor

    public function getId(): int
    {
        return (int)($this->id ?? 0);
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
     * get_episodes
     * gets all episodes for this podcast
     * @param string $state_filter
     * @return array
     */
    public function get_episodes($state_filter = '')
    {
        $params          = array();
        $sql             = "SELECT `podcast_episode`.`id` FROM `podcast_episode` ";
        $catalog_disable = AmpConfig::get('catalog_disable');
        if ($catalog_disable) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `podcast_episode`.`catalog` ";
        }
        $sql .= "WHERE `podcast_episode`.`podcast`='" . Dba::escape($this->id) . "' ";
        if (!empty($state_filter)) {
            $sql .= "AND `podcast_episode`.`state` = ? ";
            $params[] = $state_filter;
        }
        if ($catalog_disable) {
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
     * format
     * this function takes the object and formats some values
     *
     * @param bool $details
     */
    public function format($details = true): void
    {
        $this->f_description   = scrub_out($this->description);
        $this->f_language      = scrub_out($this->language);
        $this->f_copyright     = scrub_out($this->copyright);
        $this->f_generator     = scrub_out($this->generator);
        $this->f_website       = scrub_out($this->website);
        $this->f_lastbuilddate = date("c", (int)$this->lastbuilddate);
        $this->f_lastsync      = date("c", (int)$this->lastsync);
        $this->f_website_link  = "<a target=\"_blank\" href=\"" . $this->website . "\">" . $this->website . "</a>";
        $this->get_f_link();
    }

    /**
     * does the item have art?
     */
    public function has_art(): bool
    {
        if (!isset($this->has_art)) {
            $this->has_art = Art::has_db($this->id, 'podcast');
        }

        return $this->has_art;
    }

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        $keywords            = array();
        $keywords['podcast'] = array(
            'important' => true,
            'label' => T_('Podcast'),
            'value' => $this->get_fullname()
        );

        return $keywords;
    }

    /**
     * get_fullname
     */
    public function get_fullname(): string
    {
        if (!isset($this->f_name)) {
            $this->f_name = $this->title;
        }

        return $this->f_name;
    }

    /**
     * Get item link.
     */
    public function get_link(): string
    {
        // don't do anything if it's formatted
        if (!isset($this->link)) {
            $web_path   = AmpConfig::get('web_path');
            $this->link = $web_path . '/podcast.php?action=show&podcast=' . $this->id;
        }

        return $this->link;
    }

    /**
     * Get item f_link.
     */
    public function get_f_link(): string
    {
        // don't do anything if it's formatted
        if (!isset($this->f_link)) {
            $this->f_link = '<a href="' . $this->get_link() . '" title="' . scrub_out($this->get_fullname()) . '">' . scrub_out($this->get_fullname()) . '</a>';
        }

        return $this->f_link;
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
     * Search for direct children of an object
     * @param string $name
     * @return array
     */
    public function get_children($name)
    {
        debug_event(self::class, 'get_children ' . $name, 5);

        return array();
    }

    /**
     * @param string $filter_type
     * @return array
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
     * @return int|null
     */
    public function get_user_owner()
    {
        return null;
    }

    public function get_default_art_kind(): string
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
     * @param int $thumb
     * @param bool $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        if (Art::has_db($this->id, 'podcast') || $force) {
            Art::display('podcast', $this->id, $this->get_fullname(), $thumb, $this->get_link());
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
        $feed        = $data['feed'] ?? $this->feed;
        $title       = (isset($data['title'])) ? scrub_in($data['title']) : null;
        $website     = (isset($data['website'])) ? scrub_in($data['website']) : null;
        $description = (isset($data['description'])) ? scrub_in(Dba::check_length((string)$data['description'], 4096)) : null;
        $language    = (isset($data['language'])) ? scrub_in($data['language']) : null;
        $generator   = (isset($data['generator'])) ? scrub_in($data['generator']) : null;
        $copyright   = (isset($data['copyright'])) ? scrub_in($data['copyright']) : null;

        if (strpos($feed, "http://") !== 0 && strpos($feed, "https://") !== 0) {
            debug_event(self::class, 'Podcast update canceled, bad feed url.', 1);

            return $this->id;
        }

        $sql = 'UPDATE `podcast` SET `feed` = ?, `title` = ?, `website` = ?, `description` = ?, `language` = ?, `generator` = ?, `copyright` = ? WHERE `id` = ?';
        Dba::write($sql, array($feed, $title, $website, $description, $language, $generator, $copyright, $this->id));

        $this->feed        = $feed;
        $this->title       = $title;
        $this->website     = $website;
        $this->description = $description;
        $this->language    = $language;
        $this->generator   = $generator;
        $this->copyright   = $copyright;

        return $this->id;
    }

    /**
     * create
     * @param array $data
     * @param bool $return_id
     * @return bool|int
     */
    public static function create(array $data, $return_id = false)
    {
        $feed = (string) $data['feed'];
        // Feed must be http/https
        if (strpos($feed, "http://") !== 0 && strpos($feed, "https://") !== 0) {
            AmpError::add('feed', T_('Feed URL is invalid'));
        }

        $catalog_id = (int)($data['catalog']);
        if ($catalog_id < 1) {
            AmpError::add('catalog', T_('Target Catalog is required'));
        } else {
            $catalog = Catalog::create_from_id($catalog_id);
            if (!$catalog) {
                AmpError::add('catalog', T_('Catalog not found'));
            }
            if ($catalog && $catalog->gather_types !== "podcast") {
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
                Catalog::update_map($catalog_id, 'podcast', (int) $row['id']);

                return (int) $row['id'];
            }
        }

        $xmlstr = file_get_contents($feed, false, stream_context_create(Core::requests_options()));
        if ($xmlstr === false) {
            AmpError::add('feed', T_('Can not access the feed'));
        } else {
            $xml = simplexml_load_string($xmlstr);
            if ($xml === false) {
                // I've seems some &'s in feeds that screw up
                $xml = simplexml_load_string(str_replace('&', '&amp;', $xmlstr));
            }
            if ($xml === false) {
                AmpError::add('feed', T_('Can not read the feed'));
            } else {
                $title            = html_entity_decode((string)$xml->channel->title);
                $website          = (string)$xml->channel->link;
                $description      = html_entity_decode(Dba::check_length((string)$xml->channel->description, 4096));
                $language         = (string)$xml->channel->language;
                $copyright        = html_entity_decode((string)$xml->channel->copyright);
                $generator        = html_entity_decode((string)$xml->channel->generator);
                $lastbuilddatestr = (string)$xml->channel->lastBuildDate;
                if ($lastbuilddatestr) {
                    $lastbuilddate = strtotime($lastbuilddatestr);
                }

                if ($xml->channel->image) {
                    $arturl = (string)$xml->channel->image->url;
                }

                $episodes = $xml->channel->item;
            }
        }

        if (AmpError::occurred()) {
            return false;
        }

        $sql        = "INSERT INTO `podcast` (`feed`, `catalog`, `title`, `website`, `description`, `language`, `copyright`, `generator`, `lastbuilddate`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $db_results = Dba::write($sql, array(
            $feed,
            $catalog_id,
            $title,
            $website,
            $description,
            $language,
            $copyright,
            $generator,
            $lastbuilddate
        ));
        if ($db_results) {
            $podcast_id = (int)Dba::insert_id();
            $podcast    = new Podcast($podcast_id);
            $dirpath    = $podcast->get_root_path();
            if (!is_dir($dirpath)) {
                if (mkdir($dirpath) === false) {
                    debug_event(self::class, 'Cannot create directory ' . $dirpath, 1);
                }
            }
            if (!empty($arturl)) {
                $art = new Art((int)$podcast_id, 'podcast');
                $art->insert_url($arturl);
            }
            Catalog::update_map($catalog_id, 'podcast', (int)$podcast_id);
            Catalog::count_table('user');
            if ($episodes) {
                $podcast->add_episodes($episodes);
            }
            if ($return_id) {
                return (int)$podcast_id;
            }

            return true;
        }

        return false;
    }

    /**
     * add_episodes
     * @param SimpleXMLElement $episodes
     * @param int $lastSync
     * @param bool $gather
     */
    public function add_episodes($episodes, $lastSync = 0, $gather = false)
    {
        foreach ($episodes as $episode) {
            $this->add_episode($episode, $lastSync);
        }
        $change = 0;
        $time   = time();
        $params = array($this->id);

        // Select episodes to download
        $dlnb = (int)AmpConfig::get('podcast_new_download');
        if ($dlnb <> 0) {
            $sql = "SELECT `podcast_episode`.`id` FROM `podcast_episode` INNER JOIN `podcast` ON `podcast`.`id` = `podcast_episode`.`podcast` WHERE `podcast`.`id` = ? AND (`podcast_episode`.`addition_time` > `podcast`.`lastsync` OR `podcast_episode`.`state` = 'pending') ORDER BY `podcast_episode`.`pubdate` DESC";
            if ($dlnb > 0) {
                $sql .= " LIMIT " . (string)$dlnb;
            }
            $db_results = Dba::read($sql, $params);
            while ($row = Dba::fetch_row($db_results)) {
                $episode = new Podcast_Episode($row[0]);
                $episode->change_state('pending');
                if ($gather) {
                    $episode->gather();
                    $change++;
                }
            }
        }
        if ($change > 0) {
            // Remove items outside limit
            $keepnb = AmpConfig::get('podcast_keep');
            if ($keepnb > 0) {
                $sql        = "SELECT `podcast_episode`.`id` FROM `podcast_episode` WHERE `podcast_episode`.`podcast` = ? ORDER BY `podcast_episode`.`pubdate` DESC LIMIT " . $keepnb . ",18446744073709551615";
                $db_results = Dba::read($sql, $params);
                while ($row = Dba::fetch_row($db_results)) {
                    $episode = new Podcast_Episode($row[0]);
                    $episode->remove();
                }
            }
            // update the episode count after adding / removing episodes
            $sql = "UPDATE `podcast`, (SELECT COUNT(`podcast_episode`.`id`) AS `episodes`, `podcast` FROM `podcast_episode` WHERE `podcast_episode`.`podcast` = ? GROUP BY `podcast_episode`.`podcast`) AS `episode_count` SET `podcast`.`episodes` = `episode_count`.`episodes` WHERE `podcast`.`episodes` != `episode_count`.`episodes` AND `podcast`.`id` = `episode_count`.`podcast`;";
            Dba::write($sql, $params);
            Catalog::update_mapping('podcast');
            Catalog::update_mapping('podcast_episode');
            Catalog::count_table('podcast_episode');
        }
        $this->update_lastsync($time);
    }

    /**
     * add_episode
     * @param SimpleXMLElement $episode
     * @param int $lastSync
     * @return PDOStatement|bool
     */
    private function add_episode(SimpleXMLElement $episode, $lastSync = 0)
    {
        $title       = html_entity_decode((string)$episode->title);
        $website     = (string)$episode->link;
        $guid        = (string)$episode->guid;
        $description = html_entity_decode(Dba::check_length((string)$episode->description, 4096));
        $author      = html_entity_decode(Dba::check_length((string)$episode->author, 64));
        $category    = html_entity_decode((string)$episode->category);
        $source      = '';
        $time        = 0;
        if ($episode->enclosure) {
            $source = (string)$episode->enclosure['url'];
        }
        $itunes   = $episode->children('itunes', true);
        $duration = (string) $itunes->duration;
        // time is missing hour e.g. "15:23"
        if (preg_grep("/^[0-9][0-9]\:[0-9][0-9]$/", array($duration))) {
            $duration = '00:' . $duration;
        }
        // process a time string "03:23:01"
        $ptime = (preg_grep("/[0-9]?[0-9]\:[0-9][0-9]\:[0-9][0-9]/", array($duration)))
            ? date_parse((string)$duration)
            : $duration;
        // process "HH:MM:SS" time OR fall back to a seconds duration string e.g "24325"
        $time = (is_array($ptime))
            ? (int) $ptime['hour'] * 3600 + (int) $ptime['minute'] * 60 + (int) $ptime['second']
            : (int) $ptime;

        $pubdate    = 0;
        $pubdatestr = (string)$episode->pubDate;
        if ($pubdatestr) {
            $pubdate = strtotime($pubdatestr);
        }
        if ($pubdate < 1) {
            debug_event(self::class, 'Invalid episode publication date, skipped', 3);

            return false;
        }
        if (empty($source)) {
            debug_event(self::class, 'Episode source URL not found, skipped', 3);

            return false;
        }
        // don't keep adding the same episodes
        if (self::get_id_from_guid($guid) > 0) {
            debug_event(self::class, 'Episode guid already exists, skipped', 3);

            return false;
        }
        // don't keep adding urls
        if (self::get_id_from_source($source) > 0) {
            debug_event(self::class, 'Episode source URL already exists, skipped', 3);

            return false;
        }
        // podcast urls can change over time so check these
        if (self::get_id_from_title($this->id, $title, $time) > 0) {
            debug_event(self::class, 'Episode title already exists, skipped', 3);

            return false;
        }
        // podcast pubdate can be used to skip duplicate/fixed episodes when you already have them
        if (self::get_id_from_pubdate($this->id, $pubdate) > 0) {
            debug_event(self::class, 'Episode with the same publication date already exists, skipped', 3);

            return false;
        }

        // by default you want to download all the episodes
        $state = 'pending';
        // if you're syncing an old podcast, check the pubdate and skip it if published to the feed before your last sync
        if ($lastSync > 0 && $pubdate < $lastSync) {
            $state = 'skipped';
        }

        debug_event(self::class, 'Adding new episode to podcast ' . $this->id . '... ' . $pubdate, 4);
        $sql = "INSERT INTO `podcast_episode` (`title`, `guid`, `podcast`, `state`, `source`, `website`, `description`, `author`, `category`, `time`, `pubdate`, `addition_time`, `catalog`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        return Dba::write($sql, array(
            $title,
            $guid,
            $this->id,
            $state,
            $source,
            $website,
            $description,
            $author,
            $category,
            $time,
            $pubdate,
            time(),
            $this->catalog
        ));
    }

    /**
     * update_lastsync
     * @param int $time
     * @return PDOStatement|bool
     */
    private function update_lastsync($time)
    {
        $sql = "UPDATE `podcast` SET `lastsync` = ? WHERE `id` = ?";

        return Dba::write($sql, array($time, $this->id));
    }

    /**
     * sync_episodes
     * @param bool $gather
     * @return bool
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
            // I've seems some &'s in feeds that screw up
            $xml = simplexml_load_string(str_replace('&', '&amp;', $xmlstr));
        }
        if ($xml === false) {
            debug_event(self::class, 'Cannot read feed ' . $this->feed, 1);

            return false;
        }

        $this->add_episodes($xml->channel->item, $this->lastsync, $gather);

        return true;
    }

    /**
     * remove
     * Delete the object from disk and/or database where applicable.
     */
    public function remove(): bool
    {
        $episodes = $this->get_episodes();
        foreach ($episodes as $episode_id) {
            $episode = new Podcast_Episode($episode_id);
            $episode->remove();
        }

        $sql = "DELETE FROM `podcast` WHERE `id` = ?";

        if (Dba::write($sql, array($this->id)) !== false) {
            Catalog::count_table('podcast');
            Catalog::count_table('podcast_episode');

            return true;
        }

        return false;
    }

    /**
     * get_id_from_source
     *
     * Get episode id from the source url.
     *
     * @param string $url
     * @return int
     */
    public static function get_id_from_source($url)
    {
        $sql        = "SELECT `id` FROM `podcast_episode` WHERE `source` = ?";
        $db_results = Dba::read($sql, array($url));

        if ($results = Dba::fetch_assoc($db_results)) {
            return (int)$results['id'];
        }

        return 0;
    }

    /**
     * get_id_from_guid
     *
     * Get episode id from the guid.
     *
     * @param string $url
     * @return int
     */
    public static function get_id_from_guid($url)
    {
        $sql        = "SELECT `id` FROM `podcast_episode` WHERE `guid` = ?";
        $db_results = Dba::read($sql, array($url));

        if ($results = Dba::fetch_assoc($db_results)) {
            return (int)$results['id'];
        }

        return 0;
    }

    /**
     * get_id_from_title
     *
     * Get episode id from the source url.
     *
     * @param int $podcast_id
     * @param string $title
     * @param int $time
     * @return int
     */
    public static function get_id_from_title($podcast_id, $title, $time)
    {
        $sql        = "SELECT `id` FROM `podcast_episode` WHERE `podcast` = ? AND title = ? AND `time` = ?";
        $db_results = Dba::read($sql, array($podcast_id, $title, $time));

        if ($results = Dba::fetch_assoc($db_results)) {
            return (int)$results['id'];
        }

        return 0;
    }

    /**
     * get_id_from_pubdate
     *
     * Get episode id from the source url.
     *
     * @param int $podcast_id
     * @param int $pubdate
     * @return int
     */
    public static function get_id_from_pubdate($podcast_id, $pubdate)
    {
        $sql        = "SELECT `id` FROM `podcast_episode` WHERE `podcast` = ? AND pubdate = ?";
        $db_results = Dba::read($sql, array($podcast_id, $pubdate));

        if ($results = Dba::fetch_assoc($db_results)) {
            return (int)$results['id'];
        }

        return 0;
    }

    /**
     * get_root_path
     */
    public function get_root_path(): string
    {
        $catalog = Catalog::create_from_id($this->catalog);
        if (!$catalog->get_type() == 'local') {
            debug_event(self::class, 'Bad catalog type.', 1);

            return '';
        }

        $dirname = $this->title;

        // create path if it doesn't exist
        if (!is_dir($catalog->get_path() . DIRECTORY_SEPARATOR . $dirname) && !self::create_catalog_path($catalog->get_path() . DIRECTORY_SEPARATOR . $dirname)) {
            return '';
        }

        return $catalog->get_path() . DIRECTORY_SEPARATOR . $dirname;
    }

    /**
     * create_catalog_path
     * This returns the catalog types that are available
     * @param string $path
     * @return bool
     */
    private static function create_catalog_path($path)
    {
        if (!is_dir($path)) {
            if (mkdir($path) === false) {
                debug_event(__CLASS__, 'Cannot create directory ' . $path, 2);

                return false;
            }
        }

        return true;
    }
}
