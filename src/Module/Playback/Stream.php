<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Playback;

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Preference;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\User;

class Stream
{
    private static $session;

    /**
     * set_session
     *
     * This overrides the normal session value, without adding another session into the database, should be called with care
     * @param int|string $sid
     */
    public static function set_session($sid): void
    {
        if (!empty($sid)) {
            self::$session = $sid;
        }
    }

    /**
     * get_session
     */
    public static function get_session(): string
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
     * Get transcode format for media based on config settings
     *
     * @param string $source
     * @param string $target
     * @param string $player
     * @param string $media_type
     * @return string
     */
    public static function get_transcode_format(
        $source,
        $target = null,
        $player = null,
        $media_type = 'song'
    ): ?string {
        // check if we've done this before
        $format = self::get_output_cache($source, $target, $player, $media_type);
        if (!empty($format)) {
            return $format;
        }
        $input_target = $target;
        // default target for songs
        $setting_target = 'encode_target';
        // default target for video
        if ($media_type != 'song') {
            $setting_target = 'encode_' . $media_type . '_target';
        }
        if (!$player && in_array($media_type, array('song', 'podcast_episode'))) {
            $player = 'webplayer';
        }
        // webplayer / api transcode actions
        $has_player_target = false;
        if ($player) {
            // encode target for songs in webplayer/api
            $encode_target = 'encode_player_' . $player . '_target';
            if ($media_type != 'song') {
                // encode target for video in webplayer/api
                $encode_target = 'encode_' . $media_type . '_player_' . $player . '_target';
            }
            $has_player_target = AmpConfig::get($encode_target);
        }
        $has_default_target = AmpConfig::get($setting_target);
        $has_codec_target   = AmpConfig::get('encode_target_' . $source);

        // Fall backwards from the specific transcode formats to default
        // TARGET > PLAYER > CODEC > DEFAULT
        if ($target) {
            return $target;
        } elseif ($has_player_target && $source !== $has_player_target) {
            $target = $has_player_target;
            debug_event(self::class, 'Transcoding for ' . $player . ': {' . $target . '} format for: ' . $source, 5);
        } elseif ($has_codec_target && $source !== $has_codec_target) {
            $target = $has_codec_target;
            debug_event(self::class, 'Transcoding for codec: {' . $target . '} format for: ' . $source, 5);
        } elseif ($has_default_target && $source !== $has_default_target) {
            $target = $has_default_target;
            debug_event(self::class, 'Transcoding to default: {' . $target . '} format for: ' . $source, 5);
        }
        // fall back to resampling if no default
        if (!$target) {
            $target = $source;
        }
        self::set_output_cache($target, $source, $input_target, $player, $media_type);

        return $target;
    }

    /**
     * get_allowed_bitrate
     */
    public static function get_allowed_bitrate(): int
    {
        $max_bitrate = AmpConfig::get('max_bit_rate');
        $min_bitrate = AmpConfig::get('min_bit_rate', 8);
        // FIXME: This should be configurable for each output type
        $user_bit_rate = (int)AmpConfig::get('transcode_bitrate', 128);

        // If the user's crazy, that's no skin off our back
        if ($user_bit_rate < $min_bitrate) {
            $min_bitrate = $user_bit_rate;
        }

        // Are there site-wide constraints? (Dynamic downsampling.)
        if ($max_bitrate > 1) {
            $sql        = "SELECT COUNT(*) FROM `now_playing` WHERE `user` IN (SELECT DISTINCT `user_preference`.`user` FROM `preference` JOIN `user_preference` ON `preference`.`id` = `user_preference`.`preference` WHERE `preference`.`name` = 'play_type' AND `user_preference`.`value` = 'downsample')";
            $db_results = Dba::read($sql);
            $row        = Dba::fetch_row($db_results);

            $active_streams = (int) ($row[0] ?? 0);
            debug_event(self::class, 'Active transcoding streams: ' . $active_streams, 5);

            // We count as one for the algorithm
            // FIXME: Should this reflect the actual bit rates?
            $active_streams++;
            $bit_rate = floor($max_bitrate / $active_streams);

            // Exit if this would be insane
            if ($bit_rate < ($min_bitrate ?? 8)) {
                debug_event(self::class, 'Max transcode bandwidth already allocated. Active streams: ' . $active_streams, 2);
                header('HTTP/1.1 503 Service Temporarily Unavailable');

                return 0;
            }

            // Never go over the user's sample rate
            if ($bit_rate > $user_bit_rate) {
                $bit_rate = $user_bit_rate;
            }
        } else {
            $bit_rate = $user_bit_rate;
        }

        return (int)$bit_rate;
    }

    /**
     * Get stream types for media type.
     * @return list<string>
     */
    public static function get_stream_types_for_type(string $type, ?string $player = 'webplayer'): array
    {
        $types     = array();
        $transcode = AmpConfig::get('transcode_' . $type);
        if ($player !== '') {
            $player_transcode = AmpConfig::get('transcode_player_' . $player . '_' . $type);
            $player_encode    = AmpConfig::get('encode_player_' . $player . '_target');
            if ($player_transcode) {
                // Override the default TYPE transcoding behavior on a per-player basis
                // (e.g. transcode_player_webplayer_flac = "required")
                $transcode = $player_transcode;
            } elseif ($player_encode) {
                // Override the default PLAYER output format.
                // (e.g. encode_player_webplayer_target = "ogg")
                $transcode = $player_encode;
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
     * It can be confusing but when waveforms are enabled it will transcode the file twice.
     *
     * @param string $source
     * @param string $target
     * @param string $player
     * @param string $media_type
     * @param array $options
     * @return array
     */
    public static function get_transcode_settings_for_media(
        $source,
        $target = null,
        $player = null,
        $media_type = 'song',
        $options = array()
    ): array {
        $target = self::get_transcode_format($source, $target, $player, $media_type);
        $cmd    = AmpConfig::get('transcode_cmd_' . $source) ?? AmpConfig::get('transcode_cmd');
        if (empty($cmd)) {
            debug_event(self::class, 'A valid transcode_cmd is required to transcode', 5);

            return array();
        }

        $args = '';
        if (AmpConfig::get('encode_ss_frame') && array_key_exists('frame', $options)) {
            $args .= ' ' . AmpConfig::get('encode_ss_frame');
        }
        if (AmpConfig::get('encode_ss_duration') && array_key_exists('duration', $options)) {
            $args .= ' ' . AmpConfig::get('encode_ss_duration');
        }
        $args .= ' ' . AmpConfig::get('transcode_input');

        if (AmpConfig::get('encode_srt') && array_key_exists('subtitle', $options)) {
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

        return array(
            'format' => $target,
            'command' => $cmd . $args
        );
    }

    /**
     * get_output_cache
     * @param string $source
     * @param string $target
     * @param string $player
     * @param string $media_type
     */
    public static function get_output_cache($source, $target = null, $player = null, $media_type = 'song'): string
    {
        if (!empty($GLOBALS['transcode'])) {
            return $GLOBALS['transcode'][$source][$target][$player][$media_type] ?? '';
        }

        return '';
    }

    /**
     * set_output_cache
     * @param string $source
     * @param string $target
     * @param string $player
     * @param string $media_type
     */
    public static function set_output_cache($output, $source, $target = null, $player = null, $media_type = 'song'): void
    {
        if (empty($GLOBALS['transcode']) || !is_array($GLOBALS['transcode'])) {
            $GLOBALS['transcode'] = array();
        }
        $GLOBALS['transcode'][$source][$target][$player][$media_type] = $output;
    }

    /**
     * start_transcode
     *
     * This is a rather complex function that starts the transcoding or
     * resampling of a media and returns the opened file handle.
     * @param Song|Podcast_Episode|Video $media
     * @param array $transcode_settings
     * @param array|string $options
     * @return array|false
     */
    public static function start_transcode($media, $transcode_settings, $options = array())
    {
        $out_file = false;
        if (is_string($options)) {
            $out_file = $options;
            $options  = array();
        }
        // Bail out early if we're unutterably broken
        if (empty($transcode_settings)) {
            debug_event(self::class, 'Transcode requested, but get_transcode_settings failed', 2);

            return false;
        }
        $song_file = self::scrub_arg($media->file);
        $bit_rate  = (isset($options['bitrate']))
            ? $options['bitrate']
            : self::get_max_bitrate($media, $transcode_settings);
        debug_event(self::class, 'Final transcode bitrate is ' . $bit_rate, 4);

        // Finalise the command line
        $command    = $transcode_settings['command'];
        $string_map = array(
            '%FILE%' => $song_file,
            '%SAMPLE%' => $bit_rate, // Deprecated
            '%BITRATE%' => $bit_rate
        );
        $string_map['%MAXBITRATE%'] = (isset($options['maxbitrate']))
            ? $options['maxbitrate']
            : 8000;
        if ($media instanceof Video) {
            $string_map['%RESOLUTION%'] = (isset($options['resolution']))
                ? $options['resolution']
                : $media->f_resolution ?? '1280x720';
            $string_map['%QUALITY%'] = (isset($options['quality']))
                ? (31 * (101 - $options['quality'])) / 100
                : 10;
        }
        if (isset($options['frame'])) {
            $frame                = gmdate("H:i:s", $options['frame']);
            $string_map['%TIME%'] = $frame;
        }
        if (isset($options['duration'])) {
            $duration                 = gmdate("H:i:s", $options['duration']);
            $string_map['%DURATION%'] = $duration;
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
        if ($out_file) {
            // when running cache_catalog_proc redirect to the file path instead of piping
            $command = (string)str_replace("pipe:1", $out_file, (string)$command);
            debug_event(self::class, 'Final command is ' . $command, 4);
            shell_exec($command);

            return array();
        }

        return self::start_process($command, array('format' => $transcode_settings['format']));
    }

    /**
     * This function behaves like escapeshellarg, but isn't broken
     * @param $arg
     */
    private static function scrub_arg($arg): string
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return '"' . str_replace(array('"', '%'), array('', ''), $arg) . '"';
        } else {
            return "'" . str_replace("'", "'\\''", $arg) . "'";
        }
    }

    /**
     * get_max_bitrate
     *
     * get the transcoded bitrate for players that require a bit of guessing and without actually transcoding
     * @param Song|Podcast_Episode|Video $media
     * @param array $transcode_settings
     */
    public static function get_max_bitrate($media, $transcode_settings): int
    {
        // don't ignore user bitrates
        $bit_rate = (int)self::get_allowed_bitrate();
        if (!array_key_exists('bitrate', $transcode_settings)) {
            // Validate the bitrate
            $bit_rate = self::validate_bitrate($bit_rate);
        } elseif ($bit_rate > (int)$transcode_settings['bitrate'] || $bit_rate == 0) {
            // use the file bitrate if lower than the gathered
            $bit_rate = $transcode_settings['bitrate'];
        }
        debug_event(self::class, 'Configured bitrate is ' . $bit_rate, 5);

        // Never upsample a media
        if (isset($media->bitrate) && $media->type == $transcode_settings['format'] && ($bit_rate * 1024) > $media->bitrate && $media->bitrate > 0) {
            debug_event(self::class, 'Clamping bitrate to avoid upsampling to ' . $bit_rate, 5);
            $bit_rate = self::validate_bitrate($media->bitrate / 1024);
        }

        return $bit_rate;
    }

    /**
     * get_image_preview
     * @param Video $media
     * @return string
     */
    public static function get_image_preview($media): ?string
    {
        $image = null;
        $sec   = ($media->time >= 30) ? 30 : (int) ($media->time / 2);
        $frame = gmdate("H:i:s", $sec);

        if (AmpConfig::get('transcode_cmd') && AmpConfig::get('transcode_input') && AmpConfig::get('encode_get_image')) {
            $command    = AmpConfig::get('transcode_cmd') . ' ' . AmpConfig::get('transcode_input') . ' ' . AmpConfig::get('encode_get_image');
            $string_map = array(
                '%FILE%' => self::scrub_arg($media->file),
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
    private static function start_process($command, $settings = array()): array
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
            $parray = array(
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
    public static function kill_process($transcoder): void
    {
        $status = proc_get_status($transcoder['process']);
        if ($status['running']) {
            $pid = $status['pid'];
            debug_event(self::class, 'WARNING Stream is probably being killed early! pid:' . $pid, 1);

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
     */
    public static function validate_bitrate($bitrate): int
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
    public static function garbage_collection(): void
    {
        // Remove any Now Playing entries for sessions that have been GC'd
        $sql = "DELETE FROM `now_playing` USING `now_playing` LEFT JOIN `session` ON `session`.`id` = `now_playing`.`id` WHERE (`session`.`id` IS NULL AND `now_playing`.`id` NOT IN (SELECT `username` FROM `user`)) OR `now_playing`.`expire` < '" . time() . "'";
        Dba::write($sql);
    }

    /**
     * insert_now_playing
     *
     * This will insert the Now Playing data.
     * @param int $object_id
     * @param int $uid
     * @param int $length
     * @param string $sid
     * @param string $type
     * @param int $previous
     */
    public static function insert_now_playing(
        $object_id,
        $uid,
        $length,
        $sid,
        $type,
        $previous = null
    ): void {
        if (!$previous) {
            $previous = time();
        }
        // Ensure that this client only has a single row
        $sql = "REPLACE INTO `now_playing` (`id`, `object_id`, `object_type`, `user`, `expire`, `insertion`) VALUES (?, ?, ?, ?, ?, ?)";
        Dba::write($sql, array($sid, $object_id, strtolower((string) $type), $uid, (int) (time() + (int) $length), $previous));
    }

    /**
     * delete_now_playing
     *
     * This will delete the Now Playing data.
     * @param string $sid
     * @param int $object_id
     * @param string $type
     * @param int $uid
     */
    public static function delete_now_playing($sid, $object_id, $type, $uid): void
    {
        // Clear the now playing entry for this item
        $sql = "DELETE FROM `now_playing` WHERE `id` = ? AND `object_id` = ? AND `object_type` = ? AND `user` = ?;";
        Dba::write($sql, array($sid, $object_id, strtolower((string) $type), $uid));
    }

    /**
     * clear_now_playing
     *
     * There really isn't anywhere else for this function, shouldn't have
     * deleted it in the first place.
     */
    public static function clear_now_playing(): bool
    {
        $sql = 'TRUNCATE `now_playing`';
        Dba::write($sql);

        return true;
    }

    /**
     * get_now_playing
     *
     * This returns the Now Playing information
     * @param int $user_id
     * @return list<array{
     *  media: library_item,
     *  client: User,
     *  agent: string,
     *  expire: int
     * }>
     */
    public static function get_now_playing($user_id = 0): array
    {
        $sql = "SELECT `session`.`agent`, `np`.* FROM `now_playing` AS `np` LEFT JOIN `session` ON `session`.`id` = `np`.`id` ";

        if (AmpConfig::get('now_playing_per_user')) {
            $sql .= "INNER JOIN (SELECT MAX(`insertion`) AS `max_insertion`, `user` FROM `now_playing` GROUP BY `user`) `np2` ON `np`.`user` = `np2`.`user` AND `np`.`insertion` = `np2`.`max_insertion` ";
        }
        $sql .= "WHERE `np`.`object_type` IN ('song', 'video') ";

        if (!Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)) {
            // We need to check only for users which have allowed view of personal info
            $personal_info_id = Preference::id_from_name('allow_personal_info_now');
            if ($personal_info_id && Core::get_global('user') instanceof User) {
                $current_user = Core::get_global('user')->getId();
                $sql .= "AND (`np`.`user` IN (SELECT `user` FROM `user_preference` WHERE ((`preference`='$personal_info_id' AND `value`='1') OR `user`='$current_user'))) ";
            }
        }
        $sql .= "ORDER BY `np`.`expire` DESC";
        //debug_event(self::class, 'get_now_playing ' . $sql, 5);

        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $className = ObjectTypeToClassNameMapper::map($row['object_type']);
            /** @var Song|Video $media */
            $media = new $className($row['object_id']);
            if ($media->isNew()) {
                continue;
            }
            if (($user_id === 0 || (int)$row['user'] == $user_id) && Catalog::has_access($media->getCatalogId(), (int)$row['user'])) {
                $client = new User($row['user']);
                $client->format();
                if ($client->isNew()) {
                    continue;
                }
                $media->format();
                $results[] = array(
                    'media' => $media,
                    'client' => $client,
                    'agent' => $row['agent'],
                    'expire' => (int) $row['expire']
                );
            }
        } // end while

        return $results;
    }

    /**
     * check_lock_media
     *
     * This checks to see if the media is already being played.
     * @param int $media_id
     * @param string $type
     */
    public static function check_lock_media($media_id, $type): bool
    {
        $sql        = "SELECT `object_id` FROM `now_playing` WHERE `object_id` = ? AND `object_type` = ?";
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
     */
    public static function run_playlist_method(): bool
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
    }

    /**
     * get_base_url
     * This returns the base requirements for a stream URL this does not include anything after the index.php?sid=????
     * @param bool $local
     * @param null|string $streamToken
     */
    public static function get_base_url($local = false, $streamToken = null): string
    {
        $base_url = '/play/index.php?';
        if (AmpConfig::get('use_play2')) {
            $base_url .= 'action=play2&';
        }
        if (AmpConfig::get('use_auth') && AmpConfig::get('require_session')) {
            $session_id = (!empty($streamToken))
                ? $streamToken
                : self::get_session();
            $base_url .= 'ssid=' . $session_id . '&';
        }

        $web_path = ($local)
            ? AmpConfig::get('local_web_path')
            : AmpConfig::get('web_path');
        if (empty($web_path) && !empty(AmpConfig::get('fallback_url'))) {
            $web_path = rtrim((string)AmpConfig::get('fallback_url'), '/');
        }

        if (AmpConfig::get('force_http_play')) {
            $web_path = str_replace("https://", "http://", $web_path);
        }

        $http_port = ($local && preg_match("/:(\d+)/", (string)$web_path, $matches))
            ? $matches[1]
            : AmpConfig::get('http_port');
        if (!empty($http_port) && $http_port != 80 && $http_port != 443) {
            if (preg_match("/:(\d+)/", $web_path, $matches)) {
                $web_path = str_replace(':' . $matches[1], ':' . $http_port, (string)$web_path);
            } else {
                $web_path = str_replace(AmpConfig::get('http_host'), AmpConfig::get('http_host') . ':' . $http_port, (string)$web_path);
            }
        }

        return $web_path . $base_url;
    }
}
