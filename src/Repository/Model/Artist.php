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
use Ampache\Module\Artist\Tag\ArtistTagUpdaterInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\database_object;
use Ampache\Module\Database\DatabaseLockInterface;
use Ampache\Module\Label\LabelListUpdaterInterface;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\System\Plugin\Plugin;
use Ampache\Module\Util\VaInfo;
use Ampache\Plugin\AmpacheMusicBrainz;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;

class Artist extends database_object implements
    library_item,
    displayable_item,
    container_item,
    CatalogItemInterface
{
    protected const string DB_TABLENAME = 'artist';

    private static array $_mapcache = [];
    public ?int $addition_time      = null;
    public int $album_count         = 0;
    public int $album_disk_count    = 0;
    public int $id                  = 0;
    public int $last_update;
    public ?string $lastfm_url  = null;
    public ?string $link        = null;
    public bool $manual_update;
    public ?string $mbid        = null; // MusicBrainz ID
    public ?string $name        = null;
    public ?string $placeformed = null;
    public ?string $prefix      = null;
    public int $song_count      = 0;
    public ?string $summary     = null;
    public ?int $time           = null;
    public int $total_count     = 0;
    public int $total_skip      = 0;
    public ?int $user           = null;
    public ?int $yearformed     = null;
    private ?string $f_link     = null;
    private ?string $f_name     = null; // Prefix + Name, generated
    private ?bool $has_art      = null;

    /** @var array<int, array{id: int, name: string, user: int, count: int}> $moods */
    private ?array $moods = null;

    /** @var array<int, array{id: int, name: string, is_hidden: int, count: int}> $tags */
    private ?array $tags = null;

    /**
     * Artist class, for modifying an artist
     * Takes the ID of the artist and pulls the info from the db
     */
    public function __construct(
        ?int $artist_id = 0,
    ) {
        if (!$artist_id) {
            return;
        }

        $info = $this->get_info($artist_id, static::DB_TABLENAME);
        if ($info === []) {
            return;
        }

        $this->id               = (int) ($info['id'] ?? 0);
        $this->name             = $info['name'] ?? null;
        $this->prefix           = $info['prefix'] ?? null;
        $this->summary          = $info['summary'] ?? null;
        $this->mbid             = $info['mbid'] ?? null;
        $this->album_count      = (int) ($info['album_count'] ?? 0);
        $this->album_disk_count = (int) ($info['album_disk_count'] ?? 0);
        $this->song_count       = (int) ($info['song_count'] ?? 0);
        $this->time             = isset($info['time']) ? (int) $info['time'] : null;
        $this->total_count      = (int) ($info['total_count'] ?? 0);
        $this->total_skip       = (int) ($info['total_skip'] ?? 0);
        $this->yearformed       = isset($info['yearformed']) ? (int) $info['yearformed'] : null;
        $this->placeformed      = $info['placeformed'] ?? null;
        $this->user             = isset($info['user']) ? (int) $info['user'] : null;
        $this->addition_time    = isset($info['addition_time']) ? (int) $info['addition_time'] : null;
        $this->last_update      = (int) ($info['last_update'] ?? 0);
        $this->manual_update    = (bool) ($info['manual_update'] ?? false);
        $this->lastfm_url       = $info['lastfm_url'] ?? null;

        $this->time = (int) $this->time;
    }

    /**
     * Add artist map for a single item
     */
    public static function add_artist_map(?int $artist_id, string $object_type, int $object_id): void
    {
        if ((int) $artist_id > 0 && $object_id > 0) {
            self::getArtistRepository()->addArtistMap((int) $artist_id, $object_type, $object_id);
        }
    }

    /**
     * this attempts to build a cache of the data from the passed albums all in one query
     * @param array<int|string> $ids
     */
    public static function build_cache(array $ids, bool $extra = false, string $limit_threshold = ''): bool
    {
        if (empty($ids)) {
            return false;
        }

        // with the cache off these rows are discarded and the per-object queries still run, so this is a net loss
        if (!database_object::isCacheEnabled()) {
            return false;
        }

        $artistRepository = self::getArtistRepository();
        foreach ($artistRepository->getRowsByIds($ids) as $row) {
            parent::add_to_cache('artist', $row['id'], $row);
        }

        Art::build_cache($ids, 'artist');

        // Preload full names so get_fullname_by_id() stops querying one row at a time.
        foreach ($artistRepository->getFullNamesByIds($ids) as $artist_id => $fullName) {
            parent::add_to_cache('artist_fullname_by_id', $artist_id, [$fullName]);
        }

        // If we need to also pull the extra information, this is normally only used when we are doing the human display
        if (
            $extra
            && AmpConfig::get('show_played_times')
        ) {
            $played_counts = (empty($limit_threshold))
                ? []
                : Stats::get_object_counts('artist', $ids, $limit_threshold);
            foreach ($artistRepository->getPlayCountsByIds($ids) as $row) {
                $row['total_count'] = (empty($limit_threshold))
                    ? $row['total_count']
                    : ($played_counts[(int) $row['artist']] ?? 0);
                parent::add_to_cache('artist_extra', $row['artist'], $row);
            }
        }

        return true;
    }

    /**
     * check
     *
     * Checks for an existing artist; if none exists, insert one.
     */
    public static function check(string $name, ?string $mbid = '', ?int $user = null, bool $readonly = false): ?int
    {
        $split_artist = AmpConfig::get('split_artist_regex', false);
        $full_name    = ($split_artist && preg_match('/[^ ]' . $split_artist . '[^ ]/', $name))
            ? explode($split_artist, $name)[0]
            : $name;
        $trimmed = Catalog::trim_prefix(trim($name));
        $name    = $trimmed['string'];
        $prefix  = $trimmed['prefix'];
        $trimmed = Catalog::trim_featuring($name);
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
            $mbid   = '89ad4ac3-39f7-470e-963a-56509c546377';
            $prefix = null;
        }

        if (isset(self::$_mapcache[$name][$prefix ?? ''][$mbid ?? ''])) {
            return self::$_mapcache[$name][$prefix ?? ''][$mbid ?? ''];
        }

        $artist_id = self::_find_existing($name, $full_name, $mbid, $readonly);

        // cache and return the result
        if ($artist_id > 0) {
            self::$_mapcache[$name][$prefix ?? ''][$mbid ?? ''] = $artist_id;

            return $artist_id;
        }

        if ($readonly) {
            return null;
        }

        // prefer the name of the artist as provided by MusicBrainz
        if ($mbid !== null && $mbid !== '' && $mbid !== '0') {
            $plugin      = new Plugin('musicbrainz');
            $parsed_mbid = VaInfo::parse_mbid($mbid);
            $data        = ($parsed_mbid && $plugin->_plugin instanceof AmpacheMusicBrainz)
                ? $plugin->_plugin->get_artist($parsed_mbid)
                : [];
            if (array_key_exists('name', $data)) {
                $trimmed = Catalog::trim_prefix(trim((string) $data['name']));
                $name    = $trimmed['string'];
                $prefix  = $trimmed['prefix'];
            }
        }

        // if all else fails, insert a new artist, cache it and return the id
        $mbid = ($mbid === null || $mbid === '' || $mbid === '0')
            ? null
            : $mbid;

        // concurrent requests can each miss the lookup above and insert the same artist, so serialize on the name
        $lock       = self::getDatabaseLock();
        $lock_name  = sprintf('artist|%s|%s|%s', $name, $prefix ?? '', $mbid ?? '');
        $lock_taken = $lock->acquire($lock_name);

        try {
            // whoever held the lock may have inserted this artist while we waited for it
            if ($lock_taken) {
                $artist_id = self::_find_existing($name, $full_name, $mbid, $readonly);
                if ($artist_id > 0) {
                    self::$_mapcache[$name][$prefix ?? ''][$mbid ?? ''] = $artist_id;

                    return $artist_id;
                }
            }

            $artist_id = self::getArtistRepository()->create($name, $prefix, $mbid, $user);
            if ($artist_id === null) {
                return null;
            }
            debug_event(self::class, sprintf('check artist: created {%d}', $artist_id), 4);
            // map the new id
            Catalog::update_map(0, 'artist', $artist_id);
        } finally {
            if ($lock_taken) {
                $lock->release($lock_name);
            }
        }

        self::$_mapcache[$name][$prefix ?? ''][$mbid ?? ''] = $artist_id;

        return $artist_id;
    }

    /**
     * check_mbid
     *
     * Checks for an existing artist by mbid; if none exists, insert one.
     */
    public static function check_mbid(?string $mbid): int
    {
        $artist_id   = 0;
        $parsed_mbid = VaInfo::parse_mbid($mbid);

        // check for artists by mbid and split-mbid
        if ($parsed_mbid) {
            $artist_id = self::getArtistRepository()->findIdByMbid($parsed_mbid) ?? 0;

            // return the result
            if ($artist_id > 0) {
                return $artist_id;
            }

            // if that fails, insert a new artist and return the id
            $plugin = new Plugin('musicbrainz');
            $data   = ($plugin->_plugin instanceof AmpacheMusicBrainz)
                ? $plugin->_plugin->get_artist($parsed_mbid)
                : [];
            if (array_key_exists('name', $data)) {
                $trimmed = Catalog::trim_prefix(trim((string) $data['name']));
                $name    = $trimmed['string'];
                $prefix  = $trimmed['prefix'];

                $created = self::getArtistRepository()->create($name, $prefix, $parsed_mbid, null);
                if ($created === null) {
                    return $artist_id;
                }

                $artist_id = $created;
                debug_event(self::class, sprintf('check mbid: created {%d} ', $artist_id) . $data['name'], 4);
            }
        }

        return $artist_id;
    }

    /**
     * construct_from_array
     * This is used by the metadata class specifically but fills out a Artist object based on a key'd array
     * @param array{
     *     id: int,
     *     name: ?string,
     *     prefix: ?string,
     *     mbid: ?string,
     *     summary: ?string,
     *     placeformed: ?string,
     *     yearformed: ?string,
     *     last_update: ?string,
     *     user: ?string,
     *     manual_update: ?string,
     *     time: ?string,
     *     album_count: int,
     *     song_count: int,
     *     album_disk_count: int,
     *     total_count: int,
     *     total_skip: int,
     *     addition_time: ?string,
     *     weight: ?string
     * } $data
     */
    public static function construct_from_array(array $data): Artist
    {
        $artist                   = new Artist(0);
        $artist->id               = (int) $data['id'];
        $artist->name             = $data['name'] ?? null;
        $artist->prefix           = $data['prefix'] ?? null;
        $artist->summary          = $data['summary'] ?? null;
        $artist->mbid             = $data['mbid'] ?? null;
        $artist->album_count      = ($data['album_count']) ? (int) $data['album_count'] : 0;
        $artist->album_disk_count = ($data['album_disk_count']) ? (int) $data['album_disk_count'] : 0;
        $artist->song_count       = ($data['song_count']) ? (int) $data['song_count'] : 0;
        $artist->time             = ($data['time']) ? (int) $data['time'] : null;
        $artist->total_count      = ($data['total_count']) ? (int) $data['total_count'] : 0;
        $artist->total_skip       = ($data['total_skip']) ? (int) $data['total_skip'] : 0;
        $artist->yearformed       = ($data['yearformed']) ? (int) $data['yearformed'] : null;
        $artist->placeformed      = $data['placeformed'] ?? null;
        $artist->user             = ($data['user']) ? (int) $data['user'] : null;
        $artist->addition_time    = ($data['addition_time']) ? (int) $data['addition_time'] : null;
        $artist->last_update      = ($data['last_update']) ? (int) $data['last_update'] : 0;
        $artist->manual_update    = ($data['manual_update']) ? (bool) $data['manual_update'] : false;

        return $artist;
    }

    /**
     * get_artist_map
     *
     * This returns an ids of artists that have songs/albums mapped
     * @return int[]
     */
    public static function get_artist_map(string $object_type, int $object_id): array
    {
        return self::getArtistRepository()->getObjectMap($object_type, $object_id);
    }

    /**
     * get_display
     * This returns a csv formatted version of the artists that we are given
     * @param int[] $artists
     */
    public static function get_display(array $artists): string
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
     * Get item fullname by the artist id.
     */
    public static function get_fullname_by_id(?int $artist_id = 0): string
    {
        if (empty($artist_id)) {
            return '';
        }

        if (database_object::is_cached('artist_fullname_by_id', $artist_id)) {
            return database_object::get_from_cache('artist_fullname_by_id', $artist_id)[0];
        }

        $fullName = self::getArtistRepository()->getFullNameById($artist_id);
        if ($fullName !== null) {
            database_object::add_to_cache('artist_fullname_by_id', $artist_id, [$fullName]);

            return $fullName;
        }

        return '';
    }

    /**
     * get_id_array
     *
     * Get info from the artist table with the minimum detail required for subsonic
     * @return array{
     *     id: int,
     *     f_name: string,
     *     name: string,
     *     album_count: int,
     *     song_count: int,
     *     catalog_id: int,
     * }
     */
    public static function get_id_array(int $artist_id): array
    {
        return self::getArtistRepository()->getIdArray($artist_id) ?? [
            'id' => 0,
            'f_name' => '',
            'name' => '',
            'album_count' => 0,
            'song_count' => 0,
            'catalog_id' => 0,
        ];
    }

    /**
     * get_id_arrays
     *
     * Get each id from the artist table with the minimum detail required for subsonic
     * @param int[] $catalogs
     * @return array<int, array{
     *     id: int,
     *     f_name: string,
     *     name: string,
     *     album_count: int,
     *     song_count: int,
     *     catalog_id: int,
     *     has_art: int
     * }>
     */
    public static function get_id_arrays(array $catalogs = [], bool $album_artist = false): array
    {
        $artistRepository = self::getArtistRepository();
        $results          = [];

        // an empty catalog list means "every catalog", which is a different statement rather than a wider filter
        if (!empty($catalogs)) {
            foreach ($catalogs as $catalog_id) {
                foreach ($artistRepository->getIdArrayRows($catalog_id, $album_artist) as $row) {
                    $results[] = $row + ['catalog_id' => $catalog_id];
                }
            }
        } else {
            foreach ($artistRepository->getIdArrayRows(null, $album_artist) as $row) {
                $results[] = $row + ['catalog_id' => 0];
            }
        }

        return $results;
    }

    /**
     * Get item prefix, basename and name by the artist id.
     * @return array{
     *     id: string,
     *     name: string,
     *     prefix: string,
     *     basename: string
     * }
     */
    public static function get_name_array_by_id(?int $artist_id = 0): array
    {
        if ($artist_id === 0) {
            return [
                "id" => '0',
                "name" => T_('Various'),
                "prefix" => '',
                "basename" => T_('Various')
            ];
        }

        return self::getArtistRepository()->getNameArrayById((int) $artist_id) ?? [
            "id" => '',
            "name" => '',
            "prefix" => '',
            "basename" => '',
        ];
    }

    public static function is_upload(int $artist_id): bool
    {
        return self::getArtistRepository()->getUploaderId($artist_id) > 0;
    }

    /**
     * Migrate an object's associate stats to a new object
     */
    public static function migrate(int $old_object_id, int $new_object_id): void
    {
        self::getArtistRepository()->migrate($old_object_id, $new_object_id);
        self::update_table_counts();
    }

    /**
     * Delete the artist map for a single item
     */
    public static function remove_artist_map(int $artist_id, string $object_type, int $object_id): void
    {
        if ($artist_id > 0 && $object_id > 0) {
            self::getArtistRepository()->removeArtistMap($artist_id, $object_type, $object_id);
        }
    }

    /**
     * Update artist last_update time.
     */
    public static function set_last_update(int $object_id): void
    {
        self::getArtistRepository()->setField($object_id, ArtistFieldEnum::LAST_UPDATE, time());
    }

    /**
     * update_artist_count
     */
    public static function update_artist_count(int $artist_id): void
    {
        debug_event(self::class, 'update_artist_count ' . $artist_id, 5);
        self::getArtistRepository()->updateCounts($artist_id);
    }

    /**
     * update_name_from_mbid
     *
     * Refresh your atist name using external data based on the mbid
     * @return array{prefix: ?string,name: string}
     */
    public static function update_name_from_mbid(string $new_name, string $mbid): array
    {
        $split_artist = AmpConfig::get('split_artist_regex', false);
        $new_name     = ($split_artist && preg_match('/[^ ]' . $split_artist . '[^ ]/', $new_name))
            ? explode($split_artist, $new_name)[0]
            : $new_name;
        $trimmed = Catalog::trim_prefix(trim($new_name));
        $name    = $trimmed['string'];
        $prefix  = $trimmed['prefix'];
        $trimmed = Catalog::trim_featuring($name);
        $name    = $trimmed[0];
        debug_event(self::class, sprintf('update_name_from_mbid: rename {%s} to {%s} {%s}', $mbid, $prefix, $name), 4);

        self::getArtistRepository()->renameByMbid($mbid, $prefix, $name);

        return [
            'name' => $name,
            'prefix' => $prefix,
        ];
    }

    /**
     * update_table_counts
     */
    public static function update_table_counts(): void
    {
        debug_event(self::class, 'update_table_counts', 5);
        self::getArtistRepository()->updateAllCounts();
    }

    /**
     * Looks for an existing artist, back-filling a known mbid onto matching rows that were stored without one
     */
    private static function _find_existing(string $name, string $full_name, ?string $mbid, bool $readonly): int
    {
        $artistRepository = self::getArtistRepository();
        if ($mbid !== null && $mbid !== '' && $mbid !== '0') {
            // check for artists by mbid (there should only ever be one sent here); the name match below only back-fills the mbid
            $artist_id = $artistRepository->findIdByMbid($mbid) ?? 0;

            // still missing? Match on the name and update the mbid
            if (!$readonly) {
                foreach ($artistRepository->findIdsByNameWithoutMbid($name, $full_name) as $matched_id) {
                    $artistRepository->setField($matched_id, ArtistFieldEnum::MBID, $mbid);
                }
            }

            return $artist_id;
        }

        // look for artists with no mbid (if they exist) and then match on mbid artists last
        return $artistRepository->findIdByName($name, $full_name, false)
            ?? $artistRepository->findIdByName($name, $full_name, true)
            ?? 0;
    }

    /**
     * @deprecated Inject dependency
     */
    private static function getArtistRepository(): ArtistRepositoryInterface
    {
        global $dic;

        return $dic->get(ArtistRepositoryInterface::class);
    }

    /**
     * @deprecated Inject dependency
     */
    private static function getDatabaseLock(): DatabaseLockInterface
    {
        global $dic;

        return $dic->get(DatabaseLockInterface::class);
    }

    /**
     * display_art
     * @param array{width: int, height: int} $size
     */
    public function display_art(array $size, bool $force = false): void
    {
        if (Art::has_db($this->id, 'artist') || $force) {
            Art::display('artist', $this->id, (string) $this->get_fullname(), $size, $this->get_link());
        }
    }

    /**
     * Get album count for album or album_disk based on config
     */
    public function get_album_count(): int
    {
        return (AmpConfig::get('album_group'))
            ? $this->album_count
            : $this->album_disk_count;
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
        return Mood::get_display($this->get_moods(), true, 'artist');
    }

    /**
     * Return a formatted link to the parent object (if appliccable)
     */
    public function get_f_parent_link(): ?string
    {
        return null;
    }

    /**
     * Get item f_tags.
     */
    public function get_f_tags(): string
    {
        return Tag::get_display($this->get_tags(), true, 'artist');
    }

    /**
     * format time to Hours:Minutes:Seconds.
     */
    public function get_f_time(): string
    {
        $min   = sprintf("%02d", (floor($this->time / 60) % 60));
        $sec   = sprintf("%02d", ($this->time % 60));
        $hours = floor($this->time / 3600);

        return ltrim($hours . ':' . $min . ':' . $sec, '0:');
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
     * Get item keywords for metadata searches.
     * @return array<string, array{important: bool, label: string, value: string}>
     */
    public function get_keywords(): array
    {
        return [
            'mb_artistid' => [
                'important' => false,
                'label' => T_('Artist MusicBrainzID'),
                'value' => (string) $this->mbid,
            ],
            'artist' => [
                'important' => true,
                'label' => T_('Artist'),
                'value' => (string) $this->get_fullname(),
            ],
        ];
    }

    /**
     * Get item Label associations.
     * @return string[]
     */
    public function get_labels(): array
    {
        return $this->getLabelRepository()->getByArtist($this->id);
    }

    /**
     * Get item link.
     */
    public function get_link(): string
    {
        // don't do anything if it's formatted
        if ($this->link === null) {
            $web_path = AmpConfig::get_web_path('/client');

            $this->link = $web_path . '/artists.php?action=show&artist=' . $this->id;
        }

        return $this->link ?? '';
    }

    /**
     * Get all childrens and sub-childrens medias.
     *
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
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
     * Get item moods.
     * @return array<int, array{id: int, name: string, user: int, count: int}>
     */
    public function get_moods(): array
    {
        if ($this->moods === null) {
            $this->moods = Mood::get_top_moods('artist', $this->id, 0);
        }

        return $this->moods;
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
     * get_songs
     *
     * Get each album id for the artist
     * @return int[]
     */
    public function get_songs(): array
    {
        return self::getArtistRepository()->getAlbumIds($this->id);
    }

    /**
     * Get item tags.
     * @return array<int, array{id: int, name: string, is_hidden: int, count: int}>
     */
    public function get_tags(): array
    {
        if ($this->tags === null) {
            $this->tags = Tag::get_top_tags('artist', $this->id);
        }

        return $this->tags ?? [];
    }

    /**
     * Get item's owner.
     */
    public function get_user_owner(): ?int
    {
        return $this->user;
    }

    /**
     * Returns the id of the catalog the item is associated to
     */
    public function getCatalogId(): int
    {
        return 0;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::ARTIST;
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

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * update
     * This takes a key'd array of data and updates the current artist
     * @param array{
     *     name?: string,
     *     mbid?: ?string,
     *     summary?: ?string,
     *     placeformed?: ?string,
     *     yearformed?: ?int,
     *     user?: ?int,
     *     overwrite_childs?: string,
     *     add_to_childs?: string,
     *     edit_tags?: string,
     *     edit_moods?: string,
     *     edit_labels?: string
     * } $data
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
        $yearformed  = is_numeric($data['yearformed'] ?? null) ? (int) $data['yearformed'] : null;
        $user        = is_numeric($data['user'] ?? null) ? (int) $data['user'] : null;
        $current_id  = $this->id;

        // Check if name is different than the current name
        if ($this->prefix != $prefix || $this->name != $name) {
            $updated   = false;
            $artist_id = (int) self::check($name, $mbid, $user, true);

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
                self::getArtistRepository()->collectGarbage();
                Stats::garbage_collection();
                Rating::garbage_collection();
                Userflag::garbage_collection();
                $this->getLabelRepository()->collectGarbage();
                $this->getUseractivityRepository()->collectGarbage();
                self::update_table_counts();
            } // if updated
        } elseif ($this->mbid != $mbid) {
            self::getArtistRepository()->setField($current_id, ArtistFieldEnum::MBID, $mbid);
        }

        $this->update_artist_info($summary, $placeformed, $yearformed, true);

        $this->prefix = $prefix;
        $this->name   = $name;
        $this->mbid   = $mbid;

        if (
            $user
            && $this->user != $user
        ) {
            self::getArtistRepository()->setField($current_id, ArtistFieldEnum::USER, $user);
        }

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
                (string) $data['edit_tags'],
                $override_childs,
                $add_to_childs,
                true
            );
        }

        if (isset($data['edit_moods'])) {
            // no from_file_tags, so these belong to whoever is editing and outlive the next scan
            Mood::update_mood_list((string) $data['edit_moods'], 'artist', $this->id, true);
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
     */
    public function update_artist_info(?string $summary, ?string $placeformed = null, ?int $yearformed = null, bool $manual = false, ?string $lastfm_url = null): void
    {
        // set null values if missing
        $summary     = (empty($summary)) ? null : $summary;
        $placeformed = (empty($placeformed)) ? null : $placeformed;
        $yearformed  = ((int) $yearformed == 0) ? null : Catalog::normalize_year($yearformed);
        $lastfm_url  = (empty($lastfm_url)) ? null : $lastfm_url;

        self::getArtistRepository()->updateInfo($this->id, $summary, $placeformed, $yearformed, time(), $manual, $lastfm_url);

        $this->summary     = $summary;
        $this->placeformed = $placeformed;
        $this->yearformed  = $yearformed;
        $this->lastfm_url  = $lastfm_url;
    }

    /**
     * Update artist associated user.
     */
    public function update_artist_name(string $name, ?string $prefix = null): void
    {
        self::getArtistRepository()->rename($this->id, $prefix, $name);
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
    private function getLabelListUpdater(): LabelListUpdaterInterface
    {
        global $dic;

        return $dic->get(LabelListUpdaterInterface::class);
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
}
