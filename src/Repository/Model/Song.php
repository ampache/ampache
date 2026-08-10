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
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\database_object;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\Label\LabelNameFilterInterface;
use Ampache\Module\Metadata\MetadataEnabledInterface;
use Ampache\Module\Metadata\MetadataManagerInterface;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\Song\Deletion\SongDeleterInterface;
use Ampache\Module\Song\Tag\SongTagWriterInterface;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\System\Core;
use Ampache\Module\System\Plugin\Plugin;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\User\Activity\Useractivity;
use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Ampache\Module\Util\Recommendation;
use Ampache\Plugin\PluginGetLyricsInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\MetadataRepositoryInterface;
use Ampache\Repository\ShareRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\WantedRepositoryInterface;
use DateTime;
use DateTimeInterface;
use Traversable;

class Song extends database_object implements
    Media,
    displayable_item,
    container_item,
    GarbageCollectibleInterface,
    CatalogItemInterface,
    MetadataEnabledInterface
{
    // the value a player or an api response passes to fill_ext_info() for the scalars, without the comment or lyrics
    public const string PARTIAL_FILTER  = 'partial';
    protected const string DB_TABLENAME = 'song';
    // the value Waveform passes to fill_ext_info() to reach the blob the other reads leave behind
    private const string WAVEFORM_FILTER = 'waveform';

    /**
     * Uploader per song id, so a multi-column save resolves ownership once instead of per column
     *
     * @var array<int, int|false|null>
     */
    private static array $_owner_cache = [];

    public ?int $addition_time       = null;
    public int $album                = 0;
    public int $album_disk           = 0;
    public ?string $album_mbid       = null;
    public ?int $albumartist         = null;
    public ?string $albumartist_mbid = null;

    /** @var int[] $albumartists */
    public ?array $albumartists = null;

    public ?int $artist         = null;
    public ?string $artist_mbid = null;
    public int $bitrate;
    public ?float $bpm           = null;
    public int $catalog          = 0;
    public ?int $channels        = null;

    /**
     * song_data table
     */
    public ?string $comment = null;

    public ?string $composer     = null;
    public ?int $disk            = null;
    public ?string $disksubtitle = null;
    public bool $enabled         = true;
    public ?string $file         = null;
    public int $id               = 0;

    /**
     * Generated data from other areas
     */

    /** @var null|string[] $isrc */
    public ?array $isrc = null;

    public ?string $label    = null;
    public ?string $language = null;
    public ?int $last_played = null; // When this song was last streamed, as a unix timestamp; null until it has been played.
    public ?int $license     = null;
    public ?string $link     = null;
    public ?string $lyrics   = null;
    public ?string $mbid     = null;
    public ?string $mime     = null;
    public ?string $mode     = null;

    /** @var null|list<array{id: int, name: string, user: int, count: int}> $moods */
    public ?array $moods = null;

    public bool $played;
    public ?int $r128_album_gain = null;
    public ?int $r128_track_gain = null;
    public int $rate;
    public ?float $replaygain_album_gain = null;
    public ?float $replaygain_album_peak = null;
    public ?float $replaygain_track_gain = null;
    public ?float $replaygain_track_peak = null;
    public int $size;

    /** @var null|array<int, array{id: int, name: string, is_hidden: int, count: int}> $tags */
    public ?array $tags = null;

    public int $time;
    public ?string $title   = null;
    public int $total_count = 0;
    public int $total_skip  = 0;
    public ?int $track      = null;
    public string $type;
    public ?int $update_time = null;
    public ?int $user_upload = null;
    public ?string $waveform = null;
    public int $year;
    private ?string $artist_full_name = null;

    /** @var int[] $artists */
    private ?array $artists = null;

    private ?string $f_album_disk_link  = null;
    private ?string $f_album_full       = null;
    private ?string $f_album_link       = null;
    private ?string $f_albumartist_link = null;
    private ?string $f_artist_link      = null;
    private ?string $f_link             = null;
    private ?bool $has_art              = null;
    private ?License $licenseObj        = null;
    private bool $partial_data_loaded   = false;
    private bool $song_data_loaded      = false;

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

        if (!$this->has_info($song_id)) {
            return;
        }

        $this->type = strtolower(pathinfo((string) $this->file, PATHINFO_EXTENSION));
        $this->mime = self::type_to_mime($this->type);
    }

    /**
     * build_cache
     *
     * This attempts to reduce queries by asking for everything in the
     * browse all at once and storing it in the cache, this can help if the
     * db connection is the slow point.
     * @param array<int|string> $song_ids
     */
    public static function build_cache(array $song_ids, string $limit_threshold = ''): bool
    {
        if (empty($song_ids)) {
            return false;
        }

        // with the cache off these rows are discarded and the per-object queries still run, so this is a net loss
        if (!database_object::isCacheEnabled()) {
            return false;
        }

        $artists    = [];
        $albums     = [];
        $repository = self::getSongRepository();

        $played_counts = (!empty($limit_threshold) && AmpConfig::get('show_played_times'))
            ? Stats::get_object_counts('song', $song_ids, $limit_threshold)
            : [];
        $skipped_counts = (!empty($limit_threshold) && AmpConfig::get('show_skipped_times'))
            ? Stats::get_object_counts('song', $song_ids, $limit_threshold, 'skip')
            : [];

        // Song data cache
        foreach ($repository->getRowsByIds(array_values($song_ids), (bool) AmpConfig::get('catalog_disable')) as $row) {
            if (AmpConfig::get('show_played_times')) {
                $row['total_count'] = (empty($limit_threshold))
                    ? $row['total_count']
                    : ($played_counts[(int) $row['id']] ?? 0);
            }

            if (AmpConfig::get('show_skipped_times')) {
                $row['total_skip'] = (empty($limit_threshold))
                    ? $row['total_skip']
                    : ($skipped_counts[(int) $row['id']] ?? 0);
            }

            $artists[] = (int) $row['artist'];

            $albums[] = (int) $row['album'];

            parent::add_to_cache('song', $row['id'], $row);
        }

        Artist::build_cache($artists);
        Album::build_cache($albums);
        Art::build_cache($albums);

        // one artist_map read for the page instead of one per song, and the same for the album artists
        foreach ($repository->getParentIdsBulk(array_map(intval(...), array_values($song_ids)), false) as $songId => $parentIds) {
            parent::add_to_cache('song_artists', $songId, $parentIds);
        }

        foreach ($repository->getParentIdsBulk(array_values(array_unique($albums)), true) as $albumId => $parentIds) {
            parent::add_to_cache('album_artists', $albumId, $parentIds);
        }

        // If we're rating this then cache them as well
        if (AmpConfig::get('ratings')) {
            Rating::build_cache('song', $song_ids);
            Userflag::build_cache('song', $song_ids);
        }

        // Build a cache for the song's extended table
        foreach ($repository->getDataRowsByIds(array_values($song_ids)) as $row) {
            parent::add_to_cache('song_data', $row['song_id'], $row);
        }

        return true;
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
        string $album_mbid = '',
    ): string {
        $songId = self::getSongRepository()->findIdForScrobble(
            $song_name,
            $artist_name,
            $album_name,
            $song_mbid,
            $artist_mbid,
            $album_mbid
        );

        if ($songId === null) {
            debug_event(self::class, 'can_scrobble failed to find: ' . $song_name, 5);

            return '';
        }

        return (string) $songId;
    }

    /**
     * compare_media_information
     * @param string[] $string_array
     * @param string[] $skip_array
     * @return array{
     *     change: bool,
     *     element: array<string, string>
     * }
     */
    public static function compare_media_information(Video|Song $media, Video|Song $new_media, array $string_array, array $skip_array): array
    {
        $array            = [];
        $array['change']  = false;
        $array['element'] = [];

        // Pull out all the currently set vars
        $fields = get_object_vars($media);

        // Foreach them
        foreach (array_keys($fields) as $key) {
            $key = trim((string) $key);
            if (
                $key === ''
                || $key === '0'
                || in_array($key, $skip_array)
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
                !is_string($mediaData)
                && !is_numeric($mediaData)
                && !is_bool($mediaData)
                && (is_object($mediaData) && !method_exists($mediaData, '__toString'))
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
                $mediaData    = self::_clean_string_field_value($mediaData);
                $newMediaData = self::_clean_string_field_value($newMediaData);

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
     * compare_song_information
     * this compares the new ID3 tags of a file against
     * the ones in the database to see if they have changed
     * it returns false if nothing has changes, or the true
     * if they have. Static because it doesn't need this
     * @return array{
     *     change: bool,
     *     element: array<string, string>
     * }
     */
    public static function compare_song_information(Song $song, Song $new_song): array
    {
        $string_array = [
            'comment',
            'composer',
            'lyrics',
            'publisher',
            'tags',
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
            'file',
            'id',
            'mb_albumid_group',
            'mbid',
            'mime',
            'partial_data_loaded',
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
     * The id of a song already holding these tags, or `false` when there is none
     *
     * @param array<string, mixed> $data
     */
    public static function find(array $data): int|false
    {
        $repository = self::getSongRepository();
        if ($data['mb_trackid']) {
            $songId = $repository->findIdByMbid((string) $data['mb_trackid']);
            if ($songId !== null) {
                return $songId;
            }
        }

        if ($data['file']) {
            $songId = $repository->findIdByFile((string) $data['file']);
            if ($songId !== null) {
                return $songId;
            }
        }

        return $repository->findIdByTags($data) ?? false;
    }

    /**
     * garbage_collection
     *
     * Cleans up the song_data table
     */
    public static function garbage_collection(): void
    {
        debug_event(self::class, 'collectGarbage', 5);

        self::getSongRepository()->collectOrphanedGarbage((string) AmpConfig::get('catalog_ignore_pattern'));
    }

    /**
     * Get custom play actions.
     * @return array<int, array{index: int, title: string, icon: string, run: string}>
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
     * get_deleted
     * get items from the deleted_songs table
     * @return array<int, array{
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
        $deleted = [];
        foreach (self::getSongRepository()->getDeletedRows() as $row) {
            $deleted[] = [
                'id' => (int) $row['id'],
                'addition_time' => (int) $row['addition_time'],
                'delete_time' => (int) $row['delete_time'],
                'title' => $row['title'],
                'file' => $row['file'],
                'catalog' => (int) $row['catalog'],
                'total_count' => (int) $row['total_count'],
                'total_skip' => (int) $row['total_skip'],
                'album' => (int) $row['album'],
                'artist' => (int) $row['artist'],
            ];
        }

        return $deleted;
    }

    /**
     * Get parent song artists.
     * @return int[]
     */
    public static function get_parent_array(int $object_id, ?string $type = 'artist'): array
    {
        if (!$object_id) {
            return [];
        }

        $forAlbum = ($type == 'album');
        $key      = ($forAlbum) ? 'album_artists' : 'song_artists';
        if (parent::is_cached($key, $object_id)) {
            return parent::get_from_cache($key, $object_id);
        }

        return self::getSongRepository()->getParentIds($object_id, $forAlbum);
    }

    /**
     * Get song data from the song_map table (ISRC's only right now).
     * @return string[]
     */
    public static function get_song_map_array(int $song_id, ?string $type = 'isrc'): array
    {
        if (!$song_id) {
            return [];
        }

        return self::getSongRepository()->getSongMapValues($song_id, (string) $type);
    }

    /**
     * Get an ID or unique value from the song_map table.
     */
    public static function get_song_map_object_id(int $song_id, string $type): ?string
    {
        if (!$song_id) {
            return null;
        }

        return self::getSongRepository()->getSongMapValues($song_id, $type)[0] ?? null;
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
        $filtered_results = Catalog::filter_tag_results($results);
        $catalog          = $filtered_results['catalog'];
        $file             = $filtered_results['file'];
        $title            = $filtered_results['title'];
        $artist           = $filtered_results['artist'];
        $album            = $filtered_results['album'];
        $albumartist      = $filtered_results['albumartist'];
        $bitrate          = $filtered_results['bitrate'];
        $rate             = $filtered_results['rate'];
        $mode             = $filtered_results['mode'];
        $size             = $filtered_results['size'];
        $time             = $filtered_results['time'];
        $track            = $filtered_results['track'];
        $track_mbid       = $filtered_results['mbid'];
        $album_mbid       = $filtered_results['mb_albumid'];
        $album_mbid_group = $filtered_results['mb_albumid_group'];
        $artist_mbid      = $filtered_results['mb_artistid'];
        $albumartist_mbid = $filtered_results['mb_albumartistid'];
        $disk             = $filtered_results['disk'];
        $disksubtitle     = $filtered_results['disksubtitle'];
        $isrc             = $filtered_results['isrc'];
        $year             = $filtered_results['year'];
        $comment          = $filtered_results['comment'];
        $tags             = $filtered_results['genre']; // multiple genre support makes this an array
        $moods            = $filtered_results['mood'] ?? []; // a file can carry more than one mood too
        $lyrics           = $filtered_results['lyrics'];
        $user_upload      = $filtered_results['user_upload'];
        $composer         = $filtered_results['composer'];
        $label            = $filtered_results['label'];
        $label_names      = ($label && AmpConfig::get('label'))
            ? self::getLabelNameFilter()->filter(array_filter(array_map('trim', explode(';', $label))))
            : [];
        foreach ($label_names as $label_name) {
            // create the label if missing; the album association is made below, once the album id is known
            Label::helper($label_name);
        }

        // info for the artist_map table.
        $artists_array          = $filtered_results['artists'];
        $artist_mbid_array      = $filtered_results['mb_artistid_array'];
        $albumartist_mbid_array = $filtered_results['mb_albumartistid_array'];
        // if you have an artist array this will be named better than what your tags will give you
        if (!empty($artists_array)) {
            if (
                $artist !== ''
                && $artist !== '0'
                && (
                    $albumartist !== ''
                    && $albumartist !== '0'
                )
                && $artist === $albumartist
            ) {
                $albumartist = (string) $artists_array[0];
            }

            $artist = (string) $artists_array[0];
        }

        $license_id            = $filtered_results['license_id'];
        $language              = $filtered_results['language'];
        $channels              = $filtered_results['channels'];
        $release_type          = $filtered_results['release_type'];
        $release_status        = $filtered_results['release_status'];
        $replaygain_track_gain = $filtered_results['replaygain_track_gain'];
        $replaygain_track_peak = $filtered_results['replaygain_track_peak'];
        $replaygain_album_gain = $filtered_results['replaygain_album_gain'];
        $replaygain_album_peak = $filtered_results['replaygain_album_peak'];
        $r128_track_gain       = $filtered_results['r128_track_gain'];
        $r128_album_gain       = $filtered_results['r128_album_gain'];
        $bpm                   = $filtered_results['bpm'];
        $original_year         = $filtered_results['original_year'];
        $barcode               = $filtered_results['barcode'];
        $catalog_number        = $filtered_results['catalog_number'];
        $version               = $filtered_results['version'];

        $albumartist_id = null;
        if (isset($results['albumartist_id'])) {
            $albumartist_id = (int) ($results['albumartist_id']);
        } elseif (
            $albumartist !== null
            && $albumartist !== ''
            && $albumartist !== '0'
        ) {
            $albumartist_mbid = Catalog::trim_slashed_list($albumartist_mbid);
            $albumartist_id   = Artist::check($albumartist, $albumartist_mbid, $user_upload);
        }

        // song artist text is the same as album artist so don't worry about looking up id's if they match
        $artist_id = null;
        if (
            $albumartist_id
            && $albumartist
            && $albumartist === $artist
        ) {
            $artist_id = $albumartist_id;
        } elseif (isset($results['artist_id'])) {
            $artist_id = (int) ($results['artist_id']);
        } elseif ($artist !== null && $artist !== '' && $artist !== '0') {
            $artist_mbid = Catalog::trim_slashed_list($artist_mbid);
            $artist_id   = (int) Artist::check($artist, $artist_mbid, $user_upload);
        }

        // `song`.`artist` is NOT NULL, so a file with no usable artist tag lands on the same placeholder an album does
        if ($artist_id === null) {
            $artist_id = (int) Artist::check('', null, $user_upload);
        }

        if (isset($results['album_id'])) {
            $album_id = (int) ($results['album_id']);
        } else {
            $album_id = (empty($album))
                ? Album::check($catalog, '', $year, null, null, ($albumartist_id ?? $artist_id))
                : Album::check($catalog, $album, $year, $album_mbid, $album_mbid_group, $albumartist_id, $release_type, $release_status, $original_year, $barcode, $catalog_number, $version);
        }

        // The label tag is read per song but describes the release, so it is recorded against the album
        if ($label_names !== [] && $album_id > 0) {
            $labelRepository = self::getLabelRepository();
            $now             = new DateTime();
            foreach ($label_names as $label_name) {
                $label_id = $labelRepository->lookup($label_name);
                if ($label_id > 0) {
                    $labelRepository->addAlbumAssoc($label_id, $album_id, $now);
                }
            }
        }

        // create the album_disk (if missing)
        $album_disk_id = AlbumDisk::check($album_id, $disk, $catalog, $disksubtitle);

        $insert_time = time();
        $song_id     = self::getSongRepository()->insert([$catalog, $file, $album_id, $album_disk_id, $disk, $artist_id, $title, $bitrate, $rate, $mode, $size, $time, $track, $insert_time, $insert_time, $year, $track_mbid, $user_upload, $license_id, $composer ?: null, $channels]);
        if ($song_id === null) {
            debug_event(self::class, 'Unable to insert ' . $file, 2);

            return null;
        }
        $artists = [$artist_id, (int) $albumartist_id];

        // map the song to catalog album and artist maps
        Catalog::update_map((int) $catalog, 'song', $song_id);
        if ($artist_id > 0) {
            Artist::add_artist_map($artist_id, 'song', $song_id);
            Album::add_album_map($album_id, 'song', $artist_id);
        }

        if ((int) $albumartist_id > 0) {
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
                $song_artist_id = (int) Artist::check($artist_name, '', $user_upload);
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

        // Add you ISRC's to the song_map
        self::update_song_map($isrc, 'isrc', $song_id);

        // update the all the counts for the album right away
        Album::update_album_count($album_id);

        if ($user_upload) {
            // A scan maps artists to their catalog when it finishes; an upload has no such pass
            foreach (array_unique($artists) as $mapped_artist_id) {
                if ($mapped_artist_id > 0) {
                    Catalog::update_map((int) $catalog, 'artist', (int) $mapped_artist_id);
                }
            }

            self::getUserActivityPoster()->post((int) $user_upload, 'upload', 'song', $song_id, time());
        }

        // Allow scripts to populate new tags when injecting user uploads
        if (!defined('NO_SESSION') && ($user_upload && !Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER, $user_upload))) {
            $tags = Tag::clean_to_existing($tags);
        }

        if (is_array($tags)) {
            foreach ($tags as $tag) {
                $tag = trim((string) $tag);
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

        // the moods reach the album and artists the way genres do, with no owner, so a later re-read of the tags can replace them
        if (is_array($moods)) {
            foreach ($moods as $mood) {
                $mood = trim((string) $mood);
                if ($mood !== '') {
                    Mood::add('song', $song_id, $mood);
                    Mood::add('album', $album_id, $mood);
                    foreach (array_unique($artists) as $found_artist_id) {
                        if ($found_artist_id > 0) {
                            Mood::add('artist', $found_artist_id, $mood);
                        }
                    }
                }
            }
        }

        self::getSongRepository()->insertData([$song_id, $disksubtitle ?: null, $comment ?: null, $lyrics ?: null, $label ?: null, $language ?: null, $replaygain_track_gain, $replaygain_track_peak, $replaygain_album_gain, $replaygain_album_peak, $r128_track_gain, $r128_album_gain, $bpm]);

        self::getFolderRepository()->mapObject('song', $song_id, (string) $file, (int) $catalog);

        return $song_id;
    }

    /**
     * Migrate an album data to a new object
     */
    public static function migrate_album(int $new_album, int $song_id, int $old_album): bool
    {
        // migrate stats for the old album
        Stats::migrate('album', $old_album, $new_album, $song_id);
        Useractivity::migrate('album', $old_album, $new_album);
        //Recommendation::migrate('album', $old_album);
        self::getShareRepository()->migrate('album', $old_album, $new_album);
        self::getShoutRepository()->migrate('album', $old_album, $new_album);
        Tag::migrate('album', $old_album, $new_album);
        Mood::migrate('album', $old_album, $new_album);
        Userflag::migrate('album', $old_album, $new_album);
        Rating::migrate('album', $old_album, $new_album);
        Art::duplicate('album', $old_album, $new_album);
        Catalog::migrate_map('album', $old_album, $new_album);

        // update mapping tables
        return self::getSongRepository()->migrateAlbum($new_album, $old_album, $song_id);
    }

    /**
     * Migrate an artist data to a new object
     */
    public static function migrate_artist(int $new_artist, int $old_artist): bool
    {
        if ($old_artist != $new_artist) {
            // migrate stats for the old artist
            Useractivity::migrate('artist', $old_artist, $new_artist);
            Recommendation::migrate('artist', $old_artist);
            self::getShareRepository()->migrate('artist', $old_artist, $new_artist);
            self::getShoutRepository()->migrate('artist', $old_artist, $new_artist);
            Tag::migrate('artist', $old_artist, $new_artist);
            Mood::migrate('artist', $old_artist, $new_artist);
            Userflag::migrate('artist', $old_artist, $new_artist);
            Rating::migrate('artist', $old_artist, $new_artist);
            Art::duplicate('artist', $old_artist, $new_artist);
            self::getWantedRepository()->migrateArtist($old_artist, $new_artist);
            Catalog::migrate_map('artist', $old_artist, $new_artist);

            // update mapping tables
            return self::getSongRepository()->migrateArtist($new_artist, $old_artist);
        }

        return true;
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
            'opus', 'opus_rg', 'opus_car' => 'audio/ogg; codecs=opus',
            'mp3_rg', 'mp3_car' => 'audio/mpeg',
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
     * update_album
     * updates the album field
     */
    public static function update_album(int $new_album, int $song_id, int $old_album, bool $update_counts = true): bool
    {
        if ($old_album != $new_album && self::_update_item('album', $new_album, $song_id, AccessLevelEnum::CONTENT_MANAGER, true) !== false) {
            self::migrate_album($new_album, $song_id, $old_album);
            if ($update_counts) {
                Album::update_album_count($new_album);
                Album::update_album_count($old_album);
            }

            return true;
        }

        return false;
    }

    /**
     * update_artist
     * updates the artist field
     */
    public static function update_artist(int $new_artist, int $song_id, ?int $old_artist = null, bool $update_counts = true): bool
    {
        if ($old_artist != $new_artist && self::_update_item('artist', $new_artist, $song_id, AccessLevelEnum::CONTENT_MANAGER) !== false) {
            if ($update_counts && $old_artist) {
                self::migrate_artist($new_artist, $old_artist);
                Artist::update_artist_count($new_artist);
                Artist::update_artist_count($old_artist);
            }

            return true;
        }

        return false;
    }

    /**
     * update_bitrate
     * updates the bitrate field
     */
    public static function update_bitrate(int $new_bitrate, int $song_id): void
    {
        self::_update_item('bitrate', $new_bitrate, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_comment
     * updates the comment field
     */
    public static function update_comment(string $new_comment, int $song_id): void
    {
        self::_update_ext_item('comment', $new_comment, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_composer
     * updates the composer field
     */
    public static function update_composer(string $new_composer, int $song_id): void
    {
        self::_update_item('composer', $new_composer, $song_id, AccessLevelEnum::CONTENT_MANAGER, true, true);
    }

    /**
     * update_disk
     * update the disk tag
     */
    public static function update_disk(int $new_disk, int $song_id): void
    {
        self::_update_item('disk', $new_disk, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_enabled
     * sets the enabled flag
     */
    public static function update_enabled(bool $new_enabled, int $song_id): void
    {
        self::_update_item('enabled', (($new_enabled) ? 1 : 0), $song_id, AccessLevelEnum::MANAGER, true);
    }

    /**
     * update_label
     * This updates the label tag of the song
     */
    public static function update_label(string $new_value, int $song_id): void
    {
        self::_update_ext_item('label', $new_value, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_language
     * This updates the language tag of the song
     */
    public static function update_language(string $new_lang, int $song_id): void
    {
        self::_update_ext_item('language', $new_lang, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_license
     * updates license field
     */
    public static function update_license(?int $new_license, int $song_id): void
    {
        self::_update_item('license', $new_license, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_lyrics
     * updates the lyrics field
     */
    public static function update_lyrics(string $new_lyrics, int $song_id): void
    {
        self::_update_ext_item('lyrics', $new_lyrics, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_mbid
     * updates mbid field
     */
    public static function update_mbid(string $new_mbid, int $song_id): void
    {
        self::_update_item('mbid', $new_mbid, $song_id, AccessLevelEnum::CONTENT_MANAGER);
    }

    /**
     * update_mode
     * updates the mode field
     */
    public static function update_mode(string $new_mode, int $song_id): void
    {
        self::_update_item('mode', $new_mode, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_played
     * sets the played flag
     */
    public static function update_played(bool $new_played, int $song_id): void
    {
        self::_update_item('played', (($new_played) ? 1 : 0), $song_id, AccessLevelEnum::USER);
    }

    /**
     * update_rate
     * updates the rate field
     */
    public static function update_rate(int $new_rate, int $song_id): void
    {
        self::_update_item('rate', $new_rate, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_size
     * updates the size field
     */
    public static function update_size(int $new_size, int $song_id): void
    {
        self::_update_item('size', $new_size, $song_id, AccessLevelEnum::CONTENT_MANAGER);
    }

    /**
     * update_song
     * this is the main updater for a song and updates
     * the "update_time" of the song
     */
    public static function update_song(int $song_id, Song $new_song): void
    {
        $update_time = time();

        self::getSongRepository()->updateSong(
            $song_id,
            [$new_song->album, $new_song->album_disk, $new_song->disk, $new_song->year, $new_song->artist, $new_song->title, $new_song->composer ?: null, $new_song->bitrate, $new_song->rate, $new_song->mode, $new_song->channels, $new_song->size, $new_song->time, $new_song->track, $new_song->mbid, $update_time, $song_id],
            [$new_song->label ?: null, $new_song->lyrics ?: null, $new_song->language ?: null, $new_song->disksubtitle ?: null, $new_song->comment ?: null, $new_song->replaygain_track_gain, $new_song->replaygain_track_peak, $new_song->replaygain_album_gain, $new_song->replaygain_album_peak, $new_song->r128_track_gain, $new_song->r128_album_gain, $new_song->bpm, $song_id]
        );
    }

    /**
     * update_song_map
     * update and remove mapping data for a song
     * @param string[] $new_data
     */
    public static function update_song_map(array $new_data, string $type, int $song_id): void
    {
        self::getSongRepository()->updateSongMap(array_values($new_data), $type, $song_id);
    }

    /**
     * update_title
     * updates the title field
     */
    public static function update_title(string $new_title, int $song_id): void
    {
        self::_update_item('title', $new_title, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_track
     * this updates the track field
     */
    public static function update_track(int $new_track, int $song_id): void
    {
        self::_update_item('track', $new_track, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_user_upload
     * this updates the user_upload field
     */
    public static function update_user_upload(?int $new_user_upload, int $song_id): void
    {
        $value = ((int) $new_user_upload === 0) ? null : (int) $new_user_upload;
        self::_update_item('user_upload', $value, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * update_utime
     * sets a new update time
     */
    public static function update_utime(int $song_id, int $time = 0): void
    {
        if (!$time) {
            $time = time();
        }

        self::getSongRepository()->updateUpdateTime($song_id, $time);
    }

    /**
     * update_year
     * update the year tag
     */
    public static function update_year(int $new_year, int $song_id): void
    {
        self::_update_item('year', $new_year, $song_id, AccessLevelEnum::CONTENT_MANAGER, true);
    }

    /**
     * clean_string_field_value
     * Accepts anything the compare loop lets through (string, numeric or bool) so let it through too if it gets here
     */
    private static function _clean_string_field_value(string|int|float|bool|null $value = null): string
    {
        if (!$value) {
            return '';
        }

        $value = trim(stripslashes((string) preg_replace('/\s+/', ' ', (string) $value)));

        // Strings containing only UTF-8 BOM = empty string
        if (strlen($value) == 2 && (ord($value[0]) == 0xFF || ord($value[0]) == 0xFE)) {
            $value = "";
        }

        return $value;
    }

    private static function _is_codec_name(string $codec): bool
    {
        return preg_match('/^[A-Za-z0-9_+-]+$/', $codec) === 1;
    }

    /**
     * Downgrades the required access level to USER when the current user uploaded the song
     *
     * The owner is resolved once per song per request: this used to build a whole `Song`, whose
     * `get_info()` is a three-way join, for every single column a save touched.
     */
    private static function _ownerLevel(int $song_id, AccessLevelEnum $level): AccessLevelEnum
    {
        if (!array_key_exists($song_id, self::$_owner_cache)) {
            self::$_owner_cache[$song_id] = self::getSongRepository()->findOwnerId($song_id);
        }

        $ownerId = self::$_owner_cache[$song_id];

        // false is "no such song", which is what the old `$item->id` guard tested before comparing
        return ($ownerId !== false && $ownerId == Core::get_global('user')?->id)
            ? AccessLevelEnum::USER
            : $level;
    }

    private static function _scrub_custom_play_arg(string $value): string
    {
        return (string) preg_replace('/[;|&$`\\\\"\'<>(){}*?!#~\x00-\x1F]/u', '', $value);
    }

    /**
     * _update_ext_item
     * This updates a song record that is housed in the song_ext_info table
     * These are items that aren't used normally, and often large/informational only
     */
    private static function _update_ext_item(string $field, string $value, int $song_id, AccessLevelEnum $level, bool $check_owner = false): void
    {
        if ($check_owner && Core::get_global('user') instanceof User) {
            $level = self::_ownerLevel($song_id, $level);
        }

        if (!Access::check(AccessTypeEnum::INTERFACE, $level)) {
            return;
        }

        $column = SongDataFieldEnum::tryFrom($field);
        if ($column !== null) {
            self::getSongRepository()->setDataField($song_id, $column, $value);
        }
    }

    /**
     * _update_item
     * This is a private function that should only be called from within the song class.
     * It takes a field, value song id and level. first and foremost it checks the level
     * against Core::get_global('user') to make sure they are allowed to update this record
     * it then updates it and sets $this->{$field} to the new value
     */
    private static function _update_item(string $field, int|string|null $value, int $song_id, AccessLevelEnum $level, bool $check_owner = false, bool $allow_null = false): bool
    {
        if ($check_owner && Core::get_global('user') instanceof User) {
            $level = self::_ownerLevel($song_id, $level);
        }

        /* Check them Rights! */
        if (!Access::check(AccessTypeEnum::INTERFACE, $level)) {
            return false;
        }

        /* Can't update to blank */
        if (!$allow_null && !strlen(trim((string) $value))) {
            return false;
        }

        $column = SongFieldEnum::tryFrom($field);
        if ($column === null) {
            return false;
        }

        if ($column === SongFieldEnum::USER_UPLOAD) {
            unset(self::$_owner_cache[$song_id]);
        }

        return self::getSongRepository()->setField($song_id, $column, $value);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getFolderRepository(): FolderRepositoryInterface
    {
        global $dic;

        return $dic->get(FolderRepositoryInterface::class);
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
    private static function getShoutRepository(): ShoutRepositoryInterface
    {
        global $dic;

        return $dic->get(ShoutRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getStats(): Stats
    {
        global $dic;

        return $dic->get(Stats::class);
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
    private static function getWantedRepository(): WantedRepositoryInterface
    {
        global $dic;

        return $dic->get(WantedRepositoryInterface::class);
    }

    /**
     * check_play_history
     * this checks to see if the current object has been played
     * if not then it sets it to played. In any case it updates stats.
     */
    public function check_play_history(int $user, string $agent, int $date): bool
    {
        return self::getStats()->has_played_history('song', $this, $user, $agent, $date);
    }

    /**
     * display_art
     * @param array{width: int, height: int} $size
     */
    public function display_art(array $size, bool $force = false): void
    {
        if (Art::has_db($this->id, 'song')) {
            Art::display('song', $this->id, (string) $this->get_fullname(), $size, $this->get_link());
        } elseif (Art::has_db($this->album, 'album')) {
            Art::display('album', $this->album, (string) $this->get_fullname(), $size, $this->get_link());
        } elseif ($this->artist && (Art::has_db($this->artist, 'artist') || $force)) {
            Art::display('artist', $this->artist, (string) $this->get_fullname(), $size, $this->get_link());
        }
    }

    /**
     * fill_ext_info
     * This calls the _get_ext_info and then sets the correct vars
     */
    public function fill_ext_info(string $data_filter = ''): void
    {
        if ($this->isNew()) {
            return;
        }

        // the waveform is a blob no other read carries, so it loads on its own and guards on its own value
        if ($data_filter === self::WAVEFORM_FILTER) {
            if ($this->waveform !== null) {
                return;
            }
        } elseif ($this->song_data_loaded || ($data_filter !== '' && $this->partial_data_loaded)) {
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
            $this->setSongDataProperty($key, $value);
        }

        // don't repeat this process if you've got it all
        if ($data_filter === '') {
            $this->song_data_loaded = true;
        } elseif ($data_filter !== self::WAVEFORM_FILTER) {
            $this->partial_data_loaded = true;
        }
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
     * get_album_artist_fullname
     * gets the name of $this->albumartist, allows passing of id
     */
    public function get_album_artist_fullname(int $album_artist_id = 0): ?string
    {
        if ($album_artist_id) {
            return Artist::get_fullname_by_id($album_artist_id);
        }

        return Artist::get_fullname_by_id($this->get_album_artist());
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
     * get_album_barcode
     * gets the barcode of $this->album, allows passing of id
     */
    public function get_album_barcode(?int $album_id = null): ?string
    {
        if (!$album_id) {
            $album_id = $this->album;
        }

        $album = new Album($album_id);

        return $album->barcode;
    }

    /**
     * get_album_catalog_number
     * gets the catalog_number of $this->album, allows passing of id
     */
    public function get_album_catalog_number(?int $album_id = null): ?string
    {
        if ($album_id === null) {
            $album_id = $this->album;
        }

        $album = new Album($album_id);

        return $album->catalog_number;
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
     * get_album_fullname
     * gets the name of $this->album, allows passing of id
     */
    public function get_album_fullname(int $album_id = 0, bool $simple = false): string
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
     * get_album_mbid
     * gets the albumartist id for the song's album
     */
    public function get_album_mbid(): ?string
    {
        if ($this->album_mbid === null) {
            $this->album_mbid = self::getSongRepository()->findRelatedMbid(SongMbidSourceEnum::ALBUM, (int) $this->album);
        }

        return $this->album_mbid;
    }

    /**
     * get_album_original_year
     * gets the original_year of $this->album, allows passing of id
     */
    public function get_album_original_year(?int $album_id = null): ?int
    {
        if ($album_id === null) {
            $album_id = $this->album;
        }

        $album = new Album($album_id);

        return $album->original_year;
    }

    /**
     * get_albumartist_mbid
     * gets the albumartist id for the song's album
     */
    public function get_albumartist_mbid(): ?string
    {
        if ($this->albumartist_mbid === null) {
            $this->albumartist_mbid = self::getSongRepository()->findRelatedMbid(SongMbidSourceEnum::ARTIST, (int) $this->get_album_artist());
        }

        return $this->albumartist_mbid;
    }

    /**
     * get_artist_mbid
     * gets the albumartist id for the song's album
     */
    public function get_artist_mbid(): ?string
    {
        if ($this->artist_mbid === null) {
            $this->artist_mbid = self::getSongRepository()->findRelatedMbid(SongMbidSourceEnum::ARTIST, (int) $this->artist);
        }

        return $this->artist_mbid;
    }

    /**
     * Get item album_artists array
     * @return int[]
     */
    public function get_artists(): array
    {
        if ($this->artists === null) {
            $this->artists = self::get_parent_array($this->id);
        }

        return $this->artists ?? [];
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

        return $this->f_album_disk_link ?? '';
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

        return $this->f_album_link ?? '';
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

        return $this->f_albumartist_link ?? '';
    }

    /**
     * Get item f_link.
     */
    public function get_f_link(?string $title = null): string
    {
        // don't do anything if it's formatted
        if ($this->f_link === null) {
            $this->f_link = "<a href=\"" . scrub_out($this->get_link()) . "\" title=\"" . scrub_out($this->get_parent_fullname()) . " - " . scrub_out($this->get_fullname()) . "\"> " . scrub_out($title ?? $this->get_fullname()) . "</a>";
        }

        return $this->f_link;
    }

    /**
     * The moods of this song as links, with the ones a user set marked
     */
    public function get_f_moods(): string
    {
        return Mood::get_display($this->get_moods(), true, 'song');
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
     * Get item f_tags.
     */
    public function get_f_tags(): string
    {
        return Tag::get_display($this->get_tags(), true, 'song');
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
     * Get item fullname.
     */
    public function get_fullname(): ?string
    {
        return $this->title;
    }

    /**
     * Get item album_artists array
     * @return string[]
     */
    public function get_isrcs(): array
    {
        if ($this->isrc === null) {
            $this->isrc = self::get_song_map_array($this->id);
        }

        return $this->isrc ?? [];
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
                'value' => (string) $this->mbid,
            ],
            'artist' => [
                'important' => true,
                'label' => T_('Artist'),
                'value' => $this->get_parent_fullname(),
            ],
            'title' => [
                'important' => true,
                'label' => T_('Title'),
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
            $web_path = AmpConfig::get_web_path();

            $this->link = $web_path . "/song.php?action=show_song&song_id=" . $this->id;
        }

        return $this->link ?? '';
    }

    /**
     * Get lyrics.
     * @return array{'text'?: string}
     */
    public function get_lyrics(bool $db_only = false): array
    {
        if ($this->lyrics === null) {
            $this->fill_ext_info('lyrics');
        }

        if ($this->lyrics) {
            return ['text' => $this->lyrics];
        }

        if ($db_only) {
            return [];
        }

        $user = Core::get_global('user');
        if ($user instanceof User) {
            foreach (Plugin::get_plugins(PluginTypeEnum::LYRIC_RETRIEVER) as $plugin_name) {
                $plugin = new Plugin($plugin_name);
                if (
                    $plugin->_plugin instanceof PluginGetLyricsInterface
                    && $plugin->load($user)
                ) {
                    // a plugin talking to an unreachable service must not take the request down with it; skip to the next
                    try {
                        $lyrics = $plugin->_plugin->get_lyrics($this);
                    } catch (\Throwable $error) {
                        debug_event(self::class, 'get_lyrics error in ' . $plugin_name . ': ' . $error->getMessage(), 1);

                        continue;
                    }

                    if (!empty($lyrics)) {
                        // save the lyrics if not set before
                        if (!empty($lyrics['text'])) {
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
     * Get all childrens and sub-childrens medias.
     *
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
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
     * The moods mapped onto this song, whoever set them
     *
     * @return list<array{id: int, name: string, user: int, count: int}>
     */
    public function get_moods(): array
    {
        if ($this->moods === null) {
            $this->moods = Mood::get_top_moods('song', $this->id, 0);
        }

        return $this->moods;
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
     * get_parent_fullname
     * gets the name of $this->artist, allows passing of id
     */
    public function get_parent_fullname(): string
    {
        if ($this->artist_full_name === null) {
            $this->artist_full_name = Artist::get_fullname_by_id($this->artist);
        }

        return $this->artist_full_name ?? '';
    }

    /**
     * Get stream name.
     */
    public function get_stream_name(): string
    {
        return $this->get_parent_fullname() . " - " . $this->title;
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
     * Get item tags.
     * @return array<int, array{id: int, name: string, is_hidden: int, count: int}>
     */
    public function get_tags(): array
    {
        if ($this->tags === null) {
            $this->tags = Tag::get_top_tags('song', $this->id);
        }

        return $this->tags ?? [];
    }

    /**
     * Get total count
     */
    public function get_totalcount(): int
    {
        return $this->total_count;
    }

    /**
     * Get transcode settings.
     * @param array{bitrate?: float|int, maxbitrate?: int, subtitle?: string, resolution?: string, quality?: int, frame?: float, duration?: float} $options
     * @return array{format?: string, command?: string}
     */
    public function get_transcode_settings(?string $target = null, ?string $player = null, array $options = []): array
    {
        return Stream::get_transcode_settings_for_media($this->type, $target, $player, 'song', $options);
    }

    /**
     * Get item's owner.
     */
    public function get_user_owner(): ?int
    {
        return $this->user_upload;
    }

    /**
     * Returns the date at which the song was first added
     */
    public function getAdditionTime(): DateTimeInterface
    {
        return new DateTime('@' . $this->addition_time);
    }

    /**
     * Returns the id of the catalog the item is associated to
     */
    public function getCatalogId(): int
    {
        return $this->catalog;
    }

    /**
     * Returns the path of the song
     */
    public function getFile(): string
    {
        return (string) $this->file;
    }

    /**
     * Returns the filename of the media-item
     */
    public function getFileName(): string
    {
        $value = $this->get_parent_fullname() . ' - ';
        if ($this->track) {
            $value .= $this->track . ' - ';
        }

        return $value . ($this->get_fullname() . '.' . $this->type);
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getLicense(): ?License
    {
        if (
            AmpConfig::get('licensing')
            && $this->licenseObj === null
            && $this->license !== null
        ) {
            $this->licenseObj = $this->getLicenseRepository()->findById($this->license);
        }

        return $this->licenseObj;
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::SONG;
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
     * Returns the metadata object-type
     */
    public function getMetadataItemType(): string
    {
        return 'song';
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
            $this->has_art = (AmpConfig::get('show_song_art', false) && Art::has_db($this->id, 'song') || Art::has_db($this->album, 'album'));
        }

        return $this->has_art ?? false;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * play_url
     * This function takes all the song information and correctly formats a
     * stream URL taking into account the downsampling mojo and everything
     * else, this is the true function
     */
    public function play_url(string $additional_params = '', string $player = '', bool $local = false, int|string|null $uid = null, ?string $streamToken = null): string
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
        $lan_user          = $this->getNetworkChecker()->check(AccessTypeEnum::NETWORK, (int) $uid, AccessLevelEnum::DEFAULT);
        $transcode         = AmpConfig::get('transcode', 'default');

        // enforce or disable transcoding depending on local network ACL. Transcoding must also not be disabled with 'never'
        if (
            $downsample_remote
            && $transcode !== 'never'
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
            $transcode != 'never'
            && (
                empty($additional_params)
                || (
                    !str_contains($additional_params, '&bitrate=')
                    && !str_contains($additional_params, '&format=')
                )
            )
        ) {
            $cache_path     = (string) AmpConfig::get('cache_path', '');
            $cache_target   = (string) AmpConfig::get('cache_target', '');
            $file_target    = Catalog::get_cache_path($this->id, $this->catalog, $cache_path, $cache_target);
            $transcode_type = ($file_target !== null && is_file($file_target))
                ? $cache_target
                : Stream::get_transcode_format($this->type, null, $player);
            // the rate advertised here follows the player, which may carry an override of the user's default rate
            $bitrate = Stream::get_player_bitrate($player);

            // No cap configured means the file's own rate is the target. Comparing a raw zero against the source
            // instead reads as a rate below every file, which forces a transcode of everything it is asked for.
            $target_rate = ($bitrate > 0)
                ? $bitrate
                : (int) $this->bitrate;
            if (
                $transcode_type !== null
                && $transcode_type !== ''
                && $transcode_type !== '0'
                && ($this->type !== $transcode_type || $target_rate < $this->bitrate)
            ) {
                $this->type    = $transcode_type;
                $this->mime    = self::type_to_mime($transcode_type);
                $this->bitrate = $target_rate;

                // replace duplicate/incorrect parameters on the additional params
                $patterns = [
                    '/&format=[a-z]+/',
                    '/&transcode_to=[a-z|0-9]+/',
                    '/&bitrate=[0-9]+/',
                ];
                $additional_params = preg_replace($patterns, '', $additional_params);
                $additional_params .= '&transcode_to=' . $transcode_type;

                // Only a real cap belongs in the url. Pinning the source rate here would hand the stream side a
                // number it resolves better itself, and a zero is just noise the play action has to ignore.
                if ($bitrate > 0) {
                    $additional_params .= '&bitrate=' . $bitrate;
                }
            }
        }

        $media_name = $this->get_stream_name() . "." . Stream::get_base_format($this->type);
        $media_name = (string) preg_replace("/[^a-zA-Z0-9\. ]+/", "-", $media_name);
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
     * remove
     * Delete the object from disk and/or database where applicable.
     */
    public function remove(): bool
    {
        return $this->getSongDeleter()->delete($this);
    }

    /**
     * Run custom play action.
     */
    public function run_custom_play_action(int $action_index, string $codec = ''): array
    {
        $transcoder = [];
        $actions    = self::get_custom_play_actions();
        if ($action_index >= 1 && $action_index <= count($actions)) {
            $action = $actions[$action_index - 1];
            if (!$codec) {
                $codec = $this->type;
            }

            if (!self::_is_codec_name($codec)) {
                $codec = $this->type;
                if (!self::_is_codec_name($codec)) {
                    debug_event(self::class, 'Custom play action skipped: {' . $this->id . '} has no usable format', 2);

                    return $transcoder;
                }
            }

            $run = str_replace("%f", self::_scrub_custom_play_arg($this->file ?? '%f'), (string) $action['run']);
            $run = str_replace("%c", $codec, $run);
            $run = str_replace("%a", (empty($this->get_parent_fullname())) ? '%a' : self::_scrub_custom_play_arg($this->get_parent_fullname()), $run);
            $run = str_replace("%A", (empty($this->get_album_fullname())) ? '%A' : self::_scrub_custom_play_arg($this->get_album_fullname()), $run);
            $run = str_replace("%t", self::_scrub_custom_play_arg($this->get_fullname() ?? '%t'), $run);

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
        if (self::getStats()->insert('song', $this->id, $user_id, $agent, $location, 'stream', $date)) {
            // followup on some stats too
            self::getStats()->insert('album', $this->album, $user_id, $agent, $location, 'stream', $date);
            if ($this->album_disk) {
                Stats::count('album_disk', $this->album_disk, 'up', $date);
            }
            // insert plays for song and album artists
            $artists = array_unique(array_merge(self::get_parent_array($this->id), self::get_parent_array($this->album, 'album')));
            foreach ($artists as $artist_id) {
                self::getStats()->insert('artist', $artist_id, $user_id, $agent, $location, 'stream', $date);
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
     * update
     * This takes a key'd array of data does any cleaning it needs to
     * do and then calls the helper functions as needed.
     *
     * Values arriving from a request are all strings, so each one is cast to the type its own setter declares.
     */
    public function update(array $data): int
    {
        foreach ($data as $key => $value) {
            //debug_event(self::class, $key . '=' . $value, 5);
            switch ($key) {
                case 'artist_name':
                    // Create new artist name and id
                    $old_artist_id = $this->artist;
                    $new_artist_id = (int) Artist::check((string) $value);
                    if ($new_artist_id > 0) {
                        $this->artist = $new_artist_id;
                        self::update_artist($new_artist_id, $this->id, $old_artist_id);
                    }
                    break;
                case 'album_name':
                    // Create new album name and id
                    $old_album_id = $this->album;
                    $new_album_id = Album::check($this->catalog, (string) $value);
                    $this->album  = $new_album_id;
                    self::update_album($new_album_id, $this->id, $old_album_id);
                    break;
                case 'artist':
                    // Change artist the song is assigned to
                    if ($value != $this->$key) {
                        $old_artist_id = $this->artist;
                        $new_artist_id = (int) $value;
                        self::update_artist($new_artist_id, $this->id, $old_artist_id);
                    }
                    break;
                case 'album':
                    // Change album the song is assigned to
                    if ($value != $this->$key) {
                        $old_album_id = $this->$key;
                        $new_album_id = (int) $value;
                        self::update_album($new_album_id, $this->id, $old_album_id);
                    }
                    break;
                case 'disk':
                    // Check to see if it needs to be updated
                    if ($value != $this->disk) {
                        // create the album_disk (if missing)
                        $new_disk = (int) $value;
                        AlbumDisk::check($this->album, $new_disk, $this->catalog, $this->get_album_disk_subtitle());

                        self::update_disk($new_disk, $this->id);
                        $this->disk = $new_disk;
                    }
                    break;
                case 'bitrate':
                    if ($value != $this->bitrate) {
                        self::update_bitrate((int) $value, $this->id);
                        $this->setUpdatedFieldValue($key, $value);
                    }
                    break;
                case 'comment':
                    if ($value != $this->comment) {
                        self::update_comment((string) $value, $this->id);
                        $this->setUpdatedFieldValue($key, $value);
                    }
                    break;
                case 'composer':
                    if ($value != $this->composer) {
                        self::update_composer((string) $value, $this->id);
                        $this->setUpdatedFieldValue($key, $value);
                    }
                    break;
                case 'label':
                    if ($value != $this->label) {
                        self::update_label((string) $value, $this->id);
                        $this->setUpdatedFieldValue($key, $value);
                    }
                    break;
                case 'language':
                    if ($value != $this->language) {
                        self::update_language((string) $value, $this->id);
                        $this->setUpdatedFieldValue($key, $value);
                    }
                    break;
                case 'license':
                    if ($value != $this->license) {
                        self::update_license((int) $value, $this->id);
                        $this->setUpdatedFieldValue($key, $value);
                    }
                    break;
                case 'mbid':
                    if ($value != $this->mbid) {
                        self::update_mbid((string) $value, $this->id);
                        $this->setUpdatedFieldValue($key, $value);
                    }
                    break;
                case 'mode':
                    if ($value != $this->mode) {
                        self::update_mode((string) $value, $this->id);
                        $this->setUpdatedFieldValue($key, $value);
                    }
                    break;
                case 'rate':
                    if ($value != $this->rate) {
                        self::update_rate((int) $value, $this->id);
                        $this->setUpdatedFieldValue($key, $value);
                    }
                    break;
                case 'size':
                    if ($value != $this->size) {
                        self::update_size((int) $value, $this->id);
                        $this->setUpdatedFieldValue($key, $value);
                    }
                    break;
                case 'title':
                    if ($value != $this->title) {
                        self::update_title((string) $value, $this->id);
                        $this->setUpdatedFieldValue($key, $value);
                    }
                    break;
                case 'track':
                    if ($value != $this->track) {
                        self::update_track((int) $value, $this->id);
                        $this->setUpdatedFieldValue($key, $value);
                    }
                    break;
                case 'user_upload':
                    if ($value != $this->user_upload) {
                        self::update_user_upload((int) $value, $this->id);
                        $this->setUpdatedFieldValue($key, $value);
                    }
                    break;
                case 'year':
                    if ($value != $this->year) {
                        self::update_year((int) $value, $this->id);
                        $this->setUpdatedFieldValue($key, $value);
                    }
                    break;
                case 'edit_tags':
                    Tag::update_tag_list((string) $value, 'song', $this->id, true);
                    $this->tags = Tag::get_top_tags('song', $this->id);
                    break;
                case 'edit_moods':
                    // no from_file_tags, so these belong to whoever is editing and outlive the next scan
                    Mood::update_mood_list((string) $value, 'song', $this->id, true);
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
                    try {
                        $metadata->save();
                    } catch (DatabaseException $error) {
                        debug_event(self::class, 'Failed to save metadata: ' . $error->getMessage(), 2);
                    }
                }
            }
        }
    }

    /**
     * _get_ext_info
     * This function gathers information from the song_ext_info table and adds it to the current object
     * @return array<string, scalar>
     */
    private function _get_ext_info(string $select = ''): array
    {
        $repository = self::getSongRepository();

        // a partial read never answers from the general row, which no longer carries the waveform
        if ($select === self::WAVEFORM_FILTER) {
            return $repository->getWaveformRow($this->id);
        }

        if ($select !== '') {
            return $repository->getPartialDataRow($this->id);
        }

        if (parent::is_cached('song_data', $this->id)) {
            return parent::get_from_cache('song_data', $this->id);
        }

        $results = $repository->getDataRow($this->id);
        parent::add_to_cache('song_data', $this->id, $results);

        return $results;
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
    private function getLicenseRepository(): LicenseRepositoryInterface
    {
        global $dic;

        return $dic->get(LicenseRepositoryInterface::class);
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
    private function getMetadataRepository(): MetadataRepositoryInterface
    {
        global $dic;

        return $dic->get(MetadataRepositoryInterface::class);
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
     * @deprecated
     */
    private function getSongTagWriter(): SongTagWriterInterface
    {
        global $dic;

        return $dic->get(SongTagWriterInterface::class);
    }

    private function has_info(int $song_id): bool
    {
        if (parent::is_cached('song', $song_id)) {
            $results = parent::get_from_cache('song', $song_id);
            $this->setSongInfoFromArray($results);

            return true;
        }

        $results = self::getSongRepository()->getRow($song_id);
        if (isset($results['id'])) {
            parent::add_to_cache('song', $song_id, $results);
            $this->setSongInfoFromArray($results);

            return true;
        }

        return false;
    }

    private function setSongDataProperty(string $key, mixed $value): void
    {
        switch ($key) {
            case 'comment':
                $this->comment = $value !== null ? (string) $value : null;
                break;
            case 'lyrics':
                $this->lyrics = $value !== null ? (string) $value : null;
                break;
            case 'label':
                $this->label = $value !== null ? (string) $value : null;
                break;
            case 'language':
                $this->language = $value !== null ? (string) $value : null;
                break;
            case 'waveform':
                $this->waveform = $value !== null ? (string) $value : null;
                break;
            case 'replaygain_track_gain':
                $this->replaygain_track_gain = $value === null ? null : (float) $value;
                break;
            case 'replaygain_track_peak':
                $this->replaygain_track_peak = $value === null ? null : (float) $value;
                break;
            case 'replaygain_album_gain':
                $this->replaygain_album_gain = $value === null ? null : (float) $value;
                break;
            case 'replaygain_album_peak':
                $this->replaygain_album_peak = $value === null ? null : (float) $value;
                break;
            case 'r128_track_gain':
                $this->r128_track_gain = $value === null ? null : (int) $value;
                break;
            case 'r128_album_gain':
                $this->r128_album_gain = $value === null ? null : (int) $value;
                break;
            case 'disksubtitle':
                $this->disksubtitle = $value !== null ? (string) $value : null;
                break;
            case 'bpm':
                $this->bpm = $value === null ? null : (float) $value;
                break;
        }
    }

    /**
     * @param array<string, mixed> $results
     */
    private function setSongInfoFromArray(array $results): void
    {
        if (array_key_exists('id', $results)) {
            $this->id = (int) $results['id'];
        }
        if (array_key_exists('file', $results)) {
            $this->file = $results['file'] !== null ? (string) $results['file'] : null;
        }
        if (array_key_exists('catalog', $results)) {
            $this->catalog = (int) $results['catalog'];
        }
        if (array_key_exists('album', $results)) {
            $this->album = (int) $results['album'];
        }
        if (array_key_exists('album_disk', $results)) {
            $this->album_disk = (int) $results['album_disk'];
        }
        if (array_key_exists('disk', $results)) {
            $this->disk = $results['disk'] === null ? null : (int) $results['disk'];
        }
        if (array_key_exists('year', $results)) {
            $this->year = (int) $results['year'];
        }
        if (array_key_exists('artist', $results)) {
            $this->artist = $results['artist'] === null ? null : (int) $results['artist'];
        }
        if (array_key_exists('title', $results)) {
            $this->title = $results['title'] !== null ? (string) $results['title'] : null;
        }
        if (array_key_exists('bitrate', $results)) {
            $this->bitrate = (int) $results['bitrate'];
        }
        if (array_key_exists('rate', $results)) {
            $this->rate = (int) $results['rate'];
        }
        if (array_key_exists('mode', $results)) {
            $this->mode = $results['mode'] !== null ? (string) $results['mode'] : null;
        }
        if (array_key_exists('size', $results)) {
            $this->size = (int) $results['size'];
        }
        if (array_key_exists('time', $results)) {
            $this->time = (int) $results['time'];
        }
        if (array_key_exists('track', $results)) {
            $this->track = $results['track'] === null ? null : (int) $results['track'];
        }
        if (array_key_exists('mbid', $results)) {
            $this->mbid = $results['mbid'] !== null ? (string) $results['mbid'] : null;
        }
        if (array_key_exists('played', $results)) {
            $this->played = (bool) $results['played'];
        }
        if (array_key_exists('enabled', $results)) {
            $this->enabled = (bool) $results['enabled'];
        }
        if (array_key_exists('update_time', $results)) {
            $this->update_time = $results['update_time'] === null ? null : (int) $results['update_time'];
        }
        if (array_key_exists('addition_time', $results)) {
            $this->addition_time = $results['addition_time'] === null ? null : (int) $results['addition_time'];
        }
        if (array_key_exists('user_upload', $results)) {
            $this->user_upload = $results['user_upload'] === null ? null : (int) $results['user_upload'];
        }
        if (array_key_exists('license', $results)) {
            $this->license = $results['license'] === null ? null : (int) $results['license'];
        }
        if (array_key_exists('composer', $results)) {
            $this->composer = $results['composer'] !== null ? (string) $results['composer'] : null;
        }
        if (array_key_exists('channels', $results)) {
            $this->channels = $results['channels'] === null ? null : (int) $results['channels'];
        }
        if (array_key_exists('total_count', $results)) {
            $this->total_count = (int) $results['total_count'];
        }
        if (array_key_exists('total_skip', $results)) {
            $this->total_skip = (int) $results['total_skip'];
        }
        if (array_key_exists('last_played', $results)) {
            $this->last_played = $results['last_played'] === null ? null : (int) $results['last_played'];
        }
        if (array_key_exists('albumartist', $results)) {
            $this->albumartist = $results['albumartist'] === null ? null : (int) $results['albumartist'];
        }
        if (array_key_exists('album_mbid', $results)) {
            $this->album_mbid = $results['album_mbid'] !== null ? (string) $results['album_mbid'] : null;
        }
        if (array_key_exists('artist_mbid', $results)) {
            $this->artist_mbid = $results['artist_mbid'] !== null ? (string) $results['artist_mbid'] : null;
        }
        if (array_key_exists('albumartist_mbid', $results)) {
            $this->albumartist_mbid = $results['albumartist_mbid'] !== null ? (string) $results['albumartist_mbid'] : null;
        }
    }

    private function setUpdatedFieldValue(string $key, mixed $value): void
    {
        switch ($key) {
            case 'bitrate':
                $this->bitrate = (int) $value;
                break;
            case 'comment':
                $this->comment = $value !== null ? (string) $value : null;
                break;
            case 'composer':
                $this->composer = $value !== null ? (string) $value : null;
                break;
            case 'label':
                $this->label = $value !== null ? (string) $value : null;
                break;
            case 'language':
                $this->language = $value !== null ? (string) $value : null;
                break;
            case 'license':
                $this->license = $value === null ? null : (int) $value;
                break;
            case 'mbid':
                $this->mbid = $value !== null ? (string) $value : null;
                break;
            case 'mode':
                $this->mode = $value !== null ? (string) $value : null;
                break;
            case 'rate':
                $this->rate = (int) $value;
                break;
            case 'size':
                $this->size = (int) $value;
                break;
            case 'title':
                $this->title = $value !== null ? (string) $value : null;
                break;
            case 'track':
                $this->track = $value === null ? null : (int) $value;
                break;
            case 'user_upload':
                $this->user_upload = $value === null ? null : (int) $value;
                break;
            case 'year':
                $this->year = (int) $value;
                break;
        }
    }
}
