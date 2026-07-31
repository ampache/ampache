<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

use Ampache\Config\AmpConfig;
use Ampache\Module\Podcast\PodcastEpisodeStateEnum;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepository;
use Ampache\Repository\PodcastRepositoryInterface;
use DateMalformedStringException;
use DateTime;
use DateTimeInterface;
use LogicException;

/**
 * Podcast item
 *
 * @see PodcastRepository
 */
class Podcast extends database_object implements
    library_item,
    displayable_item,
    container_item,
    CatalogItemInterface
{
    protected const string DB_TABLENAME = 'podcast';

    public int $catalog          = 0;
    private ?string $copyright   = null;
    private ?string $description = null;
    private int $episodes        = 0;
    private ?string $f_link      = null;
    private ?string $feed        = null;
    private ?string $generator   = null;
    private ?bool $has_art       = null;
    private int $id              = 0;
    private ?string $language    = null;
    private int $lastbuilddate   = 0;
    private int $lastsync        = 0;
    private ?string $link        = null;
    private ?string $title       = null;
    private int $total_count     = 0;
    private int $total_skip      = 0;
    private ?string $website     = null;

    /**
     * Podcast
     * Takes the ID of the podcast and pulls the info from the db
     */
    public function __construct(?int $podcast_id = 0)
    {
        if (!$podcast_id) {
            return;
        }

        $info = $this->get_info($podcast_id, static::DB_TABLENAME);
        if ($info === []) {
            return;
        }

        $this->catalog       = (int) ($info['catalog'] ?? 0);
        $this->copyright     = $info['copyright'] ?? null;
        $this->description   = $info['description'] ?? null;
        $this->episodes      = (int) ($info['episodes'] ?? 0);
        $this->feed          = $info['feed'] ?? null;
        $this->generator     = $info['generator'] ?? null;
        $this->id            = (int) ($info['id'] ?? 0);
        $this->language      = $info['language'] ?? null;
        $this->lastbuilddate = (int) ($info['lastbuilddate'] ?? 0);
        $this->lastsync      = (int) ($info['lastsync'] ?? 0);
        $this->link          = $info['link'] ?? null;
        $this->title         = $info['title'] ?? null;
        $this->total_count   = (int) ($info['total_count'] ?? 0);
        $this->total_skip    = (int) ($info['total_skip'] ?? 0);
        $this->website       = $info['website'] ?? null;
    }

    /**
     * display_art
     * @param array{width: int, height: int} $size
     */
    public function display_art(array $size, bool $force = false): void
    {
        if (Art::has_db($this->id, 'podcast') || $force) {
            Art::display('podcast', $this->id, (string) $this->get_fullname(), $size, $this->get_link());
        }
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
        return scrub_out($this->description ?? '');
    }

    /**
     * Get item f_link.
     */
    public function get_f_link(?string $title = null): string
    {
        // don't do anything if it's formatted
        if ($this->f_link === null) {
            $this->f_link = '<a href="' . $this->get_link() . '" title="' . scrub_out($this->get_fullname()) . '">' . scrub_out($title ?? $this->get_fullname()) . '</a>';
        }

        return $this->f_link;
    }

    /**
     * Return a formatted link to the parent object (if appliccable)
     */
    public function get_f_parent_link(): ?string
    {
        return null;
    }

    /**
     * Get item f_time or f_time_h.
     */
    public function get_f_time(): string
    {
        return '';
    }

    /**
     * get_fullname
     */
    public function get_fullname(): ?string
    {
        return $this->title;
    }

    /**
     * Get item keywords for metadata searches.
     * @return array<string, array{important: bool, label: string, value: string}>
     */
    public function get_keywords(): array
    {
        return [
            'podcast' => [
                'important' => true,
                'label' => T_('Podcast'),
                'value' => (string) $this->get_fullname(),
            ],
        ];
    }

    /**
     * Get item link.
     */
    public function get_link(): string
    {
        // don't do anything if it's formatted
        if ($this->link === null) {
            $web_path = AmpConfig::get_web_path('/client');

            $this->link = $web_path . '/podcast.php?action=show&podcast=' . $this->id;
        }

        return $this->link ?? '';
    }

    /**
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
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

    /**
     * get_parent
     * Return parent `object_type`, `object_id`; null otherwise.
     */
    public function get_parent(): ?array
    {
        return null;
    }

    public function get_parent_fullname(): string
    {
        return '';
    }

    public function get_user_owner(): ?int
    {
        return null;
    }

    /**
     * Returns the id of the catalog the item is associated to
     */
    public function getCatalogId(): int
    {
        return $this->catalog;
    }

    /**
     * Returns the copyright
     */
    public function getCopyright(): string
    {
        return (string) $this->copyright;
    }

    /**
     * Returns the description
     */
    public function getDescription(): string
    {
        return (string) $this->description;
    }

    /**
     * Returns the episode count
     */
    public function getEpisodeCount(): int
    {
        return $this->episodes;
    }

    /**
     * Returns the ids of all available episodes
     *
     * @param null|PodcastEpisodeStateEnum $stateFilter Return only items with this state
     * @return list<int>
     */
    public function getEpisodeIds(
        ?PodcastEpisodeStateEnum $stateFilter = null,
    ): array {
        return $this->getPodcastEpisodeRepository()->getEpisodes($this, $stateFilter);
    }

    /**
     * Returns the feed-url
     */
    public function getFeedUrl(): string
    {
        return (string) $this->feed;
    }

    /**
     * Returns the generator
     */
    public function getGenerator(): string
    {
        return (string) $this->generator;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Returns the language
     */
    public function getLanguage(): string
    {
        return (string) $this->language;
    }

    /**
     * Returns the last build-date
     * @throws DateMalformedStringException
     */
    public function getLastBuildDate(): DateTimeInterface
    {
        return new DateTime('@' . $this->lastbuilddate);
    }

    /**
     * Returns the last sync-date
     * @throws DateMalformedStringException
     */
    public function getLastSyncDate(): DateTimeInterface
    {
        return new DateTime('@' . $this->lastsync);
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::PODCAST;
    }

    /**
     * Returns the title
     */
    public function getTitle(): string
    {
        return (string) $this->title;
    }

    /**
     * Returns the total count
     */
    public function getTotalCount(): int
    {
        return $this->total_count;
    }

    /**
     * Returns the total skip count
     */
    public function getTotalSkip(): int
    {
        return $this->total_skip;
    }

    /**
     * Returns the website
     */
    public function getWebsite(): string
    {
        return (string) $this->website;
    }

    /**
     * does the item have art?
     */
    public function has_art(): bool
    {
        if ($this->has_art === null) {
            $this->has_art = Art::has_db($this->id, 'podcast');
        }

        return $this->has_art ?? false;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
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
     * Sets the catalog
     */
    public function setCatalog(Catalog $catalog): Podcast
    {
        $this->catalog = $catalog->getId();

        return $this;
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
     * Sets the episode count
     */
    public function setEpisodeCount(int $value): Podcast
    {
        $this->episodes = $value;

        return $this;
    }

    /**
     * Sets the feed-url
     */
    public function setFeedUrl(string $value): Podcast
    {
        // Feed must be http/https
        if (
            str_starts_with($value, 'http://')
            || str_starts_with($value, 'https://')
        ) {
            $this->feed = $value;
        }

        return $this;
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
     * Sets the language
     */
    public function setLanguage(string $value): Podcast
    {
        $this->language = mb_substr($value, 0, 5);

        return $this;
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
     * Sets the last sync-date
     */
    public function setLastSyncDate(DateTimeInterface $value): Podcast
    {
        $this->lastsync = $value->getTimestamp();

        return $this;
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
     * Sets the total count
     */
    public function setTotalCount(int $value): Podcast
    {
        $this->total_count = $value;

        return $this;
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
     * Sets the website
     */
    public function setWebsite(string $value): Podcast
    {
        $this->website = $value;

        return $this;
    }

    /**
     * update
     * This takes a key'd array of data and updates the current podcast
     */
    public function update(array $data): never
    {
        throw new LogicException('Podcast::update is not in use');
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getPodcastEpisodeRepository(): PodcastEpisodeRepositoryInterface
    {
        global $dic;

        return $dic->get(PodcastEpisodeRepositoryInterface::class);
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
