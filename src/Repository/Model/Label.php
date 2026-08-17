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
use Ampache\Module\Art\Art;
use Ampache\Module\Database\database_object;
use Ampache\Module\Label\LabelNameFilterInterface;
use Ampache\Module\System\Core;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;

/**
 * This is the class responsible for handling the Label object
 * it is related to the label table in the database.
 */
class Label extends database_object implements
    library_item,
    container_item,
    displayable_item,
    ModelInterface
{
    protected const string DB_TABLENAME = 'label';

    public bool $active     = true;
    public ?string $address = null;

    /** @var int[] $albums */
    public array $albums = [];

    /** @var int[] $artists */
    public array $artists = [];

    public ?string $category   = null;
    public ?string $country    = null;
    public ?int $creation_date = null;
    public ?string $email      = null;
    public int $id             = 0;
    public ?string $link       = null;
    public ?string $mbid       = null; // MusicBrainz ID
    public ?string $name       = null;
    public ?string $summary    = null;
    public ?int $user          = null;
    public ?string $website    = null;
    private ?int $album_count  = null;
    private ?int $artist_count = null;
    private ?string $f_link    = null;

    /**
     * __construct
     */
    public function __construct(?int $label_id = 0)
    {
        if (!$label_id) {
            return;
        }

        $info                = $this->get_info($label_id, static::DB_TABLENAME);
        $this->id            = (int) ($info['id'] ?? 0);
        $this->name          = $info['name'] ?? null;
        $this->mbid          = $info['mbid'] ?? null;
        $this->category      = $info['category'] ?? null;
        $this->summary       = $info['summary'] ?? null;
        $this->address       = $info['address'] ?? null;
        $this->country       = $info['country'] ?? null;
        $this->email         = $info['email'] ?? null;
        $this->website       = $info['website'] ?? null;
        $this->active        = (bool) ($info['active'] ?? true);
        $this->user          = isset($info['user']) ? (int) $info['user'] : null;
        $this->creation_date = isset($info['creation_date']) ? (int) $info['creation_date'] : null;
    }

    /**
     * build_cache
     * This attempts to reduce # of queries by asking for everything in the
     * browse all at once and storing it in the cache
     * @param array<int|string> $ids
     */
    public static function build_cache(array $ids): bool
    {
        if (empty($ids)) {
            return false;
        }

        // with the cache off these rows are discarded and the per-object queries still run, so this is a net loss
        if (!database_object::isCacheEnabled()) {
            return false;
        }

        foreach (self::getLabelRepository()->getRowsByIds($ids) as $row) {
            parent::add_to_cache('label', (int) $row['id'], $row);
        }

        // objects with no artists would otherwise keep re-querying on every cache miss
        $counts = self::getLabelRepository()->getArtistCountsByIds($ids);
        foreach ($ids as $id) {
            parent::add_to_cache('label_artist_count', (int) $id, ['count' => $counts[(int) $id] ?? 0]);
        }

        Art::build_cache($ids, 'label');

        return true;
    }

    /**
     * create
     */
    public static function create(array $data): ?int
    {
        if (self::getLabelRepository()->lookup($data['name']) !== 0) {
            return null;
        }

        // the add form only posts the fields it renders, so every key is optional here
        $label                = new Label();
        $label->name          = $data['name'];
        $label->mbid          = $data['mbid'] ?? null;
        $label->category      = $data['category'] ?? null;
        $label->summary       = $data['summary'] ?? null;
        $label->address       = $data['address'] ?? null;
        $label->country       = $data['country'] ?? null;
        $label->email         = $data['email'] ?? null;
        $label->website       = $data['website'] ?? null;
        $label->user          = $data['user'] ?? Core::get_global('user')?->getId();
        $label->active        = (bool) ($data['active'] ?? true);
        $label->creation_date = $data['creation_date'] ?? time();

        return self::getLabelRepository()->persist($label);
    }

    /**
     * get_display
     * This returns a csv formatted version of the labels that we are given
     * @param string[] $labels
     */
    public static function get_display(array $labels, bool $link = false): string
    {
        if (empty($labels)) {
            return '';
        }

        $web_path = AmpConfig::get_web_path('/client');

        $results = '';
        // Iterate through the labels, format them according to type and element id
        foreach ($labels as $label_id => $value) {
            if ($link) {
                $results .= '<a href="' . $web_path . '/labels.php?action=show&label=' . $label_id . '" title="' . $value . '">';
            }

            $results .= $value;
            if ($link) {
                $results .= '</a>';
            }

            $results .= ', ';
        }

        return rtrim($results, ', ');
    }

    /**
     * helper
     */
    public static function helper(string $name): ?int
    {
        // tags carry placeholders like `[no label]` for releases that never had a publisher
        if (self::getLabelNameFilter()->isIgnored($name)) {
            return null;
        }

        $label_data = [
            'name' => $name,
            'mbid' => null,
            'category' => 'tag_generated',
            'summary' => null,
            'address' => null,
            'country' => null,
            'email' => null,
            'website' => null,
            'active' => 1,
            'user' => 0,
            'creation_date' => time(),
        ];

        return self::create($label_data);
    }

    /**
     * Migrate an object associate stats to a new object
     */
    public static function migrate(string $object_type, int $old_object_id, int $new_object_id): void
    {
        if ($object_type == 'artist') {
            self::getLabelRepository()->migrateArtist($old_object_id, $new_object_id);
        } elseif ($object_type == 'album') {
            self::getLabelRepository()->migrateAlbum($old_object_id, $new_object_id);
        }
    }

    /**
     * @deprecated inject dependency
     */
    private static function getLabelNameFilter(): LabelNameFilterInterface
    {
        global $dic;

        return $dic->get(LabelNameFilterInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getLabelRepository(): LabelRepositoryInterface
    {
        global $dic;

        return $dic->get(LabelRepositoryInterface::class);
    }

    /**
     * display_art
     * @param array{width: int, height: int} $size
     */
    public function display_art(array $size, bool $force = false): void
    {
        if ($this->has_art() || $force) {
            Art::display('label', $this->id, (string) $this->get_fullname(), $size, $this->get_link());
        }
    }

    /**
     * get_album_count
     */
    public function get_album_count(): int
    {
        if ($this->album_count === null) {
            $this->album_count = count($this->get_albums());
        }

        return $this->album_count;
    }

    /**
     * get_albums
     * @return int[]
     */
    public function get_albums(): array
    {
        if (empty($this->albums)) {
            $this->albums = self::getLabelRepository()->getAlbums($this);
        }

        return $this->albums;
    }

    /**
     * get_artist_count
     */
    public function get_artist_count(): int
    {
        if ($this->artist_count === null) {
            $this->artist_count = (self::is_cached('label_artist_count', $this->id))
                ? (int) self::get_from_cache('label_artist_count', $this->id)['count']
                : count($this->get_artists());
        }

        return $this->artist_count;
    }

    /**
     * get_artists
     * @return int[]
     */
    public function get_artists(): array
    {
        if (empty($this->artists)) {
            $this->artists = self::getLabelRepository()->getArtists($this);
        }

        return $this->artists;
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
        return $this->summary ?? '';
    }

    /**
     * Get item f_link.
     */
    public function get_f_link(?string $title = null): string
    {
        // don't do anything if it's formatted
        if ($this->f_link === null) {
            $this->f_link = "<a href=\"" . $this->get_link() . "\" title=\"" . scrub_out($this->get_fullname()) . "\">" . scrub_out($title ?? $this->get_fullname());
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
        return $this->name;
    }

    /**
     * Get item keywords for metadata searches.
     * @return array<string, array{important: bool, label: string, value: string}>
     */
    public function get_keywords(): array
    {
        return [
            'label' => [
                'important' => true,
                'label' => T_('Label'),
                'value' => (string) $this->get_fullname()
            ]
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

            $this->link = $web_path . '/labels.php?action=show&label=' . $this->id;
        }

        return $this->link ?? '';
    }

    /**
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function get_medias(?string $filter_type = null): array
    {
        $medias = [];
        if ($filter_type === null || $filter_type === 'song') {
            $songs = $this->getSongRepository()->getByLabel((string) $this->name);
            foreach ($songs as $song_id) {
                $medias[] = ['object_type' => LibraryItemEnum::SONG, 'object_id' => $song_id];
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

    /**
     * get_user_owner
     */
    public function get_user_owner(): ?int
    {
        return $this->user;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::LABEL;
    }

    public function has_art(): bool
    {
        return Art::has_db($this->id, 'label');
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * Persists the object
     *
     * An object that has not been saved yet will receive the id it was given
     */
    public function save(): void
    {
        $result = self::getLabelRepository()->persist($this);

        if ($result !== null) {
            $this->id = $result;
        }

        // memory_cache is on by default, so the row this object just wrote has to leave the request cache
        self::remove_from_cache('label', $this->id);
    }

    /**
     * update
     */
    public function update(array $data): ?int
    {
        // duplicate name check
        if (self::getLabelRepository()->lookup($data['name'], $this->id) !== 0) {
            return null;
        }

        $this->name     = $data['name'] ?? $this->name;
        $this->mbid     = $data['mbid'] ?? null;
        $this->category = strtolower((string) ($data['category'] ?? null));
        $this->summary  = $data['summary'] ?? null;
        $this->address  = $data['address'] ?? null;
        $this->country  = $data['country'] ?? null;
        $this->email    = $data['email'] ?? null;
        $this->website  = (isset($data['website']))
            ? filter_var(urldecode($data['website']), FILTER_VALIDATE_URL) ?: null
            : null;
        $this->active = (isset($data['active']))
            ? (bool) $data['active']
            : $this->active;

        self::getLabelRepository()->persist($this);

        return $this->id;
    }

    /**
     * @deprecated inject dependency
     */
    private function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }
}
