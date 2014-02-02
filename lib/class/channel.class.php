<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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

class Channel extends database_object
{
    private $is_init;
    private $playlist;
    private $song_pos;
    private $songs;
    public $media;
    private $media_bytes_streamed;
    private $transcoder;

    /**
     * Constructor
     */
    public function __construct($id=0)
    {
        if (!$id) { return true; }

        /* Get the information from the db */
        $info = $this->get_info($id);

        // Foreach what we've got
        foreach ($info as $key=>$value) {
            $this->$key = $value;
        }

        return true;
    } //constructor

    public function update_start($start_date, $address, $port)
    {
        $sql = "UPDATE `channel` SET `start_date` = ?, `interface` = ?, `port` = ?, `listeners` = '0' WHERE `id` = ?";
        Dba::write($sql, array($start_date, $address, $port, $this->id));
    }

    public function update_listeners($listeners, $addition=false)
    {
        $sql = "UPDATE `channel` SET `listeners` = ? ";
        $params = array($listeners);
        $this->listeners = $listeners;
        if ($listeners > $this->peak_listeners) {
            $this->peak_listeners = $listeners;
            $sql .= ", `peak_listeners` = ? ";
            $params[] = $listeners;
        }
        if ($addition) {
            $sql .= ", `connections`=`connections`+1 ";
        }
        $sql .= "WHERE `id` = ?";
        $params[] = $this->id;
        Dba::write($sql, $params);
    }

    public function get_genre()
    {
        $tags = Tag::get_object_tags('channel', $this->id);
        $genre = "";
        foreach ($tags as $tag) {
            $genre .= $tag['name'] . ' ';
        }
        $genre = trim($genre);

        return $genre;
    }

    public function delete()
    {
        $sql = "DELETE FROM `channel` WHERE `id` = ?";
        Dba::write($sql, array($this->id));
    }

    public static function create($name, $description, $url, $object_type, $object_id, $interface, $port, $admin_password, $private, $max_listeners, $random, $loop, $stream_type, $bitrate)
    {
        $sql = "INSERT INTO `channel` (`name`, `description`, `url`, `object_type`, `object_id`, `interface`, `port`, `fixed_endpoint`, `admin_password`, `is_private`, `max_listeners`, `random`, `loop`, `stream_type`, `bitrate`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $accept = $GLOBALS['user']->has_access('75') ? true : AmpConfig::get('wanted_auto_accept');
        $params = array($name, $description, $url, $object_type, $object_id, $interface, $port, (!empty($interface) && !empty($port)), $admin_password, $private, $max_listeners, $random, $loop, $stream_type, $bitrate);
        Dba::write($sql, $params);
    }

    public function show_action_buttons()
    {
        if ($this->id) {
            if ($GLOBALS['user']->has_access('75')) {
                echo " " . Ajax::button('?page=index&action=remove_channel&id=' . $this->id,'remove', T_('Remove'),'channel_remove_' . $this->id);
            }
        }
    }

    public function format()
    {

    }

    public static function get_channel_list_sql()
    {
        $sql = "SELECT `id` FROM `channel` ";

        return $sql;
    }

    public static function get_channel_list()
    {
        $sql = self::get_channel_list_sql();
        $db_results = Dba::read($sql);
        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    protected function init_channel_songs()
    {
        $this->song_pos = 0;
        $this->songs = array();
        if ($this->object_type == 'playlist') {
            $this->playlist = new Playlist($this->object_id);
            if (!$this->random) {
                $this->songs = $this->playlist->get_songs();
            }
        }
        $this->is_init = true;
    }

    public function get_chunk()
    {
        $chunk = null;

        if (!$this->is_init) {
            $this->init_channel_songs();
        }

        if ($this->is_init) {
            // Move to next song
            while ($this->media == null && ($this->random || $this->song_pos < count($this->songs))) {
                if ($this->random) {
                    $randsongs = $playlist->get_random_items(1);
                    $this->media = new Song($randsongs[0]);
                } else {
                    $this->media = new Song($this->songs[$this->song_pos]);
                }
                $this->media->format();

                if ($this->media->catalog) {
                    $catalog = Catalog::create_from_id($this->media->catalog);
                    if (make_bool($this->media->enabled)) {
                        if (AmpConfig::get('lock_songs')) {
                            if (!Stream::check_lock_media($this->media->id, 'song')) {
                                debug_event('channel', 'Media ' . $this->media->id . ' locked, skipped.', '3');
                                $this->media = null;
                            }
                        }
                    }

                    if ($this->media != null) {
                        $this->media = $catalog->prepare_media($this->media);

                        if (!$this->media->file || !Core::is_readable($this->media->file)) {
                            debug_event('channel', 'Cannot read media ' . $this->media->id . ' file, skipped.', '3');
                            $this->media = null;
                        } else {
                            $valid_types = $this->media->get_stream_types();
                            if (!in_array('transcode', $valid_types)) {
                                debug_event('channel', 'Missing settings to transcode ' . $this->media->file . ', skipped.', '3');
                                $this->media = null;
                            } else {
                                debug_event('channel', 'Now listening to ' . $this->media->file . '.', '5');
                            }
                        }
                    }
                } else {
                    debug_event('channel', 'Media ' . $this->media->id . ' doesn\'t have catalog, skipped.', '3');
                    $this->media = null;
                }

                $this->song_pos++;
                // Restart from beginning for next song if the channel is 'loop' enabled
                // and load fresh data from database
                if ($this->media != null && $this->song_pos == count($this->songs) && $this->loop) {
                    $this->init_channel_songs();
                }
            }

            if ($this->media != null) {
                // Stream not yet initialized for this media, start it
                if (!$this->transcoder) {
                    $this->transcoder = Stream::start_transcode($this->media, $this->stream_type, $this->bitrate);
                    $this->media_bytes_streamed = 0;
                }

                if (is_resource($this->transcoder['handle'])) {

                    $chunk = fread($this->transcoder['handle'], 4096);
                    $this->media_bytes_streamed += strlen($chunk);

                    // End of file, prepare to move on for next call
                    if (feof($this->transcoder['handle'])) {
                        $this->media->set_played();
                        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                            $stderr = fread($this->transcoder['stderr'], 4096);
                            fclose($this->transcoder['stderr']);
                        }
                        fclose($this->transcoder['handle']);
                        proc_close($this->transcoder['process']);

                        $this->media = null;
                        $this->transcoder = null;
                    }
                } else {
                    $this->media = null;
                    $this->transcoder = null;
                }

                if (!strlen($chunk)) {
                    $chunk = $this->get_chunk();
                }
            }
        }

        return $chunk;
    }

} // end of channel class
