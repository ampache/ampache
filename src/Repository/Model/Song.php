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

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\Metadata\MetadataEnabledInterface;
use Ampache\Module\Metadata\MetadataManagerInterface;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\Song\Deletion\SongDeleterInterface;
use Ampache\Module\Song\Tag\SongTagWriterInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Ampache\Module\Util\Recommendation;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\MetadataRepositoryInterface;
use Ampache\Repository\ShareRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\WantedRepositoryInterface;
use DateTime;
use DateTimeInterface;
use Traversable;

class Song extends database_object implements
    Media,
    library_item,
    GarbageCollectibleInterface,
    CatalogItemInterface,
    MetadataEnabledInterface
{
    protected const DB_TABLENAME = 'song';

    public int $id = 0;

    public ?string $file = null;

    public int $catalog = 0;

    public int $album = 0;

    public int $album_disk = 0;

    public ?int $disk = null;

    public int $year;

    public ?int $artist = null;

    public ?string $title = null;

    public int $bitrate;

    public int $rate;

    public ?string $mode = null;

    public int $size;

    public int $time;

    public ?int $track = null;

    public ?string $mbid = null;

    public bool $played;

    public bool $enabled;

    public ?int $update_time = null;

    public ?int $addition_time = null;

    public ?int $user_upload = null;

    public ?int $license = null;

    public ?string $composer = null;

    public ?int $channels = null;

    public int $total_count;

    public int $total_skip;

    /**
     * song_data table
     */

    public ?string $comment = null;

    public ?string $lyrics = null;

    public ?string $label = null;

    public ?string $language = null;

    public ?string $waveform = null;

    public ?float $replaygain_track_gain = null;

    public ?float $replaygain_track_peak = null;

    public ?float $replaygain_album_gain = null;

    public ?float $replaygain_album_peak = null;

    public ?int $r128_album_gain = null;

    public ?int $r128_track_gain = null;

    public ?string $disksubtitle = null;

    /**
     * Generated data from other areas
     */

    public ?string $link = null;

    /** @var string $type */
    public $type;

    public ?string $mime = null;

    /** @var int[] $albumartists */
    public ?array $albumartists = null;

    public ?string $album_mbid = null;

    public ?string $artist_mbid = null;

    public ?string $albumartist_mbid = null;

    public ?int $albumartist = null;

    /** @var null|list<array{id: int, name: string, is_hidden: int, count: int}> $tags */
    public ?array $tags = null;

    /** @var int[] $artists */
    private ?array $artists = null;

    private ?string $f_album_link = null;

    private ?string $f_album_disk_link = null;

    private ?string $f_album_full = null;

    private ?string $f_albumartist_link = null;

    private ?string $artist_full_name = null;

    private ?string $f_artist_link = null;

    private ?string $f_link = null;

    private ?bool $has_art = null;

    private ?License $licenseObj = null;

    private bool $song_data_loaded = false;

    /**
     * Constructor
     *
     * Song class, for modifying a song.
     */
    public function __construct(?int $song_id = 0)
    {
        if (!$song_id) {
            return;
        }

        $info = $this->has_info($song_id);
        if ($info === []) {
            return;
        }

        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        $this->id   = (int)$song_id;
        $this->type = strtolower(pathinfo((string)$this->file, PATHINFO_EXTENSION));
        $this->mime = self::type_to_mime($this->type);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * insert
     *
     * This inserts the song described by the passed array
     * @param array<string, mixed> $results
     */
    public static function insert(array $results): ?int
    {
        $check_file = Catalog::get_id_from_file($results['file'], 'song');
        if ($check_file > 0) {
            return $check_file;
        }
        //debug_event(self::class, "insert results: " . print_r($results, true), 4);

        $catalog          = $results['catalog'];
        $file             = $results['file'];
        $title            = (isset($results['title'])) ? Catalog::check_length(Catalog::check_title($results['title'], $file)) : null;
        $artist           = (isset($results['artist'])) ? Catalog::check_length($results['artist']) : null;
        $album            = (isset($results['album'])) ? Catalog::check_length($results['album']) : null;
        $albumartist      = (isset($results['albumartist'])) ? Catalog::check_length($results['albumartist']) : null;
        $bitrate          = $results['bitrate'] ?? 0;
        $rate             = $results['rate'] ?? 0;
        $mode             = $results['mode'] ?? null;
        $size             = $results['size'] ?? 0;
        $time             = $results['time'] ?? 0;
        $track            = Catalog::check_track((string) $results['track']);
        $track_mbid       = $results['mb_trackid'] ?? $results['mbid'] ?? null;
        $album_mbid       = $results['mb_albumid'] ?? null;
        $album_mbid_group = $results['mb_albumid_group'] ?? null;
        $artist_mbid      = $results['mb_artistid'] ?? null;
        $albumartist_mbid = $results['mb_albumartistid'] ?? null;
        $disk             = (Album::sanitize_disk($results['disk']) > 0) ? Album::sanitize_disk($results['disk']) : 1;
        $disksubtitle     = $results['disksubtitle'] ?? null;
        $year             = Catalog::normalize_year($results['year'] ?? 0);
        $comment          = $results['comment'] ?? null;
        $tags             = $results['genre'] ?? []; // multiple genre support makes this an array
        $lyrics           = $results['lyrics'] ?? null;
        $user_upload      = $results['user_upload'] ?? null;
        $composer         = (isset($results['composer'])) ? Catalog::check_length($results['composer']) : null;
        $label            = (isset($results['publisher'])) ? Catalog::get_unique_string(Catalog::check_length($results['publisher'], 128)) : null;
        if ($label && AmpConfig::get('label')) {
            // create the label if missing
            foreach (array_map('trim', explode(';', $label)) as $label_name) {
                Label::helper($label_name);
            }
        }

        // info for the artist_map table.
        $artists_array          = $results['artists'] ?? [];
        $artist_mbid_array      = $results['mb_artistid_array'] ?? [];
        $albumartist_mbid_array = $results['mb_albumartistid_array'] ?? [];
        // if you have an artist array this will be named better than what your tags will give you
        if (!empty($artists_array)) {
            if (
                $artist !== '' &&
                $artist !== '0' &&
                (
                    $albumartist !== '' &&
                    $albumartist !== '0'
                ) &&
                $artist === $albumartist
            ) {
                $albumartist = (string)$artists_array[0];
            }

            $artist = (string)$artists_array[0];
        }

        $license_id = null;
        if (isset($results['license']) && (int)$results['license'] > 0) {
            $license_id = (int)$results['license'];
        }

        $language              = (isset($results['language'])) ? Catalog::check_length($results['language'], 128) : null;
        $channels              = $results['channels'] ?? null;
        $release_type          = (isset($results['release_type'])) ? Catalog::check_length($results['release_type'], 32) : null;
        $release_status        = $results['release_status'] ?? null;
        $replaygain_track_gain = $results['replaygain_track_gain'] ?? null;
        $replaygain_track_peak = $results['replaygain_track_peak'] ?? null;
        $replaygain_album_gain = $results['replaygain_album_gain'] ?? null;
        $replaygain_album_peak = $results['replaygain_album_peak'] ?? null;
        $r128_track_gain       = $results['r128_track_gain'] ?? null;
        $r128_album_gain       = $results['r128_album_gain'] ?? null;
        $original_year         = Catalog::normalize_year($results['original_year'] ?? 0);
        $barcode               = (isset($results['barcode'])) ? Catalog::check_length($results['barcode'], 64) : null;
        $catalog_number        = (isset($results['catalog_number'])) ? Catalog::check_length($results['catalog_number'], 64) : null;
        $version               = (isset($results['version'])) ? Catalog::check_length($results['version'], 64) : null;

        if (!in_array($mode, ['vbr', 'cbr', 'abr'])) {
            debug_event(self::class, 'Error analyzing: ' . $file . ' unknown file bitrate mode: ' . $mode, 2);
            $mode = null;
        }

        if (!isset($results['albumartist_id'])) {
            $albumartist_id = null;
            if ($albumartist !== null && $albumartist !== '' && $albumartist !== '0') {
                $albumartist_mbid = Catalog::trim_slashed_list($albumartist_mbid);
                $albumartist_id   = Artist::check($albumartist, $albumartist_mbid);
            }
        } else {
            $albumartist_id = (int)($results['albumartist_id']);
        }

        if (!isset($results['artist_id'])) {
            $artist_id = null;
            if ($artist !== null && $artist !== '' && $artist !== '0') {
                $artist_mbid = Catalog::trim_slashed_list($artist_mbid);
                $artist_id   = (int)Artist::check($artist, $artist_mbid);
            }
        } else {
            $artist_id = (int)($results['artist_id']);
        }

        if (!isset($results['album_id'])) {
            $album_id = (empty($album))
                ? 0
                : Album::check($catalog, $album, $year, $album_mbid, $album_mbid_group, $albumartist_id, $release_type, $release_status, $original_year, $barcode, $catalog_number, $version);
        } else {
            $album_id = (int)($results['album_id']);
        }

        // create the album_disk (if missing)
        $album_disk_id = AlbumDisk::check($album_id, $disk, $catalog, $disksubtitle);

        $insert_time = time();
        $sql         = "INSERT INTO `song` (`catalog`, `file`, `album`, `album_disk`, `disk`, `artist`, `title`, `bitrate`, `rate`, `mode`, `size`, `time`, `track`, `addition_time`, `update_time`, `year`, `mbid`, `user_upload`, `license`, `composer`, `channels`) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $db_results  = Dba::write($sql, [$catalog, $file, $album_id, $album_disk_id, $disk, $artist_id, $title, $bitrate, $rate, $mode, $size, $time, $track, $insert_time, $insert_time, $year, $track_mbid, $user_upload, $license_id, $composer ?: null, $channels]);
        if (!$db_results) {
            debug_event(self::class, 'Unable to insert ' . $file, 2);

            return null;
        }

        $song_id = (int)Dba::insert_id();
        $artists = [$artist_id, (int)$albumartist_id];

        // map the song to catalog album and artist maps
        Catalog::update_map((int)$catalog, 'song', $song_id);
        if ($artist_id > 0) {
            Artist::add_artist_map($artist_id, 'song', $song_id);
            Album::add_album_map($album_id, 'song', $artist_id);
        }

        if ((int)$albumartist_id > 0) {
            Artist::add_artist_map($albumartist_id, 'album', $album_id);
            Album::add_album_map($album_id, 'album', (int) $albumartist_id);
        }

        foreach ($artist_mbid_array as $songArtist_mbid) {
            $song_artist_id = Artist::check_mbid($songArtist_mbid);
            if ($song_artist_id > 0) {
                $artists[] = $song_artist_id;
                if ($song_artist_id != $artist_id) {
                    Artist::add_artist_map($song_artist_id, 'song', $song_id);
                    Album::add_album_map($album_id, 'song', $song_artist_id);
                }
            }
        }

        // add song artists found by name to the list (Ignore artist names when we have the same amount of MBID's)
        if (!empty($artists_array) && !count($artists_array) == count($artist_mbid_array)) {
            foreach ($artists_array as $artist_name) {
                $song_artist_id = (int)Artist::check($artist_name);
                if ($song_artist_id > 0) {
                    $artists[] = $song_artist_id;
                    if ($song_artist_id != $artist_id) {
                        Artist::add_artist_map($song_artist_id, 'song', $song_id);
                        Album::add_album_map($album_id, 'song', $song_artist_id);
                    }
                }
            }
        }

        foreach ($albumartist_mbid_array as $albumArtist_mbid) {
            $album_artist_id = Artist::check_mbid($albumArtist_mbid);
            if ($album_artist_id > 0) {
                $artists[] = $album_artist_id;
                if ($album_artist_id != $albumartist_id) {
                    Artist::add_artist_map($album_artist_id, 'album', $album_id);
                    Album::add_album_map($album_id, 'album', $album_artist_id);
                }
            }
        }

        // update the all the counts for the album right away
        Album::update_album_count($album_id);

        if ($user_upload) {
            self::getUserActivityPoster()->post((int) $user_upload, 'upload', 'song', $song_id, time());
        }

        // Allow scripts to populate new tags when injecting user uploads
        if (!defined('NO_SESSION') && ($user_upload && !Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER, $user_upload))) {
            $tags = Tag::clean_to_existing($tags);
        }

        if (is_array($tags)) {
            foreach ($tags as $tag) {
                $tag = trim((string)$tag);
                if ($tag !== '' && $tag !== '0') {
                    Tag::add('song', $song_id, $tag);
                    Tag::add('album', $album_id, $tag);
                    foreach (array_unique($artists) as $found_artist_id) {
                        if ($found_artist_id > 0) {
                            Tag::add('artist', $found_artist_id, $tag);
                        }
                    }
                }
            }
        }

        $sql = "INSERT INTO `song_data` (`song_id`, `disksubtitle`, `comment`, `lyrics`, `label`, `language`, `replaygain_track_gain`, `replaygain_track_peak`, `replaygain_album_gain`, `replaygain_album_peak`, `r128_track_gain`, `r128_album_gain`) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        Dba::write($sql, [$song_id, $disksubtitle ?: null, $comment ?: null, $lyrics ?: null, $label ?: null, $language ?: null, $replaygain_track_gain, $replaygain_track_peak, $replaygain_album_gain, $replaygain_album_peak, $r128_track_gain, $r128_album_gain]);

        return $song_id;
    }

    /**
     * garbage_collection
     *
     * Cleans up the song_data table
     */
    public static function garbage_collection(): void
    {
        debug_event(self::class, 'collectGarbage', 5);
        // delete files matching catalog_ignore_pattern
        $ignore_pattern = AmpConfig::get('catalog_ignore_pattern');
        if ($ignore_pattern) {
            Dba::write("DELETE FROM `song` WHERE `file` REGEXP ?;", [$ignore_pattern]);
        }

        // delete duplicates
        Dba::write("DELETE `dupe` FROM `song` AS `dupe`, `song` AS `orig` WHERE `dupe`.`id` > `orig`.`id` AND `dupe`.`file` <=> `orig`.`file`;");
        // clean up missing catalogs
        Dba::write("DELETE FROM `song` WHERE `song`.`catalog` NOT IN (SELECT `id` FROM `catalog`);");
        // delete the rest
        Dba::write("DELETE FROM `song_data` WHERE `song_data`.`song_id` NOT IN (SELECT `song`.`id` FROM `song`);");
        // also clean up some bad data that might creep in
        Dba::write("UPDATE `song` SET `composer` = NULL WHERE `composer` = '';");
        Dba::write("UPDATE `song` SET `mbid` = NULL WHERE `mbid` = '';");
        Dba::write("UPDATE `song_data` SET `comment` = NULL WHERE `comment` = '';");
        Dba::write("UPDATE `song_data` SET `lyrics` = NULL WHERE `lyrics` = '';");
        Dba::write("UPDATE `song_data` SET `label` = NULL WHERE `label` = '';");
        Dba::write("UPDATE `song_data` SET `language` = NULL WHERE `language` = '';");
        Dba::write("UPDATE `song_data` SET `waveform` = NULL WHERE `waveform` = '';");
        Dba::write("UPDATE `song_data` SET `disksubtitle` = NULL WHERE `disksubtitle` = '';");
    }

    /**
     * build_cache
     *
     * This attempts to reduce queries by asking for everything in the
     * browse all at once and storing it in the cache, this can help if the
     * db connection is the slow point.
     * @param list<int|string> $song_ids
     * @param string $limit_threshold
     * @return bool
     */
    public static function build_cache(array $song_ids, string $limit_threshold = ''): bool
    {
        if (empty($song_ids)) {
            return false;
        }

        $idlist = '(' . implode(',', $song_ids) . ')';
        if ($idlist == '()') {
            return false;
        }

        $artists = [];
        $albums  = [];

        // Song data cache
        $sql = (AmpConfig::get('catalog_disable'))
            ? "SELECT `song`.`id`, `song`.`file`, `song`.`catalog`, `song`.`album`, `song`.`album_disk`, `song`.`disk`, `song`.`year`, `song`.`artist`, `song`.`title`, `song`.`bitrate`, `song`.`rate`, `song`.`mode`, `song`.`size`, `song`.`time`, `song`.`track`, `song`.`mbid`, `song`.`played`, `song`.`enabled`, `song`.`update_time`, `song`.`addition_time`, `song`.`user_upload`, `song`.`license`, `song`.`composer`, `song`.`channels`, `song`.`total_count`, `song`.`total_skip` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `song`.`id` IN $idlist AND `catalog`.`enabled` = '1' "
            : "SELECT `song`.`id`, `song`.`file`, `song`.`catalog`, `song`.`album`, `song`.`album_disk`, `song`.`disk`, `song`.`year`, `song`.`artist`, `song`.`title`, `song`.`bitrate`, `song`.`rate`, `song`.`mode`, `song`.`size`, `song`.`time`, `song`.`track`, `song`.`mbid`, `song`.`played`, `song`.`enabled`, `song`.`update_time`, `song`.`addition_time`, `song`.`user_upload`, `song`.`license`, `song`.`composer`, `song`.`channels`, `song`.`total_count`, `song`.`total_skip` FROM `song` WHERE `song`.`id` IN $idlist";

        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            if (AmpConfig::get('show_played_times')) {
                $row['total_count'] = (empty($limit_threshold))
                    ? $row['total_count']
                    : Stats::get_object_count('song', $row['id'], $limit_threshold);
            }

            if (AmpConfig::get('show_skipped_times')) {
                $row['total_skip'] = (empty($limit_threshold))
                    ? $row['total_skip']
                    : Stats::get_object_count('song', $row['id'], $limit_threshold, 'skip');
            }

            $artists[$row['artist']] = $row['artist'];

            $albums[] = (int) $row['album'];

            parent::add_to_cache('song', $row['id'], $row);
        }

        Artist::build_cache($artists);
        Album::build_cache($albums);
        Tag::build_map_cache('song', $song_ids);
        Art::build_cache($albums);

        // If we're rating this then cache them as well
        if (AmpConfig::get('ratings')) {
            Rating::build_cache('song', $song_ids);
            Userflag::build_cache('song', $song_ids);
        }

        // Build a cache for the song's extended table
        $sql        = 'SELECT * FROM `song_data` WHERE `song_id` IN ' . $idlist;
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('song_data', $row['song_id'], $row);
        }

        return true;
    }

    private function has_info(int $song_id): array
    {
        if (parent::is_cached('song', $song_id)) {
            return parent::get_from_cache('song', $song_id);
        }

        $sql        = "SELECT `song`.`id`, `song`.`file`, `song`.`catalog`, `song`.`album`, `song`.`album_disk`, `song`.`disk`, `song`.`year`, `song`.`artist`, `song`.`title`, `song`.`bitrate`, `song`.`rate`, `song`.`mode`, `song`.`size`, `song`.`time`, `song`.`track`, `song`.`mbid`, `song`.`played`, `song`.`enabled`, `song`.`update_time`, `song`.`addition_time`, `song`.`user_upload`, `song`.`license`, `song`.`composer`, `song`.`channels`, `song`.`total_count`, `song`.`total_skip`, `album`.`album_artist` AS `albumartist`, `album`.`mbid` AS `album_mbid`, `artist`.`mbid` AS `artist_mbid`, `album_artist`.`mbid` AS `albumartist_mbid` FROM `song` LEFT JOIN `album` ON `album`.`id` = `song`.`album` LEFT JOIN `artist` ON `artist`.`id` = `song`.`artist` LEFT JOIN `artist` AS `album_artist` ON `album_artist`.`id` = `album`.`album_artist` WHERE `song`.`id` = ?";
        $db_results = Dba::read($sql, [$song_id]);
        $results    = Dba::fetch_assoc($db_results);
        if (isset($results['id'])) {
            parent::add_to_cache('song', $song_id, $results);

            return $results;
        }

        return [];
    }

    public static function has_id(int|string $song_id): bool
    {
        $sql        = "SELECT `song`.`id` FROM `song` WHERE `song`.`id` = ?";
        $db_results = Dba::read($sql, [$song_id]);
        $results    = Dba::fetch_assoc($db_results);

        return isset($results['id']);
    }

    /**
     * can_scrobble
     *
     * return a song id based on a last.fm-style search in the database
     */
    public static function can_scrobble(
        string $song_name,
        string $artist_name,
        string $album_name,
        string $song_mbid = '',
        string $artist_mbid = '',
        string $album_mbid = ''
    ): string {
        // by default require song, album, artist for any searches
        $sql    = "SELECT `song`.`id` FROM `song` LEFT JOIN `album` ON `album`.`id` = `song`.`album` LEFT JOIN `artist` ON `artist`.`id` = `song`.`artist` WHERE `song`.`title` = ? AND (`artist`.`name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ?) AND (`album`.`name` = ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) = ?)";
        $params = [
            $song_name,
            $artist_name,
            $artist_name,
            $album_name,
            $album_name,
        ];
        if (!empty($song_mbid)) {
            $sql .= " AND `song`.`mbid` = ?";
            $params[] = $song_mbid;
        }

        if (!empty($artist_mbid)) {
            $sql .= " AND `artist`.`mbid` = ?";
            $params[] = $artist_mbid;
        }

        if (!empty($album_mbid)) {
            $sql .= " AND `album`.`mbid` = ?";
            $params[] = $album_mbid;
        }

        $sql .= " LIMIT 1;";
        $db_results = Dba::read($sql, $params);
        $row        = Dba::fetch_assoc($db_results);
        if ($row === []) {
            debug_event(self::class, 'can_scrobble failed to find: ' . $song_name, 5);

            return '';
        }

        return $row['id'];
    }

    /**
     * _get_ext_info
     * This function gathers information from the song_ext_info table and adds it to the current object
     * @return array<string, scalar>
     */
    public function _get_ext_info(string $select = ''): array
    {
        if (parent::is_cached('song_data', $this->id)) {
            return parent::get_from_cache('song_data', $this->id);
        }

        $columns = (empty($select))
            ? '`comment`, `lyrics`, `label`, `language`, `waveform`, `replaygain_track_gain`, `replaygain_track_peak`, `replaygain_album_gain`, `replaygain_album_peak`, `r128_track_gain`, `r128_album_gain`, `disksubtitle`'
            : Dba::escape($select);

        $sql        = sprintf('SELECT %s FROM `song_data` WHERE `song_id` = ?', $columns);
        $db_results = Dba::read($sql, [$this->id]);
        if (!$db_results) {
            return [];
        }

        $results = Dba::fetch_assoc($db_results);

        if (empty($select)) {
            parent::add_to_cache('song_data', $this->id, $results);
        }

        return $results;
    }

    /**
     * fill_ext_info
     * This calls the _get_ext_info and then sets the correct vars
     */
    public function fill_ext_info(string $data_filter = ''): void
    {
        if ($this->isNew() || $this->song_data_loaded) {
            return;
        }

        $info = $this->_get_ext_info($data_filter);
        if (empty($info)) {
            return;
        }

        if (isset($info['song_id'])) {
            unset($info['song_id']);
        }

        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        // don't repeat this process if you've got it all
        $this->song_data_loaded = ($data_filter === '');
    }

    /**
     * type_to_mime
     *
     * Returns the mime type for the specified file extension/type
     */
    public static function type_to_mime(string $type): string
    {
        // FIXME: This should really be done the other way around.
        // Store the mime type in the database, and provide a function
        // to make it a human-friendly type.
        return match ($type) {
            'spx', 'ogg' => 'application/ogg',
            'opus' => 'audio/ogg; codecs=opus',
            'wma', 'asf' => 'audio/x-ms-wma',
            'rm', 'ra' => 'audio/x-realaudio',
            'flac' => 'audio/flac',
            'wv' => 'audio/x-wavpack',
            'aac', 'mp4', 'm4a', 'm4b' => 'audio/mp4',
            'aacp' => 'audio/aacp',
            'mpc' => 'audio/x-musepack',
            'mkv' => 'audio/x-matroska',
            'wav' => 'audio/wav',
            'webma' => 'audio/webm',
            default => 'audio/mpeg',
        };
    }

    /**
     * find
     * @param array<string, mixed> $data
     */
    public static function find(array $data): bool
    {
        $sql_base = "SELECT `song`.`id` FROM `song`";
        if ($data['mb_trackid']) {
            $sql        = $sql_base . " WHERE `song`.`mbid` = ? LIMIT 1";
            $db_results = Dba::read($sql, [$data['mb_trackid']]);
            if ($results = Dba::fetch_assoc($db_results)) {
                return $results['id'];
            }
        }

        if ($data['file']) {
            $sql        = $sql_base . " WHERE `song`.`file` = ? LIMIT 1";
            $db_results = Dba::read($sql, [$data['file']]);
            if ($results = Dba::fetch_assoc($db_results)) {
                return $results['id'];
            }
        }

        $where  = "WHERE `song`.`title` = ?";
        $sql    = $sql_base;
        $params = [$data['title']];
        if ($data['track']) {
            $where .= " AND `song`.`track` = ?";
            $params[] = $data['track'];
        }

        $sql .= " INNER JOIN `artist` ON `artist`.`id` = `song`.`artist`";
        $sql .= " INNER JOIN `album` ON `album`.`id` = `song`.`album`";

        if ($data['mb_artistid']) {
            $where .= " AND `artist`.`mbid` = ?";
            $params[] = $data['mb_artistid'];
        } else {
            $where .= " AND `artist`.`name` = ?";
            $params[] = $data['artist'];
        }

        if ($data['mb_albumid']) {
            $where .= " AND `album`.`mbid` = ?";
            $params[] = $data['mb_albumid'];
        } else {
            $where .= " AND `album`.`name` = ?";
            $params[] = $data['album'];
        }

        $sql .= $where . " LIMIT 1";
        $db_results = Dba::read($sql, $params);
        if ($results = Dba::fetch_assoc($db_results)) {
            return $results['id'];
        }

        return false;
    }

    /**
     * get_album_fullname
     * gets the name of $this->album, allows passing of id
     * @param int $album_id
     * @param bool $simple
     */
    public function get_album_fullname($album_id = 0, $simple = false): string
    {
        if ($this->f_album_full !== null && $album_id == 0) {
            return $this->f_album_full;
        }

        $album = ($album_id)
            ? new Album($album_id)
            : new Album($this->album);
        $this->f_album_full = $album->get_fullname($simple);

        return $this->f_album_full;
    }

    /**
     * get_album_disk_fullname
     * gets the name of $this->album, allows passing of id
     */
    public function get_album_disk_fullname(): string
    {
        $albumDisk = new AlbumDisk($this->album_disk);

        return $albumDisk->get_fullname();
    }

    /**
     * get_album_disk_subtitle
     * gets the disk subtitle allows passing of id
     */
    public function get_album_disk_subtitle(): ?string
    {
        $albumDisk = new AlbumDisk($this->album_disk);

        return $albumDisk->disksubtitle;
    }

    /**
     * get_album_catalog_number
     * gets the catalog_number of $this->album, allows passing of id
     * @param int $album_id
     */
    public function get_album_catalog_number($album_id = null): ?string
    {
        if ($album_id === null) {
            $album_id = $this->album;
        }

        $album = new Album($album_id);

        return $album->catalog_number;
    }

    /**
     * get_album_original_year
     * gets the original_year of $this->album, allows passing of id
     * @param int $album_id
     */
    public function get_album_original_year($album_id = null): ?int
    {
        if ($album_id === null) {
            $album_id = $this->album;
        }

        $album = new Album($album_id);

        return $album->original_year;
    }

    /**
     * get_album_barcode
     * gets the barcode of $this->album, allows passing of id
     * @param int $album_id
     */
    public function get_album_barcode($album_id = null): ?string
    {
        if (!$album_id) {
            $album_id = $this->album;
        }

        $album = new Album($album_id);

        return $album->barcode;
    }

    /**
     * get_artist_fullname
     * gets the name of $this->artist, allows passing of id
     */
    public function get_artist_fullname(): string
    {
        if ($this->artist_full_name === null) {
            $this->artist_full_name = Artist::get_fullname_by_id($this->artist);
        }

        return $this->artist_full_name;
    }

    /**
     * get_album_artist_fullname
     * gets the name of $this->albumartist, allows passing of id
     * @param int $album_artist_id
     */
    public function get_album_artist_fullname($album_artist_id = 0): ?string
    {
        if ($album_artist_id) {
            return Artist::get_fullname_by_id($album_artist_id);
        }

        return Artist::get_fullname_by_id($this->get_album_artist());
    }

    /**
     * get_album_mbid
     * gets the albumartist id for the song's album
     */
    public function get_album_mbid(): ?string
    {
        if ($this->album_mbid === null) {
            $db_results = Dba::read(
                'SELECT `mbid` FROM `album` WHERE `id` = ? LIMIT 1;',
                [$this->album]
            );
            if ($row = Dba::fetch_assoc($db_results)) {
                $this->album_mbid = $row['mbid'];
            }
        }

        return $this->album_mbid;
    }

    /**
     * get_artist_mbid
     * gets the albumartist id for the song's album
     */
    public function get_artist_mbid(): ?string
    {
        if ($this->artist_mbid === null) {
            $db_results = Dba::read(
                'SELECT `mbid` FROM `artist` WHERE `id` = ? LIMIT 1;',
                [$this->artist]
            );
            if ($row = Dba::fetch_assoc($db_results)) {
                $this->artist_mbid = $row['mbid'];
            }
        }

        return $this->artist_mbid;
    }

    /**
     * get_albumartist_mbid
     * gets the albumartist id for the song's album
     */
    public function get_albumartist_mbid(): ?string
    {
        if ($this->albumartist_mbid === null) {
            $db_results = Dba::read(
                'SELECT `mbid` FROM `artist` WHERE `id` = ? LIMIT 1;',
                [$this->get_album_artist()]
            );
            if ($row = Dba::fetch_assoc($db_results)) {
                $this->albumartist_mbid = $row['mbid'];
            }
        }

        return $this->albumartist_mbid;
    }

    /**
     * get_album_artist
     * gets the albumartist id for the song's album
     */
    public function get_album_artist(): ?int
    {
        if ($this->albumartist === null) {
            $this->albumartist = $this->getAlbumRepository()->getAlbumArtistId($this->album);
        }

        return $this->albumartist;
    }


    /**
     * get_album_artists
     * gets the albumartist id for the song's album
     * @return int[]
     */
    public function get_album_artists(): array
    {
        if ($this->albumartists === null) {
            $this->albumartists = self::get_parent_array($this->album, 'album');
        }

        return $this->albumartists;
    }

    /**
     * set_played
     * this checks to see if the current object has been played
     * if not then it sets it to played. In any case it updates stats.
     * @param array{
     *     latitude?: float,
     *     longitude?: float,
     *     name?: string
     * } $location
     */
    public function set_played(int $user_id, string $agent, array $location, int $date): bool
    {
        // ignore duplicates or skip the last track
        if (!$this->check_play_history($user_id, $agent, $date)) {
            return false;
        }

        // insert stats for each object type
        if (Stats::insert('song', $this->id, $user_id, $agent, $location, 'stream', $date)) {
            // followup on some stats too
            Stats::insert('album', $this->album, $user_id, $agent, $location, 'stream', $date);
            if ($this->album_disk) {
                Stats::count('album_disk', $this->album_disk, 'up');
            }
            // insert plays for song and album artists
            $artists = array_unique(array_merge(self::get_parent_array($this->id), self::get_parent_array($this->album, 'album')));
            foreach ($artists as $artist_id) {
                Stats::insert('artist', $artist_id, $user_id, $agent, $location, 'stream', $date);
            }

            // running total of the user stream data
            $play_size = User::get_user_data($user_id, 'play_size', 0)['play_size'];
            User::set_user_data($user_id, 'play_size', ($play_size + ($this->size / 1024 / 1024)));
        }

        // If it hasn't been played, set it
        if (!$this->played) {
            self::update_played(true, $this->id);
        }

        return true;
    }

    /**
     * check_play_history
     * this checks to see if the current object has been played
     * if not then it sets it to played. In any case it updates stats.
     * @param int $user
     * @param string $agent
     * @param int $date
     */
    public function check_play_history($user, $agent, $date): bool
    {
        return Stats::has_played_history('song', $this, $user, $agent, $date);
    }

    /**
     * compare_song_information
     * this compares the new ID3 tags of a file against
     * the ones in the database to see if they have changed
     * it returns false if nothing has changes, or the true
     * if they have. Static because it doesn't need this
     */
    public static function compare_song_information(Song $song, Song $new_song): array
    {
        $string_array = [
            'comment',
            'composer',
            'lyrics',
            'tags',
            'time',
            'title',
        ];

        // Skip stuff we don't care about as this function only needs to check song information.
        $skip_array = [
            'addition_time',
            'albumartist',
            'albumartist_mbid',
            'album_mbid',
            'artist_mbid',
            'catalog',
            'disabledMetadataFields',
            'enabled',
            'id',
            'mb_albumid_group',
            'mbid',
            'mime',
            'played',
            'song_data_loaded',
            'total_count',
            'total_skip',
            'type',
            'update_time',
            'waveform',
        ];

        return self::compare_media_information($song, $new_song, $string_array, $skip_array);
    }

    /**
     * compare_media_information
     * @param Song|Video $media
     * @param Song|Video $new_media
     * @param string[] $string_array
     * @param string[] $skip_array
     */
    public static function compare_media_information($media, $new_media, $string_array, $skip_array): array
    {
        $array            = [];
        $array['change']  = false;
        $array['element'] = [];

        // Pull out all the currently set vars
        $fields = get_object_vars($media);

        // Foreach them
        foreach (array_keys($fields) as $key) {
            $key = trim((string)$key);
            if (
                $key === '' ||
                $key === '0' ||
                in_array($key, $skip_array)
            ) {
                continue;
            }

            // Represent the value as a string for simpler comparison. For array, ensure to sort similarly old/new values
            if (is_array($media->$key)) {
                $arr = ($key === 'tags' && !empty($media->get_tags()))
                    ? array_column($media->get_tags(), 'name')
                    : $media->$key;
                sort($arr);
                $mediaData = implode(" ", $arr);
            } else {
                $mediaData = $media->$key;
            }

            // Skip the item if it is no string nor something we can turn into a string
            if (
                !is_string($mediaData) &&
                !is_numeric($mediaData) &&
                !is_bool($mediaData) &&
                (is_object($mediaData) && !method_exists($mediaData, '__toString'))
            ) {
                continue;
            }

            if (is_array($new_media->$key)) {
                $arr = ($key === 'tags' && !empty($new_media->get_tags()))
                    ? array_column($new_media->get_tags(), 'name')
                    : $new_media->$key;
                sort($arr);
                $newMediaData = implode(" ", $arr);
            } else {
                $newMediaData = $new_media->$key;
            }

            if (in_array($key, $string_array)) {
                // If it's a string thing
                $mediaData    = self::clean_string_field_value($mediaData);
                $newMediaData = self::clean_string_field_value($newMediaData);

                // tag case isn't important
                if ($key === 'tags') {
                    if (strtolower($mediaData) !== strtolower($newMediaData)) {
                        $array['change']        = true;
                        $array['element'][$key] = 'OLD: ' . $mediaData . ' --> ' . $newMediaData;
                    }
                } elseif ($mediaData !== $newMediaData) {
                    $array['change']        = true;
                    $array['element'][$key] = 'OLD: ' . $mediaData . ' --> ' . $newMediaData;
                }
            } elseif ($newMediaData !== null) {
                // NOT in array of strings
                if ($media->$key != $new_media->$key) {
                    $array['change']        = true;
                    $array['element'][$key] = 'OLD:' . $mediaData . ' --> ' . $newMediaData;
                }
            }
        }

        if ($array['change']) {
            debug_event(self::class, 'media-diff ' . json_encode($array['element']), 5);
        }

        return $array;
    }

    /**
     * clean_string_field_value
     * @param string $value
     */
    private static function clean_string_field_value($value): string
    {
        if (!$value) {
            return '';
        }

        $value = trim(stripslashes((string) preg_replace('/\s+/', ' ', $value)));

        // Strings containing only UTF-8 BOM = empty string
        if (strlen($value) == 2 && (ord($value[0]) == 0xFF || ord($value[0]) == 0xFE)) {
            $value = "";
        }

        return $value;
    }

    /**
     * update
     * This takes a key'd array of data does any cleaning it needs to
     * do and then calls the helper functions as needed.
     */
    public function update(array $data): int
    {
        foreach ($data as $key => $value) {
            //debug_event(self::class, $key . '=' . $value, 5);
            switch ($key) {
                case 'artist_name':
                    // Create new artist name and id
                    $old_artist_id = $this->artist;
                    $new_artist_id = (int)Artist::check($value);
                    if ($new_artist_id > 0) {
                        $this->artist = $new_artist_id;
                        self::update_artist($new_artist_id, $this->id, $old_artist_id);
                    }
                    break;
                case 'album_name':
                    // Create new album name and id
                    $old_album_id = $this->album;
                    $new_album_id = Album::check($this->catalog, $value);
                    $this->album  = $new_album_id;
                    self::update_album($new_album_id, $this->id, $old_album_id);
                    break;
                case 'artist':
                    // Change artist the song is assigned to
                    if ($value != $this->$key) {
                        $old_artist_id = $this->artist;
                        $new_artist_id = $value;
                        self::update_artist($new_artist_id, $this->id, $old_artist_id);
                    }
                    break;
                case 'album':
                    // Change album the song is assigned to
                    if ($value != $this->$key) {
                        $old_album_id = $this->$key;
                        $new_album_id = $value;
                        self::update_album($new_album_id, $this->id, $old_album_id);
                    }
                    break;
                case 'disk':
                    // Check to see if it needs to be updated
                    if ($value != $this->disk) {
                        // create the album_disk (if missing)
                        AlbumDisk::check($this->album, $value, $this->catalog, $this->get_album_disk_subtitle());

                        self::update_disk($value, $this->id);
                        $this->disk = $value;
                    }
                    break;
                case 'bitrate':
                case 'comment':
                case 'composer':
                case 'label':
                case 'language':
                case 'license':
                case 'mbid':
                case 'mode':
                case 'rate':
                case 'size':
                case 'title':
                case 'track':
                case 'year':
                    // Check to see if it needs to be updated
                    if ($value != $this->$key) {
                        /**
                         * @see self::update_year()
                         * @see self::update_title()
                         * @see self::update_track()
                         * @see self::update_mbid()
                         * @see self::update_license()
                         * @see self::update_composer()
                         * @see self::update_label()
                         * @see self::update_language()
                         * @see self::update_comment()
                         * @see self::update_bitrate()
                         * @see self::update_rate()
                         * @see self::update_mode()
                         * @see self::update_size()
                         */
                        $function = 'update_' . $key;
                        self::$function($value, $this->id);
                        $this->$key = $value;
                    }
                    break;
                case 'edit_tags':
                    Tag::update_tag_list($value, 'song', $this->id, true);
                    $this->tags = Tag::get_top_tags('song', $this->id);
                    break;
                case 'metadata':
                    $this->updateMetadata($value);
                    break;
            }
        }

        $this->getSongTagWriter()->write(
            $this
        );

        return $this->id;
    }

    /**
     * update_song
     * this is the main updater for a song and updates
     * the "update_time" of the song
     */
    public static function update_song(int $song_id, Song $new_song): void
    {
        $update_time = time();

        $sql = "UPDATE `song` SET `album` = ?, `album_disk` = ?, `disk` = ?, `year` = ?, `artist` = ?, `title` = ?, `composer` = ?, `bitrate` = ?, `rate` = ?, `mode` = ?, `channels` = ?, `size` = ?, `time` = ?, `track` = ?, `mbid` = ?, `update_time` = ? WHERE `id` = ?";
        Dba::write($sql, [$new_song->album, $new_song->album_disk, $new_song->disk, $new_song->year, $new_song->artist, $new_song->title, $new_song->composer ?: null, $new_song->bitrate, $new_song->rate, $new_song->mode, $new_song->channels, $new_song->size, $new_song->time, $new_song->track, $new_song->mbid, $update_time, $song_id]);

        $sql = "UPDATE `song_data` SET `label` = ?, `lyrics` = ?, `language` = ?, `disksubtitle` = ?, `comment` = ?, `replaygain_track_gain` = ?, `replaygain_track_peak` = ?, `replaygain_album_gain` = ?, `replaygain_album_peak` = ?, `r128_track_gain` = ?, `r128_album_gain` = ? WHERE `song_id` = ?";
        Dba::write($sql, [$new_song->label ?: null, $new_song->lyrics ?: null, $new_song->language ?: null, $new_song->disksubtitle ?: null, $new_song->comment ?: null, $new_song->replaygain_track_gain, $new_song->replaygain_track_peak, $new_song->replaygain_album_gain, $new_song->replaygain_album_peak, $new_song->r128_track_gain, $new_song->r128_album_gain, $song_id]);
    }

    /**
     * update_disk
     * update the disk tag
     * @param int $new_disk
     * @param int $song_id
     */
    public static function update_disk($new_disk, $song_id): void
    {
        self::_update_item('disk', $new_disk, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_year
     * update the year tag
     * @param int $new_year
     * @param int $song_id
     */
    public static function update_year($new_year, $song_id): void
    {
        self::_update_item('year', $new_year, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_label
     * This updates the label tag of the song
     * @param string $new_value
     * @param int $song_id
     */
    public static function update_label($new_value, $song_id): void
    {
        self::_update_ext_item('label', $new_value, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_language
     * This updates the language tag of the song
     * @param string $new_lang
     * @param int $song_id
     */
    public static function update_language($new_lang, $song_id): void
    {
        self::_update_ext_item('language', $new_lang, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_comment
     * updates the comment field
     * @param string $new_comment
     * @param int $song_id
     */
    public static function update_comment($new_comment, $song_id): void
    {
        self::_update_ext_item('comment', $new_comment, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_lyrics
     * updates the lyrics field
     * @param string $new_lyrics
     * @param int $song_id
     */
    public static function update_lyrics($new_lyrics, $song_id): void
    {
        self::_update_ext_item('lyrics', $new_lyrics, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_title
     * updates the title field
     * @param string $new_title
     * @param int $song_id
     */
    public static function update_title($new_title, $song_id): void
    {
        self::_update_item('title', $new_title, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_composer
     * updates the composer field
     * @param string $new_composer
     * @param int $song_id
     */
    public static function update_composer($new_composer, $song_id): void
    {
        self::_update_item('composer', $new_composer, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_bitrate
     * updates the bitrate field
     * @param int $new_bitrate
     * @param int $song_id
     */
    public static function update_bitrate($new_bitrate, $song_id): void
    {
        self::_update_item('bitrate', $new_bitrate, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_rate
     * updates the rate field
     * @param int $new_rate
     * @param int $song_id
     */
    public static function update_rate($new_rate, $song_id): void
    {
        self::_update_item('rate', $new_rate, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_mode
     * updates the mode field
     * @param string $new_mode
     * @param int $song_id
     */
    public static function update_mode($new_mode, $song_id): void
    {
        self::_update_item('mode', $new_mode, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_size
     * updates the size field
     * @param int $new_size
     * @param int $song_id
     */
    public static function update_size($new_size, $song_id): void
    {
        self::_update_item('size', $new_size, $song_id, AccessLevelEnum::CONTENT_MANAGER);
    }

    /**
     * update_time
     * updates the time field
     * @param int $new_time
     * @param int $song_id
     */
    public static function update_time($new_time, $song_id): void
    {
        self::_update_item('time', $new_time, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_track
     * this updates the track field
     * @param int $new_track
     * @param int $song_id
     */
    public static function update_track($new_track, $song_id): void
    {
        self::_update_item('track', $new_track, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_mbid
     * updates mbid field
     * @param string $new_mbid
     * @param int $song_id
     */
    public static function update_mbid($new_mbid, $song_id): void
    {
        self::_update_item('mbid', $new_mbid, $song_id, AccessLevelEnum::CONTENT_MANAGER);
    }

    /**
     * update_license
     * updates license field
     * @param int|null $new_license
     * @param int $song_id
     */
    public static function update_license($new_license, $song_id): void
    {
        self::_update_item('license', $new_license, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_artist
     * updates the artist field
     * @param int $new_artist
     * @param int $song_id
     * @param int|null $old_artist
     * @param bool $update_counts
     */
    public static function update_artist($new_artist, $song_id, $old_artist, $update_counts = true): bool
    {
        if ($old_artist != $new_artist && self::_update_item('artist', $new_artist, $song_id, AccessLevelEnum::CONTENT_MANAGER) !== false) {
            if ($update_counts && $old_artist) {
                self::migrate_artist($new_artist, $old_artist);
                Artist::update_table_counts();
            }

            return true;
        }

        return false;
    }

    /**
     * update_album
     * updates the album field
     * @param int $new_album
     * @param int $song_id
     * @param int $old_album
     * @param bool $update_counts
     */
    public static function update_album($new_album, $song_id, $old_album, $update_counts = true): bool
    {
        if ($old_album != $new_album && self::_update_item('album', $new_album, $song_id, AccessLevelEnum::CONTENT_MANAGER, true) !== false) {
            self::migrate_album($new_album, $song_id, $old_album);
            if ($update_counts) {
                Album::update_table_counts();
            }

            return true;
        }

        return false;
    }

    /**
     * update_utime
     * sets a new update time
     * @param int $song_id
     * @param int $time
     */
    public static function update_utime($song_id, $time = 0): void
    {
        if (!$time) {
            $time = time();
        }

        $sql = "UPDATE `song` SET `update_time` = ? WHERE `id` = ?;";
        Dba::write($sql, [$time, $song_id]);
    }

    /**
     * update_played
     * sets the played flag
     * @param bool $new_played
     * @param int $song_id
     */
    public static function update_played($new_played, $song_id): void
    {
        self::_update_item('played', (($new_played) ? 1 : 0), $song_id, AccessLevelEnum::USER);
    }

    /**
     * update_enabled
     * sets the enabled flag
     * @param bool $new_enabled
     * @param int $song_id
     */
    public static function update_enabled($new_enabled, $song_id): void
    {
        self::_update_item('enabled', (($new_enabled) ? 1 : 0), $song_id, AccessLevelEnum::MANAGER, true);
    }

    /**
     * _update_item
     * This is a private function that should only be called from within the song class.
     * It takes a field, value song id and level. first and foremost it checks the level
     * against Core::get_global('user') to make sure they are allowed to update this record
     * it then updates it and sets $this->{$field} to the new value
     * @param string $field
     * @param string|int|null $value
     * @param int $song_id
     * @param bool $check_owner
     */
    private static function _update_item($field, $value, $song_id, AccessLevelEnum $level, $check_owner = false): bool
    {
        if ($check_owner && Core::get_global('user') instanceof User) {
            $item = new Song($song_id);
            if (isset($item->id) && Core::get_global('user') instanceof User && $item->get_user_owner() == Core::get_global('user')->id) {
                $level = AccessLevelEnum::USER;
            }
        }

        /* Check them Rights! */
        if (!Access::check(AccessTypeEnum::INTERFACE, $level)) {
            return false;
        }

        /* Can't update to blank */
        if (!strlen(trim((string)$value)) && $field != 'comment') {
            return false;
        }

        $sql = sprintf('UPDATE `song` SET `%s` = ? WHERE `id` = ?', $field);

        return (Dba::write($sql, [$value, $song_id]) !== false);
    }

    /**
     * _update_ext_item
     * This updates a song record that is housed in the song_ext_info table
     * These are items that aren't used normally, and often large/informational only
     * @param string $field
     * @param string $value
     * @param int $song_id
     * @param bool $check_owner
     */
    private static function _update_ext_item($field, $value, $song_id, AccessLevelEnum $level, $check_owner = false): void
    {
        if ($check_owner) {
            $item = new Song($song_id);
            if ($item->id && Core::get_global('user') instanceof User && $item->get_user_owner() == Core::get_global('user')->id) {
                $level = AccessLevelEnum::USER;
            }
        }

        if (Access::check(AccessTypeEnum::INTERFACE, $level)) {
            $sql = sprintf('UPDATE `song_data` SET `%s` = ? WHERE `song_id` = ?', $field);
            Dba::write($sql, [$value, $song_id]) !== false;
        }
    }

    /**
     * Returns the filename of the media-item
     */
    public function getFileName(): string
    {
        $value = $this->get_artist_fullname() . ' - ';
        if ($this->track) {
            $value .= $this->track . ' - ';
        }

        return $value . ($this->get_fullname() . '.' . $this->type);
    }

    /**
     * does the item have art?
     */
    public function has_art(): bool
    {
        if ($this->has_art === null) {
            $this->has_art = (AmpConfig::get('show_song_art', false) && Art::has_db($this->id, 'song') || Art::has_db($this->album, 'album'));
        }

        return $this->has_art;
    }

    /**
     * Get item keywords for metadata searches.
     * @return array<string, array{important: bool, label: string, value: string}>
     */
    public function get_keywords(): array
    {
        return [
            'mb_trackid' => [
                'important' => false,
                'label' => T_('Track MusicBrainzID'),
                'value' => (string)$this->mbid,
            ],
            'artist' => [
                'important' => true,
                'label' => T_('Artist'),
                'value' => $this->get_artist_fullname(),
            ],
            'title' => [
                'important' => true,
                'label' => T_('Title'),
                'value' => (string)$this->get_fullname(),
            ],
        ];
    }

    /**
     * Get total count
     */
    public function get_totalcount(): int
    {
        return $this->total_count;
    }

    /**
     * Get item fullname.
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
            $web_path = AmpConfig::get_web_path();

            $this->link = $web_path . "/song.php?action=show_song&song_id=" . $this->id;
        }

        return $this->link;
    }

    /**
     * Get item tags.
     * @return list<array{id: int, name: string, is_hidden: int, count: int}>
     */
    public function get_tags(): array
    {
        if ($this->tags === null) {
            $this->tags = Tag::get_top_tags('song', $this->id);
        }

        return $this->tags;
    }

    /**
     * Get item f_tags.
     */
    public function get_f_tags(): string
    {
        return Tag::get_display($this->get_tags(), true, 'song');
    }

    /**
     * Get item f_link.
     */
    public function get_f_link(): string
    {
        // don't do anything if it's formatted
        if ($this->f_link === null) {
            $this->f_link = "<a href=\"" . scrub_out($this->get_link()) . "\" title=\"" . scrub_out($this->get_artist_fullname()) . " - " . scrub_out($this->get_fullname()) . "\"> " . scrub_out($this->get_fullname()) . "</a>";
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
            $web_path = AmpConfig::get_web_path();

            $this->f_artist_link = '';
            foreach ($this->get_artists() as $artist_id) {
                $artist_fullname = scrub_out(Artist::get_fullname_by_id($artist_id));
                if (!empty($artist_fullname)) {
                    $this->f_artist_link .= "<a href=\"" . $web_path . "/artists.php?action=show&artist=" . $artist_id . "\" title=\"" . $artist_fullname . "\">" . $artist_fullname . "</a>,&nbsp";
                }
            }

            $this->f_artist_link = rtrim($this->f_artist_link, ",&nbsp");
        }

        return $this->f_artist_link;
    }

    /**
     * Get item f_time or f_time_h.
     */
    public function get_f_time(?bool $hours = false): string
    {
        $min = floor($this->time / 60);
        $sec = sprintf("%02d", ($this->time % 60));
        if (!$hours) {
            return $min . ":" . $sec;
        }

        $hour  = sprintf("%02d", floor($min / 60));
        $min_h = sprintf("%02d", ($min % 60));

        return $hour . ":" . $min_h . ":" . $sec;
    }

    /**
     * Get item album_artists array
     */
    public function get_artists(): array
    {
        if ($this->artists === null) {
            $this->artists = self::get_parent_array($this->id);
        }

        return $this->artists;
    }

    /**
     * Get item f_albumartist_link.
     */
    public function get_f_albumartist_link(): string
    {
        // don't do anything if it's formatted
        if ($this->f_albumartist_link === null) {
            $web_path = AmpConfig::get_web_path();

            $this->f_albumartist_link = '';
            foreach ($this->get_album_artists() as $artist_id) {
                $artist_fullname = scrub_out(Artist::get_fullname_by_id($artist_id));
                if (!empty($artist_fullname)) {
                    $this->f_albumartist_link .= "<a href=\"" . $web_path . '/artists.php?action=show&artist=' . $artist_id . "\" title=\"" . $artist_fullname . "\">" . $artist_fullname . "</a>,&nbsp";
                }
            }

            $this->f_albumartist_link = rtrim($this->f_albumartist_link, ",&nbsp");
        }

        return $this->f_albumartist_link;
    }

    /**
     * Get item get_f_album_link.
     */
    public function get_f_album_link(): string
    {
        // don't do anything if it's formatted
        if ($this->f_album_link === null) {
            $web_path = AmpConfig::get_web_path();

            $this->f_album_link = '';
            $this->f_album_link = "<a href=\"" . $web_path . "/albums.php?action=show&album=" . $this->album . "\" title=\"" . scrub_out($this->get_album_fullname()) . "\"> " . scrub_out($this->get_album_fullname()) . "</a>";
        }

        return $this->f_album_link;
    }

    /**
     * Get item get_f_album_disk_link.
     */
    public function get_f_album_disk_link(): string
    {
        // don't do anything if it's formatted
        if ($this->f_album_disk_link === null) {
            $web_path = AmpConfig::get_web_path();

            $this->f_album_disk_link = '';
            $this->f_album_disk_link = "<a href=\"" . $web_path . "/albums.php?action=show_disk&album_disk=" . $this->album_disk . "\" title=\"" . scrub_out($this->get_album_disk_fullname()) . "\"> " . scrub_out($this->get_album_disk_fullname()) . "</a>";
        }

        return $this->f_album_disk_link;
    }

    /**
     * @return array{object_type: LibraryItemEnum, object_id: int}
     */
    public function get_parent(): array
    {
        return [
            'object_type' => LibraryItemEnum::ALBUM,
            'object_id' => $this->album,
        ];
    }

    /**
     * Get parent song artists.
     * @return int[]
     */
    public static function get_parent_array(int $object_id, ?string $type = 'artist'): array
    {
        $results = [];
        if (!$object_id) {
            return $results;
        }

        $sql = ($type == 'album')
            ? "SELECT DISTINCT `object_id` FROM `album_map` WHERE `object_type` = 'album' AND `album_id` = ?;"
            : "SELECT DISTINCT `artist_id` AS `object_id` FROM `artist_map` WHERE `object_type` = 'song' AND `object_id` = ?;";

        $db_results = Dba::read($sql, [$object_id]);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['object_id'];
        }

        return $results;
    }

    /**
     * @return array{string?: list<array{object_type: LibraryItemEnum, object_id: int}>}
     */
    public function get_childrens(): array
    {
        return [];
    }

    /**
     * Search for direct children of an object
     * @param string $name
     * @return list<array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function get_children(string $name): array
    {
        debug_event(self::class, 'get_children ' . $name, 5);

        return [];
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
            $medias[] = ['object_type' => LibraryItemEnum::SONG, 'object_id' => $this->id];
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
        return $this->user_upload;
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
        if ($this->comment !== null && $this->comment !== '' && $this->comment !== '0') {
            return $this->comment;
        }

        $album = new Album($this->album);

        return $album->get_description();
    }

    /**
     * display_art
     * @param array{width: int, height: int} $size
     */
    public function display_art(array $size, bool $force = false): void
    {
        $object_id = null;
        $type      = null;

        if (Art::has_db($this->id, 'song')) {
            $object_id = $this->id;
            $type      = 'song';
        } elseif (Art::has_db($this->album, 'album')) {
            $object_id = $this->album;
            $type      = 'album';
        } elseif (($this->artist && Art::has_db($this->artist, 'artist')) || $force) {
            $object_id = $this->artist;
            $type      = 'artist';
        }

        if ($object_id !== null && $type !== null) {
            Art::display($type, $object_id, (string)$this->get_fullname(), $size, $this->get_link());
        }
    }

    /**
     * play_url
     * This function takes all the song information and correctly formats a
     * stream URL taking into account the downsampling mojo and everything
     * else, this is the true function
     * @param string $additional_params
     * @param string $player
     * @param bool $local
     * @param int|string|false $uid
     * @param null|string $streamToken
     */
    public function play_url($additional_params = '', $player = '', $local = false, $uid = false, $streamToken = null): string
    {
        if ($this->isNew()) {
            return '';
        }

        if (!$uid) {
            // No user in the case of upnp. Set to 0 instead. required to fix database insertion errors
            $uid = Core::get_global('user')?->getId() ?? 0;
        }

        // set no use when using auth
        if (!AmpConfig::get('use_auth') && !AmpConfig::get('require_session')) {
            $uid = -1;
        }

        $downsample_remote = AmpConfig::get('downsample_remote', false);
        $lan_user          = $this->getNetworkChecker()->check(AccessTypeEnum::NETWORK, (int)$uid, AccessLevelEnum::DEFAULT);
        $transcode         = AmpConfig::get('transcode', 'default');

        // enforce or disable transcoding depending on local network ACL. Transcoding must also not be disabled with 'never'
        if (
            $downsample_remote &&
            $transcode !== 'never'
        ) {
            if (!$lan_user) {
                // remote network user will require transcoding with downsample_remote
                $transcode = 'required';
                debug_event(self::class, "Transcoding due to downsample_remote", 3);
            } else {
                // lan user is allowed to play original quality
                $transcode = 'never';
                debug_event(self::class, "NOT transcoding local network due to downsample_remote", 5);
            }
        }

        // if you transcode the media mime will change
        if (
            $transcode != 'never' &&
            (
                empty($additional_params) ||
                (
                    !str_contains($additional_params, '&bitrate=') &&
                    !str_contains($additional_params, '&format=')
                )
            )
        ) {
            $cache_path     = (string)AmpConfig::get('cache_path', '');
            $cache_target   = (string)AmpConfig::get('cache_target', '');
            $file_target    = Catalog::get_cache_path($this->id, $this->catalog, $cache_path, $cache_target);
            $bitrate        = (int)AmpConfig::get('transcode_bitrate', 128) * 1000;
            $transcode_type = ($file_target !== null && is_file($file_target))
                ? $cache_target
                : Stream::get_transcode_format($this->type, null, $player);
            if (
                $transcode_type !== null &&
                $transcode_type !== '' &&
                $transcode_type !== '0' &&
                ($this->type !== $transcode_type || $bitrate < $this->bitrate)
            ) {
                $this->type    = $transcode_type;
                $this->mime    = self::type_to_mime($transcode_type);
                $this->bitrate = $bitrate;

                // replace duplicate/incorrect parameters on the additional params
                $patterns = [
                    '/&format=[a-z]+/',
                    '/&transcode_to=[a-z|0-9]+/',
                    '/&bitrate=[0-9]+/',
                ];
                $additional_params = preg_replace($patterns, '', $additional_params);
                $additional_params .= '&transcode_to=' . $transcode_type . '&bitrate=' . $bitrate;
            }
        }

        $media_name = $this->get_stream_name() . "." . $this->type;
        $media_name = (string)preg_replace("/[^a-zA-Z0-9\. ]+/", "-", $media_name);
        $media_name = (AmpConfig::get('stream_beautiful_url'))
            ? urlencode($media_name)
            : rawurlencode($media_name);

        $url = Stream::get_base_url($local, $streamToken) . "type=song&oid=" . $this->id . "&uid=" . $uid . $additional_params;
        if ($player !== '') {
            $url .= "&player=" . $player;
        }

        $url .= "&name=" . $media_name;

        return Stream_Url::format($url);
    }

    /**
     * Get stream name.
     */
    public function get_stream_name(): string
    {
        return $this->get_artist_fullname() . " - " . $this->title;
    }

    /**
     * Get stream types.
     * @return list<string>
     */
    public function get_stream_types(?string $player = null): array
    {
        return Stream::get_stream_types_for_type($this->type, $player);
    }

    /**
     * Get transcode settings.
     * @param string|null $target
     * @param string|null $player
     * @param array{bitrate?: float|int, maxbitrate?: int, subtitle?: string, resolution?: string, quality?: int, frame?: float, duration?: float} $options
     * @return array{format?: string, command?: string}
     */
    public function get_transcode_settings(?string $target = null, ?string $player = null, array $options = []): array
    {
        return Stream::get_transcode_settings_for_media($this->type, $target, $player, 'song', $options);
    }

    /**
     * getYear
     */
    public function getYear(): string
    {
        return (string)($this->year ?: '');
    }

    /**
     * Get lyrics.
     * @return array{'text'?: string}
     */
    public function get_lyrics(): array
    {
        if ($this->lyrics === null) {
            $this->fill_ext_info('lyrics');
        }

        if ($this->lyrics) {
            return ['text' => $this->lyrics];
        }

        $user = Core::get_global('user');
        if ($user instanceof User) {
            foreach (Plugin::get_plugins(PluginTypeEnum::LYRIC_RETRIEVER) as $plugin_name) {
                $plugin = new Plugin($plugin_name);
                if ($plugin->_plugin !== null && $plugin->load($user)) {
                    $lyrics = $plugin->_plugin->get_lyrics($this);
                    if (!empty($lyrics)) {
                        // save the lyrics if not set before
                        if (array_key_exists('text', $lyrics) && !empty($lyrics['text'])) {
                            self::update_lyrics($lyrics['text'], $this->id);
                        }

                        return $lyrics;
                    }
                }
            }
        }

        return [];
    }

    /**
     * Run custom play action.
     * @param int $action_index
     * @param string $codec
     */
    public function run_custom_play_action($action_index, $codec = ''): array
    {
        $transcoder = [];
        $actions    = self::get_custom_play_actions();
        if ($action_index <= count($actions)) {
            $action = $actions[$action_index - 1];
            if (!$codec) {
                $codec = $this->type;
            }

            $run = str_replace("%f", $this->file ?? '%f', (string) $action['run']);
            $run = str_replace("%c", $codec, $run);
            $run = str_replace("%a", (empty($this->get_artist_fullname())) ? '%a' : $this->get_artist_fullname(), $run);
            $run = str_replace("%A", (empty($this->get_album_fullname())) ? '%A' : $this->get_album_fullname(), $run);
            $run = str_replace("%t", $this->get_fullname() ?? '%t', $run);

            debug_event(self::class, "Running custom play action: " . $run, 3);

            $descriptors = [
                1 => [
                    'pipe',
                    'w',
                ],
            ];
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                // Windows doesn't like to provide stderr as a pipe
                $descriptors[2] = [
                    'pipe',
                    'w'
                ];
            }

            $process = proc_open($run, $descriptors, $pipes);

            $transcoder['process'] = $process;
            $transcoder['handle']  = $pipes[1];
            $transcoder['stderr']  = $pipes[2];
            $transcoder['format']  = $codec;
        }

        return $transcoder;
    }

    /**
     * Get custom play actions.
     */
    public static function get_custom_play_actions(): array
    {
        $actions = [];
        $count   = 0;
        while (AmpConfig::get('custom_play_action_title_' . $count)) {
            $actions[] = [
                'index' => ($count + 1),
                'title' => AmpConfig::get('custom_play_action_title_' . $count),
                'icon' => AmpConfig::get('custom_play_action_icon_' . $count),
                'run' => AmpConfig::get('custom_play_action_run_' . $count)
            ];
            ++$count;
        }

        return $actions;
    }

    /**
     * Update Metadata from array
     * @param array<string, scalar> $meta_value
     */
    public function updateMetadata(array $meta_value): void
    {
        if ($this->getMetadataManager()->isCustomMetadataEnabled()) {
            $metadataRepository = $this->getMetadataRepository();

            foreach ($meta_value as $metadataId => $value) {
                $metadata = $metadataRepository->findById((int) $metadataId);
                if ($metadata && $value !== $metadata->getData()) {
                    $metadata->setData((string) $value);
                    $metadata->save();
                }
            }
        }
    }

    /**
     * get_deleted
     * get items from the deleted_songs table
     * @return list<array{
     *     id: int,
     *     addition_time: int,
     *     delete_time: int,
     *     title: string,
     *     file: string,
     *     catalog: int,
     *     total_count: int,
     *     total_skip: int,
     *     album: int,
     *     artist: int,
     * }>
     */
    public static function get_deleted(): array
    {
        $deleted    = [];
        $sql        = "SELECT * FROM `deleted_song`";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $deleted[] = [
                'id' => (int)$row['id'],
                'addition_time' => (int)$row['addition_time'],
                'delete_time' => (int)$row['delete_time'],
                'title' => $row['title'],
                'file' => $row['file'],
                'catalog' => (int)$row['catalog'],
                'total_count' => (int)$row['total_count'],
                'total_skip' => (int)$row['total_skip'],
                'album' => (int)$row['album'],
                'artist' => (int)$row['artist'],
            ];
        }

        return $deleted;
    }

    /**
     * Migrate an artist data to a new object
     * @param int $new_artist
     * @param int $old_artist
     */
    public static function migrate_artist($new_artist, $old_artist): bool
    {
        if ($old_artist != $new_artist) {
            // migrate stats for the old artist
            Useractivity::migrate('artist', $old_artist, $new_artist);
            Recommendation::migrate('artist', $old_artist);
            self::getShareRepository()->migrate('artist', $old_artist, $new_artist);
            self::getShoutRepository()->migrate('artist', $old_artist, $new_artist);
            Tag::migrate('artist', $old_artist, $new_artist);
            Userflag::migrate('artist', $old_artist, $new_artist);
            Rating::migrate('artist', $old_artist, $new_artist);
            Art::duplicate('artist', $old_artist, $new_artist);
            self::getWantedRepository()->migrateArtist($old_artist, $new_artist);
            Catalog::migrate_map('artist', $old_artist, $new_artist);
            // update mapping tables
            $sql = "UPDATE IGNORE `album_map` SET `object_id` = ? WHERE `object_id` = ?";
            if (Dba::write($sql, [$new_artist, $old_artist]) === false) {
                return false;
            }

            $sql = "UPDATE IGNORE `artist_map` SET `artist_id` = ? WHERE `artist_id` = ?";
            if (Dba::write($sql, [$new_artist, $old_artist]) === false) {
                return false;
            }

            $sql = "UPDATE IGNORE `catalog_map` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";
            if (Dba::write($sql, [$new_artist, 'artist', $old_artist]) === false) {
                return false;
            }

            // delete leftovers duplicate maps
            $sql = "DELETE FROM `album_map` WHERE `object_id` = ?";
            Dba::write($sql, [$old_artist]);
            $sql = "DELETE FROM `artist_map` WHERE `artist_id` = ?";
            Dba::write($sql, [$old_artist]);
            $sql = "DELETE FROM `catalog_map` WHERE `object_type` = ? AND `object_id` = ?";
            Dba::write($sql, ['artist', $old_artist]);
        }

        return true;
    }

    /**
     * Migrate an album data to a new object
     * @param int $new_album
     * @param int $song_id
     * @param int $old_album
     */
    public static function migrate_album($new_album, $song_id, $old_album): bool
    {
        // migrate stats for the old album
        Stats::migrate('album', $old_album, $new_album, $song_id);
        Useractivity::migrate('album', $old_album, $new_album);
        //Recommendation::migrate('album', $old_album);
        self::getShareRepository()->migrate('album', $old_album, $new_album);
        self::getShoutRepository()->migrate('album', $old_album, $new_album);
        Tag::migrate('album', $old_album, $new_album);
        Userflag::migrate('album', $old_album, $new_album);
        Rating::migrate('album', $old_album, $new_album);
        Art::duplicate('album', $old_album, $new_album);
        Catalog::migrate_map('album', $old_album, $new_album);

        // update mapping tables
        $sql = "UPDATE IGNORE `album_disk` SET `album_id` = ? WHERE `album_id` = ?";
        if (Dba::write($sql, [$new_album, $old_album]) === false) {
            return false;
        }

        if ($song_id > 0) {
            $sql = "UPDATE IGNORE `album_map` SET `album_id` = ? WHERE `album_id` = ? AND `object_id` = ? AND `object_type` = 'song'";
            if (Dba::write($sql, [$new_album, $old_album, $song_id]) === false) {
                return false;
            }
        } else {
            $sql = "UPDATE IGNORE `album_map` SET `album_id` = ? WHERE `album_id` = ? AND `object_type` = 'song'";
            if (Dba::write($sql, [$new_album, $old_album]) === false) {
                return false;
            }
        }

        $sql = "UPDATE IGNORE `artist_map` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";
        if (Dba::write($sql, [$new_album, 'album', $old_album]) === false) {
            return false;
        }

        $sql = "UPDATE IGNORE `catalog_map` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";
        if (Dba::write($sql, [$new_album, 'album', $old_album]) === false) {
            return false;
        }

        // delete leftovers duplicate maps
        $sql = "DELETE FROM `album_disk` WHERE `album_id` = ?";
        Dba::write($sql, [$old_album]);
        $sql = "DELETE FROM `album_map` WHERE `album_id` = ?";
        Dba::write($sql, [$old_album]);
        $sql = "DELETE FROM `artist_map` WHERE `object_type` = ? AND `object_id` = ?";
        Dba::write($sql, ['album', $old_album]);
        $sql = "DELETE FROM `catalog_map` WHERE `object_type` = ? AND `object_id` = ?";
        Dba::write($sql, ['album', $old_album]);

        return true;
    }

    /**
     * Returns the available metadata for this object
     *
     * @return Traversable<Metadata>
     */
    public function getMetadata(): Traversable
    {
        return $this->getMetadataManager()->getMetadata($this);
    }

    /**
     * remove
     * Delete the object from disk and/or database where applicable.
     */
    public function remove(): bool
    {
        return $this->getSongDeleter()->delete($this);
    }

    public function getLicense(): ?License
    {
        if (
            AmpConfig::get('licensing') &&
            $this->licenseObj === null &&
            $this->license !== null
        ) {
            $this->licenseObj = $this->getLicenseRepository()->findById($this->license);
        }

        return $this->licenseObj;
    }

    /**
     * Returns the metadata object-type
     */
    public function getMetadataItemType(): string
    {
        return 'song';
    }

    /**
     * @return list<string>
     */
    public function getIgnoredMetadataKeys(): array
    {
        return [
            'genre',
            'mb_albumartistid',
            'mb_albumid_group',
            'mb_albumid',
            'mb_artistid',
            'mb_trackid',
            'mbid',
            'publisher',
        ];
    }

    /**
     * Returns the path of the song
     */
    public function getFile(): string
    {
        return (string) $this->file;
    }

    /**
     * Returns the date at which the song was first added
     */
    public function getAdditionTime(): DateTimeInterface
    {
        return new DateTime('@' . $this->addition_time);
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::SONG;
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
     * @deprecated
     */
    private function getNetworkChecker(): NetworkCheckerInterface
    {
        global $dic;

        return $dic->get(NetworkCheckerInterface::class);
    }

    /**
     * @deprecated
     */
    private function getSongDeleter(): SongDeleterInterface
    {
        global $dic;

        return $dic->get(SongDeleterInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getUserActivityPoster(): UserActivityPosterInterface
    {
        global $dic;

        return $dic->get(UserActivityPosterInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getShoutRepository(): ShoutRepositoryInterface
    {
        global $dic;

        return $dic->get(ShoutRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private function getAlbumRepository(): AlbumRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getShareRepository(): ShareRepositoryInterface
    {
        global $dic;

        return $dic->get(ShareRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private function getLicenseRepository(): LicenseRepositoryInterface
    {
        global $dic;

        return $dic->get(LicenseRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private function getMetadataRepository(): MetadataRepositoryInterface
    {
        global $dic;

        return $dic->get(MetadataRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private function getMetadataManager(): MetadataManagerInterface
    {
        global $dic;

        return $dic->get(MetadataManagerInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getWantedRepository(): WantedRepositoryInterface
    {
        global $dic;

        return $dic->get(WantedRepositoryInterface::class);
    }
}
