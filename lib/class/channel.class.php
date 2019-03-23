<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

class Channel extends database_object implements media, library_item
{
    public $id;
    public $is_private;
    public $interface;
    public $port;
    public $start_date;
    public $pid;
    public $listeners;
    public $peak_listeners;
    public $object_type;
    public $object_id;
    public $stream_type;
    public $random;
    public $loop;
    public $bitrate;
    public $name;
    public $description;

    public $header_chunk;
    public $chunk_size              = 4096;
    private $header_chunk_remainder = 0;

    public $tags;
    public $f_tags;

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
        if (!$id) {
            return true;
        }

        /* Get the information from the db */
        $info = $this->get_info($id);

        // Foreach what we've got
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        return true;
    } //constructor

    public function update_start($start_date, $address, $port, $pid)
    {
        $sql = "UPDATE `channel` SET `start_date` = ?, `interface` = ?, `port` = ?, `pid` = ?, `listeners` = '0' WHERE `id` = ?";
        Dba::write($sql, array($start_date, $address, $port, $pid, $this->id));

        $this->start_date = $start_date;
        $this->interface  = $address;
        $this->port       = $port;
        $this->pid        = $pid;
    }

    public function update_listeners($listeners, $addition=false)
    {
        $sql             = "UPDATE `channel` SET `listeners` = ? ";
        $params          = array($listeners);
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
        $tags  = Tag::get_object_tags('channel', $this->id);
        $genre = "";
        if ($tags) {
            foreach ($tags as $tag) {
                $genre .= $tag['name'] . ' ';
            }
            $genre = trim($genre);
        }

        return $genre;
    }

    public function delete()
    {
        $sql = "DELETE FROM `channel` WHERE `id` = ?";

        return Dba::write($sql, array($this->id));
    }

    public static function get_next_port()
    {
        $port       = 8200;
        $sql        = "SELECT MAX(`port`) AS `max_port` FROM `channel`";
        $db_results = Dba::read($sql);

        if ($results = Dba::fetch_assoc($db_results)) {
            if ($results['max_port'] > 0) {
                $port = $results['max_port'] + 1;
            }
        }

        return $port;
    }

    public static function create($name, $description, $url, $object_type, $object_id, $interface, $port, $admin_password, $private, $max_listeners, $random, $loop, $stream_type, $bitrate)
    {
        if (!empty($name)) {
            $sql    = "INSERT INTO `channel` (`name`, `description`, `url`, `object_type`, `object_id`, `interface`, `port`, `fixed_endpoint`, `admin_password`, `is_private`, `max_listeners`, `random`, `loop`, `stream_type`, `bitrate`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = array($name, $description, $url, $object_type, $object_id, $interface, $port, (!empty($interface) && !empty($port)), $admin_password, $private, $max_listeners, $random, $loop, $stream_type, $bitrate);

            return Dba::write($sql, $params);
        }

        return false;
    }

    public function update(array $data)
    {
        if (isset($data['edit_tags'])) {
            Tag::update_tag_list($data['edit_tags'], 'channel', $this->id, true);
        }

        $sql = "UPDATE `channel` SET `name` = ?, `description` = ?, `url` = ?, `interface` = ?, `port` = ?, `fixed_endpoint` = ?, `admin_password` = ?, `is_private` = ?, `max_listeners` = ?, `random` = ?, `loop` = ?, `stream_type` = ?, `bitrate` = ?, `object_id` = ? " .
            "WHERE `id` = ?";
        $params = array($data['name'], $data['description'], $data['url'], $data['interface'], $data['port'], (!empty($data['interface']) && !empty($data['port'])), $data['admin_password'], !empty($data['private']), $data['max_listeners'], $data['random'], $data['loop'], $data['stream_type'], $data['bitrate'], $data['object_id'], $this->id);
        Dba::write($sql, $params);

        return $this->id;
    }

    public static function format_type($type)
    {
        switch ($type) {
            case 'playlist':
                $ftype = $type;
                break;
            default:
                $ftype = '';
                break;
        }

        return $ftype;
    }

    public function show_action_buttons()
    {
        if ($this->id) {
            if ($GLOBALS['user']->has_access('75')) {
                echo Ajax::button('?page=index&action=start_channel&id=' . $this->id, 'run', T_('Start Channel'), 'channel_start_' . $this->id);
                echo " " . Ajax::button('?page=index&action=stop_channel&id=' . $this->id, 'stop', T_('Stop Channel'), 'channel_stop_' . $this->id);
                echo " <a id=\"edit_channel_ " . $this->id . "\" onclick=\"showEditDialog('channel_row', '" . $this->id . "', 'edit_channel_" . $this->id . "', '" . T_('Channel edit') . "', 'channel_row_', 'refresh_channel')\">" . UI::get_icon('edit', T_('Edit')) . "</a>";
                echo " <a href=\"" . AmpConfig::get('web_path') . "/channel.php?action=show_delete&id=" . $this->id . "\">" . UI::get_icon('delete', T_('Delete')) . "</a>";
            }
        }
    }

    public function format($details = true)
    {
        if ($details) {
            $this->tags   = Tag::get_top_tags('channel', $this->id);
            $this->f_tags = Tag::get_display($this->tags, true, 'channel');
        }
    }

    public function get_keywords()
    {
        return array();
    }

    public function get_fullname()
    {
        return $this->name;
    }

    public function get_parent()
    {
        return null;
    }

    public function get_childrens()
    {
        return array();
    }

    public function search_childrens($name)
    {
        return array();
    }

    public function get_medias($filter_type = null)
    {
        $medias = array();
        if (!$filter_type || $filter_type == 'channel') {
            $medias[] = array(
                    'object_type' => 'channel',
                    'object_id' => $this->id
                    );
        }

        return $medias;
    }

    public function get_user_owner()
    {
        return null;
    }

    public function get_default_art_kind()
    {
        return 'default';
    }

    public function get_description()
    {
        return $this->description;
    }

    public function display_art($thumb = 2, $force = false)
    {
        if (Art::has_db($this->id, 'channel') || $force) {
            Art::display('channel', $this->id, $this->get_fullname(), $thumb, $this->link);
        }
    }

    public function get_target_object()
    {
        $object = null;
        if ($this->object_type == 'playlist') {
            $object = new Playlist($this->object_id);
            $object->format();
        }

        return $object;
    }

    public function get_stream_url()
    {
        return "http://" . $this->interface . ":" . $this->port . "/stream." . $this->stream_type;
    }

    public function get_stream_proxy_url()
    {
        return AmpConfig::get('web_path') . '/channel/' . $this->id . '/stream.' . $this->stream_type;
    }

    public static function get_channel_list_sql()
    {
        $sql = "SELECT `id` FROM `channel` ";

        return $sql;
    }

    public static function get_channel_list()
    {
        $sql        = self::get_channel_list_sql();
        $db_results = Dba::read($sql);
        $results    = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    public function start_channel()
    {
        exec("php " . AmpConfig::get('prefix') . '/bin/channel_run.inc -c ' . $this->id . ' > /dev/null &');
    }

    public function stop_channel()
    {
        if ($this->pid) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec("taskkill /F /PID " . $this->pid);
            } else {
                exec("kill -9 " . $this->pid);
            }

            $sql = "UPDATE `channel` SET `start_date` = '0', `listeners` = '0', `pid` = '0' WHERE `id` = ?";
            Dba::write($sql, array($this->id));

            $this->pid = 0;
        }
    }

    public function check_channel()
    {
        $check = false;
        if ($this->interface && $this->port) {
            $connection = @fsockopen($this->interface, $this->port);
            if (is_resource($connection)) {
                $check = true;
                fclose($connection);
            }
        }

        return $check;
    }

    public function get_channel_state()
    {
        if ($this->check_channel()) {
            $state = T_("Running");
        } else {
            $state = T_("Stopped");
        }

        return $state;
    }

    protected function init_channel_songs()
    {
        $this->song_pos = 0;
        $this->songs    = array();
        $this->playlist = $this->get_target_object();
        if ($this->playlist) {
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
                    $randsongs   = $this->playlist->get_random_items(1);
                    $this->media = new Song($randsongs[0]['object_id']);
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

                        if (!$this->media->file || !Core::is_readable(Core::conv_lc_file($this->media->file))) {
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
                    $options = array(
                            'bitrate' => $this->bitrate
                            );
                    $this->transcoder           = Stream::start_transcode($this->media, $this->stream_type, null, $options);
                    $this->media_bytes_streamed = 0;
                }

                if (is_resource($this->transcoder['handle'])) {
                    if (ftell($this->transcoder['handle']) == 0) {
                        $this->header_chunk = '';
                    }
                    $chunk = fread($this->transcoder['handle'], $this->chunk_size);
                    $this->media_bytes_streamed += strlen($chunk);

                    if ((ftell($this->transcoder['handle']) < 10000 && strtolower($this->stream_type) == "ogg") || $this->header_chunk_remainder) {
                        //debug_event('channel', 'File handle pointer: ' . ftell($this->transcoder['handle']) ,'5');
                        $clchunk = $chunk;

                        if ($this->header_chunk_remainder) {
                            $this->header_chunk .= substr($clchunk, 0, $this->header_chunk_remainder);
                            if (strlen($clchunk) >= $this->header_chunk_remainder) {
                                $clchunk                      = substr($clchunk, $this->header_chunk_remainder);
                                $this->header_chunk_remainder = 0;
                            } else {
                                $this->header_chunk_remainder = $this->header_chunk_remainder - strlen($clchunk);
                                $clchunk                      = '';
                            }
                        }
                        // see bin/channel_run.inc for explanation what's happening here
                        while ($this->strtohex(substr($clchunk, 0, 4)) == "4F676753") {
                            $hex                = $this->strtohex(substr($clchunk, 0, 27));
                            $ogg_nr_of_segments = hexdec(substr($hex, 26 * 2, 2));
                            if ((substr($clchunk, 27 + $ogg_nr_of_segments + 1, 6) == "vorbis") || (substr($clchunk, 27 + $ogg_nr_of_segments, 4) == "Opus")) {
                                $hex .= $this->strtohex(substr($clchunk, 27, $ogg_nr_of_segments));
                                $ogg_sum_segm_laces = 0;
                                for ($segm = 0; $segm < $ogg_nr_of_segments; $segm++) {
                                    $ogg_sum_segm_laces += hexdec(substr($hex, 27 * 2 + $segm * 2, 2));
                                }
                                $this->header_chunk .= substr($clchunk, 0, 27 + $ogg_nr_of_segments + $ogg_sum_segm_laces);
                                if (strlen($clchunk) < (27 + $ogg_nr_of_segments + $ogg_sum_segm_laces)) {
                                    $this->header_chunk_remainder = (int) (27 + $ogg_nr_of_segments + $ogg_sum_segm_laces - strlen($clchunk));
                                }
                                $clchunk = substr($clchunk, 27 + $ogg_nr_of_segments + $ogg_sum_segm_laces);
                            } else { //no more interesting headers
                                $clchunk = '';
                            }
                        }
                    }
                    //debug_event('channel', 'File handle pointer: ' . ftell($this->transcoder['handle']) ,'5');
                    //debug_event('channel', 'CHUNK : ' . $chunk, '5');
                    //debug_event('channel', 'Chunk size: ' . strlen($chunk) ,'5');

                    // End of file, prepare to move on for next call
                    if (feof($this->transcoder['handle'])) {
                        $this->media->set_played(-1, 'Ampache', array());
                        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                            fread($this->transcoder['stderr'], 4096);
                            fclose($this->transcoder['stderr']);
                        }
                        fclose($this->transcoder['handle']);
                        Stream::kill_process($this->transcoder);

                        $this->media      = null;
                        $this->transcoder = null;
                    }
                } else {
                    $this->media      = null;
                    $this->transcoder = null;
                }

                if (!strlen($chunk)) {
                    $chunk = $this->get_chunk();
                }
            }
        }

        return $chunk;
    }

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return int[]
     */
    public function get_catalogs()
    {
        return array();
    }

    public static function play_url($oid, $additional_params='', $player=null, $local=false)
    {
        $channel = new Channel($oid);

        return $channel->get_stream_proxy_url() . '?rt=' . time() . '&filename=' . urlencode($channel->name) . '.' . $channel->stream_type . $additional_params;
    }

    public function get_stream_types($player = null)
    {
        // Transcode is mandatory to keep a consistant stream
        return array('transcode');
    }

    public function get_stream_name()
    {
        return $this->get_fullname();
    }

    public function set_played($user, $agent, $location)
    {
        // Do nothing
    }

    public function get_transcode_settings($target = null, $player = null, $options=array())
    {
        return false;
    }

    public static function gc()
    {
    }

    private function strtohex($x)
    {
        $s='';
        foreach (str_split($x) as $c) {
            $s .= sprintf("%02X", ord($c));
        }

        return($s);
    }
} // end of channel class
