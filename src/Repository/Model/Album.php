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
use Ampache\Module\Album\Tag\AlbumTagUpdaterInterface;
use Ampache\Module\Art\Art;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\database_object;
use Ampache\Module\Song\Tag\SongTagWriterInterface;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\System\Core;
use Ampache\Module\Wanted\WantedManagerInterface;
use Ampache\Repository\AlbumDiskRepositoryInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Exception;

/**
 * This is the class responsible for handling the Album object
 * it is related to the album table in the database.
 */
class Album extends database_object implements
    library_item,
    displayable_item,
    container_item,
    CatalogItemInterface
{
    protected const string DB_TABLENAME = 'album';

    private static array $_mapcache   = [];
    public ?int $addition_time        = null;
    public ?int $album_artist         = null;
    public int $artist_count          = 0;
    public ?string $artist_name       = null;
    public ?string $artist_prefix     = null;
    public ?string $barcode           = null;
    public int $catalog               = 0;
    public int $catalog_id            = 0;
    public ?string $catalog_number    = null;
    public int $disk_count            = 0;
    public int $id                    = 0;
    public ?int $last_played          = null; // When this was last streamed, as a unix timestamp; null until it has been played.
    public ?string $link              = null;
    public ?string $mbid              = null; // MusicBrainz ID
    public ?string $mbid_group        = null; // MusicBrainz Release Group ID
    public ?string $name              = null;
    public ?int $original_year        = null;
    public ?string $prefix            = null;
    public ?string $release_status    = null;
    public ?string $release_type      = null;
    public int $song_artist_count     = 0;

    /** @var int[] $song_artists */
    public ?array $song_artists = null;

    public int $song_count            = 0;
    public ?int $time                 = null;
    public int $total_count           = 0;
    public int $total_skip            = 0;
    public ?string $version           = null;
    public int $year                  = 0;

    // cached information

    /** @var int[] $album_artists */
    private ?array $album_artists = null;

    private ?string $f_artist_link = null;
    private ?string $f_artist_name = null;
    private ?string $f_link        = null;

    // Prefix + Name, generated
    private ?string $f_name = null;
    private ?bool $has_art  = null;

    /** @var array<int, array{id: int, name: string, is_hidden: int, count: int}> $tags */
    private ?array $tags = null;

    /**
     * __construct
     * Album constructor it loads everything relating
     * to this album from the database it does not
     * pull the album or thumb art by default or
     * get any of the counts.
     */
    public function __construct(?int $album_id = 0)
    {
        if (!$album_id) {
            return;
        }

        $info = $this->get_info($album_id, static::DB_TABLENAME);
        if ($info === []) {
            return;
        }

        $this->addition_time     = isset($info['addition_time']) ? (int) $info['addition_time'] : null;
        $this->album_artist      = isset($info['album_artist']) ? (int) $info['album_artist'] : null;
        $this->artist_count      = (int) ($info['artist_count'] ?? 0);
        $this->artist_name       = $info['artist_name'] ?? null;
        $this->artist_prefix     = $info['artist_prefix'] ?? null;
        $this->barcode           = $info['barcode'] ?? null;
        $this->catalog           = (int) ($info['catalog'] ?? 0);
        $this->catalog_id        = (int) ($info['catalog_id'] ?? 0);
        $this->catalog_number    = $info['catalog_number'] ?? null;
        $this->disk_count        = (int) ($info['disk_count'] ?? 0);
        $this->id                = (int) ($info['id'] ?? 0);
        $this->link              = $info['link'] ?? null;
        $this->mbid              = $info['mbid'] ?? null;
        $this->mbid_group        = $info['mbid_group'] ?? null;
        $this->name              = $info['name'] ?? null;
        $this->original_year     = isset($info['original_year']) ? (int) $info['original_year'] : null;
        $this->prefix            = $info['prefix'] ?? null;
        $this->release_status    = $info['release_status'] ?? null;
        $this->release_type      = $info['release_type'] ?? null;
        $this->song_artist_count = (int) ($info['song_artist_count'] ?? 0);
        $this->song_count        = (int) ($info['song_count'] ?? 0);
        $this->time              = isset($info['time']) ? (int) $info['time'] : null;
        $this->last_played       = isset($info['last_played']) ? (int) $info['last_played'] : null;
        $this->total_count       = (int) ($info['total_count'] ?? 0);
        $this->total_skip        = (int) ($info['total_skip'] ?? 0);
        $this->version           = $info['version'] ?? null;
        $this->year              = (int) ($info['year'] ?? 0);

        // Little bit of formatting here
        if ($this->album_artist === null && $this->song_artist_count > 1) {
            $this->album_artist  = 0;
            $this->artist_prefix = '';
            $this->artist_name   = T_('Various');
            $this->f_artist_name = T_('Various');
        }
    }

    /**
     * Add the album map for a single item
     */
    public static function add_album_map(int $album_id, string $object_type, int $object_id): void
    {
        if ($album_id > 0 && $object_id > 0) {
            self::getAlbumRepository()->addAlbumMap($album_id, $object_type, $object_id);
        }
    }

    /**
     * build_cache
     * This takes an array of object ids and caches all of their information
     * with a single query
     * @param array<int|string> $ids
     */
    public static function build_cache(array $ids): bool
    {
        if ($ids === []) {
            return false;
        }

        // with the cache off these rows are discarded and the per-object queries still run, so this is a net loss
        if (!database_object::isCacheEnabled()) {
            return false;
        }

        foreach (self::getAlbumRepository()->getRowsByIds($ids) as $row) {
            parent::add_to_cache('album', $row['id'], $row);
        }

        return true;
    }

    /**
     * check
     *
     * Searches for an album; if none is found, insert a new one.
     */
    public static function check(
        int $catalog_id,
        string $name,
        int $year = 0,
        ?string $mbid = null,
        ?string $mbid_group = null,
        ?int $album_artist = null,
        ?string $release_type = null,
        ?string $release_status = null,
        ?int $original_year = null,
        ?string $barcode = null,
        ?string $catalog_number = null,
        ?string $version = null,
        bool $readonly = false,
    ): int {
        $trimmed      = Catalog::trim_prefix(trim($name));
        $name         = $trimmed['string'];
        $prefix       = $trimmed['prefix'];
        $album_artist = (int) $album_artist;
        $album_artist = ($album_artist < 1) ? null : $album_artist;

        $mbid           = (empty($mbid)) ? null : $mbid;
        $mbid_group     = (empty($mbid_group)) ? null : $mbid_group;
        $release_type   = (empty($release_type)) ? null : $release_type;
        $release_status = (empty($release_status)) ? null : $release_status;
        $original_year  = ((int) substr((string) $original_year, 0, 4) < 1)
            ? null
            : substr((string) $original_year, 0, 4);
        $barcode        = (empty($barcode)) ? null : $barcode;
        $catalog_number = (empty($catalog_number)) ? null : $catalog_number;
        $version        = (empty($version)) ? null : $version;

        if (!$name || $name === T_('Unknown (Orphaned)')) {
            $name          = T_('Unknown (Orphaned)');
            $year          = 0;
            $original_year = null;
            $album_artist  = Artist::check(T_('Unknown (Orphaned)'));
            $catalog_id    = 0;
        }

        if (isset(self::$_mapcache[$name][$year][$album_artist ?? ''][$mbid ?? ''][$mbid_group ?? ''][$release_type ?? ''][$release_status ?? ''][$original_year ?? ''][$barcode ?? ''][$catalog_number ?? ''][$version ?? ''])) {
            return self::$_mapcache[$name][$year][$album_artist ?? ''][$mbid ?? ''][$mbid_group ?? ''][$release_type ?? ''][$release_status ?? ''][$original_year ?? ''][$barcode ?? ''][$catalog_number ?? ''][$version ?? ''];
        }

        $properties = [
            'name' => $name,
            'prefix' => $prefix,
            'year' => $year,
            'mbid' => $mbid,
            'mbid_group' => $mbid_group,
            'release_type' => $release_type,
            'release_status' => $release_status,
            'album_artist' => $album_artist,
            'original_year' => $original_year,
            'barcode' => $barcode,
            'catalog_number' => $catalog_number,
            'version' => $version,
            'catalog' => $catalog_id,
        ];

        $album_id = self::getAlbumRepository()->findByProperties($properties);
        if ($album_id > 0) {
            // cache the album id against it's details
            self::$_mapcache[$name][$year][$album_artist ?? ''][$mbid ?? ''][$mbid_group ?? ''][$release_type ?? ''][$release_status ?? ''][$original_year ?? ''][$barcode ?? ''][$catalog_number ?? ''][$version ?? ''] = $album_id;

            return $album_id;
        }

        if ($readonly) {
            return 0;
        }

        $album_id = self::getAlbumRepository()->create($properties, time());
        if (!$album_id) {
            return 0;
        }
        debug_event(self::class, sprintf('check album: created {%s}', $album_id), 4);
        // map the new id
        Catalog::update_map($catalog_id, 'album', (int) $album_id);
        // Remove from wanted album list if any request on it
        if (!empty($mbid) && AmpConfig::get('wanted')) {
            $user = Core::get_global('user');

            try {
                if ($user instanceof User) {
                    self::getWantedManager()->delete(
                        $mbid,
                        $user
                    );
                }
            } catch (Exception $error) {
                debug_event(self::class, 'Cannot process wanted releases auto-removal check: ' . $error->getMessage(), 2);
            }
        }

        self::$_mapcache[$name][$year][$album_artist ?? ''][$mbid ?? ''][$mbid_group ?? ''][$release_type ?? ''][$release_status ?? ''][$original_year ?? ''][$barcode ?? ''][$catalog_number ?? ''][$version ?? ''] = $album_id;

        return (int) $album_id;
    }

    /**
     * Delete the album map for a single item if this was the last track
     */
    public static function check_album_map(int $album_id, string $object_type, int $object_id): bool
    {
        if ($album_id > 0 && $object_id > 0) {
            // Remove the album_map if this was the last track
            return self::getAlbumRepository()->removeUnusedAlbumMap($album_id, $object_type, $object_id);
        }

        return false;
    }

    /**
     * Get parent album artists.
     * @return int[]
     */
    public static function get_parent_array(int $album_id, ?int $primary_id = null, string $object_type = 'album'): array
    {
        $results = self::getAlbumRepository()->getMappedObjectIds($album_id, $object_type);
        $primary = ((int) $primary_id > 0)
            ? [(int) $primary_id]
            : [];

        return array_unique(array_merge($primary, $results));
    }

    /**
     * Orphans can be annoying
     */
    public static function is_orphan(int $album_id = 0): bool
    {
        return $album_id > 0 && self::getAlbumRepository()->isOrphan($album_id);
    }

    /**
     * Delete the album map for a single item
     */
    public static function remove_album_map(int $album_id, string $object_type, int $object_id): void
    {
        if ($album_id > 0 && $object_id > 0) {
            self::getAlbumRepository()->removeAlbumMap($album_id, $object_type, $object_id);
        }
    }

    /**
     * sanitize_disk
     * Change letter disk numbers (like vinyl/cassette) to an integer
     */
    public static function sanitize_disk(int|string|null $disk): int
    {
        if ($disk === null || $disk === '') {
            return 0;
        }

        if ((int) $disk == 0) {
            // A is 0 but we want to start at disk 1
            $alphabet = range('A', 'Z');
            $disk     = (int) array_search(strtoupper((string) $disk), $alphabet, true) + 1;
        }

        return (int) $disk;
    }

    /**
     * update_album_artist
     *
     * find albums that are missing an album_artist and generate one.
     */
    public static function update_album_artist(): void
    {
        $albumRepository = self::getAlbumRepository();

        // Find all albums that are missing an album artist
        foreach ($albumRepository->getIdsMissingAlbumArtist() as $album_id) {
            // these are albums that only have 1 artist
            $artist_id = $albumRepository->getSoleSongArtistId($album_id) ?? 0;

            // Update the album
            if ($artist_id > 0) {
                debug_event(self::class, 'Found album_artist {' . $artist_id . '} for: ' . $album_id, 5);
                self::_update_field(AlbumFieldEnum::ALBUM_ARTIST, $artist_id, $album_id);
                Artist::add_artist_map($artist_id, 'album', $album_id);
                self::add_album_map($album_id, 'album', $artist_id);
            }
        }
    }

    /**
     * update_album_count
     *
     * Called this after inserting a new song to keep stats correct right away
     */
    public static function update_album_count(int $album_id): void
    {
        debug_event(self::class, 'update_album_count ' . $album_id, 5);
        self::getAlbumRepository()->updateCounts($album_id);
    }

    /**
     * update_table_counts
     * Update all albums with mapping and missing data after catalog changes
     */
    public static function update_table_counts(): void
    {
        debug_event(self::class, 'update_table_counts', 5);
        self::getAlbumRepository()->updateAllCounts();
    }

    /**
     * Update an album field.
     */
    private static function _update_field(AlbumFieldEnum $field, int|string|null $value, int $album_id): void
    {
        self::getAlbumRepository()->setField($album_id, $field, $value);
    }

    /**
     * @deprecated
     */
    private static function getAlbumRepository(): AlbumRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumRepositoryInterface::class);
    }

    /**
     * @deprecated Inject dependency
     */
    private static function getWantedManager(): WantedManagerInterface
    {
        global $dic;

        return $dic->get(WantedManagerInterface::class);
    }

    /**
     * display_art
     * @param array{width: int, height: int} $size
     */
    public function display_art(array $size, bool $force = false): void
    {
        if (Art::has_db($this->id, 'album')) {
            $title = ($this->get_parent_fullname() != "")
                ? '[' . $this->get_parent_fullname() . '] ' . $this->get_fullname()
                : $this->get_fullname();

            Art::display('album', $this->id, $title, $size, $this->get_link());
        } elseif ($this->album_artist && (Art::has_db($this->album_artist, 'artist') || $force)) {
            $title = ($this->get_parent_fullname() != "")
                ? '[' . $this->get_parent_fullname() . '] ' . $this->get_fullname()
                : $this->get_fullname();

            Art::display('artist', $this->album_artist, $title, $size, $this->get_link());
        }
    }

    /**
     * findAlbumArtist
     * Certain albums may have a single artist and not have any albumartist tags
     */
    public function findAlbumArtist(): ?int
    {
        if (
            $this->isNew() === false
            && !$this->album_artist
            && $this->song_artist_count == 1
        ) {
            $results = self::getAlbumRepository()->findSoleSongArtist($this->id);
            // overwrite so you can get something
            $this->album_artist  = $results['album_artist'] ?? null;
            $this->artist_prefix = $results['artist_prefix'] ?? null;
            $this->artist_name   = $results['artist_name'] ?? null;
        }

        return $this->album_artist;
    }

    /**
     * does the item have a single album artist and song artist?
     */
    public function get_artist_count(): int
    {
        return self::getAlbumRepository()->getArtistCount($this->id);
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
            $this->album_artists = self::get_parent_array($this->id, $this->album_artist);
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
        $artist = new Artist($this->album_artist);

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
     * Return a formatted link to the parent object (if appliccable)
     */
    public function get_f_parent_link(): ?string
    {
        // don't do anything if it's formatted
        if ($this->f_artist_link === null) {
            if ($this->album_artist === 0) {
                $this->f_artist_link = sprintf('<span title="%d ', $this->artist_count) . T_('Artists') . "\">" . T_('Various') . "</span>";
            } elseif ($this->album_artist !== null) {
                $web_path = AmpConfig::get_web_path('/client');

                $this->f_artist_link = '';
                if (!$this->album_artists) {
                    $this->get_artists();
                }

                if ($this->album_artists !== null) {
                    foreach ($this->album_artists as $artist_id) {
                        $artist_fullname = scrub_out(Artist::get_fullname_by_id($artist_id));
                        if (!empty($artist_fullname)) {
                            $this->f_artist_link .= "<a href=\"" . $web_path . '/artists.php?action=show&artist=' . $artist_id . "\" title=\"" . $artist_fullname . "\">" . $artist_fullname . "</a>,&nbsp";
                        }
                    }

                    $this->f_artist_link = rtrim($this->f_artist_link, ",&nbsp");
                } else {
                    $this->f_artist_link = '';
                }
            } else {
                $this->f_artist_link = '';
            }
        }

        return $this->f_artist_link;
    }

    /**
     * Get item f_tags.
     */
    public function get_f_tags(): string
    {
        return Tag::get_display($this->get_tags(), true, 'album');
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
            return trim(trim($this->prefix ?? '') . ' ' . trim($this->name ?? ''));
        }

        if ($force_year) {
            $f_name = trim(trim($this->prefix ?? '') . ' ' . trim($this->name ?? ''));
            if ($this->version && AmpConfig::get('show_subtitle')) {
                $f_name .= " [" . $this->version . "]";
            }

            if ($this->year > 0) {
                $f_name .= " (" . $this->year . ")";
            }

            return $f_name;
        }

        // don't do anything if it's formatted
        if ($this->f_name === null) {
            $this->f_name = trim(trim($this->prefix ?? '') . ' ' . trim($this->name ?? ''));
            if ($this->version && AmpConfig::get('show_subtitle')) {
                $this->f_name .= " [" . $this->version . "]";
            }

            // Album pages should show a year and looking if we need to display the release year
            if ($this->original_year && AmpConfig::get('show_original_year') && $this->original_year != $this->year && $this->year > 0) {
                $this->f_name .= " (" . $this->year . ")";
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

            $this->link = $web_path . '/albums.php?action=show&album=' . $this->id;
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
            $songs = $this->getSongRepository()->getByAlbum($this->id);
            foreach ($songs as $song_id) {
                $medias[] = [
                    'object_type' => LibraryItemEnum::SONG,
                    'object_id' => $song_id,
                ];
            }
        }

        return $medias;
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
     * Get the album artist fullname.
     */
    public function get_parent_fullname(): string
    {
        if ($this->f_artist_name === null) {
            $this->findAlbumArtist();
            if ($this->album_artist === 0) {
                $this->artist_prefix = '';
                $this->artist_name   = T_('Various');
                $this->f_artist_name = T_('Various');
            } elseif ($this->album_artist > 0) {
                $name_array          = Artist::get_name_array_by_id($this->album_artist);
                $this->artist_prefix = $name_array['prefix'];
                $this->artist_name   = $name_array['basename'];
                $this->f_artist_name = $name_array['name'];
            } else {
                $this->artist_prefix = '';
                $this->artist_name   = '';
                $this->f_artist_name = '';
            }
        }

        return (string) $this->f_artist_name;
    }

    /**
     * Get item song_artists array
     * @return int[]
     */
    public function get_song_artists(): array
    {
        if (empty($this->song_artists)) {
            $this->song_artists = self::get_parent_array($this->id, 0, 'song');
        }

        return $this->song_artists ?? [];
    }

    /**
     * get_songs
     *
     * Get each song id for the album
     * @return int[]
     */
    public function get_songs(): array
    {
        return self::getAlbumRepository()->getSongIds($this->id);
    }

    /**
     * Get item tags.
     * @return array<int, array{id: int, name: string, is_hidden: int, count: int}>
     */
    public function get_tags(): array
    {
        if ($this->tags === null) {
            $this->tags = Tag::get_top_tags('album', $this->id);
        }

        return $this->tags ?? [];
    }

    /**
     * Get item's owner.
     */
    public function get_user_owner(): ?int
    {
        if (!$this->album_artist) {
            return null;
        }

        $artist = new Artist($this->album_artist);

        return $artist->get_user_owner();
    }

    /**
     * Returns the albums artist id
     */
    public function getAlbumArtist(): int
    {
        return $this->album_artist ?? 0;
    }

    /**
     * Returns the id of the catalog the item is associated to
     */
    public function getCatalogId(): int
    {
        return $this->catalog;
    }

    /**
     * Returns the amount of discs associated to the album
     */
    public function getDiskCount(): int
    {
        return $this->disk_count;
    }

    /**
     * @return iterable<AlbumDisk>
     */
    public function getDisks(): iterable
    {
        return $this->getAlbumDiskRepository()->getByAlbum($this);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::ALBUM;
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
            $this->has_art = Art::has_db($this->id, 'album');
        }

        return $this->has_art ?? false;
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
        $name           = $data['name'] ?? $this->name;
        $album_artist   = (isset($data['album_artist']) && (int) $data['album_artist'] > 0) ? (int) $data['album_artist'] : null;
        $year           = (int) ($data['year'] ?? 0);
        $mbid           = $data['mbid'] ?? null;
        $mbid_group     = $data['mbid_group'] ?? null;
        $release_type   = $data['release_type'] ?? null;
        $release_status = $data['release_status'] ?? null;
        $original_year  = (empty($data['original_year']))
            ? null
            : (int) $data['original_year'];
        $barcode        = $data['barcode'] ?? null;
        $catalog_number = $data['catalog_number'] ?? null;
        $version        = $data['version'] ?? null;

        // If you have created an album_artist using 'add new...' we need to create a new artist
        if (array_key_exists('artist_name', $data) && !empty($data['artist_name'])) {
            $album_artist = Artist::check($data['artist_name']);
            if ($album_artist !== null) {
                self::_update_field(AlbumFieldEnum::ALBUM_ARTIST, $album_artist, $this->id);
                $this->album_artist = $album_artist;
            }
        }

        $current_id = $this->id;
        $updated    = false;
        $songs      = $this->getSongRepository()->getByAlbum($this->id);
        // run an album check on the current object READONLY means that it won't insert a new album
        $album_id = self::check(
            $this->catalog,
            $name,
            $year,
            $mbid,
            $mbid_group,
            $album_artist,
            $release_type,
            $release_status,
            $original_year,
            $barcode,
            $catalog_number,
            $version,
            true
        );

        $cron_cache = AmpConfig::get('cron_cache');
        if ($album_id > 0 && $album_id != $this->id) {
            debug_event(self::class, sprintf('Updating %d to new id and migrating stats {', $this->id) . $album_id . '}.', 4);

            foreach ($songs as $song_id) {
                Song::update_album($album_id, $song_id, $this->id, false);
                Song::update_year($year, $song_id);
            }

            self::update_table_counts();
            $current_id = $album_id;
            $updated    = true;
            if (!$cron_cache) {
                self::getAlbumRepository()->collectGarbage();
            }
        } else {
            // run updates on the single fields
            if (!empty($name) && $name != $this->get_fullname()) {
                $trimmed  = Catalog::trim_prefix(trim((string) $name));
                $new_name = $trimmed['string'];
                $aPrefix  = $trimmed['prefix'];

                self::_update_field(AlbumFieldEnum::NAME, $new_name, $this->id);
                self::_update_field(AlbumFieldEnum::PREFIX, $aPrefix, $this->id);

                $this->name   = $new_name;
                $this->prefix = $aPrefix;
            }

            if ($year !== $this->year) {
                self::_update_field(AlbumFieldEnum::YEAR, $year, $this->id);
                foreach ($songs as $song_id) {
                    Song::update_year($year, $song_id);
                }

                $updated = true;
            }

            // AlbumDisk update
            if ($this->disk_count === 1) {
                $disk = $this->getAlbumDiskRepository()->getByAlbum($this);
                if (count($disk) === 1) {
                    $disk_id    = $disk[0]->getId();
                    $disk_check = AlbumDisk::check(
                        $this->id,
                        (int) ($data['disk'] ?? $disk[0]->disk),
                        $this->catalog,
                        $data['disksubtitle'] ?? $disk[0]->disksubtitle,
                        $disk_id
                    );

                    if ($disk_check !== $disk_id) {
                        $updated = true;
                    }
                }
            }

            if ($mbid != $this->mbid) {
                self::_update_field(AlbumFieldEnum::MBID, $mbid, $this->id);
            }

            if ($mbid_group != $this->mbid_group) {
                self::_update_field(AlbumFieldEnum::MBID_GROUP, $mbid_group, $this->id);
            }

            if ($album_artist !== $this->album_artist) {
                self::_update_field(AlbumFieldEnum::ALBUM_ARTIST, $album_artist, $this->id);
                self::add_album_map($this->id, 'album', (int) $album_artist);
                self::remove_album_map($this->id, 'album', (int) $this->album_artist);
            }

            if ($release_type != $this->release_type) {
                self::_update_field(AlbumFieldEnum::RELEASE_TYPE, $release_type, $this->id);
            }

            if ($release_type != $this->release_status) {
                self::_update_field(AlbumFieldEnum::RELEASE_STATUS, $release_status, $this->id);
            }

            if ($original_year !== $this->original_year) {
                self::_update_field(AlbumFieldEnum::ORIGINAL_YEAR, $original_year, $this->id);
            }

            if ($barcode != $this->barcode) {
                self::_update_field(AlbumFieldEnum::BARCODE, $barcode, $this->id);
            }

            if ($catalog_number != $this->catalog_number) {
                self::_update_field(AlbumFieldEnum::CATALOG_NUMBER, $catalog_number, $this->id);
            }

            if ($version != $this->version) {
                self::_update_field(AlbumFieldEnum::VERSION, $version, $this->id);
            }
        }

        $this->year           = $year;
        $this->mbid           = $mbid;
        $this->mbid_group     = $mbid_group;
        $this->album_artist   = $album_artist;
        $this->release_type   = $release_type;
        $this->release_status = $release_status;
        $this->original_year  = $original_year;
        $this->barcode        = $barcode;
        $this->catalog_number = $catalog_number;
        $this->version        = $version;

        if ($updated && !empty($songs)) {
            $time       = time();
            $write_tags = AmpConfig::get('write_tags', false);
            foreach ($songs as $song_id) {
                Song::update_utime($song_id, $time);
                if ($write_tags) {
                    $song = new Song($song_id);
                    $this->getSongTagWriter()->write($song);
                }
            }

            if (!$cron_cache) {
                Stats::garbage_collection();
                Rating::garbage_collection();
                Userflag::garbage_collection();
                $this->getUseractivityRepository()->collectGarbage();
            }
        } // if updated

        $override_childs = false;
        if (array_key_exists('overwrite_childs', $data) && $data['overwrite_childs'] == 'checked') {
            $override_childs = true;
        }

        $add_to_childs = false;
        if (array_key_exists('add_to_childs', $data) && $data['add_to_childs'] == 'checked') {
            $add_to_childs = true;
        }

        if (isset($data['edit_tags'])) {
            $this->getAlbumTagUpdater()->updateTags(
                $this,
                $data['edit_tags'],
                $override_childs,
                $add_to_childs,
                true
            );
        }

        if (isset($data['edit_moods'])) {
            // no from_file_tags, so these belong to whoever is editing and outlive the next scan
            Mood::update_mood_list((string) $data['edit_moods'], 'album', $this->id, true);
        }

        return $current_id;
    }

    /**
     * @inject dependency
     */
    private function getAlbumDiskRepository(): AlbumDiskRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumDiskRepositoryInterface::class);
    }

    /**
     * @deprecated
     */
    private function getAlbumTagUpdater(): AlbumTagUpdaterInterface
    {
        global $dic;

        return $dic->get(AlbumTagUpdaterInterface::class);
    }

    /**
     * @deprecated
     */
    private function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }

    /**
     * @deprecated
     */
    private function getSongTagWriter(): SongTagWriterInterface
    {
        global $dic;

        return $dic->get(SongTagWriterInterface::class);
    }

    /**
     * @deprecated Inject dependency
     */
    private function getUseractivityRepository(): UserActivityRepositoryInterface
    {
        global $dic;

        return $dic->get(UserActivityRepositoryInterface::class);
    }
}
