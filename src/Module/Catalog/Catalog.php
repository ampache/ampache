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

namespace Ampache\Module\Catalog;

use Ahc\Cli\IO\Interactor;
use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Catalog\CatalogProgressTypeEnum;
use Ampache\Gui\Catalog\CatalogProgressView;
use Ampache\Module\Art\Art;
use Ampache\Module\Art\Collector\ArtCollectorInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Catalog\GarbageCollector\CatalogGarbageCollectorInterface;
use Ampache\Module\Database\database_object;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\Label\LabelNameFilterInterface;
use Ampache\Module\Metadata\MetadataEnabledInterface;
use Ampache\Module\Metadata\MetadataManagerInterface;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Song\Tag\SongTagWriterInterface;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Plugin\Plugin;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\System\Session;
use Ampache\Module\User\Activity\Useractivity;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\Recommendation;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Module\Util\VaInfo;
use Ampache\Plugin\AmpacheMusicBrainz;
use Ampache\Plugin\AmpacheTheaudiodb;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\CatalogFilterRepositoryInterface;
use Ampache\Repository\CatalogMapRepositoryInterface;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\LiveStreamRepositoryInterface;
use Ampache\Repository\MetadataRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\CatalogFieldEnum;
use Ampache\Repository\Model\container_item;
use Ampache\Repository\Model\displayable_item;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Metadata;
use Ampache\Repository\Model\Mood;
use Ampache\Repository\Model\ObjectNameTypeEnum;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Ampache\Repository\MoodRepositoryInterface;
use Ampache\Repository\ObjectNameRepositoryInterface;
use Ampache\Repository\PlaylistRepositoryInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Ampache\Repository\ShareRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\TagRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Ampache\Repository\VideoRepositoryInterface;
use Ampache\Repository\WantedRepositoryInterface;
use DateTime;
use Exception;
use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Throwable;

/**
 * This class handles all actual work in regards to the catalog,
 * it contains functions for creating/listing/updated the catalogs.
 */
abstract class Catalog extends database_object
{
    /** @var array<string, class-string> */
    public const array CATALOG_TYPES = [
        'beets' => Catalog_beets::class,
        'beetsremote' => Catalog_beetsremote::class,
        'dropbox' => Catalog_dropbox::class,
        'local' => Catalog_local::class,
        'remote' => Catalog_remote::class,
        'seafile' => Catalog_Seafile::class,
        'subsonic' => Catalog_subsonic::class,
    ];
    protected const string DB_TABLENAME = 'catalog';
    /* Used in functions */

    public ?string $catalog_type = null;
    public bool $enabled         = true;
    public ?string $gather_types = '';
    public int $id               = 0;
    public int $last_add;
    public ?int $last_clean = null;
    public int $last_update;
    public ?string $link           = null;
    public ?string $name           = null;
    public ?string $rename_pattern = '';
    public ?string $sort_pattern   = '';

    /**
     * Cache all files in catalog for quick lookup during add
     * @var array<string, int|string> $_filecache
     */
    protected array $_filecache = [];

    /**
     * This is a private var that's used during catalog builds
     * @var string[] $_playlists
     */
    protected array $_playlists = [];

    private ?string $f_link = null;

    /**
     * Run the cache_catalog_proc() on music catalogs.
     */
    public static function cache_catalogs(?Interactor $interactor = null, bool $cleanup = false): void
    {
        $cache_path   = (string) AmpConfig::get('cache_path', '');
        $cache_target = (string) AmpConfig::get('cache_target', '');
        // need a destination and target filetype
        if (is_dir($cache_path) && Core::is_readable($cache_path)) {
            $catalogs = self::get_all_catalogs('music');
            $scandir  = scandir($cache_path) ?: [];
            foreach ($scandir as $file) {
                // check for lost catalogs
                if ('.' === $file || '..' === $file) {
                    continue;
                } elseif (is_dir($cache_path . '/' . $file) && !in_array($file, $catalogs)) {
                    debug_event(self::class, 'WARNING: Orphaned catalog cache ' . $cache_path . '/' . $file, 5);
                    $interactor?->warn(
                        sprintf('WARNING: Orphaned catalog cache %s/%s', $cache_path, $file),
                        true
                    );
                }
            }
            // ReplayGain (_rg/_car) output is normalised per-source and must never be cached
            if ($cache_target && in_array($cache_target, Stream::NON_CACHEABLE_FORMATS, true)) {
                debug_event(self::class, 'cache_catalogs: refusing to cache non-cacheable target ' . $cache_target, 3);
                $interactor?->warn(
                    sprintf('cache_catalogs: `cache_target` "%s" cannot be cached; skipping', $cache_target),
                    true
                );

                return;
            }
            if ($cache_target) {
                foreach ($catalogs as $catalogid) {
                    $catalog = self::create_from_id($catalogid);
                    if ($catalog === null) {
                        break;
                    }

                    // don't cache everything when cleaning
                    if ($cleanup === false) {
                        debug_event(self::class, 'cache_catalogs: ' . $catalogid, 5);
                        $interactor?->info(
                            sprintf('cache_catalogs: %s', $catalogid),
                            true
                        );

                        $catalog->cache_catalog_proc();
                    }

                    // only walk this catalog's own cache directory
                    $catalog_cache_dir = rtrim(trim($cache_path), '/') . '/' . $catalogid;
                    if (!is_dir($catalog_cache_dir)) {
                        continue;
                    }

                    $catalog_dirs = new RecursiveDirectoryIterator($catalog_cache_dir);
                    $dir_files    = new RecursiveIteratorIterator($catalog_dirs);
                    $cache_files  = new RegexIterator($dir_files, sprintf('/\.%s/i', $cache_target));
                    debug_event(self::class, 'cache_catalogs: cleaning old files', 5);
                    $interactor?->info(
                        'cache_catalogs: cleaning old files',
                        true
                    );

                    $remote_catalog = ($catalog instanceof Catalog_remote || $catalog instanceof Catalog_subsonic);
                    $remote_cache   = (bool) AmpConfig::get('cache_remote', false);

                    // fetch all song paths in one query
                    $cache_list = [];
                    foreach ($cache_files as $file) {
                        $cache_list[] = (string) $file;
                    }
                    $song_files = [];
                    $song_ids   = array_values(array_unique(array_map(static fn($file) => (int) pathinfo($file, PATHINFO_FILENAME), $cache_list)));
                    foreach (array_chunk($song_ids, 500) as $chunk) {
                        $idlist     = implode(',', $chunk);
                        $db_results = Dba::read("SELECT `id`, `file` FROM `song` WHERE `id` IN (" . $idlist . ");");
                        while ($row = Dba::fetch_assoc($db_results)) {
                            $song_files[(int) $row['id']] = (string) $row['file'];
                        }
                    }

                    foreach ($cache_list as $file) {
                        $pathinfo  = pathinfo((string) $file);
                        $song_id   = (int) $pathinfo['filename'];
                        $song_file = $song_files[$song_id] ?? '';
                        $extension = ($song_file !== '')
                            ? (pathinfo($song_file, PATHINFO_EXTENSION) ?: '')
                            : '';
                        if ($song_file === '' || $extension === '') {
                            unlink($file);
                            debug_event(self::class, 'cache_catalogs: removed (not in database) {' . $file . '}', 4);
                            $interactor?->info(
                                sprintf('cache_catalogs: removed (not in database) {%s}', $file),
                                true
                            );
                            continue;
                        }

                        $cache_ext = $pathinfo['extension'] ?? '';
                        if ($cache_ext !== $cache_target) {
                            unlink($file);
                            debug_event(self::class, 'cache_catalogs: removed (cache_target !== ' . $cache_ext . ') {' . $file . '}', 4);
                            $interactor?->info(
                                sprintf('cache_catalogs: removed (cache_target !== %s) {%s}', $cache_ext, $file),
                                true
                            );
                            continue;
                        }

                        if ($remote_catalog && $remote_cache === false) {
                            unlink($file);
                            debug_event(self::class, 'cache_catalogs: removed (cache_remote) {' . $file . '}', 4);
                            $interactor?->info(
                                sprintf('cache_catalogs: removed (cache_remote) {%s}', $file),
                                true
                            );
                            continue;
                        }

                        if (
                            $extension
                            && !(AmpConfig::get('cache_' . $extension, false))
                        ) {
                            unlink($file);
                            debug_event(self::class, 'cache_catalogs: removed (cache_' . $extension . ' ' . $song_file . ') {' . $file . '}', 4);
                            $interactor?->info(
                                sprintf('cache_catalogs: removed (cache_%s %s) {%s}', $extension, $song_file, $file),
                                true
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * cache_remote_file
     */
    public static function cache_remote_file(string $file_target, string $remote_url): bool
    {
        try {
            $filehandle = fopen($file_target, 'w');
            if (!is_resource($filehandle)) {
                debug_event(self::class, 'Could not open file: ' . $file_target, 5);

                return false;
            }
            if ($remote_url === '' || !filter_var($remote_url, FILTER_VALIDATE_URL)) {
                debug_event(self::class, 'Invalid URL: ' . $remote_url, 5);

                return false;
            }

            $curl = curl_init();
            curl_setopt_array(
                $curl,
                [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FILE => $filehandle,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_PIPEWAIT => true,
                    CURLOPT_URL => $remote_url,
                ]
            );
            curl_exec($curl);
            fclose($filehandle);

            return true;
        } catch (Exception $error) {
            debug_event(self::class, 'CURL error: ' . $error->getMessage(), 5);

            return false;
        }
    }

    public static function can_remove(
        Podcast_Episode|AlbumDisk|Video|Song|Album|Artist|Label|Folder $libitem,
        ?int $user_id = 0,
    ): bool {
        if (!$user_id) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? false;
        }

        if (!$user_id) {
            return false;
        }

        if (!AmpConfig::get('delete_from_disk')) {
            return false;
        }

        // don't delete folders with media inside
        if ($libitem instanceof Folder && $libitem->object_count > 0) {
            return false;
        }

        return (
            Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            || (
                $libitem->get_user_owner() == $user_id
                && AmpConfig::get('upload_allow_remove')
            )
        );
    }

    /**
     * check_filter_catalog_enabled
     * Returns the `enabled` status of the filter/catalog combination
     */
    public static function check_filter_catalog_enabled(int $filter_id, int $catalog_id): bool
    {
        return self::getCatalogFilterRepository()->isCatalogEnabled($filter_id, $catalog_id);
    }

    /**
     * check_int
     * Check to make sure a number fits into the database
     */
    public static function check_int(int|float $my_int, int|float $max, int $min): int|float
    {
        if ($my_int > $max) {
            return $max;
        }

        if ($my_int < $min) {
            return $min;
        }

        return $my_int;
    }

    /**
     * clean_duplicate_artists
     *
     * Artists that have the same mbid shouldn't be duplicated but can be created and updated based on names
     */
    public static function clean_duplicate_artists(): void
    {
        debug_event(self::class, "Clean Artists with duplicate mbid's", 5);
        foreach (self::getArtistRepository()->findDuplicateMbidGroups() as $row) {
            debug_event(self::class, "clean_duplicate_artists " . $row['maxid'] . "=>" . $row['minid'], 5);
            $maxId = $row['maxid'];
            $minId = $row['minid'];
            // migrate linked tables first
            //Stats::migrate('artist', $maxId, $minId);
            Useractivity::migrate('artist', $maxId, $minId);
            Recommendation::migrate('artist', $maxId);
            self::getShareRepository()->migrate('artist', $maxId, $minId);
            self::getShoutRepository()->migrate('artist', $maxId, $minId);
            Tag::migrate('artist', $maxId, $minId);
            Mood::migrate('artist', $maxId, $minId);
            Userflag::migrate('artist', $maxId, $minId);
            Label::migrate('artist', $maxId, $minId);
            Rating::migrate('artist', $maxId, $minId);
            self::getWantedRepository()->migrateArtist($maxId, $minId);
            self::migrate_map('artist', $maxId, $minId);

            // replace all songs and albums with the original artist
            Artist::migrate($maxId, $minId);
        }

        // remove the duplicates after moving everything
        self::getArtistRepository()->collectGarbage();
        self::getAlbumRepository()->collectGarbage();
    }

    /**
     * clean_empty_albums
     */
    public static function clean_empty_albums(bool $song_check = true): void
    {
        $albumRepository      = self::getAlbumRepository();
        $catalogMapRepository = self::getCatalogMapRepository();

        $deleted = 0;
        foreach ($albumRepository->findEmpty() as $row) {
            $deleted++;
            $albumRepository->deleteEmpty($row['id']);
            $catalogMapRepository->deleteForObject('album', $row['id']);
            debug_event(self::class, 'clean_empty_albums deleted ' . $row['id'], 5);
        }

        if ($deleted > 0) {
            $counter = self::getCatalogCounter();
            $counter->count(CountableTableEnum::ALBUM);
            $counter->count(CountableTableEnum::ALBUM_DISK);
        }

        if ($song_check) {
            // these files have missing albums so you can't verify them without updating from tags first
            foreach (self::getSongRepository()->findIdsWithMissingAlbum() as $song_id) {
                self::update_single_item('song', $song_id, true, true);
            }
        }
    }

    /**
     * clear_catalog_cache
     */
    public static function clear_catalog_cache(): void
    {
        // clear caches if enabled to allow getting the new object
        parent::remove_from_cache('user_catalog');
        parent::remove_from_cache('user_catalogmusic');
        if (AmpConfig::get('podcast')) {
            parent::remove_from_cache('user_catalogpodcast');
        }

        if (AmpConfig::get('video')) {
            parent::remove_from_cache('user_catalogvideo');
        }
    }

    /**
     * count_table
     *
     * Count and/or Update a table count when adding/removing from the server
     */
    public static function count_table(CountableTableEnum $table, ?int $catalog_id = 0, ?int $update_time = 0, ?int $limit = 0): int
    {
        $counter = self::getCatalogCounter();

        return ((int) $catalog_id > 0)
            ? $counter->countForCatalog($table, (int) $catalog_id, (int) $update_time, (int) $limit)
            : $counter->count($table);
    }

    /**
     * create
     *
     * This creates a new catalog entry and associate it to current instance
     *
     * @param array{
     *     name: string,
     *     path?: string,
     *     uri?: string,
     *     type: string,
     *     rename_pattern: string,
     *     sort_pattern: string,
     *     gather_media: string,
     *     username?: ?string,
     *     password?: ?string,
     *     library_name?: string,
     *     server_uri?: string,
     *     api_call_delay?: int|null,
     *     beetsdb?: string,
     *     apikey?: ?string,
     *     secret?: ?string,
     *     authtoken?: ?string,
     *     getchunk?: string|int|null,
     * } $data
     */
    public static function create(array $data): int
    {
        $name           = $data['name'];
        $type           = $data['type'];
        $rename_pattern = $data['rename_pattern'];
        $sort_pattern   = $data['sort_pattern'];
        $gather_types   = $data['gather_media'];

        // Should it be an array? Not now.
        if (!in_array($gather_types, ['music', 'video', 'podcast'])) {
            return 0;
        }

        $insert_id = 0;
        /** @var Catalog_beets|Catalog_beetsremote|Catalog_dropbox|Catalog_local|Catalog_remote|Catalog_Seafile|Catalog_subsonic|null $classname */
        $classname = self::CATALOG_TYPES[$type] ?? null;
        if ($classname === null) {
            return $insert_id;
        }

        $catalogRepository = self::getCatalogRepository();

        $insert_id = $catalogRepository->insert($name, $type, $rename_pattern, $sort_pattern, $gather_types);
        if ($insert_id === 0) {
            AmpError::add('general', T_('Failed to create the catalog, check the debug logs'));
            debug_event(self::class, 'Insert failed: ' . json_encode($data), 2);

            return 0;
        }

        self::clear_catalog_cache();
        self::getCatalogCounter()->count(CountableTableEnum::CATALOG);

        $create_type = $classname::create_type($insert_id, $data);

        if (!$create_type) {
            $catalogRepository->deleteRow($insert_id);
            $insert_id = 0;
        }

        return $insert_id;
    }

    /**
     * create_catalog_type
     * This function attempts to create a catalog type
     */
    public static function create_catalog_type(string $type, int $catalog_id = 0): ?Catalog
    {
        if (!$type) {
            return null;
        }

        /** @var Catalog_beets|Catalog_beetsremote|Catalog_dropbox|Catalog_local|Catalog_remote|Catalog_Seafile|Catalog_subsonic|null $controller */
        $controller = self::CATALOG_TYPES[$type] ?? null;
        if ($controller === null) {
            /* Throw Error Here */
            debug_event(self::class, 'Unable to load ' . $type . ' catalog type', 2);

            return null;
        } // include

        $catalog = ($catalog_id > 0)
            ? new $controller($catalog_id)
            : new $controller();

        // identify if it's actually enabled
        $enabled = self::getCatalogRepository()->findEnabled($catalog->id);
        if ($enabled !== null) {
            $catalog->enabled = $enabled;
        }

        return $catalog;
    }

    /**
     * Create a catalog from its id.
     */
    public static function create_from_id(int $catalog_id): ?Catalog
    {
        $type = self::getCatalogRepository()->findType($catalog_id);
        if ($type === null) {
            return null;
        }

        return self::create_catalog_type($type, $catalog_id);
    }

    /**
     * delete
     * Deletes the catalog and everything associated with it
     */
    public static function delete(int $catalog_id): bool
    {
        $catalog = self::create_from_id($catalog_id);
        if ($catalog === null) {
            return false;
        }

        $type = CatalogTypeEnum::tryFrom($catalog->get_type());
        if ($type === null) {
            return false;
        }

        // Large catalog deletion can take time
        set_time_limit(0);

        if (!self::getSongRepository()->deleteByCatalog($catalog_id)) {
            return false;
        }

        self::clean_empty_albums();

        if (!self::getVideoRepository()->deleteByCatalog($catalog_id)) {
            return false;
        }

        if (!self::getPodcastRepository()->deleteByCatalog($catalog_id)) {
            return false;
        }

        if (!self::getLiveStreamRepository()->deleteByCatalog($catalog_id)) {
            return false;
        }

        $catalogRepository = self::getCatalogRepository();
        if (!$catalogRepository->deleteSubTypeRow($type, $catalog_id)) {
            return false;
        }

        // Next Remove the Catalog Entry it's self
        $catalogRepository->deleteRow($catalog_id);

        // everything the catalog held went with it, so the stored totals follow
        $counter = self::getCatalogCounter();
        $counter->count(CountableTableEnum::CATALOG);
        $counter->count(CountableTableEnum::SONG);
        $counter->count(CountableTableEnum::VIDEO);
        $counter->count(CountableTableEnum::PODCAST);
        $counter->count(CountableTableEnum::PODCAST_EPISODE);
        $counter->count(CountableTableEnum::LIVE_STREAM);

        // run garbage collection
        self::getCatalogGarbageCollector()->collect();

        return true;
    }

    /**
     * filter_catalog_count
     * This returns the number of catalogs assigned to a filter.
     */
    public static function filter_catalog_count(int $filter_id): int
    {
        return self::getCatalogFilterRepository()->countCatalogs($filter_id);
    }

    /**
     * filter_tag_results
     * This filters and normalizes the tag results from get_media_tags
     * @param array<string, mixed> $results
     * @return array<string, mixed>
     */
    public static function filter_tag_results(array $results, ?Song $song = null): array
    {
        $results['catalog']      = $song?->getCatalogId() ?? $results['catalog'];
        $results['year']         = self::normalize_year($results['year'] ?? 0);
        $results['disk']         = (Album::sanitize_disk($results['disk']) > 0) ? Album::sanitize_disk($results['disk']) : 1;
        $results['disksubtitle'] = $results['disksubtitle'] ?: null;
        $results['isrc']         = (isset($results['isrc']) && is_string($results['isrc'])) ? [$results['isrc']] : $results['isrc'] ?? [];
        $results['title']        = self::_check_length(self::_check_title($results['title'], $results['file']));
        $results['bitrate']      = (!empty($results['bitrate'])) ? (int) $results['bitrate'] : 0;
        $results['rate']         = (!empty($results['rate'])) ? (int) $results['rate'] : 0;
        if (!in_array($results['mode'], ['vbr', 'cbr', 'abr'])) {
            debug_event(self::class, 'Error analyzing: ' . $results['file'] . ' unknown file bitrate mode: ' . $results['mode'], 2);
        }
        $results['mode']     = (in_array($results['mode'], ['vbr', 'cbr', 'abr'])) ? $results['mode'] : 'vbr';
        $results['channels'] = (!empty($results['channels'])) ? (int) $results['channels'] : 0;
        $results['size']     = (!empty($results['size'])) ? (int) $results['size'] : 0;
        $results['time']     = (strlen((string) $results['time']) > 5)
            ? (int) substr((string) $results['time'], -5, 5)
            : (int) ($results['time']);
        if ($results['time'] < 0) {
            // fall back to last time if you fail to scan correctly
            $results['time'] = $song->time ?? 0;
        }

        $results['track']    = self::_check_track((string) $results['track']);
        $results['mbid']     = (!empty($results['mb_trackid'])) ? $results['mb_trackid'] : null;
        $results['composer'] = (!empty($results['composer'])) ? self::_check_length($results['composer']) : null;
        //$results['mime'] = $results['mime']; // UPDATE ONLY (Generated from the filename)

        // info for the song_data table. used in Song::update_song
        if (!empty($results['license'])) {
            $licenseRepository = self::getLicenseRepository();
            // Lookup by ID first
            $license = (is_numeric($results['license']))
                ? $licenseRepository->findById((int) $results['license'])
                : null;
            $licenseId = $license?->getId();
            // only lookup string licenses from tags
            if ($licenseId === null) {
                $licenseName = (string) $results['license'];
                $licenseId   = $licenseRepository->find($licenseName);

                if (
                    $licenseId === 0
                    || $licenseId === null
                ) {
                    $license = $licenseRepository->prototype()
                        ->setName($licenseName);

                    $license->save();

                    $licenseId = $license->getId();
                }
            }

            $results['license_id'] = $licenseId;
        } else {
            $results['license_id'] = $song?->license;
        }

        $results['label'] = (isset($results['publisher']))
            ? self::_check_length($results['publisher'], 128)
            : null;

        $results['language']              = (!empty($results['language'])) ? self::_check_length($results['language'], 128) : null;
        $results['replaygain_track_gain'] = (is_null($results['replaygain_track_gain'])) ? null : (float) $results['replaygain_track_gain'];
        $results['replaygain_track_peak'] = (is_null($results['replaygain_track_peak'])) ? null : (float) $results['replaygain_track_peak'];
        $results['replaygain_album_gain'] = (is_null($results['replaygain_album_gain'])) ? null : (float) $results['replaygain_album_gain'];
        $results['replaygain_album_peak'] = (is_null($results['replaygain_album_peak'])) ? null : (float) $results['replaygain_album_peak'];
        $results['r128_track_gain']       = (is_null($results['r128_track_gain'])) ? null : (int) $results['r128_track_gain'];
        $results['r128_album_gain']       = (is_null($results['r128_album_gain'])) ? null : (int) $results['r128_album_gain'];

        // song_data.bpm is decimal(6,2), so round to what the column can hold or every rescan reads back a different value
        $bpm             = (float) ($results['bpm'] ?? 0);
        $results['bpm']  = ($bpm > 0 && $bpm <= 9999.99) ? round($bpm, 2) : null;

        if (empty($results['genre'])) {
            $results['genre'] = [];
        } elseif (!is_array($results['genre'])) {
            $results['genre'] = [$results['genre']];
        }

        if (empty($results['mood'])) {
            $results['mood'] = [];
        } elseif (!is_array($results['mood'])) {
            $results['mood'] = [$results['mood']];
        }

        $results['user_upload'] = $results['user_upload'] ?? null;
        $results['artist_mbid'] = $results['mb_artistid'] ?? null;
        $results['artist']      = self::_check_length($results['artist']);
        if (empty($results['artists']) && !empty($results['artist'])) {
            $results['artists'] = [$results['artist']];
        }

        $results['album']            = self::_check_length($results['album']);
        $results['album_mbid']       = $results['mb_albumid'] ?? null;
        $results['album_mbid_group'] = $results['mb_albumid_group'] ?? null;
        $results['release_type']     = self::_check_length($results['release_type'], 32);
        if (empty($results['album'])) {
            $results['album_id'] = ($song?->album > 0)
                ? $song->album
                : Album::check($song->catalog ?? 0, '', $song->year ?? 0, null, null, $song?->get_album_artist() ?? $song->artist ?? null);
        }

        $results['albumartist'] = ($results['albumartist'])
            ? self::_check_length($results['albumartist'])
            : null;
        $results['albumartist_mbid'] = $results['mb_albumartistid'] ?? null;
        if (empty($results['albumartist'])) {
            $orphan_albumartist = T_(($song?->get_album_artist_fullname()) ?? T_('Unknown (Orphaned)')) === T_('Unknown (Orphaned)');

            $results['albumartist_id'] = ($song && $song->get_album_artist() > 0 && (!$orphan_albumartist || empty($results['album'])))
                ? $song->get_album_artist()
                : Artist::check($song?->get_parent_fullname() ?? $results['artist'], $results['albumartist_mbid']);
        }

        if (empty($results['albumartist']) && $results['albumartist_id'] > 0) {
            $results['albumartist'] = Artist::get_fullname_by_id($results['albumartist_id']);
        }

        $results['original_year']  = (!empty($results['original_year'])) ? (int) $results['original_year'] : null;
        $results['barcode']        = self::_check_length($results['barcode'], 64);
        $results['catalog_number'] = self::_check_length($results['catalog_number'], 64);
        $results['version']        = self::_check_length($results['version'], 64);

        $results['artists_array']          = $results['artists'] ?? [];
        $results['mb_artistid_array']      = $results['mb_artistid_array'] ?? [];
        $results['mb_albumartistid_array'] = $results['mb_albumartistid_array'] ?? [];

        return $results;
    }

    /**
     * filter_user_count
     * Returns the number of users assigned to a particular filter.
     */
    public static function filter_user_count(int $filter_id): int
    {
        return self::getUserRepository()->countByCatalogFilterGroup($filter_id);
    }

    /**
     * Update the catalog mapping for various types
     * @param string[] $tables
     */
    public static function garbage_collect_mapping(array $tables): void
    {
        $mapTables = [];
        foreach ($tables as $type) {
            $mapTable = CatalogMapTableEnum::tryFrom($type);
            if ($mapTable !== null) {
                $mapTables[] = $mapTable;
            }
        }

        self::getCatalogMapRepository()->collectGarbage($mapTables);
    }

    /**
     * gather_art_item
     */
    public static function gather_art_item(string $type, int $object_id, bool $db_art_first = false, bool $api = false): bool
    {
        $className = ObjectTypeToClassNameMapper::map($type);
        /** @var library_item $libitem */
        $libitem = new $className($object_id);

        $inserted = false;
        $options  = [];
        if ($libitem instanceof Song) {
            $libitem->fill_ext_info();
        }

        if ($libitem->getId() > 0 && $libitem instanceof displayable_item) {
            // Only search on items with default art kind AS `default`.
            if ($libitem->get_default_art_kind() == 'default') {
                $keywords = $libitem->get_keywords();
                $keyword  = '';
                foreach ($keywords as $key => $word) {
                    $options[$key] = $word['value'];
                    if ($word['important'] && !empty($word['value'])) {
                        $keyword .= ' ' . $word['value'];
                    }
                }

                $options['keyword'] = $keyword;
            }

            $parent = ($type !== 'album' && $libitem instanceof container_item)
                ? $libitem->get_parent()
                : null;

            if (!empty($parent)) {
                self::gather_art_item($parent['object_type']->value, $parent['object_id'], $db_art_first, $api);
            }
        }

        $art = new Art($object_id, $type);
        // don't search for art when you already have it
        if ($art->has_db_info() && $db_art_first) {
            debug_event(self::class, sprintf('gather_art_item %s: {%d} blocked', $type, $object_id), 5);
            $results = [];
        } else {
            debug_event(self::class, sprintf('gather_art_item %s: {%d} searching', $type, $object_id), 4);

            global $dic;
            $results = $dic->get(ArtCollectorInterface::class)->collect(
                $art,
                $options
            );
        }

        foreach ($results as $result) {
            if (isset($result['db'])) {
                debug_event(self::class, 'Database already has image.', 3);
                continue;
            }
            // Pull the string representation from the source
            $image = Art::get_from_source($result, $type);
            if (strlen($image) > '5') {
                $inserted = $art->insert($image, $result['mime']);
                if ($inserted === true) {
                    break;
                }
            } else {
                debug_event(self::class, 'Image less than 5 chars, not inserting', 3);
            }
        }

        if ($type == 'video' && AmpConfig::get('generate_video_preview')) {
            Video::generate_preview($object_id);
        }

        if (Ui::check_ticker() && !$api) {
            Ui::update_text('read_art_' . $object_id, (string) $libitem->get_fullname());
        }

        return ($inserted === true);
    }

    /**
     * get_albums
     *
     * Returns an array of ids of albums that have songs in the catalogs parameter
     * @param int[]|null $catalogs
     * @return int[]
     */
    public static function get_albums(int $size = 0, int $offset = 0, ?array $catalogs = null): array
    {
        return self::getAlbumRepository()->getIdsByCatalogs($catalogs, $size, $offset);
    }

    /**
     * get_albums_by_artist
     *
     * Returns an array of ids of albums that have songs in the catalogs parameter, grouped by artist
     * @param int[]|null $catalogs
     * @return int[]
     * @oaram int $offset
     */
    public static function get_albums_by_artist(int $size = 0, int $offset = 0, ?array $catalogs = null): array
    {
        return self::getAlbumRepository()->getIdsByCatalogsOrderedByArtist($catalogs, $size, $offset);
    }

    /**
     * get_all_catalogs
     *
     * Pull all the current catalogs and return an array of ids of what you find
     * @return int[]
     *
     * @see CatalogLoader
     */
    public static function get_all_catalogs(string $filter_type = ''): array
    {
        return self::getCatalogRepository()->getIds($filter_type);
    }

    /**
     * get_all_song_ids
     *
     * Returns an array of ids of enabled songs across every catalog (or the ones supplied)
     * @param int[]|null $catalogs
     * @return int[]
     */
    public static function get_all_song_ids(int $size = 0, int $offset = 0, ?array $catalogs = null): array
    {
        return self::getSongRepository()->getEnabledIds(
            $catalogs,
            $size,
            $offset,
            (bool) AmpConfig::get('catalog_disable')
        );
    }

    /**
     * get_artist_arrays
     *
     * Get each array of [id, f_name, name, album_count, song_count, catalog_id, has_art] for artists in an array of catalog id's
     * @param int[]|string[] $catalogs
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
    public static function get_artist_arrays(array $catalogs): array
    {
        return self::getArtistRepository()->getArrayRowsByCatalogs(array_map(intval(...), $catalogs));
    }

    /**
     * get_artists
     *
     * This returns an array of artists that have songs in the catalogs parameter
     * @return Artist[]
     */
    public static function get_artists(?array $catalogs = null, int $size = 0, int $offset = 0): array
    {
        $results = [];
        foreach (self::getArtistRepository()->getRowsByCatalogs($catalogs, $size, $offset) as $row) {
            /** @var array{id: int, name: ?string, prefix: ?string, mbid: ?string, summary: ?string, placeformed: ?string, yearformed: ?string, last_update: ?string, user: ?string, manual_update: ?string, time: ?string, album_count: int, song_count: int, album_disk_count: int, total_count: int, total_skip: int, addition_time: ?string, weight: ?string} $row */
            $results[] = Artist::construct_from_array($row);
        }

        return $results;
    }

    /**
     * Return full path of the cached music file.
     */
    public static function get_cache_path(int $object_id, int $catalog_id, string $path = '', string $target = ''): ?string
    {
        // need a destination and target filetype
        if (!is_dir($path) || empty($target)) {
            return null;
        }

        // make a folder per catalog
        if (!is_dir(rtrim(trim($path), '/') . '/' . $catalog_id)) {
            mkdir(rtrim(trim($path), '/') . '/' . $catalog_id, 0775, true);
        }

        // Create subdirectory based on the 2 last digit of the SongID. We prevent having thousands of file in one directory.
        $path .= '/' . $catalog_id . '/' . substr((string) $object_id, -1, 1) . '/' . substr((string) $object_id, -2, 1) . '/';
        if (!file_exists($path)) {
            mkdir($path, 0775, true);
        }

        return rtrim(trim($path), '/') . '/' . $object_id . '.' . $target;
    }

    /**
     * get_catalog_filters
     * This returns the filters, sorting by name
     *
     * @return Generator<array{id: int, name: string}>
     */
    public static function get_catalog_filters(): Generator
    {
        yield from self::getCatalogFilterRepository()->findGroups();
    }

    /**
     * get_catalog_id_filter
     *
     * Return an SQL condition restricting $column to the objects found in a single catalog.
     * Tables owning a `catalog` column are filtered directly, artists and playlists use the `catalog_map` table.
     * A catalog_id of 0 means "don't filter"; a negative id matches nothing (used for a catalog the user can't browse).
     * An empty string is returned when the type can't be filtered (the caller must not filter in that case).
     */
    public static function get_catalog_id_filter(string $type, string $column, int $catalog_id): string
    {
        if ($catalog_id === 0) {
            return '';
        }

        return match ($type) {
            'album', 'album_disk', 'live_stream', 'podcast', 'podcast_episode', 'song', 'video' => sprintf('%s IN (SELECT `id` FROM `%s` WHERE `catalog` = %d) ', $column, $type, $catalog_id),
            'album_artist', 'artist', 'playlist', 'song_artist' => sprintf("%s IN (SELECT `object_id` FROM `catalog_map` WHERE `object_type` = '%s' AND `catalog_id` = %d) ", $column, $type, $catalog_id),
            default => '',
        };
    }

    /**
     * get_catalogs
     *
     * Pull all the current catalogs for your user and return an array of ids
     * @return int[]
     *
     * @see CatalogLoader
     */
    public static function get_catalogs(string $filter_type = '', ?int $user_id = null, bool $query = false): array
    {
        $filter_user = null;
        if (AmpConfig::get('catalog_filter') && ($user_id > 0 || $user_id === -1)) {
            $filter_user = $user_id;
        }

        $results = self::getCatalogRepository()->getIds(
            $filter_type,
            (bool) AmpConfig::get('catalog_disable'),
            $filter_user
        );

        if ($results === [] && $query) {
            return [-1];
        }

        // orphaned albums are in catalog 0
        $results[] = 0;

        return $results;
    }

    /**
     * Get Folder for browsing (Used for WebDav)
     */
    public static function get_child(string $name, ?int $catalog_id = 0, ?int $parent_id = null): Folder|Podcast_Episode|Song|Video|null
    {
        return ($name === '/')
            ? new Folder(-1)
            : self::getFolderRepository()->getByName($name, $catalog_id, $parent_id);
    }

    /**
     * get all artists or artist children of a catalog id (Used for WebDav)
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    public static function get_children(string $name, ?int $catalog_id = 0, ?int $parent_id = null): array
    {
        $folder = ($name === '/')
            ? new Folder(-1)
            : self::getFolderRepository()->getByName($name, $catalog_id, $parent_id);

        return ($folder instanceof Folder && $folder->isNew() === false)
            ? $folder->get_children($name)
            : [];
    }

    /**
     * Get enable sql filter;
     */
    public static function get_enable_filter(string $type, string $catalog_id): string
    {
        $sql = "";
        if ($type == "song" || $type == "album" || $type == "artist" || $type == "album_artist") {
            if ($type == "song") {
                $type = "id";
            }

            $sql = "(SELECT COUNT(`song_dis`.`id`) FROM `song` AS `song_dis` LEFT JOIN `catalog` AS `catalog_dis` ON `catalog_dis`.`id` = `song_dis`.`catalog` WHERE `song_dis`.`" . $type . "` = " . $catalog_id . " AND `catalog_dis`.`enabled` = '1' GROUP BY `song_dis`.`" . $type . "`) > 0";
        } elseif ($type == "album_disk") {
            $sql = "(SELECT DISTINCT COUNT(`album_disk`.`id`) FROM `album_disk` LEFT JOIN `album` AS `album_dis` ON `album_dis`.`id` = `album_disk`.`album_id` LEFT JOIN `catalog` AS `catalog_dis` ON `catalog_dis`.`id` = `album_dis`.`catalog` WHERE `album_dis`.`id` = " . $catalog_id . " AND `catalog_dis`.`enabled` = '1' GROUP BY `album_disk`.`id`) > 0";
        } elseif ($type == "video") {
            $sql = "(SELECT COUNT(`video_dis`.`id`) FROM `video` AS `video_dis` LEFT JOIN `catalog` AS `catalog_dis` ON `catalog_dis`.`id` = `video_dis`.`catalog` WHERE `video_dis`.`id` = " . $catalog_id . " AND `catalog_dis`.`enabled` = '1' GROUP BY `video_dis`.`id`) > 0";
        }

        return $sql;
    }

    /**
     * get_id_from_file
     *
     * Get media id from the file path.
     */
    public static function get_id_from_file(string $file_path, string $media_type): int
    {
        $objectId = match ($media_type) {
            'song' => self::getSongRepository()->findIdByFile($file_path),
            'video' => self::getVideoRepository()->findIdByFile($file_path),
            'podcast_episode' => self::getPodcastEpisodeRepository()->findIdByFile($file_path),
            default => null,
        };

        return $objectId ?? 0;
    }

    /**
     * get_ids_from_folder
     *
     * Get media id's from a base folder path
     *
     * @return int[]
     */
    public static function get_ids_from_folder(string $folder_path, string $media_type): array
    {
        return match ($media_type) {
            'song' => self::getSongRepository()->getIdsByFilePrefix($folder_path),
            'video' => self::getVideoRepository()->getIdsByFilePrefix($folder_path),
            'podcast_episode' => self::getPodcastEpisodeRepository()->getIdsByFilePrefix($folder_path),
            default => [],
        };
    }

    /**
     * get_name_array
     *
     * Get each array of fullname's for a object type
     * @param int[]|string[] $objects
     * @return array{id: int|string, name: string}[]
     */
    public static function get_name_array(array $objects, string $table, string $sort = '', string $order = 'ASC'): array
    {
        $type = ObjectNameTypeEnum::tryFrom($table);
        if ($type === null) {
            return [];
        }

        return self::getObjectNameRepository()->findNames($type, array_values($objects), $sort, $order);
    }

    /**
     * get_newest_podcasts
     * @return Podcast_Episode[]
     */
    public static function get_newest_podcasts(int $count): array
    {
        $catalogs = self::get_catalogs('podcast');
        $results  = [];

        foreach ($catalogs as $catalog_id) {
            $catalog = self::create_from_id($catalog_id);
            if ($catalog === null) {
                break;
            }

            $episode_ids = $catalog->get_newest_podcasts_ids($count);
            foreach ($episode_ids as $episode_id) {
                $results[] = new Podcast_Episode($episode_id);
            }
        }

        return $results;
    }

    /**
     * get_podcasts
     * @param int[]|null $catalogs
     * @return Podcast[]
     */
    public static function get_podcasts(?array $catalogs = null): array
    {
        if (!$catalogs) {
            $catalogs = self::get_catalogs('podcast');
        }

        $podcastRepository = self::getPodcastRepository();

        $results = [];
        foreach ($catalogs as $catalog_id) {
            $catalog = self::create_from_id($catalog_id);
            if ($catalog === null) {
                break;
            }

            $podcast_ids = $catalog->get_podcast_ids();
            foreach ($podcast_ids as $podcast_id) {
                $podcast = $podcastRepository->findById($podcast_id);
                if ($podcast !== null) {
                    $results[] = $podcast;
                }
            }
        }

        return $results;
    }

    /**
     * get_server_counts
     *
     * This returns the current number of songs, videos, albums, artists, items, etc across all catalogs on the server
     * @return array<string, int>
     */
    public static function get_server_counts(int $user_id): array
    {
        return self::getCatalogCounter()->getStoredCounts($user_id);
    }

    /**
     * get_stats
     *
     * This returns an hash with the #'s for the different
     * objects that are associated with this catalog. This is used
     * to build the stats box, it also calculates time.
     * @return array<string, int|string>
     */
    public static function get_stats(?int $catalog_id = 0): array
    {
        $counts         = ($catalog_id) ? self::_count_catalog($catalog_id) : self::get_server_counts(0);
        $counts         = array_merge(self::getUserRepository()->getStatistics(), $counts);
        $counts['tags'] = ($catalog_id) ? 0 : self::_count_tags();

        $counts['formatted_size'] = Ui::format_bytes((int) $counts['size'], 2, 2);

        $hours = floor((int) $counts['time'] / 3600);
        $days  = (int) floor($hours / 24);
        $hours %= 24;

        $time_text = $days . ' ';
        $time_text .= nT_('day', 'days', $days);
        $time_text .= sprintf(', %d ', $hours);
        $time_text .= nT_('hour', 'hours', $hours);

        $counts['time_text'] = $time_text;

        return $counts;
    }

    /**
     * get_update_info
     *
     * return the counts from user_data or update_info to speed up responses
     */
    public static function get_update_info(string $key, int $user_id): int
    {
        return self::getCatalogCounter()->getStoredCount($key, $user_id);
    }

    /**
     * get_uploads_sql
     */
    public static function get_uploads_sql(string $type, int $user_id = 0): string
    {
        $sql = '';
        if ($type == 'album') {
            $where_sql = ($user_id > 0)
                ? "WHERE `artist`.`user` = '" . $user_id . "' OR `song`.`user_upload` = '" . $user_id . "'"
                : 'WHERE `artist`.`user` IS NOT NULL OR `song`.`user_upload` IS NOT NULL';
        } else {
            $column = ($type == 'song')
                ? 'user_upload'
                : 'user';
            $where_sql = ($user_id > 0)
                ? sprintf('WHERE `%s`.`%s` = \'', $type, $column) . $user_id . "'"
                : sprintf('WHERE `%s`.`%s` IS NOT NULL', $type, $column);
        }
        //debug_event(self::class, 'get_uploads_sql ' . $sql, 5);

        return match ($type) {
            'song' => 'SELECT `song`.`id` AS `id` FROM `song` ' . $where_sql,
            'album' => 'SELECT DISTINCT `album`.`id` AS `id` FROM `album` LEFT JOIN `artist` on `album`.`album_artist` = `artist`.`id` LEFT JOIN `song` on `album`.`id` = `song`.`album` ' . $where_sql,
            'artist' => 'SELECT DISTINCT `id` FROM `artist` ' . $where_sql,
            default => $sql,
        };
    }

    /**
     * Get filter_user sql filter;
     */
    public static function get_user_filter(string $type, int $user_id): string
    {
        $system = ($user_id <= 0);
        switch ($type) {
            case 'album_disk':
            case 'album':
            case 'folder':
            case 'live_stream':
            case 'podcast_episode':
            case 'podcast':
            case 'song':
            case 'video':
                $sql = ($system)
                    ? sprintf(' `%s`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) ', $type)
                    : sprintf(' `%s`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) ', $type, $user_id);
                break;
            case 'artist':
                $sql = ($system)
                    ? sprintf(' `artist`.`id` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'%s\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $type)
                    : sprintf(' `artist`.`id` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'%s\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $type, $user_id);
                break;
            case 'song_artist':
            case 'song_album':
                $type = str_replace('song_', '', $type);
                $sql  = ($system)
                    ? sprintf(' `song`.`%s` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'%s\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $type, $type)
                    : sprintf(' `song`.`%s` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'%s\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $type, $type, $user_id);
                break;
            case 'album_artist':
                $sql = ($system)
                    ? sprintf(' `album`.`%s` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'%s\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $type, $type)
                    : sprintf(' `album`.`%s` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'%s\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $type, $type, $user_id);
                break;
            case 'label':
                $sql = ($system)
                    ? ' `label`.`id` IN (SELECT `label` FROM `label_asso` LEFT JOIN `artist` ON `label_asso`.`artist` = `artist`.`id` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = \'artist\' AND `catalog_map`.`object_id` = `artist`.`id` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'artist\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `label_asso`.`label`) '
                    : sprintf(' `label`.`id` IN (SELECT `label` FROM `label_asso` LEFT JOIN `artist` ON `label_asso`.`artist` = `artist`.`id` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = \'artist\' AND `catalog_map`.`object_id` = `artist`.`id` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'artist\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `label_asso`.`label`) ', $user_id);
                break;
            case 'playlist':
                $sql = ($system)
                    ? ' `playlist`.`id` IN (SELECT `playlist` FROM `playlist_data` LEFT JOIN `song` ON `playlist_data`.`object_id` = `song`.`id` AND `playlist_data`.`object_type` = \'song\' LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = \'song\' AND `catalog_map`.`object_id` = `song`.`id` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'song\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `playlist_data`.`playlist`) '
                    : sprintf(' `playlist`.`id` IN (SELECT `playlist` FROM `playlist_data` LEFT JOIN `song` ON `playlist_data`.`object_id` = `song`.`id` AND `playlist_data`.`object_type` = \'song\' LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = \'song\' AND `catalog_map`.`object_id` = `song`.`id` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'song\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `playlist_data`.`playlist`) ', $user_id);
                break;
            case 'share':
                $sql = ($system)
                    ? ' `share`.`object_id` IN (SELECT `share`.`object_id` FROM `share` LEFT JOIN `catalog_map` ON `share`.`object_type` = `catalog_map`.`object_type` AND `share`.`object_id` = `catalog_map`.`object_id` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `share`.`object_id`, `share`.`object_type`) '
                    : sprintf(' `share`.`object_id` IN (SELECT `share`.`object_id` FROM `share` LEFT JOIN `catalog_map` ON `share`.`object_type` = `catalog_map`.`object_type` AND `share`.`object_id` = `catalog_map`.`object_id` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `share`.`object_id`, `share`.`object_type`) ', $user_id);
                break;
            case 'mood':
                $sql = ($system)
                    ? ' `mood`.`id` IN (SELECT `mood_id` FROM `mood_map` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = `mood_map`.`object_type` AND `catalog_map`.`object_id` = `mood_map`.`object_id` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `mood_map`.`mood_id`) '
                    : sprintf(' `mood`.`id` IN (SELECT `mood_id` FROM `mood_map` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = `mood_map`.`object_type` AND `catalog_map`.`object_id` = `mood_map`.`object_id` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `mood_map`.`mood_id`) ', $user_id);
                break;
            case 'mood_map':
                $sql = ($system)
                    ? ' `mood_map`.`mood_id` IN (SELECT `mood_id` FROM `mood_map` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = `mood_map`.`object_type` AND `catalog_map`.`object_id` = `mood_map`.`object_id` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `mood_map`.`mood_id`) '
                    : sprintf(' `mood_map`.`mood_id` IN (SELECT `mood_id` FROM `mood_map` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = `mood_map`.`object_type` AND `catalog_map`.`object_id` = `mood_map`.`object_id` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `mood_map`.`mood_id`) ', $user_id);
                break;
            case 'tag':
                $sql = ($system)
                    ? ' `tag`.`id` IN (SELECT `tag_id` FROM `tag_map` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = `tag_map`.`object_type` AND `catalog_map`.`object_id` = `tag_map`.`object_id` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `tag_map`.`tag_id`) '
                    : sprintf(' `tag`.`id` IN (SELECT `tag_id` FROM `tag_map` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = `tag_map`.`object_type` AND `catalog_map`.`object_id` = `tag_map`.`object_id` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `tag_map`.`tag_id`) ', $user_id);
                break;
            case 'tag_map':
                $sql = ($system)
                    ? ' `tag_map`.`tag_id` IN (SELECT `tag_id` FROM `tag_map` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = `tag_map`.`object_type` AND `catalog_map`.`object_id` = `tag_map`.`object_id` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `tag_map`.`tag_id`) '
                    : sprintf(' `tag_map`.`tag_id` IN (SELECT `tag_id` FROM `tag_map` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = `tag_map`.`object_type` AND `catalog_map`.`object_id` = `tag_map`.`object_id` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `tag_map`.`tag_id`) ', $user_id);
                break;
            case 'object_count_album_disk':
                // enum('album', 'album_disk', 'artist', 'catalog', 'tag', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'search', 'song', 'user', 'video')
                $sql = ($system)
                    ? ' `album_disk`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) '
                    : sprintf(' `album_disk`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) ', $user_id);
                break;
            case 'object_count_album':
            case 'object_count_artist':
            case 'object_count_catalog':
            case 'object_count_genre':
            case 'object_count_live_stream':
            case 'object_count_playlist':
            case 'object_count_podcast_episode':
            case 'object_count_podcast':
            case 'object_count_song':
            case 'object_count_video':
                $type = str_replace('object_count_', '', $type);
                $sql  = ($system)
                    ? sprintf(' `object_count`.`object_id` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'%s\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $type)
                    : sprintf(' `object_count`.`object_id` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'%s\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $type, $user_id);
                break;
            case 'rating_album_disk':
                // enum('album', 'album_disk', 'artist', 'catalog', 'tag', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'search', 'song', 'user', 'video')
                $sql = ($system)
                    ? ' `rating`.`object_id` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'album_disk\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) '
                    : sprintf(' `rating`.`object_id` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'album_disk\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $user_id);
                break;
            case 'rating_album':
            case 'rating_artist':
            case 'rating_live_stream':
            case 'rating_podcast_episode':
            case 'rating_podcast':
            case 'rating_song':
            case 'rating_stream':
            case 'rating_video':
                $type = str_replace('rating_', '', $type);
                $sql  = ($system)
                    ? sprintf(' `rating`.`object_id` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'%s\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $type)
                    : sprintf(' `rating`.`object_id` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'%s\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $type, $user_id);
                break;
            case 'user_flag_album_disk':
                $sql = ($system)
                    ? ' `user_flag`.`object_id` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'album_disk\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) '
                    : sprintf(' `user_flag`.`object_id` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'album_disk\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $user_id);
                break;
            case 'user_flag_album':
            case 'user_flag_artist':
            case 'user_flag_podcast_episode':
            case 'user_flag_song':
            case 'user_flag_video':
                $type = str_replace('user_flag_', '', $type);
                $sql  = ($system)
                    ? sprintf(' `user_flag`.`object_id` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'%s\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $type)
                    : sprintf(' `user_flag`.`object_id` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'%s\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $type, $user_id);
                break;
            case 'rating_playlist':
                $sql = ($system)
                    ? ' `rating`.`object_id` IN (SELECT DISTINCT(`playlist`.`id`) FROM `playlist` LEFT JOIN `playlist_data` ON `playlist_data`.`playlist` = `playlist`.`id` LEFT JOIN `catalog_map` ON `playlist_data`.`object_id` = `catalog_map`.`object_id` AND `playlist_data`.`object_type` = \'song\' LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `playlist`.`id`) '
                    : sprintf(' `rating`.`object_id` IN (SELECT DISTINCT(`playlist`.`id`) FROM `playlist` LEFT JOIN `playlist_data` ON `playlist_data`.`playlist` = `playlist`.`id` LEFT JOIN `catalog_map` ON `playlist_data`.`object_id` = `catalog_map`.`object_id` AND `playlist_data`.`object_type` = \'song\' LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `playlist`.`id`) ', $user_id);
                break;
            case 'user_flag_playlist':
                $sql = ($system)
                    ? ' `user_flag`.`object_id` IN (SELECT DISTINCT(`playlist`.`id`) FROM `playlist` LEFT JOIN `playlist_data` ON `playlist_data`.`playlist` = `playlist`.`id` LEFT JOIN `catalog_map` ON `playlist_data`.`object_id` = `catalog_map`.`object_id` AND `playlist_data`.`object_type` = \'song\' LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `playlist`.`id`) '
                    : sprintf(' `user_flag`.`object_id` IN (SELECT DISTINCT(`playlist`.`id`) FROM `playlist` LEFT JOIN `playlist_data` ON `playlist_data`.`playlist` = `playlist`.`id` LEFT JOIN `catalog_map` ON `playlist_data`.`object_id` = `catalog_map`.`object_id` AND `playlist_data`.`object_type` = \'song\' LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `playlist`.`id`) ', $user_id);
                break;
            case 'catalog':
                $sql = ($system)
                    ? ' `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) '
                    : sprintf(' `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) ', $user_id);
                break;
            case 'catalog_map':
                $sql = ($system)
                    ? ' `catalog_map`.`catalog_id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1) '
                    : sprintf(' `catalog_map`.`catalog_id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) ', $user_id);
                break;
            default:
                debug_event(self::class, 'ERROR get_user_filter: ' . $type . ' not valid', 1);
                $sql = '';
        }

        return $sql;
    }

    /**
     * get_videos
     * @param int[]|null $catalogs
     * @return Video[]
     */
    public static function get_videos(?array $catalogs = null): array
    {
        if (!$catalogs) {
            $catalogs = self::get_catalogs('video');
        }

        $results = [];
        foreach ($catalogs as $catalog_id) {
            $catalog = self::create_from_id($catalog_id);
            if ($catalog === null) {
                break;
            }

            $video_ids = $catalog->get_video_ids();
            foreach ($video_ids as $video_id) {
                $results[] = new Video($video_id);
            }
        }

        return $results;
    }

    /**
     * get_videos_count
     */
    public static function get_videos_count(?int $catalog_id = 0): int
    {
        return self::getCatalogCounter()->countVideos((int) $catalog_id);
    }

    /**
     * Get last update for catalogs.
     * @param int[]|null $catalogs
     */
    public static function getLastUpdate(?array $catalogs = null): int
    {
        $last_update = 0;
        if ($catalogs === null) {
            $catalogs = self::get_all_catalogs();
        }

        foreach ($catalogs as $catalogid) {
            $catalog = self::create_from_id($catalogid);
            if ($catalog === null) {
                break;
            }

            if ($catalog->last_add > $last_update) {
                $last_update = $catalog->last_add;
            }

            if ($catalog->last_update > $last_update) {
                $last_update = $catalog->last_update;
            }

            if ($catalog->last_clean > $last_update) {
                $last_update = $catalog->last_clean;
            }
        }

        return $last_update;
    }

    /**
     * get_name
     * Returns the name of the catalog matching the given ID
     */
    public static function getName(int $catalog_id): string
    {
        return self::getCatalogRepository()->findName($catalog_id);
    }

    /**
     * has_access
     *
     * When filtering catalogs you shouldn't be able to play the files
     */
    public static function has_access(?int $catalog_id, int $user_id): bool
    {
        if ($catalog_id === null || !AmpConfig::get('catalog_filter')) {
            return true;
        }

        return self::getCatalogFilterRepository()->hasAccess($catalog_id, $user_id);
    }

    public static function has_children(string $name, ?int $catalog_id = 0, ?int $parent_id = null): bool
    {
        $folder = ($name === '/')
            ? new Folder(-1)
            : self::getFolderRepository()->getByName($name, $catalog_id, $parent_id);

        return ($folder instanceof Folder && $folder->isNew() === false)
            ? $folder->has_children($name)
            : false;
    }

    /**
     * Check if a file is an audio.
     */
    public static function is_audio_file(string $file): bool
    {
        $ignore_pattern = AmpConfig::get('catalog_ignore_pattern');
        $ignore_check   = !($ignore_pattern) || preg_match("/(" . $ignore_pattern . ")/i", $file) === 0;
        $file_pattern   = AmpConfig::get('catalog_file_pattern');
        $pattern        = "/\.(" . $file_pattern . ")$/i";

        return ($ignore_check && preg_match($pattern, $file));
    }

    /**
     * Check if a file is a playlist.
     */
    public static function is_playlist_file(string $file): bool
    {
        $ignore_pattern   = AmpConfig::get('catalog_ignore_pattern');
        $ignore_check     = !($ignore_pattern) || preg_match("/(" . $ignore_pattern . ")/i", $file) === 0;
        $playlist_pattern = "/\.(" . AmpConfig::get('catalog_playlist_pattern') . ")$/i";

        return ($ignore_check && preg_match($playlist_pattern, $file));
    }

    /**
     * Check if a file is a video.
     */
    public static function is_video_file(string $file): bool
    {
        $ignore_pattern = AmpConfig::get('catalog_ignore_pattern');
        $ignore_check   = !($ignore_pattern) || preg_match("/(" . $ignore_pattern . ")/i", $file) === 0;
        $video_pattern  = "/\.(" . AmpConfig::get('catalog_video_pattern') . ")$/i";

        return ($ignore_check && preg_match($video_pattern, $file));
    }

    /**
     * Migrate an object associated catalog to a new object
     */
    public static function migrate_map(string $object_type, int $old_object_id, int $new_object_id): bool
    {
        return self::getCatalogMapRepository()->migrate($object_type, $old_object_id, $new_object_id);
    }

    public static function normalize_year(int|string|null $year): int
    {
        if (empty($year)) {
            return 0;
        }

        $year = (strlen((string) $year) > 4)
            ? (int) substr((string) $year, -4, 4)
            : (int) ($year);

        if ($year < 0 || $year > 9999) {
            return 0;
        }

        return $year;
    }

    /**
     * process_action
     * @param null|int[] $catalogs
     * @param null|array<string, bool> $options
     * @noinspection PhpMissingBreakStatementInspection
     */
    public static function process_action(string $action, ?array $catalogs = null, ?array $options = null): void
    {
        if (empty($options)) {
            $options = ['gather_art' => false, 'parse_playlist' => false];
        }

        // make sure parse_playlist is set
        if ($action == 'import_to_catalog') {
            $options['parse_playlist'] = true;
        }

        $catalog = null;

        switch ($action) {
            case 'add_to_all_catalogs':
                $catalogs = self::get_catalogs();
                // Intentional break fall-through
            case 'add_to_catalog':
            case 'import_to_catalog':
                $options['gather_art'] = true;
                $catalog_media_types   = [];
                if ($catalogs) {
                    foreach ($catalogs as $catalog_id) {
                        self::withCatalogLock($catalog_id, function () use ($catalog_id, $options, &$catalog_media_types): void {
                            $catalog = self::create_from_id($catalog_id);
                            if ($catalog !== null && $catalog->add_to_catalog($options)) {
                                $catalog_media_types[] = $catalog->gather_types;
                            }
                        });
                    }

                    if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
                        echo AmpError::display('catalog_add');
                    }

                    foreach ($catalog_media_types as $catalog_media_type) {
                        if ($catalog_media_type == 'music') {
                            self::clean_empty_albums();
                            Album::update_album_artist();
                        }

                        self::update_catalog_map($catalog_media_type);
                    }
                }

                if (in_array('music', $catalog_media_types)) {
                    Artist::update_table_counts();
                    Album::update_table_counts();
                }
                break;
            case 'update_all_catalogs':
                $catalogs = self::get_catalogs();
                // Intentional break fall-through
            case 'update_catalog':
                if ($catalogs) {
                    foreach ($catalogs as $catalog_id) {
                        self::withCatalogLock($catalog_id, function () use ($catalog_id): void {
                            $catalog = self::create_from_id($catalog_id);
                            $catalog?->verify_catalog();
                        });
                    }
                }
                break;
            case 'full_service':
                if (!$catalogs) {
                    $catalogs = self::get_catalogs();
                }

                /* This runs the clean/verify/add in that order */
                $catalog_media_types = [];
                foreach ($catalogs as $catalog_id) {
                    self::withCatalogLock($catalog_id, function () use ($catalog_id, &$catalog_media_types): void {
                        $catalog = self::create_from_id($catalog_id);
                        if ($catalog !== null) {
                            if ($catalog->clean_catalog() < 0 && !in_array($catalog->gather_types, $catalog_media_types)) {
                                $catalog_media_types[] = $catalog->gather_types;
                            }

                            $catalog->verify_catalog();
                            if ($catalog->add_to_catalog() && !in_array($catalog->gather_types, $catalog_media_types)) {
                                $catalog_media_types[] = $catalog->gather_types;
                            }
                        }
                    });
                }

                foreach ($catalog_media_types as $catalog_media_type) {
                    if ($catalog_media_type == 'music') {
                        self::clean_empty_albums();
                        Album::update_album_artist();
                    }

                    self::update_catalog_map($catalog_media_type);
                }
                break;
            case 'clean_all_catalogs':
                $catalogs = self::get_catalogs();
                // Intentional break fall-through
            case 'clean_catalog':
                if ($catalogs) {
                    $catalog_media_types = [];
                    foreach ($catalogs as $catalog_id) {
                        self::withCatalogLock($catalog_id, function () use ($catalog_id, &$catalog_media_types): void {
                            $catalog = self::create_from_id($catalog_id);
                            if ($catalog !== null && ($catalog->clean_catalog() < 0 && !in_array($catalog->gather_types, $catalog_media_types))) {
                                $catalog_media_types[] = $catalog->gather_types;
                            }
                        });
                    }
                    foreach ($catalog_media_types as $catalog_media_type) {
                        if ($catalog_media_type == 'music') {
                            self::clean_empty_albums();
                            Album::update_album_artist();
                        }

                        self::update_catalog_map($catalog_media_type);
                    }

                    if (in_array('music', $catalog_media_types)) {
                        Artist::update_table_counts();
                        Album::update_table_counts();
                    }
                }
                break;
            case 'update_from':
                $catalog_id = 0;
                // clean deleted files
                $clean_path = (string) ($options['clean_path'] ?? '/');
                if (strlen($clean_path) && $clean_path != '/') {
                    $catalog_id = Catalog_local::get_from_path($clean_path);
                    if (is_int($catalog_id)) {
                        self::withCatalogLock($catalog_id, function () use ($catalog_id, $clean_path): void {
                            $catalog = self::create_from_id($catalog_id);
                            if ($catalog !== null && $catalog->catalog_type == 'local') {
                                switch ($catalog->gather_types) {
                                    case 'podcast':
                                        $type      = 'podcast_episode';
                                        $file_ids  = self::get_ids_from_folder($clean_path, $type);
                                        $className = Podcast_Episode::class;
                                        break;
                                    case 'video':
                                        $type      = 'video';
                                        $file_ids  = self::get_ids_from_folder($clean_path, $type);
                                        $className = Video::class;
                                        break;
                                    case 'music':
                                    default:
                                        $type      = 'song';
                                        $file_ids  = self::get_ids_from_folder($clean_path, $type);
                                        $className = Song::class;
                                        break;
                                }

                                $changed = 0;
                                foreach ($file_ids as $file_id) {
                                    $media = new $className($file_id);
                                    if ($media->file) {
                                        /** @var Catalog_local $catalog */
                                        if ($catalog->clean_file($media->file, $type)) {
                                            ++$changed;
                                        }
                                    }
                                }

                                if ($changed > 0) {
                                    if ($catalog->gather_types === 'music') {
                                        self::clean_empty_albums();
                                        Album::update_album_artist();
                                        Album::update_table_counts();
                                        Artist::update_table_counts();
                                    }
                                    self::update_catalog_map($catalog->gather_types);
                                }
                            }
                        });
                    }
                }

                // update_from_tags
                $update_path = (string) ($options['update_path'] ?? '/');
                if (strlen($update_path) && $update_path != '/' && is_int(Catalog_local::get_from_path($update_path))) {
                    $songs = self::get_ids_from_folder($update_path, 'song');
                    foreach ($songs as $song_id) {
                        self::update_single_item('song', $song_id);
                    }
                }

                // add new files
                $add_path = (string) ($options['add_path'] ?? '/');
                if (strlen($add_path) && $add_path != '/') {
                    $catalog_id = Catalog_local::get_from_path($add_path);
                    if (is_int($catalog_id)) {
                        self::withCatalogLock($catalog_id, function () use ($catalog_id, $add_path): void {
                            $catalog = self::create_from_id($catalog_id);
                            if ($catalog !== null && $catalog->add_to_catalog(['subdirectory' => $add_path])) {
                                self::update_catalog_map($catalog->gather_types);
                            }
                        });
                    }
                }

                if ($catalog_id < 1) {
                    AmpError::add(
                        'general',
                        T_("This subdirectory is not inside an existing Catalog. The update can not be processed.")
                    );
                }
                break;
            case 'gather_media_art':
                if (!$catalogs) {
                    $catalogs = self::get_catalogs();
                }

                // Iterate throughout the catalogs and gather as needed
                foreach ($catalogs as $catalog_id) {
                    $catalog = self::create_from_id($catalog_id);
                    if ($catalog !== null) {
                        echo (new CatalogProgressView(CatalogProgressTypeEnum::ART, $catalog_id))->render();
                        flush();
                        $catalog->gather_art();
                    }
                }
                break;
            case 'update_all_file_tags':
                $catalogs = self::get_catalogs();
                // Intentional break fall-through
            case 'update_file_tags':
                $write_tags = AmpConfig::get('write_tags', false);
                AmpConfig::set_by_array(
                    ['write_tags' => 'true'],
                    true
                );

                if (!empty($catalogs)) {
                    $songTagWriter = self::getSongTagWriter();
                    set_time_limit(0);
                    foreach ($catalogs as $catalog_id) {
                        $catalog = self::create_from_id($catalog_id);
                        if ($catalog !== null) {
                            $song_ids = $catalog->get_song_ids();
                            foreach ($song_ids as $song_id) {
                                $song = new Song($song_id);

                                $songTagWriter->write($song);
                            }
                        }
                    }
                }

                AmpConfig::set_by_array(
                    ['write_tags' => $write_tags],
                    true
                );
                break;
            case 'garbage_collect':
                if (!$catalogs) {
                    $catalogs = self::get_catalogs();
                }

                debug_event(self::class, 'Run Garbage collection', 5);
                self::getCatalogGarbageCollector()->collect();
                Session::garbage_collection();
                $catalog_media_types = [];
                if (!empty($catalogs)) {
                    foreach ($catalogs as $catalog_id) {
                        $catalog = self::create_from_id($catalog_id);
                        if ($catalog !== null && !in_array($catalog->gather_types, $catalog_media_types)) {
                            $catalog_media_types[] = (string) $catalog->gather_types;
                        }
                    }

                    foreach ($catalog_media_types as $catalog_media_type) {
                        if ($catalog_media_type == 'music') {
                            self::clean_empty_albums();
                            Album::update_album_artist();
                        }

                        self::update_catalog_map($catalog_media_type);
                        switch ($catalog_media_type) {
                            case 'podcast':
                                self::garbage_collect_mapping(['podcast_episode', 'podcast']);
                                break;
                            case 'video':
                                self::garbage_collect_mapping(['video']);
                                break;
                            case 'music':
                                self::garbage_collect_mapping(['album', 'artist', 'song']);
                                break;
                        }
                    }

                    self::getCatalogFilterRepository()->collectGarbage();
                    self::getUserRepository()->resetMissingCatalogFilterGroups();
                }
                break;
            case 'scan_all_catalog_folders':
                $catalogs = self::get_catalogs();
                // Intentional break fall-through
            case 'scan_catalog_folders':
                if ($catalogs) {
                    foreach ($catalogs as $catalog_id) {
                        $catalog = self::create_from_id($catalog_id);
                        $catalog?->scan_catalog_folders(null, true);
                    }

                    self::getFolderRepository()->update_folder_map();
                    self::getFolderRepository()->update_folder_counts();
                    self::getFolderRepository()->collectGarbage();

                    if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
                        echo AmpError::display('catalog_scan');
                    }
                }
        }
    }

    /**
     * reset_user_filter
     * reset a users's catalog filter to DEFAULT after deleting a filter group
     */
    public static function reset_user_filter(int $filter_id): void
    {
        self::getUserRepository()->resetCatalogFilterGroup($filter_id);
    }

    /**
     * set_update_info
     *
     * write the total_counts to update_info
     */
    public static function set_update_info(string $key, float|int $value): void
    {
        self::getCatalogCounter()->setStoredCount($key, $value);
    }

    /**
     * This is run on every individual element of the search before it is put together
     * It removes / and \ and windows-incompatible characters (if you use -w|--windows)
     */
    public static function sort_clean_name(int|string|null $string, string $return = '', bool $windowsCompat = false): string
    {
        if (empty($string)) {
            return $return;
        }

        $string = ($windowsCompat)
            ? str_replace(['/', '\\', ':', '*', '<', '>', '"', '|', '?'], '_', (string) $string)
            : str_replace(['/', '\\'], '_', (string) $string);

        return (string) $string;
    }

    /**
     * trim_featuring
     * Splits artists featuring from the string
     * @return string[]
     */
    public static function trim_featuring(string $string): array
    {
        $items = preg_split("/ feat\. /i", $string);
        if (!$items) {
            return [$string];
        }

        return array_map('trim', $items);
    }

    /**
     * trim_prefix
     * Splits the prefix from the string
     * @return array{string: string, prefix: ?string}
     */
    public static function trim_prefix(string $string, ?string $pattern = null): array
    {
        $prefix_pattern = $pattern ?? '/^(' . implode('\\s|', explode('|', (string) AmpConfig::get('catalog_prefix_pattern', 'The|An|A|Die|Das|Ein|Eine|Les|Le|La'))) . '\\s)(.*)/i';
        if (preg_match($prefix_pattern, $string, $matches)) {
            $string = trim($matches[2]);
            $prefix = trim($matches[1]);
        } else {
            $prefix = null;
        }

        return [
            'string' => $string,
            'prefix' => $prefix,
        ];
    }

    /**
     * trim_slashed_list
     * Split items by configurable delimiter
     * Return first item as string = default
     * Return all items as array if doTrim = false passed as optional parameter
     */
    public static function trim_slashed_list(?string $string): string
    {
        $delimiters = self::getConfigContainer()->get(ConfigurationKeyEnum::ADDITIONAL_DELIMITERS);
        $pattern    = '~[\s]?(' . $delimiters . ')[\s]?~';
        $items      = preg_split($pattern, (string) $string);
        if (!$items) {
            return (string) $string;
        }

        $items = array_map('trim', $items);

        return (string) $items[0];
    }

    /**
     * Update the catalog_map table depending on table type
     */
    public static function update_catalog_map(?string $media_type): void
    {
        $counter = self::getCatalogCounter();
        if ($media_type == 'music') {
            self::update_mapping('album');
            self::update_mapping('album_disk');
            self::update_mapping('artist');

            // every add and clean ends here, so this is where a catalog's stored totals catch up
            $counter->count(CountableTableEnum::SONG);
            $counter->count(CountableTableEnum::ALBUM);
            $counter->count(CountableTableEnum::ALBUM_DISK);
            $counter->count(CountableTableEnum::ARTIST);
        } elseif ($media_type == 'podcast') {
            self::update_mapping('podcast');
            self::update_mapping('podcast_episode');

            $counter->count(CountableTableEnum::PODCAST);
            $counter->count(CountableTableEnum::PODCAST_EPISODE);
        } elseif ($media_type == 'video') {
            self::update_mapping('video');

            $counter->count(CountableTableEnum::VIDEO);
        }
    }

    /**
     * update_enabled
     * sets the enabled flag
     */
    public static function update_enabled(bool $new_enabled, int $catalog_id): bool
    {
        /* Check them Rights! */
        if (!Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)) {
            return false;
        }

        return self::_update_item(CatalogFieldEnum::ENABLED, (($new_enabled) ? 1 : 0), $catalog_id);
    }

    /**
     * Update the catalog map for a single item
     */
    public static function update_map(int $catalog, string $object_type, int $object_id): void
    {
        debug_event(self::class, sprintf('update_map %s: {%s}', $object_type, $object_id), 5);
        if ($object_type == 'artist') {
            self::getCatalogMapRepository()->addForArtist($object_id);
        } elseif ($catalog > 0) {
            self::getCatalogMapRepository()->add($catalog, $object_type, $object_id);
        }
    }

    /**
     * Update the catalog mapping for various types
     */
    public static function update_mapping(string $table): void
    {
        // fill the data
        debug_event(self::class, 'Update mapping for table: ' . $table, 5);
        $mapTable = CatalogMapTableEnum::tryFrom($table);
        if ($mapTable === null) {
            return;
        }

        self::getCatalogMapRepository()->rebuild($mapTable);
    }

    /**
     * update_media_from_tags
     * This is a 'wrapper' function calls the update function for the media
     * type in question
     * @param list<string> $gather_types
     * @return array{
     *     change?: bool,
     *     element?: array<string, string>,
     *     maps?: bool,
     *     error?: bool
     * }
     */
    public static function update_media_from_tags(
        Song|Video|Podcast_Episode $media,
        array $gather_types = ['music'],
    ): array {
        $array   = [];
        $catalog = self::create_from_id($media->catalog);
        if ($catalog === null) {
            debug_event(self::class, 'update_media_from_tags: Error loading catalog ' . $media->catalog, 2);
            $array['error'] = true;

            return $array;
        }

        if ($catalog instanceof Catalog_remote || $catalog instanceof Catalog_subsonic) {
            // remote files are read using the API and not the file
            $results = $catalog->get_media_tags($media, $gather_types, '', '');
        } else {
            // retrieve the file if needed
            $streamConfiguration = $catalog->prepare_media($media);

            if ($streamConfiguration === null) {
                debug_event(self::class, 'update_media_from_tags: Error prepare_media ' . $catalog->catalog_type, 2);
                $array['error'] = true;

                return $array;
            }

            if (empty($streamConfiguration['file_path']) || Core::get_filesize(Core::conv_lc_file($streamConfiguration['file_path'])) == 0) {
                debug_event(self::class, 'update_media_from_tags: Error loading file ' . $streamConfiguration['file_path'], 2);
                $array['error'] = true;

                return $array;
            }

            // try and get the tags from your file
            debug_event(self::class, 'Reading tags from ' . $streamConfiguration['file_path'], 4);
            $extension = strtolower(pathinfo($streamConfiguration['file_path'], PATHINFO_EXTENSION));
            $results   = $catalog->get_media_tags($media, $gather_types, '', '');
            // for files without tags try to update from their file name instead
            if ($media->id && in_array($extension, ['wav', 'shn'])) {
                // match against your catalog 'Filename Pattern' and 'Folder Pattern'
                $patres  = VaInfo::parse_pattern($streamConfiguration['file_path'], $catalog->sort_pattern ?? '', $catalog->rename_pattern ?? '');
                $results = array_merge($results, $patres);
            }

            // remote catalogs should unlink the temp files if needed // TODO add other types of remote catalog
            if ($catalog instanceof Catalog_Seafile) {
                $catalog->clean_tmp_file($streamConfiguration['file_path']);
            }
        }

        return match (true) {
            $media instanceof Song => self::update_song_from_tags($results, $media),
            $media instanceof Video => self::update_video_from_tags($results, $media),
            $media instanceof Podcast_Episode => self::_update_podcast_episode_from_tags($results, $media),
        };
    }

    /**
     * update_settings
     * This function updates the basic setting of the catalog
     * @param array{
     *     name: string,
     *     rename_pattern: string,
     *     sort_pattern: string,
     *     catalog_id: int,
     * } $data
     */
    public static function update_settings(array $data): void
    {
        self::getCatalogRepository()->updateSettings(
            $data['catalog_id'],
            $data['name'],
            $data['rename_pattern'],
            $data['sort_pattern']
        );
    }

    /**
     * update_single_item
     * updates a single album,artist,song from the tag data and return the id. (if the artist/album changes it's updated)
     * this can be done by 75+
     * @return array{
     *     object_id: int,
     *     change: bool,
     * }
     */
    public static function update_single_item(string $type, int $object_id, bool $api = false, bool $multi_object = false): array
    {
        // Because single items are large numbers of things too
        set_time_limit(0);

        $return_id = $object_id;
        $songs     = [];
        $libitem   = 0;

        switch ($type) {
            case 'album':
                $libitem = new Album($object_id);
                $songs   = self::getSongRepository()->getByAlbum($object_id);
                break;
            case 'album_disk':
                $albumDisk = new AlbumDisk($object_id);
                $libitem   = new Album($albumDisk->album_id);
                $songs     = self::getSongRepository()->getByAlbumDisk($object_id);
                break;
            case 'artist':
                $libitem = new Artist($object_id);
                $songs   = self::getSongRepository()->getAllByArtist($object_id);
                break;
            case 'song':
                $songs[] = $object_id;
                break;
            case 'podcast_episode':
                $episode = new Podcast_Episode($object_id);
                self::update_media_from_tags($episode);

                return [
                    'object_id' => $object_id,
                    'change' => true
                ];
            case 'video':
                $video = new Video($object_id);
                self::update_media_from_tags($video);

                return [
                    'object_id' => $object_id,
                    'change' => true
                ];
        }

        if (!$api) {
            echo '<table class="tabledata striped-rows">' . "\n";
            echo '<thead><tr class="th-top">' . "\n";
            echo "<th>" . T_("Song") . "</th><th>" . T_("Status") . "</th>\n";
            echo "<tbody>\n";
        }

        $album   = false;
        $artist  = false;
        $tags    = false;
        $maps    = false;
        $changed = false;
        foreach ($songs as $song_id) {
            $diff = false;
            $song = new Song($song_id);
            if ($song->isNew()) {
                $info = ['error' => true];
            } else {
                $info = self::update_media_from_tags($song);

                $changed = $changed || (bool) ($info['change'] ?? false);
                $diff    = array_key_exists('element', $info) && $info['element'] !== [];
                $album   = ($album) || ($diff && array_key_exists('album', $info['element']));
                $artist  = ($artist) || ($diff && array_key_exists('artist', $info['element']));
                $tags    = ($tags) || ($diff && array_key_exists('tags', $info['element']));
                $maps    = ($maps) || ($diff && array_key_exists('maps', $info));
            }

            // don't echo useless info when using api
            if ($api) {
                continue;
            }

            $file = scrub_out($song->file);
            if (array_key_exists('change', $info) && $info['change']) {
                if ($diff && array_key_exists($type, $info['element'])) {
                    $element   = explode(' --> ', (string) $info['element'][$type]);
                    $return_id = (int) $element[1];
                }

                echo "<tr><td>" . $file . "</td><td>" . T_('Updated') . "</td></tr>\n";
            } elseif (array_key_exists('error', $info) && $info['error']) {
                echo '<tr><td>' . $file . "</td><td>" . T_('Error') . "</td></tr>\n";
            } else {
                echo '<tr><td>' . $file . "</td><td>" . T_('No Update Needed') . "</td></tr>\n";
            }

            flush();
        }

        if (!$api) {
            echo "</tbody></table>\n";
        }

        $albumRepository = self::getAlbumRepository();

        $artists = [];

        if ($libitem instanceof Album) {
            if (
                $artist || $album || $tags || $maps
            ) {
                // update the album artists
                foreach ($albumRepository->getArtistMap($libitem, 'album') as $albumArtist_id) {
                    $artists[] = $albumArtist_id;
                }

                // update the song artists too
                foreach ($albumRepository->getArtistMap($libitem, 'song') as $songArtist_id) {
                    if (!in_array($songArtist_id, $artists)) {
                        $artists[] = $songArtist_id;
                    }
                }
            }
        }

        // artist
        if ($libitem instanceof Artist) {
            $artists[] = $libitem->id;
            $tags      = self::getSongTags('artist', $libitem->id);
            Tag::update_tag_list(implode(',', $tags), 'artist', $libitem->id, true, from_file_tags: true);
            // update incorrect counts for album_disk
            if ($libitem->album_count > 0 && $libitem->album_disk_count == 0) {
                $maps = true;
            }
        }

        if ($type !== 'song') {
            if ($album || $artist || $maps) {
                // make sure all the counts are up to date for each artist after changes
                foreach ($artists as $artistId) {
                    Artist::update_artist_count($artistId);
                }

                // collect the garbage if you're not doing it as part of a big update
                if (!$multi_object) {
                    self::getArtistRepository()->collectGarbage();
                    self::getAlbumRepository()->collectGarbage();
                }
            }
        }

        return [
            'object_id' => $return_id,
            'change' => ($changed || $album || $artist || $maps || $tags),
        ];
    }

    public static function update_video_from_tags(array $results, Video $video): array
    {
        /* Setup the vars */
        $new_video                = new Video();
        $new_video->file          = $results['file'];
        $new_video->title         = $results['title'];
        $new_video->size          = $results['size'];
        $new_video->video_codec   = $results['video_codec'];
        $new_video->audio_codec   = $results['audio_codec'];
        $new_video->resolution_x  = $results['resolution_x'];
        $new_video->resolution_y  = $results['resolution_y'];
        $new_video->time          = $results['time'];
        $new_video->release_date  = $results['release_date'] ?? null;
        $new_video->bitrate       = $results['bitrate'];
        $new_video->mode          = $results['mode'];
        $new_video->channels      = $results['channels'];
        $new_video->display_x     = $results['display_x'];
        $new_video->display_y     = $results['display_y'];
        $new_video->frame_rate    = $results['frame_rate'];
        $new_video->video_bitrate = self::check_int($results['video_bitrate'], PHP_INT_MAX, 0);
        $tags                     = Tag::get_object_tags('video', $video->id);
        $video_tags               = [];
        if ($tags) {
            foreach ($tags as $tag) {
                $video_tags[] = $tag['name'];
            }
        }

        $new_video_tags = $results['genre'];

        $video_moods     = array_column(Mood::get_object_moods('video', $video->id), 'name');
        $new_video_moods = array_values(array_filter(array_map(trim(...), (array) ($results['mood'] ?? []))));

        $info = Video::compare_video_information($video, $new_video);
        if ($info['change']) {
            debug_event(self::class, $video->file . " : differences found, updating database", 5);

            Video::update_video($video->id, $new_video);

            if ($video_tags != $new_video_tags) {
                Tag::update_tag_list(implode(',', $new_video_tags), 'video', $video->id, true, from_file_tags: true);
            }

            Video::update_video_counts($video->id);
        } else {
            // always update the time when you update
            Video::update_utime($video->id);
        }

        if (
            array_udiff($video_moods, $new_video_moods, strcasecmp(...)) !== []
            || array_udiff($new_video_moods, $video_moods, strcasecmp(...)) !== []
        ) {
            if (Mood::update_mood_list(implode(',', $new_video_moods), 'video', $video->id, true, from_file_tags: true)) {
                $info['change'] = true;
            }
        }

        return $info;
    }

    /**
     * @deprecated
     */
    protected static function getAlbumRepository(): AlbumRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    protected static function getCatalogMapRepository(): CatalogMapRepositoryInterface
    {
        global $dic;

        return $dic->get(CatalogMapRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    protected static function getCatalogRepository(): CatalogRepositoryInterface
    {
        global $dic;

        return $dic->get(CatalogRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    protected static function getFolderRepository(): FolderRepositoryInterface
    {
        global $dic;

        return $dic->get(FolderRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    protected static function getPodcastEpisodeRepository(): PodcastEpisodeRepositoryInterface
    {
        global $dic;

        return $dic->get(PodcastEpisodeRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    protected static function getPodcastRepository(): PodcastRepositoryInterface
    {
        global $dic;

        return $dic->get(PodcastRepositoryInterface::class);
    }

    /**
     * @deprecated
     */
    protected static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }

    /**
     * Get all tags from all Songs from [type] (artist, album, ...)
     * @return string[]
     */
    protected static function getSongTags(string $type, int $object_id): array
    {
        return ($type == 'artist')
            ? self::getTagRepository()->getSongTagNamesByArtist($object_id)
            : self::getTagRepository()->getSongTagNamesByAlbum($object_id);
    }

    /**
     * @deprecated inject dependency
     */
    protected static function getVideoRepository(): VideoRepositoryInterface
    {
        global $dic;

        return $dic->get(VideoRepositoryInterface::class);
    }

    /**
     * update_song_from_tags
     * Updates the song info based on tags; this is called from a bunch of
     * different places and passes in a full fledged song object, so it's a
     * static function.
     * FIXME: This is an ugly mess, this really needs to be consolidated and cleaned up.
     */
    protected static function update_song_from_tags(array $results, Song $song): array
    {
        //debug_event(self::class, "update_song_from_tags results: " . print_r($results, true), 4);
        $filtered_results = self::filter_tag_results($results, $song);

        // info for the song table. This is all the primary file data that is song related
        $new_song               = new Song();
        $new_song->file         = $filtered_results['file'];
        $new_song->catalog      = $song->getCatalogId();
        $new_song->year         = $filtered_results['year'];
        $new_song->disk         = $filtered_results['disk'];
        $new_song->disksubtitle = $filtered_results['disksubtitle'];
        $new_song->isrc         = $filtered_results['isrc'];
        $new_song->title        = $filtered_results['title'];
        $new_song->bitrate      = $filtered_results['bitrate'];
        $new_song->rate         = $filtered_results['rate'];
        $new_song->mode         = $filtered_results['mode'];
        $new_song->channels     = $filtered_results['channels'];
        $new_song->size         = $filtered_results['size'];
        $new_song->time         = $filtered_results['time'];
        $new_song->track        = $filtered_results['track'];
        $new_song->mbid         = $filtered_results['mb_trackid'];
        $new_song->composer     = $filtered_results['composer'];
        $new_song->mime         = $filtered_results['mime']; // TODO store mime in Song (Generated from the filename on new Song())

        // info for the song_data table. used in Song::update_song
        $new_song->comment = $filtered_results['comment'];
        $new_song->lyrics  = $filtered_results['lyrics'];
        $new_song->license = $filtered_results['license_id'];
        $new_song->label   = $filtered_results['label'];
        // the labels themselves are created (and associated) further down, once the album id is known

        $new_song->language              = $filtered_results['language'];
        $new_song->replaygain_track_gain = $filtered_results['replaygain_track_gain'];
        $new_song->replaygain_track_peak = $filtered_results['replaygain_track_peak'];
        $new_song->replaygain_album_gain = $filtered_results['replaygain_album_gain'];
        $new_song->replaygain_album_peak = $filtered_results['replaygain_album_peak'];
        $new_song->r128_track_gain       = $filtered_results['r128_track_gain'];
        $new_song->r128_album_gain       = $filtered_results['r128_album_gain'];
        $new_song->bpm                   = $filtered_results['bpm'];

        // genre is used in the tag and tag_map tables
        $new_tag_array = [];
        if (!empty($filtered_results['genre'])) {
            // check if this thing has been renamed into something else
            foreach ($filtered_results['genre'] as $genreName) {
                $genre = Tag::construct_from_name($genreName);
                if ($genre->isNew() === false) {
                    if ($genre->is_hidden) {
                        foreach ($genre->get_merged_tags() as $merged_genre) {
                            $new_song->tags[] = $merged_genre;
                            $new_tag_array[]  = $merged_genre['name'];
                        }
                    } else {
                        $new_song->tags[] = [
                            'id' => $genre->getId(),
                            'name' => $genre->get_fullname() ?? $genreName,
                            'is_hidden' => 0,
                            'count' => 0,
                        ];
                        $new_tag_array[] = $genreName;
                    }
                } else {
                    $new_song->tags[] = [
                        'id' => 0,
                        'name' => $genreName,
                        'is_hidden' => 0,
                        'count' => 0,
                    ];
                    $new_tag_array[] = $genreName;
                }
            }
        }

        $song_tag_array = [];
        $tags           = Tag::get_object_tags('song', $song->id);
        if ($tags) {
            foreach ($tags as $genre) {
                $song->tags[] = [
                    'id' => $genre['id'],
                    'name' => $genre['name'],
                    'is_hidden' => $genre['is_hidden'],
                    'count' => 0,
                ];
                $song_tag_array[] = $genre['name'];
            }
        }

        // moods are not part of the song comparison, so they are decided against what the object already carries
        $song_mood_array = array_column(Mood::get_object_moods('song', $song->id), 'name');
        $new_mood_array  = array_values(array_filter(array_map(trim(...), $filtered_results['mood'] ?? [])));

        // info for the artist table.
        $artist           = $filtered_results['artist'];
        $artist_mbid      = $filtered_results['mb_artistid'];
        $albumartist_mbid = $filtered_results['mb_albumartistid'];
        // info for the album table. (year is also included in album)
        $album            = $filtered_results['album'];
        $album_mbid       = $filtered_results['mb_albumid'];
        $album_mbid_group = $filtered_results['mb_albumid_group'];
        $release_type     = $filtered_results['release_type'];
        $release_status   = $filtered_results['release_status'];
        $albumartist      = (empty($filtered_results['albumartist']))
            ? $song->get_album_artist_fullname()
            : self::_check_length($filtered_results['albumartist']);
        $albumartist ??= null;

        $original_year  = $filtered_results['original_year'];
        $barcode        = $filtered_results['barcode'];
        $catalog_number = $filtered_results['catalog_number'];
        $version        = $filtered_results['version'];

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
                    $albumartist !== null
                    && $albumartist !== ''
                    && $albumartist !== '0'
                )
                && $artist === $albumartist
            ) {
                $albumartist = $artists_array[0];
            }

            $artist = $artists_array[0];
        }

        // check whether this artist exists (and the album_artist)
        $is_upload_albumartist = ($song->albumartist && Artist::is_upload($song->albumartist));
        if ($is_upload_albumartist) {
            debug_event(self::class, $song->albumartist . ' : is an uploaded album artist', 4);
            $artists_array          = [];
            $albumartist_mbid_array = [];
            $new_song->albumartist  = $song->albumartist;
        } elseif ($albumartist || !empty($song->albumartist)) {
            $new_song->albumartist = (!$albumartist)
                ? $song->albumartist
                : Artist::check($albumartist, $albumartist_mbid);
        }

        if (!$new_song->albumartist) {
            $new_song->albumartist = $song->albumartist;
        }

        $is_upload_artist = $song->artist && Artist::is_upload($song->artist);
        if ($is_upload_artist) {
            debug_event(self::class, $song->artist . ' : is an uploaded song artist', 4);
            $artist_mbid_array = [];
            $new_song->artist  = $song->artist;
        } elseif (
            $new_song->albumartist
            && $albumartist
            && $albumartist === $artist
        ) {
            $new_song->artist = $new_song->albumartist;
        } else {
            $new_song->artist = Artist::check($artist, $artist_mbid);
        }

        if (!$new_song->artist) {
            $new_song->artist = $song->artist;
        }

        $is_orphan_album = $song->album && Album::is_orphan($song->album);

        // check whether this album exists
        $new_song->album = (!$is_orphan_album && ($is_upload_artist || $is_upload_albumartist))
            ? $song->album
            : Album::check($new_song->catalog, $album, $new_song->year, $album_mbid, $album_mbid_group, $new_song->albumartist, $release_type, $release_status, $original_year, $barcode, $catalog_number, $version);
        if ($new_song->album === 0) {
            $new_song->album = $song->album;
        }

        // Check album_disk and update if needed
        $new_song->album_disk = ($is_upload_artist || $is_upload_albumartist)
            ? $song->album_disk
            : AlbumDisk::check($new_song->album, $new_song->disk ?? 1, $new_song->catalog, $new_song->disksubtitle, $song->album_disk);
        if ($new_song->album_disk === 0) {
            $new_song->album_disk = $song->album_disk;
        }

        $albumRepository = self::getAlbumRepository();
        $new_song_album  = new Album($new_song->album);

        // get the artists / album_artists for this song
        $songArtist_array  = [$new_song->artist];
        $albumArtist_array = [$new_song->albumartist];
        // artist_map stores song and album against the artist_id
        $artist_map_song  = Artist::get_artist_map('song', $song->id);
        $artist_map_album = Artist::get_artist_map('album', $new_song->album);
        // album_map stores song_artist and album_artist against the album_id
        $album_map_songArtist  = $albumRepository->getArtistMap($new_song_album, 'song');
        $album_map_albumArtist = $albumRepository->getArtistMap($new_song_album, 'album');
        // don't update counts unless something changes
        $map_change    = false;
        $artist_change = false;
        $album_change  = false;

        // add song artists with a valid mbid to the list
        if (!empty($artist_mbid_array)) {
            foreach ($artist_mbid_array as $song_artist_mbid) {
                $songArtist_id = Artist::check_mbid($song_artist_mbid);
                if ($songArtist_id > 0 && !in_array($songArtist_id, $songArtist_array)) {
                    $songArtist_array[] = $songArtist_id;
                    Artist::add_artist_map($songArtist_id, 'song', $song->id);
                }
            }
        }

        // add song artists found by name to the list (Ignore artist names when we have the same amount of MBID's)
        if (!empty($artists_array) && count($artists_array) > count($artist_mbid_array)) {
            foreach ($artists_array as $artist_name) {
                $songArtist_id = (int) Artist::check($artist_name);
                if ($songArtist_id > 0 && !in_array($songArtist_id, $songArtist_array)) {
                    $songArtist_array[] = $songArtist_id;
                    Artist::add_artist_map($songArtist_id, 'song', $song->id);
                }
            }
        }

        // map every song artist we've found
        foreach ($songArtist_array as $songArtist_id) {
            if ((int) $songArtist_id > 0 && !in_array($songArtist_id, $artist_map_song)) {
                $artist_map_song[] = (int) $songArtist_id;
                Artist::add_artist_map($songArtist_id, 'song', $song->id);
                if ($song->played) {
                    Stats::duplicate_map('song', $song->id, 'artist', (int) $songArtist_id);
                }

                $map_change = true;
            }

            if ((int) $songArtist_id > 0 && !in_array($songArtist_id, $album_map_songArtist)) {
                $album_map_songArtist[] = (int) $songArtist_id;
                Album::add_album_map($new_song->album, 'song', (int) $songArtist_id);
                if ($song->played) {
                    Stats::duplicate_map('song', $song->id, 'artist', (int) $songArtist_id);
                }

                $map_change = true;
            }
        }

        // add album artists to the list
        if (!empty($albumartist_mbid_array)) {
            foreach ($albumartist_mbid_array as $album_artist_mbid) {
                $albumArtist_id = Artist::check_mbid($album_artist_mbid);
                if ($albumArtist_id > 0 && !in_array($albumArtist_id, $albumArtist_array)) {
                    $albumArtist_array[] = $albumArtist_id;
                    Artist::add_artist_map($albumArtist_id, 'album', $new_song->album);
                }
            }
        }

        // map every album artist we've found
        foreach ($albumArtist_array as $albumArtist_id) {
            if ((int) $albumArtist_id > 0 && !in_array($albumArtist_id, $artist_map_album)) {
                $artist_map_album[] = (int) $albumArtist_id;
                Artist::add_artist_map($albumArtist_id, 'album', $new_song->album);
                $map_change = true;
            }

            if ((int) $albumArtist_id > 0 && !in_array($albumArtist_id, $album_map_albumArtist)) {
                $album_map_albumArtist[] = (int) $albumArtist_id;
                Album::add_album_map($new_song->album, 'album', (int) $albumArtist_id);
                $map_change = true;
            }
        }

        // clean up the mapped things that are missing after the update
        foreach ($artist_map_song as $existing_map) {
            if (!in_array($existing_map, $songArtist_array)) {
                Artist::remove_artist_map($existing_map, 'song', $song->id);
                Album::check_album_map($song->album, 'song', $existing_map);
                if ($song->played) {
                    Stats::delete_map('song', $song->id, 'artist', $existing_map);
                }

                $map_change    = true;
                $artist_change = true;
            }
        }

        foreach ($artist_map_song as $existing_map) {
            $not_found = !in_array($existing_map, $songArtist_array);
            // remove album song map if song artist is changed OR album changes
            if ($not_found || ($song->album !== $new_song->album)) {
                Album::check_album_map($song->album, 'song', $existing_map);
                $map_change = true;
            }

            // only delete play count on song artist change
            if ($not_found && $song->played) {
                Stats::delete_map('song', $song->id, 'artist', $existing_map);
                $map_change = true;
            }
        }

        foreach ($artist_map_album as $existing_map) {
            if (!in_array($existing_map, $albumArtist_array)) {
                Artist::remove_artist_map($existing_map, 'album', $song->album);
                Album::check_album_map($song->album, 'album', $existing_map);
                $map_change    = true;
                $artist_change = true;
            }
        }

        foreach ($album_map_songArtist as $existing_map) {
            // check song maps in the album_map table (because this is per song we need to check the whole album)
            if (Album::check_album_map($song->album, 'song', $existing_map)) {
                $map_change = true;
            }
        }

        foreach ($album_map_albumArtist as $existing_map) {
            if (!in_array($existing_map, $albumArtist_array)) {
                Album::remove_album_map($song->album, 'album', $existing_map);
                $map_change   = true;
                $album_change = true;
            }
        }

        if ($artist_mbid) {
            $new_song->artist_mbid = $artist_mbid;
        }

        if ($album_mbid) {
            $new_song->album_mbid = $album_mbid;
        }

        if ($albumartist_mbid) {
            $new_song->albumartist_mbid = $albumartist_mbid;
        }

        /* Since we're doing a full compare make sure we fill the extended information */
        $song->fill_ext_info();
        $song->get_isrcs();

        $metadataManager = self::getMetadataManager();

        if ($metadataManager->isCustomMetadataEnabled()) {
            $ctags = self::_filterMetadata($song, $results);
            //debug_event(self::class, "get_clean_metadata " . print_r($ctags, true), 4);
            foreach ($ctags as $tag => $value) {
                try {
                    if (is_array($value)) {
                        $value = implode('; ', $value);
                    }
                    $metadataManager->updateOrAddMetadata($song, $tag, (string) $value);
                } catch (DatabaseException) {
                    debug_event(self::class, "Error: DatabaseException: " . $tag . ' ' . $value, 4);
                }
            }

            /** @var Metadata $metadata */
            foreach ($metadataManager->getMetadata($song) as $metadata) {
                $field = $metadata->getField();

                if ($field === null) {
                    debug_event(self::class, "delete metadata with unknown field ", 4);

                    $metadataManager->deleteMetadata($metadata);
                    continue;
                }

                $metaName = $field->getName();

                if (!array_key_exists($metaName, $ctags)) {
                    debug_event(self::class, "delete metadata field " . $metaName, 4);
                    $metadataManager->deleteMetadata($metadata);
                }
            }
        }

        // Duplicate arts if required
        if ($song->artist > 0 && $new_song->artist && $song->artist != $new_song->artist && !Art::has_db($new_song->artist, 'artist')) {
            Art::duplicate('artist', $song->artist, $new_song->artist);
        }

        if ($song->albumartist > 0 && $new_song->albumartist && $song->albumartist != $new_song->albumartist && $song->artist != $song->albumartist) {
            Art::duplicate('artist', $song->albumartist, $new_song->albumartist);
        }

        if ($song->album > 0 && $new_song->album && $song->album != $new_song->album && !Art::has_db($new_song->album, 'album')) {
            Art::duplicate('album', $song->album, $new_song->album);
        }

        // read the label from the file tags, not the database, or a label added to a file is never picked up
        $label_names = ($new_song->label && AmpConfig::get('label'))
            ? self::getLabelNameFilter()->filter(array_filter(array_map('trim', explode(';', $new_song->label))))
            : [];
        if ($label_names !== []) {
            $labelRepository = self::getLabelRepository();
            $now             = new DateTime();

            foreach ($label_names as $label_name) {
                $label_id = Label::helper($label_name) ?? $labelRepository->lookup($label_name);
                if ($label_id > 0) {
                    $label = $labelRepository->findById($label_id);
                    if ($label !== null) {
                        // the tag is read per song but describes the release, so it is recorded against the album
                        if ($new_song->album > 0) {
                            $labelRepository->addAlbumAssoc($label->id, $new_song->album, $now);
                        }

                        $artists = $label->get_artists();
                        if ($song->artist && !in_array($song->artist, $artists)) {
                            debug_event(self::class, sprintf('%s: adding association to %s', $song->artist, $label->name), 4);
                            $labelRepository->addArtistAssoc($label->id, $song->artist, $now);
                        }
                    }
                }
            }
        }

        $info = Song::compare_song_information($song, $new_song);
        if ($info['change']) {
            debug_event(self::class, $song->file . ' : differences found, updating database', 4);

            // Update the song and song_data table
            Song::update_song($song->id, $new_song);

            // If you've migrated from an existing artist you need to migrate their data
            if (($song->artist > 0 && $new_song->artist) && $song->artist != $new_song->artist) {
                self::_migrate('artist', $song->artist, $new_song->artist, $song->id, $song->catalog);
            }

            // albums changes also require album_disk changes
            if (($song->album > 0 && $new_song->album) && $song->album != $new_song->album) {
                self::_migrate('album', $song->album, $new_song->album, $song->id, $song->catalog);
                $song->album = $new_song->album;
            }
            if (($song->album_disk > 0 && $new_song->album_disk) && $song->album_disk != $new_song->album_disk) {
                self::_migrate('album_disk', $song->album_disk, $new_song->album_disk, $song->id, $song->catalog);
            }

            if (
                array_diff($song_tag_array, $new_tag_array) !== []
                || array_diff($new_tag_array, $song_tag_array) !== []
            ) {
                // we do still care if there are no tags on your object
                $tag_comma = ($new_tag_array === [])
                    ? ''
                    : implode(',', $new_tag_array);
                Tag::update_tag_list($tag_comma, 'song', $song->id, true, from_file_tags: true);
            }

            if ($song->license !== $new_song->license) {
                Song::update_license($new_song->license, $song->id);
            }

            if ($new_song->isrc && $song->isrc !== $new_song->isrc) {
                Song::update_song_map($new_song->isrc, 'isrc', $song->id);
            }
        } else {
            // always update the time when you update
            Song::update_utime($song->id);
        }

        // a file whose only edit is the mood tag is not a song change, so this is outside it; anything a user set by hand survives
        if (
            array_udiff($song_mood_array, $new_mood_array, strcasecmp(...)) !== []
            || array_udiff($new_mood_array, $song_mood_array, strcasecmp(...)) !== []
        ) {
            $mood_change = Mood::update_mood_list(implode(',', $new_mood_array), 'song', $song->id, true, from_file_tags: true);
            if ($mood_change) {
                $info['change'] = true;
            }
        }

        // If song rating tag exists and is well formed (array user=>rating), update it
        if ($song->id && array_key_exists('rating', $filtered_results) && is_array($filtered_results['rating']) && !empty($filtered_results['rating'])) {
            $o_rating = new Rating($song->id, 'song');
            // For each user's ratings, call the function
            foreach ($filtered_results['rating'] as $user => $rating) {
                debug_event(self::class, "Updating rating for Song " . $song->id . sprintf(' to %s for user %s', $rating, $user), 5);
                if (
                    (int) $user > 0
                    && $o_rating->get_user_rating((int) $user) != (int) $rating
                ) {
                    $o_rating->set_rating((int) $rating, (int) $user, false);
                }
            }
        }

        if ($artist_change) {
            debug_event(self::class, "delete bad artist_map rows", 5);
            self::getArtistRepository()->collectOrphanedMaps();
        }
        if ($album_change) {
            debug_event(self::class, "delete bad album_map rows", 5);
            self::getAlbumRepository()->collectOrphanedAlbumMaps();
        }

        if ($map_change) {
            $info['maps']   = true;
            $info['change'] = true;
        }

        if (
            self::updateAlbumTags($song->album)
            || $map_change
            || (
                $info['change'] && (
                    array_key_exists('album', $info['element'])
                    || array_key_exists('artist', $info['element'])
                    || array_key_exists('tags', $info['element'])
                )
            )
        ) {
            self::updateArtistTags($song->album, $song->id);
        }

        return $info;
    }

    /**
     * Updates album tags from given song's album id
     */
    protected static function updateAlbumTags(int $album_id): bool
    {
        $tags = self::getSongTags('album', $album_id);

        $change = Tag::update_tag_list(implode(',', $tags), 'album', $album_id, true, from_file_tags: true);

        // an album has no file of its own, so its moods are whatever its songs carry; dropping one from every song takes it off the album
        $moods       = self::getMoodRepository()->getSongMoodNamesByAlbum($album_id);
        $mood_change = Mood::update_mood_list(implode(',', $moods), 'album', $album_id, true, from_file_tags: true);

        return $change || $mood_change;
    }

    /**
     * Updates artist tags from given song's album id
     */
    protected static function updateArtistTags(int $album_id = 0, int $song_id = 0): void
    {
        $artists = array_unique(array_merge(Song::get_parent_array($album_id, 'album'), Song::get_parent_array($song_id)));
        foreach ($artists as $artist_id) {
            $tags = self::getSongTags('artist', $artist_id);
            Tag::update_tag_list(implode(',', $tags), 'artist', $artist_id, true, from_file_tags: true);

            // same as the album: derived from the songs mapped onto the artist, never from a file
            $moods = self::getMoodRepository()->getSongMoodNamesByArtist($artist_id);
            Mood::update_mood_list(implode(',', $moods), 'artist', $artist_id, true, from_file_tags: true);
        }
    }

    /**
     * check_length
     * Check to make sure the string fits into the database
     * max_length is the maximum number of characters that the (varchar) column can hold
     */
    private static function _check_length(?string $string = null, int $max_length = 255): string
    {
        $string = (string) $string;
        if (false !== $encoding = mb_detect_encoding($string, null, true)) {
            $string = trim(mb_substr($string, 0, $max_length, $encoding));
        } else {
            $string = trim(substr($string, 0, $max_length));
        }

        return $string;
    }

    /**
     * check_title
     * this checks to make sure something is
     * set on the title, if it isn't it looks at the
     * filename and tries to set the title based on that
     */
    private static function _check_title(string $title, string $file = ''): string
    {
        if (strlen(trim($title)) < 1) {
            $title = $file;
        }

        return $title;
    }

    /**
     * check_track
     * Check to make sure the track number fits into the database: max 32767, min -32767
     */
    private static function _check_track(string $track): int
    {
        $retval = ((int) $track > 32767 || (int) $track < -32767) ? (int) substr($track, -4, 4) : (int) $track;
        if ((int) $track !== $retval) {
            debug_event(self::class, "check_track: '{" . $track . "}' out of range. Changed into '{" . $retval . "}'", 4);
        }

        return $retval;
    }

    /**
     * count_catalog
     *
     * This returns the current number of songs, videos, podcast_episodes in this catalog.
     * @return array{items: int, time: int, size: int}
     */
    private static function _count_catalog(int $catalog_id): array
    {
        $catalog = self::create_from_id($catalog_id);
        if (!$catalog instanceof Catalog) {
            return [
                'items' => 0,
                'time' => 0,
                'size' => 0,
            ];
        }

        return self::getCatalogCounter()->countCatalog($catalog_id, $catalog->gather_types);
    }

    /**
     * count_tags
     *
     * This returns the current number of unique tags in the database.
     */
    private static function _count_tags(): int
    {
        return self::getCatalogCounter()->countTags();
    }

    /**
     * Get rid of all tags found in the libraryItem
     * @param array<string, scalar|scalar[]> $metadata
     * @return array<string, scalar|scalar[]>
     */
    private static function _filterMetadata(MetadataEnabledInterface $libraryItem, array $metadata): array
    {
        $metadataManager = self::getMetadataManager();

        // these fields seem to be ignored but should be removed
        $databaseFields = [
            'album' => null,
            'albumartist' => null,
            'art' => null,
            'artist' => null,
            'artists' => null,
            'audio_codec' => null,
            'barcode' => null,
            'bitrate' => null,
            'bpm' => null,
            'catalog_number' => null,
            'channels' => null,
            'comment' => null,
            'composer' => null,
            'description' => null,
            'disk' => null,
            'disksubtitle' => null,
            'display_x' => null,
            'display_y' => null,
            'dynamic range (r128)' => null,
            'encoding' => null,
            'file' => null,
            'frame_rate' => null,
            'genre' => null,
            'isrc' => null,
            'language' => null,
            'lyrics' => null,
            'mb_albumartistid_array' => null,
            'mb_albumartistid' => null,
            'mb_albumid_group' => null,
            'mb_albumid' => null,
            'mb_artistid_array' => null,
            'mb_artistid' => null,
            'mb_trackid' => null,
            'mime' => null,
            'mode' => null,
            'mood' => null,
            'original_name' => null,
            'original_year' => null,
            'originalyear' => null,
            'peak level (r128)' => null,
            'peak level (sample)' => null,
            'publisher' => null,
            'r128_album_gain' => null,
            'r128_track_gain' => null,
            'rate' => null,
            'rating' => null,
            'release_date' => null,
            'release_status' => null,
            'release_type' => null,
            'replaygain_album_gain' => null,
            'replaygain_album_peak' => null,
            'replaygain_track_gain' => null,
            'replaygain_track_peak' => null,
            'resolution_x' => null,
            'resolution_y' => null,
            'size' => null,
            'summary' => null,
            'time' => null,
            'title' => null,
            'track' => null,
            'version' => null,
            'video_bitrate' => null,
            'video_codec' => null,
            'volume level (r128)' => null,
            'volume level (replaygain)' => null,
            'year' => null,
        ];

        // Drops ignored keys from the metadata
        $tags = array_diff_key(
            $metadata,
            get_object_vars($libraryItem),
            array_flip($libraryItem->getIgnoredMetadataKeys()),
            $databaseFields,
            array_flip($metadataManager->getDisabledMetadataFields())
        );

        // filters empty metadata values
        return array_filter($tags);
    }

    /**
     * Migrate an object associate images to a new object
     */
    private static function _migrate(string $object_type, int $old_object_id, int $new_object_id, int $song_id, int $catalog_id): bool
    {
        if ($old_object_id != $new_object_id) {
            debug_event(self::class, sprintf('migrate %d %s: {%d} to {%d}', $song_id, $object_type, $old_object_id, $new_object_id), 4);

            Stats::migrate($object_type, $old_object_id, $new_object_id, $song_id);
            Useractivity::migrate($object_type, $old_object_id, $new_object_id);
            Recommendation::migrate($object_type, $old_object_id);
            self::getShareRepository()->migrate($object_type, $old_object_id, $new_object_id);
            self::getShoutRepository()->migrate($object_type, $old_object_id, $new_object_id);
            Tag::migrate($object_type, $old_object_id, $new_object_id);
            Mood::migrate($object_type, $old_object_id, $new_object_id);
            Userflag::migrate($object_type, $old_object_id, $new_object_id);
            Rating::migrate($object_type, $old_object_id, $new_object_id);
            Art::duplicate($object_type, $old_object_id, $new_object_id);
            Playlist::migrate($object_type, $old_object_id, $new_object_id);
            Label::migrate($object_type, $old_object_id, $new_object_id);
            if ($object_type === 'artist') {
                self::getWantedRepository()->migrateArtist($old_object_id, $new_object_id);
                Artist::update_artist_count($new_object_id);
                Artist::update_artist_count($old_object_id);
                self::update_map($catalog_id, 'artist', $new_object_id);
                self::garbage_collect_mapping(['artist']);
            }

            if ($object_type === 'album') {
                self::clean_empty_albums(false);
                Album::update_album_count($new_object_id);
                Album::update_album_count($old_object_id);
                self::update_map($catalog_id, 'album', $new_object_id);
                self::update_map($catalog_id, 'album_disk', $new_object_id);
                self::garbage_collect_mapping(['album', 'album_disk']);
            }

            self::getMetadataRepository()->migrate($object_type, $old_object_id, $new_object_id);
            self::getBookmarkRepository()->migrate($object_type, $old_object_id, $new_object_id);
            self::migrate_map($object_type, $old_object_id, $new_object_id);

            return true;
        }

        return false;
    }

    /**
     * _update_item
     * This is a private function that should only be called from within the catalog class.
     * It takes a field, value, catalog id and level. first and foremost it checks the level
     * against Core::get_global('user') to make sure they are allowed to update this record
     * it then updates it and sets $this->{$field} to the new value
     */
    private static function _update_item(CatalogFieldEnum $field, int|string $value, int $catalog_id): bool
    {
        /* Can't update to blank */
        if (trim((string) $value) === '') {
            return false;
        }

        return self::getCatalogRepository()->setField($catalog_id, $field, $value);
    }

    /**
     * @param array<string, mixed> $results
     * @return array{
     *     change: bool,
     *     element: array<string, string>,
     * }
     */
    private static function _update_podcast_episode_from_tags(array $results, Podcast_Episode $podcast_episode): array
    {
        self::getPodcastEpisodeRepository()->updateFromTags(
            $podcast_episode->id,
            (string) $podcast_episode->file,
            $results,
            time()
        );

        $array            = [];
        $array['change']  = true;
        $array['element'] = [];

        $array['element']['podcast_episode'] = '';

        return $array;
    }

    /**
     * @deprecated inject dependency
     */
    private static function getArtistRepository(): ArtistRepositoryInterface
    {
        global $dic;

        return $dic->get(ArtistRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getBookmarkRepository(): BookmarkRepositoryInterface
    {
        global $dic;

        return $dic->get(BookmarkRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getCatalogCounter(): CatalogCounterInterface
    {
        global $dic;

        return $dic->get(CatalogCounterInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getCatalogFilterRepository(): CatalogFilterRepositoryInterface
    {
        global $dic;

        return $dic->get(CatalogFilterRepositoryInterface::class);
    }

    /**
     * @deprecated
     */
    private static function getCatalogGarbageCollector(): CatalogGarbageCollectorInterface
    {
        global $dic;

        return $dic->get(CatalogGarbageCollectorInterface::class);
    }

    /**
     * @deprecated inject by constructor
     */
    private static function getConfigContainer(): ConfigContainerInterface
    {
        global $dic;

        return $dic->get(ConfigContainerInterface::class);
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
     * @deprecated
     */
    private static function getLabelRepository(): LabelRepositoryInterface
    {
        global $dic;

        return $dic->get(LabelRepositoryInterface::class);
    }

    /**
     * @deprecated
     */
    private static function getLicenseRepository(): LicenseRepositoryInterface
    {
        global $dic;

        return $dic->get(LicenseRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getLiveStreamRepository(): LiveStreamRepositoryInterface
    {
        global $dic;

        return $dic->get(LiveStreamRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getMetadataManager(): MetadataManagerInterface
    {
        global $dic;

        return $dic->get(MetadataManagerInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getMetadataRepository(): MetadataRepositoryInterface
    {
        global $dic;

        return $dic->get(MetadataRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getMoodRepository(): MoodRepositoryInterface
    {
        global $dic;

        return $dic->get(MoodRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getObjectNameRepository(): ObjectNameRepositoryInterface
    {
        global $dic;

        return $dic->get(ObjectNameRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getPlaylistRepository(): PlaylistRepositoryInterface
    {
        global $dic;

        return $dic->get(PlaylistRepositoryInterface::class);
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
     * @deprecated Inject by constructor
     */
    private static function getShoutRepository(): ShoutRepositoryInterface
    {
        global $dic;

        return $dic->get(ShoutRepositoryInterface::class);
    }

    /**
     * @deprecated
     */
    private static function getSongTagWriter(): SongTagWriterInterface
    {
        global $dic;

        return $dic->get(SongTagWriterInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getTagRepository(): TagRepositoryInterface
    {
        global $dic;

        return $dic->get(TagRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
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
     * Runs a catalog scan under an exclusive per-catalog lock, so an overlapping call can't race it.
     * A catalog already locked is skipped rather than waited for.
     */
    private static function withCatalogLock(int $catalog_id, callable $callback): void
    {
        $catalogRepository = self::getCatalogRepository();
        if (!$catalogRepository->tryAcquireProcessingLock($catalog_id)) {
            debug_event(self::class, sprintf('Catalog %d is already being processed, skipping', $catalog_id), 3);

            return;
        }

        try {
            $callback();
        } finally {
            $catalogRepository->releaseProcessingLock($catalog_id);
        }
    }

    /**
     * add_to_catalog
     * @param null|array<string, string|bool> $options
     */
    abstract public function add_to_catalog(?array $options = null, ?Interactor $interactor = null): int;

    /**
     * @param array<string, scalar> $metadata
     */
    public function addMetadata(MetadataEnabledInterface $libraryItem, array $metadata): void
    {
        $metadataManager = self::getMetadataManager();

        $tags = self::_filterMetadata($libraryItem, $metadata);

        foreach ($tags as $tag => $value) {
            $value = (is_array($value))
                ? implode(', ', $value)
                : (string) $value;
            $metadataManager->addMetadata($libraryItem, $tag, $value);
        }
    }

    /**
     * cache_catalog_proc
     */
    abstract public function cache_catalog_proc(): bool;

    /**
     * @return array<
     *     string,
     *     array{description: string, type: string, value?: scalar}
     * >
     */
    abstract public function catalog_fields(): array;

    /**
     * @return string[]
     */
    abstract public function check_catalog_proc(?Interactor $interactor = null): array;

    /**
     * clean_catalog
     *
     * Cleans the catalog of files that no longer exist.
     */
    public function clean_catalog(?Interactor $interactor = null): int
    {
        // We don't want to run out of time
        set_time_limit(0);

        $interactor?->info(
            'Starting clean on ' . $this->name,
            true
        );
        debug_event(self::class, 'Starting clean on ' . $this->name, 5);

        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            echo (new CatalogProgressView(CatalogProgressTypeEnum::CLEAN, $this->getId(), $this->name))->render();
            ob_flush();
            flush();
        }

        $dead_total = $this->clean_catalog_proc($interactor);
        if ($dead_total > 0) {
            self::clean_empty_albums();
            self::clean_duplicate_artists();
        }

        $interactor?->info(
            'clean finished, ' . $dead_total . ' removed from ' . $this->name,
            true
        );
        debug_event(self::class, 'clean finished, ' . $dead_total . ' removed from ' . $this->name, 4);

        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            Ui::show_box_top();
        }

        Ui::update_text(
            T_("Catalog Cleaned"),
            sprintf(nT_("%d file removed.", "%d files removed.", $dead_total), $dead_total)
        );
        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            Ui::show_box_bottom();
        }

        $this->update_last_clean();

        return $dead_total;
    }

    /**
     * clean_catalog_proc
     */
    abstract public function clean_catalog_proc(?Interactor $interactor = null): int;

    abstract public function count_scan_folders(?Interactor $interactor = null): void;

    /**
     * gather_art
     *
     * This runs through all of the albums and finds art for them
     * This runs through all of the needs art albums and tries
     * to find the art for them from the mp3s
     */
    public function gather_art(?array $songs = null, ?array $videos = null, ?Interactor $interactor = null): bool
    {
        // Make sure they've actually got methods
        $art_order       = AmpConfig::get_array('art_order');
        $gather_song_art = AmpConfig::get('gather_song_art', false);
        $db_art_first    = ($art_order[0] == 'db');
        if (count($art_order) === 0) {
            $interactor?->info(
                'art_order not set, self::gather_art aborting',
                true
            );
            debug_event(self::class, 'art_order not set, self::gather_art aborting', 3);

            return false;
        }

        // Prevent the script from timing out
        set_time_limit(0);

        $search_count = 0;
        $searches     = [];
        if ($songs === null) {
            $searches['album']    = $this->get_album_ids('art');
            $searches['artist']   = $this->get_artist_ids('art');
            $searches['playlist'] = $this->get_playlist_ids('art');
            if ($gather_song_art) {
                $searches['song'] = $this->get_song_ids();
            }
        } else {
            $searches['album']    = [];
            $searches['artist']   = [];
            $searches['playlist'] = [];
            if ($gather_song_art) {
                $searches['song'] = [];
            }

            foreach ($songs as $song_id) {
                $song = new Song($song_id);
                if ($song->isNew() === false) {
                    if (!in_array($song->album, $searches['album'])) {
                        $searches['album'][] = $song->album;
                    }

                    if (!in_array($song->artist, $searches['artist'])) {
                        $searches['artist'][] = $song->artist;
                    }

                    if ($gather_song_art) {
                        $searches['song'][] = $song->id;
                    }
                }
            }
        }

        $searches['video'] = $videos ?? $this->get_video_ids();
        $total_count       = (count($searches['album']) + count($searches['artist']) + count($searches['song'] ?? []) + count($searches['playlist']) + count($searches['video']));
        $interactor?->info(
            'gather_art found ' . $total_count . ' items missing art',
            true
        );
        debug_event(self::class, 'gather_art found ' . $total_count . ' items missing art', 4);
        // Run through items and get the art!
        foreach ($searches as $key => $values) {
            foreach ($values as $object_id) {
                self::gather_art_item($key, (int) $object_id, $db_art_first);

                // Stupid little cutesie thing
                ++$search_count;
                if (Ui::check_ticker()) {
                    Ui::update_text('count_art_' . $this->id, $search_count);
                }
            }
        }

        // One last time for good measure
        Ui::update_text('count_art_' . $this->id, $search_count);

        return true;
    }

    /**
     * gather_artist_info
     *
     * This runs through all of the artists and refreshes last.fm information
     * including similar artists that exist in your catalog.
     * @param int[] $artist_list
     */
    public function gather_artist_info(array $artist_list = []): void
    {
        // Prevent the script from timing out
        set_time_limit(0);

        $search_count = 0;
        debug_event(self::class, 'gather_artist_info found ' . count($artist_list) . ' items to check', 4);
        // Run through items and refresh info
        foreach ($artist_list as $object_id) {
            Recommendation::get_artist_info($object_id);
            Recommendation::get_artists_like($object_id);
            Artist::set_last_update($object_id);
            // get similar songs too
            $artistSongs = self::getSongRepository()->getAllByArtist($object_id);
            foreach ($artistSongs as $song_id) {
                Recommendation::get_songs_like($song_id);
            }

            // Stupid little cutesie thing
            ++$search_count;
            if (Ui::check_ticker()) {
                Ui::update_text('count_artist_' . $object_id, $search_count);
            }
        }

        // One last time for good measure
        Ui::update_text('count_artist_complete', $search_count);
    }

    /**
     * get_album_ids
     *
     * This returns an array of ids of albums that have songs in this
     * catalog's
     * @return int[]
     */
    public function get_album_ids(string $filter = ''): array
    {
        return array_reverse(
            self::getAlbumRepository()->getIdsByCatalog($this->id, $filter === 'art')
        );
    }

    /**
     * get_artist_ids
     *
     * This returns an array of ids of artist that have songs in this catalog
     * @return int[]
     */
    public function get_artist_ids(string $filter = ''): array
    {
        $repository = self::getArtistRepository();

        // 'info' feeds the similar-artist lookup and 'time' the musicbrainz recheck, so neither is scoped to this catalog
        $results = match ($filter) {
            'art' => $repository->getIdsByCatalog($this->id, true),
            'info' => $repository->getIdsMissingRecommendation(500),
            'time' => $repository->getIdsWithStaleMbid(),
            'count' => $repository->getIdsByCatalogAddedSince($this->id, $this->last_add),
            default => $repository->getIdsByCatalog($this->id),
        };

        return array_reverse($results);
    }

    /**
     * get_create_help
     */
    abstract public function get_create_help(): string;

    /**
     * get_description
     */
    abstract public function get_description(): string;

    /**
     * Get item f_add.
     */
    public function get_f_add(): string
    {
        return ($this->last_add !== 0)
            ? get_datetime($this->last_add)
            : T_('Never');
    }

    /**
     * Get item f_clean.
     */
    public function get_f_clean(): string
    {
        return ($this->last_clean)
            ? get_datetime((int) $this->last_clean)
            : T_('Never');
    }

    /**
     * get_f_info
     */
    abstract public function get_f_info(): string;

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
     * Get item f_update.
     */
    public function get_f_update(): string
    {
        return ($this->last_update !== 0)
            ? get_datetime($this->last_update)
            : T_('Never');
    }

    /**
     * get_fullname
     */
    public function get_fullname(): ?string
    {
        return $this->name;
    }

    /**
     * get_gather_types
     * @return string[]
     */
    public function get_gather_types(string $media_type = ''): array
    {
        $catalog_media_type = $this->gather_types;
        if (
            $catalog_media_type === null
            || $catalog_media_type === ''
            || $catalog_media_type === '0'
        ) {
            $catalog_media_type = "music";
        }

        $types = explode(',', $catalog_media_type);

        if ($media_type == "video") {
            $types = array_diff($types, ['music']);
        }

        if ($media_type == "music") {
            $types = array_diff($types, ['video']);
        }

        return $types;
    }

    /**
     * Get catalog info from table.
     * @return array{
     *     id?: int,
     *     name?: ?string,
     *     catalog_type?: ?string,
     *     last_update?: int,
     *     last_clean?: int,
     *     last_add?: int,
     *     enabled?: bool,
     *     rename_pattern?: ?string,
     *     sort_pattern?: ?string,
     *     gather_types?: ?string,
     *     catalog_id?: int,
     *     beetsdb?: string,
     *     uri?: string,
     *     server_uri?: string,
     *     path?: string,
     *     apikey?: string,
     *     api_key?: string,
     *     api_call_delay?: int|null,
     *     secret?: string,
     *     library_name?: string,
     *     authtoken?: string,
     *     getchunk?: bool,
     *     username?: string,
     *     password?: string
     * }
     */
    public function get_info(int $object_id, ?string $table_name = 'catalog'): array
    {
        /** @var array{id?: int, name?: ?string, catalog_type?: ?string, last_update?: int, last_clean?: int, last_add?: int, enabled?: bool, rename_pattern?: ?string, sort_pattern?: ?string, gather_types?: ?string} $info */
        $info = parent::get_info($object_id, $table_name);

        $type = CatalogTypeEnum::tryFrom($this->get_type());
        if ($type === null) {
            return $info;
        }

        $sub_type_id = self::getCatalogRepository()->findSubTypeId($type, $object_id);
        if ($sub_type_id !== null) {
            /** @var array{id?: int, catalog_id?: int, beetsdb?: string, uri?: string, server_uri?: string, path?: string, apikey?:string, secret?: string, authtoken?: string, getchunk?: bool, username?: string, password?: string, api_key?: string, api_call_delay?: int|null, secret?: string, library_name?: string} $info_type */
            $info_type = parent::get_info($sub_type_id, $type->tableName());
            foreach ($info_type as $key => $value) {
                if (!array_key_exists($key, $info) || !$info[$key]) {
                    $info[$key] = $value;
                }
            }
        }

        return $info;
    }

    /**
     * get_label_ids
     *
     * This returns an array of ids of labels
     * @return int[]
     */
    public function get_label_ids(string $filter): array
    {
        return self::getLabelRepository()->getIdsByCategory($filter);
    }

    /**
     * Get item link.
     */
    public function get_link(): string
    {
        // don't do anything if it's formatted
        if ($this->link === null) {
            $admin_path = AmpConfig::get_web_path('/admin');
            $this->link = $admin_path . '/catalog.php?action=show_customize_catalog&catalog_id=' . $this->id;
        }

        return $this->link ?? '';
    }

    /**
     * get_media_tags
     * @param string[] $gather_types
     * @return array<string, mixed>
     */
    public function get_media_tags(Podcast_Episode|Video|Song $media, array $gather_types, string $sort_pattern, string $rename_pattern, ?string $file_override = null): array
    {
        // Check for patterns
        if (!$sort_pattern || !$rename_pattern) {
            $sort_pattern   = $this->sort_pattern;
            $rename_pattern = $this->rename_pattern;
        }

        $media_file = $file_override ?? $media->file;

        if (!$media_file) {
            return [];
        }

        if ($this instanceof Catalog_remote || $this instanceof Catalog_subsonic) {
            return ($this->get_remote_tags($media) ?? []);
        }

        if ($this->catalog_type == 'local') {
            $vainfo = $this->getUtilityFactory()->createVaInfo(
                $media_file,
                $gather_types,
                '',
                '',
                (string) $sort_pattern,
                (string) $rename_pattern
            );
            try {
                $vainfo->gather_tags();
            } catch (Throwable $exception) {
                // a malformed tag raises an Error rather than an Exception, and the caller treats no tags as unreadable
                debug_event(self::class, 'Error ' . $exception->getMessage(), 1);

                return [];
            }

            $key = VaInfo::get_tag_type($vainfo->tags);

            return VaInfo::clean_tag_info($vainfo->tags, $key, $media_file);
        }

        return [];
    }

    /**
     * get_path
     */
    abstract public function get_path(): string;

    /**
     * get_playlist_ids
     *
     * This returns an array of ids of playlists holding media of this catalog
     * @return int[]
     */
    public function get_playlist_ids(string $filter = ''): array
    {
        return array_reverse(
            self::getPlaylistRepository()->getIdsByCatalog($this->id, $filter === 'art')
        );
    }

    /**
     * get_podcast_ids
     *
     * This returns an array of ids of podcasts in this catalog
     * @return int[]
     */
    public function get_podcast_ids(): array
    {
        return self::getPodcastRepository()->getIdsByCatalog($this->id);
    }

    /**
     * get_rel_path
     */
    abstract public function get_rel_path(string $file_path): string;

    /**
     * get_remote_tags
     * @return null|array<string, mixed>
     */
    public function get_remote_tags(Podcast_Episode|Video|Song $media): ?array
    {
        return null;
    }

    /**
     * get_song_ids
     *
     * Returns an array of song ids.
     * @return int[]
     */
    public function get_song_ids(): array
    {
        return self::getSongRepository()->getEnabledIdsByCatalog($this->id);
    }

    /**
     * get_songs
     *
     * Returns an array of song objects.
     * @return Song[]
     */
    public function get_songs(?int $offset = 0, ?int $limit = 0): array
    {
        $songs = self::getSongRepository()->getEnabledIdsByCatalog($this->id, $limit ?? 0, $offset ?? 0, true);

        Song::build_cache($songs);

        $results = [];
        foreach ($songs as $song_id) {
            $results[] = new Song($song_id);
        }

        return $results;
    }

    /**
     * get_type
     */
    abstract public function get_type(): string;

    /**
     * get_version
     */
    abstract public function get_version(): string;

    /**
     * get_video_ids
     *
     * This returns an array of ids of videos in this catalog
     * @return int[]
     */
    public function get_video_ids(): array
    {
        return self::getVideoRepository()->getIdsByCatalog($this->id);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRemoteStreamingUrl(Podcast_Episode|Video|Song $media, ?string $action = null): ?string
    {
        return null;
    }

    /**
     * install
     */
    abstract public function install(): bool;

    /**
     * is_installed
     */
    abstract public function is_installed(): bool;

    /**
     * Check if the catalog is ready to perform actions (configuration completed, ...)
     */
    public function isReady(): bool
    {
        return true;
    }

    abstract public function move_catalog_proc(string $new_path): bool;

    /**
     * Perform the last step process to make the catalog ready.
     */
    public function perform_ready(): void
    {
        // Do nothing.
    }

    /**
     * @return null|array{
     *     file_path: string,
     *     file_name: string,
     *     file_size: int,
     *     file_type: string
     * }
     */
    abstract public function prepare_media(Podcast_Episode|Video|Song $media): ?array;

    /**
     * scan_catalog_folders
     */
    abstract public function scan_catalog_folders(?Interactor $interactor = null, bool $skipCounts = false): int;

    /**
     * Show a message to make the catalog ready.
     */
    public function show_ready_process(): void
    {
        // Do nothing.
    }

    /**
     * Get the directory for this file from the catalog and the song info using the sort_pattern
     * takes into account various artists and the alphabet_prefix
     */
    public function sort_find_home(
        Song $song,
        string $sort_pattern,
        ?string $base = null,
        string $various_artist = "Various Artists",
        bool $windowsCompat = false,
    ): ?string {
        $home = '';
        if ($base) {
            $home = rtrim($base, "\/");
            $home = rtrim($home, "\\");
        }

        // Create the filename that this file should have
        $album_name = self::sort_clean_name($song->get_album_fullname(), '%A', $windowsCompat);
        $track      = self::sort_clean_name($song->track, '%T', $windowsCompat);
        if ((int) $track < 10) {
            $track = '0' . $track;
        }

        $title   = self::sort_clean_name($song->title, '%t', $windowsCompat);
        $year    = self::sort_clean_name($song->year, '%y', $windowsCompat);
        $comment = self::sort_clean_name($song->comment, '%c', $windowsCompat);

        // Do the various check
        $album = new Album($song->album);

        $song_artist_name  = self::sort_clean_name($song->get_parent_fullname(), '%a', $windowsCompat);
        $album_artist_name = (empty($album->get_parent_fullname()))
            ? $various_artist
            : self::sort_clean_name($album->get_parent_fullname(), '%a', $windowsCompat);
        $disk           = self::sort_clean_name($song->disk, '%d');
        $catalog_number = self::sort_clean_name($album->catalog_number, '%C');
        $barcode        = self::sort_clean_name($album->barcode, '%b');
        $original_year  = self::sort_clean_name($album->original_year, '%Y');
        $release_type   = self::sort_clean_name($album->release_type, '%r');
        $release_status = self::sort_clean_name($album->release_status, '%R');
        $version        = self::sort_clean_name($album->version, '%s');
        $genre          = ($album->get_tags() === [])
            ? '%b'
            : Tag::get_display($album->get_tags());

        // Replace everything we can find
        $replace_array = [
            '%a',
            '%B',
            '%A',
            '%t',
            '%T',
            '%y',
            '%Y',
            '%c',
            '%C',
            '%r',
            '%R',
            '%s',
            '%d',
            '%g',
            '%b',
        ];
        $content_array = [
            $song_artist_name,
            $album_artist_name,
            $album_name,
            $title,
            $track,
            $year,
            $original_year,
            $comment,
            $catalog_number,
            $release_type,
            $release_status,
            $version,
            $disk,
            $genre,
            $barcode,
        ];
        $sort_pattern = str_replace($replace_array, $content_array, $sort_pattern);

        // Remove non A-Z0-9 chars
        $sort_pattern = preg_replace("[^\\\/A-Za-z0-9\-\_\ \'\, \(\)]", "_", $sort_pattern);

        // Replace non-critical search patterns
        $post_replace_array = [
            '%Y',
            '%c',
            '%C',
            '%r',
            '%R',
            '%g',
            '%b',
            ' []',
            ' ()',
        ];
        $post_content_array = [
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ];
        $sort_pattern = str_replace($post_replace_array, $post_content_array, (string) $sort_pattern);

        $home .= '/' . $sort_pattern;

        // don't send a mismatched file!
        if (
            array_any(
                $replace_array,
                fn($replace_string) => str_contains($sort_pattern, $replace_string)
            )
        ) {
            return null;
        }

        return $home;
    }

    public function supportsType(string $type): bool
    {
        return $this->gather_types === $type;
    }

    /**
     * uninstall
     * This removes the remote catalog
     */
    public function uninstall(): void
    {
        $type = CatalogTypeEnum::tryFrom($this->get_type());
        if ($type === null) {
            return;
        }

        $catalogRepository = self::getCatalogRepository();
        $catalogRepository->deleteByType($type);
        $catalogRepository->dropSubTypeTable($type);
    }

    /**
     * update_from_external
     *
     * This runs through all of the labels and refreshes information from musicbrainz
     * @param int[] $object_list
     */
    public function update_from_external(array $object_list, string $object_type): void
    {
        // Prevent the script from timing out
        set_time_limit(0);

        debug_event(self::class, 'update_from_external found ' . count($object_list) . ' ' . $object_type . '\'s to check', 4);

        // only allow your primary external metadata source to update values
        $overwrites  = true;
        $meta_order  = array_map('strtolower', self::getConfigContainer()->getArray(ConfigurationKeyEnum::METADATA_ORDER));
        $plugin_list = Plugin::get_plugins(PluginTypeEnum::EXTERNAL_METADATA_RETRIEVER);
        $user        = (Core::get_global('user') instanceof User)
            ? Core::get_global('user')
            : new User(-1);

        $labelRepository = self::getLabelRepository();

        foreach ($meta_order as $plugin_name) {
            if (in_array($plugin_name, $plugin_list)) {
                // only load metadata plugins you enable
                $plugin = new Plugin($plugin_name);
                if (($plugin->_plugin instanceof AmpacheMusicBrainz || $plugin->_plugin instanceof AmpacheTheaudiodb) && $plugin->load($user) && $overwrites) {
                    debug_event(self::class, "get_external_metadata with: " . $plugin_name, 3);
                    // Run through items and refresh info
                    switch ($object_type) {
                        case 'label':
                            foreach ($object_list as $label_id) {
                                $label = $labelRepository->findById($label_id);
                                if ($label !== null) {
                                    $plugin->_plugin->get_external_metadata($label, 'label');
                                }
                            }
                            break;
                        case 'artist':
                            foreach ($object_list as $artist_id) {
                                $artist = new Artist($artist_id);
                                $plugin->_plugin->get_external_metadata($artist, 'artist');
                            }

                            $overwrites = false;
                            break;
                        case 'album':
                            foreach ($object_list as $artist_id) {
                                $album = new Album($artist_id);
                                $plugin->_plugin->get_external_metadata($album, 'album');
                            }

                            $overwrites = false;
                            break;
                        default:
                    }
                }
            }
        }
    }

    /**
     * update_last_add
     * updates the last_add of the catalog
     */
    public function update_last_add(): void
    {
        $date = time();
        self::_update_item(CatalogFieldEnum::LAST_ADD, $date, $this->id);
    }

    /**
     * update_last_clean
     * This updates the last clean information
     */
    public function update_last_clean(): void
    {
        $date = time();
        self::_update_item(CatalogFieldEnum::LAST_CLEAN, $date, $this->id);
    }

    /**
     * verify_catalog
     * This function verify the catalog
     */
    public function verify_catalog(): bool
    {
        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            echo (new CatalogProgressView(CatalogProgressTypeEnum::VERIFY, $this->getId(), $this->name))->render();
            ob_flush();
            flush();
        }

        $verified = $this->verify_catalog_proc();

        debug_event(self::class, 'verify finished, ' . $verified . ' updated', 4);

        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            Ui::show_box_top();
        }

        Ui::update_text(
            T_("Catalog Verified"),
            sprintf(nT_('%d file updated.', '%d files updated.', $verified), $verified)
        );
        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            Ui::show_box_bottom();
        }

        return true;
    }

    /**
     * verify_catalog_proc
     */
    abstract public function verify_catalog_proc(?int $limit = 0, ?Interactor $interactor = null): int;

    /**
     * _create_filecache
     *
     * This populates an array which is used to speed up the add process.
     */
    protected function _create_filecache(bool $lower = true): void
    {
        if (count($this->_filecache) == 0) {
            // Get _EVERYTHING_, songs first so a video sharing a path still wins the key
            $sources = [
                self::getSongRepository()->getFilesByCatalog($this->id),
                self::getVideoRepository()->getFilesByCatalog($this->id),
            ];
            foreach ($sources as $files) {
                foreach ($files as $object_id => $file) {
                    $key                    = ($lower) ? strtolower($file) : $file;
                    $this->_filecache[$key] = $object_id;
                }
            }
        }
    }

    /**
     * _create_filemapcache
     *
     * This populates an array which is used to speed up the scan process.
     */
    protected function _create_filemapcache(): void
    {
        if (count($this->_filecache) == 0) {
            // Get _EVERYTHING_
            $this->_filecache = self::getFolderRepository()->getByCatalogKeyedByPathName($this->id);
        }
    }

    /**
     * update_last_update
     * updates the last_update of the catalog
     */
    protected function update_last_update(int $date): void
    {
        self::_update_item(CatalogFieldEnum::LAST_UPDATE, $date, $this->id);
    }

    /**
     * @param array<string, scalar> $tags
     */
    protected function updateMetadata(MetadataEnabledInterface $item, array $tags): void
    {
        $metadataManager = self::getMetadataManager();

        $tags = self::_filterMetadata($item, $tags);

        foreach ($tags as $tag => $value) {
            $value = (is_array($value))
                ? implode(', ', $value)
                : (string) $value;
            try {
                $metadataManager->updateOrAddMetadata($item, $tag, $value);
            } catch (DatabaseException) {
                debug_event(self::class, "Error: DatabaseException: " . $tag . ' ' . $value, 4);
            }
        }
    }

    /**
     * get_newest_podcasts_ids
     *
     * This returns an array of ids of latest podcast episodes in this catalog
     * @return int[]
     */
    private function get_newest_podcasts_ids(int $count): array
    {
        return self::getPodcastEpisodeRepository()->getNewestIdsByCatalog($this->id, $count);
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getUtilityFactory(): UtilityFactoryInterface
    {
        global $dic;

        return $dic->get(UtilityFactoryInterface::class);
    }
}
