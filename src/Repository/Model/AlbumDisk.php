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
use Ampache\Module\System\Dba;
use Ampache\Repository\AlbumDiskRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;

/**
 * This is the class responsible for handling the Album object
 * it is related to the album table in the database.
 */
class AlbumDisk extends database_object implements
    library_item,
    displayable_item,
    container_item,
    CatalogItemInterface
{
    protected const string DB_TABLENAME = 'album_disk';

    public ?int $addition_time = null;
    public ?int $album_artist;
    public int $album_id     = 0;
    public int $artist_count = 0;
    public ?string $barcode;
    public int $catalog;
    public int $catalog_id = 0;
    public ?string $catalog_number;
    public int $disk;
    public int $disk_count       = 0;
    public ?string $disksubtitle = null;
    public int $id               = 0;
    public ?string $link         = null;
    public ?string $mbid; // MusicBrainz ID
    public ?string $mbid_group; // MusicBrainz Release Group ID

    /**
     * Variables from parent Album
     */
    public ?string $name;

    public ?int $original_year;
    public ?string $prefix;
    public ?string $release_status;
    public ?string $release_type;
    public int $song_artist_count = 0;
    public int $song_count        = 0;
    public ?int $time             = null;
    public int $total_count       = 0;
    public int $total_skip        = 0;
    public ?string $version;
    public ?int $year;
    private Album $album;

    /** @var int[] $album_artists */
    private ?array $album_artists = null;

    private ?string $f_artist_link = null;
    private ?string $f_artist_name = null;
    private ?string $f_link        = null;

    // Prefix + Name, generated
    private ?string $f_name = null;
    private ?bool $has_art  = null;

    /** @var array<int, array{id: int, name: string, user: int, count: int}> $moods */
    private ?array $moods = null;

    /** @var array<int, array{id: int, name: string, is_hidden: int, count: int}> $tags */
    private ?array $tags = null;

    /**
     * __construct
     * Album constructor it loads everything relating
     * to this album from the database it does not
     * pull the album or thumb art by default or
     * get any of the counts.
     */
    public function __construct(?int $album_disk_id = 0)
    {
        if (!$album_disk_id) {
            $this->album = new Album();

            return;
        }

        $info = $this->get_info($album_disk_id, static::DB_TABLENAME);
        if ($info === []) {
            $this->album = new Album();

            return;
        }

        // make sure the album is valid before going further
        $this->album = new Album($info['album_id']);
        if ($this->album->isNew()) {
            return;
        }

        $this->id           = (int) ($info['id'] ?? 0);
        $this->album_id     = (int) ($info['album_id'] ?? 0);
        $this->catalog      = (int) ($info['catalog'] ?? 0);
        $this->disk         = (int) ($info['disk'] ?? 0);
        $this->disk_count   = (int) ($info['disk_count'] ?? 0);
        $this->disksubtitle = $info['disksubtitle'] ?? null;
        $this->song_count   = (int) ($info['song_count'] ?? 0);
        $this->time         = isset($info['time']) ? (int) $info['time'] : null;
        $this->total_count  = (int) ($info['total_count'] ?? 0);
        $this->total_skip   = (int) ($info['total_skip'] ?? 0);

        // set the album variables just in case
        $this->name              = $this->album->name;
        $this->prefix            = $this->album->prefix;
        $this->mbid              = $this->album->mbid;
        $this->year              = $this->album->year;
        $this->mbid_group        = $this->album->mbid_group;
        $this->release_type      = $this->album->release_type;
        $this->album_artist      = $this->album->album_artist;
        $this->original_year     = $this->album->original_year;
        $this->barcode           = $this->album->barcode;
        $this->catalog_number    = $this->album->catalog_number;
        $this->version           = $this->album->version;
        $this->release_status    = $this->album->release_status;
        $this->addition_time     = $this->album->addition_time;
        $this->artist_count      = $this->album->artist_count;
        $this->song_artist_count = $this->album->song_artist_count;
    }

    /**
     * build_cache
     * @param int[]|string[] $ids
     */
    public static function build_cache(array $ids): bool
    {
        if ($ids === []) {
            return false;
        }
        if (!database_object::isCacheEnabled()) {
            return false;
        }

        $idlist     = implode(',', array_map('intval', $ids));
        $album_ids  = [];
        $db_results = Dba::read(sprintf('SELECT * FROM `album_disk` WHERE `id` IN (%s)', $idlist));
        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('album_disk', (int) $row['id'], $row);
            if (isset($row['album_id'])) {
                $album_ids[(int) $row['album_id']] = (int) $row['album_id'];
            }
        }

        // warm parent albums so the constructor's new Album() hits cache
        if ($album_ids !== []) {
            Album::build_cache(array_values($album_ids));
        }

        return true;
    }

    /**
     * check
     *
     * Insert album_disk and do additional steps for data on insert
     */
    public static function check(int $album_id, int $disk, int $catalog_id, ?string $disksubtitle = null, ?int $current_id = null): int
    {
        return self::getAlbumDiskRepository()->check($album_id, $disk, $catalog_id, $disksubtitle, $current_id);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getAlbumDiskRepository(): AlbumDiskRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumDiskRepositoryInterface::class);
    }

    /**
     * display_art
     * @param array{width: int, height: int} $size
     */
    public function display_art(array $size, bool $force = false): void
    {
        if (Art::has_db($this->album_id, 'album')) {
            $title = (!empty($this->get_parent_fullname()))
                ? '[' . $this->get_parent_fullname() . '] ' . $this->get_fullname()
                : $this->get_fullname();

            Art::display('album', $this->album_id, $title, $size, $this->get_link());
        } elseif (
            $this->album->album_artist
            && (
                Art::has_db($this->album->album_artist, 'artist')
                || $force
            )
        ) {
            $title = (!empty($this->get_parent_fullname()))
                ? '[' . $this->get_parent_fullname() . '] ' . $this->get_fullname()
                : $this->get_fullname();

            Art::display('artist', $this->album->album_artist, $title, $size, $this->get_link());
        }
    }

    /**
     * does the item have a single album artist and song artist?
     */
    public function get_artist_count(): int
    {
        return $this->getAlbumDiskRepository()->getArtistCount($this);
    }

    /**
     * Get item album_artists array
     * @return int[]
     */
    public function get_artists(): array
    {
        if (!$this->album_artist) {
            return [];
        }

        if (
            $this->album_artists === null
            || $this->album_artists === []
        ) {
            $this->album_artists = $this->album->get_artists();
        }

        return $this->album_artists;
    }

    /**
     * Get default art kind for this item.
     */
    public function get_default_art_kind(): string
    {
        return 'default';
    }

    /**
     * get_description
     */
    public function get_description(): string
    {
        // Album description is not supported yet, always return artist description
        $artist = new Artist($this->album->album_artist);

        return $artist->get_description();
    }

    /**
     * Get item f_link.
     */
    public function get_f_link(?string $title = null): string
    {
        // don't do anything if it's formatted
        if ($this->f_link === null) {
            $this->f_link = "<a href=\"" . $this->get_link() . "\" title=\"" . scrub_out($this->get_fullname()) . "\">" . scrub_out($title ?? $this->get_fullname()) . "</a>";
        }

        return $this->f_link;
    }

    /**
     * Get item f_moods.
     */
    public function get_f_moods(): string
    {
        return Mood::get_display($this->get_moods(), true, 'album_disk');
    }

    /**
     * Return a formatted link to the parent object (if appliccable)
     */
    public function get_f_parent_link(): ?string
    {
        // don't do anything if it's formatted
        if ($this->f_artist_link === null) {
            $this->f_artist_link = $this->album->get_f_parent_link();
        }

        return $this->f_artist_link;
    }

    /**
     * Get item f_tags.
     */
    public function get_f_tags(): string
    {
        return Tag::get_display($this->get_tags(), true, 'album_disk');
    }

    /**
     * Get item f_time or f_time_h.
     */
    public function get_f_time(): string
    {
        return '';
    }

    /**
     * Get item fullname.
     */
    public function get_fullname(bool $simple = false, bool $force_year = false): string
    {
        // return the basic name without all the wild formatting
        if ($simple) {
            return trim(trim($this->album->prefix ?? '') . ' ' . trim($this->album->name ?? ''));
        }

        if ($force_year) {
            $f_name = trim(trim($this->album->prefix ?? '') . ' ' . trim($this->album->name ?? ''));
            if ($this->album->year > 0) {
                $f_name .= " (" . $this->album->year . ")";
            }

            if ($this->disk_count > 1) {
                $f_name .= " [" . T_('Disk') . " " . $this->disk . "]";
            }

            return $f_name;
        }

        // don't do anything if it's formatted
        if ($this->f_name === null) {
            $this->f_name = trim(trim($this->album->prefix ?? '') . ' ' . trim($this->album->name ?? ''));
            // Album pages should show a year and looking if we need to display the release year
            if ($this->album->original_year && AmpConfig::get('use_original_year') && $this->album->original_year != $this->album->year && $this->album->year > 0) {
                $this->f_name .= " (" . $this->album->year . ")";
            }

            if ($this->disk_count > 1) {
                $this->f_name .= " [" . T_('Disk') . " " . $this->disk . "]";
            }
        }

        return $this->f_name;
    }

    /**
     * Get item keywords for metadata searches.
     * @return array<string, array{important: bool, label: string, value: string}>
     */
    public function get_keywords(): array
    {
        return [
            'mb_albumid' => [
                'important' => false,
                'label' => T_('Album MusicBrainzID'),
                'value' => (string) $this->mbid,
            ],
            'mb_albumid_group' => [
                'important' => false,
                'label' => T_('Release Group MusicBrainzID'),
                'value' => (string) $this->mbid_group,
            ],
            'artist' => [
                'important' => true,
                'label' => T_('Artist'),
                'value' => $this->get_parent_fullname(),
            ],
            'album' => [
                'important' => true,
                'label' => T_('Album'),
                'value' => $this->get_fullname(true),
            ],
            'year' => [
                'important' => false,
                'label' => T_('Year'),
                'value' => (string) $this->year,
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

            $this->link = $web_path . '/albums.php?action=show_disk&album_disk=' . $this->id;
        }

        return $this->link ?? '';
    }

    /**
     * Get all children and sub-childrens media.
     *
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function get_medias(?string $filter_type = null): array
    {
        $medias = [];
        if (!$filter_type || $filter_type === 'song') {
            $songs = $this->getSongRepository()->getByAlbumDisk($this->id);
            foreach ($songs as $song_id) {
                $medias[] = ['object_type' => LibraryItemEnum::SONG, 'object_id' => $song_id];
            }
        }

        return $medias;
    }

    /**
     * Get item moods. The moods a scan writes land on the album, so a disk weighs its album's.
     * @return array<int, array{id: int, name: string, user: int, count: int}>
     */
    public function get_moods(): array
    {
        if ($this->moods === null) {
            $this->moods = Mood::get_top_moods('album', $this->album_id, 0);
        }

        return $this->moods;
    }

    /**
     * @return null|array{object_type: LibraryItemEnum, object_id: int}
     */
    public function get_parent(): ?array
    {
        if (!empty($this->album_artist)) {
            return [
                'object_type' => LibraryItemEnum::ARTIST,
                'object_id' => (int) $this->album_artist,
            ];
        }

        return null;
    }

    /**
     * Get item album_artist fullname.
     */
    public function get_parent_fullname(): string
    {
        if ($this->f_artist_name === null) {
            $this->f_artist_name = $this->album->get_parent_fullname();
        }

        return $this->f_artist_name ?? '';
    }

    /**
     * Get item song_artists array
     * @return int[]
     */
    public function get_song_artists(): array
    {
        return $this->album->get_song_artists();
    }

    /**
     * get_songs
     *
     * Get each song id for the album_disk
     * @return int[]
     */
    public function get_songs(): array
    {
        return $this->getAlbumDiskRepository()->getSongs($this);
    }

    /**
     * Get item tags.
     * @return array<int, array{id: int, name: string, is_hidden: int, count: int}>
     */
    public function get_tags(): array
    {
        if ($this->tags === null) {
            $this->tags = Tag::get_top_tags('album', $this->album_id);
        }

        return $this->tags ?? [];
    }

    /**
     * Get item's owner.
     */
    public function get_user_owner(): ?int
    {
        if (!$this->album->album_artist) {
            return null;
        }

        $artist = new Artist($this->album->album_artist);

        return $artist->get_user_owner();
    }

    public function getAlbumId(): int
    {
        return $this->album_id;
    }

    /**
     * Returns the id of the catalog the item is associated to
     */
    public function getCatalogId(): int
    {
        return $this->catalog;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::ALBUM_DISK;
    }

    /**
     * getYear
     */
    public function getYear(): string
    {
        return (string) ($this->year ?: '');
    }

    /**
     * does the item have art?
     */
    public function has_art(): bool
    {
        if ($this->has_art === null) {
            $this->has_art = Art::has_db($this->album_id, 'album');
        }

        return $this->has_art;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * update
     * This function takes a key'd array of data and updates this object
     * as needed
     */
    public function update(array $data): int
    {
        //debug_event(self::class, "update: " . print_r($data, true), 4);
        $album_id     = $this->album->update($data);
        $disk         = (int) ($data['disk'] ?? $this->disk);
        $catalog      = (int) ($data['catalog'] ?? $this->catalog);
        $disksubtitle = $data['disksubtitle'] ?? $this->disksubtitle;

        return self::check($album_id, $disk, $catalog, $disksubtitle, $this->id);
    }

    /**
     * @deprecated
     */
    private function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }
}
