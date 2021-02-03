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

class Stream
{
    private static $session;

    private function __construct()
    {
        // Static class, do nothing.
    }

    /**
     * set_session
     *
     * This overrides the normal session value, without adding
     * an additional session into the database, should be called
     * with care
     * @param integer|string $sid
     */
    public static function set_session($sid)
    {
        self::$session=$sid;
    } // set_session

    /**
     * get_session
     * @return string
     */
    public static function get_session()
    {
        if (!self::$session) {
            // Generate the session ID.  This is slightly wasteful.
            $data         = array();
            $data['type'] = 'stream';
            // This shouldn't be done here but at backend endpoint side
            if (Core::get_request('client') !== '') {
                $data['agent'] = Core::get_request('client');
            }

            // Copy session geolocation
            // Same thing, should be done elsewhere
            $sid = session_id();
            if ($sid) {
                $location = Session::get_geolocation($sid);
                if (isset($location['latitude'])) {
                    $data['geo_latitude'] = $location['latitude'];
                }
                if (isset($location['longitude'])) {
                    $data['geo_longitude'] = $location['longitude'];
                }
                if (isset($location['name'])) {
                    $data['geo_name'] = $location['name'];
                }
            }

            self::$session = Session::create($data);
        }

        return self::$session;
    }

    /**
     * get_allowed_bitrate
     * @return integer
     */
    public static function get_allowed_bitrate()
    {
        $max_bitrate = AmpConfig::get('max_bit_rate');
        $min_bitrate = AmpConfig::get('min_bit_rate');
        // FIXME: This should be configurable for each output type
        $user_bit_rate = AmpConfig::get('transcode_bitrate', '128');

        // If the user's crazy, that's no skin off our back
        if ($user_bit_rate < $min_bitrate) {
            $min_bitrate = $user_bit_rate;
        }

        // Are there site-wide constraints? (Dynamic downsampling.)
        if ($max_bitrate > 1) {
            $sql = 'SELECT COUNT(*) FROM `now_playing` ' .
                'WHERE `user` IN ' .
                '(SELECT DISTINCT `user_preference`.`user` ' .
                'FROM `preference` JOIN `user_preference` ' .
                'ON `preference`.`id` = ' .
                '`user_preference`.`preference` ' .
                "WHERE `preference`.`name` = 'play_type' " .
                "AND `user_preference`.`value` = 'downsample')";

            $db_results = Dba::read($sql);
            $results    = Dba::fetch_row($db_results);

            $active_streams = (int) ($results[0]) ?: 0;
            debug_event(self::class, 'Active transcoding streams: ' . $active_streams, 5);

            // We count as one for the algorithm
            // FIXME: Should this reflect the actual bit rates?
            $active_streams++;
            $bit_rate = floor($max_bitrate / $active_streams);

            // Exit if this would be insane
            if ($bit_rate < ($min_bitrate ?: 8)) {
                debug_event(self::class, 'Max transcode bandwidth already allocated. Active streams: ' . $active_streams, 2);
                header('HTTP/1.1 503 Service Temporarily Unavailable');

                return 0;
            }

            // Never go over the user's sample rate
            if ($bit_rate > $user_bit_rate) {
                $bit_rate = $user_bit_rate;
            }
        } // end if we've got bitrates
        else {
            $bit_rate = $user_bit_rate;
        }

        return (int) $bit_rate;
    }

    /**
     * start_transcode
     *
     * This is a rather complex function that starts the transcoding or
     * resampling of a media and returns the opened file handle.
     * @param $media
     * @param string $type
     * @param string $player
     * @param array $options
     * @return array|false
     */
    public static function start_transcode($media, $type = null, $player = null, $options = array())
    {
        $transcode_settings = $media->get_transcode_settings($type, $player, $options);
        // Bail out early if we're unutterably broken
        if ($transcode_settings === false) {
            debug_event(self::class, 'Transcode requested, but get_transcode_settings failed', 2);

            return false;
        }

        // don't ignore user bitrates
        $bit_rate = (int) self::get_allowed_bitrate();
        if (!$options['bitrate']) {
            debug_event(self::class, 'Configured bitrate is ' . $bit_rate, 5);
            // Validate the bitrate
            $bit_rate = self::validate_bitrate($bit_rate);
        } elseif ($bit_rate > (int) $options['bitrate'] || $bit_rate = 0) {
            // use the file bitrate if lower than the gathered
            $bit_rate = $options['bitrate'];
        }

        // Never upsample a media
        if ($media->type == $transcode_settings['format'] && ($bit_rate * 1000) > $media->bitrate && $media->bitrate > 0) {
            debug_event(self::class, 'Clamping bitrate to avoid upsampling to ' . $bit_rate, 5);
            $bit_rate = self::validate_bitrate($media->bitrate / 1000);
        }

        debug_event(self::class, 'Final transcode bitrate is ' . $bit_rate, 4);

        $song_file = scrub_arg($media->file);

        // Finalise the command line
        $command = $transcode_settings['command'];

        $string_map = array(
            '%FILE%' => $song_file,
            '%SAMPLE%' => $bit_rate, // Deprecated
            '%BITRATE%' => $bit_rate
        );
        if (isset($options['maxbitrate'])) {
            $string_map['%MAXBITRATE%'] = $options['maxbitrate'];
        } else {
            $string_map['%MAXBITRATE%'] = 8000;
        }
        if (isset($options['frame'])) {
            $frame                = gmdate("H:i:s", $options['frame']);
            $string_map['%TIME%'] = $frame;
        }
        if (isset($options['duration'])) {
            $duration                 = gmdate("H:i:s", $options['duration']);
            $string_map['%DURATION%'] = $duration;
        }
        if (isset($options['resolution'])) {
            $string_map['%RESOLUTION%'] = $options['resolution'];
        } else {
            $string_map['%RESOLUTION%'] = ($media->f_resolution) ?: '1280x720';
        }
        if (isset($options['quality'])) {
            $string_map['%QUALITY%'] = (31 * (101 - $options['quality'])) / 100;
        } else {
            $string_map['%QUALITY%'] = 10;
        }
        if (!empty($options['subtitle'])) {
            // This is too specific to ffmpeg/avconv
            $string_map['%SRTFILE%'] = str_replace(':', '\:', addslashes($options['subtitle']));
        }

        foreach ($string_map as $search => $replace) {
            $command = str_replace($search, $replace, $command, $ret);
            if ($ret === null) {
                debug_event(self::class, "$search not in transcode command", 5);
            }
        }

        return self::start_process($command, array('format' => $transcode_settings['format']));
    }

    /**
     * get_image_preview
     * @param Video $media
     * @return string
     */
    public static function get_image_preview($media)
    {
        $image = null;
        $sec   = ($media->time >= 30) ? 30 : (int) ($media->time / 2);
        $frame = gmdate("H:i:s", $sec);

        if (AmpConfig::get('transcode_cmd') && AmpConfig::get('transcode_input') && AmpConfig::get('encode_get_image')) {
            $command    = AmpConfig::get('transcode_cmd') . ' ' . AmpConfig::get('transcode_input') . ' ' . AmpConfig::get('encode_get_image');
            $string_map = array(
                '%FILE%' => scrub_arg($media->file),
                '%TIME%' => $frame
            );
            foreach ($string_map as $search => $replace) {
                $command = str_replace($search, $replace, $command, $ret);
                if ($ret === null) {
                    debug_event(self::class, "$search not in transcode command", 5);
                }
            }
            $proc = self::start_process($command);

            if (is_resource($proc['handle'])) {
                $image = '';
                do {
                    $image .= fread($proc['handle'], 1024);
                } while (!feof($proc['handle']));
                fclose($proc['handle']);
            }
        } else {
            debug_event(self::class, 'Missing transcode_cmd / encode_get_image parameters to generate media preview.', 3);
        }

        return $image;
    }

    /**
     * start_process
     * @param $command
     * @param array $settings
     * @return array
     */
    private static function start_process($command, $settings = array())
    {
        debug_event(self::class, "Transcode command: " . $command, 3);

        $descriptors = array(1 => array('pipe', 'w'));
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            // Windows doesn't like to provide stderr as a pipe
            $descriptors[2] = array('pipe', 'w');
            $cmdPrefix      = "exec ";
        } else {
            $cmdPrefix = "start /B ";
        }

        debug_event(self::class, "Transcode command prefix: " . $cmdPrefix, 3);

        $process = proc_open($cmdPrefix . $command, $descriptors, $pipes);
        if ($process === false) {
            debug_event(self::class, 'Transcode command failed to open.', 1);
            $parray = array(
                'handle' => null
            );
        } else {
            $parray  = array(
                'process' => $process,
                'handle' => $pipes[1],
                'stderr' => $pipes[2]
            );

            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                stream_set_blocking($pipes[2], false); // Be sure stderr is non-blocking
            }
        }

        return array_merge($parray, $settings);
    }

    /**
     * kill_process
     * @param $transcoder
     */
    public static function kill_process($transcoder)
    {
        $status = proc_get_status($transcoder['process']);
        if ($status['running'] == true) {
            $pid = $status['pid'];
            debug_event(self::class, 'Stream process about to be killed. pid:' . $pid, 1);

            (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') ? exec("kill -9 $pid") : exec("taskkill /F /T /PID $pid");

            proc_close($transcoder['process']);
        } else {
            debug_event(self::class, 'Process is not running, kill skipped.', 5);
        }
    }

    /**
     * validate_bitrate
     * this function takes a bitrate and returns a valid one
     * @param $bitrate
     * @return integer
     */
    public static function validate_bitrate($bitrate)
    {
        /* Round to standard bitrates */
        return (int) (16 * (floor((int) $bitrate / 16)));
    }

    /**
     * garbage_collection
     *
     * This will garbage collect the Now Playing data,
     * this is done on every play start.
     */
    public static function garbage_collection()
    {
        // Remove any Now Playing entries for sessions that have been GC'd
        $sql = "DELETE FROM `now_playing` USING `now_playing` " .
            "LEFT JOIN `session` ON `session`.`id` = `now_playing`.`id` " .
            "WHERE (`session`.`id` IS NULL AND `now_playing`.`id` NOT IN (SELECT `username` FROM `user`)) OR `now_playing`.`expire` < '" . time() . "'";
        Dba::write($sql);
    }

    /**
     * insert_now_playing
     *
     * This will insert the Now Playing data.
     * @param integer $object_id
     * @param integer $uid
     * @param integer $length
     * @param string $sid
     * @param string $type
     */
    public static function insert_now_playing($object_id, $uid, $length, $sid, $type)
    {
        // Ensure that this client only has a single row
        $sql = 'REPLACE INTO `now_playing` ' .
            '(`id`, `object_id`, `object_type`, `user`, `expire`, `insertion`) ' .
            'VALUES (?, ?, ?, ?, ?, ?)';
        Dba::write($sql, array($sid, $object_id, strtolower((string) $type), $uid, (int) (time() + (int) $length), time()));
    }

    /**
     * clear_now_playing
     *
     * There really isn't anywhere else for this function, shouldn't have
     * deleted it in the first place.
     * @return boolean
     */
    public static function clear_now_playing()
    {
        $sql = 'TRUNCATE `now_playing`';
        Dba::write($sql);

        return true;
    }

    /**
     * get_now_playing
     *
     * This returns the Now Playing information
     * @return array
     */
    public static function get_now_playing()
    {
        $sql = 'SELECT `session`.`agent`, `np`.* FROM `now_playing` AS `np` ';
        $sql .= 'LEFT JOIN `session` ON `session`.`id` = `np`.`id` ';

        if (AmpConfig::get('now_playing_per_user')) {
            $sql .= 'INNER JOIN ( ' .
                'SELECT MAX(`insertion`) AS `max_insertion`, `user` ' .
                'FROM `now_playing` ' .
                'GROUP BY `user`' .
                ') `np2` ' .
                'ON `np`.`user` = `np2`.`user` ' .
                'AND `np`.`insertion` = `np2`.`max_insertion` ';
        }
        $sql .= "WHERE `np`.`object_type` IN ('song', 'video')";

        if (!Access::check('interface', 100)) {
            // We need to check only for users which have allowed view of personnal info
            $personal_info_id = Preference::id_from_name('allow_personal_info_now');
            if ($personal_info_id) {
                $current_user = Core::get_global('user')->id;
                $sql .= " AND (`np`.`user` IN (SELECT `user` FROM `user_preference` WHERE ((`preference`='$personal_info_id' AND `value`='1') OR `user`='$current_user'))) ";
            }
        }

        $sql .= 'ORDER BY `np`.`expire` DESC';
        $db_results = Dba::read($sql);

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $type  = $row['object_type'];
            $media = new $type($row['object_id']);
            $media->format();
            $client = new User($row['user']);
            $client->format();
            $results[] = array(
                'media' => $media,
                'client' => $client,
                'agent' => $row['agent'],
                'expire' => $row['expire']
            );
        } // end while

        return $results;
    } // get_now_playing

    /**
     * check_lock_media
     *
     * This checks to see if the media is already being played.
     * @param integer $media_id
     * @param string $type
     * @return boolean
     */
    public static function check_lock_media($media_id, $type)
    {
        $sql = 'SELECT `object_id` FROM `now_playing` WHERE ' .
            '`object_id` = ? AND `object_type` = ?';
        $db_results = Dba::read($sql, array($media_id, $type));

        if (Dba::num_rows($db_results)) {
            debug_event(self::class, 'Unable to play media currently locked by another user', 3);

            return false;
        }

        return true;
    }

    /**
     * run_playlist_method
     *
     * This takes care of the different types of 'playlist methods'. The
     * reason this is here is because it deals with streaming rather than
     * playlist mojo. If something needs to happen this will echo the
     * javascript required to cause a reload of the iframe.
     * @return boolean
     */
    public static function run_playlist_method()
    {
        // If this wasn't ajax included run away
        if (!defined('AJAX_INCLUDE')) {
            return false;
        }

        switch (AmpConfig::get('playlist_method')) {
            case 'send':
                $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=basket';
                break;
            case 'send_clear':
                $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=basket&playlist_method=clear';
                break;
            case 'clear':
            case 'default':
            default:
                return true;
        } // end switch on method

        // Load our javascript
        echo "<script>";
        echo Core::get_reloadutil() . "('" . $_SESSION['iframe']['target'] . "');";
        echo "</script>";

        return true;
    } // run_playlist_method

    /**
     * get_base_url
     * This returns the base requirements for a stream URL this does not include anything after the index.php?sid=????
     * @param boolean $local
     * @return string
     */
    public static function get_base_url($local = false)
    {
        $session_string = '';
        if (AmpConfig::get('use_auth') && AmpConfig::get('require_session')) {
            $session_string = 'ssid=' . self::get_session() . '&';
        }

        if ($local) {
            $web_path = AmpConfig::get('local_web_path');
        } else {
            $web_path = AmpConfig::get('web_path');
        }

        if (AmpConfig::get('force_http_play')) {
            $web_path = str_replace("https://", "http://", $web_path);
        }

        $http_port = AmpConfig::get('http_port');
        if (!empty($http_port) && $http_port != 80 && $http_port != 443) {
            if (preg_match("/:(\d+)/", $web_path, $matches)) {
                $web_path = str_replace(':' . $matches['1'], ':' . $http_port, $web_path);
            } else {
                $web_path = str_replace(AmpConfig::get('http_host'), AmpConfig::get('http_host') . ':' . $http_port, $web_path);
            }
        }

        return $web_path . "/play/index.php?$session_string";
    } // get_base_url
} // end stream.class
