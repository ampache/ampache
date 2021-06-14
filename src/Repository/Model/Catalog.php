<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 */

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Art\Collector\ArtCollectorInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Catalog\ArtItemGatherer;
use Ampache\Module\Catalog\Catalog_beets;
use Ampache\Module\Catalog\Catalog_beetsremote;
use Ampache\Module\Catalog\Catalog_dropbox;
use Ampache\Module\Catalog\Catalog_local;
use Ampache\Module\Catalog\Catalog_remote;
use Ampache\Module\Catalog\Catalog_Seafile;
use Ampache\Module\Catalog\Catalog_soundcloud;
use Ampache\Module\Catalog\Catalog_subsonic;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\Recommendation;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Module\Util\VaInfo;
use Ampache\Module\Video\VideoLoaderInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Exception;

/**
 * This class handles all actual work in regards to the catalog,
 * it contains functions for creating/listing/updated the catalogs.
 */
abstract class Catalog extends database_object
{
    protected const DB_TABLENAME = 'catalog';

    private const CATALOG_TYPES = [
        'beets' => Catalog_beets::class,
        'beetsremote' => Catalog_beetsremote::class,
        'dropbox' => Catalog_dropbox::class,
        'local' => Catalog_local::class,
        'remote' => Catalog_remote::class,
        'seafile' => Catalog_Seafile::class,
        'soundcloud' => Catalog_soundcloud::class,
        'subsonic' => Catalog_subsonic::class,
    ];

    /**
     * @var integer $id
     */
    public $id;
    /**
     * @var string $name
     */
    public $name;
    /**
     * @var integer $last_update
     */
    public $last_update;
    /**
     * @var integer $last_add
     */
    public $last_add;
    /**
     * @var integer $last_clean
     */
    public $last_clean;
    /**
     * @var string $key
     */
    public $key;
    /**
     * @var string $rename_pattern
     */
    public $rename_pattern;
    /**
     * @var string $sort_pattern
     */
    public $sort_pattern;
    /**
     * @var string $catalog_type
     */
    public $catalog_type;
    /**
     * @var string $gather_types
     */
    public $gather_types;

    /**
     * @var string $f_name
     */
    public $f_name;
    /**
     * @var string $link
     */
    public $link;
    /**
     * @var string $f_link
     */
    public $f_link;
    /**
     * @var string $f_update
     */
    public $f_update;
    /**
     * @var string $f_add
     */
    public $f_add;
    /**
     * @var string $f_clean
     */
    public $f_clean;
    /**
     * alias for catalog paths, urls, etc etc
     * @var string $f_full_info
     */
    public $f_full_info;
    /**
     * alias for catalog paths, urls, etc etc
     * @var string $f_info
     */
    public $f_info;
    /**
     * @var integer $enabled
     */
    public $enabled;

    /**
     * This is a private var that's used during catalog builds
     * @var array $_playlists
     */
    protected $_playlists = array();

    /**
     * Cache all files in catalog for quick lookup during add
     * @var array $_filecache
     */
    protected $_filecache = array();

    // Used in functions
    /**
     * @var array $albums
     */
    protected static $albums = array();
    /**
     * @var array $artists
     */
    protected static $artists = array();
    /**
     * @var array $tags
     */
    protected static $tags = array();

    /**
     * @return string
     */
    abstract public function get_type();

    /**
     * @return string
     */
    abstract public function get_description();

    /**
     * @return string
     */
    abstract public function get_version();

    /**
     * @return string
     */
    abstract public function get_create_help();

    /**
     * @return boolean
     */
    abstract public function is_installed();

    /**
     * @return boolean
     */
    abstract public function install();

    /**
     * @param array $options
     * @return mixed
     */
    abstract public function add_to_catalog($options = null);

    /**
     * @return mixed
     */
    abstract public function verify_catalog_proc();

    /**
     * @return int
     */
    abstract public function clean_catalog_proc();

    /**
     * @param string $new_path
     * @return boolean
     */
    abstract public function move_catalog_proc($new_path);

    /**
     * @return array
     */
    abstract public function catalog_fields();

    /**
     * @param string $file_path
     * @return string
     */
    abstract public function get_rel_path($file_path);

    /**
     * @param PlayableMediaInterface $media
     * @return false|PlayableMediaInterface|null
     */
    abstract public function prepare_media($media);

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * Check if the catalog is ready to perform actions (configuration completed, ...)
     * @return boolean
     */
    public function isReady()
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
     * @return boolean
     */
    public function uninstall()
    {
        $sql = "DELETE FROM `catalog` WHERE `catalog_type` = ?";
        Dba::query($sql, array($this->get_type()));

        $sql = "DROP TABLE `catalog_" . $this->get_type() . "`";
        Dba::query($sql);

        return true;
    } // uninstall

    /**
     * @deprecated See CatalogLoader
     *
     * Create a catalog from its id.
     * @param integer $catalog_id
     * @return Catalog|null
     */
    public static function create_from_id($catalog_id)
    {
        $sql        = 'SELECT `catalog_type` FROM `catalog` WHERE `id` = ?';
        $db_results = Dba::read($sql, array($catalog_id));
        $results    = Dba::fetch_assoc($db_results);

        return self::create_catalog_type($results['catalog_type'], $catalog_id);
    }

    /**
     * create_catalog_type
     * This function attempts to create a catalog type
     * @param string $type
     * @param integer $catalog_id
     * @return Catalog|null
     */
    private static function create_catalog_type($type, $catalog_id = 0)
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
        if ($catalog_id > 0) {
            $catalog = new $controller($catalog_id);
        } else {
            $catalog = new $controller();
        }
        if (!($catalog instanceof Catalog)) {
            debug_event(__CLASS__, $type . ' not an instance of Catalog abstract, unable to load', 1);

            return null;
        }
        // identify if it's actually enabled
        $sql        = 'SELECT `enabled` FROM `catalog` WHERE `id` = ?';
        $db_results = Dba::read($sql, array($catalog->id));

        while ($results = Dba::fetch_assoc($db_results)) {
            $catalog->enabled = $results['enabled'];
        }

        return $catalog;
    }

    /**
     * get_catalog_types
     * This returns the catalog types that are available
     * @return string[]
     */
    public static function get_catalog_types()
    {
        return array_keys(self::CATALOG_TYPES);
    }

    /**
     * Check if a file is an audio.
     * @param string $file
     * @return boolean
     */
    public static function is_audio_file($file)
    {
        $pattern = "/\.(" . AmpConfig::get('catalog_file_pattern') . ")$/i";

        return (preg_match($pattern, $file) === 1);
    }

    /**
     * Check if a file is a video.
     * @param string $file
     * @return boolean
     */
    public static function is_video_file($file)
    {
        $video_pattern = "/\.(" . AmpConfig::get('catalog_video_pattern') . ")$/i";

        return (preg_match($video_pattern, $file) === 1);
    }

    /**
     * Check if a file is a playlist.
     * @param string $file
     * @return integer
     */
    public static function is_playlist_file($file)
    {
        $playlist_pattern = "/\.(" . AmpConfig::get('catalog_playlist_pattern') . ")$/i";

        return preg_match($playlist_pattern, $file);
    }

    /**
     * Get catalog info from table.
     * @param integer $object_id
     * @param string $table_name
     * @return array
     */
    public function get_info($object_id, $table_name = 'catalog')
    {
        $info = parent::get_info($object_id, $table_name);

        $table      = 'catalog_' . $this->get_type();
        $sql        = "SELECT `id` FROM $table WHERE `catalog_id` = ?";
        $db_results = Dba::read($sql, array($object_id));

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
     * @return string
     */
    public static function get_enable_filter($type, $catalog_id)
    {
        $sql = "";
        if ($type == "song" || $type == "album" || $type == "artist") {
            if ($type == "song") {
                $type = "id";
            }
            $sql = "(SELECT COUNT(`song_dis`.`id`) FROM `song` AS `song_dis` LEFT JOIN `catalog` AS `catalog_dis` ON `catalog_dis`.`id` = `song_dis`.`catalog` " . "WHERE `song_dis`.`" . $type . "`=" . $catalog_id . " AND `catalog_dis`.`enabled` = '1' GROUP BY `song_dis`.`" . $type . "`) > 0";
        } elseif ($type == "video") {
            $sql = "(SELECT COUNT(`video_dis`.`id`) FROM `video` AS `video_dis` LEFT JOIN `catalog` AS `catalog_dis` ON `catalog_dis`.`id` = `video_dis`.`catalog` " . "WHERE `video_dis`.`id`=" . $catalog_id . " AND `catalog_dis`.`enabled` = '1' GROUP BY `video_dis`.`id`) > 0";
        }

        return $sql;
    }

    /**
     * _create_filecache
     *
     * This populates an array which is used to speed up the add process.
     * @return boolean
     */
    protected function _create_filecache()
    {
        if (count($this->_filecache) == 0) {
            // Get _EVERYTHING_
            $sql        = 'SELECT `id`, `file` FROM `song` WHERE `catalog` = ?';
            $db_results = Dba::read($sql, array($this->id));

            // Populate the filecache
            while ($results = Dba::fetch_assoc($db_results)) {
                $this->_filecache[strtolower((string)$results['file'])] = $results['id'];
            }

            $sql        = 'SELECT `id`, `file` FROM `video` WHERE `catalog` = ?';
            $db_results = Dba::read($sql, array($this->id));

            while ($results = Dba::fetch_assoc($db_results)) {
                $this->_filecache[strtolower((string)$results['file'])] = 'v_' . $results['id'];
            }
        }

        return true;
    }

    /**
     * get_count
     *
     * return the counts from update info to speed up responses
     * @param string $table
     * @return integer
     */
    public static function get_count(string $table)
    {
        if ($table == 'playlist' || $table == 'search') {
            $sql        = "SELECT 'playlist' AS `key`, SUM(value) AS `value` FROM `update_info`" .
                "WHERE `key` IN ('playlist', 'search')";
            $db_results = Dba::read($sql);
        } else {
            $sql        = "SELECT * FROM `update_info` WHERE `key` = ?";
            $db_results = Dba::read($sql, array($table));
        }
        $results    = Dba::fetch_assoc($db_results);

        return (int) $results['value'];
    } // get_count

    /**
     * update_enabled
     * sets the enabled flag
     * @param string $new_enabled
     * @param integer $catalog_id
     */
    public static function update_enabled($new_enabled, $catalog_id)
    {
        self::_update_item('enabled', make_bool($new_enabled), $catalog_id, '75');
    } // update_enabled

    /**
     * _update_item
     * This is a private function that should only be called from within the catalog class.
     * It takes a field, value, catalog id and level. first and foremost it checks the level
     * against Core::get_global('user') to make sure they are allowed to update this record
     * it then updates it and sets $this->{$field} to the new value
     * @param string $field
     * @param boolean $value
     * @param integer $catalog_id
     * @param integer $level
     */
    private static function _update_item($field, $value, $catalog_id, $level)
    {
        /* Check them Rights! */
        if (!Access::check('interface', $level)) {
            return false;
        }

        /* Can't update to blank */
        if (!strlen(trim((string)$value))) {
            return false;
        }

        $value = Dba::escape($value);

        $sql = "UPDATE `catalog` SET `$field`='$value' WHERE `id`='$catalog_id'";

        Dba::write($sql);
    } // _update_item

    /**
     * format
     *
     * This makes the object human-readable.
     */
    public function format()
    {
        $this->f_name   = $this->name;
        $this->link     = AmpConfig::get('web_path') . '/admin/catalog.php?action=show_customize_catalog&catalog_id=' . $this->id;
        $this->f_link   = '<a href="' . $this->link . '" title="' . scrub_out($this->name) . '">' . scrub_out($this->f_name) . '</a>';
        $this->f_update = $this->last_update ? get_datetime((int)$this->last_update) : T_('Never');
        $this->f_add    = $this->last_add ? get_datetime((int)$this->last_add) : T_('Never');
        $this->f_clean  = $this->last_clean ? get_datetime((int)$this->last_clean) : T_('Never');
    }

    /**
     * Get last catalogs update.
     * @param integer[]|null $catalogs
     * @return integer
     */
    public static function getLastUpdate($catalogs = null)
    {
        $last_update = 0;
        if ($catalogs == null || !is_array($catalogs)) {
            $catalogs = static::getCatalogRepository()->getList();
        }
        foreach ($catalogs as $catalogid) {
            $catalog = self::create_from_id($catalogid);
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
     * @param integer|null $catalog_id
     * @return array
     */
    public static function get_stats($catalog_id = null)
    {
        $counts         = ($catalog_id)
            ? self::count_catalog($catalog_id)
            : static::getUpdateInfoRepository()->getServerCounts();
        $counts         = array_merge(User::count(), $counts);
        $counts['tags'] = self::count_tags();

        $counts['formatted_size'] = Ui::format_bytes($counts['size']);

        $hours = floor($counts['time'] / 3600);
        $days  = floor($hours / 24);
        $hours = $hours % 24;

        $time_text = "$days ";
        $time_text .= nT_('day', 'days', $days);
        $time_text .= ", $hours ";
        $time_text .= nT_('hour', 'hours', $hours);

        $counts['time_text'] = $time_text;

        return $counts;
    }

    /**
     * create
     *
     * This creates a new catalog entry and associate it to current instance
     * @param array $data
     * @return integer
     */
    public static function create($data)
    {
        $name           = $data['name'];
        $type           = $data['type'];
        $rename_pattern = $data['rename_pattern'];
        $sort_pattern   = $data['sort_pattern'];
        $gather_types   = $data['gather_media'];

        // Should it be an array? Not now.
        if (!in_array($gather_types,
            array('music', 'clip', 'tvshow', 'movie', 'personal_video', 'podcast'))) {
            return 0;
        }

        $insert_id = 0;

        $classname = self::CATALOG_TYPES[$type] ?? null;

        if ($classname === null) {
            return $insert_id;
        }

        $sql = 'INSERT INTO `catalog` (`name`, `catalog_type`, ' . '`rename_pattern`, `sort_pattern`, `gather_types`) VALUES (?, ?, ?, ?, ?)';
        Dba::write($sql, array(
            $name,
            $type,
            $rename_pattern,
            $sort_pattern,
            $gather_types
        ));

        $insert_id = Dba::insert_id();

        if (!$insert_id) {
            AmpError::add('general', T_('Failed to create the catalog, check the debug logs'));
            debug_event(__CLASS__, 'Insert failed: ' . json_encode($data), 2);

            return 0;
        }

        if (!$classname::create_type($insert_id, $data)) {
            $sql = 'DELETE FROM `catalog` WHERE `id` = ?';
            Dba::write($sql, array($insert_id));
            $insert_id = 0;
        }

        return (int)$insert_id;
    }

    /**
     * count_tags
     *
     * This returns the current number of unique tags in the database.
     * @return integer
     */
    public static function count_tags()
    {
        // FIXME: Ignores catalog_id
        $sql        = "SELECT COUNT(`id`) FROM `tag`";
        $db_results = Dba::read($sql);

        $row = Dba::fetch_row($db_results);

        return $row[0];
    }

    /**
     * count_catalog
     *
     * This returns the current number of songs, videos, podcast_episodes in this catalog.
     * @param integer $catalog_id
     * @return array
     */
    public static function count_catalog($catalog_id)
    {
        $where_sql = $catalog_id ? 'WHERE `catalog` = ?' : '';
        $params    = $catalog_id ? array($catalog_id) : array();
        $results   = array();
        $catalog   = self::create_from_id($catalog_id);

        if ($catalog->id) {
            $table = self::get_table_from_type($catalog->gather_types);
            if ($table == 'podcast_episode' && $catalog_id) {
                $where_sql = "WHERE `podcast` IN ( SELECT `id` FROM `podcast` WHERE `catalog` = ?)";
            }
            $sql              = "SELECT COUNT(`id`), IFNULL(SUM(`time`), 0), IFNULL(SUM(`size`), 0) FROM `" . $table . "` " . $where_sql;
            $db_results       = Dba::read($sql, $params);
            $data             = Dba::fetch_row($db_results);
            $results['items'] = $data[0];
            $results['time']  = $data[1];
            $results['size']  = $data[2];
        }

        return $results;
    } // count_catalog

    /**
     * get_uploads_sql
     *
     * @param string $type
     * @param integer|null $user_id
     * @return string
     */
    public static function get_uploads_sql($type, $user_id = null)
    {
        if ($user_id === null) {
            $user_id = Core::get_global('user')->id;
        }
        $user_id = (int)($user_id);

        switch ($type) {
            case 'song':
                $sql = "SELECT `song`.`id` as `id` FROM `song` WHERE `song`.`user_upload` = '" . $user_id . "'";
                break;
            case 'album':
                $sql = "SELECT `album`.`id` as `id` FROM `album` JOIN `song` ON `song`.`album` = `album`.`id` WHERE `song`.`user_upload` = '" . $user_id . "' GROUP BY `album`.`id`";
                break;
            case 'artist':
            default:
                $sql = "SELECT `artist`.`id` as `id` FROM `artist` JOIN `song` ON `song`.`artist` = `artist`.`id` WHERE `song`.`user_upload` = '" . $user_id . "' GROUP BY `artist`.`id`";
                break;
        }

        return $sql;
    } // get_uploads_sql

    /**
     * get_album_ids
     *
     * This returns an array of ids of albums that have songs in this
     * catalog's
     * @param string $filter
     * @return integer[]
     */
    public function get_album_ids($filter = '')
    {
        $results = array();

        $sql = 'SELECT `album`.`id` FROM `album` WHERE `album`.`catalog` = ?';
        if ($filter === 'art') {
            $sql = "SELECT `album`.`id` FROM `album` LEFT JOIN `image` ON `album`.`id` = `image`.`object_id` AND `object_type` = 'album'" . "WHERE `album`.`catalog` = ? AND `image`.`object_id` IS NULL";
        }
        $db_results = Dba::read($sql, array($this->id));

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return array_reverse($results);
    }

    /**
     * get_video_ids
     *
     * This returns an array of ids of videos in this catalog
     * @param string $type
     * @return integer[]
     */
    public function get_video_ids($type = '')
    {
        $results = array();

        $sql = 'SELECT DISTINCT(`video`.`id`) AS `id` FROM `video` ';
        if (!empty($type)) {
            $sql .= 'JOIN `' . $type . '` ON `' . $type . '`.`id` = `video`.`id`';
        }
        $sql .= 'WHERE `video`.`catalog` = ?';
        $db_results = Dba::read($sql, array($this->id));

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     *
     * @param integer|null $catalog_id
     * @param string $type
     * @return integer
     */
    public static function get_videos_count($catalog_id = null, $type = '')
    {
        $sql = "SELECT COUNT(`video`.`id`) AS `video_cnt` FROM `video` ";
        if (!empty($type)) {
            $sql .= "JOIN `" . $type . "` ON `" . $type . "`.`id` = `video`.`id` ";
        }
        if ($catalog_id) {
            $sql .= "WHERE `video`.`catalog` = `" . (string)($catalog_id) . "`";
        }
        $db_results = Dba::read($sql);
        $video_cnt  = 0;
        if ($row = Dba::fetch_row($db_results)) {
            $video_cnt = $row[0];
        }

        return $video_cnt;
    }

    /**
     * get_tvshow_ids
     *
     * This returns an array of ids of tvshows in this catalog
     * @return integer[]
     */
    public function get_tvshow_ids()
    {
        $results = array();

        $sql = 'SELECT DISTINCT(`tvshow`.`id`) AS `id` FROM `tvshow` ';
        $sql .= 'JOIN `tvshow_season` ON `tvshow_season`.`tvshow` = `tvshow`.`id` ';
        $sql .= 'JOIN `tvshow_episode` ON `tvshow_episode`.`season` = `tvshow_season`.`id` ';
        $sql .= 'JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` ';
        $sql .= 'WHERE `video`.`catalog` = ?';

        $db_results = Dba::read($sql, array($this->id));
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * get_tvshows
     * @param integer[]|null $catalogs
     * @return TvShow[]
     */
    public static function get_tvshows($catalogs = null)
    {
        if (!$catalogs) {
            $catalogs = static::getCatalogRepository()->getList();
        }

        $modelFactory = static::getModelFactory();
        $results      = array();
        foreach ($catalogs as $catalog_id) {
            $catalog    = self::create_from_id($catalog_id);
            $tvshow_ids = $catalog->get_tvshow_ids();
            foreach ($tvshow_ids as $tvshow_id) {
                $results[] = $modelFactory->createTvShow($tvshow_id);
            }
        }

        return $results;
    }

    /**
     * get_artist_arrays
     *
     * Get each array of [id, full_name, name] for artists in an array of catalog id's
     * @param array $catalogs
     * @return array
     */
    public static function get_artist_arrays($catalogs)
    {
        $list = Dba::escape(implode(',', $catalogs));
        $sql  = "SELECT DISTINCT `artist`.`id`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `f_name`, `artist`.`name`, MIN(`catalog_map`.`catalog_id`) FROM `artist` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = 'artist' AND `catalog_map`.`object_id` = `artist`.`id` WHERE `catalog_map`.`catalog_id` IN ($list) GROUP BY `artist`.`id` ORDER BY `artist`.`name`";

        $db_results = Dba::read($sql);
        $results    = array();
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
     * @return integer[]
     */
    public function get_artist_ids($filter = '')
    {
        $results = array();
        $params  = [$this->id];

        $sql = 'SELECT DISTINCT(`song`.`artist`) AS `artist` FROM `song` WHERE `song`.`catalog` = ?';
        if ($filter === 'art') {
            $sql = "SELECT DISTINCT(`song`.`artist`) AS `artist` FROM `song` LEFT JOIN `image` ON `song`.`artist` = `image`.`object_id` AND `object_type` = 'artist'" . "WHERE `song`.`catalog` = ? AND `image`.`object_id` IS NULL";
        }
        if ($filter === 'info') {
            // only update info when you haven't done it for 6 months
            $sql = "SELECT DISTINCT(`artist`.`id`) AS `artist` FROM `artist` WHERE `artist`.`last_update` < (UNIX_TIMESTAMP() - 15768000)";
        }
        if ($filter === 'count') {
            // Update for things added in the last run or empty ones
            $sql = "SELECT DISTINCT(`artist`.`id`) AS `artist` FROM `artist` WHERE `artist`.`id` IN (SELECT DISTINCT `song`.`artist` FROM `song` WHERE `song`.`catalog` = ? AND `addition_time` > " . $this->last_add . ") OR (`album_count` = 0 AND `song_count` = 0) ";
        }
        $db_results = Dba::read($sql, $params);

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
     * @param integer $size
     * @param integer $offset
     * @return Artist[]
     */
    public static function get_artists($catalogs = null, $size = 0, $offset = 0)
    {
        $sql_where = "";
        if (is_array($catalogs) && count($catalogs)) {
            $catlist   = '(' . implode(',', $catalogs) . ')';
            $sql_where = "WHERE `song`.`catalog` IN $catlist ";
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
        $sql = "SELECT `artist`.`id` FROM `song` LEFT JOIN `artist` ON `artist`.`id` = `song`.`artist` " . $sql_where . " GROUP BY `artist`.`id` ORDER BY `artist`.`name` " . $sql_limit;

        $results    = array();
        $db_results = Dba::read($sql);

        $modelFactory = static::getModelFactory();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $modelFactory->createArtist($row['id']);
        }

        return $results;
    }

    /**
     * get_catalog_map
     *
     * This returns an id of artist that have songs in this catalog
     * @param string $object_type
     * @param string $object_id
     * @return integer
     */
    public static function get_catalog_map($object_type, $object_id)
    {
        $sql = "SELECT MIN(`catalog_map`.`catalog_id`) AS `catalog_id` FROM `catalog_map` WHERE `object_type` = ? AND `object_id` = ?";

        $db_results = Dba::read($sql, array($object_type, $object_id));
        if ($row = Dba::fetch_assoc($db_results)) {
            return (int) $row['catalog_id'];
        }

        return 0;
    }

    /**
     * get_id_from_file
     *
     * Get media id from the file path.
     *
     * @param string $file_path
     * @param string $media_type
     * @return integer
     */
    public static function get_id_from_file($file_path, $media_type)
    {
        $sql        = "SELECT `id` FROM $media_type WHERE `file` = ?";
        $db_results = Dba::read($sql, array($file_path));

        if ($results = Dba::fetch_assoc($db_results)) {
            return (int)$results['id'];
        }

        return 0;
    }

    /**
     * get_label_ids
     *
     * This returns an array of ids of labels
     * @param string $filter
     * @return integer[]
     */
    public function get_label_ids($filter)
    {
        $results = array();

        $sql        = 'SELECT `id` FROM `label` WHERE `category` = ? OR `mbid` IS NULL';
        $db_results = Dba::read($sql, array($filter));

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     * @param string $name
     * @param integer $catalog_id
     * @return array
     */
    public static function search_childrens($name, $catalog_id = 0)
    {
        $search                    = array();
        $search['type']            = "artist";
        $search['rule_0_input']    = $name;
        $search['rule_0_operator'] = 4;
        $search['rule_0']          = "name";
        if ($catalog_id > 0) {
            $search['rule_1_input']    = $catalog_id;
            $search['rule_1_operator'] = 0;
            $search['rule_1']          = "catalog";
        }
        $artists = Search::run($search);

        $childrens = array();
        foreach ($artists as $artist_id) {
            $childrens[] = array(
                'object_type' => 'artist',
                'object_id' => $artist_id
            );
        }

        return $childrens;
    }

    /**
     * get_albums
     *
     * Returns an array of ids of albums that have songs in the catalogs parameter
     * @param integer $size
     * @param integer $offset
     * @param integer[]|null $catalogs
     * @return integer[]
     */
    public static function get_albums($size = 0, $offset = 0, $catalogs = null)
    {
        $sql = "SELECT `album`.`id` FROM `album` ";
        if (is_array($catalogs) && count($catalogs)) {
            $catlist = '(' . implode(',', $catalogs) . ')';
            $sql     = "SELECT `album`.`id` FROM `song` LEFT JOIN `album` ON `album`.`id` = `song`.`album` WHERE `song`.`catalog` IN $catlist ";
        }

        $sql_limit = "";
        if ($offset > 0 && $size > 0) {
            $sql_limit = "LIMIT $offset, $size";
        } elseif ($size > 0) {
            $sql_limit = "LIMIT $size";
        } elseif ($offset > 0) {
            // MySQL doesn't have notation for last row, so we have to use the largest possible BIGINT value
            // https://dev.mysql.com/doc/refman/5.0/en/select.html
            $sql_limit = "LIMIT $offset, 18446744073709551615";
        }

        $sql .= "GROUP BY `album`.`id` ORDER BY `album`.`name` $sql_limit";

        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     * get_albums_by_artist
     *
     * Returns an array of ids of albums that have songs in the catalogs parameter, grouped by artist
     * @param integer $size
     * @param integer $offset
     * @param integer[]|null $catalogs
     * @return integer[]
     * @oaram int $offset
     */
    public static function get_albums_by_artist($size = 0, $offset = 0, $catalogs = null)
    {
        $sql       = "SELECT `album`.`id` FROM `album` ";
        $sql_where = "";
        $sql_group = "GROUP BY `album`.`id`, `artist`.`name`, `artist`.`id`, `album`.`name`, `album`.`mbid`";
        if (is_array($catalogs) && count($catalogs)) {
            $catlist   = '(' . implode(',', $catalogs) . ')';
            $sql       = "SELECT `song`.`album` as 'id' FROM `song` LEFT JOIN `album` ON `album`.`id` = `song`.`album` ";
            $sql_where = "WHERE `song`.`catalog` IN $catlist";
            $sql_group = "GROUP BY `song`.`album`, `artist`.`name`, `artist`.`id`, `album`.`name`, `album`.`mbid`";
        }

        $sql_limit = "";
        if ($offset > 0 && $size > 0) {
            $sql_limit = "LIMIT $offset, $size";
        } elseif ($size > 0) {
            $sql_limit = "LIMIT $size";
        } elseif ($offset > 0) {
            // MySQL doesn't have notation for last row, so we have to use the largest possible BIGINT value
            // https://dev.mysql.com/doc/refman/5.0/en/select.html  // TODO mysql8 test
            $sql_limit = "LIMIT $offset, 18446744073709551615";
        }

        $sql .= "LEFT JOIN `artist` ON `artist`.`id` = `album`.`album_artist` $sql_where $sql_group ORDER BY `artist`.`name`, `artist`.`id`, `album`.`name` $sql_limit";

        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     * gather_art_item
     * @param string $type
     * @param integer $object_id
     * @param boolean $db_art_first
     * @param boolean $api
     * @return boolean
     *
     * @deprecated
     * @see ArtItemGatherer::gather()
     */
    public static function gather_art_item($type, $object_id, $db_art_first = false, $api = false)
    {
        // Should be more generic !
        if ($type == 'video') {
            $libitem = static::getVideoLoader()->load((int) $object_id);
        } else {
            $libitem = static::getModelFactory()->mapObjectType($type, (int) $object_id);
        }
        $inserted = false;
        $options  = array();
        $libitem->format();
        if ($libitem->id) {
            // Only search on items with default art kind as `default`.
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

            $parent = $libitem->get_parent();
            if (!empty($parent)) {
                self::gather_art_item($parent['object_type'], $parent['object_id'], $db_art_first, $api);
            }
        }

        $art = new Art($object_id, $type);
        // don't search for art when you already have it
        if ($art->has_db_info() && $db_art_first) {
            debug_event(self::class, 'Blocking art search for ' . $type . '/' . $object_id . ' DB item exists', 5);
            $results = array();
        } else {
            debug_event(__CLASS__, 'Gathering art for ' . $type . '/' . $object_id . '...', 4);

            global $dic;
            $results = $dic->get(ArtCollectorInterface::class)->collect(
                $art,
                $options
            );
        }

        foreach ($results as $result) {
            // Pull the string representation from the source
            $image = Art::get_from_source($result, $type);
            if (strlen((string)$image) > '5') {
                $inserted = $art->insert($image, $result['mime']);
                // If they've enabled resizing of images generate a thumbnail
                if (AmpConfig::get('resize_images')) {
                    $size  = array('width' => 275, 'height' => 275);
                    $thumb = $art->generate_thumb($image, $size, $result['mime']);
                    if (!empty($thumb)) {
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
        if ($inserted) {
            return true;
        }

        return false;
    }

    /**
     * gather_art
     *
     * This runs through all of the albums and finds art for them
     * This runs through all of the needs art albums and tries
     * to find the art for them from the mp3s
     * @param integer[]|null $songs
     * @param integer[]|null $videos
     * @return boolean
     */
    public function gather_art($songs = null, $videos = null)
    {
        // Make sure they've actually got methods
        $art_order       = AmpConfig::get('art_order');
        $gather_song_art = AmpConfig::get('gather_song_art', false);
        $db_art_first    = ($art_order[0] == 'db');
        if (!count($art_order)) {
            debug_event(self::class, 'art_order not set, self::gather_art aborting', 3);

            return false;
        }

        // Prevent the script from timing out
        set_time_limit(0);

        $search_count = 0;
        $searches     = array();
        if ($songs == null) {
            $searches['album']  = $this->get_album_ids('art');
            $searches['artist'] = $this->get_artist_ids('art');
            if ($gather_song_art) {
                $searches['song'] = $this->getSongRepository()->getByCatalog($this);
            }
        } else {
            $searches['album']  = array();
            $searches['artist'] = array();
            if ($gather_song_art) {
                $searches['song'] = array();
            }
            foreach ($songs as $song_id) {
                $song = new Song($song_id);
                if ($song->id) {
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
        if ($videos == null) {
            $searches['video'] = $this->get_video_ids();
        } else {
            $searches['video'] = $videos;
        }

        debug_event(self::class, 'gather_art found ' . (string) count($searches) . ' items missing art', 4);
        // Run through items and get the art!
        foreach ($searches as $key => $values) {
            foreach ($values as $object_id) {
                self::gather_art_item($key, $object_id, $db_art_first);

                // Stupid little cutesie thing
                $search_count++;
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
    public function gather_artist_info($artist_list = array())
    {
        // Prevent the script from timing out
        set_time_limit(0);

        $search_count = 0;
        debug_event(self::class, 'gather_artist_info found ' . (string) count($artist_list) . ' items to check', 4);
        // Run through items and refresh info
        foreach ($artist_list as $object_id) {
            Recommendation::get_artist_info($object_id);
            Recommendation::get_artists_like($object_id);
            $this->getArtistRepository()->updateLastUpdate($object_id);

            // Stupid little cutesie thing
            $search_count++;
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
     */
    public function update_from_external($object_list = array())
    {
        // Prevent the script from timing out
        set_time_limit(0);

        $modelFactory = static::getModelFactory();

        debug_event(self::class, 'update_from_external found ' . (string) count($object_list) . ' items to check', 4);
        $plugin = new Plugin('musicbrainz');
        if ($plugin->load(new User(-1))) {
            // Run through items and refresh info
            foreach ($object_list as $label_id) {
                $label = $modelFactory->createLabel((int) $label_id);
                $plugin->_plugin->get_external_metadata($label, 'label');
            }
        }
    }

    /**
     * update_last_update
     * updates the last_update of the catalog
     */
    protected function update_last_update()
    {
        $date = time();
        $sql  = "UPDATE `catalog` SET `last_update` = ? WHERE `id` = ?";
        Dba::write($sql, array($date, $this->id));
    } // update_last_update

    /**
     * update_last_add
     * updates the last_add of the catalog
     */
    public function update_last_add()
    {
        $date = time();
        $sql  = "UPDATE `catalog` SET `last_add` = ? WHERE `id` = ?";
        Dba::write($sql, array($date, $this->id));
    } // update_last_add

    /**
     * update_last_clean
     * This updates the last clean information
     */
    public function update_last_clean()
    {
        $date = time();
        $sql  = "UPDATE `catalog` SET `last_clean` = ? WHERE `id` = ?";
        Dba::write($sql, array($date, $this->id));
    } // update_last_clean

    /**
     * update_settings
     * This function updates the basic setting of the catalog
     * @param array $data
     * @return boolean
     */
    public static function update_settings($data)
    {
        $sql    = "UPDATE `catalog` SET `name` = ?, `rename_pattern` = ?, `sort_pattern` = ? WHERE `id` = ?";
        $params = array($data['name'], $data['rename_pattern'], $data['sort_pattern'], $data['catalog_id']);
        Dba::write($sql, $params);

        return true;
    } // update_settings

    /**
     * Get rid of all tags found in the libraryItem
     * @param library_item $libraryItem
     * @param array $metadata
     * @return array
     */
    public static function get_clean_metadata(library_item $libraryItem, $metadata)
    {
        $tags = array_diff_key($metadata, get_object_vars($libraryItem), array_flip($libraryItem::$aliases ?: array()));

        return array_filter($tags);
    }

    /**
     *
     * @param library_item $libraryItem
     * @param array $metadata
     */
    public static function add_metadata(library_item $libraryItem, $metadata)
    {
        $tags = self::get_clean_metadata($libraryItem, $metadata);

        foreach ($tags as $tag => $value) {
            $field = $libraryItem->getField($tag);
            $libraryItem->addMetadata($field, $value);
        }
    }

    /**
     * get_media_tags
     * @param PlayableMediaInterface $media
     * @param array $gather_types
     * @param string $sort_pattern
     * @param string $rename_pattern
     * @return array
     */
    public function get_media_tags($media, $gather_types, $sort_pattern, $rename_pattern)
    {
        // Check for patterns
        if (!$sort_pattern || !$rename_pattern) {
            $sort_pattern   = $this->sort_pattern;
            $rename_pattern = $this->rename_pattern;
        }

        $vainfo = $this->getUtilityFactory()->createVaInfo(
            $media->getFile(),
            $gather_types,
            null,
            null,
            $sort_pattern,
            $rename_pattern
        );
        try {
            $vainfo->get_info();
        } catch (Exception $error) {
            debug_event(self::class, 'Error ' . $error->getMessage(), 1);

            return array();
        }

        $key = VaInfo::get_tag_type($vainfo->tags);

        return VaInfo::clean_tag_info($vainfo->tags, $key, $media->getFile());
    }

    /**
     * get_gather_types
     * @param string $media_type
     * @return array
     */
    public function get_gather_types($media_type = '')
    {
        $gtypes = $this->gather_types;
        if (empty($gtypes)) {
            $gtypes = "music";
        }
        $types = explode(',', $gtypes);

        if ($media_type == "video") {
            $types = array_diff($types, array('music'));
        }

        if ($media_type == "music") {
            $types = array_diff($types, array('personal_video', 'movie', 'tvshow', 'clip'));
        }

        return $types;
    }

    /**
     * get_table_from_type
     * @param string $gather_type
     * @return string
     */
    public static function get_table_from_type($gather_type)
    {
        switch ($gather_type) {
            case 'clip':
            case 'tvshow':
            case 'movie':
            case 'personal_video':
                $table = 'video';
                break;
            case 'podcast':
                $table = 'podcast_episode';
                break;
            case 'music':
            default:
                $table = 'song';
                break;
        }

        return $table;
    }

    /**
     * clean_catalog
     *
     * Cleans the catalog of files that no longer exist.
     */
    public function clean_catalog()
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
            static::getAlbumRepository()->cleanEmptyAlbums();
        }

        debug_event(self::class, 'clean finished, ' . $dead_total . ' removed from ' . $this->name, 4);

        if (!defined('SSE_OUTPUT') && !defined('CLI')) {
            Ui::show_box_top();
        }
        Ui::update_text(T_("Catalog Cleaned"),
            sprintf(nT_("%d file removed.", "%d files removed.", $dead_total), $dead_total));
        if (!defined('SSE_OUTPUT') && !defined('CLI')) {
            Ui::show_box_bottom();
        }

        $this->update_last_clean();
    } // clean_catalog

    /**
     * verify_catalog
     * This function verify the catalog
     */
    public function verify_catalog()
    {
        if (!defined('SSE_OUTPUT') && !defined('CLI')) {
            require Ui::find_template('show_verify_catalog.inc.php');
            ob_flush();
            flush();
        }

        $verified = $this->verify_catalog_proc();

        if (!defined('SSE_OUTPUT') && !defined('CLI')) {
            Ui::show_box_top();
        }
        Ui::update_text(T_("Catalog Verified"),
            sprintf(nT_('%d file updated.', '%d files updated.', $verified['updated']), $verified['updated']));
        if (!defined('SSE_OUTPUT') && !defined('CLI')) {
            Ui::show_box_bottom();
        }

        return true;
    } // verify_catalog

    /**
     * trim_prefix
     * Splits the prefix from the string
     * @param string $string
     * @return array
     */
    public static function trim_prefix($string)
    {
        $prefix_pattern = '/^(' . implode('\\s|',
                explode('|', AmpConfig::get('catalog_prefix_pattern'))) . '\\s)(.*)/i';
        preg_match($prefix_pattern, $string, $matches);

        if (count($matches)) {
            $string = trim((string)$matches[2]);
            $prefix = trim((string)$matches[1]);
        } else {
            $prefix = null;
        }

        return array('string' => $string, 'prefix' => $prefix);
    } // trim_prefix

    /**
     * @param $year
     * @return integer
     */
    public static function normalize_year($year)
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
     * @param string $string
     * @param bool $doTrim
     * @return string|array
     */
    public static function trim_slashed_list($string, $doTrim = true)
    {
        $delimiters = static::getConfigContainer()->get(ConfigurationKeyEnum::ADDITIONAL_DELIMITERS);
        $pattern    = '~[\s]?(' . $delimiters . ')[\s]?~';
        $items      = preg_split($pattern, $string);
        $items      = array_map('trim', $items);

        if ((isset($items) && isset($items[0])) && $doTrim) {
            return $items[0];
        }

        return $items;
    } // trim_slashed_list

    /**
     * trim_featuring
     * Splits artists featuring from the string
     * @param string $string
     * @return array
     */
    public static function trim_featuring($string)
    {
        return array_map('trim', explode(' feat. ', $string));
    } // trim_featuring

    /**
     * check_title
     * this checks to make sure something is
     * set on the title, if it isn't it looks at the
     * filename and tries to set the title based on that
     * @param string $title
     * @param string $file
     * @return string
     */
    public static function check_title($title, $file = '')
    {
        if (strlen(trim((string)$title)) < 1) {
            $title = Dba::escape($file);
        }

        return $title;
    } // check_title

    /**
     * check_length
     * Check to make sure the string fits into the database
     * max_length is the maximum number of characters that the (varchar) column can hold
     * @param string $string
     * @param integer $max_length
     * @return string
     */
    public static function check_length($string, $max_length = 255)
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
     * @return integer
     */
    public static function check_track($track)
    {
        $retval = ((int)$track > 32767 || (int)$track < -32767) ? (int)substr($track, -4, 4) : (int)$track;
        if ((int)$track !== $retval) {
            debug_event(__CLASS__, "check_track: '{$track}' out of range. Changed into '{$retval}'", 4);
        }

        return $retval;
    }

    /**
     * check_int
     * Check to make sure a number fits into the database
     *
     * @param integer $track
     * @param integer $max
     * @param integer $min
     * @return integer
     */
    public static function check_int($track, $max, $min)
    {
        if ($track > $max) {
            return $max;
        }
        if ($track < $min) {
            return $min;
        }

        return $track;
    }

    /**
     * get_unique_string
     * Check to make sure the string doesn't have duplicate strings ({)e.g. "Enough Records; Enough Records")
     *
     * @param string $str_array
     * @return string
     */
    public static function get_unique_string($str_array)
    {
        $array = array_unique(array_map('trim', explode(';', $str_array)));

        return implode($array);
    }

    /**
     * @deprecated
     */
    private static function getCatalogRepository(): CatalogRepositoryInterface
    {
        global $dic;

        return $dic->get(CatalogRepositoryInterface::class);
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
    private function getArtistRepository(): ArtistRepositoryInterface
    {
        global $dic;

        return $dic->get(ArtistRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    protected static function getModelFactory(): ModelFactoryInterface
    {
        global $dic;

        return $dic->get(ModelFactoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getVideoLoader(): VideoLoaderInterface
    {
        global $dic;

        return $dic->get(VideoLoaderInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getAlbumRepository(): AlbumRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getUpdateInfoRepository(): UpdateInfoRepositoryInterface
    {
        global $dic;

        return $dic->get(UpdateInfoRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }
}
