<?php

declare(strict_types=0);

/**
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

namespace Ampache\Repository\Model;

use Ampache\Module\Podcast\PodcastSyncerInterface;
use Ampache\Module\System\Dba;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;

class Podcast extends database_object implements library_item
{
    protected const DB_TABLENAME = 'podcast';

    /* Variables from DB */
    public int $id = 0;
    public ?string $feed;
    public int $catalog;
    public ?string $title;
    public ?string $website;
    public ?string $description;
    public ?string $language;
    public ?string $copyright;
    public ?string $generator;
    public int $lastbuilddate;
    public int $lastsync;
    public int $total_count;
    public int $total_skip;
    public int $episodes;

    public ?string $link = null;
    public $f_name;
    public $f_website;
    public $f_description;
    public $f_language;
    public $f_copyright;
    public $f_generator;
    public $f_lastbuilddate;
    public $f_lastsync;
    public $f_link;
    public $f_website_link;

    private ?bool $has_art = null;

    /**
     * Podcast
     * Takes the ID of the podcast and pulls the info from the db
     * @param int|null $podcast_id
     */
    public function __construct($podcast_id = 0)
    {
        if (!$podcast_id) {
            return;
        }

        $info = $this->get_info($podcast_id, static::DB_TABLENAME);
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return list<int>
     */
    public function get_catalogs()
    {
        return array($this->catalog);
    }

    /**
     * get_episodes
     * gets all episodes for this podcast
     * @return list<int>
     */
    public function get_episodes(string $state_filter = ''): array
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
            $results[] = (int) $row['id'];
        }

        return $results;
    }

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
        if ($this->has_art === null) {
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
    public function get_fullname(): ?string
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
        if ($this->link === null) {
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
     * get_parent
     * Return parent `object_type`, `object_id`; null otherwise.
     */
    public function get_parent(): ?array
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
    public function get_user_owner(): ?int
    {
        return null;
    }

    public function get_default_art_kind(): string
    {
        return 'default';
    }

    /**
     * get_description
     */
    public function get_description(): string
    {
        if (!isset($this->f_description)) {
            $this->f_description = scrub_out($this->description ?? '');
        }

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
            Art::display('podcast', $this->id, (string)$this->get_fullname(), $thumb, $this->get_link());
        }
    }

    /**
     * update
     * This takes a key'd array of data and updates the current podcast
     * @param array{
     *  feed?: string|null,
     *  title?: string|null,
     *  website?: string|null,
     *  description?: string|null,
     *  language?: string|null,
     *  generator?: string|null,
     *  copyright?: string|null
     * } $data
     * @return int|false
     */
    public function update(array $data)
    {
        $feed = $data['feed'] ?? $this->feed ?? '';

        /** @var null|string $title */
        $title = (isset($data['title'])) ? $data['title'] : null;

        /** @var null|string $website */
        $website = (isset($data['website'])) ? $data['website'] : null;

        /** @var null|string $description */
        $description = (isset($data['description'])) ? Dba::check_length((string)$data['description'], 4096) : null;

        /** @var null|string $language */
        $language = (isset($data['language'])) ? $data['language'] : null;

        /** @var null|string $generator */
        $generator = (isset($data['generator'])) ? $data['generator'] : null;

        /** @var null|string $copyright */
        $copyright = (isset($data['copyright'])) ? $data['copyright'] : null;

        if (strpos($feed, "http://") !== 0 && strpos($feed, "https://") !== 0) {
            debug_event(self::class, 'Podcast update canceled, bad feed url.', 1);

            return false;
        }

        $sql = 'UPDATE `podcast` SET `feed` = ?, `title` = ?, `website` = ?, `description` = ?, `language` = ?, `generator` = ?, `copyright` = ? WHERE `id` = ?';
        Dba::write($sql, array($feed, $title, $website, $description, $language, $generator, $copyright, $this->id));

        return $this->id;
    }

    /**
     * create
     * @param array{
     *  catalog_id: int,
     *  feed: string
     * } $data
     * @return bool|int
     */
    public static function create(array $data, bool $return_id = false)
    {
        $feed = (string) $data['feed'];
        // Feed must be http/https
        if (strpos($feed, "http://") !== 0 && strpos($feed, "https://") !== 0) {
            AmpError::add('feed', T_('Feed URL is invalid'));
        }

        $catalog_id = (int)($data['catalog_id']);
        if ($catalog_id < 1) {
            AmpError::add('catalog', T_('Target Catalog is required'));
        } else {
            $catalog = Catalog::create_from_id($catalog_id);
            if (!$catalog instanceof Catalog) {
                AmpError::add('catalog', T_('Catalog not found'));
            }
            if ($catalog !== null && $catalog->gather_types !== "podcast") {
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
                self::getPodcastSyncer()->addEpisodes($podcast, $episodes);
            }
            if ($return_id) {
                return (int)$podcast_id;
            }

            return true;
        }

        return false;
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
     * get_root_path
     */
    public function get_root_path(): string
    {
        $catalog = Catalog::create_from_id($this->catalog);
        if ($catalog === null || !$catalog->get_type() == 'local') {
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
     */
    private static function create_catalog_path(string $path): bool
    {
        if (!is_dir($path)) {
            if (mkdir($path) === false) {
                debug_event(__CLASS__, 'Cannot create directory ' . $path, 2);

                return false;
            }
        }

        return true;
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getPodcastSyncer(): PodcastSyncerInterface
    {
        global $dic;

        return $dic->get(PodcastSyncerInterface::class);
    }
}
