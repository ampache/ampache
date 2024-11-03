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

use Ampache\Module\Artist\Tag\ArtistTagUpdaterInterface;
use Ampache\Module\Label\LabelListUpdaterInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Dba;
use Ampache\Config\AmpConfig;
use Ampache\Module\Util\VaInfo;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;

class Artist extends database_object implements library_item, CatalogItemInterface
{
    protected const DB_TABLENAME = 'artist';

    public int $id = 0;

    public ?string $name = null;

    public ?string $prefix = null;

    public ?string $mbid = null; // MusicBrainz ID

    public ?string $summary = null;

    public ?string $placeformed = null;

    public ?int $yearformed = null;

    public int $last_update;

    public ?int $user = null;

    public bool $manual_update;

    public ?int $time = null;

    public int $song_count;

    public int $album_count;

    public int $album_disk_count;

    public int $total_count;

    public ?string $link = null;

    /** @var int $catalog_id */
    public $catalog_id;

    /** @var int $songs */
    public $songs;

    /** @var int $albums */
    public $albums;

    /** @var array $tags */
    public $tags;

    /** @var null|string $f_tags */
    public $f_tags;

    /** @var array $labels */
    public $labels;

    /** @var null|string $f_labels */
    public $f_labels;

    /** @var null|string $f_name */
    public $f_name; // Prefix + Name, generated

    /** @var null|string $f_link */
    public $f_link;

    /** @var null|string $f_time */
    public $f_time;

    /** @var bool $_fake */
    public $_fake = false; // Set if construct_from_array used

    private ?bool $has_art = null;

    /** @var array $_mapcache */
    private static $_mapcache = [];

    /**
     * Artist class, for modifying an artist
     * Takes the ID of the artist and pulls the info from the db
     * @param int|null $artist_id
     * @param int $catalog_init
     */
    public function __construct(
        $artist_id = 0,
        $catalog_init = 0
    ) {
        if (!$artist_id) {
            return;
        }

        $info = $this->get_info($artist_id, static::DB_TABLENAME);
        if ($info === []) {
            return;
        }

        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        $this->time             = (int)$this->time;
        $this->catalog_id       = (int)$catalog_init;
        $this->get_fullname();
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
     * construct_from_array
     * This is used by the metadata class specifically but fills out a Artist object
     * based on a key'd array, it sets $_fake to true
     * @param array $data
     */
    public static function construct_from_array($data): Artist
    {
        $artist = new Artist(0);
        foreach ($data as $key => $value) {
            $artist->$key = $value;
        }

        // Ack that this is not a real object from the DB
        $artist->_fake = true;

        return $artist;
    }

    /**
     * this attempts to build a cache of the data from the passed albums all in one query
     * @param int[] $ids
     * @param bool $extra
     * @param string $limit_threshold
     */
    public static function build_cache($ids, $extra = false, $limit_threshold = ''): bool
    {
        if (empty($ids)) {
            return false;
        }

        $idlist     = '(' . implode(',', $ids) . ')';
        $sql        = 'SELECT * FROM `artist` WHERE `id` IN ' . $idlist;
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('artist', $row['id'], $row);
        }

        // If we need to also pull the extra information, this is normally only used when we are doing the human display
        if (
            $extra &&
            AmpConfig::get('show_played_times')
        ) {
            $sql = sprintf('SELECT `song`.`artist`, SUM(`song`.`total_count`) AS `total_count` FROM `song` WHERE `song`.`artist` IN %s GROUP BY `song`.`artist`', $idlist);

            //debug_event(self::class, "build_cache sql: " . $sql, 5);
            $db_results = Dba::read($sql);

            while ($row = Dba::fetch_assoc($db_results)) {
                $row['total_count'] = (empty($limit_threshold))
                    ? $row['total_count']
                    : Stats::get_object_count('artist', $row['artist'], $limit_threshold);
                parent::add_to_cache('artist_extra', $row['artist'], $row);
            }
        }

        return true;
    }

    /**
     * get_id_arrays
     *
     * Get each id from the artist table with the minimum detail required for subsonic
     * @param array $catalogs
     */
    public static function get_id_arrays($catalogs = []): array
    {
        $results = [];
        // if you have no catalogs set, just grab it all
        if (!empty($catalogs)) {
            $sql = "SELECT DISTINCT `artist`.`id`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `f_name`, `artist`.`name`, `artist`.`album_count` AS `album_count`, `artist`.`song_count`, `image`.`object_id` AS `has_art` FROM `artist` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = 'artist' AND `catalog_map`.`object_id` = `artist`.`id` LEFT JOIN `image` ON `image`.`object_type` = 'artist' AND `image`.`object_id` = `artist`.`id` AND `image`.`size` = 'original' WHERE `catalog_map`.`catalog_id` = ? ORDER BY `artist`.`name`";
            foreach ($catalogs as $catalog_id) {
                $db_results = Dba::read($sql, [$catalog_id]);
                while ($row = Dba::fetch_assoc($db_results, false)) {
                    $results[] = $row;
                }
            }
        } else {
            $sql        = "SELECT DISTINCT `artist`.`id`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `f_name`, `artist`.`name`, `artist`.`album_count` AS `album_count`, `artist`.`song_count`, `image`.`object_id` AS `has_art` FROM `artist` LEFT JOIN `image` ON `image`.`object_type` = 'artist' AND `image`.`object_id` = `artist`.`id` AND `image`.`size` = 'original' ORDER BY `artist`.`name`";
            $db_results = Dba::read($sql);
            while ($row = Dba::fetch_assoc($db_results, false)) {
                $results[] = $row;
            }
        }

        return $results;
    }

    /**
     * get_id_array
     *
     * Get info from the artist table with the minimum detail required for subsonic
     * @param int $artist_id
     */
    public static function get_id_array($artist_id): array
    {
        $sql        = "SELECT DISTINCT `artist`.`id`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `f_name`, `artist`.`name`, `artist`.`album_count` AS `album_count`, `artist`.`song_count`, `catalog_map`.`catalog_id`, `image`.`object_id` FROM `artist` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = 'artist' AND `catalog_map`.`object_id` = `artist`.`id` AND `catalog_map`.`catalog_id` = (SELECT MIN(`catalog_map`.`catalog_id`) FROM `catalog_map` WHERE `catalog_map`.`object_type` = 'artist' AND `catalog_map`.`object_id` = `artist`.`id`) LEFT JOIN `image` ON `image`.`object_type` = 'artist' AND `image`.`object_id` = `artist`.`id` AND `image`.`size` = 'original' WHERE `artist`.`id` = ? ORDER BY `artist`.`name`";
        $db_results = Dba::read($sql, [$artist_id]);

        return Dba::fetch_assoc($db_results, false);
    }

    /**
     * get_songs
     *
     * Get each album id for the artist
     * @return int[]
     */
    public function get_songs(): array
    {
        $sql        = "SELECT DISTINCT `album`.`id` FROM `album` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `album`.`id` WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'album' AND `catalog`.`enabled` = '1'";
        $db_results = Dba::read($sql, [$this->id]);
        $results    = [];

        while ($row = Dba::fetch_assoc($db_results, false)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     * format
     * this function takes an array of artist
     * information and formats the relevant values
     * so they can be displayed in a table for example
     * it changes the title into a full link.
     *
     * @param bool $details
     * @param string $limit_threshold
     */
    public function format($details = true, $limit_threshold = ''): void
    {
        if ($this->isNew()) {
            return;
        }

        $this->songs  = $this->song_count ?? 0;
        $this->albums = (AmpConfig::get('album_group'))
            ? $this->album_count
            : $this->album_disk_count;

        // set link and f_link
        $this->get_f_link();

        if ($details) {
            $min   = sprintf("%02d", (floor($this->time / 60) % 60));
            $sec   = sprintf("%02d", ($this->time % 60));
            $hours = floor($this->time / 3600);

            $this->f_time = ltrim($hours . ':' . $min . ':' . $sec, '0:');
            $this->tags   = Tag::get_top_tags('artist', $this->id);
            $this->f_tags = Tag::get_display($this->tags, true, 'artist');

            if (AmpConfig::get('label')) {
                $this->labels   = $this->getLabelRepository()->getByArtist($this->id);
                $this->f_labels = Label::get_display($this->labels, true);
            }
        }
    }

    /**
     * does the item have art?
     */
    public function has_art(): bool
    {
        if ($this->has_art === null) {
            $this->has_art = Art::has_db($this->id, 'artist');
        }

        return $this->has_art;
    }

    public static function is_upload(int $artist_id): bool
    {
        $sql        = "SELECT `user` FROM `artist` WHERE `id` = ?";
        $db_results = Dba::read($sql, [$artist_id]);
        $user_id    = 0;
        if ($results = Dba::fetch_assoc($db_results)) {
            $user_id = (int)$results['user'];
        }

        return ($user_id > 0);
    }

    /**
     * Get item keywords for metadata searches.
     */
    public function get_keywords(): array
    {
        return [
            'mb_artistid' => [
                'important' => false,
                'label' => T_('Artist MusicBrainzID'),
                'value' => $this->mbid,
            ],
            'artist' => [
                'important' => true,
                'label' => T_('Artist'),
                'value' => $this->get_fullname(),
            ],
        ];
    }

    /**
     * Get item fullname.
     */
    public function get_fullname(): ?string
    {
        if ($this->f_name === null) {
            // set the full name
            $this->f_name = trim(trim($this->prefix ?? '') . ' ' . trim($this->name ?? ''));
        }

        return $this->f_name;
    }

    /**
     * Get item fullname by the artist id.
     */
    public static function get_fullname_by_id(?int $artist_id = 0): string
    {
        if ($artist_id === 0) {
            return '';
        }

        if (database_object::is_cached('artist_fullname_by_id', $artist_id)) {
            return database_object::get_from_cache('artist_fullname_by_id', $artist_id)[0];
        }

        $sql        = "SELECT LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `f_name` FROM `artist` WHERE `id` = ?;";
        $db_results = Dba::read($sql, [$artist_id]);
        if ($row = Dba::fetch_assoc($db_results)) {
            database_object::add_to_cache('artist_fullname_by_id', $artist_id, [$row['f_name']]);

            return $row['f_name'];
        }

        return '';
    }

    /**
     * Get item prefix, basename and name by the artist id.
     */
    public static function get_name_array_by_id(?int $artist_id = 0): array
    {
        if ($artist_id === 0) {
            return [
                "id" => 0,
                "name" => '',
                "prefix" => T_('Various'),
                "basename" => T_('Various')
            ];
        }

        $sql        = "SELECT `artist`.`id`, `artist`.`prefix`, `artist`.`name` AS `basename`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `name` FROM `artist` WHERE `id` = ?;";
        $db_results = Dba::read($sql, [$artist_id]);
        if ($row = Dba::fetch_assoc($db_results)) {
            return [
                "id" => (string)$row['id'],
                "name" => $row['name'],
                "prefix" => $row['prefix'],
                "basename" => $row['basename']
            ];
        }

        return [
            "id" => '',
            "name" => '',
            "prefix" => '',
            "basename" => ''
        ];
    }

    /**
     * get_display
     * This returns a csv formatted version of the artists that we are given
     * @param array $artists
     */
    public static function get_display($artists): string
    {
        $results = '';
        if (empty($artists)) {
            return $results;
        }

        foreach ($artists as $artists_id) {
            $results .= self::get_fullname_by_id($artists_id) . ', ';
        }

        return rtrim($results, ', ');
    }

    /**
     * Get item link.
     */
    public function get_link(): string
    {
        // don't do anything if it's formatted
        if ($this->link === null) {
            $web_path   = AmpConfig::get_web_path();
            $this->link = ($this->catalog_id > 0)
                ? $web_path . '/artists.php?action=show&catalog=' . $this->catalog_id . '&artist=' . $this->id
                : $web_path . '/artists.php?action=show&artist=' . $this->id;
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
        return null;
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
     * Get item childrens.
     */
    public function get_childrens(): array
    {
        $medias = [];
        $albums = $this->getAlbumRepository()->getAlbumByArtist($this->id);
        $type   = 'album';
        foreach ($albums as $album_id) {
            $medias[] = ['object_type' => $type, 'object_id' => $album_id];
        }

        return [$type => $medias];
    }

    /**
     * Search for direct children of an object
     * @param string $name
     */
    public function get_children($name): array
    {
        $childrens  = [];
        $sql        = "SELECT DISTINCT `album`.`id` FROM `album` LEFT JOIN `album_map` ON `album_map`.`album_id` = `album`.`id` WHERE `album_map`.`object_id` = ? AND `album_map`.`object_type` = 'album' AND (`album`.`name` = ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) = ?);";
        $db_results = Dba::read($sql, [$this->id, $name, $name]);
        while ($row = Dba::fetch_assoc($db_results)) {
            $childrens[] = [
                'object_type' => 'album',
                'object_id' => $row['id']
            ];
        }

        return $childrens;
    }

    /**
     * Get all childrens and sub-childrens medias.
     *
     * @return list<array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function get_medias(?string $filter_type = null): array
    {
        $medias = [];
        if ($filter_type === null || $filter_type === 'song') {
            $songs = $this->getSongRepository()->getByArtist($this->id);
            foreach ($songs as $song_id) {
                $medias[] = ['object_type' => LibraryItemEnum::SONG, 'object_id' => $song_id];
            }
        }

        return $medias;
    }

    /**
     * Returns the id of the catalog the item is associated to
     */
    public function getCatalogId(): int
    {
        return $this->catalog_id;
    }

    /**
     * Get item's owner.
     */
    public function get_user_owner(): ?int
    {
        return $this->user;
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
        return $this->summary ?? '';
    }

    /**
     * display_art
     * @param int $thumb
     * @param bool $force
     */
    public function display_art($thumb = 2, $force = false): void
    {
        $artist_id = null;
        $type      = null;

        if (Art::has_db($this->id, 'artist') || $force) {
            $artist_id = $this->id;
            $type      = 'artist';
        }

        if ($artist_id !== null && $type !== null) {
            Art::display($type, $artist_id, (string)$this->get_fullname(), $thumb, $this->get_link());
        }
    }

    /**
     * check
     *
     * Checks for an existing artist; if none exists, insert one.
     * @param string $name
     * @param string|null $mbid
     * @param bool $readonly
     */
    public static function check($name, $mbid = '', $readonly = false): ?int
    {
        $full_name = $name;
        $trimmed   = Catalog::trim_prefix(trim((string)$name));
        $name      = $trimmed['string'];
        $prefix    = $trimmed['prefix'];
        $trimmed   = Catalog::trim_featuring($name);
        if ($name !== $trimmed[0]) {
            debug_event(self::class, "check artist: cut {" . $name . "} to {" . $trimmed[0] . "}", 4);
        }

        $name = $trimmed[0];

        // If Ampache support multiple artists per song one day, we should also handle other artists here
        $mbid = VaInfo::parse_mbid($mbid);

        if (!$name) {
            $name   = T_('Unknown (Orphaned)');
            $prefix = null;
        }

        if ($name == 'Various Artists') {
            $mbid = '89ad4ac3-39f7-470e-963a-56509c546377';
        }

        if (isset(self::$_mapcache[$name][$prefix][$mbid])) {
            return self::$_mapcache[$name][$prefix][$mbid];
        }

        $artist_id = 0;
        $exists    = false;

        if ($mbid !== null && $mbid !== '' && $mbid !== '0') {
            // check for artists by mbid (there should only ever be one sent here)
            $sql        = 'SELECT `id` FROM `artist` WHERE `mbid` = ?';
            $db_results = Dba::read($sql, [$mbid]);
            if ($row = Dba::fetch_assoc($db_results)) {
                $artist_id = (int)$row['id'];
                $exists    = ($artist_id > 0);
            }

            // still missing? Match on the name and update the mbid
            $sql        = "SELECT `id` FROM `artist` WHERE (`artist`.`name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ?) AND `mbid` IS NULL;";
            $db_results = Dba::read($sql, [$name, $full_name]);
            while ($row = Dba::fetch_assoc($db_results)) {
                if (!$readonly) {
                    $sql = 'UPDATE `artist` SET `mbid` = ? WHERE `id` = ?';
                    Dba::write($sql, [$mbid, $row['id']]);
                }
            }
        } else {
            // look for artists with no mbid (if they exist) and then match on mbid artists last
            $id_array   = [];
            $sql        = "SELECT `id` FROM `artist` WHERE `mbid` IS NULL AND (`artist`.`name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ?) ORDER BY `id`;";
            $db_results = Dba::read($sql, [$name, $full_name]);
            while ($row = Dba::fetch_assoc($db_results)) {
                $id_array[] = (int)$row['id'];
            }

            $sql        = "SELECT `id`, `mbid` FROM `artist` WHERE `mbid` IS NOT NULL AND (`artist`.`name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ?) ORDER BY `id`;";
            $db_results = Dba::read($sql, [$name, $full_name]);
            while ($row = Dba::fetch_assoc($db_results)) {
                $id_array[] = (int)$row['id'];
            }

            if ($id_array !== []) {
                // Pick the first one (nombid and nombid used before matching on mbid)
                $artist_id = $id_array[0];
                $exists    = true;
            }
        }

        // cache and return the result
        if ($exists && $artist_id > 0) {
            self::$_mapcache[$name][$prefix][$mbid] = $artist_id;

            return $artist_id;
        }

        if ($readonly) {
            return null;
        }

        // prefer the name of the artist as provided by MusicBrainz
        if ($mbid !== null && $mbid !== '' && $mbid !== '0') {
            $plugin      = new Plugin('musicbrainz');
            $parsed_mbid = VaInfo::parse_mbid($mbid);
            $data        = $plugin->_plugin->get_artist($parsed_mbid);
            if (array_key_exists('name', $data)) {
                $trimmed = Catalog::trim_prefix(trim((string)$data['name']));
                $name    = $trimmed['string'];
                $prefix  = $trimmed['prefix'];
            }
        }

        // if all else fails, insert a new artist, cache it and return the id
        $sql  = 'INSERT INTO `artist` (`name`, `prefix`, `mbid`) VALUES(?, ?, ?)';
        $mbid = ($mbid === null || $mbid === '' || $mbid === '0')
            ? null
            : $mbid;

        $db_results = Dba::write($sql, [$name, $prefix, $mbid]);
        if (!$db_results) {
            return null;
        }

        $artist_id = (int) Dba::insert_id();
        debug_event(self::class, sprintf('check artist: created {%d}', $artist_id), 4);
        // map the new id
        Catalog::update_map(0, 'artist', $artist_id);

        self::$_mapcache[$name][$prefix][$mbid] = $artist_id;

        return $artist_id;
    }

    /**
     * check_mbid
     *
     * Checks for an existing artist by mbid; if none exists, insert one.
     * @param string $mbid
     */
    public static function check_mbid($mbid): int
    {
        $artist_id   = 0;
        $parsed_mbid = VaInfo::parse_mbid($mbid);

        // check for artists by mbid and split-mbid
        if ($parsed_mbid) {
            $sql        = 'SELECT `id` FROM `artist` WHERE `mbid` = ?';
            $db_results = Dba::read($sql, [$parsed_mbid]);
            if ($results = Dba::fetch_assoc($db_results)) {
                $artist_id = (int)$results['id'];
            }

            // return the result
            if ($artist_id > 0) {
                return $artist_id;
            }

            // if that fails, insert a new artist and return the id
            $plugin = new Plugin('musicbrainz');
            $data   = $plugin->_plugin->get_artist($parsed_mbid);
            if (array_key_exists('name', $data)) {
                $trimmed = Catalog::trim_prefix(trim((string)$data['name']));
                $name    = $trimmed['string'];
                $prefix  = $trimmed['prefix'];

                $sql        = "INSERT INTO `artist` (`name`, `prefix`, `mbid`) VALUES(?, ?, ?)";
                $db_results = Dba::write($sql, [$name, $prefix, $parsed_mbid]);
                if (!$db_results) {
                    return $artist_id;
                }

                $artist_id = (int)Dba::insert_id();
                debug_event(self::class, sprintf('check mbid: created {%d} ', $artist_id) . $data['name'], 4);
            }
        }

        return $artist_id;
    }

    /**
     * Add artist map for a single item
     */
    public static function add_artist_map(?int $artist_id, string $object_type, int $object_id): void
    {
        if ((int)$artist_id > 0 && (int)$object_id > 0) {
            debug_event(self::class, "add_artist_map artist_id {" . $artist_id . sprintf('} %s {', $object_type) . $object_id . "}", 5);
            $sql = "INSERT IGNORE INTO `artist_map` (`artist_id`, `object_type`, `object_id`) VALUES (?, ?, ?);";
            Dba::write($sql, [$artist_id, $object_type, $object_id]);
        }
    }

    /**
     * Delete the artist map for a single item
     */
    public static function remove_artist_map(int $artist_id, string $object_type, int $object_id): void
    {
        if ((int)$artist_id > 0 && (int)$object_id > 0) {
            debug_event(self::class, "remove_artist_map artist_id {" . $artist_id . sprintf('} %s {', $object_type) . $object_id . "}", 5);
            $sql = "DELETE FROM `artist_map` WHERE `artist_id` = ? AND `object_type` = ? AND `object_id` = ?;";
            Dba::write($sql, [$artist_id, $object_type, $object_id]);
        }
    }

    /**
     * get_artist_map
     *
     * This returns an ids of artists that have songs/albums mapped
     * @return int[]
     */
    public static function get_artist_map(string $object_type, int $object_id): array
    {
        $results    = [];
        $sql        = "SELECT `artist_id` AS `artist_id` FROM `artist_map` WHERE `object_type` = ? AND `object_id` = ?";
        $db_results = Dba::read($sql, [$object_type, $object_id]);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['artist_id'];
        }

        return $results;
    }

    /**
     * update_name_from_mbid
     *
     * Refresh your atist name using external data based on the mbid
     * @return array{prefix: ?string,name: string}
     */
    public static function update_name_from_mbid(string $new_name, string $mbid): array
    {
        $trimmed = Catalog::trim_prefix(trim((string)$new_name));
        $name    = $trimmed['string'];
        $prefix  = $trimmed['prefix'];
        $trimmed = Catalog::trim_featuring($name);
        $name    = $trimmed[0];
        debug_event(self::class, sprintf('update_name_from_mbid: rename {%s} to {%s} {%s}', $mbid, $prefix, $name), 4);

        $sql = 'UPDATE `artist` SET `prefix` = ?, `name` = ? WHERE `mbid` = ?';
        Dba::write($sql, [$prefix, $name, $mbid]);

        return [
            'name' => $name,
            'prefix' => $prefix
        ];
    }

    /**
     * update
     * This takes a key'd array of data and updates the current artist
     * @param array{
     *      name?: string,
     *      mbid?: string|null,
     *      summary?: string|null,
     *      placeformed?: string|null,
     *      yearformed?: int|null,
     *      overwrite_childs?: string,
     *      add_to_childs?: string,
     *      edit_tags?: string,
     *      edit_labels?: string
     *  } $data
     */
    public function update(array $data): int
    {
        //debug_event(self::class, "update: " . print_r($data, true), 5);
        // Save our current ID
        $full_name   = $data['name'] ?? '';
        $prefix      = Catalog::trim_prefix($full_name)['prefix'];
        $name        = Catalog::trim_prefix($full_name)['string'];
        $mbid        = $data['mbid'] ?? null;
        $summary     = $data['summary'] ?? null;
        $placeformed = $data['placeformed'] ?? null;
        $yearformed  = $data['yearformed'] ?? null;
        $current_id  = $this->id;

        // Check if name is different than the current name
        if ($this->prefix != $prefix || $this->name != $name) {
            $updated   = false;
            $artist_id = (int)self::check($name, $mbid, true);

            // If you couldn't find an artist OR you found the current one, just rename it and move on
            if ($artist_id == 0 || ($artist_id > 0 && $artist_id == $current_id)) {
                debug_event(self::class, "updated name: " . $prefix . ' ' . $name, 5);
                $this->update_artist_name($name, $prefix);
            }

            // If it's changed we need to update
            if ($artist_id > 0 && $artist_id != $current_id) {
                debug_event(self::class, "updated: " . $current_id . "  to: " . $artist_id, 5);
                $time  = time();
                $songs = $this->getSongRepository()->getByArtist($this->id);
                foreach ($songs as $song_id) {
                    Song::update_artist($artist_id, $song_id, $this->id, false);
                    Song::update_utime($song_id, $time);
                }

                Song::migrate_artist($artist_id, $this->id);
                self::update_table_counts();
                $updated    = true;
                $current_id = $artist_id;
            }

            // clear out the old data
            if ($updated) {
                debug_event(self::class, "garbage_collection: " . $artist_id, 5);
                $this->getArtistRepository()->collectGarbage();
                Stats::garbage_collection();
                Rating::garbage_collection();
                Userflag::garbage_collection();
                $this->getLabelRepository()->collectGarbage();
                $this->getUseractivityRepository()->collectGarbage();
                self::update_table_counts();
            } // if updated
        } elseif ($this->mbid != $mbid) {
            $sql = 'UPDATE `artist` SET `mbid` = ? WHERE `id` = ?';
            Dba::write($sql, [$mbid, $current_id]);
        }

        $this->update_artist_info($summary, $placeformed, $yearformed, true);

        $this->prefix = $prefix;
        $this->name   = $name;
        $this->mbid   = $mbid;

        $override_childs = false;
        if (array_key_exists('overwrite_childs', $data) && $data['overwrite_childs'] == 'checked') {
            $override_childs = true;
        }

        $add_to_childs = false;
        if (array_key_exists('add_to_childs', $data) && $data['add_to_childs'] == 'checked') {
            $add_to_childs = true;
        }

        if (isset($data['edit_tags'])) {
            $this->getArtistTagUpdater()->updateTags(
                $this,
                (string)$data['edit_tags'],
                $override_childs,
                $add_to_childs,
                true
            );
        }

        if (AmpConfig::get('label') && isset($data['edit_labels'])) {
            $this->getLabelListUpdater()->update(
                $data['edit_labels'],
                $this->id,
                true
            );
        }

        return $current_id;
    }

    /**
     * Update artist information.
     * @param string|null $summary
     * @param null|string $placeformed
     * @param null|int $yearformed
     * @param bool $manual
     */
    public function update_artist_info($summary, $placeformed, $yearformed, $manual = false): void
    {
        // set null values if missing
        $summary     = (empty($summary)) ? null : $summary;
        $placeformed = (empty($placeformed)) ? null : $placeformed;
        $yearformed  = ((int)$yearformed == 0) ? null : Catalog::normalize_year($yearformed);

        $sql = "UPDATE `artist` SET `summary` = ?, `placeformed` = ?, `yearformed` = ?, `last_update` = ?, `manual_update` = ? WHERE `id` = ?";
        Dba::write($sql, [
            $summary,
            $placeformed,
            $yearformed,
            time(),
            (int)$manual,
            $this->id,
        ]);

        $this->summary     = $summary;
        $this->placeformed = $placeformed;
        $this->yearformed  = $yearformed;
    }

    /**
     * Update artist associated user_id.
     * @param int $user_id
     */
    public function update_artist_user($user_id): void
    {
        $sql = "UPDATE `artist` SET `user` = ? WHERE `id` = ?";
        Dba::write($sql, [$user_id, $this->id]);
    }

    /**
     * Update artist associated user.
     * @param string $name
     * @param null|string $prefix
     */
    public function update_artist_name($name, $prefix): void
    {
        $sql = "UPDATE `artist` SET `prefix` = ?, `name` = ? WHERE `id` = ?";
        Dba::write($sql, [$prefix, $name, $this->id]);
    }

    /**
     * update_artist_counts
     */
    public static function update_table_counts(): void
    {
        debug_event(self::class, 'update_table_counts', 5);
        // artist.time
        $sql = "UPDATE `artist`, (SELECT SUM(`song`.`time`) AS `time`, `artist_map`.`artist_id` FROM `song` LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' GROUP BY `artist_map`.`artist_id`) AS `song` SET `artist`.`time` = `song`.`time` WHERE (`artist`.`time` IS NULL OR `artist`.`time` != `song`.`time`) AND `artist`.`id` = `song`.`artist_id`;";
        Dba::write($sql);
        // artist.total_count
        $sql = "UPDATE `artist`, (SELECT COUNT(`object_count`.`object_id`) AS `total_count`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'artist' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id`) AS `object_count` SET `artist`.`total_count` = `object_count`.`total_count` WHERE `artist`.`total_count` != `object_count`.`total_count` AND `artist`.`id` = `object_count`.`object_id`;";
        Dba::write($sql);
        // object count = 0
        $sql = "UPDATE `artist`, (SELECT 0 AS `total_count`, `artist`.`id` FROM `artist` WHERE `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'artist' AND `count_type` = 'stream')) AS `object_count` SET `artist`.`total_count` = `object_count`.`total_count` WHERE `artist`.`total_count` != `object_count`.`total_count` AND `artist`.`id` = `object_count`.`id`;";
        Dba::write($sql);
        // artist.album_count
        $sql = "UPDATE `artist`, (SELECT COUNT(DISTINCT `album`.`id`) AS `album_count`, `artist_map`.`artist_id` FROM `artist_map` LEFT JOIN `album` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `artist_map`.`artist_id`) AS `album` SET `artist`.`album_count` = `album`.`album_count` WHERE `artist`.`album_count` != `album`.`album_count` AND `artist`.`id` = `album`.`artist_id`;";
        Dba::write($sql);
        // artist.song_count
        $sql = "UPDATE `artist`, (SELECT COUNT(`song`.`id`) AS `song_count`, `artist_map`.`artist_id` FROM `artist_map` LEFT JOIN `song` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `artist_map`.`artist_id`) AS `song` SET `artist`.`song_count` = `song`.`song_count` WHERE `artist`.`song_count` != `song`.`song_count` AND `artist`.`id` = `song`.`artist_id`;";
        Dba::write($sql);
    }

    /**
     * Update artist last_update time.
     * @param int $object_id
     */
    public static function set_last_update($object_id): void
    {
        $sql = "UPDATE `artist` SET `last_update` = ? WHERE `id` = ?";
        Dba::write($sql, [time(), $object_id]);
    }

    /**
     * Migrate an object's associate stats to a new object
     * @param int $old_object_id
     * @param int $new_object_id
     */
    public static function migrate($old_object_id, $new_object_id): void
    {
        if ((int)$new_object_id > 0) {
            // migrating to a new artist
            $params = [$new_object_id, $old_object_id];
            $sql    = "UPDATE `song` SET `artist` = ? WHERE `artist` = ?;";
            Dba::write($sql, $params);
            $sql = "UPDATE `album` SET `album_artist` = ? WHERE `album_artist` = ?;";
            Dba::write($sql, $params);
            // migrate the maps and delete ones that aren't required
            $sql = "UPDATE IGNORE `artist_map` SET `artist_id` = ? WHERE `artist_id` = ?;";
            Dba::write($sql, $params);
            $sql = "UPDATE IGNORE `album_map` SET `object_id` = ? WHERE `object_id` = ? AND `object_type` = 'album';";
            Dba::write($sql, $params);
        } else {
            // removing the artist
            $params = [$old_object_id];
            $sql    = "UPDATE `song` SET `artist` = NULL WHERE `artist` = ?;";
            Dba::write($sql, $params);
            $sql = "UPDATE `album` SET `album_artist` = NULL WHERE `album_artist` = ?;";
            Dba::write($sql, $params);
        }

        // delete the old one if it's a dupe row above
        $sql = "DELETE FROM `artist_map` WHERE `artist_id` = ?;";
        Dba::write($sql, [$old_object_id]);
        $sql = "DELETE FROM `album_map` WHERE `object_id` = ? AND `object_type` = 'album';";
        Dba::write($sql, [$old_object_id]);
        self::update_table_counts();
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::ARTIST;
    }

    /**
     * @deprecated
     */
    private function getLabelRepository(): LabelRepositoryInterface
    {
        global $dic;

        return $dic->get(LabelRepositoryInterface::class);
    }

    /**
     * @deprecated
     */
    private function getLabelListUpdater(): LabelListUpdaterInterface
    {
        global $dic;

        return $dic->get(LabelListUpdaterInterface::class);
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
    private function getArtistTagUpdater(): ArtistTagUpdaterInterface
    {
        global $dic;

        return $dic->get(ArtistTagUpdaterInterface::class);
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
    private function getUseractivityRepository(): UserActivityRepositoryInterface
    {
        global $dic;

        return $dic->get(UserActivityRepositoryInterface::class);
    }

    /**
     * @deprecated Inject dependency
     */
    private function getArtistRepository(): ArtistRepositoryInterface
    {
        global $dic;

        return $dic->get(ArtistRepositoryInterface::class);
    }
}
