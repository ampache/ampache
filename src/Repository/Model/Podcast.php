<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Repository\Model;

use Ampache\Module\Podcast\PodcastEpisodeStateEnum;
use Ampache\Config\AmpConfig;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepository;
use Ampache\Repository\PodcastRepositoryInterface;
use DateTime;
use DateTimeInterface;
use LogicException;

/**
 * Podcast item
 *
 * @see PodcastRepository
 */
class Podcast extends database_object implements library_item, CatalogItemInterface
{
    protected const DB_TABLENAME = 'podcast';

    private int $id = 0;

    private ?string $feed = null;

    private int $catalog = 0;

    private ?string $title = null;

    private ?string $website = null;

    private ?string $description = null;

    private ?string $language = null;

    private ?string $copyright = null;

    private ?string $generator = null;

    private int $lastbuilddate = 0;

    private int $lastsync = 0;

    private int $total_count = 0;

    private int $total_skip = 0;

    private int $episodes = 0;

    private ?string $link = null;

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
     * format
     * this function takes the object and formats some values
     *
     * @deprecated
     */
    public function format(?bool $details = true): void
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
     * @return array{
     *  podcast: array{
     *    important: bool,
     *    label: string,
     *    value: null|string
     *  }
     * }
     */
    public function get_keywords(): array
    {
        return [
            'podcast' => [
                'important' => true,
                'label' => T_('Podcast'),
                'value' => $this->get_fullname(),
            ],
        ];
    }

    /**
     * get_fullname
     */
    public function get_fullname(): ?string
    {
        return $this->title;
    }

    /**
     * Get item link.
     */
    public function get_link(): string
    {
        // don't do anything if it's formatted
        if ($this->link === null) {
            $web_path   = AmpConfig::get_web_path();
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

    public function get_childrens(): array
    {
        return ['podcast_episode' => $this->getEpisodeIds()];
    }

    /**
     * Search for direct children of an object
     * @param string $name
     */
    public function get_children($name): array
    {
        debug_event(self::class, 'get_children ' . $name, 5);

        return [];
    }

    /**
     * @return list<array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function get_medias(?string $filter_type = null): array
    {
        $medias = [];
        if ($filter_type === null || $filter_type === 'podcast_episode') {
            $episodes = $this->getEpisodeIds(PodcastEpisodeStateEnum::COMPLETED);
            foreach ($episodes as $episode_id) {
                $medias[] = ['object_type' => LibraryItemEnum::PODCAST_EPISODE, 'object_id' => $episode_id];
            }
        }

        return $medias;
    }

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
        if ($this->f_description === null) {
            $this->f_description = scrub_out($this->description ?? '');
        }

        return $this->f_description;
    }

    /**
     * Sets the episode count
     */
    public function setEpisodeCount(int $value): Podcast
    {
        $this->episodes = $value;

        return $this;
    }

    /**
     * Returns the episode count
     */
    public function getEpisodeCount(): int
    {
        return $this->episodes;
    }

    /**
     * Sets the total count
     */
    public function setTotalCount(int $value): Podcast
    {
        $this->total_count = $value;

        return $this;
    }

    /**
     * Returns the total count
     */
    public function getTotalCount(): int
    {
        return $this->total_count;
    }

    /**
     * Sets the total skip count
     */
    public function setTotalSkip(int $value): Podcast
    {
        $this->total_skip = $value;

        return $this;
    }

    /**
     * Returns the total skip count
     */
    public function getTotalSkip(): int
    {
        return $this->total_skip;
    }

    /**
     * Sets the generator
     */
    public function setGenerator(string $value): Podcast
    {
        $this->generator = $value;

        return $this;
    }

    /**
     * Returns the generator
     */
    public function getGenerator(): string
    {
        return (string) $this->generator;
    }

    /**
     * Sets the website
     */
    public function setWebsite(string $value): Podcast
    {
        $this->website = $value;

        return $this;
    }

    /**
     * Returns the website
     */
    public function getWebsite(): string
    {
        return (string) $this->website;
    }

    /**
     * Sets the copyright
     */
    public function setCopyright(string $value): Podcast
    {
        $this->copyright = $value;

        return $this;
    }

    /**
     * Returns the copyright
     */
    public function getCopyright(): string
    {
        return (string) $this->copyright;
    }

    /**
     * Sets the language
     */
    public function setLanguage(string $value): Podcast
    {
        $this->language = mb_substr($value, 0, 5);

        return $this;
    }

    /**
     * Returns the language
     */
    public function getLanguage(): string
    {
        return (string) $this->language;
    }

    /**
     * Sets the feed-url
     */
    public function setFeedUrl(string $value): Podcast
    {
        $this->feed = $value;

        return $this;
    }

    /**
     * Returns the feed-url
     */
    public function getFeedUrl(): string
    {
        return (string) $this->feed;
    }

    /**
     * Sets the title
     */
    public function setTitle(string $value): Podcast
    {
        $this->title = $value;

        return $this;
    }

    /**
     * Returns the title
     */
    public function getTitle(): string
    {
        return (string) $this->title;
    }

    /**
     * Sets the description
     */
    public function setDescription(string $value): Podcast
    {
        /**
         * db field is limited to 4096 chars
         */
        $this->description = mb_substr($value, 0, 4096);

        return $this;
    }

    /**
     * Returns the description
     */
    public function getDescription(): string
    {
        return (string) $this->description;
    }

    /**
     * Sets the catalog
     */
    public function setCatalog(Catalog $catalog): Podcast
    {
        $this->catalog = $catalog->getId();

        return $this;
    }

    /**
     * Returns the id of the catalog the item is associated to
     */
    public function getCatalogId(): int
    {
        return $this->catalog;
    }

    /**
     * Sets the last sync-date
     */
    public function setLastSyncDate(DateTimeInterface $value): Podcast
    {
        $this->lastsync = $value->getTimestamp();

        return $this;
    }

    /**
     * Returns the last sync-date
     */
    public function getLastSyncDate(): DateTimeInterface
    {
        return new DateTime('@' . $this->lastsync);
    }

    /**
     * Sets the last build-date
     */
    public function setLastBuildDate(?DateTimeInterface $value): Podcast
    {
        if ($value !== null) {
            $this->lastbuilddate = $value->getTimestamp();
        }

        return $this;
    }

    /**
     * Returns the last build-date
     */
    public function getLastBuildDate(): DateTimeInterface
    {
        return new DateTime('@' . $this->lastbuilddate);
    }

    /**
     * Saves the item
     */
    public function save(): void
    {
        $id = $this->getPodcastRepository()->persist($this);

        if ($id !== null) {
            $this->id = $id;
        }
    }

    /**
     * display_art
     * @param int $thumb
     * @param bool $force
     */
    public function display_art($thumb = 2, $force = false): void
    {
        if (Art::has_db($this->id, 'podcast') || $force) {
            Art::display('podcast', $this->id, (string)$this->get_fullname(), $thumb, $this->get_link());
        }
    }

    /**
     * update
     * This takes a key'd array of data and updates the current podcast
     * @param array<mixed> $data
     */
    public function update(array $data): never
    {
        throw new LogicException('Podcast::update is not in use');
    }

    /**
     * Returns the ids of all available episodes
     *
     * @param null|PodcastEpisodeStateEnum $stateFilter Return only items with this state
     *
     * @return list<int>
     */
    public function getEpisodeIds(
        ?PodcastEpisodeStateEnum $stateFilter = null
    ): array {
        return $this->getPodcastEpisodeRepository()->getEpisodes($this, $stateFilter);
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::PODCAST;
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getPodcastRepository(): PodcastRepositoryInterface
    {
        global $dic;

        return $dic->get(PodcastRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getPodcastEpisodeRepository(): PodcastEpisodeRepositoryInterface
    {
        global $dic;

        return $dic->get(PodcastEpisodeRepositoryInterface::class);
    }
}
