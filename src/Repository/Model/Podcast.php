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

use Ampache\Module\Podcast\PodcastEpisodeStateEnum;
use Ampache\Module\System\Dba;
use Ampache\Config\AmpConfig;
use Ampache\Repository\PodcastRepositoryInterface;
use DateTime;
use DateTimeInterface;

class Podcast extends database_object implements library_item
{
    protected const DB_TABLENAME = 'podcast';

    /* Variables from DB */
    public int $id = 0;

    private ?string $feed;

    public int $catalog;

    private ?string $title;

    private ?string $website;

    private ?string $description;

    private ?string $language;

    private ?string $copyright;

    private ?string $generator;

    private int $lastbuilddate;

    private int $lastsync;

    private int $total_count;

    private int $total_skip;

    private int $episodes;

    private ?string $link = null;

    private ?string $f_name = null;

    private ?string $f_description = null;

    private ?string $f_link = null;

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
     * format
     * this function takes the object and formats some values
     *
     * @param bool $details
     *
     * @deprecated
     */
    public function format($details = true): void
    {
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
        if ($this->f_link === null) {
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
        return array('podcast_episode' => $this->getPodcastRepository()->getEpisodes($this));
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
            $episodes = $this->getPodcastRepository()->getEpisodes($this, PodcastEpisodeStateEnum::COMPLETED);
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

    public function getEpisodeCount(): int
    {
        return $this->episodes;
    }

    public function getTotalCount(): int
    {
        return $this->total_count;
    }

    public function getTotalSkip(): int
    {
        return $this->total_skip;
    }

    public function getGenerator(): string
    {
        return (string) $this->generator;
    }

    public function getWebsite(): string
    {
        return (string) $this->website;
    }

    public function getCopyright(): string
    {
        return (string) $this->copyright;
    }

    public function getLanguage(): string
    {
        return (string) $this->language;
    }

    public function getFeed(): string
    {
        return (string) $this->feed;
    }

    public function getTitle(): string
    {
        return (string) $this->title;
    }

    public function getDescription(): string
    {
        return (string) $this->description;
    }

    public function getCatalogId(): int
    {
        return $this->catalog;
    }

    public function getLink(): string
    {
        return (string) $this->link;
    }

    public function getLastSyncDate(): DateTimeInterface
    {
        return new DateTime('@' . $this->lastsync);
    }

    public function getLastBuildDate(): DateTimeInterface
    {
        return new DateTime('@' . $this->lastbuilddate);
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
     * @deprecated Inject by constructor
     */
    private function getPodcastRepository(): PodcastRepositoryInterface
    {
        global $dic;

        return $dic->get(PodcastRepositoryInterface::class);
    }
}
