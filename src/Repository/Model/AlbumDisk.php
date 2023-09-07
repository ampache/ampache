<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
use Ampache\Module\System\Dba;
use Ampache\Repository\SongRepositoryInterface;

/**
 * This is the class responsible for handling the Album object
 * it is related to the album table in the database.
 */
class AlbumDisk extends database_object implements library_item
{
    protected const DB_TABLENAME = 'album_disk';

    /* Variables from DB */

    /**
     * @var integer $id
     */
    public $id;

    /**
     * @var integer $album_id
     */
    public $album_id;

    /**
     * @var integer $disk
     */
    public $disk;

    /**
     * @var string $disksubtitle
     */
    public $disksubtitle;

    /**
     * @var integer $time
     */
    public $time;

    /**
     * @var integer $song_count
     */
    public $song_count;

    /**
     * @var integer $total_count
     */
    public $total_count;

    /**
     * @var integer $total_duration
     */
    public $total_duration;

    /**
     * @var integer $catalog_id
     */
    public $catalog_id;

    /**
     * @var integer $catalog
     */
    public $catalog;

    /* Variables from parent Album */

    /**
     * @var string $name
     */
    public $name;

    /**
     * @var integer $album_artist
     */
    public $album_artist;

    /**
     * @var array $album_artists
     */
    public array $album_artists;

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
     * @var integer $addition_time
     */
    public $addition_time;

    /**
     * @var integer $original_year
     */
    public $original_year;

    /**
     * @var integer $disk_count
     */
    public $disk_count;

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
     * @var integer $song_artist_count
     */
    public $song_artist_count;

    /**
     * @var bool $has_art
     */
    public $has_art;

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
     * @var string $f_release_type
     */
    public $f_release_type;

    private Album $album;

    /**
     * __construct
     * Album constructor it loads everything relating
     * to this album from the database it does not
     * pull the album or thumb art by default or
     * get any of the counts.
     * @param integer $album_disk_id
     */
    public function __construct($album_disk_id)
    {
        $info = $this->get_info($album_disk_id, static::DB_TABLENAME);
        if (empty($info)) {
            return false;
        }
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
        $this->album = new Album($this->album_id);

        // Little bit of formatting here
        $this->total_duration = (int)$this->time;
        $this->song_count     = (int)$this->song_count;
        $this->disk_count     = (int)$this->disk_count;
        $this->total_count    = (int)$this->total_count;
        // set the album variables just in case
        $this->name           = $this->album->name;
        $this->prefix         = $this->album->prefix;
        $this->mbid           = $this->album->mbid;
        $this->year           = $this->album->year;
        $this->mbid_group     = $this->album->mbid_group;
        $this->release_type   = $this->album->release_type;
        $this->album_artist   = $this->album->album_artist;
        $this->original_year  = $this->album->original_year;
        $this->barcode        = $this->album->barcode;
        $this->catalog_number = $this->album->catalog_number;
        $this->release_status = $this->album->release_status;
        $this->addition_time  = $this->album->addition_time;
        $this->artist_count   = $this->album->artist_count;

        // finally; set up your formatted name
        $this->f_name = $this->get_fullname();

        return true;
    } // constructor

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    public function getAlbumId(): int
    {
        return (int)$this->album_id;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
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
        if (!isset($this->album)) {
            $this->album = new Album($this->album_id);
        }

        $this->f_release_type = $this->album->f_release_type;
        $this->album_artists  = $this->album->get_artists();

        if ($details) {
            $this->tags   = $this->album->tags;
            $this->f_tags = $this->album->f_tags;
        }
        $this->tags   = Tag::get_top_tags('album', $this->album_id);
        $this->f_tags = Tag::get_display($this->tags, true, 'album_disk');
        // set link and f_link
        $this->get_artist_fullname();
        $this->get_f_link();
        $this->get_f_artist_link();

        if (!$this->year) {
            $this->f_year = $this->album->f_year;
        } else {
            $year              = $this->year;
            $this->f_year_link = "<a href=\"$web_path/search.php?type=album_disk&action=search&limit=0rule_1=year&rule_1_operator=2&rule_1_input=" . $year . "\">" . $year . "</a>";
        }
    } // format

    /**
     * does the item have art?
     * @return bool
     */
    public function has_art()
    {
        if (!isset($this->has_art)) {
            $this->has_art = Art::has_db($this->album_id, 'album');
        }

        return $this->has_art;
    }

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
            'value' => ($this->get_artist_fullname())
        );
        $keywords['album'] = array(
            'important' => true,
            'label' => T_('Album'),
            'value' => $this->get_fullname(true)
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
     * @param bool $simple
     * @param bool $force_year
     * @return string
     */
    public function get_fullname($simple = false, $force_year = false)
    {
        // return the basic name without all the wild formatting
        if ($simple) {
            return trim(trim($this->album->prefix ?? '') . ' ' . trim($this->album->name ?? ''));
        }
        if ($force_year) {
            $f_name = trim(trim($this->album->prefix ?? '') . ' ' . trim($this->album->name ?? ''));
            if ($this->album->year > 0) {
                $f_name .= " (" . $this->album->year . ")";
            }
            if ($this->disk_count > 1) {
                $f_name .= " [" . T_('Disk') . " " . $this->disk . "]";
            }

            return $f_name;
        }
        // don't do anything if it's formatted
        if (!isset($this->f_name)) {
            $this->f_name = trim(trim($this->album->prefix ?? '') . ' ' . trim($this->album->name ?? ''));
            // Album pages should show a year and looking if we need to display the release year
            if ($this->album->original_year && AmpConfig::get('use_original_year') && $this->album->original_year != $this->album->year && $this->album->year > 0) {
                $this->f_name .= " (" . $this->album->year . ")";
            }
            if ($this->disk_count > 1) {
                $this->f_name .= " [" . T_('Disk') . " " . $this->disk . "]";
            }
        }

        return $this->f_name;
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
            $this->link = $web_path . '/albums.php?action=show_disk&album_disk=' . scrub_out($this->id);
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
     * Get item f_artist_link.
     * @return string
     */
    public function get_f_artist_link()
    {
        // don't do anything if it's formatted
        if (!isset($this->f_artist_link)) {
            $this->f_artist_link = $this->album->get_f_artist_link();
        }

        return $this->f_artist_link;
    }

    /**
     * get_song_count_disk
     *
     * Returns the song_count id for an album disk
     * @param int $album_id
     * @return int
     */
    public static function get_song_count_disk($album_id, $disk_id)
    {
        $sql        = "SELECT `song_count` FROM `album_disk` WHERE `album_id` = ? AND `disk` = ?;";
        $db_results = Dba::read($sql, array($album_id, $disk_id));
        $results    = Dba::fetch_assoc($db_results);
        if (empty($results)) {
            return 0;
        }

        return (int)$results['song_count'];
    }

    /**
     * Get item album_artist fullname.
     * @return string
     */
    public function get_artist_fullname()
    {
        if (!isset($this->f_artist_name)) {
            $this->f_artist_name = $this->album->get_artist_fullname();
        }

        return $this->f_artist_name;
    }

    /**
     * Get parent item description.
     * @return array|null
     */
    public function get_parent()
    {
        return array('object_type' => 'album', 'object_id' => $this->album_id);
    }

    /**
     * Get parent album artists.
     * @param int $album_id
     * @param int $primary_id
     * @return array
     */
    public static function get_parent_array($album_id, $primary_id)
    {
        $results    = array();
        $sql        = "SELECT DISTINCT `object_id` FROM `album_map` WHERE `object_type` = 'album' AND `album_id` = ?;";
        $db_results = Dba::read($sql, array($album_id));
        //debug_event(self::class, 'get_parent_array ' . $sql, 5);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['object_id'];
        }
        $primary = ((int)$primary_id > 0)
            ? array((int)$primary_id)
            : array();

        return array_unique(array_merge($primary, $results));
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
     * Search for direct children of an object
     * @param string $name
     * @return array
     */
    public function get_children($name)
    {
        debug_event(self::class, 'get_children ' . $name, 5);

        return array();
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
            $songs = $this->getSongRepository()->getByAlbumDisk($this->id);
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
        if (!$this->album->album_artist) {
            return null;
        }

        $artist = new Artist($this->album->album_artist);

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
        $sql          = "SELECT DISTINCT `album_disk`.`id`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) AS `f_name`, `album`.`name`, `album`.`album_artist` FROM `album_disk` LEFT JOIN `album` ON `album`.`id` = `album_disk`.`album_id` WHERE `album_disk`.`id` = ? ORDER BY `album`.`name`";
        $db_results   = Dba::read($sql, array($album_id));
        $results      = array();

        while ($row = Dba::fetch_assoc($db_results, false)) {
            $results[] = $row;
        }

        return $results;
    }

    /**
     * get_songs
     *
     * Get each song id for the album_disk
     * @return int[]
     */
    public function get_songs()
    {
        $results = array();
        $params  = array($this->album_id, $this->disk);
        $sql     = (AmpConfig::get('catalog_disable'))
            ? "SELECT DISTINCT `song`.`id` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `song`.`album` = ? AND `song`.`disk` = ? AND `catalog`.`enabled` = '1'"
            : "SELECT DISTINCT `song`.`id` FROM `song` WHERE `song`.`album` = ? AND `song`.`disk` = ?";
        $db_results = Dba::read($sql, $params);

        while ($row = Dba::fetch_assoc($db_results, false)) {
            $results[] = (int)$row['id'];
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
        $artist = new Artist($this->album->album_artist);

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

        if (Art::has_db($this->album_id, 'album')) {
            $album_id = $this->album_id;
            $type     = 'album';
        } elseif (Art::has_db($this->album->album_artist, 'artist') || $force) {
            $album_id = $this->album->album_artist;
            $type     = 'artist';
        }

        if ($album_id !== null && $type !== null) {
            $title = '[' . ($this->get_artist_fullname() ?? $this->get_artist_fullname()) . '] ' . $this->get_fullname();
            Art::display($type, $album_id, $title, $thumb, $this->get_link());
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
        return $this->id;
    } // update

    /**
     * does the item have a single album artist and song artist?
     * @return int
     */
    public function get_artist_count()
    {
        $sql        = "SELECT COUNT(DISTINCT(`object_id`)) AS `artist_count` FROM `album_map` WHERE `album_id` = ?;";
        $db_results = Dba::read($sql, array($this->id));
        $row        = Dba::fetch_assoc($db_results);
        if (!empty($row)) {
            return (int)$row['artist_count'];
        }

        return 0;
    }

    /**
     * sanitize_disk
     * Change letter disk numbers (like vinyl/cassette) to an integer
     * @param string|integer $disk
     * @return integer
     */
    public static function sanitize_disk($disk)
    {
        if ((int)$disk == 0) {
            // A is 0 but we want to start at disk 1
            $alphabet = range('A', 'Z');
            $disk     = (int)array_search(strtoupper((string)$disk), $alphabet) + 1;
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
}
