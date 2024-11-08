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

use Ampache\Module\Album\Tag\AlbumTagUpdaterInterface;
use Ampache\Module\Song\Tag\SongTagWriterInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\AlbumDiskRepositoryInterface;
use Ampache\Module\Wanted\WantedManagerInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Exception;

/**
 * This is the class responsible for handling the Album object
 * it is related to the album table in the database.
 */
class Album extends database_object implements library_item, CatalogItemInterface
{
    protected const DB_TABLENAME = 'album';

    public int $id = 0;

    public ?string $name = null;

    public ?string $prefix = null;

    public ?string $mbid = null; // MusicBrainz ID

    public int $year;

    public int $disk_count = 0;

    public ?string $mbid_group = null; // MusicBrainz Release Group ID

    public ?string $release_type = null;

    public ?int $album_artist = null;

    public ?int $original_year = null;

    public ?string $barcode = null;

    public ?string $catalog_number = null;

    public ?string $version = null;

    public ?int $time = null;

    public ?string $release_status = null;

    public int $addition_time;

    public int $catalog;

    public int $total_count;

    public int $song_count;

    public int $artist_count;

    public int $song_artist_count;

    public ?string $link = null;

    /** @var int[] $album_artists */
    public ?array $album_artists = null;

    /** @var int[] $song_artists */
    public ?array $song_artists = null;

    /** @var int $total_duration */
    public $total_duration;

    /** @var int $catalog_id */
    public $catalog_id;

    /** @var string $artist_prefix */
    public $artist_prefix;

    /** @var string $artist_name */
    public $artist_name;

    /** @var array $tags */
    public $tags;

    /** @var null|string $f_artist_name */
    public $f_artist_name;

    /** @var null|string $f_artist_link */
    public $f_artist_link;

    /** @var null|string $f_artist */
    public $f_artist;

    /** @var null|string $f_name // Prefix + Name, generated */
    public $f_name;

    /** @var null|string $f_link */
    public $f_link;

    /** @var null|string $f_tags */
    public $f_tags;

    /** @var null|string $f_year */
    public $f_year;

    /** @var null|string $f_year_link */
    public $f_year_link;

    /** @var null|string $f_release_type */
    public $f_release_type;

    /** @var int $song_id */
    public $song_id;

    /** @var int $artist_id */
    public $artist_id;

    // cached information

    /** @var bool $_fake */
    public $_fake;

    /** @var array $_songs */
    public $_songs = [];

    private ?bool $has_art = null;

    /** @var array $_mapcache */
    private static $_mapcache = [];

    /**
     * __construct
     * Album constructor it loads everything relating
     * to this album from the database it does not
     * pull the album or thumb art by default or
     * get any of the counts.
     * @param int|null $album_id
     */
    public function __construct($album_id = 0)
    {
        if (!$album_id) {
            return;
        }

        $info = $this->get_info($album_id, static::DB_TABLENAME);
        if ($info === []) {
            return;
        }

        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        // Little bit of formatting here
        $this->total_duration    = (int)$this->time;

        if ($this->album_artist === null && $this->song_artist_count > 1) {
            $this->album_artist  = 0;
            $this->artist_prefix = '';
            $this->artist_name   = T_('Various');
            $this->f_artist_name = T_('Various');
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
     * Returns the amount of discs associated to the album
     */
    public function getDiskCount(): int
    {
        return $this->disk_count;
    }

    /**
     * Returns the albums artist id
     */
    public function getAlbumArtist(): int
    {
        return $this->album_artist ?? 0;
    }

    /**
     * build_cache
     * This takes an array of object ids and caches all of their information
     * with a single query
     * @param list<int> $ids
     */
    public static function build_cache(array $ids): bool
    {
        if ($ids === []) {
            return false;
        }

        $idlist     = '(' . implode(',', $ids) . ')';
        $sql        = 'SELECT * FROM `album` WHERE `id` IN ' . $idlist;
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('album', $row['id'], $row);
        }

        return true;
    }

    /**
     * _get_extra_info
     * This pulls the extra information from our tables, this is a 3 table join, which is why we don't normally
     * do it
     */
    private function _get_extra_info(): array
    {
        if ($this->isNew()) {
            return [];
        }

        if (parent::is_cached('album_extra', $this->id)) {
            return parent::get_from_cache('album_extra', $this->id);
        }

        $results = [];
        if (
            !$this->album_artist &&
            $this->song_artist_count == 1
        ) {
            $sql        = "SELECT MIN(`song`.`id`) AS `song_id`, `artist`.`name` AS `artist_name`, `artist`.`prefix` AS `artist_prefix`, MIN(`artist`.`id`) AS `artist_id` FROM `song` INNER JOIN `artist` ON `artist`.`id`=`song`.`artist` WHERE `song`.`album` = ? GROUP BY `song`.`album`, `artist`.`prefix`, `artist`.`name`";
            $db_results = Dba::read($sql, [$this->id]);
            $results    = Dba::fetch_assoc($db_results);
            // overwrite so you can get something
            $this->album_artist  = $results['artist_id'] ?? null;
            $this->artist_prefix = $results['artist_prefix'] ?? null;
            $this->artist_name   = $results['artist_name'] ?? null;
        }

        $this->has_art();

        if (AmpConfig::get('show_played_times')) {
            $results['total_count'] = $this->total_count;
        }

        parent::add_to_cache('album_extra', $this->id, $results);

        return $results;
    }

    /**
     * check
     *
     * Searches for an album; if none is found, insert a new one.
     * @param int $catalog_id
     * @param string $name
     * @param int $year
     * @param string|null $mbid
     * @param string|null $mbid_group
     * @param int|null $album_artist
     * @param string|null $release_type
     * @param string|null $release_status
     * @param int|null $original_year
     * @param string|null $barcode
     * @param string|null $catalog_number
     * @param string|null $version
     * @param bool $readonly
     */
    public static function check(
        $catalog_id,
        $name,
        $year = 0,
        $mbid = null,
        $mbid_group = null,
        $album_artist = null,
        $release_type = null,
        $release_status = null,
        $original_year = null,
        $barcode = null,
        $catalog_number = null,
        $version = null,
        $readonly = false
    ): int {
        $trimmed        = Catalog::trim_prefix(trim((string) $name));
        $name           = $trimmed['string'];
        $prefix         = $trimmed['prefix'];
        $album_artist   = (int)$album_artist;
        $album_artist   = ($album_artist < 1) ? null : $album_artist;

        $mbid           = empty($mbid) ? null : $mbid;
        $mbid_group     = empty($mbid_group) ? null : $mbid_group;
        $release_type   = empty($release_type) ? null : $release_type;
        $release_status = empty($release_status) ? null : $release_status;
        $original_year  = ((int)substr((string)$original_year, 0, 4) < 1) ? null : substr((string)$original_year, 0, 4);
        $barcode        = empty($barcode) ? null : $barcode;
        $catalog_number = empty($catalog_number) ? null : $catalog_number;
        $version        = empty($version) ? null : $version;

        if (!$name) {
            $name          = T_('Unknown (Orphaned)');
            $year          = 0;
            $original_year = null;
            $album_artist  = Artist::check(T_('Unknown (Orphaned)'));
            $catalog_id    = 0;
        }

        if (isset(self::$_mapcache[$name][$year][$album_artist][$mbid][$mbid_group][$release_type][$release_status][$original_year][$barcode][$catalog_number][$version])) {
            return self::$_mapcache[$name][$year][$album_artist][$mbid][$mbid_group][$release_type][$release_status][$original_year][$barcode][$catalog_number][$version];
        }

        $sql    = "SELECT DISTINCT(`album`.`id`) AS `id` FROM `album` WHERE (`album`.`name` = ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) = ?) AND `album`.`year` = ? ";
        $params = [
            $name,
            $name,
            $year,
        ];

        if ($prefix) {
            $sql .= 'AND `album`.`prefix` = ? ';
            $params[] = $prefix;
        } else {
            $sql .= 'AND `album`.`prefix` IS NULL ';
        }

        if ($mbid) {
            $sql .= 'AND `album`.`mbid` = ? ';
            $params[] = $mbid;
        } else {
            $sql .= 'AND `album`.`mbid` IS NULL ';
        }

        if ($mbid_group) {
            $sql .= 'AND `album`.`mbid_group` = ? ';
            $params[] = $mbid_group;
        } else {
            $sql .= 'AND `album`.`mbid_group` IS NULL ';
        }

        if ($album_artist) {
            $sql .= 'AND `album`.`album_artist` = ? ';
            $params[] = $album_artist;
        } else {
            $sql .= 'AND `album`.`album_artist` IS NULL ';
        }

        if ($release_type) {
            $sql .= 'AND `album`.`release_type` = ? ';
            $params[] = $release_type;
        } else {
            $sql .= 'AND `album`.`release_type` IS NULL ';
        }

        if ($release_status) {
            $sql .= 'AND `album`.`release_status` = ? ';
            $params[] = $release_status;
        } else {
            $sql .= 'AND `album`.`release_status` IS NULL ';
        }

        if ($original_year) {
            $sql .= 'AND `album`.`original_year` = ? ';
            $params[] = $original_year;
        } else {
            $sql .= 'AND `album`.`original_year` IS NULL ';
        }

        if ($barcode) {
            $sql .= 'AND `album`.`barcode` = ? ';
            $params[] = $barcode;
        } else {
            $sql .= 'AND `album`.`barcode` IS NULL ';
        }

        if ($catalog_number) {
            $sql .= 'AND `album`.`catalog_number` = ? ';
            $params[] = $catalog_number;
        } else {
            $sql .= 'AND `album`.`catalog_number` IS NULL ';
        }

        if ($version) {
            $sql .= 'AND `album`.`version` = ? ';
            $params[] = $version;
        } else {
            $sql .= 'AND `album`.`version` IS NULL ';
        }

        $sql .= 'AND `album`.`catalog` = ?;';
        $params[] = $catalog_id;

        $db_results = Dba::read($sql, $params);

        if ($row = Dba::fetch_assoc($db_results)) {
            $album_id = (int)$row['id'];
            if ($album_id > 0) {
                // cache the album id against it's details
                self::$_mapcache[$name][$year][$album_artist][$mbid][$mbid_group][$release_type][$release_status][$original_year][$barcode][$catalog_number][$version] = $album_id;

                return $album_id;
            }
        }

        if ($readonly) {
            return 0;
        }

        $sql = 'INSERT INTO `album` (`name`, `prefix`, `year`, `mbid`, `mbid_group`, `release_type`, `release_status`, `album_artist`, `original_year`, `barcode`, `catalog_number`, `version`, `catalog`, `addition_time`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $db_results = Dba::write($sql, [
            $name,
            $prefix,
            $year,
            $mbid,
            $mbid_group,
            $release_type,
            $release_status,
            $album_artist,
            $original_year,
            $barcode,
            $catalog_number,
            $version,
            $catalog_id, time(),
        ]);
        if (!$db_results) {
            return 0;
        }

        $album_id = Dba::insert_id();
        debug_event(self::class, sprintf('check album: created {%s}', $album_id), 4);
        // map the new id
        Catalog::update_map($catalog_id, 'album', $album_id);
        // Remove from wanted album list if any request on it
        if (!empty($mbid) && AmpConfig::get('wanted')) {
            $user = Core::get_global('user');

            try {
                if ($user instanceof User) {
                    self::getWantedManager()->delete(
                        (string) $mbid,
                        $user
                    );
                }
            } catch (Exception $error) {
                debug_event(self::class, 'Cannot process wanted releases auto-removal check: ' . $error->getMessage(), 2);
            }
        }

        self::$_mapcache[$name][$year][$album_artist][$mbid][$mbid_group][$release_type][$release_status][$original_year][$barcode][$catalog_number][$version] = $album_id;

        return (int)$album_id;
    }

    /**
     * format
     * This is the format function for this object. It sets cleaned up
     * album information with the base required
     * f_link, f_name
     *
     * @param bool $details
     * @param string $limit_threshold
     */
    public function format($details = true, $limit_threshold = ''): void
    {
        if ($this->isNew()) {
            return;
        }

        $this->f_release_type = ucwords((string)$this->release_type);
        $this->get_artists();

        if ($details) {
            /* Pull the advanced information */
            $data = $this->_get_extra_info();
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }

            $this->tags   = Tag::get_top_tags('album', $this->id);
            $this->f_tags = Tag::get_display($this->tags, true, 'album');
        }

        // set link and f_link
        $this->get_f_link();
        $this->get_artist_fullname();
        $this->get_f_artist_link();

        if ($this->year === 0) {
            $this->f_year = "N/A";
        } else {
            $web_path          = AmpConfig::get_web_path();
            $year              = $this->year;
            $this->f_year_link = sprintf('<a href="%s/search.php?type=album&action=search&limit=0rule_1=year&rule_1_operator=2&rule_1_input=', $web_path) . $year . "\">" . $year . "</a>";
        }
    }

    /**
     * does the item have art?
     */
    public function has_art(): bool
    {
        if ($this->has_art === null) {
            $this->has_art = Art::has_db($this->id, 'album');
        }

        return $this->has_art;
    }

    /**
     * Get item keywords for metadata searches.
     */
    public function get_keywords(): array
    {
        return [
            'mb_albumid' => [
                'important' => false,
                'label' => T_('Album MusicBrainzID'),
                'value' => $this->mbid,
            ],
            'mb_albumid_group' => [
                'important' => false,
                'label' => T_('Release Group MusicBrainzID'),
                'value' => $this->mbid_group,
            ],
            'artist' => [
                'important' => true,
                'label' => T_('Artist'),
                'value' => ($this->get_artist_fullname()),
            ],
            'album' => [
                'important' => true,
                'label' => T_('Album'),
                'value' => $this->get_fullname(true),
            ],
            'year' => [
                'important' => false,
                'label' => T_('Year'),
                'value' => $this->year,
            ],
        ];
    }

    /**
     * Get item fullname.
     * @param bool $simple
     * @param bool $force_year
     */
    public function get_fullname($simple = false, $force_year = false): string
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
     * Get item link.
     */
    public function get_link(): string
    {
        // don't do anything if it's formatted
        if ($this->link === null) {
            $web_path   = AmpConfig::get_web_path();
            $this->link = $web_path . '/albums.php?action=show&album=' . $this->id;
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
            $this->f_link = "<a href=\"" . $this->get_link() . "\" title=\"" . scrub_out($this->get_fullname()) . "\">" . scrub_out($this->get_fullname()) . "</a>";
        }

        return $this->f_link;
    }

    /**
     * Return a formatted link to the parent object (if appliccable)
     */
    public function get_f_parent_link(): ?string
    {
        return $this->get_f_artist_link();
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
            $this->album_artists === null ||
            $this->album_artists === []
        ) {
            $this->album_artists = self::get_parent_array($this->id, $this->album_artist);
        }

        return $this->album_artists ?? [];
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
     * getYear
     */
    public function getYear(): string
    {
        return (string)($this->year ?: '');
    }

    /**
     * Get item f_artist_link.
     */
    public function get_f_artist_link(): ?string
    {
        // don't do anything if it's formatted
        if ($this->f_artist_link === null) {
            if ($this->album_artist === 0) {
                $this->f_artist_link = sprintf('<span title="%d ', $this->artist_count) . T_('Artists') . "\">" . T_('Various') . "</span>";
            } elseif ($this->album_artist !== null) {
                $this->f_artist_link = '';
                $web_path            = AmpConfig::get_web_path();
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
     * Get the album artist fullname.
     */
    public function get_artist_fullname(): ?string
    {
        if ($this->f_artist_name === null) {
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

        return $this->f_artist_name;
    }

    /**
     * @return iterable<AlbumDisk>
     */
    public function getDisks(): iterable
    {
        return $this->getAlbumDiskRepository()->getByAlbum($this);
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
     * Get parent album artists.
     * @param int $album_id
     * @param int $primary_id
     * @param string $object_type
     * @return int[]
     */
    public static function get_parent_array($album_id, $primary_id, $object_type = 'album'): array
    {
        $results    = [];
        $sql        = "SELECT DISTINCT `object_id` FROM `album_map` WHERE `object_type` = ? AND `album_id` = ?;";
        $db_results = Dba::read($sql, [$object_type, $album_id]);
        //debug_event(self::class, 'get_parent_array ' . $sql, 5);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['object_id'];
        }

        $primary = ((int)$primary_id > 0)
            ? [(int)$primary_id]
            : [];

        return array_unique(array_merge($primary, $results));
    }

    /**
     * Get item children.
     */
    public function get_childrens(): array
    {
        return $this->get_medias();
    }

    /**
     * Search for direct children of an object
     * @param string $name
     */
    public function get_children($name): array
    {
        $childrens  = [];
        $sql        = "SELECT DISTINCT `song`.`id` FROM `song` WHERE `song`.`album` = ? AND `song`.`file` LIKE ?;";
        $db_results = Dba::read($sql, [$this->id, "%" . $name]);
        while ($row = Dba::fetch_assoc($db_results)) {
            $childrens[] = [
                'object_type' => 'song',
                'object_id' => $row['id']
            ];
        }

        return $childrens;
    }

    /**
     * Get all children and sub-childrens media.
     *
     * @return list<array{object_type: LibraryItemEnum, object_id: int}>
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
     * Returns the id of the catalog the item is associated to
     */
    public function getCatalogId(): int
    {
        return $this->catalog;
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
     * Get default art kind for this item.
     */
    public function get_default_art_kind(): string
    {
        return 'default';
    }

    /**
     * get_songs
     *
     * Get each song id for the album
     * @return list<int>
     */
    public function get_songs(): array
    {
        $results = [];
        $params  = [$this->id];
        $sql     = (AmpConfig::get('catalog_disable'))
            ? "SELECT DISTINCT `song`.`id` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `song`.`album` = ? AND `catalog`.`enabled` = '1'"
            : "SELECT DISTINCT `song`.`id` FROM `song` WHERE `song`.`album` = ?";
        $db_results = Dba::read($sql, $params);

        while ($row = Dba::fetch_assoc($db_results, false)) {
            $results[] = (int)$row['id'];
        }

        return $results;
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
     * display_art
     * @param int $thumb
     * @param bool $force
     */
    public function display_art($thumb = 2, $force = false): void
    {
        $album_id = null;
        $type     = null;

        if (Art::has_db($this->id, 'album')) {
            $album_id = $this->id;
            $type     = 'album';
        } elseif ($this->album_artist && (Art::has_db($this->album_artist, 'artist') || $force)) {
            $album_id = $this->album_artist;
            $type     = 'artist';
        }

        if ($album_id !== null && $type !== null) {
            $title = ($this->get_artist_fullname() != "")
                ? '[' . $this->get_artist_fullname() . '] ' . $this->get_fullname()
                : $this->get_fullname();
            Art::display($type, $album_id, $title, $thumb, $this->get_link());
        }
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
        $album_artist   = (isset($data['album_artist']) && (int)$data['album_artist'] > 0) ? (int)$data['album_artist'] : null;
        $year           = (int)($data['year'] ?? 0);
        $mbid           = $data['mbid'] ?? null;
        $mbid_group     = $data['mbid_group'] ?? null;
        $release_type   = $data['release_type'] ?? null;
        $release_status = $data['release_status'] ?? null;
        $original_year  = (empty($data['original_year']))
            ? null
            : (int)$data['original_year'];
        $barcode        = $data['barcode'] ?? null;
        $catalog_number = $data['catalog_number'] ?? null;
        $version        = $data['version'] ?? null;

        // If you have created an album_artist using 'add new...' we need to create a new artist
        if (array_key_exists('artist_name', $data) && !empty($data['artist_name'])) {
            $album_artist = Artist::check($data['artist_name']);
            if ($album_artist !== null) {
                self::update_field('album_artist', $album_artist, $this->id);
                $this->album_artist = $album_artist;
            }
        }

        $current_id = $this->id;
        $updated    = false;
        $ndata      = [];
        $changed    = [];
        $songs      = $this->getSongRepository()->getByAlbum($this->id);
        // run an album check on the current object READONLY means that it won't insert a new album
        $album_id   = self::check(
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
                Song::update_utime($song_id);

                $this->getSongTagWriter()->write(new Song($song_id));
            }

            self::update_table_counts();
            $current_id = $album_id;
            $updated    = true;
            if (!$cron_cache) {
                $this->getAlbumRepository()->collectGarbage();
            }
        } else {
            // run updates on the single fields
            if (!empty($name) && $name != $this->get_fullname()) {
                $trimmed          = Catalog::trim_prefix(trim((string) $name));
                $new_name         = $trimmed['string'];
                $aPrefix          = $trimmed['prefix'];
                $ndata['album']   = $name;
                $changed['album'] = 'album';

                self::update_field('name', $new_name, $this->id);
                self::update_field('prefix', $aPrefix, $this->id);

                $this->name   = $new_name;
                $this->prefix = $aPrefix;
            }

            if ($year !== $this->year) {
                self::update_field('year', $year, $this->id);
                foreach ($songs as $song_id) {
                    Song::update_year($year, $song_id);
                    $this->getSongTagWriter()->write(new Song($song_id));
                }
            }

            if ($mbid != $this->mbid) {
                self::update_field('mbid', $mbid, $this->id);
            }

            if ($mbid_group != $this->mbid_group) {
                self::update_field('mbid_group', $mbid_group, $this->id);
            }

            if ($album_artist !== $this->album_artist) {
                self::update_field('album_artist', $album_artist, $this->id);
                self::add_album_map($this->id, 'album', (int)$album_artist);
                self::remove_album_map($this->id, 'album', (int)$this->album_artist);
            }

            if ($release_type != $this->release_type) {
                self::update_field('release_type', $release_type, $this->id);
            }

            if ($release_type != $this->release_status) {
                self::update_field('release_status', $release_status, $this->id);
            }

            if ($original_year !== $this->original_year) {
                self::update_field('original_year', $original_year, $this->id);
            }

            if ($barcode != $this->barcode) {
                self::update_field('barcode', $barcode, $this->id);
            }

            if ($catalog_number != $this->catalog_number) {
                self::update_field('catalog_number', $catalog_number, $this->id);
            }

            if ($version != $this->version) {
                self::update_field('version', $version, $this->id);
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

        if ($updated && is_array($songs)) {
            foreach ($songs as $song_id) {
                Song::update_utime($song_id);
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

        return $current_id;
    }

    /**
     * Update an album field.
     * @param string $field
     * @param string|int|null $value
     * @param int $album_id
     */
    private static function update_field($field, $value, $album_id): void
    {
        if ($value === null) {
            $sql = "UPDATE `album` SET `" . $field . "` = NULL WHERE `id` = ?";
            Dba::write($sql, [$album_id]);
        } else {
            $sql = "UPDATE `album` SET `" . $field . "` = ? WHERE `id` = ?";
            Dba::write($sql, [$value, $album_id]);
        }
    }

    /**
     * update_album_artist
     *
     * find albums that are missing an album_artist and generate one.
     */
    public static function update_album_artist(): void
    {
        // Find all albums that are missing an album artist
        $sql        = "SELECT `id` FROM `album` WHERE `album_artist` IS NULL AND `name` != ?;";
        $db_results = Dba::read($sql, [T_('Unknown (Orphaned)')]);
        while ($row = Dba::fetch_assoc($db_results)) {
            $album_id = (int) $row['id'];

            $artist_id  = 0;
            $sql        = "SELECT MIN(`artist`) AS `artist` FROM `song` WHERE `album` = ? GROUP BY `album` HAVING COUNT(DISTINCT `artist`) = 1 LIMIT 1";
            $db_results = Dba::read($sql, [$album_id]);

            // these are albums that only have 1 artist
            while ($row = Dba::fetch_assoc($db_results)) {
                $artist_id = (int)$row['artist'];
            }

            // Update the album
            if ($artist_id > 0) {
                debug_event(self::class, 'Found album_artist {' . $artist_id . '} for: ' . $album_id, 5);
                self::update_field('album_artist', $artist_id, $album_id);
                Artist::add_artist_map($artist_id, 'album', $album_id);
                self::add_album_map($album_id, 'album', $artist_id);
            }
        }
    }

    /**
     * Add the album map for a single item
     */
    public static function add_album_map(int $album_id, string $object_type, int $object_id): void
    {
        if ($album_id > 0 && $object_id > 0) {
            debug_event(self::class, "add_album_map album_id {" . $album_id . "} " . $object_type . "_artist {" . $object_id . "}", 5);
            $sql = "INSERT IGNORE INTO `album_map` (`album_id`, `object_type`, `object_id`) VALUES (?, ?, ?);";
            Dba::write($sql, [$album_id, $object_type, $object_id]);
        }
    }

    /**
     * Delete the album map for a single item
     */
    public static function remove_album_map(int $album_id, string $object_type, int $object_id): void
    {
        if ($album_id > 0 && $object_id > 0) {
            debug_event(self::class, "remove_album_map album_id {" . $album_id . "} " . $object_type . "_artist {" . $object_id . "}", 5);
            $sql = "DELETE FROM `album_map` WHERE `album_id` = ? AND `object_type` = ? AND `object_id` = ?;";
            Dba::write($sql, [$album_id, $object_type, $object_id]);
        }
    }

    /**
     * Delete the album map for a single item if this was the last track
     */
    public static function check_album_map(int $album_id, string $object_type, int $object_id): bool
    {
        if ($album_id > 0 && $object_id > 0) {
            // Remove the album_map if this was the last track
            $sql = ($object_type == 'album')
                ? "SELECT `artist_id` FROM `artist_map` WHERE `artist_id` = ? AND `object_id` = ? AND object_type = ?;"
                : "SELECT `artist_id` FROM `artist_map` WHERE `artist_id` = ? AND `object_id` IN (SELECT `id` FROM `song` WHERE `album` = ?) AND object_type = ?;";
            $db_results = Dba::read($sql, [$object_id, $album_id, $object_type]);
            $row        = Dba::fetch_assoc($db_results);
            if ($row === []) {
                Album::remove_album_map($album_id, $object_type, $object_id);

                return true;
            }
        }

        return false;
    }

    /**
     * count_album
     *
     * Called this after inserting a new song to keep stats correct right away
     */
    public static function update_album_count(int $album_id): void
    {
        $params = [$album_id];
        // album.time
        $sql = "UPDATE `album`, (SELECT SUM(`song`.`time`) AS `time`, `song`.`album` FROM `song` WHERE `album` = ? GROUP BY `song`.`album`) AS `song` SET `album`.`time` = `song`.`time` WHERE `album`.`id` = `song`.`album` AND ((`album`.`time` != `song`.`time`) OR (`album`.`time` IS NULL AND `song`.`time` > 0));";
        Dba::write($sql, $params);
        // album.song_count
        $sql = "UPDATE `album`, (SELECT COUNT(`song`.`id`) AS `song_count`, `album` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1' AND `album` = ? GROUP BY `album`) AS `song` SET `album`.`song_count` = `song`.`song_count` WHERE `album`.`song_count` != `song`.`song_count` AND `album`.`id` = `song`.`album`;";
        Dba::write($sql, $params);
        $sql = "UPDATE `album`, (SELECT COUNT(DISTINCT(`album_map`.`object_id`)) AS `artist_count`, `album_id` FROM `album_map` LEFT JOIN `album` ON `album`.`id` = `album_map`.`album_id` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `album_map`.`object_type` = 'album' AND `catalog`.`enabled` = '1' AND `album`.`id` = ? GROUP BY `album_id`) AS `album_map` SET `album`.`artist_count` = `album_map`.`artist_count` WHERE `album`.`artist_count` != `album_map`.`artist_count` AND `album`.`id` = `album_map`.`album_id` AND `album`.`album_artist` IS NOT NULL;";
        Dba::write($sql, $params);
        // album.song_artist_count
        $sql = "UPDATE `album`, (SELECT COUNT(DISTINCT(`album_map`.`object_id`)) AS `artist_count`, `album_id` FROM `album_map` LEFT JOIN `album` ON `album`.`id` = `album_map`.`album_id` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `album_map`.`object_type` = 'song' AND `catalog`.`enabled` = '1' AND `album`.`id` = ? GROUP BY `album_id`) AS `album_map` SET `album`.`song_artist_count` = `album_map`.`artist_count` WHERE `album`.`song_artist_count` != `album_map`.`artist_count` AND `album`.`id` = `album_map`.`album_id`;";
        Dba::write($sql, $params);
        // album.disk_count
        $sql = "UPDATE `album`, (SELECT COUNT(DISTINCT `album_disk`.`disk`) AS `disk_count`, `album_id` FROM `album_disk` WHERE `album_disk`.`album_id` = ? GROUP BY `album_disk`.`album_id`) AS `album_disk` SET `album`.`disk_count` = `album_disk`.`disk_count` WHERE `album`.`disk_count` != `album_disk`.`disk_count` AND `album`.`id` = `album_disk`.`album_id`;";
        Dba::write($sql, $params);
        // album_disk.disk_count
        $sql = "UPDATE `album_disk`, (SELECT `album`.`disk_count`, `id` FROM `album` WHERE `album`.`id` = ?) AS `album` SET `album_disk`.`disk_count` = `album`.`disk_count` WHERE `album`.`disk_count` != `album_disk`.`disk_count` AND `album`.`id` = `album_disk`.`album_id`;";
        Dba::write($sql, $params);
    }

    /**
     * update_album_counts
     * Update all albums with mapping and missing data after catalog changes
     */
    public static function update_table_counts(): void
    {
        debug_event(self::class, 'update_table_counts', 5);
        // album.time
        $sql = "UPDATE `album`, (SELECT SUM(`song`.`time`) AS `time`, `song`.`album` FROM `song` GROUP BY `song`.`album`) AS `song` SET `album`.`time` = `song`.`time` WHERE `album`.`id` = `song`.`album` AND ((`album`.`time` != `song`.`time`) OR (`album`.`time` IS NULL AND `song`.`time` > 0));";
        Dba::write($sql);
        // album.addition_time
        $sql = "UPDATE `album`, (SELECT MIN(`song`.`addition_time`) AS `addition_time`, `song`.`album` FROM `song` GROUP BY `song`.`album`) AS `song` SET `album`.`addition_time` = `song`.`addition_time` WHERE `album`.`addition_time` != `song`.`addition_time` AND `song`.`album` = `album`.`id`;";
        Dba::write($sql);
        // album.total_count
        $sql = "UPDATE `album`, (SELECT COUNT(`object_count`.`object_id`) AS `total_count`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'album' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id`) AS `object_count` SET `album`.`total_count` = `object_count`.`total_count` WHERE `album`.`total_count` != `object_count`.`total_count` AND `album`.`id` = `object_count`.`object_id`;";
        Dba::write($sql);
        // album.total_count 0 plays
        $sql = "UPDATE `album`, (SELECT 0 AS `total_count`, `album`.`id` FROM `album` WHERE `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'album' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id`)) AS `object_count` SET `album`.`total_count` = `object_count`.`total_count` WHERE `album`.`total_count` != `object_count`.`total_count` AND `object_count`.`id` = `album`.`id`;";
        Dba::write($sql);
        // album.song_count
        $sql = "UPDATE `album`, (SELECT COUNT(`song`.`id`) AS `song_count`, `album` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `album`) AS `song` SET `album`.`song_count` = `song`.`song_count` WHERE `album`.`song_count` != `song`.`song_count` AND `album`.`id` = `song`.`album`;";
        Dba::write($sql);
        // album.artist_count
        $sql = "UPDATE `album` SET `album`.`artist_count` = 0 WHERE `album_artist` IS NULL;";
        Dba::write($sql);
        $sql = "UPDATE `album`, (SELECT COUNT(DISTINCT(`album_map`.`object_id`)) AS `artist_count`, `album_id` FROM `album_map` LEFT JOIN `album` ON `album`.`id` = `album_map`.`album_id` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `album_map`.`object_type` = 'album' AND `catalog`.`enabled` = '1' GROUP BY `album_id`) AS `album_map` SET `album`.`artist_count` = `album_map`.`artist_count` WHERE `album`.`artist_count` != `album_map`.`artist_count` AND `album`.`id` = `album_map`.`album_id` AND `album`.`album_artist` IS NOT NULL;";
        Dba::write($sql);
        // album.song_artist_count
        $sql = "UPDATE `album`, (SELECT COUNT(DISTINCT(`album_map`.`object_id`)) AS `artist_count`, `album_id` FROM `album_map` LEFT JOIN `album` ON `album`.`id` = `album_map`.`album_id` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `album_map`.`object_type` = 'song' AND `catalog`.`enabled` = '1' GROUP BY `album_id`) AS `album_map` SET `album`.`song_artist_count` = `album_map`.`artist_count` WHERE `album`.`song_artist_count` != `album_map`.`artist_count` AND `album`.`id` = `album_map`.`album_id`;";
        Dba::write($sql);
        // missing album_disk
        $sql = "INSERT IGNORE INTO `album_disk` (`album_id`, `disk`, `catalog`) SELECT DISTINCT `song`.`album` AS `album_id`, `song`.`disk` AS `disk`, `song`.`catalog` AS `catalog` FROM `song`;";
        Dba::write($sql);
        // album.disk_count
        $sql = "UPDATE `album`, (SELECT COUNT(DISTINCT `album_disk`.`disk`) AS `disk_count`, `album_id` FROM `album_disk` GROUP BY `album_disk`.`album_id`) AS `album_disk` SET `album`.`disk_count` = `album_disk`.`disk_count` WHERE `album`.`disk_count` != `album_disk`.`disk_count` AND `album`.`id` = `album_disk`.`album_id`;";
        Dba::write($sql);
        // album_disk.disk_count
        $sql = "UPDATE `album_disk`, (SELECT `disk_count`, `id` FROM `album`) AS `album` SET `album_disk`.`disk_count` = `album`.`disk_count` WHERE `album`.`disk_count` != `album_disk`.`disk_count` AND `album`.`id` = `album_disk`.`album_id`;";
        Dba::write($sql);
        // album_disk.time
        $sql = "UPDATE `album_disk`, (SELECT SUM(`time`) AS `time`, `album`, `disk` FROM `song` GROUP BY `album`, `disk`) AS `song` SET `album_disk`.`time` = `song`.`time` WHERE (`album_disk`.`time` != `song`.`time` OR `album_disk`.`time` IS NULL) AND `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk`;";
        Dba::write($sql);
        // album_disk.song_count
        $sql = "UPDATE `album_disk`, (SELECT COUNT(DISTINCT `id`) AS `song_count`, `album`, `disk` FROM `song` GROUP BY `album`, `disk`) AS `song` SET `album_disk`.`song_count` = `song`.`song_count` WHERE `album_disk`.`song_count` != `song`.`song_count` AND `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk`;";
        Dba::write($sql);
        // album_disk.total_count
        $sql = "UPDATE `album_disk`, (SELECT SUM(`song`.`total_count`) AS `total_count`, `album_disk`.`id` AS `object_id` FROM `song` LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk` GROUP BY `album_disk`.`id`) AS `object_count` SET `album_disk`.`total_count` = `object_count`.`total_count` WHERE `album_disk`.`total_count` != `object_count`.`total_count` AND `album_disk`.`id` = `object_count`.`object_id`;";
        Dba::write($sql);
    }

    /**
     * does the item have a single album artist and song artist?
     */
    public function get_artist_count(): int
    {
        $sql        = "SELECT COUNT(DISTINCT(`object_id`)) AS `artist_count` FROM `album_map` WHERE `album_id` = ?;";
        $db_results = Dba::read($sql, [$this->id]);
        $row        = Dba::fetch_assoc($db_results);
        if ($row !== []) {
            return (int)$row['artist_count'];
        }

        return 0;
    }

    /**
     * sanitize_disk
     * Change letter disk numbers (like vinyl/cassette) to an integer
     * @param string|int $disk
     */
    public static function sanitize_disk($disk): int
    {
        if ((int)$disk == 0) {
            // A is 0 but we want to start at disk 1
            $alphabet = range('A', 'Z');
            $disk     = (int)array_search(strtoupper((string)$disk), $alphabet, true) + 1;
        }

        return (int)$disk;
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::ALBUM;
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
    private function getAlbumRepository(): AlbumRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumRepositoryInterface::class);
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

    /**
     * @inject dependency
     */
    private function getAlbumDiskRepository(): AlbumDiskRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumDiskRepositoryInterface::class);
    }

    /**
     * @deprecated Inject dependency
     */
    private static function getWantedManager(): WantedManagerInterface
    {
        global $dic;

        return $dic->get(WantedManagerInterface::class);
    }
}
