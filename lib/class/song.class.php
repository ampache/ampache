<?php
declare(strict_types=0);
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */

use Lib\Metadata\Metadata;

/**
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

class Song extends database_object implements media, library_item
{
    use Metadata;

    /* Variables from DB */

    /**
     * @var integer $id
     */
    public $id;
    /**
     * @var string $file
     */
    public $file;
    /**
     * @var integer $album
     */
    public $album;
    /**
     * @var integer $artist
     */
    public $artist;
    /**
     * @var string $title
     */
    public $title;
    /**
     * @var integer $year
     */
    public $year;
    /**
     * @var integer $bitrate
     */
    public $bitrate;
    /**
     * @var integer $rate
     */
    public $rate;
    /**
     * @var string $mode
     */
    public $mode;
    /**
     * @var integer $size
     */
    public $size;
    /**
     * @var integer $time
     */
    public $time;
    /**
     * @var integer $track
     */
    public $track;
    /**
     * @var string $album_mbid
     */
    public $album_mbid;
    /**
     * @var string $artist_mbid
     */
    public $artist_mbid;
    /**
     * @var string $albumartist_mbid
     */
    public $albumartist_mbid;
    /**
     * @var string $type
     */
    public $type;
    /**
     * @var string $mime
     */
    public $mime;
    /**
     * @var boolean $played
     */
    public $played;
    /**
     * @var boolean $enabled
     */
    public $enabled;
    /**
     * @var integer $addition_time
     */
    public $addition_time;
    /**
     * @var integer $update_time
     */
    public $update_time;
    /**
     * MusicBrainz ID
     * @var string $mbid
     */
    public $mbid;
    /**
     * @var integer $catalog
     */
    public $catalog;
    /**
     * @var integer|null $waveform
     */
    public $waveform;
    /**
     * @var integer|null $user_upload
     */
    public $user_upload;
    /**
     * @var integer|null $license
     */
    public $license;
    /**
     * @var string $composer
     */
    public $composer;
    /**
     * @var string $catalog_number
     */
    public $catalog_number;
    /**
     * @var integer $channels
     */
    public $channels;

    /**
     * @var array $tags
     */
    public $tags;
    /**
     * @var string $label
     */
    public $label;
    /**
     * @var string $language
     */
    public $language;
    /**
     * @var string $comment
     */
    public $comment;
    /**
     * @var string $lyrics
     */
    public $lyrics;
    /**
     * @var float|null $replaygain_track_gain
     */
    public $replaygain_track_gain;
    /**
     * @var float|null $replaygain_track_peak
     */
    public $replaygain_track_peak;
    /**
     * @var float|null $replaygain_album_gain
     */
    public $replaygain_album_gain;
    /**
     * @var float|null $replaygain_album_peak
     */
    public $replaygain_album_peak;
    /**
     * @var integer|null $r128_album_gain
     */
    public $r128_album_gain;
    /**
     * @var integer|null $r128_track_gain
     */
    public $r128_track_gain;
    /**
     * @var string $f_title
     */
    public $f_title;
    /**
     * @var string $f_artist
     */
    public $f_artist;
    /**
     * @var string $f_album
     */
    public $f_album;
    /**
     * @var string $f_artist_full
     */
    public $f_artist_full;
    /**
     * @var integer $albumartist
     */
    public $albumartist;
    /**
     * @var string $f_albumartist_full
     */
    public $f_albumartist_full;
    /**
     * @var string $f_album_full
     */
    public $f_album_full;
    /**
     * @var string $f_time
     */
    public $f_time;
    /**
     * @var string $f_time_h
     */
    public $f_time_h;
    /**
     * @var string $f_track
     */
    public $f_track;
    /**
     * @var string $disk
     */
    public $disk;
    /**
     * @var string $f_bitrate
     */
    public $f_bitrate;
    /**
     * @var string $link
     */
    public $link;
    /**
     * @var string $f_file
     */
    public $f_file;
    /**
     * @var string $f_title_full
     */
    public $f_title_full;
    /**
     * @var string $f_link
     */
    public $f_link;
    /**
     * @var string $f_album_link
     */
    public $f_album_link;
    /**
     * @var string $f_artist_link
     */
    public $f_artist_link;
    /**
     * @var string $f_albumartist_link
     */
    public $f_albumartist_link;

    /**
     * @var string f_year_link
     */
    public $f_year_link;

    /**
     * @var string $f_tags
     */
    public $f_tags;
    /**
     * @var string $f_size
     */
    public $f_size;
    /**
     * @var string $f_lyrics
     */
    public $f_lyrics;
    /**
     * @var string $f_pattern
     */
    public $f_pattern;
    /**
     * @var integer $count
     */
    public $count;
    /**
     * @var string $f_publisher
     */
    public $f_publisher;
    /**
     * @var string $f_composer
     */
    public $f_composer;
    /**
     * @var string $f_license
     */
    public $f_license;

    /* Setting Variables */
    /**
     * @var boolean $_fake
     */
    public $_fake = false; // If this is a 'construct_from_array' object

    /**
     * Aliases used in insert function
     */
    public static $aliases = array(
        'mb_trackid', 'mbid', 'mb_albumid', 'mb_albumid_group', 'mb_artistid', 'mb_albumartistid', 'genre', 'publisher'
    );

    /**
     * Constructor
     *
     * Song class, for modifying a song.
     * @param integer|null $songid
     * @param string $limit_threshold
     */
    public function __construct($songid = null, $limit_threshold = '')
    {
        if ($songid === null) {
            return false;
        }

        $this->id = (int) ($songid);

        if (self::isCustomMetadataEnabled()) {
            $this->initializeMetadata();
        }

        $info = $this->has_info($limit_threshold);
        if ($info !== false && is_array($info)) {
            foreach ($info as $key => $value) {
                $this->$key = $value;
            }
            $data       = pathinfo($this->file);
            $this->type = strtolower((string) $data['extension']);
            $this->mime = self::type_to_mime($this->type);
        } else {
            $this->id = null;

            return false;
        }

        return true;
    } // constructor

    /**
     * insert
     *
     * This inserts the song described by the passed array
     * @param array $results
     * @return integer|boolean
     */
    public static function insert(array $results)
    {
        $catalog               = $results['catalog'];
        $file                  = $results['file'];
        $title                 = Catalog::check_length(Catalog::check_title($results['title'], $file));
        $artist                = Catalog::check_length($results['artist']);
        $album                 = Catalog::check_length($results['album']);
        $albumartist           = Catalog::check_length($results['albumartist'] ?: $results['band']);
        $albumartist           = $albumartist ?: null;
        $bitrate               = $results['bitrate'] ?: 0;
        $rate                  = $results['rate'] ?: 0;
        $mode                  = $results['mode'];
        $size                  = $results['size'] ?: 0;
        $time                  = $results['time'] ?: 0;
        $track                 = Catalog::check_track((string) $results['track']);
        $track_mbid            = $results['mb_trackid'] ?: $results['mbid'];
        $track_mbid            = $track_mbid ?: null;
        $album_mbid            = $results['mb_albumid'];
        $album_mbid_group      = $results['mb_albumid_group'];
        $artist_mbid           = $results['mb_artistid'];
        $albumartist_mbid      = $results['mb_albumartistid'];
        $disk                  = (Album::sanitize_disk($results['disk']) > 0) ? Album::sanitize_disk($results['disk']) : 1;
        $year                  = Catalog::normalize_year($results['year'] ?: 0);
        $comment               = $results['comment'];
        $tags                  = $results['genre']; // multiple genre support makes this an array
        $lyrics                = $results['lyrics'];
        $user_upload           = isset($results['user_upload']) ? $results['user_upload'] : null;
        $license               = isset($results['license']) ? License::lookup((string) $results['license']) : null;
        $composer              = isset($results['composer']) ? Catalog::check_length($results['composer']) : null;
        $label                 = isset($results['publisher']) ? Catalog::get_unique_string(Catalog::check_length($results['publisher'], 128)) : null;
        if ($label && AmpConfig::get('label')) {
            // create the label if missing
            foreach (array_map('trim', explode(';', $label)) as $label_name) {
                Label::helper($label_name);
            }
        }
        $catalog_number        = isset($results['catalog_number']) ? Catalog::check_length($results['catalog_number'], 64) : null;
        $language              = isset($results['language']) ? Catalog::check_length($results['language'], 128) : null;
        $channels              = $results['channels'] ?: 0;
        $release_type          = isset($results['release_type']) ? Catalog::check_length($results['release_type'], 32) : null;
        $replaygain_track_gain = isset($results['replaygain_track_gain']) ? $results['replaygain_track_gain'] : null;
        $replaygain_track_peak = isset($results['replaygain_track_peak']) ? $results['replaygain_track_peak'] : null;
        $replaygain_album_gain = isset($results['replaygain_album_gain']) ? $results['replaygain_album_gain'] : null;
        $replaygain_album_peak = isset($results['replaygain_album_peak']) ? $results['replaygain_album_peak'] : null;
        $r128_track_gain       = isset($results['r128_track_gain']) ? $results['r128_track_gain'] : null;
        $r128_album_gain       = isset($results['r128_album_gain']) ? $results['r128_album_gain'] : null;
        $original_year         = Catalog::normalize_year($results['original_year'] ?: 0);
        $barcode               = Catalog::check_length($results['barcode'], 64);

        if (!in_array($mode, ['vbr', 'cbr', 'abr'])) {
            debug_event(self::class, 'Error analyzing: ' . $file . ' unknown file bitrate mode: ' . $mode, 2);
            $mode = null;
        }
        if (!isset($results['albumartist_id'])) {
            $albumartist_id   = null;
            if ($albumartist) {
                // Multiple artist per songs not supported for now
                $albumartist_mbid = Catalog::trim_slashed_list($albumartist_mbid);
                $albumartist_id   = Artist::check($albumartist, $albumartist_mbid);
            }
        } else {
            $albumartist_id = (int) ($results['albumartist_id']);
        }
        if (!isset($results['artist_id'])) {
            // Multiple artist per songs not supported for now
            $artist_mbid = Catalog::trim_slashed_list($artist_mbid);
            $artist_id   = Artist::check($artist, $artist_mbid);
        } else {
            $artist_id = (int) ($results['artist_id']);
        }
        if (!isset($results['album_id'])) {
            $album_id = Album::check($album, $year, $disk, $album_mbid, $album_mbid_group, $albumartist_id, $release_type, $original_year, $barcode, $catalog_number);
        } else {
            $album_id = (int) ($results['album_id']);
        }
        $insert_time = time();

        $sql = 'INSERT INTO `song` (`catalog`, `file`, `album`, `artist`, ' .
            '`title`, `bitrate`, `rate`, `mode`, `size`, `time`, `track`, ' .
            '`addition_time`, `update_time`, `year`, `mbid`, `user_upload`, `license`, ' .
            '`composer`, `channels`) ' .
            'VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $db_results = Dba::write($sql, array(
            $catalog, $file, $album_id, $artist_id,
            $title, $bitrate, $rate, $mode, $size, $time, $track,
            $insert_time, $insert_time, $year, $track_mbid, $user_upload, $license,
            $composer, $channels));

        if (!$db_results) {
            debug_event(self::class, 'Unable to insert ' . $file, 2);

            return false;
        }

        $song_id = (int) Dba::insert_id();

        if ($user_upload) {
            Useractivity::post_activity((int) ($user_upload), 'upload', 'song', $song_id, time());
        }

        // Allow scripts to populate new tags when injecting user uploads
        if (!defined('NO_SESSION')) {
            if ($user_upload && !Access::check('interface', 50, $user_upload)) {
                $tags = Tag::clean_to_existing($tags);
            }
        }
        if (is_array($tags)) {
            foreach ($tags as $tag) {
                $tag = trim((string) $tag);
                if (!empty($tag)) {
                    Tag::add('song', $song_id, $tag, false);
                    Tag::add('album', $album_id, $tag, false);
                    Tag::add('artist', $artist_id, $tag, false);
                }
            }
        }

        $sql = 'INSERT INTO `song_data` (`song_id`, `comment`, `lyrics`, `label`, `language`, `replaygain_track_gain`, `replaygain_track_peak`, `replaygain_album_gain`, `replaygain_album_peak`, `r128_track_gain`, `r128_album_gain`) ' .
            'VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        Dba::write($sql, array($song_id, $comment, $lyrics, $label, $language, $replaygain_track_gain, $replaygain_track_peak, $replaygain_album_gain, $replaygain_album_peak, $r128_track_gain, $r128_album_gain));

        return $song_id;
    }

    /**
     * garbage_collection
     *
     * Cleans up the song_data table
     */
    public static function garbage_collection()
    {
        // clean up missing catalogs
        Dba::write("DELETE FROM `song` WHERE `song`.`catalog` NOT IN (SELECT `id` FROM `catalog`)");
        // delete the rest
        Dba::write('DELETE FROM `song_data` USING `song_data` LEFT JOIN `song` ON `song`.`id` = `song_data`.`song_id` WHERE `song`.`id` IS NULL');
    }

    /**
     * build_cache
     *
     * This attempts to reduce queries by asking for everything in the
     * browse all at once and storing it in the cache, this can help if the
     * db connection is the slow point.
     * @param integer[] $song_ids
     * @param string $limit_threshold
     * @return boolean
     */
    public static function build_cache($song_ids, $limit_threshold = '')
    {
        if (empty($song_ids)) {
            return false;
        }
        $idlist = '(' . implode(',', $song_ids) . ')';
        if ($idlist == '()') {
            return false;
        }

        // Song data cache
        $sql = 'SELECT `song`.`id`, `file`, `catalog`, `album`, ' .
            '`year`, `artist`, `title`, `bitrate`, `rate`, ' .
            '`mode`, `size`, `time`, `track`, `played`, ' .
            '`song`.`enabled`, `update_time`, `tag_map`.`tag_id`, ' .
            '`mbid`, `addition_time`, `license`, `composer`, `user_upload` ' .
            'FROM `song` LEFT JOIN `tag_map` ' .
            'ON `tag_map`.`object_id`=`song`.`id` ' .
            "AND `tag_map`.`object_type`='song' ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
        }
        $sql .= "WHERE `song`.`id` IN $idlist ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND `catalog`.`enabled` = '1' ";
        }
        $db_results = Dba::read($sql);

        $artists = array();
        $albums  = array();
        $tags    = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            if (AmpConfig::get('show_played_times')) {
                $row['object_cnt'] = Stats::get_object_count('song', $row['id'], $limit_threshold);
            }
            if (AmpConfig::get('show_skipped_times')) {
                $row['skip_cnt']   = Stats::get_object_count('song', $row['id'], $limit_threshold, 'skip');
            }
            parent::add_to_cache('song', $row['id'], $row);
            $artists[$row['artist']] = $row['artist'];
            $albums[$row['album']]   = $row['album'];
            if ($row['tag_id']) {
                $tags[$row['tag_id']] = $row['tag_id'];
            }
        }

        Artist::build_cache($artists);
        Album::build_cache($albums);
        Tag::build_cache($tags);
        Tag::build_map_cache('song', $song_ids);
        Art::build_cache($albums);

        // If we're rating this then cache them as well
        if (AmpConfig::get('ratings')) {
            Rating::build_cache('song', $song_ids);
        }
        if (AmpConfig::get('userflags')) {
            Userflag::build_cache('song', $song_ids);
        }

        // Build a cache for the song's extended table
        $sql        = "SELECT * FROM `song_data` WHERE `song_id` IN $idlist";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('song_data', $row['song_id'], $row);
        }

        return true;
    } // build_cache

    /**
     * has_info
     * @param string $limit_threshold
     * @return array|boolean
     */
    private function has_info($limit_threshold = '')
    {
        $song_id = $this->id;

        if (parent::is_cached('song', $song_id)) {
            return parent::get_from_cache('song', $song_id);
        }

        $sql = 'SELECT `song`.`id`, `song`.`file`, `song`.`catalog`, `song`.`album`, `album`.`album_artist` AS `albumartist`, `song`.`year`, `song`.`artist`, ' .
            '`song`.`title`, `song`.`bitrate`, `song`.`rate`, `song`.`mode`, `song`.`size`, `song`.`time`, `song`.`track`, ' .
            '`song`.`played`, `song`.`enabled`, `song`.`update_time`, `song`.`mbid`, `song`.`addition_time`, `song`.`license`, ' .
            '`song`.`composer`, `song`.`user_upload`, `album`.`disk`, `album`.`mbid` AS `album_mbid`, `artist`.`mbid` AS `artist_mbid`, `album_artist`.`mbid` AS `albumartist_mbid` ' .
            'FROM `song` LEFT JOIN `album` ON `album`.`id` = `song`.`album` LEFT JOIN `artist` ON `artist`.`id` = `song`.`artist` ' .
            'LEFT JOIN `artist` AS `album_artist` ON `album_artist`.`id` = `album`.`album_artist` ' .
            'WHERE `song`.`id` = ?';
        $db_results = Dba::read($sql, array($song_id));

        $results = Dba::fetch_assoc($db_results);
        if (isset($results['id'])) {
            if (AmpConfig::get('show_played_times')) {
                $results['object_cnt'] = Stats::get_object_count('song', $results['id'], $limit_threshold);
            }
            if (AmpConfig::get('show_skipped_times')) {
                $results['skip_cnt']   = Stats::get_object_count('song', $results['id'], $limit_threshold, 'skip');
            }

            parent::add_to_cache('song', $song_id, $results);

            return $results;
        }

        return false;
    }

    /**
     * can_scrobble
     *
     * return a song id based on a last.fm-style search in the database
     * @param string $song_name
     * @param string $artist_name
     * @param string $album_name
     * @param string $song_mbid
     * @param string $artist_mbid
     * @param string $album_mbid
     * @return string
     */
    public static function can_scrobble($song_name, $artist_name, $album_name, $song_mbid = '', $artist_mbid = '', $album_mbid = '')
    {
        // by default require song, album, artist for any searches
        $sql = 'SELECT `song`.`id` FROM `song` LEFT JOIN `album` ON `album`.`id` = `song`.`album` LEFT JOIN `artist` ON `artist`.`id` = `song`.`artist` ' .
                'LEFT JOIN `artist` AS `album_artist` ON `album_artist`.`id` = `album`.`album_artist` ' .
                "WHERE `song`.`title` = '" . Dba::escape($song_name) . "' AND " .
                "(`artist`.`name` = '" . Dba::escape($artist_name) . "' OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), `artist`.`name`)) = '" . Dba::escape($artist_name) . "') AND " .
                "(`album`.`name` = '" . Dba::escape($album_name) . "' OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), `album`.`name`)) = '" . Dba::escape($album_name) . "')";
        if ($song_mbid) {
            $sql .= " AND `song`.`mbid` = '" . $song_mbid . "'";
        }
        if ($artist_mbid) {
            $sql .= " AND `artist`.`mbid` = '" . $song_mbid . "'";
        }
        if ($album_mbid) {
            $sql .= " AND `album`.`mbid` = '" . $song_mbid . "'";
        }
        $db_results = Dba::read($sql);

        $results = Dba::fetch_assoc($db_results);
        if (isset($results['id'])) {
            return $results['id'];
        }

        return '';
    }

    /**
     * _get_ext_info
     * This function gathers information from the song_ext_info table and adds it to the
     * current object
     * @param string $select
     * @return array
     */
    public function _get_ext_info($select = '')
    {
        $song_id = (int) ($this->id);
        $columns = (!empty($select)) ? Dba::escape($select) : '*';

        if (parent::is_cached('song_data', $song_id)) {
            return parent::get_from_cache('song_data', $song_id);
        }

        $sql        = "SELECT $columns FROM `song_data` WHERE `song_id` = ?";
        $db_results = Dba::read($sql, array($song_id));

        $results = Dba::fetch_assoc($db_results);

        parent::add_to_cache('song_data', $song_id, $results);

        return $results;
    } // _get_ext_info

    /**
     * fill_ext_info
     * This calls the _get_ext_info and then sets the correct vars
     * @param string $data_filter
     */
    public function fill_ext_info($data_filter = '')
    {
        $info = $this->_get_ext_info($data_filter);

        if (!empty($info)) {
            foreach ($info as $key => $value) {
                if ($key != 'song_id') {
                    $this->$key = $value;
                }
            } // end foreach
        }
    } // fill_ext_info

    /**
     * type_to_mime
     *
     * Returns the mime type for the specified file extension/type
     * @param string $type
     * @return string
     */
    public static function type_to_mime($type)
    {
        // FIXME: This should really be done the other way around.
        // Store the mime type in the database, and provide a function
        // to make it a human-friendly type.
        switch ($type) {
            case 'spx':
            case 'ogg':
                return 'application/ogg';
            case 'opus':
                return 'audio/ogg; codecs=opus';
            case 'wma':
            case 'asf':
                return 'audio/x-ms-wma';
            case 'rm':
            case 'ra':
                return 'audio/x-realaudio';
            case 'flac':
                return 'audio/x-flac';
            case 'wv':
                return 'audio/x-wavpack';
            case 'aac':
            case 'mp4':
            case 'm4a':
                return 'audio/mp4';
            case 'aacp':
                return 'audio/aacp';
            case 'mpc':
                return 'audio/x-musepack';
            case 'mkv':
                return 'audio/x-matroska';
            case 'mpeg3':
            case 'mp3':
            default:
                return 'audio/mpeg';
        }
    }

    /**
     * get_disabled
     *
     * Gets a list of the disabled songs for and returns an array of Songs
     * @param integer $count
     * @return Song[]
     */
    public static function get_disabled($count = 0)
    {
        $results = array();

        $sql = "SELECT `id` FROM `song` WHERE `enabled`='0'";
        if ($count) {
            $sql .= " LIMIT $count";
        }
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = new Song($row['id']);
        }

        return $results;
    }

    /**
     * find_duplicates
     *
     * This function takes a search type and returns a list of probable
     * duplicates
     * @param string $search_type
     * @return array
     */
    public static function find_duplicates($search_type)
    {
        $where_sql = $_REQUEST['search_disabled'] ? '' : "WHERE `enabled` != '0'";
        $sql       = 'SELECT `artist`, `album`, `title`, ' .
            'COUNT(`title`) FROM `song` ' . $where_sql .
            ' GROUP BY `artist`, `album`, `title`';

        if ($search_type == 'artist_title' ||
            $search_type == 'artist_album_title') {
            $sql .= ',`artist`';
        }
        if ($search_type == 'artist_album_title') {
            $sql .= ',`album`';
        }

        $sql .= ' HAVING COUNT(`title`) > 1 ORDER BY `title`';

        $db_results = Dba::read($sql);

        $results = array();

        while ($item = Dba::fetch_assoc($db_results)) {
            $results[] = $item;
        } // end while

        return $results;
    }

    /**
     * find
     * @param array $data
     * @return boolean
     */
    public static function find($data)
    {
        $sql_base = "SELECT `song`.`id` FROM `song`";
        if ($data['mb_trackid']) {
            $sql        = $sql_base . " WHERE `song`.`mbid` = ? LIMIT 1";
            $db_results = Dba::read($sql, array($data['mb_trackid']));
            if ($results = Dba::fetch_assoc($db_results)) {
                return $results['id'];
            }
        }
        if ($data['file']) {
            $sql        = $sql_base . " WHERE `song`.`file` = ? LIMIT 1";
            $db_results = Dba::read($sql, array($data['file']));
            if ($results = Dba::fetch_assoc($db_results)) {
                return $results['id'];
            }
        }

        $where  = "WHERE `song`.`title` = ?";
        $sql    = $sql_base;
        $params = array($data['title']);
        if ($data['track']) {
            $where .= " AND `song`.`track` = ?";
            $params[] = $data['track'];
        }
        $sql .= " INNER JOIN `artist` ON `artist`.`id` = `song`.`artist`";
        $sql .= " INNER JOIN `album` ON `album`.`id` = `song`.`album`";

        if ($data['mb_artistid']) {
            $where .= " AND `artist`.`mbid` = ?";
            $params[] = $data['mb_albumid'];
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
     * Get duplicate information.
     * @param array $dupe
     * @param string $search_type
     * @return integer[]
     */
    public static function get_duplicate_info($dupe, $search_type)
    {
        $results = array();
        if (isset($dupe['id'])) {
            $results[] = $dupe['id'];
        } else {
            $sql = "SELECT `id` FROM `song` WHERE " .
                    "`title`='" . Dba::escape($dupe['title']) . "' ";

            if ($search_type == 'artist_title' ||
                $search_type == 'artist_album_title') {
                $sql .= "AND `artist`='" . Dba::escape($dupe['artist']) . "' ";
            }
            if ($search_type == 'artist_album_title') {
                $sql .= "AND `album` = '" . Dba::escape($dupe['album']) . "' ";
            }
            $sql .= 'ORDER BY `time`, `bitrate`, `size`';

            if ($search_type == 'album') {
                $sql = "SELECT `id` from `song` " .
                       "LEFT JOIN (SELECT MIN(`id`) AS `dupe_id1`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), " .
                       "' ', `album`.`name`)) AS `fullname`, COUNT(LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), " .
                       "' ', `album`.`name`))) AS `Counting` FROM `album` GROUP BY `album_artist`, " .
                       "LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `disk` " .
                       "HAVING `Counting` > 1) AS `dupe_search` ON song.album = `dupe_search`.`dupe_id1` " .
                       "LEFT JOIN (SELECT MAX(`id`) AS `dupe_id2`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), " .
                       "' ', `album`.`name`)) AS `fullname`, COUNT(LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), " .
                       "' ', `album`.`name`))) AS `Counting` FROM `album` GROUP BY `album_artist`, " .
                       "LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `disk` " .
                       "HAVING `Counting` > 1) AS `dupe_search2` ON `song`.`album` = `dupe_search2`.`dupe_id2` " .
                       "WHERE `dupe_search`.`dupe_id1` IS NOT NULL OR `dupe_search2`.`dupe_id2` IS NOT NULL " .
                       "ORDER BY `album`, `track`";
            }

            $db_results = Dba::read($sql);

            while ($item = Dba::fetch_assoc($db_results)) {
                $results[] = $item['id'];
            } // end while
        }

        return $results;
    }

    /**
     * get_album_name
     * gets the name of $this->album, allows passing of id
     * @param integer $album_id
     * @return string
     */
    public function get_album_name($album_id = 0)
    {
        if (!$album_id) {
            $album_id = $this->album;
        }
        $album = new Album($album_id);
        if ($album->prefix) {
            return $album->prefix . " " . $album->name;
        } else {
            return $album->name;
        }
    } // get_album_name

    /**
     * get_album_catalog_number
     * gets the catalog_number of $this->album, allows passing of id
     * @param integer $album_id
     * @return string
     */
    public function get_album_catalog_number($album_id = null)
    {
        if ($album_id === null) {
            $album_id = $this->album;
        }
        $album = new Album($album_id);

        return $album->catalog_number;
    } // get_album_catalog_number

    /**
     * get_album_original_year
     * gets the original_year of $this->album, allows passing of id
     * @param integer $album_id
     * @return integer
     */
    public function get_album_original_year($album_id = null)
    {
        if ($album_id === null) {
            $album_id = $this->album;
        }
        $album = new Album($album_id);

        return $album->original_year;
    } // get_album_original_year

    /**
     * get_album_barcode
     * gets the barcode of $this->album, allows passing of id
     * @param integer $album_id
     * @return string
     */
    public function get_album_barcode($album_id = null)
    {
        if (!$album_id) {
            $album_id = $this->album;
        }
        $album = new Album($album_id);

        return $album->barcode;
    } // get_album_barcode

    /**
     * get_artist_name
     * gets the name of $this->artist, allows passing of id
     * @param integer $artist_id
     * @return string
     */
    public function get_artist_name($artist_id = 0)
    {
        if (!$artist_id) {
            $artist_id = $this->artist;
        }
        $artist = new Artist($artist_id);
        if ($artist->prefix) {
            return $artist->prefix . " " . $artist->name;
        } else {
            return $artist->name;
        }
    } // get_artist_name

    /**
     * get_album_artist_name
     * gets the name of $this->albumartist, allows passing of id
     * @param integer $album_artist_id
     * @return string
     */
    public function get_album_artist_name($album_artist_id = 0)
    {
        if (!$album_artist_id) {
            $album_artist_id = $this->albumartist;
        }
        $album_artist = new Artist($album_artist_id);
        if ($album_artist->prefix) {
            return $album_artist->prefix . " " . $album_artist->name;
        } else {
            return (string) $album_artist->name;
        }
    } // get_album_artist_name

    /**
     * set_played
     * this checks to see if the current object has been played
     * if not then it sets it to played. In any case it updates stats.
     * @param integer $user
     * @param string $agent
     * @param array $location
     * @param integer $date
     * @return boolean
     */
    public function set_played($user, $agent, $location, $date = null)
    {
        // ignore duplicates or skip the last track
        if (!$this->check_play_history($user, $agent, $date)) {
            return false;
        }
        // insert stats for each object type
        if (Stats::insert('song', $this->id, $user, $agent, $location, 'stream', $date)) {
            Stats::insert('album', $this->album, $user, $agent, $location, 'stream', $date);
            Stats::insert('artist', $this->artist, $user, $agent, $location, 'stream', $date);
        }
        // If it hasn't been played, set it
        if (!$this->played) {
            self::update_played(true, $this->id);
        }

        return true;
    } // set_played

    /**
     * check_play_history
     * this checks to see if the current object has been played
     * if not then it sets it to played. In any case it updates stats.
     * @param integer $user
     * @param string $agent
     * @param integer $date
     * @return boolean
     */
    public function check_play_history($user, $agent, $date)
    {
        return Stats::has_played_history($this, $user, $agent, $date);
    }

    /**
     * compare_song_information
     * this compares the new ID3 tags of a file against
     * the ones in the database to see if they have changed
     * it returns false if nothing has changes, or the true
     * if they have. Static because it doesn't need this
     * @param Song $song
     * @param Song $new_song
     * @return array
     */
    public static function compare_song_information(Song $song, Song $new_song)
    {
        // Remove some stuff we don't care about as this function only needs to check song information.
        unset($song->catalog, $song->played, $song->enabled, $song->addition_time, $song->update_time, $song->type, $song->disk);
        $string_array = array('title', 'comment', 'lyrics', 'composer', 'tags', 'artist', 'album', 'time');
        $skip_array   = array('id', 'tag_id', 'mime', 'mbid', 'waveform', 'object_cnt', 'skip_cnt', 'albumartist', 'artist_mbid', 'album_mbid', 'albumartist_mbid', 'mb_albumid_group', 'disabledMetadataFields');

        return self::compare_media_information($song, $new_song, $string_array, $skip_array);
    } // compare_song_information

    /**
     * compare_media_information
     * @param $media
     * @param $new_media
     * @param string[] $string_array
     * @param string[] $skip_array
     * @return array
     */
    public static function compare_media_information($media, $new_media, $string_array, $skip_array)
    {
        $array        = array();

        // Pull out all the currently set vars
        $fields = get_object_vars($media);

        // Foreach them
        foreach ($fields as $key => $value) {
            $key = trim((string) $key);
            if (empty($key) || in_array($key, $skip_array)) {
                continue;
            }

            // Represent the value as a string for simpler comparaison.
            // For array, ensure to sort similarly old/new values
            if (is_array($media->$key)) {
                $arr = $media->$key;
                sort($arr);
                $mediaData = implode(" ", $arr);
            } else {
                $mediaData = $media->$key;
            }

            // Skip the item if it is no string nor something we can turn into a string
            if (!is_string($mediaData) && !is_numeric($mediaData) && !is_bool($mediaData)) {
                if (is_object($mediaData) && !method_exists($mediaData, '__toString')) {
                    continue;
                }
            }

            if (is_array($new_media->$key)) {
                $arr = $new_media->$key;
                sort($arr);
                $newMediaData = implode(" ", $arr);
            } else {
                $newMediaData = $new_media->$key;
            }

            // If it's a stringie thing
            if (in_array($key, $string_array)) {
                $mediaData    = self::clean_string_field_value($mediaData);
                $newMediaData = self::clean_string_field_value($newMediaData);
                if ($mediaData != $newMediaData) {
                    $array['change']        = true;
                    $array['element'][$key] = 'OLD: ' . $mediaData . ' --> ' . $newMediaData;
                }
            } // in array of stringies
            elseif ($newMediaData !== null) {
                if ($media->$key != $new_media->$key) {
                    $array['change']        = true;
                    $array['element'][$key] = 'OLD:' . $mediaData . ' --> ' . $newMediaData;
                }
            } // end else
        } // end foreach

        if ($array['change']) {
            debug_event(self::class, 'media-diff ' . json_encode($array['element']), 5);
        }

        return $array;
    }

    /**
     * clean_string_field_value
     * @param string $value
     * @return string
     */
    private static function clean_string_field_value($value)
    {
        $value = trim(stripslashes(preg_replace('/\s+/', ' ', $value)));

        // Strings containing  only UTF-8 BOM = empty string
        if (strlen((string) $value) == 2 && (ord($value[0]) == 0xFF || ord($value[0]) == 0xFE)) {
            $value = "";
        }

        return $value;
    }

    /**
     * update
     * This takes a key'd array of data does any cleaning it needs to
     * do and then calls the helper functions as needed.
     * @param array $data
     * @return integer
     */
    public function update(array $data)
    {
        $changed = array();
        foreach ($data as $key => $value) {
            debug_event(self::class, $key . '=' . $value, 5);

            switch ($key) {
                case 'artist_name':
                    // Create new artist name and id
                    $old_artist_id = $this->artist;
                    $new_artist_id = Artist::check($value);
                    $this->artist  = $new_artist_id;
                    self::update_artist($new_artist_id, $this->id, $old_artist_id);
                    $changed[] = (string) $key;
                    break;
                case 'album_name':
                    // Create new album name and id
                    $old_album_id = $this->album;
                    $new_album_id = Album::check($value);
                    $this->album  = $new_album_id;
                    self::update_album($new_album_id, $this->id, $old_album_id);
                    $changed[] = (string) $key;
                    break;
                case 'artist':
                    // Change artist the song is assigned to
                    if ($value != $this->$key) {
                        $old_artist_id = $this->artist;
                        $new_artist_id = $value;
                        self::update_artist($new_artist_id, $this->id, $old_artist_id);
                        $changed[] = (string) $key;
                    }
                    break;
                case 'album':
                    // Change album the song is assigned to
                    if ($value != $this->$key) {
                        $old_album_id = $this->$key;
                        $new_album_id = $value;
                        self::update_album($new_album_id, $this->id, $old_album_id);
                        $changed[] = (string) $key;
                    }
                    break;
                case 'year':
                case 'title':
                case 'track':
                case 'mbid':
                case 'license':
                case 'composer':
                case 'label':
                case 'language':
                case 'comment':
                    // Check to see if it needs to be updated
                    if ($value != $this->$key) {
                        $function = 'update_' . $key;
                        self::$function($value, $this->id);
                        $this->$key = $value;
                        $changed[]  = (string) $key;
                    }
                    break;
                case 'edit_tags':
                    Tag::update_tag_list($value, 'song', $this->id, true);
                    $this->tags = Tag::get_top_tags('song', $this->id);
                    $changed[]  = (string) $key;
                    break;
                case 'metadata':
                    if (self::isCustomMetadataEnabled()) {
                        $this->updateMetadata($value);
                    }
                    break;
                default:
                    break;
            } // end whitelist
        } // end foreach

        $this->format();
        $this->write_id3($data, $changed);

        return $this->id;
    } // update

    /**
     * write_id3
     * Write the current song id3 metadata to the file
     * @param array $data
     * @param array $changed
     * @throws Exception
     */
    public function write_id3($data = null, $changed = null)
    {
        if (AmpConfig::get('write_id3', false)) {
            $catalog = Catalog::create_from_id($this->catalog);
            if ($catalog->get_type() == 'local') {
                debug_event(self::class, 'Writing id3 metadata to file ' . $this->file, 5);
                if (self::isCustomMetadataEnabled()) {
                    foreach ($this->getMetadata() as $metadata) {
                        $meta[$metadata->getField()->getName()] = $metadata->getData();
                    }
                }
                $id3    = new vainfo($this->file);
                $result = $id3->read_id3();
                if ($result['fileformat'] == 'mp3') {
                    $tdata = $result['tags']['id3v2'];
                    $meta  = $this->get_metadata();
                } else {
                    $tdata = $result['tags']['vorbiscomment'];
                    $meta  = $this->get_vorbis_metadata();
                }
                $ndata = $id3->prepare_id3_frames($tdata);
                // $song = new Song($this->id);
                // $song->format();
                if (isset($changed)) {
                    foreach ($changed as $key => $value) {
                        switch ($value) {
                        case 'artist':
                        case 'artist_name':
                            $ndata['artist'][0] = $this->f_artist;
                            break;
                        case 'album':
                        case 'album_name':
                            $ndata['album'][0] = $this->f_album;
                            break;
                        case 'track':
                            $ndata['track_number'][0] = $data['track'];
                            break;
                        case 'label':
                            $ndata['publisher'][0] = $data['label'];
                            break;
                        case 'edit_tags':
                            $ndata['genre'][0] = $data['edit_tags'];
                            break;
                        default:
                            $ndata[$value][0] = $data[$value];
                            break;
                        }
                    }
                    $pics = array();
                    if (isset($data['id3v2']['APIC'])) {
                        $pics = art::prepare_pics($data['id3v2']['APIC']);
                    }
                    $ndata = array_merge($pics, $ndata);
                } else {
                    // Fill in existing tag frames
                    foreach ($meta as $key => $value) {
                        if ($key != 'text' && $key != 'totaltracks') {
                            $ndata[$key][0] = $meta[$key] ?:'';
                        }
                    }

                    $art = new Art($this->album, 'album');
                    if ($art->has_db_info()) {
                        $album_image                                   = $art->get(true);
                        $ndata['attached_picture'][0]['description']   = $this->f_album;
                        $ndata['attached_picture'][0]['data']          = $album_image;
                        $ndata['attached_picture'][0]['picturetypeid'] = '3';
                        $ndata['attached_picture'][0]['mime']          = $art->raw_mime;
                    }
                    $art = new Art($this->artist, 'artist');
                    if ($art->has_db_info()) {
                        $artist_image                                   = $art->get(true);
                        $i                                              = (!empty($album_image)) ? 1 : 0;
                        $ndata['attached_picture'][$i]['description']   = $this->f_artist;
                        $ndata['attached_picture'][$i]['data']          = $artist_image;
                        $ndata['attached_picture'][$i]['picturetypeid'] = '8';
                        $ndata['attached_picture'][$i]['mime']          = $art->raw_mime;
                    }
                }
                $id3->write_id3($ndata);
                // Catalog::update_media_from_tags($this);
            }
        }
    }

    /**
     * write_id3_for_song
     * Write id3 metadata to the file for the excepted song id
     * @param integer $song_id
     */
    public static function write_id3_for_song($song_id)
    {
        $song = new Song($song_id);
        if ($song->id) {
            $song->format();
            $song->write_id3();
        }
    }

    /**
     * update_song
     * this is the main updater for a song it actually
     * calls a whole bunch of mini functions to update
     * each little part of the song... lastly it updates
     * the "update_time" of the song
     * @param integer $song_id
     * @param Song $new_song
     */
    public static function update_song($song_id, Song $new_song)
    {
        $update_time = time();

        $sql = "UPDATE `song` SET `album` = ?, `year` = ?, `artist` = ?, " .
            "`title` = ?, `composer` = ?, `bitrate` = ?, `rate` = ?, `mode` = ?, " .
            "`size` = ?, `time` = ?, `track` = ?, `mbid` = ?, " .
            "`update_time` = ? WHERE `id` = ?";
        Dba::write($sql, array($new_song->album, $new_song->year, $new_song->artist,
                                $new_song->title, $new_song->composer, (int) $new_song->bitrate, (int) $new_song->rate, $new_song->mode,
                                (int) $new_song->size, (int) $new_song->time, $new_song->track, $new_song->mbid,
                                $update_time, $song_id));

        $sql = "UPDATE `song_data` SET `label` = ?, `lyrics` = ?, `language` = ?, `comment` = ?, `replaygain_track_gain` = ?, `replaygain_track_peak` = ?, " .
            "`replaygain_album_gain` = ?, `replaygain_album_peak` = ?, `r128_track_gain` = ?, `r128_album_gain` = ? " .
            "WHERE `song_id` = ?";
        Dba::write($sql, array($new_song->label, $new_song->lyrics, $new_song->language, $new_song->comment, $new_song->replaygain_track_gain,
            $new_song->replaygain_track_peak, $new_song->replaygain_album_gain, $new_song->replaygain_album_peak, $new_song->r128_track_gain, $new_song->r128_album_gain, $song_id));
    } // update_song

    /**
     * update_year
     * update the year tag
     * @param integer $new_year
     * @param integer $song_id
     */
    public static function update_year($new_year, $song_id)
    {
        self::_update_item('year', $new_year, $song_id, 50, true);
    } // update_year

    /**
     * update_label
     * This updates the label tag of the song
     * @param string $new_value
     * @param integer $song_id
     */
    public static function update_label($new_value, $song_id)
    {
        self::_update_ext_item('label', $new_value, $song_id, 50, true);
    } // update_label

    /**
     * update_language
     * This updates the language tag of the song
     * @param string $new_lang
     * @param integer $song_id
     */
    public static function update_language($new_lang, $song_id)
    {
        self::_update_ext_item('language', $new_lang, $song_id, 50, true);
    } // update_language

    /**
     * update_comment
     * updates the comment field
     * @param string $new_comment
     * @param integer $song_id
     */
    public static function update_comment($new_comment, $song_id)
    {
        self::_update_ext_item('comment', $new_comment, $song_id, 50, true);
    } // update_comment

    /**
     * update_lyrics
     * updates the lyrics field
     * @param string $new_lyrics
     * @param integer $song_id
     */
    public static function update_lyrics($new_lyrics, $song_id)
    {
        self::_update_ext_item('lyrics', $new_lyrics, $song_id, 50, true);
    } // update_lyrics

    /**
     * update_title
     * updates the title field
     * @param string $new_title
     * @param integer $song_id
     */
    public static function update_title($new_title, $song_id)
    {
        self::_update_item('title', $new_title, $song_id, 50, true);
    } // update_title

    /**
     * update_composer
     * updates the composer field
     * @param string $new_value
     * @param integer $song_id
     */
    public static function update_composer($new_value, $song_id)
    {
        self::_update_item('composer', $new_value, $song_id, 50, true);
    } // update_composer

    /**
     * update_publisher
     * updates the publisher field
     * @param string $new_value
     * @param integer $song_id
     */
    public static function update_publisher($new_value, $song_id)
    {
        self::_update_item('publisher', $new_value, $song_id, 50, true);
    } // update_publisher

    /**
     * update_bitrate
     * updates the bitrate field
     * @param integer $new_bitrate
     * @param integer $song_id
     */
    public static function update_bitrate($new_bitrate, $song_id)
    {
        self::_update_item('bitrate', $new_bitrate, $song_id, 50, true);
    } // update_bitrate

    /**
     * update_rate
     * updates the rate field
     * @param integer $new_rate
     * @param integer $song_id
     */
    public static function update_rate($new_rate, $song_id)
    {
        self::_update_item('rate', $new_rate, $song_id, 50, true);
    } // update_rate

    /**
     * update_mode
     * updates the mode field
     * @param string $new_mode
     * @param integer $song_id
     */
    public static function update_mode($new_mode, $song_id)
    {
        self::_update_item('mode', $new_mode, $song_id, 50, true);
    } // update_mode

    /**
     * update_size
     * updates the size field
     * @param integer $new_size
     * @param integer $song_id
     */
    public static function update_size($new_size, $song_id)
    {
        self::_update_item('size', $new_size, $song_id, 50);
    } // update_size

    /**
     * update_time
     * updates the time field
     * @param integer $new_time
     * @param integer $song_id
     */
    public static function update_time($new_time, $song_id)
    {
        self::_update_item('time', $new_time, $song_id, 50, true);
    } // update_time

    /**
     * update_track
     * this updates the track field
     * @param integer $new_track
     * @param integer $song_id
     */
    public static function update_track($new_track, $song_id)
    {
        self::_update_item('track', $new_track, $song_id, 50, true);
    } // update_track

    /**
     * update_mbid
     * updates mbid field
     * @param string $new_mbid
     * @param integer $song_id
     */
    public static function update_mbid($new_mbid, $song_id)
    {
        self::_update_item('mbid', $new_mbid, $song_id, 50);
    } // update_mbid

    /**
     * update_license
     * updates license field
     * @param string $new_license
     * @param integer $song_id
     */
    public static function update_license($new_license, $song_id)
    {
        self::_update_item('license', $new_license, $song_id, 50, true);
    } // update_license

    /**
     * update_artist
     * updates the artist field
     * @param integer $new_artist
     * @param integer $song_id
     * @param integer $old_artist
     */
    public static function update_artist($new_artist, $song_id, $old_artist)
    {
        self::_update_item('artist', $new_artist, $song_id, 50);

        // migrate stats for the old artist
        Stats::migrate('artist', $old_artist, $new_artist);
        UserActivity::migrate('artist', $old_artist, $new_artist);
        Recommendation::migrate('artist', $old_artist, $new_artist);
        Share::migrate('artist', $old_artist, $new_artist);
        Shoutbox::migrate('artist', $old_artist, $new_artist);
        Tag::migrate('artist', $old_artist, $new_artist);
        Userflag::migrate('artist', $old_artist, $new_artist);
        Rating::migrate('artist', $old_artist, $new_artist);
        Art::migrate('artist', $old_artist, $new_artist);
    } // update_artist

    /**
     * update_album
     * updates the album field
     * @param integer $new_album
     * @param integer $song_id
     * @param integer $old_album
     */
    public static function update_album($new_album, $song_id, $old_album)
    {
        self::_update_item('album', $new_album, $song_id, 50, true);

        // migrate stats for the old album
        Stats::migrate('album', $old_album, $new_album);
        UserActivity::migrate('album', $old_album, $new_album);
        Recommendation::migrate('album', $old_album, $new_album);
        Share::migrate('album', $old_album, $new_album);
        Shoutbox::migrate('album', $old_album, $new_album);
        Tag::migrate('album', $old_album, $new_album);
        Userflag::migrate('album', $old_album, $new_album);
        Rating::migrate('album', $old_album, $new_album);
        Art::migrate('album', $old_album, $new_album);
    } // update_album

    /**
     * update_utime
     * sets a new update time
     * @param integer $song_id
     * @param integer $time
     */
    public static function update_utime($song_id, $time = 0)
    {
        if (!$time) {
            $time = time();
        }

        self::_update_item('update_time', $time, $song_id, 75, true);
    } // update_utime

    /**
     * update_played
     * sets the played flag
     * @param boolean $new_played
     * @param integer $song_id
     */
    public static function update_played($new_played, $song_id)
    {
        self::_update_item('played', ($new_played ? 1 : 0), $song_id, 25);
    } // update_played

    /**
     * update_enabled
     * sets the enabled flag
     * @param boolean $new_enabled
     * @param integer $song_id
     */
    public static function update_enabled($new_enabled, $song_id)
    {
        self::_update_item('enabled', ($new_enabled ? 1 : 0), $song_id, 75, true);
    } // update_enabled

    /**
     * _update_item
     * This is a private function that should only be called from within the song class.
     * It takes a field, value song id and level. first and foremost it checks the level
     * against Core::get_global('user') to make sure they are allowed to update this record
     * it then updates it and sets $this->{$field} to the new value
     * @param string $field
     * @param mixed $value
     * @param integer $song_id
     * @param integer $level
     * @param boolean $check_owner
     * @return PDOStatement|boolean
     */
    private static function _update_item($field, $value, $song_id, $level, $check_owner = false)
    {
        if ($check_owner) {
            $item = new Song($song_id);
            if ($item->id && $item->get_user_owner() == Core::get_global('user')->id) {
                $level = 25;
            }
        }
        /* Check them Rights! */
        if (!Access::check('interface', $level)) {
            return false;
        }

        /* Can't update to blank */
        if (!strlen(trim((string) $value)) && $field != 'comment') {
            return false;
        }

        $sql = "UPDATE `song` SET `$field` = ? WHERE `id` = ?";

        return Dba::write($sql, array($value, $song_id));
    } // _update_item

    /**
     * _update_ext_item
     * This updates a song record that is housed in the song_ext_info table
     * These are items that aren't used normally, and often large/informational only
     * @param string $field
     * @param string $value
     * @param integer $song_id
     * @param integer $level
     * @param boolean $check_owner
     * @return PDOStatement|boolean
     */
    private static function _update_ext_item($field, $value, $song_id, $level, $check_owner = false)
    {
        if ($check_owner) {
            $item = new Song($song_id);
            if ($item->id && $item->get_user_owner() == Core::get_global('user')->id) {
                $level = 25;
            }
        }

        /* Check them rights boy! */
        if (!Access::check('interface', $level)) {
            return false;
        }

        $sql = "UPDATE `song_data` SET `$field` = ? WHERE `song_id` = ?";

        return Dba::write($sql, array($value, $song_id));
    } // _update_ext_item

    /**
     * format
     * This takes the current song object
     * and does a ton of formating on it creating f_??? variables on the current
     * object
     * @param boolean $details
     */
    public function format($details = true)
    {
        if ($details) {
            $this->fill_ext_info();

            // Get the top tags
            $this->tags   = Tag::get_top_tags('song', $this->id);
            $this->f_tags = Tag::get_display($this->tags, true, 'song');
        }
        // force the album artist.
        $album             = new Album($this->album);
        $this->albumartist = (!empty($this->albumartist)) ? $this->albumartist : $album->album_artist;

        // fix missing song disk (where is this coming from?)
        $this->disk = ($this->disk) ? $this->disk : $album->disk;

        // Format the album name
        $this->f_album_full = $this->get_album_name();
        $this->f_album      = $this->f_album_full;

        // Format the artist name
        $this->f_artist_full = $this->get_artist_name();
        $this->f_artist      = $this->f_artist_full;

        // Format the album_artist name
        $this->f_albumartist_full = $this->get_album_artist_name();

        // Format the title
        $this->f_title_full = $this->title;
        $this->f_title      = $this->title;

        // Create Links for the different objects
        $this->link          = AmpConfig::get('web_path') . "/song.php?action=show_song&song_id=" . $this->id;
        $this->f_link        = "<a href=\"" . scrub_out($this->link) . "\" title=\"" . scrub_out($this->f_artist) . " - " . scrub_out($this->title) . "\"> " . scrub_out($this->f_title) . "</a>";
        $this->f_album_link  = "<a href=\"" . AmpConfig::get('web_path') . "/albums.php?action=show&amp;album=" . $this->album . "\" title=\"" . scrub_out($this->f_album_full) . "\"> " . scrub_out($this->f_album) . "</a>";
        $this->f_artist_link = "<a href=\"" . AmpConfig::get('web_path') . "/artists.php?action=show&amp;artist=" . $this->artist . "\" title=\"" . scrub_out($this->f_artist_full) . "\"> " . scrub_out($this->f_artist) . "</a>";
        if (!empty($this->albumartist)) {
            $this->f_albumartist_link = "<a href=\"" . AmpConfig::get('web_path') . "/artists.php?action=show&amp;artist=" . $this->albumartist . "\" title=\"" . scrub_out($this->f_albumartist_full) . "\"> " . scrub_out($this->f_albumartist_full) . "</a>";
        }

        // Format the Bitrate
        $this->f_bitrate = (int) ($this->bitrate / 1000) . "-" . strtoupper((string) $this->mode);

        // Format the Time
        $min            = floor($this->time / 60);
        $sec            = sprintf("%02d", ($this->time % 60));
        $this->f_time   = $min . ":" . $sec;
        $hour           = sprintf("%02d", floor($min / 60));
        $min_h          = sprintf("%02d", ($min % 60));
        $this->f_time_h = $hour . ":" . $min_h . ":" . $sec;

        // Format the track (there isn't really anything to do here)
        $this->f_track = (string) $this->track;

        // Format the size
        $this->f_size = UI::format_bytes($this->size);

        $this->f_lyrics = "<a title=\"" . scrub_out($this->title) . "\" href=\"" . AmpConfig::get('web_path') . "/song.php?action=show_lyrics&song_id=" . $this->id . "\">" . T_('Show Lyrics') . "</a>";

        $this->f_file = $this->f_artist . ' - ';
        if ($this->track) {
            $this->f_file .= $this->track . ' - ';
        }
        $this->f_file .= $this->f_title . '.' . $this->type;

        $this->f_publisher = $this->label;
        $this->f_composer  = $this->composer;

        $year              = (int) $this->year;
        $this->f_year_link = "<a href=\"" . AmpConfig::get('web_path') . "/search.php?type=album&action=search&limit=0&rule_1=year&rule_1_operator=2&rule_1_input=" . $year . "\">" . $year . "</a>";

        if (AmpConfig::get('licensing') && $this->license !== null) {
            $license = new License($this->license);
            $license->format();
            $this->f_license = $license->f_link;
        }
    } // format

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        $keywords               = array();
        $keywords['mb_trackid'] = array('important' => false,
            'label' => T_('Track MusicBrainzID'),
            'value' => $this->mbid);
        $keywords['artist'] = array('important' => true,
            'label' => T_('Artist'),
            'value' => $this->f_artist);
        $keywords['title'] = array('important' => true,
            'label' => T_('Title'),
            'value' => $this->f_title);

        return $keywords;
    }

    /**
     * Get item fullname.
     * @return string
     */
    public function get_fullname()
    {
        return $this->f_title;
    }

    /**
     * Get parent item description.
     * @return array|null
     */
    public function get_parent()
    {
        return array('object_type' => 'album', 'object_id' => $this->album);
    }

    /**
     * Get item children.
     * @return array
     */
    public function get_childrens()
    {
        return array();
    }

    /**
     * Search for item children.
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        debug_event(self::class, 'search_childrens ' . $name, 5);

        return array();
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
            $medias[] = array(
                'object_type' => 'song',
                'object_id' => $this->id
            );
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
        if ($this->user_upload !== null) {
            return $this->user_upload;
        }

        return null;
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
        if (!empty($this->comment)) {
            return $this->comment;
        }

        $album = new Album($this->album);
        $album->format();

        return $album->get_description();
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        $object_id = null;
        $type      = null;

        if (Art::has_db($this->id, 'song')) {
            $object_id = $this->id;
            $type      = 'song';
        } else {
            if (Art::has_db($this->album, 'album')) {
                $object_id = $this->album;
                $type      = 'album';
            } else {
                if (Art::has_db($this->artist, 'artist') || $force) {
                    $object_id = $this->artist;
                    $type      = 'artist';
                }
            }
        }

        if ($object_id !== null && $type !== null) {
            Art::display($type, $object_id, $this->get_fullname(), $thumb, $this->link);
        }
    }

    /**
     * get_fields
     * This returns all of the 'data' fields for this object, we need to filter out some that we don't
     * want to present to a user, and add some that don't exist directly on the object but are related
     * @return array
     */
    public static function get_fields()
    {
        $fields = get_class_vars('Song');

        unset($fields['id'], $fields['_transcoded'], $fields['_fake'], $fields['cache_hit'], $fields['mime'], $fields['type']);

        // Some additional fields
        $fields['tag']     = true;
        $fields['catalog'] = true;
        // FIXME: These are here to keep the ideas, don't want to have to worry about them for now
        //        $fields['rating'] = true;
        //        $fields['recently Played'] = true;

        return $fields;
    } // get_fields

    /**
     * get_from_path
     * This returns all of the songs that exist under the specified path
     * @param string $path
     * @return integer[]
     */
    public static function get_from_path($path)
    {
        $path = Dba::escape($path);

        $sql        = "SELECT * FROM `song` WHERE `file` LIKE '$path%'";
        $db_results = Dba::read($sql);

        $songs = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $songs[] = $row['id'];
        }

        return $songs;
    } // get_from_path

    /**
     * @function    get_rel_path
     * @discussion    returns the path of the song file stripped of the catalog path
     *        used for mpd playback
     * @param string $file_path
     * @param integer $catalog_id
     * @return string
     */
    public function get_rel_path($file_path = null, $catalog_id = 0)
    {
        $info = null;
        if ($file_path === null) {
            $info      = $this->has_info();
            $file_path = $info['file'];
        }
        if (!$catalog_id) {
            if (!is_array($info)) {
                $info = $this->has_info();
            }
            $catalog_id = $info['catalog'];
        }
        $catalog = Catalog::create_from_id($catalog_id);

        return $catalog->get_rel_path($file_path);
    } // get_rel_path

    /**
     * Generate a simple play url.
     * @param integer $uid
     * @param string $player
     * @return string
     */
    public function get_play_url($uid = -1, $player = '')
    {
        if (!$this->id) {
            return '';
        }
        // set no use when using auth
        if (!AmpConfig::get('use_auth') && !AmpConfig::get('require_session')) {
            $uid = -1;
        }

        $media_name = $this->get_stream_name() . "." . $this->type;
        $media_name = preg_replace("/[^a-zA-Z0-9\. ]+/", "-", $media_name);
        $media_name = rawurlencode($media_name);

        $url = Stream::get_base_url() . "type=song&oid=" . $this->id . "&uid=" . (string) $uid;
        if ($player !== '') {
            $url .= "&client=" . $player;
        }
        $url .= "&name=" . $media_name;

        return $url;
    }

    /**
     * play_url
     * This function takes all the song information and correctly formats a
     * a stream URL taking into account the downsampling mojo and everything
     * else, this is the true function
     * @param string $additional_params
     * @param string $player
     * @param boolean $local
     * @param integer $uid
     * @return string
     */
    public function play_url($additional_params = '', $player = '', $local = false, $uid = false)
    {
        if (!$this->id) {
            return '';
        }
        if (!$uid) {
            // No user in the case of upnp. Set to 0 instead. required to fix database insertion errors
            $uid = Core::get_global('user')->id ?: 0;
        }
        // set no user when not using auth
        if (!AmpConfig::get('use_auth') && !AmpConfig::get('require_session')) {
            $uid = -1;
        }

        $type = $this->type;

        $this->format();
        $media_name = $this->get_stream_name() . "." . $type;
        $media_name = preg_replace("/[^a-zA-Z0-9\. ]+/", "-", $media_name);
        $media_name = rawurlencode($media_name);

        $url = Stream::get_base_url($local) . "type=song&oid=" . $this->id . "&uid=" . (string) $uid . $additional_params;
        if ($player !== '') {
            $url .= "&player=" . $player;
        }
        $url .= "&name=" . $media_name;

        return Stream_URL::format($url);
    } // play_url

    /**
     * Get stream name.
     * @return string
     */
    public function get_stream_name()
    {
        return $this->get_artist_name() . " - " . $this->title;
    }

    /**
     * get_recently_played
     * This function returns the last X songs that have been played
     * it uses the popular threshold to figure out how many to pull
     * it will only return unique object
     * @param integer $user_id
     * @return array
     */
    public static function get_recently_played($user_id = 0)
    {
        $personal_info_recent = 91;
        $personal_info_time   = 92;
        $personal_info_agent  = 93;

        $results = array();
        $limit   = AmpConfig::get('popular_threshold', 10);
        $sql     = "SELECT `object_id`, `object_count`.`user`, `object_type`, `date`, `agent`, `geo_latitude`, `geo_longitude`, `geo_name`, `pref_recent`.`value` AS `user_recent`, `pref_time`.`value` AS `user_time`, `pref_agent`.`value` AS `user_agent` " .
                   "FROM `object_count`" .
                   "LEFT JOIN `user_preference` AS `pref_recent` ON `pref_recent`.`preference`='$personal_info_recent' AND `pref_recent`.`user` = `object_count`.`user`" .
                   "LEFT JOIN `user_preference` AS `pref_time` ON `pref_time`.`preference`='$personal_info_time' AND `pref_time`.`user` = `object_count`.`user`" .
                   "LEFT JOIN `user_preference` AS `pref_agent` ON `pref_agent`.`preference`='$personal_info_agent' AND `pref_agent`.`user` = `object_count`.`user`" .
                   "WHERE `object_type` = 'song' AND `count_type` = 'stream' ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND " . Catalog::get_enable_filter('song', '`object_id`') . " ";
        }
        if ($user_id > 0) {
            // If user is not empty, we're looking directly to user personal info (admin view)
            $sql .= "AND `object_count`.`user`='$user_id' ";
        } else {
            if (!Access::check('interface', 100)) {
                // If user identifier is empty, we need to retrieve only users which have allowed view of personal info
                $current_user = (int) Core::get_global('user')->id;
                if ($current_user > 0) {
                    $sql .= "AND `object_count`.`user` IN (SELECT `user` FROM `user_preference` WHERE (`preference`='$personal_info_recent' AND `value`='1') OR `user`='$current_user') ";
                }
            }
        }
        $sql .= "ORDER BY `date` DESC LIMIT " . (string) $limit . " ";

        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            if (empty($row['geo_name']) && $row['latitude'] && $row['longitude']) {
                $row['geo_name'] = Stats::get_cached_place_name($row['latitude'], $row['longitude']);
            }
            $results[] = $row;
        }

        return $results;
    } // get_recently_played

    /**
     * Get stream types.
     * @param string $player
     * @return array
     */
    public function get_stream_types($player = null)
    {
        return Song::get_stream_types_for_type($this->type, $player);
    }

    /**
     * Get stream types for media type.
     * @param string $type
     * @param string $player
     * @return array
     */
    public static function get_stream_types_for_type($type, $player = '')
    {
        $types     = array();
        $transcode = AmpConfig::get('transcode_' . $type);
        if ($player !== '') {
            $player_transcode = AmpConfig::get('transcode_player_' . $player . '_' . $type);
            if ($player_transcode) {
                $transcode = $player_transcode;
            }
        }

        if ($transcode != 'required') {
            $types[] = 'native';
        }
        if (make_bool($transcode)) {
            $types[] = 'transcode';
        }

        return $types;
    }

    /**
     * Get transcode settings for media.
     * It can be confusing but when waveforms are enabled
     * it will transcode the file twice.
     *
     * @param string $source
     * @param string $target
     * @param string $player
     * @param string $media_type
     * @param array $options
     * @return array
     */
    public static function get_transcode_settings_for_media($source, $target = null, $player = null, $media_type = 'song', $options = array())
    {
        // default target for songs
        $setting_target = 'encode_target';
        // default target for video
        if ($media_type != 'song') {
            $setting_target = 'encode_' . $media_type . '_target';
        }
        // webplayer / api transcode actions
        $has_player_target = false;
        if ($player) {
            // encode target for songs in webplayer/api
            $player_setting_target = 'encode_player_' . $player . '_target';
            if ($media_type != 'song') {
                // encode target for video in webplayer/api
                $player_setting_target = 'encode_' . $media_type . '_player_' . $player . '_target';
            }
            $has_player_target  = AmpConfig::get($player_setting_target);
        }
        $has_default_target = AmpConfig::get($setting_target);
        $has_codec_target   = AmpConfig::get('encode_target_' . $source);

        // Fall backwards from the specific transcode formats to default
        // TARGET > PLAYER > CODEC > DEFAULT
        if ($target) {
            debug_event(self::class, 'Explicit target requested: {' . $target . '} format for: ' . $source, 5);
        } elseif ($has_player_target) {
            $target = $has_player_target;
            debug_event(self::class, 'Transcoding for ' . $player . ': {' . $target . '} format for: ' . $source, 5);
        } elseif ($has_codec_target) {
            $target = $has_codec_target;
            debug_event(self::class, 'Transcoding for codec: {' . $target . '} format for: ' . $source, 5);
        } elseif ($has_default_target) {
            $target = $has_default_target;
            debug_event(self::class, 'Transcoding to default: {' . $target . '} format for: ' . $source, 5);
        }
        // fall back to resampling if no defuault
        if (!$target) {
            $target = $source;
            debug_event(self::class, 'No transcode target for: ' . $source . ', choosing to resample', 5);
        }

        $cmd  = AmpConfig::get('transcode_cmd_' . $source) ?: AmpConfig::get('transcode_cmd');
        $args = '';
        if (AmpConfig::get('encode_ss_frame') && isset($options['frame'])) {
            $args .= ' ' . AmpConfig::get('encode_ss_frame');
        }
        if (AmpConfig::get('encode_ss_duration') && isset($options['duration'])) {
            $args .= ' ' . AmpConfig::get('encode_ss_duration');
        }

        $args .= ' ' . AmpConfig::get('transcode_input');

        if (AmpConfig::get('encode_srt') && $options['subtitle']) {
            debug_event(self::class, 'Using subtitle ' . $options['subtitle'], 5);
            $args .= ' ' . AmpConfig::get('encode_srt');
        }

        $argst = AmpConfig::get('encode_args_' . $target);
        if (!$args) {
            debug_event(self::class, 'Target format ' . $target . ' is not properly configured', 2);

            return array();
        }
        $args .= ' ' . $argst;

        debug_event(self::class, 'Command: ' . $cmd . ' Arguments:' . $args, 5);

        return array('format' => $target, 'command' => $cmd . $args);
    }

    /**
     * Get transcode settings.
     * @param string $target
     * @param string $player
     * @param array $options
     * @return array
     */
    public function get_transcode_settings($target = null, $player = null, $options = array())
    {
        return Song::get_transcode_settings_for_media($this->type, $target, $player, 'song', $options);
    }

    /**
     * Get lyrics.
     * @return array
     */
    public function get_lyrics()
    {
        if ($this->lyrics) {
            return array('text' => $this->lyrics);
        }

        foreach (Plugin::get_plugins('get_lyrics') as $plugin_name) {
            $plugin = new Plugin($plugin_name);
            if ($plugin->load(Core::get_global('user'))) {
                $lyrics = $plugin->_plugin->get_lyrics($this);
                if ($lyrics != false) {
                    return $lyrics;
                }
            }
        }

        return null;
    }

    /**
     * Run custom play action.
     * @param integer $action_index
     * @param string $codec
     * @return array
     */
    public function run_custom_play_action($action_index, $codec = '')
    {
        $transcoder = array();
        $actions    = Song::get_custom_play_actions();
        if ($action_index <= count($actions)) {
            $action = $actions[$action_index - 1];
            if (!$codec) {
                $codec = $this->type;
            }

            $run = str_replace("%f", $this->file, $action['run']);
            $run = str_replace("%c", $codec, $run);
            $run = str_replace("%a", $this->f_artist, $run);
            $run = str_replace("%A", $this->f_album, $run);
            $run = str_replace("%t", $this->f_title, $run);

            debug_event(self::class, "Running custom play action: " . $run, 3);

            $descriptors = array(1 => array('pipe', 'w'));
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                // Windows doesn't like to provide stderr as a pipe
                $descriptors[2] = array('pipe', 'w');
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
     * Show custom play actions.
     */
    public function show_custom_play_actions()
    {
        $actions = Song::get_custom_play_actions();
        foreach ($actions as $action) {
            echo Ajax::button('?page=stream&action=directplay&object_type=song&object_id=' . $this->id . '&custom_play_action=' . $action['index'], $action['icon'], T_($action['title']), $action['icon'] . '_song_' . $this->id);
        }
    }

    /**
     * Get custom play actions.
     * @return array
     */
    public static function get_custom_play_actions()
    {
        $actions = array();
        $count   = 0;
        while (AmpConfig::get('custom_play_action_title_' . $count)) {
            $actions[] = array(
                'index' => ($count + 1),
                'title' => AmpConfig::get('custom_play_action_title_' . $count),
                'icon' => AmpConfig::get('custom_play_action_icon_' . $count),
                'run' => AmpConfig::get('custom_play_action_run_' . $count),
            );
            ++$count;
        }

        return $actions;
    }

    /**
     * get_metadata
     * Get an array of metadata
     * for writing id3 file tags.
     * @return array
     */
    public function get_metadata()
    {
        $meta = array();

        $meta['year']                  = $this->year;
        $meta['time']                  = $this->time;
        $meta['title']                 = $this->title;
        $meta['comment']               = $this->comment;
        $meta['album']                 = $this->f_album_full;
        $meta['artist']                = $this->f_artist_full;
        $meta['band']                  = $this->f_albumartist_full;
        $meta['composer']              = $this->composer;
        $meta['publisher']             = $this->f_publisher;
        $meta['track_number']          = $this->f_track;
        $meta['part_of_a_set']         = $this->disk;
        $meta['genre']                 = array();
        if (!empty($this->tags)) {
            foreach ($this->tags as $tag) {
                if (!in_array($tag['name'], $meta['genre'])) {
                    $meta['genre'][] = $tag['name'];
                }
            }
        }
        $meta['genre'] = implode(',', $meta['genre']);

        return $meta;
    }

    /**
     * get_vorbis_metadata
     * @return array
     */
    public function get_vorbis_metadata()
    {
        $meta = array();

        $meta['date']                  = $this->year;
        $meta['time']                  = $this->time;
        $meta['title']                 = $this->title;
        $meta['comment']               = $this->comment;
        $meta['album']                 = $this->f_album_full;
        $meta['artist']                = $this->f_artist_full;
        $meta['albumartist']           = $this->f_albumartist_full;
        $meta['composer']              = $this->composer;
        $meta['publisher']             = $this->f_publisher;
        $meta['track']                 = $this->f_track;
        $meta['discnumber']            = $this->disk;
        $meta['genre']                 = array();
        if (!empty($this->tags)) {
            foreach ($this->tags as $tag) {
                if (!in_array($tag['name'], $meta['genre'])) {
                    $meta['genre'][] = $tag['name'];
                }
            }
        }
        $meta['genre'] = implode(',', $meta['genre']);

        return $meta;
    }

    /**
     * getId
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Update Metadata from array
     * @param array $meta_value
     */
    public function updateMetadata($meta_value)
    {
        foreach ($meta_value as $metadataId => $value) {
            $metadata = $this->metadataRepository->findById($metadataId);
            if (!$metadata || $value != $metadata->getData()) {
                $metadata->setData($value);
                $this->metadataRepository->update($metadata);
            }
        }
    }

    /**
     * remove
     * Remove the song from disk.
     * @return PDOStatement|boolean
     */
    public function remove()
    {
        if (file_exists($this->file)) {
            $deleted = unlink($this->file);
        } else {
            $deleted = true;
        }
        if ($deleted === true) {
            $sql     = "DELETE FROM `song` WHERE `id` = ?";
            $deleted = Dba::write($sql, array($this->id));
            if ($deleted) {
                Art::garbage_collection('song', $this->id);
                Userflag::garbage_collection('song', $this->id);
                Rating::garbage_collection('song', $this->id);
                Shoutbox::garbage_collection('song', $this->id);
                Useractivity::garbage_collection('song', $this->id);
            }
        } else {
            debug_event(self::class, 'Cannot delete ' . $this->file . 'file. Please check permissions.', 1);
        }

        return $deleted;
    }
} // end song.class
