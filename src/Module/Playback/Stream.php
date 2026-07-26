<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Session;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;

class Stream
{
    /**
     * Players that get a `transcode_bitrate_<player>` preference of their own, matching the players that
     * already have an `encode_player_<player>_target` format override. Anything else uses the default rate.
     *
     * @var list<string>
     */
    public const array BITRATE_OVERRIDE_PLAYERS = ['webplayer', 'api'];

    /**
     * Output formats that must never be served from or written to the transcode cache.
     * Their loudness normalisation is applied per-source at stream time, so a cached copy
     * (keyed only by object + target extension) would be wrong for every other request.
     *
     * @var list<string>
     */
    public const array NON_CACHEABLE_FORMATS = ['mp3_rg', 'mp3_car', 'opus_rg', 'opus_car'];

    /**
     * Classification of the transcode output formats offered in the preferences picker.
     * A format is only actually available when a matching `encode_args_<format>` config key exists.
     *
     * @var array<string, list<string>>
     */
    private const array ENCODE_FORMAT_KINDS = [
        'audio' => ['mp3', 'ogg', 'opus', 'm4a', 'wav', 'mp3_rg', 'mp3_car', 'opus_rg', 'opus_car'],
        'video' => ['flv', 'webm', 'ts', 'ogv'],
    ];

    private static string $session = '';

    /**
     * check_lock_media
     *
     * This checks to see if the media is already being played.
     */
    public static function check_lock_media(int $media_id, string $type): bool
    {
        $sql        = "SELECT `object_id` FROM `now_playing` WHERE `object_id` = ? AND `object_type` = ?";
        $db_results = Dba::read($sql, [$media_id, $type]);

        if (Dba::num_rows($db_results) !== 0) {
            debug_event(self::class, 'Unable to play media currently locked by another user', 3);

            return false;
        }

        return true;
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
     * delete_now_playing
     *
     * This will delete the Now Playing data.
     */
    public static function delete_now_playing(string $sid, int $object_id, string $type, int $uid): void
    {
        // Clear the now playing entry for this item
        $sql = "DELETE FROM `now_playing` WHERE `id` = ? AND `object_id` = ? AND `object_type` = ? AND `user` = ?;";
        Dba::write($sql, [$sid, $object_id, strtolower($type), $uid]);
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
     * get_allowed_bitrate
     *
     * Work out the bitrate this user is allowed for the given player, after the site-wide dynamic downsampling
     * constraints. Passing null, or a player with no override of its own, uses the default `transcode_bitrate`.
     */
    public static function get_allowed_bitrate(?string $player = null): int
    {
        // All bitrate values (transcode_bitrate, max_bit_rate, min_bit_rate) are stored and
        // handled in bits per second (bps). max_bit_rate/min_bit_rate are per-user preferences.
        $max_bitrate   = (int) AmpConfig::get('max_bit_rate', 0);
        $min_bitrate   = (int) AmpConfig::get('min_bit_rate', 8000);
        $user_bit_rate = self::get_player_bitrate($player);

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
            if ($bit_rate < ($min_bitrate ?: 8000)) {
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

        return (int) $bit_rate;
    }

    /**
     * get_available_encode_formats
     *
     * Return the transcode output formats of a given kind ('audio'|'video') that are actually
     * configured (a matching `encode_args_<format>` exists). Used to populate the preference pickers
     * so the list reflects real server capabilities, including the ReplayGain (_rg/_car) profiles.
     *
     * @return list<string>
     */
    public static function get_available_encode_formats(string $kind): array
    {
        $formats = self::ENCODE_FORMAT_KINDS[$kind] ?? [];

        return array_values(
            array_filter(
                $formats,
                static fn(string $format): bool => !empty(AmpConfig::get('encode_args_' . $format))
            )
        );
    }

    /**
     * get_base_url
     * This returns the base requirements for a stream URL this does not include anything after the index.php?sid=????
     */
    public static function get_base_url(bool $local = false, ?string $streamToken = null): string
    {
        $base_url = '/play/index.php?';

        if (AmpConfig::get('use_auth') && AmpConfig::get('require_session')) {
            $session_id = (in_array($streamToken, [null, '', '0'], true))
                ? self::get_session()
                : $streamToken;
            $base_url .= 'ssid=' . $session_id . '&';
        }

        $web_path = ($local)
            ? AmpConfig::get('local_web_path')
            : AmpConfig::get_web_path();
        if (empty($web_path) && !empty(AmpConfig::get('fallback_url'))) {
            $web_path = rtrim((string) AmpConfig::get('fallback_url'), '/');
        }

        if (AmpConfig::get('force_http_play')) {
            $web_path = str_replace("https://", "http://", $web_path);
        }

        $http_port = ($local && preg_match("/:(\d+)/", (string) $web_path, $matches))
            ? $matches[1]
            : AmpConfig::get('http_port');
        if (!empty($http_port) && $http_port != 80 && $http_port != 443) {
            if (preg_match("/:(\d+)/", (string) $web_path, $matches)) {
                $web_path = str_replace(':' . $matches[1], ':' . $http_port, (string) $web_path);
            } else {
                $web_path = str_replace(AmpConfig::get('http_host'), AmpConfig::get('http_host') . ':' . $http_port, (string) $web_path);
            }
        }

        return $web_path . $base_url;
    }

    /**
     * get_image_preview
     */
    public static function get_image_preview(Video $media): ?string
    {
        $image = null;
        $sec   = mt_rand((int) ($media->time * 0.2), (int) ($media->time * 0.8));
        $frame = gmdate("H:i:s", $sec);

        if (AmpConfig::get('transcode_cmd') && AmpConfig::get('transcode_input') && AmpConfig::get('encode_get_image')) {
            $command    = AmpConfig::get('transcode_cmd') . ' ' . AmpConfig::get('transcode_input') . ' ' . AmpConfig::get('encode_get_image');
            $string_map = [
                '%FILE%' => self::_scrub_arg($media->file),
                '%TIME%' => $frame
            ];
            foreach ($string_map as $search => $replace) {
                $command = str_replace($search, $replace, $command, $ret);
                if ($ret === 0) {
                    debug_event(self::class, $search . ' not in transcode command', 5);
                }
            }

            $proc = self::_start_process($command);

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
     * get_latest_now_playing
     *
     * Return the most recently registered now-playing song/video for one of the given streaming session keys.
     * Used by the web player to resolve the real internal media that a random or democratic stream is actually playing
     * (those items only carry a placeholder in the client playlist).
     *
     * @param list<string> $session_ids
     * @return array{object_id: int, object_type: string}|null
     */
    public static function get_latest_now_playing(array $session_ids): ?array
    {
        $session_ids = array_values(array_filter($session_ids, static fn(string $sid): bool => $sid !== ''));
        if ($session_ids === []) {
            return null;
        }

        $placeholders = implode(', ', array_fill(0, count($session_ids), '?'));
        $sql          = "SELECT `object_id`, `object_type` FROM `now_playing` WHERE `id` IN ($placeholders) AND `object_type` IN ('song', 'video') ORDER BY `insertion` DESC LIMIT 1";
        $db_results   = Dba::read($sql, $session_ids);
        $row          = Dba::fetch_assoc($db_results);
        if ($row === []) {
            return null;
        }

        return [
            'object_id' => (int) $row['object_id'],
            'object_type' => (string) $row['object_type'],
        ];
    }

    /**
     * get_max_bitrate
     *
     * get the transcoded bitrate for players that require a bit of guessing and without actually transcoding
     * @param array{format?: string, command?: string} $transcode_settings
     * @param array{bitrate?: float|int, maxbitrate?: int, subtitle?: string, resolution?: string, quality?: int, frame?: float, duration?: float} $options
     */
    public static function get_max_bitrate(
        Podcast_Episode|Video|Song $media,
        array $transcode_settings,
        array $options,
        ?string $player = null,
    ): int {
        // don't ignore user bitrates
        $bit_rate = self::get_allowed_bitrate($player);
        if (!array_key_exists('bitrate', $options)) {
            // Validate the bitrate
            $bit_rate = self::validate_bitrate($bit_rate);
        } elseif ($bit_rate > ((int) $options['bitrate']) || $bit_rate === 0) {
            // use the file bitrate if lower than the gathered
            $bit_rate = $options['bitrate'];
        }

        debug_event(self::class, 'Configured bitrate is ' . $bit_rate, 5);

        // Never upsample a media ($media->bitrate and $bit_rate are both bps)
        if (
            isset($media->bitrate)
            && isset($transcode_settings['format'])
            && $media->type == $transcode_settings['format']
            && $bit_rate > $media->bitrate
            && $media->bitrate > 0
        ) {
            debug_event(self::class, 'Clamping bitrate to avoid upsampling to ' . $media->bitrate, 5);
            $bit_rate = self::validate_bitrate((int) $media->bitrate);
        }

        return (int) $bit_rate;
    }

    /**
     * get_now_playing
     *
     * This returns the Now Playing information
     * @return array<int, array{
     *     media: library_item,
     *     client: User,
     *     agent: string,
     *     expire: int
     * }>
     */
    public static function get_now_playing(int $user_id = 0): array
    {
        $sql    = "SELECT `session`.`agent`, `np`.* FROM `now_playing` AS `np` LEFT JOIN `session` ON `session`.`id` = `np`.`id` ";
        $params = [];

        if (AmpConfig::get('now_playing_per_user')) {
            $sql .= "INNER JOIN (SELECT MAX(`insertion`) AS `max_insertion`, `user` FROM `now_playing` GROUP BY `user`) `np2` ON `np`.`user` = `np2`.`user` AND `np`.`insertion` = `np2`.`max_insertion` ";
        }

        $sql .= "WHERE `np`.`object_type` IN ('song', 'video') ";

        // We need to check only for users which have allowed view of personal info
        if (!Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN) && Core::get_global('user') instanceof User) {
            $current_user = Core::get_global('user')->getId();
            $sql .= "AND (`np`.`user` IN (SELECT `user` FROM `user_preference` WHERE ((`name`='allow_personal_info_now' AND `value`='1') OR `user` = ?))) ";
            $params[] = $current_user;
        }

        $sql .= "ORDER BY `np`.`expire` DESC";
        //debug_event(self::class, 'get_now_playing ' . $sql, 5);

        $db_results = Dba::read($sql, $params);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $className = ObjectTypeToClassNameMapper::map($row['object_type']);
            /** @var Song|Video $media */
            $media = new $className($row['object_id']);
            if ($media->isNew()) {
                continue;
            }

            if (($user_id === 0 || (int) $row['user'] === $user_id) && Catalog::has_access($media->getCatalogId(), (int) $row['user'])) {
                $client = new User($row['user']);
                if ($client->isNew()) {
                    continue;
                }

                $results[] = [
                    'media' => $media,
                    'client' => $client,
                    'agent' => $row['agent'],
                    'expire' => (int) $row['expire']
                ];
            }
        }

        return $results;
    }

    /**
     * get_output_cache
     */
    public static function get_output_cache(
        string $source,
        ?string $target = null,
        ?string $player = null,
        string $media_type = 'song',
    ): string {
        if (!empty($GLOBALS['transcode'])) {
            return $GLOBALS['transcode'][$source][$target ?? ''][$player ?? ''][$media_type] ?? '';
        }

        return '';
    }

    /**
     * get_player_bitrate
     *
     * Return the bitrate (bps) this user wants for a given player, falling back to their default rate whenever
     * that player carries no override of its own; an override stored as 0 counts as unset. Only the web player
     * and the API can be overridden, so every other caller takes `transcode_bitrate` as it stands.
     */
    public static function get_player_bitrate(?string $player = null): int
    {
        if ($player !== null && in_array($player, self::BITRATE_OVERRIDE_PLAYERS, true)) {
            $override = (int) AmpConfig::get('transcode_bitrate_' . $player, 0);
            if ($override > 0) {
                return $override;
            }
        }

        return (int) AmpConfig::get('transcode_bitrate', 128000);
    }

    /**
     * get_session
     */
    public static function get_session(): string
    {
        if (self::$session === '' || self::$session === '0') {
            // Generate the session ID.  This is slightly wasteful.
            $data         = [];
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
     * Get stream types for media type.
     * @return list<string>
     */
    public static function get_stream_types_for_type(string $type, ?string $player = 'webplayer'): array
    {
        $types     = [];
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
     * Get transcode format for media based on config settings
     */
    public static function get_transcode_format(
        string $source,
        ?string $target = null,
        ?string $player = null,
        string $media_type = 'song',
    ): ?string {
        // check if we've done this before
        $format = self::get_output_cache($source, $target, $player, $media_type);
        if ($format !== '' && $format !== '0') {
            return $format;
        }

        $input_target = $target;
        // default target for songs
        $setting_target = 'encode_target';
        // default target for video
        if ($media_type !== 'song') {
            $setting_target = 'encode_' . $media_type . '_target';
        }

        if (!$player && in_array($media_type, ['song', 'podcast_episode'], true)) {
            $player = 'webplayer';
        }

        // webplayer / api transcode actions
        $has_player_target = false;
        if ($player) {
            // encode target for songs in webplayer/api
            $encode_target = 'encode_player_' . $player . '_target';
            if ($media_type !== 'song') {
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
     * Get transcode settings for media.
     * It can be confusing but when waveforms are enabled it will transcode the file twice.
     *
     * @param array{bitrate?: float|int, maxbitrate?: int, subtitle?: string, resolution?: string, quality?: int, frame?: float, duration?: float} $options
     * @return array{format?: string, command?: string}
     */
    public static function get_transcode_settings_for_media(
        string $source,
        ?string $target = null,
        ?string $player = null,
        string $media_type = 'song',
        array $options = [],
    ): array {
        $target = self::get_transcode_format($source, $target, $player, $media_type);
        $cmd    = AmpConfig::get('transcode_cmd_' . $source) ?? AmpConfig::get('transcode_cmd');
        if (empty($cmd)) {
            debug_event(self::class, 'A valid transcode_cmd is required to transcode', 5);

            return [];
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
        if (
            !$argst
            || !$target
        ) {
            debug_event(self::class, 'Target format ' . $target . ' is not properly configured', 2);

            return [];
        }

        $args .= ' ' . $argst;

        debug_event(self::class, 'Command: ' . $cmd . ' Arguments:' . $args, 5);

        return [
            'format' => $target,
            'command' => $cmd . $args,
        ];
    }

    /**
     * insert_now_playing
     *
     * This will insert the Now Playing data.
     */
    public static function insert_now_playing(
        int $object_id,
        int $uid,
        int $length,
        string $sid,
        string $type,
        ?int $previous = null,
    ): void {
        if (!$previous) {
            $previous = time();
        }

        // Ensure that this client only has a single row
        $sql = "REPLACE INTO `now_playing` (`id`, `object_id`, `object_type`, `user`, `expire`, `insertion`) VALUES (?, ?, ?, ?, ?, ?)";
        Dba::write($sql, [$sid, $object_id, strtolower($type), $uid, time() + $length, $previous]);
    }

    /**
     * kill_process
     */
    public static function kill_process(array $transcoder): void
    {
        $status = proc_get_status($transcoder['process']);
        if ($status['running']) {
            $pid = $status['pid'];
            debug_event(self::class, 'WARNING Stream is probably being killed early! pid:' . $pid, 1);

            (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') ? exec('kill -9 ' . $pid) : exec('taskkill /F /T /PID ' . $pid);

            proc_close($transcoder['process']);
        } else {
            debug_event(self::class, 'Process is not running, kill skipped.', 5);
        }
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
                $_SESSION['iframe']['target'] = AmpConfig::get_web_path() . '/stream.php?action=basket';
                break;
            case 'send_clear':
                $_SESSION['iframe']['target'] = AmpConfig::get_web_path() . '/stream.php?action=basket&playlist_method=clear';
                break;
            case 'clear':
            case 'default':
            default:
                return true;
        }

        // Load our javascript
        echo "<script>";
        echo Core::get_reloadutil() . "('" . $_SESSION['iframe']['target'] . "');";
        echo "</script>";

        return true;
    }

    /**
     * set_output_cache
     */
    public static function set_output_cache(
        ?string $output,
        string $source,
        ?string $target = null,
        ?string $player = null,
        string $media_type = 'song',
    ): void {
        if (empty($GLOBALS['transcode']) || !is_array($GLOBALS['transcode'])) {
            $GLOBALS['transcode'] = [];
        }

        $GLOBALS['transcode'][$source][$target ?? ''][$player ?? ''][$media_type] = $output;
    }

    /**
     * set_session
     *
     * This overrides the normal session value, without adding another session into the database, should be called with care
     */
    public static function set_session(int|string $sid): void
    {
        if ($sid !== 0 && ($sid !== '' && $sid !== '0')) {
            self::$session = (string) $sid;
        }
    }

    /**
     * skip_transcode
     * True when a transcode would hand back the source format at (or above) the rate it already has, which can only
     * lose quality, so the original file is the better stream. Rates are bps and 0 means "not requested"; an unknown
     * source rate or a different output format never skips because that conversion is the point of the transcode.
     */
    public static function skip_transcode(
        ?string $output_format,
        string $source_format,
        int $source_rate,
        int $requested_rate = 0,
        int $max_rate = 0,
        ?string $player = null,
    ): bool {
        if ($output_format !== $source_format || $source_rate <= 0) {
            return false;
        }

        $target_rate = ($requested_rate > 0)
            ? $requested_rate
            : self::get_allowed_bitrate($player);
        if ($max_rate > 0 && $max_rate < $target_rate) {
            $target_rate = $max_rate;
        }

        if ($target_rate < $source_rate) {
            return false;
        }

        debug_event(self::class, 'Not transcoding ' . $source_format . ' to itself; target ' . $target_rate . ' is not below the source bitrate ' . $source_rate, 4);

        return true;
    }

    /**
     * start_transcode
     *
     * This is a rather complex function that starts the transcoding or
     * resampling of a media and returns the opened file handle.
     * @param array{format?: string, command?: string} $transcode_settings
     * @param array{bitrate?: float|int, maxbitrate?: int, subtitle?: string, resolution?: string, quality?: int, frame?: float, duration?: float}|string $options
     * @return array{
     *     handle?: resource|null,
     *     process?: resource|null,
     *     stderr?: resource|null,
     *     format?: string|null
     * }
     */
    public static function start_transcode(
        Podcast_Episode|Video|Song $media,
        array $transcode_settings,
        array|string $options = [],
        ?string $player = null,
    ): array {
        $out_file = false;
        if (is_string($options)) {
            $out_file = $options;
            $options  = [];
        }

        // Bail out early if we're unutterably broken
        if ($transcode_settings === [] || !array_key_exists('command', $transcode_settings) || !array_key_exists('format', $transcode_settings)) {
            debug_event(self::class, 'Transcode requested, but get_transcode_settings failed', 2);

            return [];
        }

        $song_file = self::_scrub_arg($media->file);
        $bit_rate  = isset($options['bitrate'])
            ? (int) $options['bitrate']
            : self::get_max_bitrate($media, $transcode_settings, $options, $player);
        debug_event(self::class, 'Final transcode bitrate is ' . $bit_rate, 4);

        // Both %BITRATE% and %MAXBITRATE% are substituted as plain bps values
        $max_bit_rate = (int) ($options['maxbitrate'] ?? 8000000);

        // Finalise the command line
        $command    = $transcode_settings['command'];
        $string_map = [
            '%FILE%' => $song_file,
        ];
        if ($media instanceof Video) {
            $string_map['%RESOLUTION%'] = $options['resolution'] ?? $media->get_f_resolution() ?? '1280x720';
            $string_map['%QUALITY%']    = (isset($options['quality']))
                ? (31 * (101 - $options['quality'])) / 100
                : 10;
        }

        if (isset($options['frame'])) {
            $frame                = gmdate("H:i:s", (int) $options['frame']);
            $string_map['%TIME%'] = $frame;
        }

        if (isset($options['duration'])) {
            $duration                 = gmdate("H:i:s", (int) $options['duration']);
            $string_map['%DURATION%'] = $duration;
        }

        if (!empty($options['subtitle'])) {
            // This is too specific to ffmpeg/avconv
            $string_map['%SRTFILE%'] = str_replace(':', '\:', self::_scrub_arg($options['subtitle']));
        }

        foreach ($string_map as $search => $replace) {
            $command = str_replace($search, (string) $replace, $command, $ret);
            if ($ret === 0) {
                debug_event(self::class, $search . ' not in transcode command', 5);
            }
        }

        $command = self::_replace_bitrates(
            (string) $command,
            [
                '%SAMPLE%' => $bit_rate,
                '%BITRATE%' => $bit_rate,
                '%MAXBITRATE%' => $max_bit_rate,
            ]
        );

        if ($out_file) {
            // when running cache_catalog_proc redirect to the file path instead of piping
            $command = str_replace("pipe:1", $out_file, (string) $command);
            debug_event(self::class, 'Final command is ' . $command, 4);
            $process = proc_open($command, [], $pipes);
            if (is_resource($process)) {
                proc_close($process);
            }

            return [];
        }

        return self::_start_process($command, ['format' => (string) $transcode_settings['format']]);
    }

    /**
     * validate_bitrate
     * this function takes a bitrate (in bps) and returns a valid one rounded to the nearest kbps
     */
    public static function validate_bitrate(int $bitrate): int
    {
        /* Round to standard bitrates (values are bps, round to 1 kbps steps) */
        return (int) (1000 * (floor($bitrate / 1000)));
    }

    /**
     * _replace_bitrates
     * Substitute the rate placeholders in a transcode command. Rates are plain bits per second now, so a
     * trailing `k` or `K` left over from a pre-8.0.0 config (`%BITRATE%k`) is consumed with the placeholder.
     * @param array<string, int> $bitrate_map
     */
    private static function _replace_bitrates(string $command, array $bitrate_map): string
    {
        foreach ($bitrate_map as $search => $replace) {
            $count   = 0;
            $command = (string) preg_replace('/' . preg_quote($search, '/') . '[kK]?/', (string) $replace, $command, -1, $count);
            if ($count === 0) {
                debug_event(self::class, $search . ' not in transcode command', 5);
            }
        }

        return $command;
    }

    /**
     * This function behaves like escapeshellarg, but isn't broken
     */
    private static function _scrub_arg(?string $arg): string
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return '"' . str_replace(['"', '%'], ['', ''], (string) $arg) . '"';
        }

        return "'" . str_replace("'", "'\\''", (string) $arg) . "'";
    }

    /**
     * start_process
     * @param array{format?: string} $settings
     * @return array{
     *     handle: resource|null,
     *     process?: resource,
     *     stderr?: resource|null,
     *     format?: string
     * }
     */
    private static function _start_process(string $command, array $settings = []): array
    {
        debug_event(self::class, "Transcode command: " . $command, 3);

        $descriptors = [1 => ['pipe', 'w']];
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            // Windows doesn't like to provide stderr as a pipe
            $descriptors[2] = ['pipe', 'w'];
            $cmdPrefix      = "exec ";
        } else {
            $cmdPrefix = "start /B ";
        }

        debug_event(self::class, "Transcode command prefix: " . $cmdPrefix, 3);

        $parray  = ['handle' => null];
        $process = proc_open($cmdPrefix . $command, $descriptors, $pipes);
        if ($process === false) {
            debug_event(self::class, 'Transcode command failed to open.', 1);
        } else {
            $parray = [
                'process' => $process,
                'handle' => $pipes[1],
                'stderr' => $pipes[2]
            ];

            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                stream_set_blocking($pipes[2], false); // Be sure stderr is non-blocking
            }
        }

        return array_merge($parray, $settings);
    }
}
