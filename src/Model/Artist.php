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
 *
 */

declare(strict_types=0);

namespace Ampache\Model;

use Ampache\Module\Artist\Tag\ArtistTagUpdaterInterface;
use Ampache\Module\Label\LabelListUpdaterInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\Recommendation;
use Ampache\Config\AmpConfig;
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
     * @var string $f_name
     */
    public $f_name;

    /**
     * @var string $f_full_name
     */
    public $f_full_name;

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

        return true;
    } // constructor

    public function getId(): int
    {
        return (int) $this->id;
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
        Dba::write('DELETE FROM `artist` USING `artist` LEFT JOIN `song` ON `song`.`artist` = `artist`.`id` ' . 'LEFT JOIN `album` ON `album`.`album_artist` = `artist`.`id` ' . 'LEFT JOIN `wanted` ON `wanted`.`artist` = `artist`.`id` ' . 'LEFT JOIN `clip` ON `clip`.`artist` = `artist`.`id` ' . 'WHERE `song`.`id` IS NULL AND `album`.`id` IS NULL AND `wanted`.`id` IS NULL AND `clip`.`id` IS NULL');
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
        if ($extra) {
            $sql = "SELECT `song`.`artist`, COUNT(DISTINCT `song`.`id`) AS `song_count`, COUNT(DISTINCT `song`.`album`) AS `album_count`, SUM(`song`.`time`) AS `time` FROM `song` WHERE `song`.`artist` IN $idlist GROUP BY `song`.`artist`";

            //debug_event("artist.class", "build_cache sql: " . $sql, 5);
            $db_results = Dba::read($sql);

            while ($row = Dba::fetch_assoc($db_results)) {
                if (AmpConfig::get('show_played_times')) {
                    $row['object_cnt'] = Stats::get_object_count('artist', $row['artist'], $limit_threshold);
                }
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

        return (int) $results['time'];
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
            // Calculation
            $sql  = "SELECT COUNT(DISTINCT `song`.`id`) AS `song_count`, " . "COUNT(DISTINCT `song`.`album`) AS `album_count`, " . "SUM(`song`.`time`) AS `time` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
            $sqlw = "WHERE `song`.`artist` = ? ";
            if (AmpConfig::get('album_group')) {
                $sql  = "SELECT COUNT(DISTINCT `song`.`id`) AS `song_count`, " . "COUNT(DISTINCT CONCAT(COALESCE(`album`.`prefix`, ''), `album`.`name`, COALESCE(`album`.`album_artist`, ''), COALESCE(`album`.`mbid`, ''), COALESCE(`album`.`year`, ''))) AS `album_count`, " . "SUM(`song`.`time`) AS `time` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` LEFT JOIN `album` ON `album`.`id` = `song`.`album` ";
                $sqlw = "WHERE `song`.`artist` = ? ";
            }
            if ($catalog) {
                $params[] = $catalog;
                $sqlw .= "AND (`song`.`catalog` = ?) ";
            }
            if (AmpConfig::get('catalog_disable')) {
                $sqlw .= " AND `catalog`.`enabled` = '1' ";
            }
            $sql .= $sqlw . "GROUP BY `song`.`artist`";

            $db_results = Dba::read($sql, $params);
            $row        = Dba::fetch_assoc($db_results);

            // Get associated information from first song only
            $sql = "SELECT `song`.`artist`, `song`.`catalog` as `catalog_id` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
            $sql .= $sqlw . "LIMIT 1";

            $db_results = Dba::read($sql, $params);
            $row        = array_merge($row, Dba::fetch_assoc($db_results));

            if (AmpConfig::get('show_played_times')) {
                $row['object_cnt'] = Stats::get_object_count('artist', $row['artist'], $limit_threshold);
            }
            parent::add_to_cache('artist_extra', $row['artist'], $row);
        }

        /* Set Object Vars */
        $this->songs      = $row['song_count'];
        $this->albums     = $row['album_count'];
        $this->time       = $row['time'];
        $this->catalog_id = $row['catalog_id'];

        return $row;
    } // _get_extra_info

    /**
     * format
     * this function takes an array of artist
     * information and reformats the relevent values
     * so they can be displayed in a table for example
     * it changes the title into a full link.
     * @param boolean $details
     * @param string $limit_threshold
     * @return boolean
     */
    public function format($details = true, $limit_threshold = '')
    {
        /* Combine prefix and name, trim then add ... if needed */
        $name              = trim((string)$this->prefix . " " . $this->name);
        $this->f_name      = $name;
        $this->f_full_name = trim(trim((string)$this->prefix) . ' ' . trim((string)$this->name));

        // If this is a memory-only object, we're done here
        if (!$this->id) {
            return true;
        }

        if ($this->catalog_id) {
            $this->link   = AmpConfig::get('web_path') . '/artists.php?action=show&catalog=' . $this->catalog_id . '&artist=' . $this->id;
            $this->f_link = "<a href=\"" . $this->link . "\" title=\"" . $this->f_full_name . "\">" . $name . "</a>";
        } else {
            $this->link   = AmpConfig::get('web_path') . '/artists.php?action=show&artist=' . $this->id;
            $this->f_link = "<a href=\"" . $this->link . "\" title=\"" . $this->f_full_name . "\">" . $name . "</a>";
        }

        if ($details) {
            // Get the counts
            $extra_info = $this->_get_extra_info($this->catalog_id, $limit_threshold);

            // Format the new time thingy that we just got
            $min = sprintf("%02d", (floor($extra_info['time'] / 60) % 60));

            $sec   = sprintf("%02d", ($extra_info['time'] % 60));
            $hours = floor($extra_info['time'] / 3600);

            $this->f_time = ltrim((string)$hours . ':' . $min . ':' . $sec, '0:');

            $this->tags   = Tag::get_top_tags('artist', $this->id);
            $this->f_tags = Tag::get_display($this->tags, true, 'artist');

            if (AmpConfig::get('label')) {
                $this->labels   = $this->getLabelRepository()->getByArtist((int) $this->id);
                $this->f_labels = Label::get_display($this->labels, true);
            }

            $this->object_cnt = $extra_info['object_cnt'];
        }
        if ($this->time == 0) {
            $this->time = $this->update_time();
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
            'value' => $this->f_full_name
        );

        return $keywords;
    }

    /**
     * Get item fullname.
     * @return string
     */
    public function get_fullname()
    {
        return $this->f_full_name;
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
        $albums = $this->getAlbumRepository()->getByArtist($this);
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
            $songs = $this->getSongRepository()->getByArtist($this);
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

        if ($mbid !== '') {
            $sql        = 'SELECT `id` FROM `artist` WHERE `mbid` = ?';
            $db_results = Dba::read($sql, array($mbid));

            if ($row = Dba::fetch_assoc($db_results)) {
                $artist_id = (int)$row['id'];
                $exists    = true;
            }
        }

        if (!$exists) {
            $sql        = 'SELECT `id`, `mbid` FROM `artist` WHERE `name` LIKE ?';
            $db_results = Dba::read($sql, array($name));

            $id_array = array();
            while ($row = Dba::fetch_assoc($db_results)) {
                $key            = $row['mbid'] ?: 'null';
                $id_array[$key] = $row['id'];
            }

            if (count($id_array)) {
                if ($mbid !== '') {
                    if (isset($id_array['null']) && !$readonly) {
                        $sql = 'UPDATE `artist` SET `mbid` = ? WHERE `id` = ?';
                        Dba::write($sql, array($mbid, $id_array['null']));
                    }
                    if (isset($id_array['null'])) {
                        $artist_id = $id_array['null'];
                        $exists    = true;
                    }
                } else {
                    // Pick one at random
                    $artist_id = array_shift($id_array);
                    $exists    = true;
                }
            }
        }

        if ($exists) {
            self::$_mapcache[$name][$prefix][$mbid] = $artist_id;

            return (int)$artist_id;
        }

        $sql = 'INSERT INTO `artist` (`name`, `prefix`, `mbid`) ' . 'VALUES(?, ?, ?)';

        $db_results = Dba::write($sql, array($name, $prefix, $mbid));
        if (!$db_results) {
            return null;
        }

        $artist_id = (int) Dba::insert_id();
        debug_event(self::class, 'Artist check created new artist id `' . $artist_id . '`.', 4);

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
            $updated    = false;
            $songs      = array();
            $artist_id  = self::check($name, $mbid, true);
            $cron_cache = AmpConfig::get('cron_cache');

            // If it's changed we need to update
            if ($artist_id !== null && $artist_id !== $this->id) {
                $songs = $this->getSongRepository()->getByArtist($this);
                foreach ($songs as $song_id) {
                    Song::update_artist($artist_id, $song_id, $this->id);
                }
                $updated    = true;
                $current_id = $artist_id;
                Stats::migrate('artist', $this->id, $artist_id);
                UserActivity::migrate('artist', $this->id, $artist_id);
                Recommendation::migrate('artist', $this->id, $artist_id);
                Share::migrate('artist', $this->id, $artist_id);
                Shoutbox::migrate('artist', $this->id, $artist_id);
                Tag::migrate('artist', $this->id, $artist_id);
                Userflag::migrate('artist', $this->id, $artist_id);
                Rating::migrate('artist', $this->id, $artist_id);
                Art::migrate('artist', $this->id, $artist_id);
                if (!$cron_cache) {
                    self::garbage_collection();
                }
            } // end if it changed

            if ($updated) {
                foreach ($songs as $song_id) {
                    Song::update_utime($song_id);
                }
                if (!$cron_cache) {
                    Stats::garbage_collection();
                    Rating::garbage_collection();
                    Userflag::garbage_collection();
                    $this->getUseractivityRepository()->collectGarbage();
                }
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
        $sql    = "UPDATE `artist` SET `summary` = ?, `placeformed` = ?, `yearformed` = ?, `last_update` = ?, `manual_update` = ? WHERE `id` = ?";
        $sqlret = Dba::write($sql,
            array($summary, $placeformed, Catalog::normalize_year($yearformed), time(), $manual ? 1 : 0, $this->id));

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
     * Update artist last_update time.
     * @param integer $object_id
     */
    public static function set_last_update($object_id)
    {
        $sql = "UPDATE `artist` SET `last_update` = ? WHERE `id` = ?";
        Dba::write($sql, array(time(), $object_id));
    }

    /**
     * update_time
     *
     * Get time for an artist and set it.
     * @return integer
     */
    public function update_time()
    {
        $time = self::get_time((int) $this->id);
        if ($time !== $this->time && $this->id) {
            $sql = "UPDATE `artist` SET `time`=$time WHERE `id`=" . $this->id;
            Dba::write($sql);
        }

        return $time;
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
