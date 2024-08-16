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
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Art\Collector\ArtCollectorInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Catalog\Catalog_beets;
use Ampache\Module\Catalog\Catalog_beetsremote;
use Ampache\Module\Catalog\Catalog_dropbox;
use Ampache\Module\Catalog\Catalog_local;
use Ampache\Module\Catalog\Catalog_remote;
use Ampache\Module\Catalog\Catalog_Seafile;
use Ampache\Module\Catalog\Catalog_subsonic;
use Ampache\Module\Catalog\CatalogLoader;
use Ampache\Module\Catalog\GarbageCollector\CatalogGarbageCollectorInterface;
use Ampache\Module\Metadata\MetadataEnabledInterface;
use Ampache\Module\Metadata\MetadataManagerInterface;
use Ampache\Module\Song\Tag\SongTagWriterInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\Recommendation;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Module\Util\VaInfo;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\MetadataRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Ampache\Repository\ShareRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Ampache\Repository\WantedRepositoryInterface;
use DateTime;
use Exception;
use Generator;
use PDOStatement;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;
use RegexIterator;

/**
 * This class handles all actual work in regards to the catalog,
 * it contains functions for creating/listing/updated the catalogs.
 */
abstract class Catalog extends database_object
{
    protected const DB_TABLENAME = 'catalog';

    /** @var array<string, class-string> */
    public const CATALOG_TYPES = [
        'beets' => Catalog_beets::class,
        'beetsremote' => Catalog_beetsremote::class,
        'dropbox' => Catalog_dropbox::class,
        'local' => Catalog_local::class,
        'remote' => Catalog_remote::class,
        'seafile' => Catalog_Seafile::class,
        'subsonic' => Catalog_subsonic::class,
    ];

    /** @var array{
     *  album: int,
     *  album_disk: int,
     *  album_group: int,
     *  artist: int,
     *  catalog: int,
     *  items: int,
     *  label: int,
     *  license: int,
     *  live_stream: int,
     *  playlist: int,
     *  podcast: int,
     *  podcast_episode: int,
     *  search: int,
     *  share: int,
     *  size: int,
     *  song: int,
     *  tag: int,
     *  time: int,
     *  user: int,
     *  video: int
     * }
     */
    private const SERVER_COUNTS = [
        'album' => 0,
        'album_disk' => 0,
        'album_group' => 0,
        'artist' => 0,
        'catalog' => 0,
        'items' => 0,
        'label' => 0,
        'license' => 0,
        'live_stream' => 0,
        'playlist' => 0,
        'podcast' => 0,
        'podcast_episode' => 0,
        'search' => 0,
        'share' => 0,
        'size' => 0,
        'song' => 0,
        'tag' => 0,
        'time' => 0,
        'user' => 0,
        'video' => 0,
    ];

    public int $id = 0;

    public ?string $name = null;

    public ?string $catalog_type = null;

    public int $last_update;

    public ?int $last_clean = null;

    public int $last_add;

    public bool $enabled;

    public ?string $rename_pattern = '';

    public ?string $sort_pattern = '';

    public ?string $gather_types = '';

    /** @var string $key */
    public $key;

    /** @var null|string $f_name */
    public $f_name;

    /** @var null|string $link */
    public $link;

    /** @var null|string $f_link */
    public $f_link;

    /** @var null|string $f_update */
    public $f_update;

    /** @var null|string $f_add */
    public $f_add;

    /** @var null|string $f_clean */
    public $f_clean;

    /**
     * alias for catalog paths, urls, etc etc
     * @var null|string $f_full_info
     */
    public $f_full_info;

    /**
     * alias for catalog paths, urls, etc etc
     * @var null|string $f_info
     */
    public $f_info;

    /**
     * This is a private var that's used during catalog builds
     * @var array $_playlists
     */
    protected $_playlists = [];

    /**
     * Cache all files in catalog for quick lookup during add
     * @var array $_filecache
     */
    protected $_filecache = [];

    /* Used in functions */

    /** @var array $albums */
    protected static $albums = [];

    /** @var array $artists */
    protected static $artists = [];

    /** @var array $tags */
    protected static $tags = [];

    /**
     * get_path
     */
    abstract public function get_path(): string;

    /**
     * get_type
     */
    abstract public function get_type(): string;

    /**
     * get_description
     */
    abstract public function get_description(): string;

    /**
     * get_version
     */
    abstract public function get_version(): string;

    /**
     * get_create_help
     */
    abstract public function get_create_help(): string;

    /**
     * is_installed
     */
    abstract public function is_installed(): bool;

    /**
     * install
     */
    abstract public function install(): bool;

    /**
     * @param array $options
     */
    abstract public function add_to_catalog($options = null): int;

    /**
     * verify_catalog_proc
     */
    abstract public function verify_catalog_proc(): int;

    /**
     * clean_catalog_proc
     */
    abstract public function clean_catalog_proc(): int;

    abstract public function check_catalog_proc(): array;

    /**
     * @param string $new_path
     */
    abstract public function move_catalog_proc($new_path): bool;

    /**
     * cache_catalog_proc
     */
    abstract public function cache_catalog_proc(): bool;

    abstract public function catalog_fields(): array;

    /**
     * @param string $file_path
     */
    abstract public function get_rel_path($file_path): string;

    /**
     * @param Song|Podcast_Episode|Video $media
     * @return null|array{
     *  file_path: string,
     *  file_name: string,
     *  file_size: int,
     *  file_type: string
     * }
     */
    abstract public function prepare_media($media): ?array;

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    /**
     * @param Song|Podcast_Episode|Video $media
     */
    public function getRemoteStreamingUrl($media): ?string
    {
        return null;
    }

    /**
     * Check if the catalog is ready to perform actions (configuration completed, ...)
     */
    public function isReady(): bool
    {
        return true;
    }

    /**
     * Show a message to make the catalog ready.
     */
    public function show_ready_process()
    {
        // Do nothing.
    }

    /**
     * Perform the last step process to make the catalog ready.
     */
    public function perform_ready()
    {
        // Do nothing.
    }

    /**
     * uninstall
     * This removes the remote catalog
     */
    public function uninstall(): void
    {
        $sql = "DELETE FROM `catalog` WHERE `catalog_type` = ?";
        Dba::query($sql, [$this->get_type()]);

        $sql = "DROP TABLE `catalog_" . $this->get_type() . "`";
        Dba::query($sql);
    }

    /**
     * Create a catalog from its id.
     * @param int $catalog_id
     */
    public static function create_from_id($catalog_id): ?Catalog
    {
        $sql        = 'SELECT `catalog_type` FROM `catalog` WHERE `id` = ?';
        $db_results = Dba::read($sql, [$catalog_id]);
        $row        = Dba::fetch_assoc($db_results);
        if ($row === []) {
            return null;
        }

        return self::create_catalog_type($row['catalog_type'], $catalog_id);
    }

    /**
     * create_catalog_type
     * This function attempts to create a catalog type
     * @param string $type
     * @param int $catalog_id
     */
    public static function create_catalog_type($type, $catalog_id = 0): ?Catalog
    {
        if (!$type) {
            return null;
        }

        $controller = self::CATALOG_TYPES[$type] ?? null;
        if ($controller === null) {
            /* Throw Error Here */
            debug_event(self::class, 'Unable to load ' . $type . ' catalog type', 2);

            return null;
        } // include

        /** @var Catalog_beets|Catalog_beetsremote|Catalog_dropbox|Catalog_local|Catalog_remote|Catalog_Seafile|Catalog_subsonic $controller */
        $catalog = ($catalog_id > 0)
            ? new $controller($catalog_id)
            : new $controller();


        // identify if it's actually enabled
        $sql        = 'SELECT `enabled` FROM `catalog` WHERE `id` = ?';
        $db_results = Dba::read($sql, [$catalog->id]);

        while ($results = Dba::fetch_assoc($db_results)) {
            $catalog->enabled = $results['enabled'];
        }

        return $catalog;
    }

    /**
     * get_catalog_filters
     * This returns the filters, sorting by name
     *
     * @return Generator<array{id: int, name: string}>
     */
    public static function get_catalog_filters(): Generator
    {
        // Now fetch the rest;
        $sql        = "SELECT `id`, `name` FROM `catalog_filter_group` ORDER BY `name` ";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            yield [
                'id' => (int) $row['id'],
                'name' => $row['name'],
            ];
        }
    }

    /**
     * get_name
     * Returns the name of the catalog matching the given ID
     */
    public static function getName(int $catalog_id): string
    {
        $sql        = "SELECT `name` FROM `catalog` WHERE `id` = ?";
        $db_results = Dba::read($sql, [$catalog_id]);
        $row        = Dba::fetch_assoc($db_results);

        return $row['name'] ?? '';
    }

    /**
     * get_fullname
     */
    public function get_fullname(): ?string
    {
        if ($this->f_name === null) {
            $this->f_name = $this->name;
        }

        return $this->f_name;
    }

    /**
     * Get item link.
     */
    public function get_link(): ?string
    {
        // don't do anything if it's formatted
        if ($this->link === null) {
            $web_path   = AmpConfig::get_web_path('/client');
            $this->link = $web_path . '/admin/catalog.php?action=show_customize_catalog&catalog_id=' . $this->id;
        }

        return $this->link;
    }

    /**
     * Get item f_link.
     */
    public function get_f_link(): ?string
    {
        // don't do anything if it's formatted
        if ($this->f_link === null) {
            $this->f_link = '<a href="' . $this->get_link() . '" title="' . scrub_out($this->get_fullname()) . '">' . scrub_out($this->get_fullname()) . '</a>';
        }

        return $this->f_link;
    }

    /**
     * filter_user_count
     * Returns the number of users assigned to a particular filter.
     */
    public static function filter_user_count(int $filter_id): int
    {
        $sql        = "SELECT COUNT(1) AS `count` FROM `user` WHERE `catalog_filter_group` = ?";
        $db_results = Dba::read($sql, [$filter_id]);
        $row        = Dba::fetch_assoc($db_results);

        return (int) $row['count'];
    }

    /**
     * filter_catalog_count
     * This returns the number of catalogs assigned to a filter.
     */
    public static function filter_catalog_count(int $filter_id): int
    {
        $sql        = "SELECT COUNT(1) AS `count` FROM `catalog_filter_group_map` WHERE `group_id` = ? AND `enabled` = 1";
        $db_results = Dba::read($sql, [$filter_id]);
        $row        = Dba::fetch_assoc($db_results);

        return (int) $row['count'];
    }

    /**
     * filter_name_exists
     * can specifiy an ID to ignore in this check, useful for filter names.
     */
    public static function filter_name_exists(string $filter_name, int $exclude_id = 0): bool
    {
        $params = [$filter_name];
        $sql    = "SELECT `id` FROM `catalog_filter_group` WHERE `name` = ?";
        if ($exclude_id >= 0) {
            $sql .= " AND `id` != ?";
            $params[] = $exclude_id;
        }

        $db_results = Dba::read($sql, $params);

        return Dba::num_rows($db_results) > 0;
    }

    /**
     * check_filter_catalog_enabled
     * Returns the `enabled` status of the filter/catalog combination
     */
    public static function check_filter_catalog_enabled(int $filter_id, int $catalog_id): bool
    {
        $sql        = "SELECT `enabled` FROM `catalog_filter_group_map` WHERE `group_id` = ? AND `catalog_id` = ? AND `enabled` = 1;";
        $db_results = Dba::read($sql, [$filter_id, $catalog_id]);

        return Dba::num_rows($db_results) > 0;
    }

    /**
     * add_catalog_filter_group_map
     * Adds appropriate rows when a catalog is added.
     */
    public static function add_catalog_filter_group_map(int $catalog_id): void
    {
        $results    = [];
        $sql        = "SELECT `id` FROM `catalog_filter_group` ORDER BY `id`";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        foreach ($results as $filter_id) {
            $enabled = ($filter_id == 0) ? 1 : 0; // always enable for the DEFAULT group
            $sql     = "INSERT IGNORE INTO `catalog_filter_group_map` (`group_id`, `catalog_id`, `enabled`) VALUES (?, ?, ?);";
            $params  = [$filter_id, $catalog_id, $enabled];
            Dba::write($sql, $params);
        }
    }

    /**
     * add_catalog_filter_group
     *
     * @param array<string, int> $catalogs
     *
     * @return PDOStatement|false
     */
    public static function add_catalog_filter_group(string $filter_name, array $catalogs)
    {
        // Create the filter
        Dba::write(
            'INSERT INTO `catalog_filter_group` (`name`) VALUES (?)',
            [$filter_name]
        );

        $filter_id = Dba::insert_id();

        // Fill in catalog_filter_group_map table for the new filter
        $results    = [];
        $sql        = "SELECT `id` FROM `catalog` ORDER BY `id`";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        $sql = "INSERT INTO `catalog_filter_group_map` (`group_id`, `catalog_id`, `enabled`) VALUES ";
        foreach ($results as $catalog_id) {
            $catalog_name = self::getName($catalog_id);
            $enabled      = $catalogs[$catalog_name];
            $sql .= sprintf('(%s, %d, %d),', $filter_id, $catalog_id, $enabled);
        }

        // Remove last comma to avoid SQL error
        $sql = substr($sql, 0, -1);

        return Dba::write($sql);
    }

    /**
     * edit_catalog_filter
     *
     * @param array<int, int> $catalogs
     */
    public static function edit_catalog_filter(int $filter_id, string $filter_name, array $catalogs): bool
    {
        // Modify the filter name
        $results = [];
        $sql     = "UPDATE `catalog_filter_group` SET `name` = ? WHERE `id` = ?;";
        Dba::write($sql, [$filter_name, $filter_id]);

        // Fill in catalog_filter_group_map table for the filter
        $sql        = "SELECT `id` FROM `catalog` ORDER BY `id`";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        foreach ($results as $catalog_id) {
            $sql        = "SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `group_id` = ? AND `catalog_id` = ?";
            $db_results = Dba::read($sql, [$filter_id, $catalog_id]);
            $enabled    = $catalogs[$catalog_id];
            $sql        = (Dba::num_rows($db_results) !== 0)
                ? "UPDATE `catalog_filter_group_map` SET `enabled` = ? WHERE `group_id` = ? AND `catalog_id` = ?"
                : "INSERT INTO `catalog_filter_group_map` SET `enabled` = ?, `group_id` = ?, `catalog_id` = ?";
            if (!Dba::write($sql, [$enabled, $filter_id, $catalog_id])) {
                return false;
            }
        }

        self::garbage_collect_filters();

        return true;
    }

    /**
     * delete_catalog_filter
     * @return PDOStatement|false
     */
    public static function delete_catalog_filter(int $filter_id)
    {
        if ($filter_id > 0) {
            $params = [$filter_id];
            $sql    = "DELETE FROM `catalog_filter_group` WHERE `id` = ?";
            if (Dba::write($sql, $params)) {
                $sql = "DELETE FROM `catalog_filter_group_map` WHERE `group_id` = ?";

                return Dba::write($sql, $params);
            }
        }

        return false;
    }

    /**
     * reset_user_filter
     * reset a users's catalog filter to DEFAULT after deleting a filter group
     */
    public static function reset_user_filter(int $filter_id): void
    {
        $sql = "UPDATE `user` SET `catalog_filter_group` = 0 WHERE `catalog_filter_group` = ?";
        Dba::write($sql, [$filter_id]);
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
     * Get catalog info from table.
     * @param int $object_id
     * @param string $table_name
     */
    public function get_info($object_id, $table_name = 'catalog'): array
    {
        $info = parent::get_info($object_id, $table_name);

        $table      = 'catalog_' . $this->get_type();
        $sql        = sprintf('SELECT `id` FROM `%s` WHERE `catalog_id` = ?', $table);
        $db_results = Dba::read($sql, [$object_id]);
        if ($results = Dba::fetch_assoc($db_results)) {
            $info_type = parent::get_info($results['id'], $table);
            foreach ($info_type as $key => $value) {
                if (!array_key_exists($key, $info) || !$info[$key]) {
                    $info[$key] = $value;
                }
            }
        }

        return $info;
    }

    /**
     * Get enable sql filter;
     * @param string $type
     * @param string $catalog_id
     */
    public static function get_enable_filter($type, $catalog_id): string
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
     * Get filter_user sql filter;
     * @param string $type
     * @param int $user_id
     */
    public static function get_user_filter($type, $user_id): string
    {
        switch ($type) {
            case "album":
            case "song":
            case "video":
            case "podcast":
            case "podcast_episode":
            case "live_stream":
                $sql = sprintf(' `%s`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) ', $type, $user_id);
                break;
            case "artist":
                $sql = sprintf(' `artist`.`id` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'%s\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $type, $user_id);
                break;
            case "song_artist":
            case "song_album":
                $type = str_replace('song_', '', (string) $type);
                $sql  = sprintf(' `song`.`%s` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'%s\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $type, $type, $user_id);
                break;
            case "album_disk":
                $sql = sprintf(' `%s`.`album_id` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'album_disk\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $type, $user_id);
                break;
            case "album_artist":
                $sql = sprintf(' `album`.`%s` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'%s\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $type, $type, $user_id);
                break;
            case "label":
                $sql = sprintf(' `label`.`id` IN (SELECT `label` FROM `label_asso` LEFT JOIN `artist` ON `label_asso`.`artist` = `artist`.`id` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = \'artist\' AND `catalog_map`.`object_id` = `artist`.`id` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'artist\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `label_asso`.`label`) ', $user_id);
                break;
            case "playlist":
                $sql = sprintf(' `playlist`.`id` IN (SELECT `playlist` FROM `playlist_data` LEFT JOIN `song` ON `playlist_data`.`object_id` = `song`.`id` AND `playlist_data`.`object_type` = \'song\' LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = \'song\' AND `catalog_map`.`object_id` = `song`.`id` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'song\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `playlist_data`.`playlist`) ', $user_id);
                break;
            case "share":
                $sql = sprintf(' `share`.`object_id` IN (SELECT `share`.`object_id` FROM `share` LEFT JOIN `catalog_map` ON `share`.`object_type` = `catalog_map`.`object_type` AND `share`.`object_id` = `catalog_map`.`object_id` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `share`.`object_id`, `share`.`object_type`) ', $user_id);
                break;
            case "tag":
                $sql = sprintf(' `tag`.`id` IN (SELECT `tag_id` FROM `tag_map` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = `tag_map`.`object_type` AND `catalog_map`.`object_id` = `tag_map`.`object_id` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `tag_map`.`tag_id`) ', $user_id);
                break;
            case "object_count_album_disk":
                // enum('album', 'album_disk', 'artist', 'catalog', 'tag', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'search', 'song', 'user', 'video')
                $sql = sprintf(' `object_count`.`object_id` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'album_disk\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $user_id);
                break;
            case "object_count_artist":
            case "object_count_album":
            case "object_count_song":
            case "object_count_playlist":
            case "object_count_genre":
            case "object_count_catalog":
            case "object_count_live_stream":
            case "object_count_video":
            case "object_count_podcast":
            case "object_count_podcast_episode":
                $type = str_replace('object_count_', '', (string) $type);
                $sql  = sprintf(' `object_count`.`object_id` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'%s\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $type, $user_id);
                break;
            case "rating_album_disk":
                // enum('album', 'album_disk', 'artist', 'catalog', 'tag', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'search', 'song', 'user', 'video')
                $sql = sprintf(' `rating`.`object_id` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'album_disk\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $user_id);
                break;
            case "rating_artist":
            case "rating_album":
            case "rating_song":
            case "rating_stream":
            case "rating_live_stream":
            case "rating_video":
            case "rating_podcast":
            case "rating_podcast_episode":
                $type = str_replace('rating_', '', (string) $type);
                $sql  = sprintf(' `rating`.`object_id` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'%s\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $type, $user_id);
                break;
            case "user_flag_album_disk":
                $sql = sprintf(' `user_flag`.`object_id` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'album_disk\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $user_id);
                break;
            case "user_flag_artist":
            case "user_flag_album":
            case "user_flag_song":
            case "user_flag_video":
            case "user_flag_podcast_episode":
                $type = str_replace('user_flag_', '', (string) $type);
                $sql  = sprintf(' `user_flag`.`object_id` IN (SELECT `catalog_map`.`object_id` FROM `catalog_map` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog_map`.`object_type` = \'%s\' AND `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `catalog_map`.`object_id`) ', $type, $user_id);
                break;
            case "rating_playlist":
                $sql = sprintf(' `rating`.`object_id` IN (SELECT DISTINCT(`playlist`.`id`) FROM `playlist` LEFT JOIN `playlist_data` ON `playlist_data`.`playlist` = `playlist`.`id` LEFT JOIN `catalog_map` ON `playlist_data`.`object_id` = `catalog_map`.`object_id` AND `playlist_data`.`object_type` = \'song\' LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `playlist`.`id`) ', $user_id);
                break;
            case "user_flag_playlist":
                $sql = sprintf(' `user_flag`.`object_id` IN (SELECT DISTINCT(`playlist`.`id`) FROM `playlist` LEFT JOIN `playlist_data` ON `playlist_data`.`playlist` = `playlist`.`id` LEFT JOIN `catalog_map` ON `playlist_data`.`object_id` = `catalog_map`.`object_id` AND `playlist_data`.`object_type` = \'song\' LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `playlist`.`id`) ', $user_id);
                break;
            case "catalog":
                $sql = sprintf(' `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = %d AND `catalog_filter_group_map`.`enabled`=1) ', $user_id);
                break;
            default:
                debug_event(self::class, 'ERROR get_user_filter: ' . $type . ' not valid', 1);
                $sql = "";
        }

        return $sql;
    }

    /**
     * _create_filecache
     *
     * This populates an array which is used to speed up the add process.
     */
    protected function _create_filecache(): void
    {
        if (count($this->_filecache) == 0) {
            // Get _EVERYTHING_
            $sql        = 'SELECT `id`, `file` FROM `song` WHERE `catalog` = ?';
            $db_results = Dba::read($sql, [$this->id]);

            // Populate the filecache
            while ($results = Dba::fetch_assoc($db_results)) {
                $this->_filecache[strtolower((string)$results['file'])] = $results['id'];
            }

            $sql        = 'SELECT `id`, `file` FROM `video` WHERE `catalog` = ?';
            $db_results = Dba::read($sql, [$this->id]);

            while ($results = Dba::fetch_assoc($db_results)) {
                $this->_filecache[strtolower((string)$results['file'])] = 'v_' . $results['id'];
            }
        }
    }

    /**
     * get_update_info
     *
     * return the counts from user_data or update_info to speed up responses
     */
    public static function get_update_info(string $key, int $user_id): int
    {
        $sql = ($user_id > 0)
            ? "SELECT `key`, `value` FROM `user_data` WHERE `key` = ? AND `user` = " . $user_id
            : "SELECT `key`, `value` FROM `update_info` WHERE `key` = ?";
        $db_results = Dba::read($sql, [$key]);
        $results    = Dba::fetch_assoc($db_results);

        return (int)($results['value'] ?? 0);
    }

    /**
     * set_update_info
     *
     * write the total_counts to update_info
     * @param string $key
     * @param int|float $value
     */
    public static function set_update_info($key, $value): void
    {
        Dba::write("REPLACE INTO `update_info` SET `key` = ?, `value` = ?;", [$key, $value]);
    }

    /**
     * update_enabled
     * sets the enabled flag
     * @param bool $new_enabled
     * @param int $catalog_id
     * @return PDOStatement|bool
     */
    public static function update_enabled($new_enabled, $catalog_id)
    {
        /* Check them Rights! */
        if (!Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)) {
            return false;
        }

        return self::_update_item('enabled', ($new_enabled ? 1 : 0), $catalog_id);
    }

    /**
     * _update_item
     * This is a private function that should only be called from within the catalog class.
     * It takes a field, value, catalog id and level. first and foremost it checks the level
     * against Core::get_global('user') to make sure they are allowed to update this record
     * it then updates it and sets $this->{$field} to the new value
     * @param string $field
     * @param string|int $value
     * @param int $catalog_id
     * @return PDOStatement|bool
     */
    private static function _update_item($field, $value, $catalog_id)
    {
        /* Can't update to blank */
        if (trim((string)$value) === '') {
            return false;
        }

        $sql = sprintf('UPDATE `catalog` SET `%s` = ? WHERE `id` = ?', $field);

        return Dba::write($sql, [$value, $catalog_id]);
    }

    /**
     * format
     *
     * This makes the object human-readable.
     */
    public function format(): void
    {
        $this->get_fullname();
        $this->get_link();
        $this->get_f_link();
        $this->f_update = $this->last_update !== 0 ? get_datetime((int)$this->last_update) : T_('Never');
        $this->f_add    = $this->last_add !== 0 ? get_datetime((int)$this->last_add) : T_('Never');
        $this->f_clean  = $this->last_clean ? get_datetime((int)$this->last_clean) : T_('Never');
    }

    /**
     * get_catalogs
     *
     * Pull all the current catalogs and return an array of ids of what you find
     * @param string $filter_type
     * @param int|null $user_id
     * @param bool $query
     * @return int[]
     *
     * @see CatalogLoader
     */
    public static function get_catalogs($filter_type = '', $user_id = null, $query = false): array
    {
        $params = [];
        $sql    = "SELECT `id` FROM `catalog` ";
        $join   = 'WHERE';
        if (!empty($filter_type)) {
            $sql .= $join . ' `gather_types` = ? ';
            $params[] = $filter_type;
            $join     = 'AND';
        }

        if (AmpConfig::get('catalog_disable')) {
            $sql .= $join . ' `enabled` = 1 ';
            $join = 'AND';
        }

        if (AmpConfig::get('catalog_filter')) {
            if ($user_id > 0) {
                $sql .= $join . self::get_user_filter('catalog', $user_id);
                $join = 'AND';
            }

            if ($user_id == -1) {
                $sql .= $join . ' `id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `enabled` = 1 AND `group_id` = 0) ';
            }
        }

        $sql .= "ORDER BY `name`;";
        //debug_event(self::class, 'get_catalogs ' . $sql . ' ' . print_r($params, true), 5);
        $db_results = Dba::read($sql, $params);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        if ($results === [] && $query) {
            return [0];
        }

        return $results;
    }

    /**
     * Run the cache_catalog_proc() on music catalogs.
     */
    public static function cache_catalogs(): void
    {
        $path   = (string)AmpConfig::get('cache_path', '');
        $target = (string)AmpConfig::get('cache_target', '');
        // need a destination and target filetype
        if (is_dir($path) && $target) {
            $catalogs = self::get_catalogs('music');
            foreach ($catalogs as $catalogid) {
                debug_event(self::class, 'cache_catalogs: ' . $catalogid, 5);
                $catalog = self::create_from_id($catalogid);
                if ($catalog === null) {
                    break;
                }

                $catalog->cache_catalog_proc();
            }

            $catalog_dirs = new RecursiveDirectoryIterator($path);
            $dir_files    = new RecursiveIteratorIterator($catalog_dirs);
            $cache_files  = new RegexIterator($dir_files, sprintf('/\.%s/i', $target));
            debug_event(self::class, 'cache_catalogs: cleaning old files', 5);
            foreach ($cache_files as $file) {
                $path    = pathinfo((string) $file);
                $song_id = $path['filename'];
                if (!Song::has_id($song_id)) {
                    unlink($file);
                    debug_event(self::class, 'cache_catalogs: removed {' . $file . '}', 4);
                }
            }
        }
    }

    /**
     * Get last catalogs update.
     * @param int[]|null $catalogs
     */
    public static function getLastUpdate($catalogs = null): int
    {
        $last_update = 0;
        if ($catalogs == null || !is_array($catalogs)) {
            $catalogs = self::get_catalogs();
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
     * get_stats
     *
     * This returns an hash with the #'s for the different
     * objects that are associated with this catalog. This is used
     * to build the stats box, it also calculates time.
     * @param int|null $catalog_id
     * @return array{
     *  tags: int,
     *  formatted_size: string,
     *  time_text: string,
     *  users: int,
     *  connected: int
     * }
     */
    public static function get_stats($catalog_id = 0): array
    {
        $counts         = ($catalog_id) ? self::count_catalog($catalog_id) : self::get_server_counts(0);
        $counts         = array_merge(self::getUserRepository()->getStatistics(), $counts);
        $counts['tags'] = ($catalog_id) ? 0 : self::count_tags();

        $counts['formatted_size'] = Ui::format_bytes($counts['size'], 2, 2);

        $hours = floor((int) $counts['time'] / 3600);
        $days  = (int)floor($hours / 24);
        $hours %= 24;

        $time_text = $days . ' ';
        $time_text .= nT_('day', 'days', $days);
        $time_text .= sprintf(', %d ', $hours);
        $time_text .= nT_('hour', 'hours', $hours);

        $counts['time_text'] = $time_text;

        return $counts;
    }

    /**
     * create
     *
     * This creates a new catalog entry and associate it to current instance
     * @param array $data
     */
    public static function create($data): int
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
        $classname = self::CATALOG_TYPES[$type] ?? null;
        if ($classname === null) {
            return $insert_id;
        }

        $sql = 'INSERT INTO `catalog` (`name`, `catalog_type`, `rename_pattern`, `sort_pattern`, `gather_types`) VALUES (?, ?, ?, ?, ?)';
        Dba::write($sql, [
            $name,
            $type,
            $rename_pattern,
            $sort_pattern,
            $gather_types,
        ]);

        $insert_id = Dba::insert_id();
        if (!$insert_id) {
            AmpError::add('general', T_('Failed to create the catalog, check the debug logs'));
            debug_event(self::class, 'Insert failed: ' . json_encode($data), 2);

            return 0;
        }

        self::clear_catalog_cache();

        /** @var Catalog_beets|Catalog_beetsremote|Catalog_dropbox|Catalog_local|Catalog_remote|Catalog_Seafile|Catalog_subsonic $classname */
        if (!$classname::create_type($insert_id, $data)) {
            $sql = 'DELETE FROM `catalog` WHERE `id` = ?';
            Dba::write($sql, [$insert_id]);
            $insert_id = 0;
        }

        return (int)$insert_id;
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
     * count_tags
     *
     * This returns the current number of unique tags in the database.
     */
    public static function count_tags(): int
    {
        $sql        = "SELECT COUNT(`id`) FROM `tag` WHERE `is_hidden` = 0;";
        $db_results = Dba::read($sql);
        $row        = Dba::fetch_row($db_results);

        return $row[0] ?? 0;
    }

    /**
     * has_access
     *
     * When filtering catalogs you shouldn't be able to play the files
     * @param int|null $catalog_id
     * @param int $user_id
     */
    public static function has_access($catalog_id, $user_id): bool
    {
        if ($catalog_id === null || !AmpConfig::get('catalog_filter')) {
            return true;
        }

        if ($user_id == -1) {
            // DEFAULT group only for System / Guest access
            $params = [$catalog_id];
            $sql    = "SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_id` = ? AND `enabled` = 1 AND `group_id` = 0;";
        } else {
            $params = [$catalog_id, $user_id];
            $sql    = "SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_id` = ? AND `enabled` = 1 AND `group_id` IN (SELECT `catalog_filter_group` FROM `user` WHERE `id` = ?);";
        }

        //debug_event(self::class, 'has_access ' . $sql . ' ' . print_r($params, true), 5);

        $db_results = Dba::read($sql, $params);

        return (bool) Dba::num_rows($db_results);
    }

    /**
     * get_server_counts
     *
     * This returns the current number of songs, videos, albums, artists, items, etc across all catalogs on the server
     * @param int $user_id
     * @return int[]
     */
    public static function get_server_counts($user_id): array
    {
        $results = self::SERVER_COUNTS;
        if ($user_id > 0) {
            $sql        = "SELECT `key`, `value` FROM `user_data` WHERE `user` = ?;";
            $db_results = Dba::read($sql, [$user_id]);
        } else {
            $sql        = "SELECT `key`, `value` FROM `update_info`;";
            $db_results = Dba::read($sql);
        }

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['key']] = (int)$row['value'];
        }

        return $results;
    }

    /**
     * count_table
     *
     * Count and/or Update a table count when adding/removing from the server
     */
    public static function count_table(string $table, ?int $catalog_id = 0, ?int $update_time = 0): int
    {
        $sql       = sprintf('SELECT COUNT(`id`) FROM `%s` ', $table);
        $params    = [];
        $where_sql = 'WHERE';
        if ($catalog_id > 0) {
            $sql .= $where_sql . " `catalog` = ? ";
            $params[]  = $catalog_id;
            $where_sql = 'AND';
        }

        if ($update_time > 0) {
            $sql .= $where_sql . " `update_time` <= ? ";
            $params[] = $update_time;
        }

        $sql = rtrim($sql, ';');
        //debug_event(self::class, 'count_table ' . $sql . ' ' . print_r($params, true), 5);
        $db_results = Dba::read($sql, $params);
        $row        = Dba::fetch_row($db_results);
        if ($row === []) {
            return 0;
        }

        if ($catalog_id === 0) {
            self::set_update_info($table, (int)$row[0]);
        }

        return (int)$row[0];
    }

    /**
     * count_catalog
     *
     * This returns the current number of songs, videos, podcast_episodes in this catalog.
     * @param int $catalog_id
     * @return int[]
     */
    public static function count_catalog($catalog_id): array
    {
        $catalog = self::create_from_id($catalog_id);
        $results = ['items' => 0, 'time' => 0, 'size' => 0];
        if ($catalog instanceof Catalog) {
            $where_sql = $catalog_id ? 'WHERE `catalog` = ?' : '';
            $params    = $catalog_id ? [$catalog_id] : [];

            $table = self::get_table_from_type($catalog->gather_types);
            if ($table == 'podcast_episode' && $catalog_id) {
                $where_sql = "WHERE `podcast` IN (SELECT `id` FROM `podcast` WHERE `catalog` = ?)";
            }

            $sql              = "SELECT COUNT(`id`) AS `items`, IFNULL(SUM(`time`), 0) AS `time`, IFNULL(SUM(`size`)/1024/1024, 0) AS `size` FROM `" . $table . "` " . $where_sql;
            $db_results       = Dba::read($sql, $params);
            $row              = Dba::fetch_assoc($db_results);
            $results['items'] = (int)($row['items'] ?? 0);
            $results['time']  = (int)($row['time'] ?? 0);
            $results['size']  = (int)($row['size'] ?? 0);
        }

        return $results;
    }

    /**
     * get_uploads_sql
     *
     * @param string $type
     * @param int $user_id
     */
    public static function get_uploads_sql($type, $user_id = 0): string
    {
        $sql    = '';
        $column = ($type == 'song')
            ? 'user_upload'
            : 'user';
        $table = ($type == 'album')
            ? 'artist'
            : $type;
        $where_sql = ($user_id > 0)
            ? sprintf('WHERE `%s`.`%s` = \'', $table, $column) . $user_id . "'"
            : sprintf('WHERE `%s`.`%s` IS NOT NULL', $table, $column);
        //debug_event(self::class, 'get_uploads_sql ' . $sql, 5);

        return match ($type) {
            'song' => 'SELECT `song`.`id` AS `id` FROM `song` ' . $where_sql,
            'album' => 'SELECT DISTINCT `album`.`id` AS `id` FROM `album` LEFT JOIN `artist` on `album`.`album_artist` = `artist`.`id` ' . $where_sql,
            'artist' => 'SELECT DISTINCT `id` FROM `artist` ' . $where_sql,
            default => $sql,
        };
    }

    /**
     * get_album_ids
     *
     * This returns an array of ids of albums that have songs in this
     * catalog's
     * @param string $filter
     * @return int[]
     */
    public function get_album_ids($filter = ''): array
    {
        $results = [];

        $sql = 'SELECT `album`.`id` FROM `album` WHERE `album`.`catalog` = ?';
        if ($filter === 'art') {
            $sql = "SELECT `album`.`id` FROM `album` LEFT JOIN `image` ON `album`.`id` = `image`.`object_id` AND `object_type` = 'album' WHERE `album`.`catalog` = ? AND `image`.`object_id` IS NULL";
        }

        $db_results = Dba::read($sql, [$this->id]);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return array_reverse($results);
    }

    /**
     * get_video_ids
     *
     * This returns an array of ids of videos in this catalog
     * @param string $type
     * @return int[]
     */
    public function get_video_ids($type = ''): array
    {
        $results = [];

        $sql = 'SELECT DISTINCT(`video`.`id`) AS `id` FROM `video` ';
        if (!empty($type)) {
            $sql .= 'JOIN `' . $type . '` ON `' . $type . '`.`id` = `video`.`id`';
        }

        $sql .= 'WHERE `video`.`catalog` = ?';
        $db_results = Dba::read($sql, [$this->id]);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     *
     * @param int[]|null $catalogs
     * @param string $type
     * @return Video[]
     */
    public static function get_videos($catalogs = null, $type = ''): array
    {
        if (!$catalogs) {
            $catalogs = self::get_catalogs();
        }

        $results = [];
        foreach ($catalogs as $catalog_id) {
            $catalog = self::create_from_id($catalog_id);
            if ($catalog === null) {
                break;
            }

            $video_ids = $catalog->get_video_ids($type);
            foreach ($video_ids as $video_id) {
                $results[] = Video::create_from_id($video_id);
            }
        }

        return $results;
    }

    /**
     * get_videos_count
     */
    public static function get_videos_count(?int $catalog_id = 0): int
    {
        $sql = "SELECT COUNT(`video`.`id`) AS `video_cnt` FROM `video` ";

        if ($catalog_id) {
            $sql .= "WHERE `video`.`catalog` = `" . $catalog_id . "`";
        }

        $db_results = Dba::read($sql);
        $row        = Dba::fetch_row($db_results);

        return $row[0] ?? 0;
    }

    /**
     * get_name_array
     *
     * Get each array of fullname's for a object type
     * @param array $objects
     * @param string $table
     * @param string $sort
     * @param string $order
     * @return array
     */
    public static function get_name_array($objects, $table, $sort = '', $order = 'ASC'): array
    {
        switch ($table) {
            case 'album':
            case 'artist':
                $sql = sprintf('SELECT DISTINCT `%s`.`id`, LTRIM(CONCAT(COALESCE(`%s`.`prefix`, \'\'), \' \', `%s`.`name`)) AS `name` FROM `%s` WHERE `id` IN (', $table, $table, $table, $table) . implode(",", $objects) . ")";
                break;
            case 'album_artist':
            case 'song_artist':
                $sql = "SELECT DISTINCT `artist`.`id`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `name` FROM `artist` WHERE `id` IN (" . implode(",", $objects) . ")";
                break;
            case 'catalog':
            case 'live_stream':
            case 'playlist':
            case 'search':
                $sql = sprintf('SELECT DISTINCT `%s`.`id`, `%s`.`name` AS `name` FROM `%s` WHERE `id` IN (', $table, $table, $table) . implode(",", $objects) . ")";
                break;
            case 'podcast':
            case 'podcast_episode':
            case 'song':
            case 'video':
                $sql = sprintf('SELECT DISTINCT `%s`.`id`, `%s`.`title` AS `name` FROM `%s` WHERE `id` IN (', $table, $table, $table) . implode(",", $objects) . ")";
                break;
            case 'share':
                $sql = sprintf('SELECT DISTINCT `%s`.`id`, `%s`.`description` AS `name` FROM `%s` WHERE `id` IN (', $table, $table, $table) . implode(",", $objects) . ")";
                break;
            case 'playlist_search':
                $object_string = '';
                foreach ($objects as $playlist_id) {
                    $object_string .= (is_numeric($playlist_id))
                        ? $playlist_id . ", "
                        : "'" . $playlist_id . "', ";
                }
                $object_string = rtrim($object_string, ', ');
                $sql           = "SELECT `id`, `name` FROM (SELECT `id`, `name` FROM `playlist` UNION SELECT CONCAT('smart_', `id`) AS `id`, `name` FROM `search`) AS `playlist` WHERE `id` IN (" . $object_string . ")";
                break;
            default:
                return [];
        }


        $sort_sql = ';';
        if (!empty($sort)) {
            $sort_sql = match ($sort) {
                'name_year' => " ORDER BY `name` " . $order . ", `year` " . $order . ";",
                'name_original_year' => " ORDER BY `name` " . $order . ", `original_year` " . $order . ";",
                default => " ORDER BY " . $sort . " " . $order . ";",
            };
        }

        $sql .= $sort_sql;

        $db_results = Dba::read($sql);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results, false)) {
            $results[] = $row;
        }

        return $results;
    }

    /**
     * get_artist_arrays
     *
     * Get each array of [id, f_name, name, album_count, catalog_id, has_art] for artists in an array of catalog id's
     * @param array $catalogs
     */
    public static function get_artist_arrays($catalogs): array
    {
        $sql = (count($catalogs) == 1)
            ? "SELECT DISTINCT `artist`.`id`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `f_name`, `artist`.`name`, `artist`.`album_count` AS `album_count`, `catalog_map`.`catalog_id` AS `catalog_id`, `image`.`object_id` AS `has_art` FROM `artist` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = 'artist' AND `catalog_map`.`object_id` = `artist`.`id` AND `catalog_map`.`catalog_id` = " . (int)$catalogs[0] . " LEFT JOIN `image` ON `image`.`object_type` = 'artist' AND `image`.`object_id` = `artist`.`id` AND `image`.`size` = 'original' WHERE `catalog_map`.`catalog_id` IS NOT NULL ORDER BY `f_name`;"
            : "SELECT DISTINCT `artist`.`id`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `f_name`, `artist`.`name`, `artist`.`album_count` AS `album_count`, MIN(`catalog_map`.`catalog_id`) AS `catalog_id`, `image`.`object_id` AS `has_art` FROM `artist` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = 'artist' AND `catalog_map`.`object_id` = `artist`.`id` AND `catalog_map`.`catalog_id` IN (" . Dba::escape(implode(',', $catalogs)) . ") LEFT JOIN `image` ON `image`.`object_type` = 'artist' AND `image`.`object_id` = `artist`.`id` AND `image`.`size` = 'original' WHERE `catalog_map`.`catalog_id` IS NOT NULL GROUP BY `artist`.`id`, `f_name`, `artist`.`name`, `artist`.`album_count`, `image`.`object_id` ORDER BY `f_name`;";

        $db_results = Dba::read($sql);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results, false)) {
            $results[] = $row;
        }

        return $results;
    }

    /**
     * get_artist_ids
     *
     * This returns an array of ids of artist that have songs in this catalog
     * @param string $filter
     * @return int[]
     */
    public function get_artist_ids($filter = ''): array
    {
        $results = [];

        $sql = 'SELECT DISTINCT(`song`.`artist`) AS `artist` FROM `song` WHERE `song`.`catalog` = ?';
        if ($filter === 'art') {
            $sql = "SELECT DISTINCT(`song`.`artist`) AS `artist` FROM `song` LEFT JOIN `image` ON `song`.`artist` = `image`.`object_id` AND `object_type` = 'artist' WHERE `song`.`catalog` = ? AND `image`.`object_id` IS NULL";
        }

        if ($filter === 'info') {
            // used for recommendations / similar artists
            $sql = "SELECT DISTINCT(`artist`.`id`) AS `artist` FROM `artist` WHERE `artist`.`id` NOT IN (SELECT `object_id` FROM `recommendation` WHERE `object_type` = 'artist') ORDER BY RAND() LIMIT 500;";
        }

        if ($filter === 'time') {
            // used checking musicbrainz and other plugins
            $sql = "SELECT DISTINCT(`artist`.`id`) AS `artist` FROM `artist` WHERE (`artist`.`last_update` < (UNIX_TIMESTAMP() - 2629800) AND `artist`.`mbid` LIKE '%-%-%-%-%') ORDER BY RAND();";
        }

        if ($filter === 'count') {
            // Update for things added in the last run or empty ones
            $sql = "SELECT DISTINCT(`artist`.`id`) AS `artist` FROM `artist` WHERE `artist`.`id` IN (SELECT DISTINCT `song`.`artist` FROM `song` WHERE `song`.`catalog` = ? AND `addition_time` > " . $this->last_add . ") OR (`album_count` = 0 AND `song_count` = 0) ";
        }

        $db_results = Dba::read($sql, [$this->id]);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['artist'];
        }

        return array_reverse($results);
    }

    /**
     * get_artists
     *
     * This returns an array of artists that have songs in the catalogs parameter
     * @param array|null $catalogs
     * @param int $size
     * @param int $offset
     * @return Artist[]
     */
    public static function get_artists($catalogs = null, $size = 0, $offset = 0): array
    {
        $sql_where = "WHERE `artist`.`album_count` > 0";
        if (is_array($catalogs) && count($catalogs)) {
            $catlist   = '(' . implode(',', $catalogs) . ')';
            $sql_where = ' AND `song`.`catalog` IN ' . $catlist;
        }

        $sql_limit = "";
        if ($offset > 0 && $size > 0) {
            $sql_limit = "LIMIT " . $offset . ", " . $size;
        } elseif ($size > 0) {
            $sql_limit = "LIMIT " . $size;
        } elseif ($offset > 0) {
            // MySQL doesn't have notation for last row, so we have to use the largest possible BIGINT value
            // https://dev.mysql.com/doc/refman/5.0/en/select.html  // TODO mysql8 test
            $sql_limit = "LIMIT " . $offset . ", 18446744073709551615";
        }

        $sql        = sprintf('SELECT `artist`.`id`, `artist`.`name`, `artist`.`prefix`, `artist`.`summary`, `artist`.`album_count` AS `albums` FROM `song` LEFT JOIN `artist` ON `artist`.`id` = `song`.`artist` %s GROUP BY `artist`.`id`, `artist`.`name`, `artist`.`prefix`, `artist`.`summary`, `song`.`artist`, `artist`.`album_count` ORDER BY `artist`.`name` ', $sql_where) . $sql_limit;
        $db_results = Dba::read($sql);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = Artist::construct_from_array($row);
        }

        return $results;
    }

    /**
     * get_id_from_file
     *
     * Get media id from the file path.
     *
     * @param string $file_path
     * @param string $media_type
     */
    public static function get_id_from_file($file_path, $media_type): int
    {
        $sql        = sprintf('SELECT `id` FROM `%s` WHERE `file` = ?;', $media_type);
        $db_results = Dba::read($sql, [$file_path]);

        if ($results = Dba::fetch_assoc($db_results)) {
            return (int)$results['id'];
        }

        return 0;
    }

    /**
     * get_ids_from_folder
     *
     * Get media id's from a base folder path
     *
     * @param string $folder_path
     * @param string $media_type
     * @return int[]
     */
    public static function get_ids_from_folder($folder_path, $media_type): array
    {
        $objects     = [];
        $folder_path = Dba::escape($folder_path);
        $media_type  = Dba::escape($media_type);
        $sql         = sprintf('SELECT `id` FROM `%s` WHERE `file` LIKE \'%s%%\'', $media_type, $folder_path);
        $db_results  = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $objects[] = (int)$row['id'];
        }

        return $objects;
    }

    /**
     * get_label_ids
     *
     * This returns an array of ids of labels
     * @param string $filter
     * @return int[]
     */
    public function get_label_ids($filter): array
    {
        $results = [];

        $sql        = 'SELECT `id` FROM `label` WHERE `category` = ? OR `mbid` IS NULL';
        $db_results = Dba::read($sql, [$filter]);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     * get all artists or artist children of a catalog id (Used for WebDav)
     * @param string $name
     * @param int $catalog_id
     */
    public static function get_children($name, $catalog_id = 0): array
    {
        $childrens = [];
        $sql       = "SELECT DISTINCT `artist`.`id` FROM `artist` ";
        if ((int)$catalog_id > 0) {
            $sql .= "LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = 'album_artist' AND `catalog_map`.`object_id` = `artist`.`id` AND `catalog_map`.`catalog_id` = " . (int)$catalog_id;
        }

        $sql .= "WHERE (`artist`.`name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ?) ";
        if ((int)$catalog_id > 0) {
            $sql .= "AND `catalog_map`.`object_id` IS NOT NULL";
        }

        $db_results = Dba::read($sql, [$name, $name]);
        while ($row = Dba::fetch_assoc($db_results)) {
            $childrens[] = [
                'object_type' => 'artist',
                'object_id' => $row['id']
            ];
        }

        return $childrens;
    }

    /**
     * get_albums
     *
     * Returns an array of ids of albums that have songs in the catalogs parameter
     * @param int $size
     * @param int $offset
     * @param int[]|null $catalogs
     * @return int[]
     */
    public static function get_albums($size = 0, $offset = 0, $catalogs = null): array
    {
        $sql = "SELECT `album`.`id` FROM `album` ";
        if (is_array($catalogs) && count($catalogs)) {
            $catlist = '(' . implode(',', $catalogs) . ')';
            $sql     = sprintf('SELECT `album`.`id` FROM `song` LEFT JOIN `album` ON `album`.`id` = `song`.`album` WHERE `song`.`catalog` IN %s ', $catlist);
        }

        $sql_limit = "";
        if ($offset > 0 && $size > 0) {
            $sql_limit = sprintf('LIMIT %d, %d', $offset, $size);
        } elseif ($size > 0) {
            $sql_limit = 'LIMIT ' . $size;
        } elseif ($offset > 0) {
            // MySQL doesn't have notation for last row, so we have to use the largest possible BIGINT value
            // https://dev.mysql.com/doc/refman/5.0/en/select.html
            $sql_limit = sprintf('LIMIT %d, 18446744073709551615', $offset);
        }

        $sql .= 'GROUP BY `album`.`id` ORDER BY `album`.`name` ' . $sql_limit;

        $db_results = Dba::read($sql);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     * get_albums_by_artist
     *
     * Returns an array of ids of albums that have songs in the catalogs parameter, grouped by artist
     * @param int $size
     * @param int $offset
     * @param int[]|null $catalogs
     * @return int[]
     * @oaram int $offset
     */
    public static function get_albums_by_artist($size = 0, $offset = 0, $catalogs = null): array
    {
        $sql       = "SELECT `album`.`id` FROM `album` ";
        $sql_where = "";
        $sql_group = "GROUP BY `album`.`id`, `artist`.`name`, `artist`.`id`, `album`.`name`, `album`.`mbid`";
        if (is_array($catalogs) && count($catalogs)) {
            $catlist   = '(' . implode(',', $catalogs) . ')';
            $sql       = "SELECT `song`.`album` as 'id' FROM `song` LEFT JOIN `album` ON `album`.`id` = `song`.`album` ";
            $sql_where = 'WHERE `song`.`catalog` IN ' . $catlist;
            $sql_group = "GROUP BY `song`.`album`, `artist`.`name`, `artist`.`id`, `album`.`name`, `album`.`mbid`";
        }

        $sql_limit = "";
        if ($offset > 0 && $size > 0) {
            $sql_limit = sprintf('LIMIT %d, %d', $offset, $size);
        } elseif ($size > 0) {
            $sql_limit = 'LIMIT ' . $size;
        } elseif ($offset > 0) {
            // MySQL doesn't have notation for last row, so we have to use the largest possible BIGINT value
            // https://dev.mysql.com/doc/refman/5.0/en/select.html  // TODO mysql8 test
            $sql_limit = sprintf('LIMIT %d, 18446744073709551615', $offset);
        }

        $sql .= sprintf('LEFT JOIN `artist` ON `artist`.`id` = `album`.`album_artist` %s %s ORDER BY `artist`.`name`, `artist`.`id`, `album`.`name` %s', $sql_where, $sql_group, $sql_limit);

        $db_results = Dba::read($sql);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     * get_podcast_ids
     *
     * This returns an array of ids of podcasts in this catalog
     * @return int[]
     */
    public function get_podcast_ids(): array
    {
        $results = [];

        $sql        = 'SELECT `podcast`.`id` FROM `podcast` WHERE `podcast`.`catalog` = ?';
        $db_results = Dba::read($sql, [$this->id]);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     *
     * @param int[]|null $catalogs
     * @return Podcast[]
     */
    public static function get_podcasts($catalogs = null): array
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
     * get_newest_podcasts_ids
     *
     * This returns an array of ids of latest podcast episodes in this catalog
     * @return list<int>
     */
    private function get_newest_podcasts_ids(int $count): array
    {
        $results = [];

        $sql = 'SELECT `podcast_episode`.`id` FROM `podcast_episode` INNER JOIN `podcast` ON `podcast`.`id` = `podcast_episode`.`podcast` WHERE `podcast`.`catalog` = ? ORDER BY `podcast_episode`.`pubdate` DESC';
        if ($count > 0) {
            $sql .= ' LIMIT ' . $count;
        }

        $db_results = Dba::read($sql, [$this->id]);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     *
     * @param int $count
     * @return Podcast_Episode[]
     */
    public static function get_newest_podcasts($count): array
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
     * gather_art_item
     * @param string $type
     * @param int $object_id
     * @param bool $db_art_first
     * @param bool $api
     */
    public static function gather_art_item($type, $object_id, $db_art_first = false, $api = false): bool
    {
        // Should be more generic !
        if ($type == 'video') {
            $libitem = Video::create_from_id($object_id);
        } else {
            $className = ObjectTypeToClassNameMapper::map($type);
            /** @var library_item $libitem */
            $libitem = new $className($object_id);
        }

        $inserted = false;
        $options  = [];
        if (method_exists($libitem, 'format')) {
            $libitem->format();
        }

        if ($libitem->getId() > 0) {
            // Only search on items with default art kind AS `default`.
            if ($libitem->get_default_art_kind() == 'default') {
                $keywords = $libitem->get_keywords();
                $keyword  = '';
                foreach ($keywords as $key => $word) {
                    $options[$key] = $word['value'];
                    if (array_key_exists('important', $word) && !empty($word['value'])) {
                        $keyword .= ' ' . $word['value'];
                    }
                }

                $options['keyword'] = $keyword;
            }

            $parent = $libitem->get_parent();
            if (!empty($parent) && $type !== 'album') {
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
            // Pull the string representation from the source
            $image = Art::get_from_source($result, $type);
            if (strlen($image) > '5') {
                $inserted = $art->insert($image, $result['mime']);
                // If they've enabled resizing of images generate a thumbnail
                if (AmpConfig::get('resize_images')) {
                    $size  = ['width' => 275, 'height' => 275];
                    $thumb = $art->generate_thumb($image, $size, $result['mime']);
                    if ($thumb !== []) {
                        $art->save_thumb($thumb['thumb'], $thumb['thumb_mime'], $size);
                    }
                }

                if ($inserted) {
                    break;
                }
            } elseif ($result === true) {
                debug_event(self::class, 'Database already has image.', 3);
            } else {
                debug_event(self::class, 'Image less than 5 chars, not inserting', 3);
            }
        }

        if ($type == 'video' && AmpConfig::get('generate_video_preview')) {
            Video::generate_preview($object_id);
        }

        if (Ui::check_ticker() && !$api) {
            Ui::update_text('read_art_' . $object_id, $libitem->get_fullname());
        }

        return $inserted;
    }

    /**
     * gather_art
     *
     * This runs through all of the albums and finds art for them
     * This runs through all of the needs art albums and tries
     * to find the art for them from the mp3s
     * @param int[]|null $songs
     * @param int[]|null $videos
     */
    public function gather_art($songs = null, $videos = null): bool
    {
        // Make sure they've actually got methods
        $art_order       = AmpConfig::get('art_order');
        $gather_song_art = AmpConfig::get('gather_song_art', false);
        $db_art_first    = ($art_order[0] == 'db');
        if (count($art_order) === 0) {
            debug_event(self::class, 'art_order not set, self::gather_art aborting', 3);

            return false;
        }

        // Prevent the script from timing out
        set_time_limit(0);

        $search_count = 0;
        $searches     = [];
        if ($songs == null) {
            $searches['album']  = $this->get_album_ids('art');
            $searches['artist'] = $this->get_artist_ids('art');
            if ($gather_song_art) {
                $searches['song'] = $this->get_song_ids();
            }
        } else {
            $searches['album']  = [];
            $searches['artist'] = [];
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

        $searches['video'] = $videos == null ? $this->get_video_ids() : $videos;

        debug_event(self::class, 'gather_art found ' . count($searches) . ' items missing art', 4);
        // Run through items and get the art!
        foreach ($searches as $key => $values) {
            foreach ($values as $object_id) {
                self::gather_art_item($key, (int)$object_id, $db_art_first);

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
     * @param array $artist_list
     */
    public function gather_artist_info($artist_list = []): void
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
            $artistSongs = static::getSongRepository()->getAllByArtist($object_id);
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
     * update_from_external
     *
     * This runs through all of the labels and refreshes information from musicbrainz
     * @param array $object_list
     * @param string $object_type
     */
    public function update_from_external($object_list, $object_type): void
    {
        // Prevent the script from timing out
        set_time_limit(0);

        debug_event(self::class, 'update_from_external found ' . count($object_list) . ' ' . $object_type . '\'s to check', 4);

        // only allow your primary external metadata source to update values
        $overwrites  = true;
        $meta_order  = array_map('strtolower', static::getConfigContainer()->get(ConfigurationKeyEnum::METADATA_ORDER));
        $plugin_list = Plugin::get_plugins(PluginTypeEnum::EXTERNAL_METADATA_RETRIEVER);
        $user        = (Core::get_global('user') instanceof User)
            ? Core::get_global('user')
            : new User(-1);

        $labelRepository = self::getLabelRepository();

        foreach ($meta_order as $plugin_name) {
            if (in_array($plugin_name, $plugin_list)) {
                // only load metadata plugins you enable
                $plugin = new Plugin($plugin_name);
                if ($plugin->_plugin !== null && $plugin->load($user) && $overwrites) {
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
                        default:
                    }
                }
            }
        }
    }

    /**
     * get_songs
     *
     * Returns an array of song objects.
     * @return Song[]
     */
    public function get_songs(?int $offset = 0, ?int $limit = 0): array
    {
        $songs   = [];
        $results = [];
        if ($offset > 0) {
            $limit = $offset . ', ' . $limit;
        }

        $sql = "SELECT `id` FROM `song` WHERE `catalog` = ? AND `enabled` = '1' ORDER BY `album`";
        if ($offset > 0 || $limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }

        $db_results = Dba::read($sql, [$this->id]);

        while ($row = Dba::fetch_assoc($db_results)) {
            $songs[] = (int)$row['id'];
        }

        if (AmpConfig::get('memory_cache')) {
            Song::build_cache($songs);
        }

        foreach ($songs as $song_id) {
            $results[] = new Song($song_id);
        }

        return $results;
    }

    /**
     * get_song_ids
     *
     * Returns an array of song ids.
     * @return int[]
     */
    public function get_song_ids(): array
    {
        $songs = [];

        $sql        = "SELECT `id` FROM `song` WHERE `catalog` = ? AND `enabled` = '1'";
        $db_results = Dba::read($sql, [$this->id]);

        while ($row = Dba::fetch_assoc($db_results)) {
            $songs[] = (int)$row['id'];
        }

        return $songs;
    }

    /**
     * update_last_update
     * updates the last_update of the catalog
     */
    protected function update_last_update(int $date): void
    {
        self::_update_item('last_update', $date, $this->id);
    }

    /**
     * update_last_add
     * updates the last_add of the catalog
     */
    public function update_last_add(): void
    {
        $date = time();
        self::_update_item('last_add', $date, $this->id);
    }

    /**
     * update_last_clean
     * This updates the last clean information
     */
    public function update_last_clean(): void
    {
        $date = time();
        self::_update_item('last_clean', $date, $this->id);
    }

    /**
     * update_settings
     * This function updates the basic setting of the catalog
     * @param array $data
     */
    public static function update_settings($data): void
    {
        $sql    = "UPDATE `catalog` SET `name` = ?, `rename_pattern` = ?, `sort_pattern` = ? WHERE `id` = ?";
        $params = [$data['name'], $data['rename_pattern'], $data['sort_pattern'], $data['catalog_id']];
        Dba::write($sql, $params);
    }

    /**
     * update_single_item
     * updates a single album,artist,song from the tag data and return the id. (if the artist/album changes it's updated)
     * this can be done by 75+
     * @param string $type
     * @param int $object_id
     * @param bool $api
     */
    public static function update_single_item($type, $object_id, $api = false): array
    {
        // Because single items are large numbers of things too
        set_time_limit(0);

        $return_id = $object_id;
        $songs     = [];
        $libitem   = 0;

        switch ($type) {
            case 'album':
                $libitem = new Album($object_id);
                $songs   = static::getSongRepository()->getByAlbum($object_id);
                break;
            case 'album_disk':
                $albumDisk = new AlbumDisk($object_id);
                $libitem   = new Album($albumDisk->album_id);
                $songs     = static::getSongRepository()->getByAlbumDisk($object_id);
                break;
            case 'artist':
                $libitem = new Artist($object_id);
                $songs   = static::getSongRepository()->getAllByArtist($object_id);
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
        }

        if (!$api) {
            echo '<table class="tabledata striped-rows">' . "\n";
            echo '<thead><tr class="th-top">' . "\n";
            echo "<th>" . T_("Song") . "</th><th>" . T_("Status") . "</th>\n";
            echo "<tbody>\n";
        }

        $album  = false;
        $artist = false;
        $tags   = false;
        $maps   = false;
        foreach ($songs as $song_id) {
            $song   = new Song($song_id);
            $info   = self::update_media_from_tags($song);
            $file   = scrub_out($song->file);
            $diff   = array_key_exists('element', $info) && is_array($info['element']) && $info['element'] !== [];
            $album  = ($album) || ($diff && array_key_exists('album', $info['element']));
            $artist = ($artist) || ($diff && array_key_exists('artist', $info['element']));
            $tags   = ($tags) || ($diff && array_key_exists('tags', $info['element']));
            $maps   = ($maps) || ($diff && array_key_exists('maps', $info));
            // don't echo useless info when using api
            if (array_key_exists('change', $info) && $info['change'] && (!$api)) {
                if ($diff && array_key_exists($type, $info['element'])) {
                    $element   = explode(' --> ', (string)$info['element'][$type]);
                    $return_id = (int)$element[1];
                }

                echo "<tr><td>" . $file . "</td><td>" . T_('Updated') . "</td></tr>\n";
            } elseif (array_key_exists('error', $info) && $info['error'] && (!$api)) {
                echo '<tr><td>' . $file . "</td><td>" . T_('Error') . "</td></tr>\n";
            } elseif (!$api) {
                echo '<tr><td>' . $file . "</td><td>" . T_('No Update Needed') . "</td></tr>\n";
            }

            flush();
        }
        if (!$api) {
            echo "</tbody></table>\n";
        }

        $albumRepository = self::getAlbumRepository();

        // Update the tags for parent items (Songs -> Albums -> Artist)
        if ($libitem instanceof Album) {
            $genres = self::getSongTags('album', $libitem->id);
            Tag::update_tag_list(implode(',', $genres), 'album', $libitem->id, true);
            if ($artist || $album || $tags || $maps) {
                $artists = [];
                // update the album artists
                foreach ($albumRepository->getArtistMap($libitem, 'album') as $albumArtist_id) {
                    $artists[] = $albumArtist_id;
                    $genres    = self::getSongTags('artist', $albumArtist_id);
                    Tag::update_tag_list(implode(',', $genres), 'artist', $albumArtist_id, true);
                }

                // update the song artists too
                foreach ($albumRepository->getArtistMap($libitem, 'song') as $songArtist_id) {
                    if (!in_array($songArtist_id, $artists)) {
                        $genres = self::getSongTags('artist', $songArtist_id);
                        Tag::update_tag_list(implode(',', $genres), 'artist', $songArtist_id, true);
                    }
                }
            }
        }

        // artist
        if ($libitem instanceof Artist) {
            // make sure albums are updated before the artist (include if you're just a song artist too)
            foreach (static::getAlbumRepository()->getAlbumByArtist($object_id) as $album_id) {
                $album_tags = self::getSongTags('album', $album_id);
                Tag::update_tag_list(implode(',', $album_tags), 'album', $album_id, true);
            }

            // refresh the artist tags after everything else
            $genres = self::getSongTags('artist', $libitem->id);
            Tag::update_tag_list(implode(',', $genres), 'artist', $libitem->id, true);
        }

        if ($type !== 'song') {
            // check counts
            if ($album || $maps) {
                Album::update_table_counts();
            }

            if ($artist || $maps) {
                Artist::update_table_counts();
            }

            // collect the garbage too
            if ($album || $artist || $maps) {
                self::getArtistRepository()->collectGarbage();
                self::getAlbumRepository()->collectGarbage();
            }
        }

        return [
            'object_id' => $return_id,
            'change' => ($album || $artist || $maps || $tags)
        ];
    }

    /**
     * update_media_from_tags
     * This is a 'wrapper' function calls the update function for the media
     * type in question
     * @param list<string> $gather_types
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

        // retrieve the file if needed
        $streamConfiguration = $catalog->prepare_media($media);

        if ($streamConfiguration === null) {
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

        if ($media instanceof Song) {
            $update = self::update_song_from_tags($results, $media);
        } elseif ($media instanceof Video) {
            $update = self::update_video_from_tags($results, $media);
        } elseif ($media instanceof Podcast_Episode) {
            $update = self::update_podcast_episode_from_tags($results, $media);
        } else {
            $update = [];
        }

        // remote catalogs should unlink the temp files if needed //TODO add other types of remote catalog
        if ($catalog instanceof Catalog_Seafile) {
            $catalog->clean_tmp_file($streamConfiguration['file_path']);
        }

        return $update;
    }

    /**
     * update_song_from_tags
     * Updates the song info based on tags; this is called from a bunch of
     * different places and passes in a full fledged song object, so it's a
     * static function.
     * FIXME: This is an ugly mess, this really needs to be consolidated and cleaned up.
     * @throws ReflectionException
     */
    public static function update_song_from_tags(array $results, Song $song): array
    {
        //debug_event(self::class, "update_song_from_tags results: " . print_r($results, true), 4);
        // info for the song table. This is all the primary file data that is song related
        $new_song       = new Song();
        $new_song->file = $results['file'];
        $new_song->year = (strlen((string)$results['year']) > 4)
            ? (int)substr((string) $results['year'], -4, 4)
            : (int)($results['year']);
        $new_song->disk         = (Album::sanitize_disk($results['disk']) > 0) ? Album::sanitize_disk($results['disk']) : 1;
        $new_song->disksubtitle = $results['disksubtitle'];
        $new_song->title        = self::check_length(self::check_title($results['title'], $new_song->file));
        $new_song->bitrate      = $results['bitrate'];
        $new_song->rate         = $results['rate'];
        $new_song->mode         = (in_array($results['mode'], ['vbr', 'cbr', 'abr'])) ? $results['mode'] : 'vbr';
        $new_song->channels     = $results['channels'];
        $new_song->size         = $results['size'];
        $new_song->time         = (strlen((string)$results['time']) > 5)
            ? (int)substr((string) $results['time'], -5, 5)
            : (int)($results['time']);
        if ($new_song->time < 0) {
            // fall back to last time if you fail to scan correctly
            $new_song->time = $song->time;
        }

        $new_song->track    = self::check_track((string)$results['track']);
        $new_song->mbid     = $results['mb_trackid'];
        $new_song->composer = self::check_length($results['composer']);
        $new_song->mime     = $results['mime'];

        // info for the song_data table. used in Song::update_song
        $new_song->comment = $results['comment'];
        $new_song->lyrics  = str_replace(
            ["\r\n", "\r", "\n"],
            '<br />',
            strip_tags((string) $results['lyrics'])
        );
        if (isset($results['license'])) {
            $licenseRepository = static::getLicenseRepository();
            $licenseName       = (string) $results['license'];
            $licenseId         = $licenseRepository->find($licenseName);

            if ($licenseId === 0) {
                $license = $licenseRepository->prototype()
                    ->setName($licenseName);

                $license->save();

                $licenseId = $license->getId();
            }

            $new_song->license = $licenseId;
        } else {
            $new_song->license = null;
        }

        $new_song->label = isset($results['publisher']) ? self::check_length($results['publisher'], 128) : null;
        if ($song->label !== null && $song->label !== '' && $song->label !== '0' && AmpConfig::get('label')) {
            // create the label if missing
            foreach (array_map('trim', explode(';', (string) $new_song->label)) as $label_name) {
                Label::helper($label_name);
            }
        }

        $new_song->language              = self::check_length($results['language'], 128);
        $new_song->replaygain_track_gain = (is_null($results['replaygain_track_gain'])) ? null : (float) $results['replaygain_track_gain'];
        $new_song->replaygain_track_peak = (is_null($results['replaygain_track_peak'])) ? null : (float) $results['replaygain_track_peak'];
        $new_song->replaygain_album_gain = (is_null($results['replaygain_album_gain'])) ? null : (float) $results['replaygain_album_gain'];
        $new_song->replaygain_album_peak = (is_null($results['replaygain_album_peak'])) ? null : (float) $results['replaygain_album_peak'];
        $new_song->r128_track_gain       = (is_null($results['r128_track_gain'])) ? null : (int) $results['r128_track_gain'];
        $new_song->r128_album_gain       = (is_null($results['r128_album_gain'])) ? null : (int) $results['r128_album_gain'];

        // genre is used in the tag and tag_map tables
        $tag_array = [];
        if (!empty($results['genre'])) {
            if (!is_array($results['genre'])) {
                $results['genre'] = [$results['genre']];
            }

            // check if this thing has been renamed into something else
            foreach ($results['genre'] as $tagName) {
                $merged = Tag::construct_from_name($tagName);
                if ($merged->isNew() === false && $merged->is_hidden) {
                    foreach ($merged->get_merged_tags() as $merged_tag) {
                        $tag_array[] = $merged_tag['name'];
                    }
                } else {
                    $tag_array[] = $tagName;
                }
            }
        }

        $new_song->tags = $tag_array;
        $tags           = Tag::get_object_tags('song', $song->id);
        if ($tags) {
            foreach ($tags as $tag) {
                $song->tags[] = $tag['name'];
            }
        }

        // info for the artist table.
        $artist           = self::check_length($results['artist']);
        $artist_mbid      = $results['mb_artistid'];
        $albumartist_mbid = $results['mb_albumartistid'];
        // info for the album table.
        $album      = self::check_length($results['album']);
        $album_mbid = $results['mb_albumid'];
        // year is also included in album
        $album_mbid_group = $results['mb_albumid_group'];
        $release_type     = self::check_length($results['release_type'], 32);
        $release_status   = $results['release_status'];
        $albumartist      = (empty($results['albumartist']))
            ? $song->get_album_artist_fullname()
            : self::check_length($results['albumartist']);
        $albumartist ??= null;

        $original_year    = $results['original_year'];
        $barcode          = self::check_length($results['barcode'], 64);
        $catalog_number   = self::check_length($results['catalog_number'], 64);
        $version          = self::check_length($results['version'], 64);

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
                    $albumartist !== null &&
                    $albumartist !== '' &&
                    $albumartist !== '0'
                ) &&
                $artist === $albumartist
            ) {
                $albumartist = $artists_array[0];
            }

            $artist = $artists_array[0];
        }

        $is_upload_artist = false;
        if ($song->artist) {
            $is_upload_artist = Artist::is_upload($song->artist);
            if ($is_upload_artist) {
                debug_event(self::class, $song->artist . ' : is an uploaded song artist', 4);
                $artist_mbid_array = [];
            }
        }

        $is_upload_albumartist = false;
        if ($song->album && $song->albumartist) {
            $is_upload_albumartist = Artist::is_upload($song->albumartist);
            if ($is_upload_albumartist) {
                debug_event(self::class, $song->albumartist . ' : is an uploaded album artist', 4);
                $albumartist_mbid_array = [];
            }
        }

        // check whether this artist exists (and the album_artist)
        $new_song->artist = ($is_upload_artist)
            ? $song->artist
            : Artist::check($artist, $artist_mbid);
        if ($albumartist || !empty($song->albumartist)) {
            $new_song->albumartist = ($is_upload_albumartist || !$albumartist)
                ? $song->albumartist
                : Artist::check($albumartist, $albumartist_mbid);
            if (!$new_song->albumartist) {
                $new_song->albumartist = $song->albumartist;
            }
        }

        if (!$new_song->artist) {
            $new_song->artist = $song->artist;
        }

        // check whether this album exists
        $new_song->album = ($is_upload_albumartist)
            ? $song->album
            : Album::check($song->getCatalogId(), $album, $new_song->year, $album_mbid, $album_mbid_group, $new_song->albumartist, $release_type, $release_status, $original_year, $barcode, $catalog_number, $version);
        if ($new_song->album === 0) {
            $new_song->album = $song->album;
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
        $map_change = false;

        // add song artists with a valid mbid to the list
        if (!empty($artist_mbid_array)) {
            foreach ($artist_mbid_array as $song_artist_mbid) {
                $songArtist_id = Artist::check_mbid($song_artist_mbid);
                if ($songArtist_id > 0 && !in_array($songArtist_id, $songArtist_array)) {
                    $songArtist_array[] = $songArtist_id;
                }
            }
        }

        // add song artists found by name to the list (Ignore artist names when we have the same amount of MBID's)
        if (!empty($artists_array) && count($artists_array) > count($artist_mbid_array)) {
            foreach ($artists_array as $artist_name) {
                $songArtist_id = (int)Artist::check($artist_name);
                if ($songArtist_id > 0 && !in_array($songArtist_id, $songArtist_array)) {
                    $songArtist_array[] = $songArtist_id;
                }
            }
        }

        // map every song artist we've found
        foreach ($songArtist_array as $songArtist_id) {
            if ((int)$songArtist_id > 0 && !in_array($songArtist_id, $artist_map_song)) {
                $artist_map_song[] = (int)$songArtist_id;
                Artist::add_artist_map($songArtist_id, 'song', $song->id);
                if ($song->played) {
                    Stats::duplicate_map('song', $song->id, 'artist', (int)$songArtist_id);
                }

                $map_change = true;
            }

            if ((int)$songArtist_id > 0 && !in_array($songArtist_id, $album_map_songArtist)) {
                $album_map_songArtist[] = (int)$songArtist_id;
                Album::add_album_map($new_song->album, 'song', (int)$songArtist_id);
                if ($song->played) {
                    Stats::duplicate_map('song', $song->id, 'artist', (int)$songArtist_id);
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
                }
            }
        }

        // map every album artist we've found
        foreach ($albumArtist_array as $albumArtist_id) {
            if ((int)$albumArtist_id > 0 && !in_array($albumArtist_id, $artist_map_album)) {
                $artist_map_album[] = (int)$albumArtist_id;
                Artist::add_artist_map($albumArtist_id, 'album', $new_song->album);
                $map_change = true;
            }

            if ((int)$albumArtist_id > 0 && !in_array($albumArtist_id, $album_map_albumArtist)) {
                $album_map_albumArtist[] = (int)$albumArtist_id;
                Album::add_album_map($new_song->album, 'album', (int)$albumArtist_id);
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

                $map_change = true;
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
                $map_change = true;
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
                $map_change = true;
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

        $metadataManager = self::getMetadataManager();

        if ($metadataManager->isCustomMetadataEnabled()) {
            $ctags = self::filterMetadata($song, $results);
            //debug_event(self::class, "get_clean_metadata " . print_r($ctags, true), 4);
            foreach ($ctags as $tag => $value) {
                $metadataManager->updateOrAddMetadata($song, $tag, (string) $value);
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

        if ($song->albumartist > 0 && $new_song->albumartist && $song->albumartist != $new_song->albumartist && !Art::has_db($new_song->albumartist, 'artist')) {
            Art::duplicate('artist', $song->albumartist, $new_song->albumartist);
        }

        if ($song->album > 0 && $new_song->album && $song->album != $new_song->album && !Art::has_db($new_song->album, 'album')) {
            Art::duplicate('album', $song->album, $new_song->album);
        }

        if ($song->label && AmpConfig::get('label')) {
            $labelRepository = static::getLabelRepository();

            foreach (array_map('trim', explode(';', $song->label)) as $label_name) {
                $label_id = Label::helper($label_name) ?? $labelRepository->lookup($label_name);
                if ($label_id > 0) {
                    $label = $labelRepository->findById($label_id);
                    if ($label !== null) {
                        $artists = $label->get_artists();
                        if ($song->artist && !in_array($song->artist, $artists)) {
                            debug_event(self::class, sprintf('%s: adding association to %s', $song->artist, $label->name), 4);
                            $labelRepository->addArtistAssoc($label->id, $song->artist, new DateTime());
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
                self::migrate('artist', $song->artist, $new_song->artist, $song->id);
            }

            // albums changes also require album_disk changes
            if (($song->album > 0 && $new_song->album) && self::migrate('album', $song->album, $new_song->album, $song->id)) {
                $sql = "UPDATE IGNORE `album_disk` SET `album_id` = ? WHERE `id` = ?";
                Dba::write($sql, [$new_song->album, $song->get_album_disk()]);
            }

            // a change on any song will update for the entire disk
            if ($new_song->disksubtitle !== $song->disksubtitle) {
                $sql = "UPDATE `album_disk` SET `disksubtitle` = ? WHERE `id` = ?";
                Dba::write($sql, [$new_song->disksubtitle, $song->get_album_disk()]);
            }

            if ($song->tags != $new_song->tags) {
                // we do still care if there are no tags on your object
                $tag_comma = ($new_song->tags === [])
                    ? ''
                    : implode(',', $new_song->tags);
                Tag::update_tag_list($tag_comma, 'song', $song->id, true);
            }

            if ($song->license !== $new_song->license) {
                Song::update_license($new_song->license, $song->id);
            }
        } else {
            // always update the time when you update
            Song::update_utime($song->id);
        }

        // If song rating tag exists and is well formed (array user=>rating), update it
        if ($song->id && is_array($results) && array_key_exists('rating', $results) && is_array($results['rating'])) {
            // For each user's ratings, call the function
            foreach ($results['rating'] as $user => $rating) {
                debug_event(self::class, "Updating rating for Song " . $song->id . sprintf(' to %s for user %s', $rating, $user), 5);
                $o_rating = new Rating($song->id, 'song');
                $o_rating->set_rating((int)$rating, $user);
            }
        }

        if ($map_change) {
            $info['change'] = true;
            $info['maps']   = true;
            self::updateArtistTags($song->id);
            self::updateAlbumArtistTags($song->album);
        }

        return $info;
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
        $new_video->video_bitrate = self::check_int($results['video_bitrate'], 4294967294, 0);
        $tags                     = Tag::get_object_tags('video', $video->id);
        if ($tags) {
            foreach ($tags as $tag) {
                $video->tags[] = $tag['name'];
            }
        }

        $new_video->tags = $results['genre'];

        $info = Video::compare_video_information($video, $new_video);
        if ($info['change']) {
            debug_event(self::class, $video->file . " : differences found, updating database", 5);

            Video::update_video($video->id, $new_video);

            if ($video->tags != $new_video->tags) {
                Tag::update_tag_list(implode(',', $new_video->tags), 'video', $video->id, true);
            }

            Video::update_video_counts($video->id);
        } else {
            // always update the time when you update
            Video::update_utime($video->id);
        }

        return $info;
    }

    public static function update_podcast_episode_from_tags(array $results, Podcast_Episode $podcast_episode): array
    {
        $sql = "UPDATE `podcast_episode` SET `file` = ?, `size` = ?, `time` = ?, `bitrate` = ?, `rate` = ?, `mode` = ?, `channels` = ?, `state` = 'completed' WHERE `id` = ?";
        Dba::write($sql, [$podcast_episode->file, $results['size'], $results['time'], $results['bitrate'], $results['rate'], $results['mode'], $results['channels'], $podcast_episode->id]);

        $podcast_episode->size     = $results['size'];
        $podcast_episode->time     = $results['time'];
        $podcast_episode->bitrate  = $results['bitrate'];
        $podcast_episode->rate     = $results['rate'];
        $podcast_episode->mode     = (in_array($results['mode'], ['vbr', 'cbr', 'abr'])) ? $results['mode'] : 'vbr';
        $podcast_episode->channels = $results['channels'];

        $array            = [];
        $array['change']  = true;
        $array['element'] = false;

        return $array;
    }

    /**
     * Get rid of all tags found in the libraryItem
     * @param array<string, scalar> $metadata
     * @return array<string, scalar>
     */
    private static function filterMetadata(MetadataEnabledInterface $libraryItem, array $metadata): array
    {
        $metadataManager = self::getMetadataManager();

        // these fields seem to be ignored but should be removed
        $databaseFields = [
            'artists' => null,
            'mb_albumartistid_array' => null,
            'mb_artistid_array' => null,
            'original_year' => null,
            'release_status' => null,
            'release_type' => null,
            'originalyear' => null,
            'dynamic range (r128)' => null,
            'volume level (r128)' => null,
            'volume level (replaygain)' => null,
            'peak level (r128)' => null,
            'peak level (sample)' => null,
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
     * update the artist or album counts on catalog changes
     */
    public static function update_counts(): void
    {
        $update_time = self::get_update_info('update_counts', -1);
        $now_time    = time();
        // give the server a 30 min break for this help with load
        if ($update_time !== 0 && $update_time > ($now_time - 1800)) {
            return;
        }

        self::set_update_info('update_counts', $now_time);
        debug_event(self::class, 'update_counts after catalog changes', 5);
        // missing map tables are pretty important
        $sql = "INSERT IGNORE INTO `artist_map` (`artist_id`, `object_type`, `object_id`) SELECT DISTINCT `song`.`artist` AS `artist_id`, 'song', `song`.`id` FROM `song` WHERE `song`.`artist` > 0 AND `song`.`artist` IS NOT NULL UNION SELECT DISTINCT `album`.`album_artist` AS `artist_id`, 'album', `album`.`id` FROM `album` WHERE `album`.`album_artist` > 0 AND `album`.`album_artist` IS NOT NULL;";
        Dba::write($sql);
        $sql = "INSERT IGNORE INTO `album_map` (`album_id`, `object_type`, `object_id`) SELECT DISTINCT `artist_map`.`object_id` AS `album_id`, 'album' AS `object_type`, `artist_map`.`artist_id` AS `object_id` FROM `artist_map` WHERE `artist_map`.`object_type` = 'album' AND `artist_map`.`object_id` IS NOT NULL UNION SELECT DISTINCT `song`.`album` AS `album_id`, 'song' AS `object_type`, `song`.`artist` AS `object_id` FROM `song` WHERE `song`.`album` IS NOT NULL UNION SELECT DISTINCT `song`.`album` AS `album_id`, 'song' AS `object_type`, `artist_map`.`artist_id` AS `object_id` FROM `artist_map` LEFT JOIN `song` ON `artist_map`.`object_type` = 'song' AND `artist_map`.`object_id` = `song`.`id` WHERE `song`.`album` IS NOT NULL AND `artist_map`.`object_type` = 'song';";
        Dba::write($sql);
        $sql = "INSERT IGNORE INTO `album_disk` (`album_id`, `disk`, `catalog`) SELECT DISTINCT `song`.`album` AS `album_id`, `song`.`disk` AS `disk`, `song`.`catalog` AS `catalog` FROM `song`;";
        Dba::write($sql);
        // do the longer updates over a larger stretch of time
        if ($update_time !== 0 && $update_time < ($now_time - 86400)) {
            // delete old maps in album_map table
            $sql        = "SELECT `album_map`.`album_id`, `album_map`.`object_id`, `album_map`.`object_type` FROM (SELECT * FROM `album_map` WHERE `object_type` = 'song') AS `album_map` LEFT JOIN (SELECT DISTINCT `artist_id`, `album` FROM (SELECT `artist_id`, `object_id` AS `song_id` FROM `artist_map` WHERE `object_type` = 'song') AS `artist_songs`, `song` WHERE `song_id` = `id`) AS `artist_map` ON `album_map`.`object_id` = `artist_map`.`artist_id` AND `album_map`.`album_id` = `artist_map`.`album` WHERE `artist_map`.`album` IS NULL;";
            $db_results = Dba::read($sql);
            while ($row = Dba::fetch_assoc($db_results)) {
                $sql = "DELETE FROM `album_map` WHERE `album_id` = ? AND `object_id` = ? AND `object_type` = ?;";
                Dba::write($sql, [$row['album_id'], $row['object_id'], $row['object_type']]);
            }

            // this isn't really needed often and is slow
            Dba::write("DELETE FROM `recommendation_item` WHERE `recommendation` NOT IN (SELECT `id` FROM `recommendation`);");
            // Fill in null Agents with a value
            $sql = "UPDATE `object_count` SET `agent` = 'Unknown' WHERE `agent` IS NULL;";
            Dba::write($sql);
            // object_count.album
            $sql = "UPDATE IGNORE `object_count`, (SELECT `song_count`.`date`, `song`.`id` AS `songid`, `song`.`album`, `album_count`.`object_id` AS `albumid`, `album_count`.`user`, `album_count`.`agent`, `album_count`.`count_type` FROM `song` LEFT JOIN `object_count` AS `song_count` ON `song_count`.`object_type` = 'song' AND `song_count`.`count_type` = 'stream' AND `song_count`.`object_id` = `song`.`id` LEFT JOIN `object_count` AS `album_count` ON `album_count`.`object_type` = 'album' AND `album_count`.`count_type` = 'stream' AND `album_count`.`date` = `song_count`.`date` WHERE `song_count`.`date` IS NOT NULL AND `song`.`album` != `album_count`.`object_id` AND `album_count`.`count_type` = 'stream') AS `album_check` SET `object_count`.`object_id` = `album_check`.`album` WHERE `object_count`.`object_id` != `album_check`.`album` AND `object_count`.`object_type` = 'album' AND `object_count`.`date` = `album_check`.`date` AND `object_count`.`user` = `album_check`.`user` AND `object_count`.`agent` = `album_check`.`agent` AND `object_count`.`count_type` = `album_check`.`count_type`;";
            Dba::write($sql);
            // object_count.artist
            $sql = "UPDATE IGNORE `object_count`, (SELECT `song_count`.`date`, MIN(`song`.`id`) AS `songid`, MIN(`song`.`artist`) AS `artist`, `artist_count`.`object_id` AS `artistid`, `artist_count`.`user`, `artist_count`.`agent`, `artist_count`.`count_type` FROM `song` LEFT JOIN `object_count` AS `song_count` ON `song_count`.`object_type` = 'song' AND `song_count`.`count_type` = 'stream' AND `song_count`.`object_id` = `song`.`id` LEFT JOIN `object_count` AS `artist_count` ON `artist_count`.`object_type` = 'artist' AND `artist_count`.`count_type` = 'stream' AND `artist_count`.`date` = `song_count`.`date` WHERE `song_count`.`date` IS NOT NULL AND `song`.`artist` != `artist_count`.`object_id` AND `artist_count`.`count_type` = 'stream' GROUP BY `artist_count`.`object_id`, `date`, `user`, `agent`, `count_type`) AS `artist_check` SET `object_count`.`object_id` = `artist_check`.`artist` WHERE `object_count`.`object_id` != `artist_check`.`artist` AND `object_count`.`object_type` = 'artist' AND `object_count`.`date` = `artist_check`.`date` AND `object_count`.`user` = `artist_check`.`user` AND `object_count`.`agent` = `artist_check`.`agent` AND `object_count`.`count_type` = `artist_check`.`count_type`;";
            Dba::write($sql);
        }

        // fix object_count table missing artist row
        debug_event(self::class, 'update_counts object_count table missing artist row', 5);
        $sql = "INSERT IGNORE INTO `object_count` (`object_type`, `object_id`, `date`, `user`, `agent`, `geo_latitude`, `geo_longitude`, `geo_name`, `count_type`) SELECT 'artist', `artist_map`.`artist_id`, `object_count`.`date`, `object_count`.`user`, `object_count`.`agent`, `object_count`.`geo_latitude`, `object_count`.`geo_longitude`, `object_count`.`geo_name`, `object_count`.`count_type` FROM `object_count` LEFT JOIN `artist_map` on `object_count`.`object_type` = `artist_map`.`object_type` AND `object_count`.`object_id` = `artist_map`.`object_id` LEFT JOIN `object_count` AS `artist_check` ON `object_count`.`date` = `artist_check`.`date` AND `artist_check`.`object_type` = 'artist' AND `artist_check`.`object_id` = `artist_map`.`artist_id` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_id` IN (SELECT `id` FROM `song` WHERE `id` IN (SELECT `object_id` FROM `artist_map` WHERE `object_type` = 'song')) AND `artist_check`.`object_id` IS NULL UNION SELECT 'artist', `artist_map`.`artist_id`, `object_count`.`date`, `object_count`.`user`, `object_count`.`agent`, `object_count`.`geo_latitude`, `object_count`.`geo_longitude`, `object_count`.`geo_name`, `object_count`.`count_type` FROM `object_count` LEFT JOIN `artist_map` ON `object_count`.`object_type` = `artist_map`.`object_type` AND `object_count`.`object_id` = `artist_map`.`object_id` LEFT JOIN `object_count` AS `artist_check` ON `object_count`.`date` = `artist_check`.`date` AND `artist_check`.`object_type` = 'artist' AND `artist_check`.`object_id` = `artist_map`.`artist_id` WHERE `object_count`.`object_type` = 'album' AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_id` IN (SELECT `id` FROM `song` WHERE `id` IN (SELECT `object_id` FROM `artist_map` WHERE `object_type` = 'album')) AND `artist_check`.`object_id` IS NULL GROUP BY `artist_map`.`artist_id`, `object_count`.`object_type`, `object_count`.`object_id`, `object_count`.`date`, `object_count`.`user`, `object_count`.`agent`, `object_count`.`geo_latitude`, `object_count`.`geo_longitude`, `object_count`.`geo_name`, `object_count`.`count_type`;";
        Dba::write($sql);
        // fix object_count table missing album row
        debug_event(self::class, 'update_counts object_count table missing album row', 5);
        $sql = "INSERT IGNORE INTO `object_count` (`object_type`, `object_id`, `date`, `user`, `agent`, `geo_latitude`, `geo_longitude`, `geo_name`, `count_type`) SELECT 'album', `song`.`album`, `object_count`.`date`, `object_count`.`user`, `object_count`.`agent`, `object_count`.`geo_latitude`, `object_count`.`geo_longitude`, `object_count`.`geo_name`, `object_count`.`count_type` FROM `object_count` LEFT JOIN `song` ON `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_id` = `song`.`id` LEFT JOIN `object_count` AS `album_count` ON `album_count`.`object_type` = 'album' AND `object_count`.`date` = `album_count`.`date` AND `object_count`.`user` = `album_count`.`user` AND `object_count`.`agent` = `album_count`.`agent` AND `object_count`.`count_type` = `album_count`.`count_type` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `album_count`.`id` IS NULL;";
        Dba::write($sql);
        // also clean up some bad data that might creep in
        Dba::write("UPDATE `artist` SET `prefix` = NULL WHERE `prefix` = '';");
        Dba::write("UPDATE `artist` SET `mbid` = NULL WHERE `mbid` = '';");
        Dba::write("UPDATE `artist` SET `summary` = NULL WHERE `summary` = '';");
        Dba::write("UPDATE `artist` SET `placeformed` = NULL WHERE `placeformed` = '';");
        Dba::write("UPDATE `artist` SET `yearformed` = NULL WHERE `yearformed` = 0;");
        Dba::write("UPDATE `album` SET `album_artist` = NULL WHERE `album_artist` = 0;");
        Dba::write("UPDATE `album` SET `prefix` = NULL WHERE `prefix` = '';");
        Dba::write("UPDATE `album` SET `mbid` = NULL WHERE `mbid` = '';");
        Dba::write("UPDATE `album` SET `mbid_group` = NULL WHERE `mbid_group` = '';");
        Dba::write("UPDATE `album` SET `release_type` = NULL WHERE `release_type` = '';");
        Dba::write("UPDATE `album` SET `original_year` = NULL WHERE `original_year` = 0;");
        Dba::write("UPDATE `album` SET `barcode` = NULL WHERE `barcode` = '';");
        Dba::write("UPDATE `album` SET `catalog_number` = NULL WHERE `catalog_number` = '';");
        Dba::write("UPDATE `album` SET `release_status` = NULL WHERE `release_status` = '';");
        // song.played might have had issues
        $sql = "UPDATE `song` SET `song`.`played` = 0 WHERE `song`.`played` = 1 AND `song`.`id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'song' AND `count_type` = 'stream');";
        Dba::write($sql);
        $sql = "UPDATE `song` SET `song`.`played` = 1 WHERE `song`.`played` = 0 AND `song`.`id` IN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'song' AND `count_type` = 'stream');";
        Dba::write($sql);
        // fix up incorrect total_count values too
        $sql = "UPDATE `song` SET `total_count` = 0 WHERE `total_count` > 0 AND `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream');";
        Dba::write($sql);
        $sql = "UPDATE `song` SET `total_skip` = 0 WHERE `total_skip` > 0 AND `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream');";
        Dba::write($sql);
        if (AmpConfig::get('podcast')) {
            //debug_event(self::class, 'update_counts podcast_episode table', 5);
            // fix object_count table missing podcast row
            $sql        = "SELECT `podcast_episode`.`podcast`, `object_count`.`date`, `object_count`.`user`, `object_count`.`agent`, `object_count`.`geo_latitude`, `object_count`.`geo_longitude`, `object_count`.`geo_name`, `object_count`.`count_type` FROM `object_count` LEFT JOIN `podcast_episode` ON `object_count`.`object_type` = 'podcast_episode' AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_id` = `podcast_episode`.`id` LEFT JOIN `object_count` AS `podcast_count` ON `podcast_count`.`object_type` = 'podcast' AND `object_count`.`date` = `podcast_count`.`date` AND `object_count`.`user` = `podcast_count`.`user` AND `object_count`.`agent` = `podcast_count`.`agent` AND `object_count`.`count_type` = `podcast_count`.`count_type` WHERE `object_count`.`count_type` = 'stream' AND `object_count`.`object_type` = 'podcast_episode' AND `podcast_count`.`id` IS NULL LIMIT 100;";
            $db_results = Dba::read($sql);
            while ($row = Dba::fetch_assoc($db_results)) {
                $sql = "INSERT IGNORE INTO `object_count` (`object_type`, `object_id`, `count_type`, `date`, `user`, `agent`, `geo_latitude`, `geo_longitude`, `geo_name`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                Dba::write($sql, ['podcast', $row['podcast'], $row['count_type'], $row['date'], $row['user'], $row['agent'], $row['geo_latitude'], $row['geo_longitude'], $row['geo_name']]);
            }

            $sql = "UPDATE `podcast_episode` SET `total_count` = 0 WHERE `total_count` > 0 AND `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'podcast_episode' AND `object_count`.`count_type` = 'stream');";
            Dba::write($sql);
            $sql = "UPDATE `podcast_episode` SET `total_skip` = 0 WHERE `total_skip` > 0 AND `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'podcast_episode' AND `object_count`.`count_type` = 'stream');";
            Dba::write($sql);
            $sql = "UPDATE `podcast_episode` SET `podcast_episode`.`played` = 0 WHERE `podcast_episode`.`played` = 1 AND `podcast_episode`.`id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'podcast_episode' AND `count_type` = 'stream');";
            Dba::write($sql);
            $sql = "UPDATE `podcast_episode` SET `podcast_episode`.`played` = 1 WHERE `podcast_episode`.`played` = 0 AND `podcast_episode`.`id` IN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'podcast_episode' AND `count_type` = 'stream');";
            Dba::write($sql);
            // podcast_episode.total_count
            $sql = "UPDATE `podcast_episode`, (SELECT COUNT(`object_count`.`object_id`) AS `total_count`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'podcast_episode' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id`) AS `object_count` SET `podcast_episode`.`total_count` = `object_count`.`total_count` WHERE `podcast_episode`.`total_count` != `object_count`.`total_count` AND `podcast_episode`.`id` = `object_count`.`object_id`;";
            Dba::write($sql);
            // podcast_episode.played
            $sql = "UPDATE `podcast_episode` SET `played` = 0 WHERE `total_count` = 0 and `played` = 1;";
            Dba::write($sql);
            // podcast.total_count
            $sql = "UPDATE `podcast`, (SELECT SUM(`podcast_episode`.`total_count`) AS `total_count`, `podcast` FROM `podcast_episode` GROUP BY `podcast_episode`.`podcast`) AS `object_count` SET `podcast`.`total_count` = `object_count`.`total_count` WHERE `podcast`.`total_count` != `object_count`.`total_count` AND `podcast`.`id` = `object_count`.`podcast`;";
            Dba::write($sql);
            // podcast.total_skip
            $sql = "UPDATE `podcast`, (SELECT SUM(`podcast_episode`.`total_skip`) AS `total_skip`, `podcast` FROM `podcast_episode` GROUP BY `podcast_episode`.`podcast`) AS `object_count` SET `podcast`.`total_skip` = `object_count`.`total_skip` WHERE `podcast`.`total_skip` != `object_count`.`total_skip` AND `podcast`.`id` = `object_count`.`podcast`;";
            Dba::write($sql);
            // song.total_count
            $sql = "UPDATE `song`, (SELECT COUNT(`object_count`.`object_id`) AS `total_count`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id`) AS `object_count` SET `song`.`total_count` = `object_count`.`total_count` WHERE `song`.`total_count` != `object_count`.`total_count` AND `song`.`id` = `object_count`.`object_id`;";
            Dba::write($sql);
            // song.total_skip
            $sql = "UPDATE `song`, (SELECT COUNT(`object_count`.`object_id`) AS `total_skip`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'skip' GROUP BY `object_count`.`object_id`) AS `object_count` SET `song`.`total_skip` = `object_count`.`total_skip` WHERE `song`.`total_skip` != `object_count`.`total_skip` AND `song`.`id` = `object_count`.`object_id`;";
            Dba::write($sql);
            // song.played
            $sql = "UPDATE `song` SET `played` = 0 WHERE `total_count` = 0 and `played` = 1;";
            Dba::write($sql);
        }

        if (AmpConfig::get('allow_video')) {
            //debug_event(self::class, 'update_counts video table', 5);
            $sql = "UPDATE `video` SET `total_count` = 0 WHERE `total_count` > 0 AND `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'video' AND `object_count`.`count_type` = 'stream');";
            Dba::write($sql);
            $sql = "UPDATE `video` SET `total_skip` = 0 WHERE `total_skip` > 0 AND `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'video' AND `object_count`.`count_type` = 'stream');";
            Dba::write($sql);
            $sql = "UPDATE `video` SET `video`.`played` = 0 WHERE `video`.`played` = 1 AND `video`.`id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'video' AND `count_type` = 'stream');";
            Dba::write($sql);
            $sql = "UPDATE `video` SET `video`.`played` = 1 WHERE `video`.`played` = 0 AND `video`.`id` IN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'video' AND `count_type` = 'stream');";
            Dba::write($sql);
            // video.total_count
            $sql = "UPDATE `video`, (SELECT COUNT(`object_count`.`object_id`) AS `total_count`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'video' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id`) AS `object_count` SET `video`.`total_count` = `object_count`.`total_count` WHERE `video`.`total_count` != `object_count`.`total_count` AND `video`.`id` = `object_count`.`object_id`;";
            Dba::write($sql);
            // video.played
            $sql = "UPDATE `video` SET `played` = 0 WHERE `total_count` = 0 and `played` = 1;";
            Dba::write($sql);
        }

        Artist::update_table_counts();
        Album::update_table_counts();

        // update server total counts
        debug_event(self::class, 'update_counts server total counts', 5);
        $catalog_disable = AmpConfig::get('catalog_disable');
        // tables with media items to count, song-related tables and the rest
        $media_tables = [
            'song',
            'video',
            'podcast_episode'
        ];
        $items        = 0;
        $time         = 0;
        $size         = 0;
        foreach ($media_tables as $table) {
            $enabled_sql = ($catalog_disable) ? sprintf(' WHERE `%s`.`enabled` = \'1\'', $table) : '';
            $sql         = sprintf('SELECT COUNT(`id`), IFNULL(SUM(`time`), 0), IFNULL(SUM(`size`)/1024/1024, 0) FROM `%s`', $table) . $enabled_sql;
            $db_results  = Dba::read($sql);
            $row         = Dba::fetch_row($db_results);
            // save the object and add to the current size
            $items += (int)($row[0] ?? 0);
            $time += (int)($row[1] ?? 0);
            $size += $row[2] ?? 0;
            self::set_update_info($table, (int)($row[0] ?? 0));
        }

        self::set_update_info('items', $items);
        self::set_update_info('time', $time);
        self::set_update_info('size', $size);

        $list_tables = [
            'artist',
            'album',
            'album_disk',
            'search',
            'playlist',
            'live_stream',
            'podcast',
            'user',
            'catalog',
            'label',
            'tag',
            'share',
            'license',
        ];
        foreach ($list_tables as $table) {
            $sql        = sprintf('SELECT COUNT(`id`) FROM `%s`', $table);
            $db_results = Dba::read($sql);
            $row        = Dba::fetch_row($db_results);
            self::set_update_info($table, (int)($row[0] ?? 0));
        }

        debug_event(self::class, 'update_counts User::update_counts()', 5);
        // user accounts may have different items to return based on catalog_filter so lets set those too
        User::update_counts();
        debug_event(self::class, 'update_counts completed', 5);
    }

    /**
     * @param array<string, scalar> $metadata
     */
    public function addMetadata(MetadataEnabledInterface $libraryItem, array $metadata): void
    {
        $metadataManager = self::getMetadataManager();

        $tags = self::filterMetadata($libraryItem, $metadata);

        foreach ($tags as $tag => $value) {
            $metadataManager->addMetadata($libraryItem, $tag, (string) $value);
        }
    }

    /**
     * @param array<string, scalar> $tags
     */
    protected function updateMetadata(MetadataEnabledInterface $item, array $tags): void
    {
        $metadataManager = self::getMetadataManager();

        $tags = self::filterMetadata($item, $tags);

        foreach ($tags as $tag => $value) {
            $metadataManager->updateOrAddMetadata($item, $tag, (string) $value);
        }
    }

    /**
     * get_media_tags
     * @param Song|Video|Podcast_Episode $media
     * @param array $gather_types
     * @param string $sort_pattern
     * @param string $rename_pattern
     */
    public function get_media_tags($media, $gather_types, $sort_pattern, $rename_pattern): array
    {
        // Check for patterns
        if (!$sort_pattern || !$rename_pattern) {
            $sort_pattern   = $this->sort_pattern;
            $rename_pattern = $this->rename_pattern;
        }

        if ($media->file === null) {
            return [];
        }

        $vainfo = $this->getUtilityFactory()->createVaInfo(
            $media->file,
            $gather_types,
            '',
            '',
            (string) $sort_pattern,
            (string) $rename_pattern
        );
        try {
            $vainfo->gather_tags();
        } catch (Exception $exception) {
            debug_event(self::class, 'Error ' . $exception->getMessage(), 1);

            return [];
        }

        $key = VaInfo::get_tag_type($vainfo->tags);

        return VaInfo::clean_tag_info($vainfo->tags, $key, $media->file);
    }

    /**
     * get_gather_types
     * @param string $media_type
     */
    public function get_gather_types($media_type = ''): array
    {
        $catalog_media_type = $this->gather_types;
        if (
            $catalog_media_type === null ||
            $catalog_media_type === '' ||
            $catalog_media_type === '0'
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
     * get_table_from_type
     * @param null|string $gather_type
     */
    public static function get_table_from_type($gather_type): string
    {
        return match ($gather_type) {
            'video' => 'video',
            'podcast' => 'podcast_episode',
            default => 'song',
        };
    }

    /**
     * clean_empty_albums
     */
    public static function clean_empty_albums(): void
    {
        $sql        = "SELECT `id`, `album_artist` FROM `album` WHERE NOT EXISTS (SELECT `id` FROM `song` WHERE `song`.`album` = `album`.`id`);";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $sql = "DELETE FROM `album` WHERE `id` = ?";
            Dba::write($sql, [$row['id']]);
        }

        // these files have missing albums so you can't verify them without updating from tags first
        $sql        = "SELECT `id` FROM `song` WHERE `album` in (SELECT `album_id` FROM `album_map` WHERE `album_id` NOT IN (SELECT `id` from `album`));";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            self::update_single_item('song', $row['id'], true);
        }
    }

    /**
     * clean_duplicate_artists
     *
     * Artists that have the same mbid shouldn't be duplicated but can be created and updated based on names
     */
    public static function clean_duplicate_artists(): void
    {
        debug_event(self::class, "Clean Artists with duplicate mbid's", 5);
        $sql        = "SELECT `mbid`, min(`id`) AS `minid`, max(`id`) AS `maxid` FROM `artist` WHERE `mbid` IS NOT NULL GROUP BY `mbid` HAVING count(`mbid`) >1;";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            debug_event(self::class, "clean_duplicate_artists " . $row['maxid'] . "=>" . $row['minid'], 5);
            $maxId = (int)$row['maxid'];
            $minId = (int)$row['minid'];
            // migrate linked tables first
            //Stats::migrate('artist', $maxId, $minId);
            Useractivity::migrate('artist', $maxId, $minId);
            Recommendation::migrate('artist', $maxId);
            self::getShareRepository()->migrate('artist', $maxId, $minId);
            self::getShoutRepository()->migrate('artist', $maxId, $minId);
            Tag::migrate('artist', $maxId, $minId);
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
     * clean_catalog
     *
     * Cleans the catalog of files that no longer exist.
     */
    public function clean_catalog(): int
    {
        // We don't want to run out of time
        set_time_limit(0);

        debug_event(self::class, 'Starting clean on ' . $this->name, 5);

        if (!defined('SSE_OUTPUT') && !defined('CLI')) {
            require Ui::find_template('show_clean_catalog.inc.php');
            ob_flush();
            flush();
        }

        $dead_total = $this->clean_catalog_proc();
        if ($dead_total > 0) {
            self::clean_empty_albums();
            self::clean_duplicate_artists();
        }

        debug_event(self::class, 'clean finished, ' . $dead_total . ' removed from ' . $this->name, 4);

        if (!defined('SSE_OUTPUT') && !defined('CLI')) {
            Ui::show_box_top();
        }

        Ui::update_text(
            T_("Catalog Cleaned"),
            sprintf(nT_("%d file removed.", "%d files removed.", $dead_total), $dead_total)
        );
        if (!defined('SSE_OUTPUT') && !defined('CLI')) {
            Ui::show_box_bottom();
        }

        $this->update_last_clean();

        return $dead_total;
    }

    /**
     * verify_catalog
     * This function verify the catalog
     */
    public function verify_catalog(): bool
    {
        if (!defined('SSE_OUTPUT') && !defined('CLI')) {
            require Ui::find_template('show_verify_catalog.inc.php');
            ob_flush();
            flush();
        }

        $verified = $this->verify_catalog_proc();

        debug_event(self::class, 'verify finished, ' . $verified . ' updated', 4);

        if (!defined('SSE_OUTPUT') && !defined('CLI')) {
            Ui::show_box_top();
        }

        Ui::update_text(
            T_("Catalog Verified"),
            sprintf(nT_('%d file updated.', '%d files updated.', $verified), $verified)
        );
        if (!defined('SSE_OUTPUT') && !defined('CLI')) {
            Ui::show_box_bottom();
        }

        return true;
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
            'prefix' => $prefix
        ];
    }

    /**
     * @param int|string|null $year
     */
    public static function normalize_year($year): int
    {
        if (empty($year)) {
            return 0;
        }

        $year = (int)($year);
        if ($year < 0 || $year > 9999) {
            return 0;
        }

        return $year;
    }

    /**
     * trim_slashed_list
     * Split items by configurable delimiter
     * Return first item as string = default
     * Return all items as array if doTrim = false passed as optional parameter
     * @param string|null $string
     * @param bool $doTrim
     * @return string|array
     */
    public static function trim_slashed_list($string, $doTrim = true)
    {
        $delimiters = static::getConfigContainer()->get(ConfigurationKeyEnum::ADDITIONAL_DELIMITERS);
        $pattern    = '~[\s]?(' . $delimiters . ')[\s]?~';
        $items      = preg_split($pattern, (string)$string);
        if (!$items) {
            return (string)$string;
        }

        $items = array_map('trim', $items);

        if (isset($items[0]) && $doTrim) {
            return $items[0];
        }

        return $items;
    }

    /**
     * trim_featuring
     * Splits artists featuring from the string
     * @param string $string
     */
    public static function trim_featuring($string): array
    {
        $items = preg_split("/ feat\. /i", $string);
        if (!$items) {
            return [$string];
        }

        return array_map('trim', $items);
    }

    /**
     * check_title
     * this checks to make sure something is
     * set on the title, if it isn't it looks at the
     * filename and tries to set the title based on that
     * @param string $title
     * @param string $file
     */
    public static function check_title($title, $file = ''): string
    {
        if (strlen(trim((string)$title)) < 1) {
            $title = Dba::escape($file) ?? '';
        }

        return $title;
    }

    /**
     * check_length
     * Check to make sure the string fits into the database
     * max_length is the maximum number of characters that the (varchar) column can hold
     * @param string $string
     * @param int $max_length
     */
    public static function check_length($string, $max_length = 255): string
    {
        $string = (string)$string;
        if (false !== $encoding = mb_detect_encoding($string, null, true)) {
            $string = trim(mb_substr($string, 0, $max_length, $encoding));
        } else {
            $string = trim(substr($string, 0, $max_length));
        }

        return $string;
    }

    /**
     * check_track
     * Check to make sure the track number fits into the database: max 32767, min -32767
     *
     * @param string $track
     */
    public static function check_track($track): int
    {
        $retval = ((int)$track > 32767 || (int)$track < -32767) ? (int)substr($track, -4, 4) : (int)$track;
        if ((int)$track !== $retval) {
            debug_event(self::class, "check_track: '{" . $track . "}' out of range. Changed into '{" . $retval . "}'", 4);
        }

        return $retval;
    }

    /**
     * check_int
     * Check to make sure a number fits into the database
     *
     * @param int $my_int
     * @param int $max
     * @param int $min
     */
    public static function check_int($my_int, $max, $min): int
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
     * get_unique_string
     * Check to make sure the string doesn't have duplicate strings ({)e.g. "Enough Records; Enough Records")
     *
     * @param string $str_array
     */
    public static function get_unique_string($str_array): string
    {
        $array = array_unique(array_map('trim', explode(';', $str_array)));

        return implode('', $array);
    }

    /**
     * delete
     * Deletes the catalog and everything associated with it
     * @param int $catalog_id
     */
    public static function delete($catalog_id): bool
    {
        $params  = [$catalog_id];
        $catalog = self::create_from_id($catalog_id);
        if ($catalog === null) {
            return false;
        }

        // Large catalog deletion can take time
        set_time_limit(0);

        $sql        = "DELETE FROM `song` WHERE `catalog` = ?";
        $db_results = Dba::write($sql, $params);
        if (!$db_results) {
            return false;
        }

        self::clean_empty_albums();

        $sql        = "DELETE FROM `video` WHERE `catalog` = ?";
        $db_results = Dba::write($sql, $params);
        if (!$db_results) {
            return false;
        }

        $sql        = "DELETE FROM `podcast` WHERE `catalog` = ?";
        $db_results = Dba::write($sql, $params);
        if (!$db_results) {
            return false;
        }

        $sql        = "DELETE FROM `live_stream` WHERE `catalog` = ?";
        $db_results = Dba::write($sql, $params);
        if (!$db_results) {
            return false;
        }

        $sql        = 'DELETE FROM `catalog_' . $catalog->get_type() . '` WHERE `catalog_id` = ?';
        $db_results = Dba::write($sql, $params);
        if (!$db_results) {
            return false;
        }

        // Next Remove the Catalog Entry it's self
        $sql = "DELETE FROM `catalog` WHERE `id` = ?";
        Dba::write($sql, $params);

        // run garbage collection
        static::getCatalogGarbageCollector()->collect();

        return true;
    }

    /**
     * Update the catalog mapping for various types
     * @param string $table
     */
    public static function update_mapping($table): void
    {
        // fill the data
        debug_event(self::class, 'Update mapping for table: ' . $table, 5);
        if ($table == 'artist') {
            // insert catalog_map artists
            $sql = "INSERT IGNORE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` LEFT JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'song_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'album_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` LEFT JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL GROUP BY `catalog`, `artist_map`.`object_type`, `artist_map`.`artist_id`;";
        } elseif ($table == 'playlist') {
            $sql = "INSERT IGNORE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT `song`.`catalog`, 'playlist', `playlist`.`id` FROM `playlist` LEFT JOIN `playlist_data` ON `playlist`.`id`=`playlist_data`.`playlist` LEFT JOIN `song` ON `song`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'song' GROUP BY `song`.`catalog`, 'playlist', `playlist`.`id`;";
        } else {
            // 'album', 'album_disk', 'song', 'video', 'podcast', 'podcast_episode', 'live_stream'
            $sql = sprintf('INSERT IGNORE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT `%s`.`catalog`, \'%s\', `%s`.`id` FROM `%s` GROUP BY `%s`.`catalog`, \'%s\', `%s`.`id`;', $table, $table, $table, $table, $table, $table, $table);
        }
        Dba::write($sql);
    }

    /**
     * Update the catalog_map table depending on table type
     * @param null|string $media_type
     */
    public static function update_catalog_map($media_type): void
    {
        if ($media_type == 'music') {
            self::update_mapping('artist');
            self::update_mapping('album');
            self::update_mapping('album_disk');
        } elseif ($media_type == 'podcast') {
            self::update_mapping('podcast');
            self::update_mapping('podcast_episode');
        } elseif ($media_type == 'video') {
            self::update_mapping('video');
        }
    }

    /**
     * Update the catalog mapping for various types
     */
    public static function garbage_collect_mapping(): void
    {
        // delete non-existent maps
        $tables = [
            'song',
            'album',
            'video',
            'podcast',
            'podcast_episode',
            'live_stream',
        ];
        foreach ($tables as $type) {
            $sql = sprintf('DELETE FROM `catalog_map` USING `catalog_map` LEFT JOIN (SELECT DISTINCT `%s`.`catalog` AS `catalog_id`, `%s`.`id` AS `object_id` FROM `%s`) AS `valid_maps` ON `valid_maps`.`catalog_id` = `catalog_map`.`catalog_id` AND `valid_maps`.`object_id` = `catalog_map`.`object_id` WHERE `catalog_map`.`object_type` = \'%s\' AND `valid_maps`.`object_id` IS NULL;', $type, $type, $type, $type);
            Dba::write($sql);
        }

        // delete catalog_map artists
        $sql = "DELETE FROM `catalog_map` USING `catalog_map` LEFT JOIN (SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` INNER JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` INNER JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'song_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` INNER JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'album_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` INNER JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL GROUP BY `album`.`catalog`, `artist_map`.`object_type`, `artist_map`.`artist_id`) AS `valid_maps` ON `valid_maps`.`catalog_id` = `catalog_map`.`catalog_id` AND `valid_maps`.`object_id` = `catalog_map`.`object_id` AND `valid_maps`.`map_type` = `catalog_map`.`object_type` WHERE `catalog_map`.`object_type` IN ('artist', 'song_artist', 'album_artist') AND `valid_maps`.`object_id` IS NULL;";
        Dba::write($sql);
        // empty catalogs
        $sql = "DELETE FROM `catalog_map` WHERE `catalog_id` = 0";
        Dba::write($sql);
    }

    /**
     * Delete catalog filters that might have gone missing
     */
    public static function garbage_collect_filters(): void
    {
        Dba::write("DELETE FROM `catalog_filter_group_map` WHERE `group_id` NOT IN (SELECT `id` FROM `catalog_filter_group`);");
        Dba::write("DELETE FROM `catalog_filter_group_map` WHERE `catalog_id` NOT IN (SELECT `id` FROM `catalog`);");
        Dba::write("UPDATE `user` SET `catalog_filter_group` = 0 WHERE `catalog_filter_group` NOT IN (SELECT `id` FROM `catalog_filter_group`);");
        Dba::write("UPDATE IGNORE `catalog_filter_group` SET `id` = 0 WHERE `name` = 'DEFAULT' AND `id` > 0;");
    }

    /**
     * Update the catalog map for a single item
     */
    public static function update_map($catalog, $object_type, $object_id): void
    {
        debug_event(self::class, sprintf('update_map %s: {%s}', $object_type, $object_id), 5);
        if ($object_type == 'artist') {
            // insert catalog_map artists
            $sql = "INSERT IGNORE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` LEFT JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'song_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'album_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` LEFT JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL GROUP BY `catalog`, `artist_map`.`object_type`, `artist_map`.`artist_id`;";
            Dba::write($sql, [$object_id, $object_id, $object_id, $object_id]);
        } elseif ($catalog > 0) {
            $sql = "INSERT IGNORE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) VALUES (?, ?, ?);";
            Dba::write($sql, [$catalog, $object_type, $object_id]);
        }
    }

    /**
     * Migrate an object associated catalog to a new object
     * @param string $object_type
     * @param int $old_object_id
     * @param int $new_object_id
     * @return PDOStatement|bool
     */
    public static function migrate_map($object_type, $old_object_id, $new_object_id)
    {
        $sql    = "UPDATE IGNORE `catalog_map` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";
        $params = [$new_object_id, $object_type, $old_object_id];

        return Dba::write($sql, $params);
    }

    /**
     * Updates artist tags from given song id
     */
    protected static function updateArtistTags(int $song_id): void
    {
        foreach (Song::get_parent_array($song_id) as $artist_id) {
            $tags = self::getSongTags('artist', $artist_id);
            Tag::update_tag_list(implode(',', $tags), 'artist', $artist_id, true);
        }
    }

    /**
     * Updates artist tags from given song id
     */
    protected static function updateAlbumArtistTags(int $album_id): void
    {
        foreach (Song::get_parent_array($album_id, 'album') as $artist_id) {
            $tags = self::getSongTags('artist', $artist_id);
            Tag::update_tag_list(implode(',', $tags), 'artist', $artist_id, true);
        }
    }

    /**
     * Get all tags from all Songs from [type] (artist, album, ...)
     * @param string $type
     * @param int $object_id
     */
    protected static function getSongTags($type, $object_id): array
    {
        $tags = [];
        $sql  = ($type == 'artist')
            ? "SELECT `tag`.`name` FROM `tag` JOIN `tag_map` ON `tag`.`id` = `tag_map`.`tag_id` JOIN `song` ON `tag_map`.`object_id` = `song`.`id` WHERE `song`.`id` IN (SELECT `object_id` FROM `artist_map` WHERE `artist_id` = ? AND `object_type` = 'song') AND `tag_map`.`object_type` = 'song' GROUP BY `tag`.`id`, `tag`.`name`;"
            : sprintf('SELECT `tag`.`name` FROM `tag` JOIN `tag_map` ON `tag`.`id` = `tag_map`.`tag_id` JOIN `song` ON `tag_map`.`object_id` = `song`.`id` WHERE `song`.`%s` = ? AND `tag_map`.`object_type` = \'song\' GROUP BY `tag`.`id`, `tag`.`name`;', $type);
        $db_results = Dba::read($sql, [$object_id]);
        while ($row = Dba::fetch_assoc($db_results)) {
            $tags[] = $row['name'];
        }

        return $tags;
    }

    /**
     * @param Album|AlbumDisk|Artist|Song|Video|Podcast_Episode|Label $libitem
     * @param int|null $user_id
     */
    public static function can_remove($libitem, $user_id = 0): bool
    {
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

        return (
            Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER) ||
            (
                $libitem->get_user_owner() == $user_id &&
                AmpConfig::get('upload_allow_remove')
            )
        );
    }

    /**
     * Return full path of the cached music file.
     * @param int $object_id
     * @param int $catalog_id
     * @param string $path
     * @param string $target
     */
    public static function get_cache_path($object_id, $catalog_id, $path = '', $target = ''): ?string
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
        $path .= '/' . $catalog_id . '/' . substr((string)$object_id, -1, 1) . '/' . substr((string)$object_id, -2, 1) . '/';
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        return rtrim(trim($path), '/') . '/' . $object_id . '.' . $target;
    }

    /**
     * process_action
     * @param string $action
     * @param array|null $catalogs
     * @param array $options
     * @noinspection PhpMissingBreakStatementInspection
     */
    public static function process_action($action, $catalogs, $options = null): void
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
                $catalog_media_types = [];
                if ($catalogs) {
                    foreach ($catalogs as $catalog_id) {
                        $catalog = self::create_from_id($catalog_id);
                        if ($catalog !== null && $catalog->add_to_catalog($options)) {
                            $catalog_media_types[] = $catalog->gather_types;
                        }
                    }

                    if (!defined('SSE_OUTPUT') && !defined('CLI')) {
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
                        $catalog = self::create_from_id($catalog_id);
                        if ($catalog !== null) {
                            $catalog->verify_catalog();
                        }
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
                        $catalog = self::create_from_id($catalog_id);
                        if ($catalog !== null && ($catalog->clean_catalog() < 0 && !in_array($catalog->gather_types, $catalog_media_types))) {
                            $catalog_media_types[] = $catalog->gather_types;
                        }
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
                $catalog_id  = 0;
                // clean deleted files
                $clean_path = (string)($options['clean_path'] ?? '/');
                if (strlen($clean_path) && $clean_path != '/') {
                    $catalog_id = Catalog_local::get_from_path($clean_path);
                    if (is_int($catalog_id)) {
                        $catalog = self::create_from_id($catalog_id);
                        if ($catalog !== null && $catalog->catalog_type == 'local') {
                            switch ($catalog->gather_types) {
                                case 'podcast':
                                    $type      = 'podcast_episode';
                                    $file_ids  = Catalog::get_ids_from_folder($clean_path, $type);
                                    $className = Podcast_Episode::class;
                                    break;
                                case 'video':
                                    $type      = 'video';
                                    $file_ids  = Catalog::get_ids_from_folder($clean_path, $type);
                                    $className = Video::class;
                                    break;
                                case 'music':
                                default:
                                    $type      = 'song';
                                    $file_ids  = Catalog::get_ids_from_folder($clean_path, $type);
                                    $className = Song::class;
                                    break;
                            }

                            $changed = 0;
                            foreach ($file_ids as $file_id) {
                                /** @var Song|Podcast_Episode|Video $className */
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
                                    Catalog::clean_empty_albums();
                                    Album::update_album_artist();
                                    Album::update_table_counts();
                                    Artist::update_table_counts();
                                }
                                self::update_catalog_map($catalog->gather_types);
                            }
                        }
                    }
                }

                // update_from_tags
                $update_path = (string)($options['update_path'] ?? '/');
                if (strlen($update_path) && $update_path != '/' && is_int(Catalog_local::get_from_path($update_path))) {
                    $songs = self::get_ids_from_folder($update_path, 'song');
                    foreach ($songs as $song_id) {
                        self::update_single_item('song', $song_id);
                    }
                }

                // add new files
                $add_path = (string)($options['add_path'] ?? '/');
                if (strlen($add_path) && $add_path != '/') {
                    $catalog_id = Catalog_local::get_from_path($add_path);
                    if (is_int($catalog_id)) {
                        $catalog = self::create_from_id($catalog_id);
                        if ($catalog !== null && $catalog->add_to_catalog(['subdirectory' => $add_path])) {
                            self::update_catalog_map($catalog->gather_types);
                        }
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
                        require Ui::find_template('show_gather_art.inc.php');
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
                    $songTagWriter = static::getSongTagWriter();
                    set_time_limit(0);
                    foreach ($catalogs as $catalog_id) {
                        $catalog = self::create_from_id($catalog_id);
                        if ($catalog !== null) {
                            $song_ids = $catalog->get_song_ids();
                            foreach ($song_ids as $song_id) {
                                $song = new Song($song_id);
                                $song->format();

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
                debug_event(self::class, 'Run Garbage collection', 5);
                static::getCatalogGarbageCollector()->collect();
                $catalog_media_types = [];
                if (!empty($catalogs)) {
                    foreach ($catalogs as $catalog_id) {
                        $catalog = self::create_from_id($catalog_id);
                        if ($catalog !== null && !in_array($catalog->gather_types, $catalog_media_types)) {
                            $catalog_media_types[] = $catalog_media_types;
                        }
                    }

                    foreach ($catalog_media_types as $catalog_media_type) {
                        if ($catalog_media_types == 'music') {
                            self::clean_empty_albums();
                            Album::update_album_artist();
                        }

                        self::update_catalog_map($catalog_media_type);
                    }

                    self::garbage_collect_mapping();
                    self::garbage_collect_filters();
                    self::update_counts();
                }
        }
    }

    /**
     * Get the directory for this file from the catalog and the song info using the sort_pattern
     * takes into account various artists and the alphabet_prefix
     * @param Song $song
     * @param string $sort_pattern
     * @param string|null $base
     * @param string $various_artist
     * @param bool $windowsCompat
     */
    public function sort_find_home($song, $sort_pattern, $base = null, $various_artist = "Various Artists", $windowsCompat = false): ?string
    {
        $home = '';
        if ($base) {
            $home = rtrim($base, "\/");
            $home = rtrim($home, "\\");
        }

        // Create the filename that this file should have
        $album = self::sort_clean_name($song->get_album_fullname(), '%A', $windowsCompat);
        //$artist = self::sort_clean_name($song->get_artist_fullname(), '%a', $windowsCompat);
        $track = self::sort_clean_name($song->track, '%T', $windowsCompat);
        if ((int) $track < 10) {
            $track = '0' . $track;
        }

        $title   = self::sort_clean_name($song->title, '%t', $windowsCompat);
        $year    = self::sort_clean_name($song->year, '%y', $windowsCompat);
        $comment = self::sort_clean_name($song->comment, '%c', $windowsCompat);

        // Do the various check
        $album_object = new Album($song->album);
        $album_object->format();

        $artist = (empty($album_object->f_artist_name))
            ? $various_artist
            : self::sort_clean_name($album_object->f_artist_name, '%a', $windowsCompat);
        $disk           = self::sort_clean_name($song->disk, '%d');
        $catalog_number = self::sort_clean_name($album_object->catalog_number, '%C');
        $barcode        = self::sort_clean_name($album_object->barcode, '%b');
        $original_year  = self::sort_clean_name($album_object->original_year, '%Y');
        $release_type   = self::sort_clean_name($album_object->release_type, '%r');
        $release_status = self::sort_clean_name($album_object->release_status, '%R');
        $version        = self::sort_clean_name($album_object->version, '%s');
        $genre          = ($album_object->tags === [])
            ? '%b'
            : Tag::get_display($album_object->tags);

        // Replace everything we can find
        $replace_array = [
            '%a',
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
            $artist,
            $album,
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
        $sort_pattern  = str_replace($replace_array, $content_array, $sort_pattern);

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
        $sort_pattern       = str_replace($post_replace_array, $post_content_array, (string)$sort_pattern);

        $home .= '/' . $sort_pattern;

        // don't send a mismatched file!
        foreach ($replace_array as $replace_string) {
            if (str_contains($sort_pattern, $replace_string)) {
                return null;
            }
        }

        return $home;
    }

    /**
     * This is run on every individual element of the search before it is put together
     * It removes / and \ and windows-incompatible characters (if you use -w|--windows)
     * @param string|int|null $string
     * @param string $return
     * @param bool $windowsCompat
     */
    public static function sort_clean_name($string, $return = '', $windowsCompat = false): string
    {
        if (empty($string)) {
            return $return;
        }

        $string = ($windowsCompat)
            ? str_replace(['/', '\\', ':', '*', '<', '>', '"', '|', '?'], '_', (string)$string)
            : str_replace(['/', '\\'], '_', (string)$string);

        return (string)$string;
    }

    /**
     * Migrate an object associate images to a new object
     * @param string $object_type
     * @param int $old_object_id
     * @param int $new_object_id
     * @param int $song_id
     */
    public static function migrate($object_type, $old_object_id, $new_object_id, $song_id): bool
    {
        if ($old_object_id != $new_object_id) {
            debug_event(self::class, sprintf('migrate %d %s: {%d} to {%d}', $song_id, $object_type, $old_object_id, $new_object_id), 4);

            Stats::migrate($object_type, $old_object_id, $new_object_id, $song_id);
            Useractivity::migrate($object_type, $old_object_id, $new_object_id);
            Recommendation::migrate($object_type, $old_object_id);
            self::getShareRepository()->migrate($object_type, $old_object_id, $new_object_id);
            self::getShoutRepository()->migrate($object_type, $old_object_id, $new_object_id);
            Tag::migrate($object_type, $old_object_id, $new_object_id);
            Userflag::migrate($object_type, $old_object_id, $new_object_id);
            Rating::migrate($object_type, $old_object_id, $new_object_id);
            Art::duplicate($object_type, $old_object_id, $new_object_id);
            Playlist::migrate($object_type, $old_object_id, $new_object_id);
            Label::migrate($object_type, $old_object_id, $new_object_id);
            if ($object_type === 'artist') {
                self::getWantedRepository()->migrateArtist($old_object_id, $new_object_id);
            }

            self::getMetadataRepository()->migrate($object_type, $old_object_id, $new_object_id);
            self::getBookmarkRepository()->migrate($object_type, $old_object_id, $new_object_id);
            self::migrate_map($object_type, $old_object_id, $new_object_id);

            return true;
        }

        return false;
    }

    public function supportsType(string $type): bool
    {
        return $this->gather_types === $type;
    }

    /**
     * @deprecated
     */
    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
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
     * @deprecated
     */
    private static function getCatalogGarbageCollector(): CatalogGarbageCollectorInterface
    {
        global $dic;

        return $dic->get(CatalogGarbageCollectorInterface::class);
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
     * @deprecated inject by constructor
     */
    private static function getConfigContainer(): ConfigContainerInterface
    {
        global $dic;

        return $dic->get(ConfigContainerInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getUtilityFactory(): UtilityFactoryInterface
    {
        global $dic;

        return $dic->get(UtilityFactoryInterface::class);
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
     * @deprecated Inject by constructor
     */
    private static function getShoutRepository(): ShoutRepositoryInterface
    {
        global $dic;

        return $dic->get(ShoutRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getPodcastRepository(): PodcastRepositoryInterface
    {
        global $dic;

        return $dic->get(PodcastRepositoryInterface::class);
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
     * @deprecated  inject dependency
     */
    private static function getShareRepository(): ShareRepositoryInterface
    {
        global $dic;

        return $dic->get(ShareRepositoryInterface::class);
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
    private static function getBookmarkRepository(): BookmarkRepositoryInterface
    {
        global $dic;

        return $dic->get(BookmarkRepositoryInterface::class);
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
     * @deprecated inject dependency
     */
    private static function getArtistRepository(): ArtistRepositoryInterface
    {
        global $dic;

        return $dic->get(ArtistRepositoryInterface::class);
    }
}
