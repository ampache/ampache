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
     * @var integer $object_cnt
     */
    public $object_cnt;

    /**
     * @var integer $total_count
     */
    private $total_count;

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

        $this->catalog_id = $catalog_init;
        /* Get the information from the db */
        $info = $this->get_info($artist_id);

        foreach ($info as $key => $value) {
            $this->$key = $value;
        } // foreach info

        // set the full name
        $this->f_name = trim(trim((string) $info['prefix']) . ' ' . trim((string) $info['name']));
        // make sure the int values are cast to integers
        $this->object_cnt        = (int)$this->total_count;
        $this->time              = (int)$this->time;
        $this->album_count       = (int)$this->album_count;
        $this->album_group_count = (int)$this->album_group_count;
        $this->song_count        = (int)$this->song_count;

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
        Dba::write('DELETE FROM `artist` WHERE `artist`.`id` NOT IN (SELECT `song`.`artist` FROM `song`) AND `artist`.`id` NOT IN (SELECT `album`.`album_artist` FROM `album`) AND `artist`.`id` NOT IN (SELECT `wanted`.`artist` FROM `wanted`) AND `artist`.`id` NOT IN (SELECT `clip`.`artist` FROM `clip`)');
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
            $sql = "SELECT `song`.`artist` FROM `song` WHERE `song`.`artist` IN $idlist";

            //debug_event("artist.class", "build_cache sql: " . $sql, 5);
            $db_results = Dba::read($sql);

            while ($row = Dba::fetch_assoc($db_results)) {
                $row['object_cnt'] = (!empty($limit_threshold))
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
        $sql        = "SELECT SUM(`song`.`time`) AS `time` from `song` WHERE `song`.`artist` = ?";
        $db_results = Dba::read($sql, $params);
        $results    = Dba::fetch_assoc($db_results);
        // album artists that don't have any songs
        if ((int) $results['time'] == 0) {
            $sql        = "SELECT SUM(`album`.`time`) AS `time` from `album` WHERE `album`.`album_artist` = ?";
            $db_results = Dba::read($sql, $params);
            $results    = Dba::fetch_assoc($db_results);
        }

        return (int) $results['time'];
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
        $sql        = "SELECT COUNT(`song`.`id`) AS `song_count` from `song` WHERE `song`.`artist` = ?";
        $db_results = Dba::read($sql, $params);
        $results    = Dba::fetch_assoc($db_results);

        return (int) $results['song_count'];
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
        $sql        = "SELECT COUNT(DISTINCT `album`.`id`) AS `album_count` FROM `album` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `album`.`album_artist` = ? AND `catalog`.`enabled` = '1'";
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
        $sql        = "SELECT COUNT(DISTINCT CONCAT(COALESCE(`album`.`prefix`, ''), `album`.`name`, COALESCE(`album`.`album_artist`, ''), COALESCE(`album`.`mbid`, ''), COALESCE(`album`.`year`, ''))) AS `album_count` FROM `album` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `album`.`album_artist` = ? AND `catalog`.`enabled` = '1'";
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
        $group_column = (AmpConfig::get('album_group')) ? '`artist`.`album_group_count`' : '`artist`.`album_count`';
        if (!empty($catalogs)) {
            $sql        = "SELECT DISTINCT `artist`.`id`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `f_name`, `artist`.`name`, $group_column AS `album_count`, `artist`.`song_count` FROM `artist` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = 'artist' AND `catalog_map`.`object_id` = `artist`.`id` WHERE `catalog_map`.`catalog_id` = ? ORDER BY `artist`.`name`";
            $db_results = Dba::read($sql, $catalogs);
        } else {
            $sql        = "SELECT DISTINCT `artist`.`id`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `f_name`, `artist`.`name`, $group_column AS `album_count`, `artist`.`song_count` FROM `artist` ORDER BY `artist`.`name`";
            $db_results = Dba::read($sql);
        }
        $results = array();

        while ($row = Dba::fetch_assoc($db_results, false)) {
            $results[] = $row;
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
        $sql          = "SELECT DISTINCT `artist`.`id`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `f_name`, `artist`.`name`, $group_column AS `album_count`, `artist`.`song_count` FROM `artist` WHERE `artist`.`id` = ? ORDER BY `artist`.`name`";
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
        $sql        = "SELECT DISTINCT `album`.`id` FROM `album` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `album`.`album_artist` = ? AND `catalog`.`enabled` = '1'";
        $db_results = Dba::read($sql, array($this->id));
        $results    = array();

        while ($row = Dba::fetch_assoc($db_results, false)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     * _get_extra info
     * This returns the extra information for the artist, this means totals etc
     * @param integer $catalog
     * @param string $limit_threshold
     * @return array
     */
    private function _get_extra_info($catalog = 0, $limit_threshold = '')
    {
        // Try to find it in the cache and save ourselves the trouble
        if (parent::is_cached('artist_extra', $this->id)) {
            $row = parent::get_from_cache('artist_extra', $this->id);
        } else {
            $params = array($this->id);
            // Get associated information from first song only
            $sql  = "SELECT `song`.`artist`, `song`.`catalog` as `catalog_id`, `artist`.`total_count` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` LEFT JOIN `artist` ON `artist`.`id` = `song`.`artist` ";
            $sqlw = "WHERE `song`.`artist` = ? ";
            if ($catalog) {
                $params[] = $catalog;
                $sqlw .= "AND (`song`.`catalog` = ?) ";
            }
            $sql .= $sqlw . "LIMIT 1";

            $db_results = Dba::read($sql, $params);
            $row        = Dba::fetch_assoc($db_results);

            if (AmpConfig::get('show_played_times')) {
                $row['object_cnt'] = (!empty($limit_threshold))
                    ? Stats::get_object_count('artist', $row['artist'], $limit_threshold)
                    : $row['total_count'];
            }
            parent::add_to_cache('artist_extra', $row['artist'], $row);
        }

        /* Set Object Vars */
        $this->catalog_id = $row['catalog_id'];

        return $row;
    } // _get_extra_info

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

        if ($this->catalog_id) {
            $this->link   = AmpConfig::get('web_path') . '/artists.php?action=show&catalog=' . $this->catalog_id . '&artist=' . $this->id;
            $this->f_link = "<a href=\"" . $this->link . "\" title=\"" . $this->f_name . "\">" . $this->f_name . "</a>";
        } else {
            $this->link   = AmpConfig::get('web_path') . '/artists.php?action=show&artist=' . $this->id;
            $this->f_link = "<a href=\"" . $this->link . "\" title=\"" . $this->f_name . "\">" . $this->f_name . "</a>";
        }

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
            'value' => $this->f_name
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
            Art::display($type, $artist_id, $this->get_fullname(), $thumb, $this->link);
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
        $trimmed = Catalog::trim_prefix(trim((string)$name));
        $name    = $trimmed['string'];
        $prefix  = $trimmed['prefix'];
        // If Ampache support multiple artists per song one day, we should also handle other artists here
        $trimmed = Catalog::trim_featuring($name);
        $name    = $trimmed[0];

        // If Ampache support multiple artists per song one day, we should also handle other artists here
        $mbid = Catalog::trim_slashed_list($mbid);

        if (!$name) {
            $name   = T_('Unknown (Orphaned)');
            $prefix = null;
        }
        if ($name == 'Various Artists') {
            $mbid = '';
        }

        if (isset(self::$_mapcache[$name][$prefix][$mbid])) {
            return self::$_mapcache[$name][$prefix][$mbid];
        }

        $artist_id = 0;
        $exists    = false;
        $matches   = array();

        // check for artists by mbid and split-mbid
        if ($mbid !== '') {
            $sql     = 'SELECT `id` FROM `artist` WHERE `mbid` = ?';
            $matches = VaInfo::get_mbid_array($mbid);
            foreach ($matches as $mbid_string) {
                $db_results = Dba::read($sql, array($mbid_string));

                if (!$exists) {
                    $row       = Dba::fetch_assoc($db_results);
                    $artist_id = (int)$row['id'];
                    $exists    = ($artist_id > 0);
                    $mbid      = ($exists)
                        ? $mbid_string
                        : $mbid;
                }
            }
            // try the whole string if it didn't work
            if (!$exists) {
                $db_results = Dba::read($sql, array($mbid));

                if ($row = Dba::fetch_assoc($db_results)) {
                    $artist_id = (int)$row['id'];
                    $exists    = ($artist_id > 0);
                }
            }
        }
        // search by the artist name and build an array
        if (!$exists) {
            $sql        = 'SELECT `id`, `mbid` FROM `artist` WHERE `name` LIKE ?';
            $db_results = Dba::read($sql, array($name));
            $id_array   = array();
            while ($row = Dba::fetch_assoc($db_results)) {
                $key            = $row['mbid'] ?: 'null';
                $id_array[$key] = $row['id'];
            }
            if (count($id_array)) {
                if ($mbid !== '') {
                    $matches = VaInfo::get_mbid_array($mbid);
                    foreach ($matches as $mbid_string) {
                        // reverse search artist id if it's still not found for some reason
                        if (isset($id_array[$mbid_string]) && !$exists) {
                            $artist_id = (int)$id_array[$mbid_string];
                            $exists    = ($artist_id > 0);
                            $mbid      = ($exists)
                                ? $mbid_string
                                : $mbid;
                        }
                        // update empty artists that match names
                        if (isset($id_array['null']) && !$readonly) {
                            $sql = 'UPDATE `artist` SET `mbid` = ? WHERE `id` = ?';
                            Dba::write($sql, array($mbid_string, $id_array['null']));
                        }
                    }
                    if (isset($id_array['null'])) {
                        if (!$readonly) {
                            $sql = 'UPDATE `artist` SET `mbid` = ? WHERE `id` = ?';
                            Dba::write($sql, array($mbid, $id_array['null']));
                        }
                        $artist_id = (int)$id_array['null'];
                        $exists    = true;
                    }
                } else {
                    // Pick one at random
                    $artist_id = array_shift($id_array);
                    $exists    = true;
                }
            }
        }
        // cache and return the result
        if ($exists) {
            self::$_mapcache[$name][$prefix][$mbid] = $artist_id;

            return (int)$artist_id;
        }
        // if all else fails, insert a new artist, cache it and return the id
        $sql  = 'INSERT INTO `artist` (`name`, `prefix`, `mbid`) ' . 'VALUES(?, ?, ?)';
        $mbid = (!empty($matches)) ? $matches[0] : $mbid; // TODO only use primary mbid until multi-artist is ready

        $db_results = Dba::write($sql, array($name, $prefix, $mbid));
        if (!$db_results) {
            return null;
        }

        $artist_id = (int) Dba::insert_id();
        debug_event(self::class, "check album: created {{$artist_id}}", 4);
        // map the new id
        Catalog::update_map(0, 'artist', $artist_id);

        self::$_mapcache[$name][$prefix][$mbid] = $artist_id;

        return $artist_id;
    }

    /**
     * update
     * This takes a key'd array of data and updates the current artist
     * @param array $data
     * @return integer
     */
    public function update(array $data)
    {
        // Save our current ID
        $name        = isset($data['name']) ? $data['name'] : $this->name;
        $mbid        = isset($data['mbid']) ? $data['mbid'] : $this->mbid;
        $summary     = isset($data['summary']) ? $data['summary'] : $this->summary;
        $placeformed = isset($data['placeformed']) ? $data['placeformed'] : $this->placeformed;
        $yearformed  = isset($data['yearformed']) ? $data['yearformed'] : $this->yearformed;

        $current_id = $this->id;

        // Check if name is different than current name
        if ($this->name != $name) {
            $updated   = false;
            $artist_id = self::check($name, $mbid, true);

            // If it's changed we need to update
            if ($artist_id !== null && $artist_id !== $this->id) {
                $time  = time();
                $songs = $this->getSongRepository()->getByArtist($this->id);
                foreach ($songs as $song_id) {
                    Song::update_artist($artist_id, $song_id, $this->id);
                    Song::update_utime($song_id, $time);
                }
                $updated    = true;
                $current_id = $artist_id;
                Stats::migrate('artist', $this->id, $artist_id);
                Useractivity::migrate('artist', $this->id, $artist_id);
                Recommendation::migrate('artist', $this->id, $artist_id);
                Share::migrate('artist', $this->id, $artist_id);
                Shoutbox::migrate('artist', $this->id, $artist_id);
                Tag::migrate('artist', $this->id, $artist_id);
                Userflag::migrate('artist', $this->id, $artist_id);
                Label::migrate('artist', $this->id, $artist_id);
                Rating::migrate('artist', $this->id, $artist_id);
                Art::duplicate('artist', $this->id, $artist_id);
                Catalog::migrate_map('artist', $this->id, $artist_id);
            } // end if it changed

            // clear out the old data
            if ($updated) {
                self::garbage_collection();
                Stats::garbage_collection();
                Rating::garbage_collection();
                Userflag::garbage_collection();
                Label::garbage_collection();
                $this->getUseractivityRepository()->collectGarbage();
                self::update_artist_counts($current_id);
            } // if updated
        } else {
            if ($this->mbid != $mbid) {
                $sql = 'UPDATE `artist` SET `mbid` = ? WHERE `id` = ?';
                Dba::write($sql, array($mbid, $current_id));
            }
        }

        // Update artist name (if we don't want to use the MusicBrainz name)
        $trimmed = Catalog::trim_prefix(trim((string)$name));
        $name    = $trimmed['string'];
        if ($name != '' && $name != $this->name) {
            $sql = 'UPDATE `artist` SET `name` = ? WHERE `id` = ?';
            Dba::write($sql, array($name, $current_id));
        }

        $this->update_artist_info($summary, $placeformed, $yearformed, true);

        $this->name = $name;
        $this->mbid = $mbid;

        $override_childs = false;
        if ($data['overwrite_childs'] == 'checked') {
            $override_childs = true;
        }

        $add_to_childs = false;
        if ($data['add_to_childs'] == 'checked') {
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
     * update_artist_counts
     *
     * @param integer $artist_id
     */
    public static function update_artist_counts($artist_id)
    {
        if ($artist_id > 0) {
            $params = array($artist_id);
            // artist.time
            $sql = "UPDATE `artist`, (SELECT sum(`song`.`time`) as `time`, `song`.`artist` FROM `song` WHERE `song`.`artist` = ? GROUP BY `song`.`artist`) AS `song` SET `artist`.`time` = `song`.`time` WHERE `artist`.`id` = `song`.`artist` AND `artist`.`time` != `song`.`time`;";
            Dba::write($sql, $params);
            // artist.total_count
            $sql = "UPDATE `artist`, (SELECT COUNT(`object_count`.`object_id`) AS `total_count`, `object_id` FROM `object_count` WHERE `object_count`.`object_id` = ? AND `object_count`.`object_type` = 'artist' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id`) AS `object_count` SET `artist`.`total_count` = `object_count`.`total_count` WHERE `artist`.`total_count` != `object_count`.`total_count` AND `artist`.`id` = `object_count`.`object_id`;";
            Dba::write($sql, $params);
            // artist.album_count
            $sql = "UPDATE `artist`, (SELECT COUNT(DISTINCT `album`.`id`) AS `album_count`, `album_artist` FROM `album` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `album`.`album_artist` = ? AND `catalog`.`enabled` = '1' GROUP BY `album_artist`) AS `album` SET `artist`.`album_count` = `album`.`album_count` WHERE `artist`.`album_count` != `album`.`album_count` AND `artist`.`id` = `album`.`album_artist`;";
            Dba::write($sql, $params);
            // artist.album_group_count
            $sql = "UPDATE `artist`, (SELECT COUNT(DISTINCT CONCAT(COALESCE(`album`.`prefix`, ''), `album`.`name`, COALESCE(`album`.`album_artist`, ''), COALESCE(`album`.`mbid`, ''), COALESCE(`album`.`year`, ''))) AS `album_group_count`, `album_artist` FROM `album` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `album`.`album_artist` = ? AND `catalog`.`enabled` = '1' GROUP BY `album_artist`) AS `album` SET `artist`.`album_group_count` = `album`.`album_group_count` WHERE `artist`.`album_group_count` != `album`.`album_group_count` AND `artist`.`id` = `album`.`album_artist`;";
            Dba::write($sql, $params);
            // artist.song_count
            $sql = "UPDATE `artist`, (SELECT COUNT(`song`.`id`) AS `song_count`, `artist` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `song`.`artist` = ? AND `catalog`.`enabled` = '1' GROUP BY `artist`) AS `song` SET `artist`.`song_count` = `song`.`song_count` WHERE `artist`.`song_count` != `song`.`song_count` AND `artist`.`id` = `song`.`artist`;";
            Dba::write($sql, $params);
        }
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
