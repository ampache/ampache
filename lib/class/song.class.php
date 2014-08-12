<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

class Song extends database_object implements media, library_item
{
    /* Variables from DB */

    /**
     * @var int $id
     */
    public $id;
    /**
     * @var string $file
     */
    public $file;
    /**
     * @var int $album
     */
    public $album;
    /**
     * @var int $album_artist
     */
    public $album_artist;
    /**
     * @var int $artist
     */
    public $artist;
    /**
     * @var string $title
     */
    public $title;
    /**
     * @var int $year
     */
    public $year;
    /**
     * @var int $bitrate
     */
    public $bitrate;
    /**
     * @var int $rate
     */
    public $rate;
    /**
     * @var string $mode
     */
    public $mode;
    /**
     * @var int $size
     */
    public $size;
    /**
     * @var int $time
     */
    public $time;
    /**
     * @var int $track
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
     * @var int $addition_time
     */
    public $addition_time;
    /**
     * @var int $update_time
     */
    public $update_time;
    /**
     * MusicBrainz ID
     * @var string $mbid
     */
    public $mbid;
    /**
     * @var int $catalog
     */
    public $catalog;
    /**
     * @var int|null $waveform
     */
    public $waveform;
    /**
     * @var int|null $user_upload
     */
    public $user_upload;
    /**
     * @var int|null $license
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
     * @var int $channels
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
     * @var string $f_album_artist_full
     */
    public $f_album_artist_full;
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
     * @var string $f_album_artist_link
     */
    public $f_album_artist_link;
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
     * @var int $count
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

    /* Setting Variables */
    /**
     * @var boolean $_fake
     */
    public $_fake = false; // If this is a 'construct_from_array' object

    /**
     * Constructor
     *
     * Song class, for modifing a song.
     * @param int|null $id
     */
    public function __construct($id = null)
    {
        if (!$id) { return false; }

        $this->id = intval($id);

        if ($info = $this->_get_info()) {
            foreach ($info as $key => $value) {
                $this->$key = $value;
            }
            $data = pathinfo($this->file);
            $this->type = strtolower($data['extension']);
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
     * @return int|boolean
     */
    public static function insert(array $results)
    {
        $catalog = $results['catalog'];
        $file = $results['file'];
        $title = trim($results['title']) ?: $file;
        $artist = $results['artist'];
        $album = $results['album'];
        $album_artist = $results['albumartist'] ?: $results['band'];
        $album_artist = $album_artist ?: null;
        $bitrate = $results['bitrate'] ?: 0;
        $rate = $results['rate'] ?: 0;
        $mode = $results['mode'];
        $size = $results['size'] ?: 0;
        $time = $results['time'] ?: 0;
        $track = $results['track'];
        $track_mbid = $results['mb_trackid'] ?: $results['mbid'];
        $track_mbid = $track_mbid ?: null;
        $album_mbid = $results['mb_albumid'];
        $album_mbid_group = $results['mb_albumid_group'];
        $artist_mbid = $results['mb_artistid'];
        $album_artist_mbid = $results['mb_albumartistid'];
        $disk = $results['disk'] ?: 0;
        $year = $results['year'] ?: 0;
        $comment = $results['comment'];
        $tags = $results['genre']; // multiple genre support makes this an array
        $lyrics = $results['lyrics'];
        $user_upload = isset($results['user_upload']) ? $results['user_upload'] : null;
        $license = isset($results['license']) ? $results['license'] : null;
        $composer = isset($results['composer']) ? $results['composer'] : null;
        $label = isset($results['publisher']) ? $results['publisher'] : null;
        $catalog_number = isset($results['catalog_number']) ? $results['catalog_number'] : null;
        $language = isset($results['language']) ? $results['language'] : null;
        $channels = $results['channels'] ?: 0;
        $release_type = isset($results['release_type']) ? $results['release_type'] : null;

        $artist_id = Artist::check($artist, $artist_mbid);
        $album_artist_id = null;
        if ($album_artist) {
            $album_artist_id = Artist::check($album_artist, $album_artist_mbid);
        }
        $album_id = Album::check($album, $year, $disk, $album_mbid, $album_mbid_group, $album_artist_id, $release_type);

        $sql = 'INSERT INTO `song` (`file`, `catalog`, `album`, `artist`, ' .
            '`title`, `bitrate`, `rate`, `mode`, `size`, `time`, `track`, ' .
            '`addition_time`, `year`, `mbid`, `user_upload`, `license`, `album_artist`, ' .
            '`composer`, `channels`) ' .
            'VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $db_results = Dba::write($sql, array(
            $file, $catalog, $album_id, $artist_id,
            $title, $bitrate, $rate, $mode, $size, $time, $track,
            time(), $year, $track_mbid, $user_upload, $license, $album_artist_id,
            $composer, $channels));

        if (!$db_results) {
            debug_event('song', 'Unable to insert ' . $file, 2);
            return false;
        }

        $song_id = Dba::insert_id();

        if (is_array($tags)) {
            foreach ($tags as $tag) {
                $tag = trim($tag);
                if (!empty($tag)) {
                    Tag::add('song', $song_id, $tag, false);
                    Tag::add('album', $album_id, $tag, false);
                    Tag::add('artist', $artist_id, $tag, false);
                }
            }
        }

        $sql = 'INSERT INTO `song_data` (`song_id`, `comment`, `lyrics`, `label`, `language`, `catalog_number`) ' .
            'VALUES(?, ?, ?, ?, ?, ?)';
        Dba::write($sql, array($song_id, $comment, $lyrics, $label, $language, $catalog_number));

        return $song_id;
    }

    /**
     * gc
     *
     * Cleans up the song_data table
     */
    public static function gc()
    {
        Dba::write('DELETE FROM `song_data` USING `song_data` LEFT JOIN `song` ON `song`.`id` = `song_data`.`song_id` WHERE `song`.`id` IS NULL');
    }

    /**
     * build_cache
     *
     * This attempts to reduce queries by asking for everything in the
     * browse all at once and storing it in the cache, this can help if the
     * db connection is the slow point.
     * @param int[] $song_ids
     * @return boolean
     */
    public static function build_cache($song_ids)
    {
        if (!is_array($song_ids) || !count($song_ids)) { return false; }

        $idlist = '(' . implode(',', $song_ids) . ')';

        // Callers might have passed array(false) because they are dumb
        if ($idlist == '()') { return false; }

        // Song data cache
        $sql = 'SELECT `song`.`id`, `file`, `catalog`, `album`, ' .
            '`year`, `artist`, `title`, `bitrate`, `rate`, ' .
            '`mode`, `size`, `time`, `track`, `played`, ' .
            '`song`.`enabled`, `update_time`, `tag_map`.`tag_id`, '.
            '`mbid`, `addition_time`, `license`, `composer` ' .
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
        $albums = array();
        $tags = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            if (AmpConfig::get('show_played_times')) {
                $row['object_cnt'] = Stats::get_object_count('song', $row['id']);
            }
            parent::add_to_cache('song', $row['id'], $row);
            $artists[$row['artist']] = $row['artist'];
            $albums[$row['album']] = $row['album'];
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
        $sql = "SELECT * FROM `song_data` WHERE `song_id` IN $idlist";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('song_data', $row['song_id'], $row);
        }

        return true;

    } // build_cache

    /**
     * _get_info
     * @return array|boolean
     */
    private function _get_info()
    {
        $id = $this->id;

        if (parent::is_cached('song', $id)) {
            return parent::get_from_cache('song', $id);
        }

        $sql = 'SELECT `song`.`id`, `song`.`file`, `song`.`catalog`, `song`.`album`, `song`.`album_artist`, `song`.`year`, `song`.`artist`,' .
            '`song`.`title`, `song`.`bitrate`, `song`.`rate`, `song`.`mode`, `song`.`size`, `song`.`time`, `song`.`track`, ' .
            '`song`.`played`, `song`.`enabled`, `song`.`update_time`, `song`.`mbid`, `song`.`addition_time`, `song`.`license`, ' .
            '`song`.`composer`, `album`.`mbid` AS `album_mbid`, `artist`.`mbid` AS `artist_mbid`, `albumartist`.`mbid` AS `albumartist_mbid` ' .
            'FROM `song` LEFT JOIN `album` ON `album`.`id` = `song`.`album` LEFT JOIN `artist` ON `artist`.`id` = `song`.`artist` ' .
            'LEFT JOIN `artist` AS `albumartist` ON `albumartist`.`id` = `song`.`album_artist` ' .
            'WHERE `song`.`id` = ?';
        $db_results = Dba::read($sql, array($id));

        $results = Dba::fetch_assoc($db_results);
        if (isset($results['id'])) {
            if (AmpConfig::get('show_played_times')) {
                $results['object_cnt'] = Stats::get_object_count('song', $results['id']);
            }

            parent::add_to_cache('song', $id, $results);
            return $results;
        }

        return false;
    }

    /**
      * _get_ext_info
     * This function gathers information from the song_ext_info table and adds it to the
     * current object
     * @return array
     */
    public function _get_ext_info()
    {
        $id = intval($this->id);

        if (parent::is_cached('song_data',$id)) {
            return parent::get_from_cache('song_data',$id);
        }

        $sql = "SELECT * FROM song_data WHERE `song_id` = ?";
        $db_results = Dba::read($sql, array($id));

        $results = Dba::fetch_assoc($db_results);

        parent::add_to_cache('song_data',$id,$results);

        return $results;

    } // _get_ext_info

    /**
      * fill_ext_info
     * This calls the _get_ext_info and then sets the correct vars
     */
    public function fill_ext_info()
    {
        $info = $this->_get_ext_info();

        foreach ($info as $key=>$value) {
            if ($key != 'song_id') {
                $this->$key = $value;
            }
        } // end foreach

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
            case 'wma':
            case 'asf':
                return 'audio/x-ms-wma';
            case 'mp3':
            case 'mpeg3':
                return 'audio/mpeg';
            case 'rm':
            case 'ra':
                return 'audio/x-realaudio';
            case 'flac';
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
            default:
                return 'audio/mpeg';
        }
    }

    /**
     * get_disabled
     *
     * Gets a list of the disabled songs for and returns an array of Songs
     * @param int $count
     * @return int[]
     */
    public static function get_disabled($count = 0)
    {
        $results = array();

        $sql = "SELECT `id` FROM `song` WHERE `enabled`='0'";
        if ($count) { $sql .= " LIMIT $count"; }
        $db_results = Dba::read($sql);

        while ($r = Dba::fetch_assoc($db_results)) {
            $results[] = new Song($r['id']);
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
        $sql = 'SELECT `id`, `artist`, `album`, `title`, ' .
            'COUNT(`title`) FROM `song` ' . $where_sql .
            ' GROUP BY `title`';

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
     * Get duplicate information.
     * @param array $dupe
     * @param string $search_type
     * @return int[]
     */
    public static function get_duplicate_info($dupe, $search_type)
    {
        $sql = 'SELECT `id` FROM `song` ' .
            "WHERE `title`='" . Dba::escape($dupe['title']) . "' ";

        if ($search_type == 'artist_title' ||
            $search_type == 'artist_album_title') {
            $sql .= "AND `artist`='" . Dba::escape($dupe['artist']) . "' ";
        }
        if ($search_type == 'artist_album_title') {
            $sql .= "AND `album` = '" . Dba::escape($dupe['album']) . "' ";
        }

        $sql .= 'ORDER BY `time`,`bitrate`,`size`';
        $db_results = Dba::read($sql);

        $results = array();

        while ($item = Dba::fetch_assoc($db_results)) {
            $results[] = $item['id'];
        } // end while

        return $results;
    }

    /**
     * get_album_name
     * gets the name of $this->album, allows passing of id
     * @param int $album_id
     * @return string
     */
    public function get_album_name($album_id=0)
    {
        if (!$album_id) { $album_id = $this->album; }
          $album = new Album($album_id);
        if ($album->prefix)
          return $album->prefix . " " . $album->name;
        else
          return $album->name;
    } // get_album_name

    /**
     * get_artist_name
     * gets the name of $this->artist, allows passing of id
     * @param int $artist_id
     * @return string
     */
    public function get_artist_name($artist_id=0)
    {
        if (!$artist_id) { $artist_id = $this->artist; }
        $artist = new Artist($artist_id);
        if ($artist->prefix)
          return $artist->prefix . " " . $artist->name;
        else
          return $artist->name;

    } // get_artist_name

    /**
     * get_album_artist_name
     * gets the name of $this->album_artist, allows passing of id
     * @param int $album_artist_id
     * @return string
     */
    public function get_album_artist_name($album_artist_id=0)
    {
        if (!$album_artist_id) { $album_artist_id = $this->album_artist; }
        $album_artist = new Artist($album_artist_id);
        if ($album_artist->prefix)
          return $album_artist->prefix . " " . $album_artist->name;
        else
          return $album_artist->name;

    } // get_album_artist_name

    /**
     * set_played
     * this checks to see if the current object has been played
     * if not then it sets it to played. In any case it updates stats.
     * @param int $user
     * @param string $agent
     * @return boolean
     */
    public function set_played($user, $agent)
    {
        Stats::insert('song', $this->id, $user, $agent);
        Stats::insert('album', $this->album, $user, $agent);
        Stats::insert('artist', $this->artist, $user, $agent);

        if ($this->played) {
            return true;
        }

        /* If it hasn't been played, set it! */
        self::update_played('1', $this->id);

        return true;

    } // set_played

    /**
     * compare_song_information
     * this compares the new ID3 tags of a file against
     * the ones in the database to see if they have changed
     * it returns false if nothing has changes, or the true
     * if they have. Static because it doesn't need this
     * @param \Song $song
     * @param \Song $new_song
     * @return array
     */
    public static function compare_song_information(Song $song, Song $new_song)
    {
        // Remove some stuff we don't care about
        unset($song->catalog,$song->played,$song->enabled,$song->addition_time,$song->update_time,$song->type);

        $array = array();
        $string_array = array('title','comment','lyrics');
        $skip_array = array('id','tag_id','mime','mb_artistid','mbid');

        // Pull out all the currently set vars
        $fields = get_object_vars($song);

        // Foreach them
        foreach ($fields as $key=>$value) {
            if (in_array($key,$skip_array)) { continue; }
            // If it's a stringie thing
            if (in_array($key,$string_array)) {
                if (trim(stripslashes($song->$key)) != trim(stripslashes($new_song->$key))) {
                    $array['change'] = true;
                    $array['element'][$key] = 'OLD: ' . $song->$key . ' --> ' . $new_song->$key;
                }
            } // in array of stringies
            else {
                if ($song->$key != $new_song->$key) {
                    $array['change'] = true;
                    $array['element'][$key] = 'OLD:' . $song->$key . ' --> ' . $new_song->$key;
                }
            } // end else

        } // end foreach

        if ($array['change']) {
            debug_event('song-diff', json_encode($array['element']), 5);
        }

        return $array;

    } // compare_song_information


    /**
     * update
     * This takes a key'd array of data does any cleaning it needs to
     * do and then calls the helper functions as needed.
     * @param array $data
     * @return int
     */
    public function update(array $data)
    {
        foreach ($data as $key=>$value) {
            debug_event('song.class.php', $key.'='.$value, '5');

            switch ($key) {
                case 'artist_name':
                    // Need to create new artist according the name
                    $new_artist_id = Artist::check($value);
                    $this->artist = $new_artist_id;
                    self::update_artist($new_artist_id, $this->id);
                break;
                case 'album_artist_name':
                    // Need to create new artist according the name
                    $new_artist_id = Artist::check($value);
                    $this->album_artist = $new_artist_id;
                    self::update_album_artist($new_artist_id, $this->id);
                break;
                case 'album_name':
                    // Need to create new album according the name
                    $new_album_id = Album::check($value);
                    $this->album = $new_album_id;
                    self::update_album($new_album_id, $this->id);
                break;
                case 'album_artist':
                case 'year':
                case 'title':
                case 'track':
                case 'artist':
                case 'album':
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
                    }
                break;
                case 'edit_tags':
                    Tag::update_tag_list($value, 'song', $this->id);
                break;
                default:
                break;
            } // end whitelist
        } // end foreach

        $this->format();

        if (AmpConfig::get('write_id3')) {
            $catalog = Catalog::create_from_id($this->catalog);
            if ($catalog->get_type() == 'local') {
                $meta = $this->get_metadata();
                $id3 = new vainfo($this->file);
                $id3->write_id3($meta);
            }
        }

        return $this->id;
    } // update

    /**
     * update_song
     * this is the main updater for a song it actually
     * calls a whole bunch of mini functions to update
     * each little part of the song... lastly it updates
     * the "update_time" of the song
     * @param int $song_id
     * @param \Song $new_song
     */
    public static function update_song($song_id, Song $new_song)
    {
        $title = Dba::escape($new_song->title);
        $bitrate = Dba::escape($new_song->bitrate);
        $rate = Dba::escape($new_song->rate);
        $mode = Dba::escape($new_song->mode);
        $size = Dba::escape($new_song->size);
        $time = Dba::escape($new_song->time);
        $track = Dba::escape($new_song->track);
        $mbid = Dba::escape($new_song->mbid);
        $artist = Dba::escape($new_song->artist);
        $album = Dba::escape($new_song->album);
        $album_artist = Dba::escape($new_song->album_artist);
        $year = Dba::escape($new_song->year);
        $song_id = Dba::escape($song_id);
        $update_time = time();

        $sql = "UPDATE `song` SET `album`='$album', `year`='$year', `artist`='$artist', " .
            "`title`='$title', `bitrate`='$bitrate', `rate`='$rate', `mode`='$mode', " .
            "`size`='$size', `time`='$time', `track`='$track', " .
            "`mbid`='$mbid', " .
            "`album_artist`='$album_artist', " .
            "`update_time`='$update_time' WHERE `id`='$song_id'";

        Dba::write($sql);

        $comment     = Dba::escape($new_song->comment);
        $language    = Dba::escape($new_song->language);
        $lyrics        = Dba::escape($new_song->lyrics);

        $sql = "UPDATE `song_data` SET `lyrics`='$lyrics', `language`='$language', `comment`='$comment' " .
            "WHERE `song_id`='$song_id'";
        Dba::write($sql);

    } // update_song

    /**
     * update_year
     * update the year tag
     * @param int $new_year
     * @param int $song_id
     */
    public static function update_year($new_year,$song_id)
    {
        self::_update_item('year',$new_year,$song_id,'50');

    } // update_year

    /**
     * update_label
     * This updates the label tag of the song
     * @param string $new_value
     * @param int $song_id
     */
    public static function update_label($new_value,$song_id)
    {
        self::_update_ext_item('label',$new_value,$song_id,'50');

    } // update_label

    /**
     * update_language
     * This updates the language tag of the song
     * @param string $new_lang
     * @param int $song_id
     */
    public static function update_language($new_lang,$song_id)
    {
        self::_update_ext_item('language',$new_lang,$song_id,'50');

    } // update_language

    /**
     * update_comment
     * updates the comment field
     * @param string $new_comment
     * @param int $song_id
     */
    public static function update_comment($new_comment,$song_id)
    {
        self::_update_ext_item('comment',$new_comment,$song_id,'50');

    } // update_comment

    /**
     * update_lyrics
     * updates the lyrics field
     * @param string $new_lyrics
     * @param int $song_id
     */
    public static function update_lyrics($new_lyrics,$song_id)
    {
        self::_update_ext_item('lyrics',$new_lyrics,$song_id,'50');

    } // update_lyrics

    /**
     * update_title
     * updates the title field
     * @param string $new_title
     * @param int $song_id
     */
    public static function update_title($new_title,$song_id)
    {
        self::_update_item('title',$new_title,$song_id,'50');

    } // update_title

    /**
     * update_composer
     * updates the composer field
     * @param string $new_value
     * @param int $song_id
     */
    public static function update_composer($new_value, $song_id)
    {
        self::_update_item('composer', $new_value, $song_id, '50');

    } // update_composer

    /**
     * update_publisher
     * updates the publisher field
     * @param string $new_value
     * @param int $song_id
     */
    public static function update_publisher($new_value, $song_id)
    {
        self::_update_item('publisher', $new_value, $song_id, '50');

    } // update_publisher

    /**
     * update_album_artist
     * updates the album_artist field
     * @param int $new_album_artist
     * @param int $song_id
     */
    public static function update_album_artist($new_album_artist,$song_id)
    {
        $new_album_artist = intval($new_album_artist);
        if ($new_album_artist <= 0) {
            $new_album_artist = null;
        }

        self::_update_item('album_artist', $new_album_artist, $song_id, '50');
    } // update_album_artist

    /**
     * update_bitrate
     * updates the bitrate field
     * @param int $new_bitrate
     * @param int $song_id
     */
    public static function update_bitrate($new_bitrate,$song_id)
    {
        self::_update_item('bitrate',$new_bitrate,$song_id,'50');

    } // update_bitrate

    /**
     * update_rate
     * updates the rate field
     * @param int $new_rate
     * @param int $song_id
     */
    public static function update_rate($new_rate,$song_id)
    {
        self::_update_item('rate',$new_rate,$song_id,'50');

    } // update_rate

    /**
     * update_mode
     * updates the mode field
     * @param string $new_mode
     * @param int $song_id
     */
    public static function update_mode($new_mode,$song_id)
    {
        self::_update_item('mode',$new_mode,$song_id,'50');

    } // update_mode

    /**
     * update_size
     * updates the size field
     * @param int $new_size
     * @param int $song_id
     */
    public static function update_size($new_size,$song_id)
    {
        self::_update_item('size',$new_size,$song_id,'50');

    } // update_size

    /**
     * update_time
     * updates the time field
     * @param int $new_time
     * @param int $song_id
     */
    public static function update_time($new_time,$song_id)
    {
        self::_update_item('time',$new_time,$song_id,'50');

    } // update_time

    /**
     * update_track
     * this updates the track field
     * @param int $new_track
     * @param int $song_id
     */
    public static function update_track($new_track,$song_id)
    {
        self::_update_item('track',$new_track,$song_id,'50');

    } // update_track

    /**
     * update_mbid
     * updates mbid field
     * @param string $new_mbid
     * @param int $song_id
     */
    public static function update_mbid($new_mbid, $song_id)
    {
        self::_update_item('mbid', $new_mbid, $song_id, '50');

    } // update_mbid

    /**
     * update_license
     * updates license field
     * @param string $new_license
     * @param int $song_id
     */
    public static function update_license($new_license, $song_id)
    {
        self::_update_item('license', $new_license, $song_id, '50');

    } // update_license

    /**
     * update_artist
     * updates the artist field
     * @param int $new_artist
     * @param int $song_id
     */
    public static function update_artist($new_artist,$song_id)
    {
        self::_update_item('artist',$new_artist,$song_id,'50');

    } // update_artist

    /**
     * update_album
     * updates the album field
     * @param int $new_album
     * @param in $song_id
     */
    public static function update_album($new_album,$song_id)
    {
        self::_update_item('album',$new_album,$song_id,'50');

    } // update_album

    /**
     * update_utime
     * sets a new update time
     * @param int $song_id
     * @param int $time
     */
    public static function update_utime($song_id,$time=0)
    {
        if (!$time) { $time = time(); }

        self::_update_item('update_time',$time,$song_id,'75');

    } // update_utime

    /**
     * update_played
     * sets the played flag
     * @param boolean $new_played
     * @param in $song_id
     */
    public static function update_played($new_played,$song_id)
    {
        self::_update_item('played',$new_played,$song_id,'25');

    } // update_played

    /**
     * update_enabled
     * sets the enabled flag
     * @param boolean $new_enabled
     * @param int $song_id
     */
    public static function update_enabled($new_enabled, $song_id)
    {
        self::_update_item('enabled',$new_enabled,$song_id,'75');

    } // update_enabled

    /**
     * _update_item
     * This is a private function that should only be called from within the song class.
     * It takes a field, value song id and level. first and foremost it checks the level
     * against $GLOBALS['user'] to make sure they are allowed to update this record
     * it then updates it and sets $this->{$field} to the new value
     * @param string $field
     * @param mixed $value
     * @param in $song_id
     * @param in $level
     * @return boolean
     */
    private static function _update_item($field, $value, $song_id, $level)
    {
        /* Check them Rights! */
        if (!Access::check('interface',$level)) { return false; }

        /* Can't update to blank */
        if (!strlen(trim($value)) && $field != 'comment' && $field != 'album_artist') { return false; }

        $sql = "UPDATE `song` SET `$field` = ? WHERE `id` = ?";
        return Dba::write($sql, array($value, $song_id));

    } // _update_item

    /**
     * _update_ext_item
     * This updates a song record that is housed in the song_ext_info table
     * These are items that aren't used normally, and often large/informational only
     * @param string $field
     * @param mixed $value
     * @param int $song_id
     * @param int $level
     * @return boolean
     */
    private static function _update_ext_item($field, $value, $song_id, $level)
    {
        /* Check them rights boy! */
        if (!Access::check('interface',$level)) { return false; }

        $sql = "UPDATE `song_data` SET `$field` = ? WHERE `song_id` = ?";
        return Dba::write($sql, array($value, $song_id));

    } // _update_ext_item

    /**
     * format
     * This takes the current song object
     * and does a ton of formating on it creating f_??? variables on the current
     * object
     */
    public function format()
    {
        $this->fill_ext_info();

        // Format the album name
        $this->f_album_full = $this->get_album_name();
        $this->f_album = $this->f_album_full;

        // Format the artist name
        $this->f_artist_full = $this->get_artist_name();
        $this->f_artist = $this->f_artist_full;

        // Format the album_artist name
        $this->f_album_artist_full = $this->get_album_artist_name();

        // Format the title
        $this->f_title_full = $this->title;
        $this->f_title = $this->title;

        // Create Links for the different objects
        $this->link = AmpConfig::get('web_path') . "/song.php?action=show_song&song_id=" . $this->id;
        $this->f_link = "<a href=\"" . scrub_out($this->link) . "\" title=\"" . scrub_out($this->f_artist) . " - " . scrub_out($this->title) . "\"> " . scrub_out($this->f_title) . "</a>";
        $this->f_album_link = "<a href=\"" . AmpConfig::get('web_path') . "/albums.php?action=show&amp;album=" . $this->album . "\" title=\"" . scrub_out($this->f_album_full) . "\"> " . scrub_out($this->f_album) . "</a>";
        $this->f_artist_link = "<a href=\"" . AmpConfig::get('web_path') . "/artists.php?action=show&amp;artist=" . $this->artist . "\" title=\"" . scrub_out($this->f_artist_full) . "\"> " . scrub_out($this->f_artist) . "</a>";
        if (!empty($this->album_artist)) {
            $this->f_album_artist_link = "<a href=\"" . AmpConfig::get('web_path') . "/artists.php?action=show&amp;artist=" . $this->album_artist . "\" title=\"" . scrub_out($this->f_album_artist_full) . "\"> " . scrub_out($this->f_album_artist_full) . "</a>";
        }

        // Format the Bitrate
        $this->f_bitrate = intval($this->bitrate/1000) . "-" . strtoupper($this->mode);

        // Format the Time
        $min = floor($this->time/60);
        $sec = sprintf("%02d", ($this->time%60));
        $this->f_time = $min . ":" . $sec;
        $hour = sprintf("%02d", floor($min/60));
        $min_h = sprintf("%02d", ($min%60));
        $this->f_time_h = $hour . ":" . $min_h . ":" . $sec;

        // Format the track (there isn't really anything to do here)
        $this->f_track = $this->track;

        // Get the top tags
        $this->tags = Tag::get_top_tags('song', $this->id);
        $this->f_tags = Tag::get_display($this->tags, true, 'song');

        // Format the size
        $this->f_size = UI::format_bytes($this->size);

        $this->f_lyrics = "<a title=\"" . scrub_out($this->title) . "\" href=\"" . AmpConfig::get('web_path') . "/song.php?action=show_lyrics&song_id=" . $this->id . "\">" . T_('Show Lyrics') . "</a>";

        $this->f_file = $this->f_artist . ' - ';
        if ($this->track) {
            $this->f_file .= $this->track . ' - ';
        }
        $this->f_file .= $this->f_title . '.' . $this->type;

        $this->f_publisher = $this->publisher;
        $this->f_composer = $this->composer;

    } // format

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        $keywords = array();
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
     * Get item childrens.
     * @return array
     */
    public function get_childrens()
    {
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
        if (!$filter_type || $filter_type == 'song') {
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
     * @return int[]
     */
    public function get_catalogs()
    {
        return array($this->catalog);
    }

    /**
     * Get item's owner.
     * @return int|null
     */
    public function get_user_owner()
    {
        if ($this->user_upload) {
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
     * get_fields
     * This returns all of the 'data' fields for this object, we need to filter out some that we don't
     * want to present to a user, and add some that don't exist directly on the object but are related
     * @return array
     */
    public static function get_fields()
    {
        $fields = get_class_vars('Song');

        unset($fields['id'],$fields['_transcoded'],$fields['_fake'],$fields['cache_hit'],$fields['mime'],$fields['type']);

        // Some additional fields
        $fields['tag'] = true;
        $fields['catalog'] = true;
//FIXME: These are here to keep the ideas, don't want to have to worry about them for now
//        $fields['rating'] = true;
//        $fields['recently Played'] = true;

        return $fields;

    } // get_fields

    /**
     * get_from_path
     * This returns all of the songs that exist under the specified path
     * @param string $path
     * @return int[]
     */
    public static function get_from_path($path)
    {
        $path = Dba::escape($path);

        $sql = "SELECT * FROM `song` WHERE `file` LIKE '$path%'";
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
     * @param int $catalog_id
     * @return string
     */
    public function get_rel_path($file_path=null,$catalog_id=0)
    {
        $info = null;
        if (!$file_path) {
            $info = $this->_get_info();
            $file_path = $info['file'];
        }
        if (!$catalog_id) {
            if (!is_array($info)) {
                $info = $this->_get_info();
            }
            $catalog_id = $info['catalog'];
        }
        $catalog = Catalog::create_from_id( $catalog_id );
        return $catalog->get_rel_path($file_path);

    } // get_rel_path

    /**
     * Generate generic play url.
     * @param string $object_type
     * @param int $object_id
     * @param string $additional_params
     * @param boolean $local
     * @return string
     */
    public static function generic_play_url($object_type, $object_id, $additional_params, $local=false)
    {
        $media = new $object_type($object_id);
        if (!$media->id) return null;

        $uid = $GLOBALS['user']->id ? scrub_out($GLOBALS['user']->id) : '-1';
        $type = $media->type;

        // Checking if the media is gonna be transcoded into another type
        // Some players doesn't allow a type streamed into another without giving the right extension
        $transcode_cfg = AmpConfig::get('transcode');
        $transcode_mode = AmpConfig::get('transcode_' . $type);
        if ($transcode_cfg == 'always' || ($transcode_cfg != 'never' && $transcode_mode == 'required')) {
            $transcode_settings = $media->get_transcode_settings(null);
            if ($transcode_settings) {
                debug_event("media", "Changing play url type from {".$type."} to {".$transcode_settings['format']."} due to encoding settings...", 5);
                $type = $transcode_settings['format'];
            }
        }

        $media_name = $media->get_stream_name() . "." . $type;
        $media_name = str_replace("/", "-", $media_name);
        $media_name = str_replace("?", "", $media_name);
        $media_name = str_replace("#", "", $media_name);
        $media_name = rawurlencode($media_name);

        $url = Stream::get_base_url($local) . "type=" . $object_type . "&oid=" . $object_id . "&uid=" . $uid . $additional_params . "&name=" . $media_name;

        return Stream_URL::format($url);
    }

    /**
     * play_url
     * This function takes all the song information and correctly formats a
     * a stream URL taking into account the downsmapling mojo and everything
     * else, this is the true function
     * @param int $oid
     * @param string $additional_params
     * @param boolean $local
     * @return string
     */
    public static function play_url($oid, $additional_params='', $local=false)
    {
        return self::generic_play_url('song', $oid, $additional_params, $local);
    }

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
     * @param int $user_id
     * @return array
     */
    public static function get_recently_played($user_id=0)
    {
        $user_id = intval($user_id);

        $sql = "SELECT `object_id`, `user`, `object_type`, `date`, `agent` " .
            "FROM `object_count` WHERE `object_type`='song' ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND " . Catalog::get_enable_filter('song', '`object_id`') . " ";
        }
        if ($user_id) {
            // If user is not empty, we're looking directly to user personal info (admin view)
            $sql .= "AND `user`='$user_id' ";
        } else if (!Access::check('interface','100')) {
            // If user identifier is empty, we need to retrieve only users which have allowed view of personnal info
            $personal_info_id = Preference::id_from_name('allow_personal_info_recent');
            if ($personal_info_id) {
                $current_user = $GLOBALS['user']->id;
                $sql .= "AND `user` IN (SELECT `user` FROM `user_preference` WHERE (`preference`='$personal_info_id' AND `value`='1') OR `user`='$current_user') ";
            }
        }
        $sql .= "ORDER BY `date` DESC ";
        $db_results = Dba::read($sql);

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row;
            if (count($results) >= AmpConfig::get('popular_threshold')) { break; }
        }

        return $results;

    } // get_recently_played

    /**
     * Get stream types.
     * @return array
     */
    public function get_stream_types()
    {
        return Song::get_stream_types_for_type($this->type);
    }

    /**
     * Get stream types for media type.
     * @param string $type
     * @return string
     */
    public static function get_stream_types_for_type($type)
    {
        $types = array();
        $transcode = AmpConfig::get('transcode_' . $type);

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
     * @param string $source
     * @param string $target
     * @param string $media_type
     * @param array $options
     * @return array|boolean
     */
    public static function get_transcode_settings_for_media($source, $target = null, $media_type = 'song', $options=array())
    {
        $setting_target = 'encode_target';
        if ($media_type != 'song') {
            $setting_target = 'encode_' . $media_type . '_target';
        }

        if ($target) {
            debug_event('media', 'Explicit format request {' . $target . '}', 5);
        } else if ($target = AmpConfig::get('encode_target_' . $source)) {
            debug_event('media', 'Defaulting to configured target format for ' . $source, 5);
        } else if ($target = AmpConfig::get($setting_target)) {
            debug_event('media', 'Using default target format', 5);
        } else {
            $target = $source;
            debug_event('media', 'No default target for ' . $source . ', choosing to resample', 5);
        }

        debug_event('media', 'Transcode settings: from ' . $source . ' to ' . $target, 5);

        $cmd = AmpConfig::get('transcode_cmd_' . $source) ?: AmpConfig::get('transcode_cmd');
        $args = '';
        if (AmpConfig::get('encode_ss_frame') && isset($options['frame'])) {
            $args .= ' ' . AmpConfig::get('encode_ss_frame');
        }
        if (AmpConfig::get('encode_ss_duration') && isset($options['duration'])) {
            $args .= ' ' . AmpConfig::get('encode_ss_duration');
        }

        $args .= ' ' . AmpConfig::get('transcode_input');

        if (AmpConfig::get('encode_srt') && $options['subtitle']) {
            debug_event('media', 'Using subtitle ' . $options['subtitle'], 5);
            $args .= ' ' . AmpConfig::get('encode_srt');
        }

        $argst = AmpConfig::get('encode_args_' . $target);
        if (!$args) {
            debug_event('media', 'Target format ' . $target . ' is not properly configured', 2);
            return false;
        }
        $args .= ' ' . $argst;

        debug_event('media', 'Command: ' . $cmd . ' Arguments:' . $args, 5);
        return array('format' => $target, 'command' => $cmd . $args);
    }

    /**
     * Get transcode settings.
     * @param string $target
     * @param array $options
     * @return array|boolean
     */
    public function get_transcode_settings($target = null, $options=array())
    {
        return Song::get_transcode_settings_for_media($this->type, $target, 'song', $options);
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
            if ($plugin->load($GLOBALS['user'])) {
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
     * @param int $action_index
     * @param string $codec
     * @return array
     */
    public function run_custom_play_action($action_index, $codec='')
    {
        $transcoder = array();
        $actions = Song::get_custom_play_actions();
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

            debug_event('song', "Running custom play action: " . $run, 3);

            $descriptors = array(1 => array('pipe', 'w'));
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                // Windows doesn't like to provide stderr as a pipe
                $descriptors[2] = array('pipe', 'w');
            }
            $process = proc_open($run, $descriptors, $pipes);

            $transcoder['process'] = $process;
            $transcoder['handle'] = $pipes[1];
            $transcoder['stderr'] = $pipes[2];
            $transcoder['format'] = $codec;
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
        $i = 0;
        while (AmpConfig::get('custom_play_action_title_' . $i)) {
            $actions[] = array(
                'index' => ($i + 1),
                'title' => AmpConfig::get('custom_play_action_title_' . $i),
                'icon' => AmpConfig::get('custom_play_action_icon_' . $i),
                'run' => AmpConfig::get('custom_play_action_run_' . $i),
            );
            ++$i;
        }

        return $actions;
    }

    /*
     * get_metadata
     * Get an array of song metadata
     * @return array
     */
    public function get_metadata()
    {
        $meta = array();

        $meta['file'] = $this->file;
        $meta['bitrate'] = $this->bitrate;
        $meta['rate'] = $this->rate;
        $meta['mode'] = $this->mode;
        $meta['year'] = $this->year;
        $meta['size'] = $this->size;
        $meta['time'] = $this->time;
        $meta['mime'] = $this->mime;
        $meta['title'] = $this->title;
        $meta['album'] = $this->f_album_full;
        $meta['artist'] = $this->f_artist_full;
        $meta['band'] = $meta['albumartist'] = $this->f_album_artist_full;
        $meta['mb_trackid'] = $this->mbid;
        $meta['mb_albumid'] = $this->album_mbid;
        $meta['mb_artistid'] = $this->artist_mbid;
        $meta['mb_albumartistid'] = $this->albumartist_mbid;
        $meta['tracknumber'] = $meta['track'] = $this->track;
        $meta['genre'] = array();
        if ($this->tags) {
            foreach ($this->tags as $tag) {
                if (!in_array($tag['name'], $meta['genre'])) {
                    $meta['genre'][] = $tag['name'];
                }
            }
        }
        $meta['genre'] = implode(',', $meta['genre']);

        return $meta;
    }

} // end of song class
