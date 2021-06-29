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

use Ampache\Module\Album\Tag\AlbumTagUpdaterInterface;
use Ampache\Module\Song\Tag\SongId3TagWriterInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\Recommendation;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Exception;
use PDOStatement;

/**
 * This is the class responsible for handling the Album object
 * it is related to the album table in the database.
 */
class Album extends database_object implements library_item
{
    protected const DB_TABLENAME = 'album';

    /* Variables from DB */

    /**
     * @var integer $id
     */
    public $id;

    /**
     * @var string $name
     */
    public $name;

    /**
     * @var integer $album_artist
     */
    public $album_artist;

    /**
     * @var integer $disk
     */
    public $disk;

    /**
     * @var integer $year
     */
    public $year;

    /**
     * @var string $prefix
     */
    public $prefix;

    /**
     * @var string $mbid
     */
    public $mbid; // MusicBrainz ID

    /**
     * @var string $mbid_group
     */
    public $mbid_group; // MusicBrainz Release Group ID

    /**
     * @var string $release_type
     */
    public $release_type;

    /**
     * @var string $release_status
     */
    public $release_status;

    /**
     * @var string $catalog_number
     */
    public $catalog_number;

    /**
     * @var string $barcode
     */
    public $barcode;

    /**
     * @var integer $time
     */
    public $time;

    /**
     * @var integer $addition_time
     */
    public $addition_time;

    /**
     *  @var integer $total_duration
     */
    public $total_duration;

    /**
     *  @var integer $original_year
     */
    public $original_year;

    /**
     * @var integer $catalog_id
     */
    public $catalog_id;

    /**
     * @var integer $catalog
     */
    public $catalog;

    /**
     * @var integer $song_count
     */
    public $song_count;

    /**
     * @var string $artist_prefix
     */
    public $artist_prefix;

    /**
     * @var string $artist_name
     */
    public $artist_name;

    /**
     * @var array $tags
     */
    public $tags;

    /**
     * @var integer $artist_count
     */
    public $artist_count;

    /**
     * @var integer $object_cnt
     */
    public $object_cnt;

    /**
     * @var integer $total_count
     */
    private $total_count;

    /**
     * @var string $f_artist_name
     */
    public $f_artist_name;

    /**
     * @var string $f_artist_link
     */
    public $f_artist_link;

    /**
     * @var string $f_artist
     */
    public $f_artist;

    /**
     * @var string $album_artist_name
     */
    public $album_artist_name;

    /**
     * @var string $f_album_artist_name
     */
    public $f_album_artist_name;

    /**
     * @var string $f_album_artist_link
     */
    public $f_album_artist_link;

    /**
     * @var string $f_name // Prefix + Name, generated
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
     * @var string $f_tags
     */
    public $f_tags;

    /**
     * @var string $f_year
     */
    public $f_year;

    /**
     * @var string $f_year_link
     */
    public $f_year_link;

    /**
     * @var string $f_title
     */
    public $f_title;

    /**
     * @var string $f_release_type
     */
    public $f_release_type;

    // cached information

    /**
     * @var boolean $_fake
     */
    public $_fake;

    /**
     * @var array $_songs
     */
    public $_songs = array();

    /**
     * @var array $_mapcache
     */
    private static $_mapcache = array();

    /**
     * @var array $album_suite
     */
    public $album_suite = array();

    /**
     * @var boolean $allow_group_disks
     */
    public $allow_group_disks = false;

    /**
     * __construct
     * Album constructor it loads everything relating
     * to this album from the database it does not
     * pull the album or thumb art by default or
     * get any of the counts.
     * @param integer $album_id
     */
    public function __construct($album_id)
    {
        /* Get the information from the db */
        $info = $this->get_info($album_id);

        if (empty($info)) {
            return false;
        }

        // Foreach what we've got
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        // Little bit of formatting here
        $this->f_name         = trim(trim((string) $info['prefix']) . ' ' . trim((string) $info['name']));
        $this->total_duration = (int)$this->time;
        $this->object_cnt     = (int)$this->total_count;
        $this->addition_time  = (int)$this->addition_time;
        $this->song_count     = (int)$this->song_count;
        $this->artist_count   = (int)$this->artist_count;


        // Looking for other albums with same mbid, ordering by disk ascending
        if (AmpConfig::get('album_group')) {
            $albumRepository         = $this->getAlbumRepository();
            $this->album_suite       = $albumRepository->getAlbumSuite($this);
            $this->allow_group_disks = true;
            // don't reset and query if it's all going to be the same
            if (count($this->album_suite) > 1) {
                $this->total_duration    = 0;
                $this->object_cnt        = 0;
                $this->song_count        = 0;
                $this->artist_count      = 0;

                foreach ($this->album_suite as $albumId) {
                    $this->total_duration += $albumRepository->getAlbumDuration((int)$albumId);
                    $this->object_cnt += $albumRepository->getAlbumPlayCount((int)$albumId);
                    $this->song_count += $albumRepository->getSongCount((int)$albumId);
                    $this->artist_count += $albumRepository->getArtistCount((int)$albumId);
                }
            }
        }

        return true;
    } // constructor

    public function getId(): int
    {
        return (int)$this->id;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * build_cache
     * This takes an array of object ids and caches all of their information
     * with a single query
     * @param array $ids
     * @return boolean
     */
    public static function build_cache(array $ids)
    {
        if (empty($ids)) {
            return false;
        }
        $idlist     = '(' . implode(',', $ids) . ')';
        $sql        = "SELECT * FROM `album` WHERE `id` IN $idlist";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('album', $row['id'], $row);
        }

        return true;
    } // build_cache

    /**
     * _get_extra_info
     * This pulls the extra information from our tables, this is a 3 table join, which is why we don't normally
     * do it
     * @param string $limit_threshold
     * @return array
     */
    private function _get_extra_info($limit_threshold = '')
    {
        if (!$this->id) {
            return array();
        }
        if (parent::is_cached('album_extra', $this->id)) {
            return parent::get_from_cache('album_extra', $this->id);
        }

        if (!$this->album_artist && $this->artist_count == 1) {
            $sql        = "SELECT MIN(`song`.`id`) AS `song_id`, `artist`.`name` AS `artist_name`, `artist`.`prefix` AS `artist_prefix`, MIN(`artist`.`id`) AS `artist_id` FROM `song` INNER JOIN `artist` ON `artist`.`id`=`song`.`artist` WHERE `song`.`album` = " . $this->id . " GROUP BY `song`.`album`, `artist`.`prefix`, `artist`.`name`";
            $db_results = Dba::read($sql);
            $results    = Dba::fetch_assoc($db_results);
            // overwrite so you can get something
            $this->album_artist = $results['artist_id'];
        }
        $art = new Art($this->id, 'album');
        $art->has_db_info();
        $results['has_art']   = make_bool($art->raw);
        $results['has_thumb'] = make_bool($art->thumb);

        if (AmpConfig::get('show_played_times')) {
            $results['object_cnt'] = $this->object_cnt;
        }

        parent::add_to_cache('album_extra', $this->id, $results);

        return $results;
    } // _get_extra_info

    /**
     * check
     *
     * Searches for an album; if none is found, insert a new one.
     * @param integer $catalog
     * @param string $name
     * @param integer $year
     * @param integer $disk
     * @param string $mbid
     * @param string $mbid_group
     * @param string $album_artist
     * @param string $release_type
     * @param string $release_status
     * @param integer $original_year
     * @param string $barcode
     * @param string $catalog_number
     * @param boolean $readonly
     * @return integer
     */
    public static function check($catalog, $name, $year = 0, $disk = 1, $mbid = null, $mbid_group = null, $album_artist = null, $release_type = null, $release_status = null, $original_year = 0, $barcode = null, $catalog_number = null, $readonly = false)
    {
        $trimmed        = Catalog::trim_prefix(trim((string) $name));
        $name           = $trimmed['string'];
        $prefix         = $trimmed['prefix'];
        $album_artist   = (int)$album_artist;
        $album_artist   = ($album_artist < 1) ? null : $album_artist;
        $mbid           = empty($mbid) ? null : $mbid;
        $mbid_group     = empty($mbid_group) ? null : $mbid_group;
        $release_type   = empty($release_type) ? null : $release_type;
        $release_status = empty($release_status) ? null : $release_status;
        $disk           = (self::sanitize_disk($disk) < 1) ? 1 : self::sanitize_disk($disk);
        $original_year  = ((int)substr((string)$original_year, 0, 4) < 1) ? null : substr((string)$original_year, 0, 4);
        $barcode        = empty($barcode) ? null : $barcode;
        $catalog_number = empty($catalog_number) ? null : $catalog_number;

        if (!$name) {
            $name          = T_('Unknown (Orphaned)');
            $year          = 0;
            $original_year = 0;
            $disk          = 1;
            $album_artist  = null;
        }
        if (isset(self::$_mapcache[$name][$disk][$year][$original_year][$mbid][$mbid_group][$album_artist])) {
            return self::$_mapcache[$name][$disk][$year][$original_year][$mbid][$mbid_group][$album_artist];
        }

        $sql    = "SELECT MIN(`album`.`id`) AS `id` FROM `album` WHERE (`album`.`name` = ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) = ?) AND `album`.`disk` = ? AND `album`.`year` = ? ";
        $params = array($name, $name, $disk, $year);

        if ($mbid) {
            $sql .= 'AND `album`.`mbid` = ? ';
            $params[] = $mbid;
        } else {
            $sql .= 'AND `album`.`mbid` IS NULL ';
        }
        if ($prefix) {
            $sql .= 'AND `album`.`prefix` = ? ';
            $params[] = $prefix;
        }
        if ($album_artist) {
            $sql .= 'AND `album`.`album_artist` = ? ';
            $params[] = $album_artist;
        }
        if ($original_year) {
            $sql .= 'AND `album`.`original_year` = ? ';
            $params[] = $original_year;
        }
        if ($release_type) {
            $sql .= 'AND `album`.`release_type` = ? ';
            $params[] = $release_type;
        }
        if ($release_status) {
            $sql .= 'AND `album`.`release_status` = ? ';
            $params[] = $release_status;
        }
        $sql .= 'AND `album`.`catalog` = ? ';
        $params[] = $catalog;

        $db_results = Dba::read($sql, $params);

        if ($row = Dba::fetch_assoc($db_results)) {
            $album_id = (int)$row['id'];
            if ($album_id > 0) {
                // cache the album id against it's details
                self::$_mapcache[$name][$disk][$year][$original_year][$mbid][$mbid_group][$album_artist] = $album_id;

                return $album_id;
            }
        }

        if ($readonly) {
            return 0;
        }

        $sql = 'INSERT INTO `album` (`name`, `prefix`, `year`, `disk`, `mbid`, `mbid_group`, `release_type`, `release_status`, `album_artist`, `original_year`, `barcode`, `catalog_number`, `catalog`, `addition_time`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $db_results = Dba::write($sql, array(
            $name,
            $prefix,
            $year,
            $disk,
            $mbid,
            $mbid_group,
            $release_type,
            $release_status,
            $album_artist,
            $original_year,
            $barcode,
            $catalog_number,
            $catalog,
            time()
        ));
        if (!$db_results) {
            return 0;
        }

        $album_id = Dba::insert_id();
        debug_event(self::class, "check album: created {{$album_id}}", 4);
        // map the new id
        Catalog::update_map($catalog, 'album', $album_id);
        // Remove from wanted album list if any request on it
        if (!empty($mbid) && AmpConfig::get('wanted')) {
            try {
                Wanted::delete_wanted_release((string)$mbid);
            } catch (Exception $error) {
                debug_event(self::class, 'Cannot process wanted releases auto-removal check: ' . $error->getMessage(), 2);
            }
        }

        self::$_mapcache[$name][$disk][$year][$original_year][$mbid][$mbid_group][$album_artist] = $album_id;

        return $album_id;
    }

    /**
     * get_http_album_query_ids
     * return the html album parameters with all album suite ids
     * @param string $url_param_name
     * @return string
     */
    public function get_http_album_query_ids($url_param_name)
    {
        if ($this->allow_group_disks) {
            $suite_array = $this->get_group_disks_ids();
        } else {
            $suite_array = array($this->id);
        }

        return $url_param_name . '=' . implode(',', $suite_array);
    }

    /**
     * get_http_album_query_id
     * return the html album parameters for a single album id
     * @param string $url_param_name
     * @return string
     */
    public function get_http_album_query_id($url_param_name)
    {
        return http_build_query(array($url_param_name => array($this->id)));
    }

    /**
     * get_group_disks_ids
     * return all album suite ids or current album if no albums
     * @return integer[]
     */
    public function get_group_disks_ids()
    {
        $suite_array = $this->album_suite;
        if (!count($suite_array)) {
            $suite_array[$this->disk] = $this->id;
        }

        return $suite_array;
    }

    /**
     * format
     * This is the format function for this object. It sets cleaned up
     * album information with the base required
     * f_link, f_name
     * @param boolean $details
     * @param string $limit_threshold
     */
    public function format($details = true, $limit_threshold = '')
    {
        $web_path = AmpConfig::get('web_path');

        $this->f_release_type = ucwords((string)$this->release_type);

        if ($details) {
            /* Pull the advanced information */
            $data = $this->_get_extra_info($limit_threshold);
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }

            if ($this->album_artist) {
                $album_artist = new Artist($this->album_artist);
                $album_artist->format();
                $this->album_artist_name   = $album_artist->name;
                $this->f_album_artist_name = $album_artist->f_name;
                $this->f_album_artist_link = "<a href=\"" . $web_path . "/artists.php?action=show&artist=" . $this->album_artist . "\" title=\"" . scrub_out($this->album_artist_name) . "\">" . $this->f_album_artist_name . "</a>";
            }

            $this->tags   = Tag::get_top_tags('album', $this->id);
            $this->f_tags = Tag::get_display($this->tags, true, 'album');
        }
        $this->link   = $web_path . '/albums.php?action=show&album=' . scrub_out($this->id);
        $this->f_link = "<a href=\"" . $this->link . "\" title=\"" . scrub_out($this->f_name) . "\">" . scrub_out($this->f_name);

        // Looking if we need to combine or display disks
        if ($this->disk && !$this->allow_group_disks && count($this->getAlbumRepository()->getAlbumSuite($this)) > 1) {
            $this->f_link .= " <span class=\"discnb\">[" . T_('Disk') . " " . $this->disk . "]</span>";
        }

        $this->f_link .= "</a>";

        $this->f_title = $this->f_name;
        if ($this->artist_count == '1') {
            $artist              = trim(trim((string)$this->artist_prefix) . ' ' . trim((string)$this->artist_name));
            $this->f_artist_name = $artist;
            $this->f_artist_link = "<a href=\"$web_path/artists.php?action=show&artist=" . $this->album_artist . "\" title=\"" . scrub_out($this->artist_name) . "\">" . $artist . "</a>";
            $this->f_artist      = $artist;
        } else {
            $this->f_artist_link = "<span title=\"$this->artist_count " . T_('Artists') . "\">" . T_('Various') . "</span>";
            $this->f_artist      = T_('Various');
            $this->f_artist_name = $this->f_artist;
        }

        if (!$this->year) {
            $this->f_year = "N/A";
        } else {
            $year              = $this->year;
            $this->f_year_link = "<a href=\"$web_path/search.php?type=album&action=search&limit=0rule_1=year&rule_1_operator=2&rule_1_input=" . $year . "\">" . $year . "</a>";
        }
    } // format

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        $keywords               = array();
        $keywords['mb_albumid'] = array(
            'important' => false,
            'label' => T_('Album MusicBrainzID'),
            'value' => $this->mbid
        );
        $keywords['mb_albumid_group'] = array(
            'important' => false,
            'label' => T_('Release Group MusicBrainzID'),
            'value' => $this->mbid_group
        );
        $keywords['artist'] = array(
            'important' => true,
            'label' => T_('Artist'),
            'value' => ($this->f_album_artist_name)
        );
        $keywords['album'] = array(
            'important' => true,
            'label' => T_('Album'),
            'value' => $this->f_name
        );
        $keywords['year'] = array(
            'important' => false,
            'label' => T_('Year'),
            'value' => $this->year
        );

        return $keywords;
    }

    /**
     * Get item fullname.
     * @return string
     */
    public function get_fullname()
    {
        return $this->f_name;
    }

    /**
     * Get parent item description.
     * @return array|null
     */
    public function get_parent()
    {
        if ($this->artist_count == 1) {
            return array('object_type' => 'artist', 'object_id' => $this->album_artist);
        }

        return null;
    }

    /**
     * Get item children.
     * @return array
     */
    public function get_childrens()
    {
        return $this->get_medias();
    }

    /**
     * Search for item childrens.
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        $search                    = array();
        $search['type']            = "song";
        $search['rule_0_input']    = $name;
        $search['rule_0_operator'] = 4;
        $search['rule_0']          = "title";
        $search['rule_1_input']    = $this->name;
        $search['rule_1_operator'] = 4;
        $search['rule_1']          = "album";
        $search['rule_2_input']    = $this->album_artist_name;
        $search['rule_2_operator'] = 4;
        $search['rule_2']          = "artist";
        $songs                     = Search::run($search);

        $childrens = array();
        foreach ($songs as $song_id) {
            $childrens[] = array(
                'object_type' => 'song',
                'object_id' => $song_id
            );
        }

        return $childrens;
    }

    /**
     * Get all children and sub-childrens media.
     * @param string $filter_type
     * @return array
     */
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if (!$filter_type || $filter_type == 'song') {
            $songs = $this->getSongRepository()->getByAlbum($this->id);
            foreach ($songs as $song_id) {
                $medias[] = array(
                    'object_type' => 'song',
                    'object_id' => $song_id
                );
            }
        }

        return $medias;
    }

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return integer[]
     */
    public function get_catalogs()
    {
        return array($this->catalog);
    }

    /**
     * Get item's owner.
     * @return integer|null
     */
    public function get_user_owner()
    {
        if (!$this->album_artist) {
            return null;
        }

        $artist = new Artist($this->album_artist);

        return $artist->get_user_owner();
    }

    /**
     * Get default art kind for this item.
     * @return string
     */
    public function get_default_art_kind()
    {
        return 'default';
    }

    /**
     * get_id_array
     *
     * Get info from the album table with the minimum detail required for subsonic
     * @param integer $album_id
     * @return array
     */
    public static function get_id_array($album_id)
    {
        $sql          = "SELECT DISTINCT `album`.`id`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) AS `f_name`, `album`.`name`, `album`.`album_artist` FROM `album` WHERE `album`.`id` = ? ORDER BY `album`.`name`";
        $db_results   = Dba::read($sql, array($album_id));
        $results      = array();

        while ($row = Dba::fetch_assoc($db_results, false)) {
            $results[] = $row;
        }

        return $results;
    }

    /**
     * get_child_ids
     *
     * Get each song id for the album
     * @return int[]
     */
    public function get_child_ids()
    {
        $results    = array();
        $album_list = $this->album_suite;
        if (empty($album_list)) {
            $album_list = array($this->id);
        }
        $sql = (AmpConfig::get('catalog_disable'))
            ? "SELECT DISTINCT `song`.`id` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `song`.`album` = ? AND `catalog`.`enabled` = '1'"
            : "SELECT DISTINCT `song`.`id` FROM `song` WHERE `song`.`album` = ?";
        foreach ($album_list as $album_id) {
            $db_results = Dba::read($sql, array($album_id));

            while ($row = Dba::fetch_assoc($db_results, false)) {
                $results[] = (int)$row['id'];
            }
        }

        return $results;
    }

    /**
     * get_description
     * @return string
     */
    public function get_description()
    {
        // Album description is not supported yet, always return artist description
        $artist = new Artist($this->album_artist);

        return $artist->get_description();
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        $album_id = null;
        $type     = null;

        if (Art::has_db($this->id, 'album')) {
            $album_id = $this->id;
            $type     = 'album';
        } else {
            if (Art::has_db($this->album_artist, 'artist') || $force) {
                $album_id = $this->album_artist;
                $type     = 'artist';
            }
        }

        if ($album_id !== null && $type !== null) {
            $title = '[' . ($this->f_album_artist_name ?: $this->f_artist) . '] ' . $this->f_name;
            Art::display($type, $album_id, $title, $thumb, $this->link);
        }
    }

    /**
     * update
     * This function takes a key'd array of data and updates this object
     * as needed
     * @param array $data
     * @return integer
     */
    public function update(array $data)
    {
        $name           = (isset($data['name'])) ? $data['name'] : $this->name;
        $album_artist   = (isset($data['album_artist'])) ? (int) $data['album_artist'] : $this->album_artist;
        $year           = (isset($data['year'])) ? $data['year'] : $this->year;
        $disk           = (self::sanitize_disk($data['disk']) > 0) ? self::sanitize_disk($data['disk']) : $this->disk;
        $mbid           = (isset($data['mbid'])) ? $data['mbid'] : $this->mbid;
        $mbid_group     = (isset($data['mbid_group'])) ? $data['mbid_group'] : $this->mbid_group;
        $release_type   = (isset($data['release_type'])) ? $data['release_type'] : $this->release_type;
        $release_status = (isset($data['release_status'])) ? $data['release_status'] : $this->release_status;
        $barcode        = (isset($data['barcode'])) ? $data['barcode'] : $this->barcode;
        $catalog_number = (isset($data['catalog_number'])) ? $data['catalog_number'] : $this->catalog_number;
        $original_year  = (isset($data['original_year'])) ? $data['original_year'] : $this->original_year;

        // If you have created an album_artist using 'add new...' we need to create a new artist
        if (!empty($data['album_artist_name'])) {
            $album_artist = Artist::check($data['album_artist_name']);
            self::update_field('album_artist', $album_artist, $this->id);
        }

        $current_id = $this->id;
        $updated    = false;
        $ndata      = array();
        $changed    = array();
        $songs      = $this->getSongRepository()->getByAlbum($this->getId());
        // run an album check on the current object READONLY means that it won't insert a new album
        $album_id   = self::check($this->catalog, $name, $year, $disk, $mbid, $mbid_group, $album_artist, $release_type, $release_status, $original_year, $barcode, $catalog_number, true);
        $cron_cache = AmpConfig::get('cron_cache');
        if ($album_id > 0 && $album_id != $this->id) {
            debug_event(self::class, "Updating $this->id to new id and migrating stats {" . $album_id . '}.', 4);

            foreach ($songs as $song_id) {
                Song::update_album($album_id, $song_id, $this->id);
                Song::update_year($year, $song_id);
                Song::update_utime($song_id);

                $this->getSongId3TagWriter()->write(new Song($song_id));
            }
            $current_id = $album_id;
            $updated    = true;
            Stats::migrate('album', $this->id, $album_id);
            Useractivity::migrate('album', $this->id, $album_id);
            Recommendation::migrate('album', $this->id, $album_id);
            Share::migrate('album', $this->id, $album_id);
            Shoutbox::migrate('album', $this->id, $album_id);
            Tag::migrate('album', $this->id, $album_id);
            Userflag::migrate('album', $this->id, $album_id);
            Rating::migrate('album', $this->id, $album_id);
            Art::duplicate('album', $this->id, $album_id);
            Catalog::migrate_map('album', $this->id, $album_id);
            if (!$cron_cache) {
                $this->getAlbumRepository()->collectGarbage();
            }
        } else {
            // run updates on the single fields
            if (!empty($name) && $name != $this->f_name) {
                $trimmed          = Catalog::trim_prefix(trim((string) $name));
                $new_name         = $trimmed['string'];
                $aPrefix          = $trimmed['prefix'];
                $ndata['album']   = $name;
                $changed['album'] = 'album';

                self::update_field('name', $new_name, $this->id);
                self::update_field('prefix', $aPrefix, $this->id);
            }

            if (empty($data['album_artist_name']) && !empty($album_artist) && $album_artist != $this->album_artist) {
                self::update_field('album_artist', $new_name, $this->id);
            }
            if (!empty($year) && $year != $this->year) {
                self::update_field('year', $year, $this->id);
                foreach ($songs as $song_id) {
                    Song::update_year($year, $song_id);

                    $this->getSongId3TagWriter()->write(new Song($song_id));
                }
            }
            if (!empty($disk) && $disk != $this->disk) {
                self::update_field('disk', $disk, $this->id);
            }
            if (!empty($mbid) && $mbid != $this->mbid) {
                self::update_field('mbid', $mbid, $this->id);
            }
            if (!empty($mbid_group) && $mbid_group != $this->mbid_group) {
                self::update_field('mbid_group', $mbid_group, $this->id);
            }
            if (!empty($release_type) && $release_type != $this->release_type) {
                self::update_field('release_type', $release_type, $this->id);
            }
            if (!empty($catalog_number) && $catalog_number != $this->catalog_number) {
                self::update_field('catalog_number', $catalog_number, $this->id);
            }
            if (!empty($barcode) && $barcode != $this->barcode) {
                self::update_field('barcode', $barcode, $this->id);
            }
            if (!empty($original_year) && $original_year != $this->original_year) {
                self::update_field('original_year', $original_year, $this->id);
            }
        }

        $this->year           = $year;
        $this->mbid_group     = $mbid_group;
        $this->release_type   = $release_type;
        $this->name           = $name;
        $this->disk           = $disk;
        $this->mbid           = $mbid;
        $this->album_artist   = $album_artist;
        $this->original_year  = $original_year;
        $this->barcode        = $barcode;
        $this->catalog_number = $catalog_number;

        if ($updated && is_array($songs)) {
            foreach ($songs as $song_id) {
                Song::update_utime($song_id);
            } // foreach song of album
            if (!$cron_cache) {
                Stats::garbage_collection();
                Rating::garbage_collection();
                Userflag::garbage_collection();
                $this->getUseractivityRepository()->collectGarbage();
            }
        } // if updated

        $override_childs = false;
        if ($data['overwrite_childs'] == 'checked') {
            $override_childs = true;
        }

        $add_to_childs = false;
        if ($data['add_to_childs'] == 'checked') {
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
    } // update

    /**
     * Update an album field.
     * @param string $field
     * @param $value
     * @param integer $album_id
     * @return PDOStatement|boolean
     */
    private static function update_field($field, $value, $album_id)
    {
        $sql = "UPDATE `album` SET `" . $field . "` = ? WHERE `id` = ?";

        return Dba::write($sql, array($value, $album_id));
    }

    /**
     * update_album_artist
     *
     * find albums that are missing an album_artist and generate one.
     * @param array $album_ids
     */
    public static function update_album_artist($album_ids = array())
    {
        $results = $album_ids;
        if (empty($results)) {
            // Find all albums that are missing an album artist
            $sql        = "SELECT `id` FROM `album` WHERE `album_artist` IS NULL AND `name` != 'Unknown (Orphaned)'";
            $db_results = Dba::read($sql);
            while ($row = Dba::fetch_assoc($db_results)) {
                $results[] = (int) $row['id'];
            }
        }
        foreach ($results as $album_id) {
            $artists    = array();
            $sql        = "SELECT MIN(`artist`) FROM `song` WHERE `album` = ? GROUP BY `album` HAVING COUNT(DISTINCT `artist`) = 1 LIMIT 1";
            $db_results = Dba::read($sql, array($album_id));

            // these are albums that only have 1 artist
            while ($row = Dba::fetch_assoc($db_results)) {
                $artists[] = (int) $row['artist'];
            }

            // Update the album
            if (!empty($artists)) {
                debug_event(self::class, 'Found album_artist {' . $artists[0] . '} for: ' . $album_id, 5);
                Album::update_field('album_artist', $artists[0], $album_id);
            }
        }
    }

    /**
     * update_album_counts
     *
     * @param integer $album_id
     */
    public static function update_album_counts($album_id)
    {
        if ($album_id > 0) {
            $params = array($album_id);
            // album.time
            $sql = "UPDATE `album`, (SELECT sum(`song`.`time`) as `time`, `song`.`album` FROM `song` WHERE `song`.`album` = ? GROUP BY `song`.`album`) AS `song` SET `album`.`time` = `song`.`time` WHERE `album`.`id` = `song`.`album` AND `album`.`time` != `song`.`time`;";
            Dba::write($sql, $params);
            // album.total_count
            $sql = "UPDATE `album`, (SELECT COUNT(`object_count`.`object_id`) AS `total_count`, `object_id` FROM `object_count` WHERE `object_count`.`object_id` = ? AND `object_count`.`object_type` = 'album' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id`) AS `object_count` SET `album`.`total_count` = `object_count`.`total_count` WHERE `album`.`total_count` != `object_count`.`total_count` AND `album`.`id` = `object_count`.`object_id`;";
            Dba::write($sql, $params);
            // album.addition_time
            $sql = "UPDATE `album`, (SELECT MIN(`song`.`addition_time`) AS `addition_time`, `song`.`album` FROM `song` WHERE `song`.`album` = ? GROUP BY `song`.`album`) AS `song` SET `album`.`addition_time` = `song`.`addition_time` WHERE `album`.`addition_time` != `song`.`addition_time` AND `song`.`album` = `album`.`id`;";
            Dba::write($sql, $params);
            // album.song_count
            $sql = "UPDATE `album`, (SELECT COUNT(`song`.`id`) AS `song_count`, `album` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `song`.`album` = ? AND `catalog`.`enabled` = '1' GROUP BY `album`) AS `song` SET `album`.`song_count` = `song`.`song_count` WHERE `album`.`song_count` != `song`.`song_count` AND `album`.`id` = `song`.`album`;";
            Dba::write($sql, $params);
            // album.artist_count
            $sql = "UPDATE `album`, (SELECT COUNT(DISTINCT(`song`.`artist`)) AS `artist_count`, `album` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `song`.`album` = ? AND `catalog`.`enabled` = '1' GROUP BY `album`) AS `song` SET `album`.`artist_count` = `song`.`artist_count` WHERE `album`.`artist_count` != `song`.`artist_count` AND `album`.`id` = `song`.`album`;";
            Dba::write($sql, $params);
        }
    }

    /**
     * sanitize_disk
     * Change letter disk numbers (like vinyl/cassette) to an integer
     * @param string|integer $disk
     * @return integer
     */
    public static function sanitize_disk($disk)
    {
        $alphabet = range('A', 'Z');
        if ((int)$disk == 0) {
            // A is 0 but we want to start at disk 1
            $disk = (int)array_search(strtoupper((string)$disk), $alphabet) + 1;
        }

        return (int)$disk;
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
    private function getSongId3TagWriter(): SongId3TagWriterInterface
    {
        global $dic;

        return $dic->get(SongId3TagWriterInterface::class);
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
