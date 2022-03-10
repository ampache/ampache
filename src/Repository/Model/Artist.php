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

use Ampache\Module\Artist\Tag\ArtistTagUpdaterInterface;
use Ampache\Module\Label\LabelListUpdaterInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\Recommendation;
use Ampache\Config\AmpConfig;
use Ampache\Module\Util\VaInfo;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use PDOStatement;

class Artist extends database_object implements library_item, GarbageCollectibleInterface
{
    protected const DB_TABLENAME = 'artist';

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
     * @var string $summary
     */
    public $summary;

    /**
     * @var string $placeformed
     */
    public $placeformed;

    /**
     * @var integer $yearformed
     */
    public $yearformed;

    /**
     * @var integer $last_update
     */
    public $last_update;

    /**
     * @var integer $songs
     */
    public $songs;

    /**
     * @var integer $albums
     */
    public $albums;

    /**
     * @var string $prefix
     */
    public $prefix;

    /**
     * @var string $mbid
     */
    public $mbid; // MusicBrainz ID

    /**
     * @var integer $catalog_id
     */
    public $catalog_id;

    /**
     * @var integer $time
     */
    public $time;

    /**
     * @var integer $user
     */
    public $user;

    /**
     * @var boolean $manual_update
     */
    public $manual_update;

    /**
     * @var array $tags
     */
    public $tags;

    /**
     * @var bool $has_art
     */
    public $has_art;

    /**
     * @var string $f_tags
     */
    public $f_tags;

    /**
     * @var array $labels
     */
    public $labels;

    /**
     * @var string $f_labels
     */
    public $f_labels;

    /**
     * @var integer $total_count
     */
    public $total_count;

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
     * @var string $f_time
     */
    public $f_time;

    // Constructed vars
    /**
     * @var boolean $_fake
     */
    public $_fake = false; // Set if construct_from_array() used

    /**
     * @var integer $album_count
     */
    private $album_count;

    /**
     * @var integer $album_group_count
     */
    private $album_group_count;

    /**
     * @var integer $song_count
     */
    private $song_count;

    /**
     * @var array $_mapcache
     */
    private static $_mapcache = array();

    /**
     * Artist
     * Artist class, for modifying an artist
     * Takes the ID of the artist and pulls the info from the db
     * @param integer|null $artist_id
     * @param integer $catalog_init
     */
    public function __construct($artist_id = null, $catalog_init = 0)
    {
        /* If they failed to pass in an id, just run for it */
        if ($artist_id === null) {
            return false;
        }

        /* Get the information from the db */
        $info = $this->get_info($artist_id);
        if (empty($info)) {
            return false;
        }

        foreach ($info as $key => $value) {
            $this->$key = $value;
        } // foreach info

        // make sure the int values are cast to integers
        $this->total_count       = (int)$this->total_count;
        $this->time              = (int)$this->time;
        $this->album_count       = (int)$this->album_count;
        $this->album_group_count = (int)$this->album_group_count;
        $this->song_count        = (int)$this->song_count;
        $this->catalog_id        = (int)$catalog_init;
        $this->get_fullname();

        return true;
    } // constructor

    public function getId(): int
    {
        return (int) $this->id;
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
     * @return Artist
     */
    public static function construct_from_array($data)
    {
        $artist = new Artist(0);
        foreach ($data as $key => $value) {
            $artist->$key = $value;
        }

        // Ack that this is not a real object from the DB
        $artist->_fake = true;

        return $artist;
    } // construct_from_array

    /**
     * garbage_collection
     *
     * This cleans out unused artists
     */
    public static function garbage_collection()
    {
        Dba::write("DELETE FROM `artist_map` WHERE `object_type` = 'song' AND `object_id` NOT IN (SELECT `id` FROM `song`);");
        Dba::write("DELETE FROM `artist_map` WHERE `object_type` = 'album' AND `object_id` NOT IN (SELECT `id` FROM `album`);");
        Dba::write("DELETE FROM `artist_map` WHERE `artist_id` NOT IN (SELECT `id` FROM `artist`);");
        // delete the artists
        Dba::write("DELETE FROM `artist` WHERE `id` IN (SELECT `id` FROM `artist` LEFT JOIN (SELECT DISTINCT `song`.`artist` AS `artist_id` FROM `song` UNION SELECT DISTINCT `album`.`album_artist` AS `artist_id` FROM `album` UNION SELECT DISTINCT `wanted`.`artist` AS `artist_id` FROM `wanted` UNION SELECT DISTINCT `clip`.`artist` AS `artist_id` FROM `clip` UNION SELECT DISTINCT `artist_id` FROM `artist_map`) AS `artist_map` ON `artist_map`.`artist_id` = `artist`.`id` WHERE `artist_map`.`artist_id` IS NULL);");
        // then clean up remaining artists with data that might creep in
        Dba::write("UPDATE `artist` SET `prefix` = NULL WHERE `prefix` = '';");
        Dba::write("UPDATE `artist` SET `mbid` = NULL WHERE `mbid` = '';");
        Dba::write("UPDATE `artist` SET `summary` = NULL WHERE `summary` = '';");
        Dba::write("UPDATE `artist` SET `placeformed` = NULL WHERE `placeformed` = '';");
        Dba::write("UPDATE `artist` SET `yearformed` = NULL WHERE `yearformed` = 0;");
    }

    /**
     * this attempts to build a cache of the data from the passed albums all in one query
     * @param integer[] $ids
     * @param boolean $extra
     * @param string $limit_threshold
     * @return boolean
     */
    public static function build_cache($ids, $extra = false, $limit_threshold = '')
    {
        if (empty($ids)) {
            return false;
        }
        $idlist     = '(' . implode(',', $ids) . ')';
        $sql        = "SELECT * FROM `artist` WHERE `id` IN $idlist";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('artist', $row['id'], $row);
        }

        // If we need to also pull the extra information, this is normally only used when we are doing the human display
        if ($extra && (AmpConfig::get('show_played_times'))) {
            $sql = "SELECT `song`.`artist`, SUM(`song`.`total_count`) AS `total_count` FROM `song` WHERE `song`.`artist` IN $idlist GROUP BY `song`.`artist`";

            //debug_event(__CLASS__, "build_cache sql: " . $sql, 5);
            $db_results = Dba::read($sql);

            while ($row = Dba::fetch_assoc($db_results)) {
                $row['total_count'] = (!empty($limit_threshold))
                    ? Stats::get_object_count('artist', $row['artist'], $limit_threshold)
                    : $row['total_count'];
                parent::add_to_cache('artist_extra', $row['artist'], $row);
            }
        } // end if extra

        return true;
    } // build_cache

    /**
     * get_from_name
     * This gets an artist object based on the artist name
     * @param string $name
     * @return Artist
     */
    public static function get_from_name($name)
    {
        $sql        = "SELECT `id` FROM `artist` WHERE `name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ? ";
        $db_results = Dba::read($sql, array($name, $name));

        $row = Dba::fetch_assoc($db_results);

        return new Artist($row['id']);
    } // get_from_name

    /**
     * get_time
     *
     * Get time for an artist's songs.
     * @param integer $artist_id
     * @return integer
     */
    public static function get_time($artist_id)
    {
        $params     = array($artist_id);
        $sql        = "SELECT DISTINCT SUM(`song`.`time`) AS `time` FROM `song` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `song`.`id` WHERE `artist_map`.`object_type` = 'song' AND `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'song'";
        $db_results = Dba::read($sql, $params);
        $results    = Dba::fetch_assoc($db_results);
        $total_time = (int) ($results['time'] ?? 0);
        // album artists that don't have any songs
        if ($total_time == 0) {
            $sql        = "SELECT DISTINCT SUM(`album`.`time`) AS `time` FROM `album` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `album`.`id` WHERE `artist_map`.`object_type` = 'album' AND `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'album'";
            $db_results = Dba::read($sql, $params);
            $results    = Dba::fetch_assoc($db_results);
            $total_time = (int) ($results['time'] ?? 0);
        }

        return $total_time;
    }

    /**
     * get_song_count
     *
     * Get count for an artist's songs.
     * @param integer $artist_id
     * @return integer
     */
    public static function get_song_count($artist_id)
    {
        $params     = array($artist_id);
        $sql        = "SELECT DISTINCT COUNT(`song`.`id`) AS `song_count` FROM `song` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `song`.`id` WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'song'";
        $db_results = Dba::read($sql, $params);
        $results    = Dba::fetch_assoc($db_results);

        return (int)($results['song_count'] ?? 0);
    }

    /**
     * get_album_count
     *
     * Get count for an artist's albums.
     * @param integer $artist_id
     * @return integer
     */
    public static function get_album_count($artist_id)
    {
        $params     = array($artist_id);
        $sql        = "SELECT COUNT(DISTINCT `album`.`id`) AS `album_count` FROM `album` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `album`.`id` WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'album' AND `catalog`.`enabled` = '1'";
        $db_results = Dba::read($sql, $params);
        $results    = Dba::fetch_assoc($db_results);

        return (int) $results['album_count'];
    }

    /**
     * get_album_group_count
     *
     * Get count for an artist's albums.
     * @param integer $artist_id
     * @return integer
     */
    public static function get_album_group_count($artist_id)
    {
        $params     = array($artist_id);
        $sql        = "SELECT COUNT(DISTINCT CONCAT(COALESCE(`album`.`prefix`, ''), `album`.`name`, COALESCE(`album`.`album_artist`, ''), COALESCE(`album`.`mbid`, ''), COALESCE(`album`.`year`, ''))) AS `album_count` FROM `album` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `album`.`id` WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'album' AND `catalog`.`enabled` = '1'";
        $db_results = Dba::read($sql, $params);
        $results    = Dba::fetch_assoc($db_results);

        return (int) $results['album_count'];
    }

    /**
     * get_id_arrays
     *
     * Get each id from the artist table with the minimum detail required for subsonic
     * @param array $catalogs
     * @return array
     */
    public static function get_id_arrays($catalogs = array())
    {
        $results       = array();
        $group_column  = (AmpConfig::get('album_group')) ? '`artist`.`album_group_count`' : '`artist`.`album_count`';
        // if you have no catalogs set, just grab it all
        if (!empty($catalogs)) {
            $sql = "SELECT DISTINCT `artist`.`id`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `f_name`, `artist`.`name`, $group_column AS `album_count`, `artist`.`song_count` FROM `artist` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = 'artist' AND `catalog_map`.`object_id` = `artist`.`id` WHERE `catalog_map`.`catalog_id` = ? ORDER BY `artist`.`name`";
            foreach ($catalogs as $catalog_id) {
                $db_results = Dba::read($sql, array($catalog_id));
                while ($row = Dba::fetch_assoc($db_results, false)) {
                    $results[] = $row;
                }
            }
        } else {
            $sql        = "SELECT DISTINCT `artist`.`id`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `f_name`, `artist`.`name`, $group_column AS `album_count`, `artist`.`song_count` FROM `artist` ORDER BY `artist`.`name`";
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
     * @param integer $artist_id
     * @return array
     */
    public static function get_id_array($artist_id)
    {
        $group_column = (AmpConfig::get('album_group')) ? '`artist`.`album_group_count`' : '`artist`.`album_count`';
        $sql          = "SELECT DISTINCT `artist`.`id`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `f_name`, `artist`.`name`, $group_column AS `album_count`, `artist`.`song_count`, `catalog_map`.`catalog_id` FROM `artist` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = 'artist' AND `catalog_map`.`object_id` = `artist`.`id` AND `catalog_map`.`catalog_id` = (SELECT MIN(`catalog_map`.`catalog_id`) FROM `catalog_map` WHERE `catalog_map`.`object_type` = 'artist' AND `catalog_map`.`object_id` = `artist`.`id`) WHERE `artist`.`id` = ? ORDER BY `artist`.`name`";
        $db_results   = Dba::read($sql, array($artist_id));
        $row          = Dba::fetch_assoc($db_results, false);

        return $row;
    }

    /**
     * get_child_ids
     *
     * Get each album id for the artist
     * @return int[]
     */
    public function get_child_ids()
    {
        $sql        = "SELECT DISTINCT `album`.`id` FROM `album` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `album`.`id` WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'album' AND `catalog`.`enabled` = '1'";
        $db_results = Dba::read($sql, array($this->id));
        $results    = array();

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
     * @param boolean $details
     * @param string $limit_threshold
     * @return boolean
     */
    public function format($details = true, $limit_threshold = '')
    {
        // If this is a memory-only object, we're done here
        if (!$this->id) {
            return true;
        }
        $this->songs  = $this->song_count;
        $this->albums = (AmpConfig::get('album_group')) ? $this->album_group_count : $this->album_count;
        // set link and f_link
        $this->get_f_link();

        if ($details) {
            $min   = sprintf("%02d", (floor($this->time / 60) % 60));
            $sec   = sprintf("%02d", ($this->time % 60));
            $hours = floor($this->time / 3600);

            $this->f_time = ltrim((string)$hours . ':' . $min . ':' . $sec, '0:');
            $this->tags   = Tag::get_top_tags('artist', $this->id);
            $this->f_tags = Tag::get_display($this->tags, true, 'artist');

            if (AmpConfig::get('label')) {
                $this->labels   = $this->getLabelRepository()->getByArtist((int) $this->id);
                $this->f_labels = Label::get_display($this->labels, true);
            }
        }

        return true;
    } // format

    /**
     * does the item have art?
     * @return bool
     */
    public function has_art()
    {
        if (!isset($this->has_art)) {
            $this->has_art = Art::has_db($this->id, 'album');
        }

        return $this->has_art;
    }

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        $keywords                = array();
        $keywords['mb_artistid'] = array(
            'important' => false,
            'label' => T_('Artist MusicBrainzID'),
            'value' => $this->mbid
        );
        $keywords['artist'] = array(
            'important' => true,
            'label' => T_('Artist'),
            'value' => $this->get_fullname()
        );

        return $keywords;
    }

    /**
     * Get item fullname.
     * @return string
     */
    public function get_fullname()
    {
        if (!isset($this->f_name)) {
            // set the full name
            $this->f_name = trim(trim($this->prefix . ' ' . trim($this->name)));
        }

        return $this->f_name;
    }

    /**
     * Get item fullname by the artist id.
     * @return string
     */
    public static function get_fullname_by_id($artist_id)
    {
        $sql        = "SELECT LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `f_name` FROM `artist` WHERE `id` = ?;";
        $db_results = Dba::read($sql, array($artist_id));
        if ($row = Dba::fetch_assoc($db_results)) {
            return $row['f_name'];
        }

        return '';
    }

    /**
     * Get item link.
     * @return string
     */
    public function get_link()
    {
        // don't do anything if it's formatted
        if (!isset($this->link)) {
            $web_path   = AmpConfig::get('web_path');
            $this->link = ($this->catalog_id > 0)
                ? $web_path . '/artists.php?action=show&catalog=' . $this->catalog_id . '&artist=' . $this->id
                : $web_path . '/artists.php?action=show&artist=' . $this->id;
        }

        return $this->link;
    }

    /**
     * Get item f_link.
     * @return string
     */
    public function get_f_link()
    {
        // don't do anything if it's formatted
        if (!isset($this->f_link)) {
            $this->f_link = "<a href=\"" . $this->get_link() . "\" title=\"" . scrub_out($this->get_fullname()) . "\">" . scrub_out($this->get_fullname()) . "</a>";
        }

        return $this->f_link;
    }

    /**
     * Get parent item description.
     * @return array|null
     */
    public function get_parent()
    {
        return null;
    }

    /**
     * Get item childrens.
     * @return array
     */
    public function get_childrens()
    {
        $medias = array();
        $albums = $this->getAlbumRepository()->getByArtist($this->id);
        foreach ($albums as $album_id) {
            $medias[] = array(
                'object_type' => 'album',
                'object_id' => $album_id
            );
        }

        return array('album' => $medias);
    }

    /**
     * Search for item childrens.
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        $search                    = array();
        $search['type']            = "album";
        $search['rule_0_input']    = $name;
        $search['rule_0_operator'] = 4;
        $search['rule_0']          = "title";
        $search['rule_1_input']    = $this->name;
        $search['rule_1_operator'] = 4;
        $search['rule_1']          = "artist";
        $albums                    = Search::run($search);

        $childrens = array();
        foreach ($albums as $album_id) {
            $childrens[] = array(
                'object_type' => 'album',
                'object_id' => $album_id
            );
        }

        return $childrens;
    }

    /**
     * Get all childrens and sub-childrens medias.
     * @param string $filter_type
     * @return array
     */
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if ($filter_type === null || $filter_type == 'song') {
            $songs = $this->getSongRepository()->getByArtist($this->id);
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
        return array($this->catalog_id);
    }

    /**
     * Get item's owner.
     * @return integer|null
     */
    public function get_user_owner()
    {
        return $this->user;
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
     * get_description
     * @return string
     */
    public function get_description()
    {
        return $this->summary;
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        $artist_id = null;
        $type      = null;

        if (Art::has_db($this->id, 'artist') || $force) {
            $artist_id = $this->id;
            $type      = 'artist';
        }

        if ($artist_id !== null && $type !== null) {
            Art::display($type, $artist_id, $this->get_fullname(), $thumb, $this->get_link());
        }
    }

    /**
     * check
     *
     * Checks for an existing artist; if none exists, insert one.
     * @param string $name
     * @param string $mbid
     * @param boolean $readonly
     * @return integer|null
     */
    public static function check($name, $mbid = '', $readonly = false)
    {
        $full_name = $name;
        $trimmed   = Catalog::trim_prefix(trim((string)$name));
        $name      = $trimmed['string'];
        $prefix    = $trimmed['prefix'];
        // If Ampache support multiple artists per song one day, we should also handle other artists here
        $trimmed = Catalog::trim_featuring($name);
        if ($name !== $trimmed[0]) {
            debug_event(__CLASS__, "check artist: cut {{$name}} to {{$trimmed[0]}}", 4);
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

        if (!empty($mbid)) {
            // check for artists by mbid (there should only ever be one sent here)
            $sql        = 'SELECT `id` FROM `artist` WHERE `mbid` = ?';
            $db_results = Dba::read($sql, array($mbid));
            if ($row = Dba::fetch_assoc($db_results)) {
                $artist_id = (int)$row['id'];
                $exists    = ($artist_id > 0);
            }
            // still missing? Match on the name and update the mbid
            $sql        = "SELECT `id` FROM `artist` WHERE (`artist`.`name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ?) AND `mbid` IS NULL;";
            $db_results = Dba::read($sql, array($name, $full_name));
            while ($row = Dba::fetch_assoc($db_results)) {
                if (!$readonly) {
                    $sql = 'UPDATE `artist` SET `mbid` = ? WHERE `id` = ?';
                    Dba::write($sql, array($mbid, $row['id']));
                }
            }
        } else {
            // look for artists with no mbid (if they exist) and then match on mbid artists last
            $id_array   = array();
            $sql        = "SELECT `id` FROM `artist` WHERE `mbid` IS NULL AND (`artist`.`name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ?) ORDER BY `id`;";
            $db_results = Dba::read($sql, array($name, $full_name));
            while ($row = Dba::fetch_assoc($db_results)) {
                $id_array[] = (int)$row['id'];
            }
            $sql        = "SELECT `id`, `mbid` FROM `artist` WHERE `mbid` IS NOT NULL AND (`artist`.`name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ?) ORDER BY `id`;";
            $db_results = Dba::read($sql, array($name, $full_name));
            while ($row = Dba::fetch_assoc($db_results)) {
                $id_array[] = (int)$row['id'];
            }
            if (!empty($id_array)) {
                // Pick the first one (nombid and nombid used before matching on mbid)
                $artist_id = $id_array[0];
                $exists    = true;
            }
        }
        // cache and return the result
        if ($exists && (int)$artist_id > 0) {
            self::$_mapcache[$name][$prefix][$mbid] = $artist_id;

            return (int)$artist_id;
        }
        if ($readonly) {
            return null;
        }
        // if all else fails, insert a new artist, cache it and return the id
        $sql  = 'INSERT INTO `artist` (`name`, `prefix`, `mbid`) ' . 'VALUES(?, ?, ?)';
        $mbid = !empty($mbid) ? $mbid : null;

        $db_results = Dba::write($sql, array($name, $prefix, $mbid));
        if (!$db_results) {
            return null;
        }

        $artist_id = (int) Dba::insert_id();
        debug_event(__CLASS__, "check artist: created {{$artist_id}}", 4);
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
     * @return integer
     */
    public static function check_mbid($mbid)
    {
        $artist_id   = 0;
        $parsed_mbid = VaInfo::parse_mbid($mbid);

        // check for artists by mbid and split-mbid
        if ($parsed_mbid) {
            $sql        = 'SELECT `id` FROM `artist` WHERE `mbid` = ?';
            $db_results = Dba::read($sql, array($parsed_mbid));
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
                $db_results = Dba::write($sql, array($name, $prefix, $parsed_mbid));
                if (!$db_results) {
                    return $artist_id;
                }

                $artist_id = (int)Dba::insert_id();
                debug_event(__CLASS__, "check mbid: created {{$artist_id}} " . $data['name'], 4);
            }
        }

        return $artist_id;
    }

    /**
     * Update the artist map for a single item
     */
    public static function update_artist_map($artist_id, $object_type, $object_id)
    {
        if ((int)$artist_id > 0 && (int)$object_id > 0) {
            debug_event(__CLASS__, "update_artist_map artist_id {" . $artist_id . "} $object_type {" . $object_id . "}", 5);
            $sql = "INSERT IGNORE INTO `artist_map` (`artist_id`, `object_type`, `object_id`) VALUES (?, ?, ?);";
            Dba::write($sql, array($artist_id, $object_type, $object_id));
        }
    }

    /**
     * Delete the artist map for a single item
     */
    public static function remove_artist_map($artist_id, $object_type, $object_id)
    {
        if ((int)$artist_id > 0 && (int)$object_id > 0) {
            debug_event(__CLASS__, "remove_artist_map artist_id {" . $artist_id . "} $object_type {" . $object_id . "}", 5);
            $sql = "DELETE FROM `artist_map` WHERE `artist_id` = ? AND `object_type` = ? AND `object_id` = ?;";
            Dba::write($sql, array($artist_id, $object_type, $object_id));
        }
    }

    /**
     * get_artist_map
     *
     * This returns an ids of artists that have songs/albums mapped
     * @param string $object_type
     * @param int $object_id
     * @return integer[]
     */
    public static function get_artist_map($object_type, $object_id)
    {
        $results    = array();
        $sql        = "SELECT `artist_id` AS `artist_id` FROM `artist_map` WHERE `object_type` = ? AND `object_id` = ?";
        $db_results = Dba::read($sql, array($object_type, $object_id));
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['artist_id'];
        }

        return $results;
    }

    /**
     * update_name_from_mbid
     *
     * Refresh your atist name using external data based on the mbid
     * @param string $new_name
     * @param string $mbid
     * @return array
     */
    public static function update_name_from_mbid($new_name, $mbid)
    {
        $trimmed = Catalog::trim_prefix(trim((string)$new_name));
        $name    = $trimmed['string'];
        $prefix  = $trimmed['prefix'];
        $trimmed = Catalog::trim_featuring($name);
        $name    = $trimmed[0];
        debug_event(__CLASS__, "update_name_from_mbid: rename {{$mbid}} to {{$prefix}} {{$name}}", 4);

        $sql = 'UPDATE `artist` SET `prefix` = ?, `name` = ? WHERE `mbid` = ?';
        Dba::write($sql, array($prefix, $name, $mbid));

        return array('name' => $name, 'prefix' => $prefix);
    }

    /**
     * update
     * This takes a key'd array of data and updates the current artist
     * @param array $data
     * @return integer
     */
    public function update(array $data)
    {
        //debug_event(__CLASS__, "update: " . print_r($data, true), 5);
        // Save our current ID
        $prefix      = Catalog::trim_prefix($data['name'])['prefix'] ?? '';
        $name        = Catalog::trim_prefix($data['name'])['string'] ?? $this->name;
        $mbid        = $data['mbid'] ?? $this->mbid;
        $summary     = $data['summary'] ?? $this->summary;
        $placeformed = $data['placeformed'] ?? $this->placeformed;
        $yearformed  = $data['yearformed'] ?? $this->yearformed;
        $current_id  = $this->id;

        // Check if name is different than the current name
        if ($this->prefix != $prefix || $this->name != $name) {
            $updated   = false;
            $artist_id = (int)self::check($name, $mbid, true);

            // If you couldn't find an artist OR you found the current one, just rename it and move on
            if ($artist_id == 0 || ($artist_id > 0 && $artist_id == $current_id)) {
                debug_event(__CLASS__, "updated name: " . $prefix . ' ' . $name, 5);
                $this->update_artist_name($name, $prefix);
            }
            // If it's changed we need to update
            if ($artist_id > 0 && $artist_id != $current_id) {
                debug_event(__CLASS__, "updated: " . $current_id . "  to: " . $artist_id, 5);
                $time  = time();
                $songs = $this->getSongRepository()->getByArtist($this->id);
                foreach ($songs as $song_id) {
                    Song::update_artist($artist_id, $song_id, $this->id);
                    Song::update_utime($song_id, $time);
                }
                $updated    = true;
                $current_id = $artist_id;
                Stats::migrate('artist', $this->id, $artist_id, $song_id);
                Useractivity::migrate('artist', $this->id, $artist_id);
                Recommendation::migrate('artist', $this->id);
                Share::migrate('artist', $this->id, $artist_id);
                Shoutbox::migrate('artist', $this->id, $artist_id);
                Tag::migrate('artist', $this->id, $artist_id);
                Userflag::migrate('artist', $this->id, $artist_id);
                Label::migrate('artist', $this->id, $artist_id);
                Rating::migrate('artist', $this->id, $artist_id);
                Art::duplicate('artist', $this->id, $artist_id);
                Wanted::migrate('artist', $this->id, $artist_id);
                Catalog::migrate_map('artist', $this->id, $artist_id);
            } // end if it changed

            // clear out the old data
            if ($updated) {
                debug_event(__CLASS__, "garbage_collection: " . $artist_id, 5);
                self::garbage_collection();
                Stats::garbage_collection();
                Rating::garbage_collection();
                Userflag::garbage_collection();
                Label::garbage_collection();
                $this->getUseractivityRepository()->collectGarbage();
                self::update_artist_counts($current_id);
            } // if updated
        } elseif ($this->mbid != $mbid) {
            $sql = 'UPDATE `artist` SET `mbid` = ? WHERE `id` = ?';
            Dba::write($sql, array($mbid, $current_id));
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
                $data['edit_tags'],
                $override_childs,
                $add_to_childs,
                true
            );
        }

        if (AmpConfig::get('label') && isset($data['edit_labels'])) {
            $this->getLabelListUpdater()->update(
                $data['edit_labels'],
                (int) $this->id,
                true
            );
        }

        return $current_id;
    } // update

    /**
     * Update artist information.
     * @param string $summary
     * @param string $placeformed
     * @param integer $yearformed
     * @param boolean $manual
     * @return PDOStatement|boolean
     */
    public function update_artist_info($summary, $placeformed, $yearformed, $manual = false)
    {
        // set null values if missing
        $summary     = (empty($summary)) ? null : $summary;
        $placeformed = (empty($placeformed)) ? null : $placeformed;
        $yearformed  = ((int)$yearformed == 0) ? null : Catalog::normalize_year($yearformed);

        $sql     = "UPDATE `artist` SET `summary` = ?, `placeformed` = ?, `yearformed` = ?, `last_update` = ?, `manual_update` = ? WHERE `id` = ?";
        $sqlret  = Dba::write($sql, array($summary, $placeformed, $yearformed, time(), (int)$manual, $this->id));

        $this->summary     = $summary;
        $this->placeformed = $placeformed;
        $this->yearformed  = $yearformed;

        return $sqlret;
    }

    /**
     * Update artist associated user.
     * @param integer $user
     * @return PDOStatement|boolean
     */
    public function update_artist_user($user)
    {
        $sql = "UPDATE `artist` SET `user` = ? WHERE `id` = ?";

        return Dba::write($sql, array($user, $this->id));
    }

    /**
     * Update artist associated user.
     * @param string $name
     * @param string|null $prefix
     * @return PDOStatement|boolean
     */
    public function update_artist_name($name, $prefix)
    {
        $sql = "UPDATE `artist` SET `prefix` = ?, `name` = ? WHERE `id` = ?";

        return Dba::write($sql, array($prefix, $name, $this->id));
    }

    /**
     * update_artist_counts
     *
     */
    public static function update_artist_counts()
    {
        // artist.time
        $sql = "UPDATE `artist`, (SELECT SUM(`song`.`time`) AS `time`, `artist_map`.`artist_id` FROM `song` LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' GROUP BY `artist_map`.`artist_id`) AS `song` SET `artist`.`time` = `song`.`time` WHERE `artist`.`time` != `song`.`time` AND `artist`.`id` = `song`.`artist_id`;";
        Dba::write($sql);
        // artist.total_count
        $sql = "UPDATE `artist`, (SELECT COUNT(`object_count`.`object_id`) AS `total_count`, `artist_map`.`artist_id` FROM `object_count` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `object_count`.`object_id` AND `object_count`.`count_type` = 'stream' WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' GROUP BY `artist_map`.`artist_id`) AS `object_count` SET `artist`.`total_count` = `object_count`.`total_count` WHERE `artist`.`total_count` != `object_count`.`total_count` AND `artist`.`id` = `object_count`.`artist_id`;";
        Dba::write($sql);
        // artist.album_count
        $sql = "UPDATE `artist`, (SELECT COUNT(DISTINCT `album`.`id`) AS `album_count`, `artist_map`.`artist_id` FROM `artist_map` LEFT JOIN `album` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `artist_map`.`artist_id`) AS `album` SET `artist`.`album_count` = `album`.`album_count` WHERE `artist`.`album_count` != `album`.`album_count` AND `artist`.`id` = `album`.`artist_id`;";
        Dba::write($sql);
        // artist.album_group_count
        $sql = "UPDATE `artist`, (SELECT COUNT(DISTINCT CONCAT(COALESCE(`album`.`prefix`, ''), `album`.`name`, COALESCE(`album`.`album_artist`, ''), COALESCE(`album`.`mbid`, ''), COALESCE(`album`.`year`, ''))) AS `album_count`, `artist_map`.`artist_id` FROM `artist_map` LEFT JOIN `album` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `artist_map`.`artist_id`) AS `album` SET `artist`.`album_count` = `album`.`album_count` WHERE `artist`.`album_count` != `album`.`album_count` AND `artist`.`id` = `album`.`artist_id`;";
        Dba::write($sql);
        // artist.song_count
        $sql = "UPDATE `artist`, (SELECT COUNT(`song`.`id`) AS `song_count`, `artist_map`.`artist_id` FROM `artist_map` LEFT JOIN `song` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `artist_map`.`artist_id`) AS `song` SET `artist`.`song_count` = `song`.`song_count` WHERE `artist`.`song_count` != `song`.`song_count` AND `artist`.`id` = `song`.`artist_id`;";
        Dba::write($sql);
    }

    /**
     * Update artist last_update time.
     * @param integer $object_id
     */
    public static function set_last_update($object_id)
    {
        $sql = "UPDATE `artist` SET `last_update` = ? WHERE `id` = ?";
        Dba::write($sql, array(time(), $object_id));
    }

    /**
     * Migrate an object's associate stats to a new object
     * @param integer $old_object_id
     * @param integer $new_object_id
     */
    public static function migrate($old_object_id, $new_object_id)
    {
        $params = array($new_object_id, $old_object_id);
        $sql    = "UPDATE `song` SET `artist` = ? WHERE `artist` = ?;";
        Dba::write($sql, $params);
        $sql = "UPDATE `album` SET `album_artist` = ? WHERE `album_artist` = ?;";
        Dba::write($sql, $params);
        // migrate the maps and delete ones that aren't required
        $sql = "UPDATE IGNORE `artist_map` SET `artist_id` = ? WHERE `artist_id` = ?;";
        Dba::write($sql, $params);
        $sql = "UPDATE IGNORE `album_map` SET `object_id` = ? WHERE `object_id` = ? AND `object_type` = 'album';";
        Dba::write($sql, $params);
        // delete the old one if it's a dupe row above
        $sql = "DELETE FROM `artist_map` WHERE `artist_id` = ?;";
        Dba::write($sql, array($old_object_id));
        $sql = "DELETE FROM `album_map` WHERE `object_id` = ? AND `object_type` = 'album';";
        Dba::write($sql, array($old_object_id));
        self::update_artist_counts($new_object_id);
    }

    /**
     * Migrate an object associated artist to a new object
     * @param string $object_type
     * @param integer $old_object_id
     * @param integer $new_object_id
     * @return PDOStatement|boolean
     */
    public static function migrate_map($object_type, $old_object_id, $new_object_id)
    {
        $sql    = "UPDATE IGNORE `artist_map` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";
        $params = array($new_object_id, $object_type, $old_object_id);

        return Dba::write($sql, $params);
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
}
