<?php
declare(strict_types=0);
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
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

class Song_Preview extends database_object implements media, playable_item
{
    public $id;
    public $file;
    public $artist; // artist.id (Int)
    public $title;
    public $disk;
    public $track;
    public $album_mbid;
    public $artist_mbid;
    public $type;
    public $mime;
    public $mbid; // MusicBrainz ID
    public $enabled = true;

    public $f_file;
    public $f_artist;
    public $f_artist_full;
    public $f_artist_link;
    public $f_title;
    public $f_title_full;
    public $link;
    public $f_link;
    public $f_album_link;
    public $f_album;
    public $f_track;

    /**
     * Constructor
     *
     * Song Preview class
     * @param integer $object_id
     */
    public function __construct($object_id)
    {
        $this->id = (int) ($object_id);

        if ($info = $this->has_info()) {
            foreach ($info as $key => $value) {
                $this->$key = $value;
            }
            if ($this->file) {
                $data       = pathinfo($this->file);
                $this->type = strtolower((string) $data['extension']) ?: 'mp3';
                $this->mime = Song::type_to_mime($this->type);
            }
        } else {
            $this->id = null;

            return false;
        }

        return true;
    } // constructor

    /**
     * insert
     *
     * This inserts the song preview described by the passed array
     * @param array $results
     * @return string|null
     */
    public static function insert($results)
    {
        if ((int) $results['disk'] == 0) {
            $results['disk'] = Album::sanitize_disk($results['disk']);
        }
        if ((int) $results['track'] == 0) {
            $results['disk']  = Album::sanitize_disk($results['track'][0]);
            $results['track'] = substr($results['track'], 1);
        }
        $sql = 'INSERT INTO `song_preview` (`file`, `album_mbid`, `artist`, `artist_mbid`, `title`, `disk`, `track`, `mbid`, `session`) ' .
            ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $db_results = Dba::write($sql, array(
            $results['file'],
            $results['album_mbid'],
            $results['artist'],
            $results['artist_mbid'],
            $results['title'],
            $results['disk'],
            $results['track'],
            $results['mbid'],
            $results['session'],
        ));

        if (!$db_results) {
            debug_event(self::class, 'Unable to insert ' . $results['disk'] . '-' . $results['track'] . '-' . $results['title'], 2);

            return null;
        }

        return Dba::insert_id();
    }

    /**
     * build_cache
     *
     * This attempts to reduce queries by asking for everything in the
     * browse all at once and storing it in the cache, this can help if the
     * db connection is the slow point.
     * @param array $song_ids
     * @return boolean
     */
    public static function build_cache($song_ids)
    {
        if (empty($song_ids)) {
            return false;
        }
        $idlist = '(' . implode(',', $song_ids) . ')';
        if ($idlist == '()') {
            return false;
        }

        // Song data cache
        $sql = 'SELECT `id`, `file`, `album_mbid`, `artist`, `artist_mbid`, `title`, `disk`, `track`, `mbid` ' .
            'FROM `song_preview` ' .
            "WHERE `id` IN $idlist";
        $db_results = Dba::read($sql);

        $artists = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('song_preview', $row['id'], $row);
            if ($row['artist']) {
                $artists[$row['artist']] = $row['artist'];
            }
        }

        Artist::build_cache($artists);

        return true;
    } // build_cache

    /**
     * has_info
     * @return array
     */
    private function has_info()
    {
        $preview_id = $this->id;

        if (parent::is_cached('song_preview', $preview_id)) {
            return parent::get_from_cache('song_preview', $preview_id);
        }

        $sql = 'SELECT `id`, `file`, `album_mbid`, `artist`, `artist_mbid`, `title`, `disk`, `track`, `mbid` ' .
            'FROM `song_preview` WHERE `id` = ?';
        $db_results = Dba::read($sql, array($preview_id));

        $results = Dba::fetch_assoc($db_results);
        if (!empty($results['id'])) {
            if (empty($results['artist_mbid'])) {
                $sql        = 'SELECT `mbid` FROM `artist` WHERE `id` = ?';
                $db_results = Dba::read($sql, array($results['artist']));
                if ($artist_res = Dba::fetch_assoc($db_results)) {
                    $results['artist_mbid'] = $artist_res['mbid'];
                }
            }
            parent::add_to_cache('song_preview', $preview_id, $results);

            return $results;
        }

        return array();
    }

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
    } // get_album_name

    /**
     * format
     * This takes the current song object
     * and does a ton of formatting on it creating f_??? variables on the current
     * object
     * @param boolean $details
     * @return boolean
     */
    public function format($details = true)
    {
        unset($details); // dead code but called from other format calls
        // Format the artist name
        if ($this->artist) {
            $this->f_artist_full = $this->get_artist_name();
            $this->f_artist_link = "<a href=\"" . AmpConfig::get('web_path') . "/artists.php?action=show&amp;artist=" . $this->artist . "\" title=\"" . scrub_out($this->f_artist_full) . "\"> " . scrub_out($this->f_artist_full) . "</a>";
        } else {
            $wartist             = Wanted::get_missing_artist($this->artist_mbid);
            $this->f_artist_link = $wartist['link'];
            $this->f_artist_full = $wartist['name'];
        }
        $this->f_artist = $this->f_artist_full;

        // Format the title
        $this->f_title_full = $this->title;
        $this->f_title      = $this->title;

        $this->link         = "#";
        $this->f_link       = "<a href=\"" . scrub_out($this->link) . "\" title=\"" . scrub_out($this->f_artist) . " - " . scrub_out($this->title) . "\"> " . scrub_out($this->f_title) . "</a>";
        $this->f_album_link = "<a href=\"" . AmpConfig::get('web_path') . "/albums.php?action=show_missing&amp;mbid=" . $this->album_mbid . "&amp;artist=" . $this->artist . "\" title=\"" . $this->f_album . "\">" . $this->f_album . "</a>";

        // Format the track (there isn't really anything to do here)
        $this->f_track = $this->track;

        return true;
    } // format

    /**
     * @return string
     */
    public function get_fullname()
    {
        return $this->f_title;
    }

    /**
     * @return null
     */
    public function get_parent()
    {
        // Wanted album is not part of the library, cannot return it.
        return null;
    }

    /**
     * @return array
     */
    public function get_childrens()
    {
        return array();
    }

    /**
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        debug_event(self::class, 'search_childrens ' . $name, 5);

        return array();
    }

    /**
     * @param string $filter_type
     * @return array|mixed
     */
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if ($filter_type === null || $filter_type == 'song_preview') {
            $medias[] = array(
                'object_type' => 'song_preview',
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
        return array();
    }

    /**
     * play_url
     * This function takes all the song information and correctly formats a
     * a stream URL taking into account the downsampling mojo and everything
     * else, this is the true function
     * @param string $additional_params
     * @param string $player
     * @param boolean $local
     * @return string
     */
    public function play_url($additional_params = '', $player = '', $local = false)
    {
        $user_id   = Core::get_global('user')->id ? scrub_out(Core::get_global('user')->id) : '-1';
        $type      = $this->type;
        $song_name = rawurlencode($this->get_artist_name() . " - " . $this->title . "." . $type);
        $url       = Stream::get_base_url($local) . "type=song_preview&oid=" . $this->id . "&uid=" . $user_id . "&name=" . $song_name;

        return Stream_URL::format($url . $additional_params);
    } // play_url

    /**
     * stream
     */
    public function stream()
    {
        foreach (Plugin::get_plugins('stream_song_preview') as $plugin_name) {
            $plugin = new Plugin($plugin_name);
            if ($plugin->load(Core::get_global('user'))) {
                if ($plugin->_plugin->stream_song_preview($this->file)) {
                    break;
                }
            }
        }
    }

    /**
     * get_stream_types
     *
     * @param $player
     * @return string[]
     */
    public function get_stream_types($player = null)
    {
        return array('native');
    }

    /**
     * get_transcode_settings
     *
     * FIXME: Song Preview transcoding is not implemented
     * @param string $target
     * @param string $player
     * @param array $options
     * @return boolean
     */
    public function get_transcode_settings($target = null, $player = null, $options = array())
    {
        return false;
    }

    /**
     * @return mixed
     */
    public function get_stream_name()
    {
        return $this->title;
    }

    /**
     * @param integer $user
     * @param string $agent
     * @param array $location
     * @param integer $date
     * @return boolean
     */
    public function set_played($user, $agent, $location, $date = null)
    {
        // Do nothing
        unset($user, $agent, $location, $date);

        return false;
    }

    /**
     * @param integer $user
     * @param string $agent
     * @param integer $date
     * @return boolean
     */
    public function check_play_history($user, $agent, $date)
    {
        // Do nothing
        unset($user, $agent, $date);

        return false;
    }

    /**
     * @param string $album_mbid
     * @return array
     */
    public static function get_song_previews($album_mbid)
    {
        $songs = array();

        $sql = "SELECT `id` FROM `song_preview` " .
            "WHERE `session` = ? AND `album_mbid` = ?";
        $db_results = Dba::read($sql, array(session_id(), $album_mbid));

        while ($results = Dba::fetch_assoc($db_results)) {
            $songs[] = new Song_Preview($results['id']);
        }

        return $songs;
    }

    /**
     * @return PDOStatement|boolean
     */
    public static function garbage_collection()
    {
        $sql = 'DELETE FROM `song_preview` USING `song_preview` ' .
            'LEFT JOIN `session` ON `session`.`id`=`song_preview`.`session` ' .
            'WHERE `session`.`id` IS NULL';

        return Dba::write($sql);
    }
} // end song_preview.class
