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

namespace Ampache\Module\Application\Playback;

use Ampache\Config\AmpConfig;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authentication\AuthenticationManagerInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\Catalog_local;
use Ampache\Module\Catalog\Catalog_remote;
use Ampache\Module\Catalog\Catalog_subsonic;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Session;
use Ampache\Module\Util\Horde_Browser;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Random;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Song_Preview;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class PlayAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'play';

    private RequestParserInterface $requestParser;

    private Horde_Browser $browser;

    private AuthenticationManagerInterface $authenticationManager;

    private NetworkCheckerInterface $networkChecker;

    private UserRepositoryInterface $userRepository;

    private LoggerInterface $logger;

    public function __construct(
        RequestParserInterface $requestParser,
        Horde_Browser $browser,
        AuthenticationManagerInterface $authenticationManager,
        NetworkCheckerInterface $networkChecker,
        UserRepositoryInterface $userRepository,
        LoggerInterface $logger
    ) {
        $this->requestParser         = $requestParser;
        $this->browser               = $browser;
        $this->authenticationManager = $authenticationManager;
        $this->networkChecker        = $networkChecker;
        $this->userRepository        = $userRepository;
        $this->logger                = $logger;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        //$this->logger->debug(print_r(apache_request_headers(), true), [LegacyLogger::CONTEXT_TYPE => self::class]);
        ob_end_clean();

        $use_auth         = AmpConfig::get('use_auth');
        $can_share        = AmpConfig::get('share');
        $player_customize = AmpConfig::get('transcode_player_customize');
        $session_name     = AmpConfig::get('session_name', 'ampache');
        $require_session  = AmpConfig::get('require_session');
        $localnet_session = AmpConfig::get('require_localnet_session');

        /**
         * The following code takes a "beautiful" url, splits it into key/value pairs and
         * then replaces the PHP $_REQUEST as if the URL had arrived in un-beautified form.
         * (This is necessary to avoid some DLNA players barfing on the URL, particularly Windows Media Player)
         *
         * The reason for not trying to do the whole job in mod_rewrite is that there are typically
         * more than 10 arguments to this function now, and that's tricky with mod_rewrite's 10 arg limit
         */
        $slashcount = substr_count($_SERVER['QUERY_STRING'], '/');
        if ($slashcount > 2) {
            // e.g. ssid/3ca112fff23376ef7c74f018497dd39d/type/song/oid/280/uid/player/api/name/Glad.mp3
            $new_arr     = explode('/', $_SERVER['QUERY_STRING']);
            $new_request = [];
            $key         = null;
            $i           = 0;
            // alternate key and value through the split array e.g:
            // array('ssid', '3ca112fff23376ef7c74f018497dd39d', 'type', 'song', 'oid', '280', 'uid', 'player', 'api', 'name', 'Glad.mp3))
            foreach ($new_arr as $v) {
                if ($i == 0) {
                    // key name
                    $key = $v;
                    $i   = 1;
                } else {
                    // key value
                    $value = $v;
                    $i     = 0;
                    // set it now that you've set both
                    $new_request[$key] = $value;
                }
            }
            $_REQUEST     = $new_request;
            $action       = (string)($new_request['action'] ?? '');
            $stream_name  = (string)($new_request['name'] ?? '');
            $object_id    = (int)scrub_in((string) ($new_request['oid'] ?? 0));
            $user_id      = (int)scrub_in((string) ($new_request['uid'] ?? 0));
            $session_id   = (string)scrub_in((string) ($new_request['ssid'] ?? ''));
            $type         = scrub_in((string) ($new_request['type'] ?? ''));
            $client       = (string)scrub_in((string) ($new_request['client'] ?? ''));
            $cache        = (int)scrub_in((string) ($new_request['cache'] ?? 0));
            $bitrate      = (int)scrub_in((string) ($new_request['bitrate'] ?? 0));
            $player       = scrub_in((string) ($new_request['player'] ?? ''));
            $format       = scrub_in((string) ($new_request['format'] ?? ''));
            $original     = ($format == 'raw');
            $transcode_to = (!$original && $format != '')
                ? $format
                : scrub_in((string) ($new_request['transcode_to'] ?? ''));

            // Share id and secret if used
            $share_id = (int)scrub_in((string) ($new_request['share_id'] ?? 0));
            $secret   = (string)scrub_in((string) ($new_request['share_secret'] ?? ''));

            // This is specifically for tmp playlist requests
            $demo_id = (int)scrub_in((string) ($new_request['demo_id'] ?? 0));
            $random  = (int)scrub_in((string) ($new_request['random'] ?? 0));

            // don't put this one here
            $cpaction = null;
        } else {
            /* These parameters had better come in on the url. */
            $action       = (string)filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
            $stream_name  = (string)filter_input(INPUT_GET, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
            $object_id    = (int)filter_input(INPUT_GET, 'oid', FILTER_SANITIZE_NUMBER_INT);
            $user_id      = (int)filter_input(INPUT_GET, 'uid', FILTER_SANITIZE_NUMBER_INT);
            $session_id   = (string)scrub_in((string) filter_input(INPUT_GET, 'ssid', FILTER_SANITIZE_SPECIAL_CHARS));
            $type         = scrub_in((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS));
            $client       = (string)scrub_in((string) filter_input(INPUT_GET, 'client', FILTER_SANITIZE_SPECIAL_CHARS));
            $cache        = (int)scrub_in((string) filter_input(INPUT_GET, 'cache', FILTER_SANITIZE_NUMBER_INT));
            $bitrate      = (int)filter_input(INPUT_GET, 'bitrate', FILTER_SANITIZE_NUMBER_INT);
            $player       = scrub_in((string) filter_input(INPUT_GET, 'player', FILTER_SANITIZE_SPECIAL_CHARS));
            $format       = scrub_in((string) filter_input(INPUT_GET, 'format', FILTER_SANITIZE_SPECIAL_CHARS));
            $original     = ($format == 'raw');
            $transcode_to = (!$original && $format != '')
                ? $format
                : scrub_in((string) filter_input(INPUT_GET, 'transcode_to', FILTER_SANITIZE_SPECIAL_CHARS));

            // Share id and secret if used
            $share_id = (int)filter_input(INPUT_GET, 'share_id', FILTER_SANITIZE_NUMBER_INT);
            $secret   = scrub_in((string) filter_input(INPUT_GET, 'share_secret', FILTER_SANITIZE_SPECIAL_CHARS));

            // This is specifically for tmp playlist requests
            $demo_id = (int)filter_input(INPUT_GET, 'demo_id', FILTER_SANITIZE_NUMBER_INT);
            $random  = (int)filter_input(INPUT_GET, 'random', FILTER_SANITIZE_NUMBER_INT);

            // run_custom_play_action... whatever that is
            $cpaction = filter_input(INPUT_GET, 'custom_play_action', FILTER_SANITIZE_NUMBER_INT);
        }
        //$this->logger->debug('REQUEST: ' . print_r($_REQUEST, true), [LegacyLogger::CONTEXT_TYPE => self::class]);
        // democratic play url doesn't include these
        if ($demo_id > 0) {
            $type = 'song';
        }
        // random play url can be multiple types but default to song if missing
        if ($random === 1) {
            $type = 'song';
        }
        // if you don't specify, assume stream
        if (empty($action)) {
            $action = 'stream';
        }

        // disable share access if config is disabled
        if (!$can_share && $share_id > 0) {
            $this->logger->error(
                'Enable: share to allow access to shares',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            $share_id = 0;
            $secret   = '';
        }

        // allow disabling stat recording from the play url
        $record_stats = true;
        if (
            $share_id ||
            $cache == 1 ||
            !in_array($type, ['song', 'video', 'podcast_episode'])
        ) {
            $this->logger->debug(
                'record_stats disabled: cache {' . $type . "}",
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            $record_stats = false;
        }

        $is_download   = ($action == 'download');
        $maxbitrate    = 0;
        $media_bitrate = 0;
        $resolution    = '';
        $quality       = 0;
        $time          = time();

        if ($player_customize && !$original) {
            // Trick to avoid LimitInternalRecursion reconfiguration
            $vsettings = scrub_in((string) filter_input(INPUT_GET, 'transcode_to', FILTER_SANITIZE_SPECIAL_CHARS));
            if (!empty($vsettings)) {
                $vparts  = explode('-', $vsettings);
                $v_count = count($vparts);
                for ($i = 0; $i < $v_count; $i += 2) {
                    switch ($vparts[$i]) {
                        case 'maxbitrate':
                            $maxbitrate = (int)($vparts[$i + 1]);
                            break;
                        case 'resolution':
                            $resolution = $vparts[$i + 1];
                            break;
                        case 'quality':
                            $quality = (int)($vparts[$i + 1]);
                            break;
                    }
                }
            }
        }
        $subtitle         = '';
        $send_full_stream = (string)AmpConfig::get('send_full_stream');
        $send_all_in_once = ($send_full_stream == 'true' || $send_full_stream == $player);

        if (!$type) {
            $type = 'song';
        }

        $this->logger->debug(
            "Asked for type {" . $type . "}",
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        if ($type == 'playlist') {
            $playlist_type = scrub_in((string) $_REQUEST['playlist_type']);
            $object_id     = $session_id;
        }

        // First things first, if we don't have a uid/oid stop here
        if (
            empty($object_id) &&
            (
                !$demo_id &&
                !$share_id &&
                !$secret &&
                !$random
            )
        ) {
            $this->logger->error(
                'No object OID specified, nothing to play',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            header('HTTP/1.1 400 Nothing To Play');

            return null;
        }

        // Authenticate the user if specified
        $username = $_REQUEST['PHP_AUTH_USER'] ?? '';
        if (empty($username)) {
            $username = $_REQUEST['u'] ?? '';
        }
        $password = $_REQUEST['PHP_AUTH_PW'] ?? '';
        if (empty($password)) {
            $password = $_REQUEST['p'] ?? '';
        }
        $apikey    = $_REQUEST['apikey'] ?? '';
        $user      = null;
        $user_auth = false;
        // If explicit user authentication was passed
        if (!empty($session_id)) {
            $user = $this->userRepository->findByStreamToken(trim($session_id));
            if ($user) {
                $user_auth = true;
                $agent     = (!empty($client))
                    ? $client
                    : substr(Core::get_server('HTTP_USER_AGENT'), 0, 254);
                // this is a permastream link so create a session
                if (!Session::exists(AccessTypeEnum::STREAM->value, $session_id)) {
                    Session::create(
                        [
                            'sid' => $session_id,
                            'username' => $user->username,
                            'value' => '',
                            'type' => 'stream',
                            'agent' => ''
                        ]
                    );
                } else {
                    Session::update_agent($session_id, $agent);
                    Session::extend($session_id, AccessTypeEnum::STREAM->value);
                }
            }
        } elseif (!empty($apikey)) {
            $user = $this->userRepository->findByApiKey(trim($apikey));
            if ($user) {
                $user_auth = true;
            }
        } elseif (!empty($username) && !empty($password)) {
            $auth = $this->authenticationManager->login($username, $password);
            if ($auth['success']) {
                $user      = User::get_from_username($auth['username']);
                $user_auth = true;
            }
        }
        // try the session ID as well
        if (!$user instanceof User) {
            $user = User::get_from_username(Session::username($session_id));
        }

        // Identify the user according to it's web session
        // We try to avoid the generic 'Ampache User' as much as possible
        if (!($user instanceof User) && array_key_exists($session_name, $_COOKIE) && Session::exists(AccessTypeEnum::INTERFACE->value, $_COOKIE[$session_name])) {
            Session::check();
            $user = (array_key_exists('userdata', $_SESSION) && array_key_exists('username', $_SESSION['userdata']))
                ? User::get_from_username($_SESSION['userdata']['username'])
                : new User(-1);
        }

        // did you pass a specific user id? (uid)
        $user_id = ($user instanceof User)
            ? $user->id
            : $user_id;

        if (!$share_id) {
            // No explicit authentication, use session
            if (!$user instanceof User) {
                $user = new User($user_id);
            }

            // If the user has been disabled (true value)
            if (make_bool($user->disabled)) {
                $this->logger->warning(
                    $user->username . " is currently disabled, stream access denied",
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
                header('HTTP/1.1 403 User disabled');

                return null;
            }

            // If require_session is set then we need to make sure we're legit
            if (!$user_auth && $use_auth && $require_session) {
                if (!$localnet_session && $this->networkChecker->check(AccessTypeEnum::NETWORK, Core::get_global('user')?->getId(), AccessLevelEnum::GUEST)) {
                    $this->logger->notice(
                        'Streaming access allowed for local network IP ' . filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP),
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                } elseif (!Session::exists(AccessTypeEnum::STREAM->value, $session_id)) {
                    // No valid session id given, try with cookie session from web interface
                    $session_id = $_COOKIE[$session_name] ?? false;
                    if ($session_id === false || !Session::exists(AccessTypeEnum::INTERFACE->value, $session_id)) {
                        $this->logger->warning(
                            "Streaming access denied: Session $session_id has expired",
                            [LegacyLogger::CONTEXT_TYPE => self::class]
                        );
                        header('HTTP/1.1 403 Session Expired');

                        return null;
                    }
                }
                // Now that we've confirmed the session is valid extend it
                Session::extend($session_id, AccessTypeEnum::STREAM->value);
            }

            // Update the users last seen information
            $this->userRepository->updateLastSeen($user->id);
        } else {
            $user_id = 0;
            $share   = new Share($share_id);

            if (!$share->is_valid($secret, 'stream')) {
                header('HTTP/1.1 403 Access Unauthorized');

                return null;
            }

            if (!$share->is_shared_media((int) $object_id)) {
                header('HTTP/1.1 403 Access Unauthorized');

                return null;
            }

            $user = new User($share->user);
        }

        if ((!$user instanceof User || $user->isNew()) && (!$share_id && !$secret)) {
            $this->logger->error(
                'No user specified {' . print_r($user, true) . '}',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            header('HTTP/1.1 400 No User Specified');

            return null;
        }
        Session::createGlobalUser($user);
        Preference::init();

        // If we are in demo mode; die here
        if (AmpConfig::get('demo_mode')) {
            throw new AccessDeniedException(
                'Streaming Access Denied: Disable demo_mode in \'config/ampache.cfg.php\''
            );
        }
        // Check whether streaming is allowed
        $prefs = AmpConfig::get('allow_stream_playback') && $_SESSION['userdata']['preferences']['allow_stream_playback'];
        if (!$prefs) {
            throw new AccessDeniedException(
                'Streaming Access Denied: Enable \'Allow Streaming\' in Server Config -> Options'
            );
        }

        // If they are using access lists let's make sure that they have enough access to play this mojo
        if (AmpConfig::get('access_control')) {
            if (
                !$this->networkChecker->check(AccessTypeEnum::STREAM, Core::get_global('user')?->getId()) &&
                !$this->networkChecker->check(AccessTypeEnum::NETWORK, Core::get_global('user')?->getId())
            ) {
                throw new AccessDeniedException(
                    sprintf('Streaming Access Denied: %s does not have stream level access', Core::get_user_ip())
                );
            }
        } // access_control is enabled

        // Handle playlist downloads
        if ($type == 'playlist' && isset($playlist_type)) {
            $playlist = new Stream_Playlist($object_id);
            // Some rudimentary security
            if ($user_id != $playlist->user) {
                throw new AccessDeniedException();
            }

            $playlist->generate_playlist($playlist_type);

            return null;
        }

        /**
         * If we've got a Democratic playlist then get the current song and redirect to that media files URL
         */
        if ($demo_id > 0) {
            $democratic = new Democratic($demo_id);
            $democratic->set_parent();

            // If there is a cooldown we need to make sure this song isn't a repeat
            if (!$democratic->cooldown) {
                // This takes into account votes, etc and removes the
                $object_id = $democratic->get_next_object();
            } else {
                // Pull history
                $song_cool_check = 0;
                $object_id       = $democratic->get_next_object($song_cool_check);
                $object_ids      = $democratic->get_cool_songs();
                while (in_array($object_id, $object_ids)) {
                    $song_cool_check++;
                    $object_id = $democratic->get_next_object($song_cool_check);
                    if ($song_cool_check >= '5') {
                        break;
                    }
                } // while we've got the 'new' song in old the array
            } // end if we've got a cooldown
            $media = new Song($object_id);
            if ($media->id > 0) {
                // Always remove the play from the list
                $democratic->delete_from_oid($media->id, $type);

                // If the media is disabled
                if ((isset($media->enabled) && !make_bool($media->enabled)) || !Core::is_readable(Core::conv_lc_file((string)$media->file))) {
                    $this->logger->warning(
                        "Error: " . $media->file . " is currently disabled, song skipped",
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                    header('HTTP/1.1 404 File disabled');

                    return null;
                }

                // play the song instead of going through all the crap
                header('Location: ' . $media->play_url('', $player, false, $user->id, $user->streamtoken), true, 303);

                return null;
            }
            $this->logger->warning(
                "Error: DEMOCRATIC song could not be found",
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            header('HTTP/1.1 404 File not found');

            return null;
        } // if democratic ID passed

        /**
         * if we are doing random let's pull the random object and redirect to that media files URL
         */
        if ($random === 1) {
            $last_id   = (int)User::get_user_data($user_id, 'random_song', 0)['random_song'];
            $last_time = (int)User::get_user_data($user_id, 'random_time', 0)['random_time'];
            if ($last_id > 0 && $last_time >= $time) {
                // continue the current object
                $object_id = $last_id;
                $this->logger->debug(
                    'Called random again too quickly sending last song id: {' . $object_id . '}',
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            } else {
                // get a new random object and redirect to that object
                if (array_key_exists('random_type', $_REQUEST)) {
                    $rtype = $_REQUEST['random_type'];
                } else {
                    $rtype = $type;
                }
                $object_id = Random::get_single_song($rtype, $user, (int)$_REQUEST['random_id']);
            }
            $media = new Song($object_id);
            if ($media->id > 0) {
                // If the media is disabled
                if ((isset($media->enabled) && !make_bool($media->enabled)) || !Core::is_readable(Core::conv_lc_file((string)$media->file))) {
                    $this->logger->warning(
                        "Error: " . $media->file . " is currently disabled, song skipped",
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                    header('HTTP/1.1 404 File disabled');

                    return null;
                }
                // Save this for a short time in case there are issues loading the url
                User::set_user_data($user_id, 'random_song', $object_id);
                User::set_user_data($user_id, 'random_time', ($time + (min(10, ($media->time)))));

                // play the song instead of going through all the crap
                header('Location: ' . $media->play_url('', $player, false, $user->id, $user->streamtoken), true, 303);

                return null;
            }
            $this->logger->warning(
                "Error: RANDOM song could not be found",
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            header('HTTP/1.1 404 File not found');

            return null;
        } // if random

        if ($type == 'video') {
            $media = new Video((int) $object_id);
            if (array_key_exists('subtitle', $_REQUEST)) {
                $subtitle = $media->get_subtitle_file($_REQUEST['subtitle']);
            }
        } elseif ($type == 'song_preview') {
            $media = new Song_Preview((int) $object_id);
        } elseif ($type == 'podcast_episode') {
            $media = new Podcast_Episode((int) $object_id);
        } else {
            // default to song
            $media = new Song((int) $object_id);
        }
        if ($media->isNew()) {
            $this->logger->error(
                "Media " . $object_id . " not found",
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            header('HTTP/1.1 404 Invalid media, file not found or file unreadable');

            return null;
        }

        if (!User::stream_control([['object_type' => $type, 'object_id' => $media->id]])) {
            throw new AccessDeniedException(
                sprintf(
                    'Stream control failed for user %s on %s',
                    Core::get_global('user')?->username,
                    $media->get_stream_name()
                )
            );
        }

        $transcode     = false;
        $transcode_cfg = AmpConfig::get('transcode', 'default');
        $cache_file    = false;
        $mediaOwnerId  = ($media instanceof Song_Preview)
            ? null
            : $media->get_user_owner();
        $mediaCatalogId = ($media instanceof Song_Preview)
            ? null
            : $media->catalog;
        if ($mediaCatalogId) {
            /** @var Song|Podcast_Episode|Video $media */
            // The media catalog is restricted
            $catalogs = (isset($user->catalogs['music'])) ? $user->catalogs['music'] : User::get_user_catalogs($user->id);
            if (!in_array($mediaCatalogId, $catalogs) && ($mediaOwnerId === null || $mediaOwnerId !== $user->id)) {
                $this->logger->warning(
                    "Error: You are not allowed to play $media->file",
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                return null;
            }
            // If we are running in Legalize mode, don't play medias already playing
            if (AmpConfig::get('lock_songs')) {
                if (!Stream::check_lock_media($media->id, $type)) {
                    return null;
                }
            }

            $catalog      = Catalog::create_from_id($mediaCatalogId);
            $cache_path   = (string)AmpConfig::get('cache_path', '');
            $cache_target = AmpConfig::get('cache_target', '');
            $file_target  = (!empty($cache_target) && $cache_target === $transcode_to)
                ? Catalog::get_cache_path($media->id, $mediaCatalogId, $cache_path, $cache_target)
                : null;

            $has_cache = ($file_target !== null && is_file($file_target));
            if ($catalog && !$has_cache) {
                if (($catalog instanceof Catalog_remote || $catalog instanceof Catalog_subsonic) && AmpConfig::get('cache_remote', '')) {
                    $media_file = $catalog->getRemoteStreamingUrl($media);
                    if ($file_target && $media_file) {
                        $catalog->cache_catalog_file($file_target, $media_file);
                    }
                }
                if ($catalog instanceof Catalog_local && $file_target && $cache_target) {
                    $catalog->cache_catalog_file($file_target, $media, $cache_target);
                }
            }

            if ($has_cache) {
                $size = Core::get_filesize($file_target);
                sleep(2);
                while ($size > 0 && $size !== Core::get_filesize($file_target)) {
                    $size = Core::get_filesize($file_target);
                    sleep(2);
                }
            }

            if (
                $file_target &&
                $transcode_cfg != 'never' &&
                $transcode_to &&
                ($bitrate === 0 || $bitrate = (int)AmpConfig::get('transcode_bitrate', 128) * 1000) &&
                $has_cache
            ) {
                $this->logger->debug(
                    'Found pre-cached file {' . $file_target . '}',
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
                $cache_file   = true;
                $original     = true;
                $transcode_to = null;

                $streamConfiguration = [
                    'file_path' => $file_target,
                    'file_name' => $media->getFileName(),
                    'file_size' => (($media->file && preg_match('/^https?:\/\//i', $media->file)) || time() - filemtime($file_target) < 30) ? $media->size : Core::get_filesize($file_target),
                    'file_type' => $cache_target,
                ];
            } elseif ($catalog === null) {
                return null;
            } else {
                // Some catalogs redirect you to the remote url so stop here
                $remoteStreamingUrl = $catalog->getRemoteStreamingUrl($media);
                if ($remoteStreamingUrl !== null) {
                    $this->logger->debug(
                        'Started remote stream - ' . $remoteStreamingUrl,
                        [
                            LegacyLogger::CONTEXT_TYPE => self::class,
                            'catalog_type' => $catalog->get_type()
                        ]
                    );

                    header('Location: ' . $remoteStreamingUrl);

                    return null;
                }

                $streamConfiguration = $catalog->prepare_media($media);
                if ($streamConfiguration === null) {
                    return null;
                }
            }
        } else {
            // No catalog, must be song preview or something like that => just redirect to file
            if ($type == "song_preview" && $media instanceof Song_Preview) {
                $media->stream(); // header redirect using preview plugin ($plugin->_plugin->stream_song_preview())
            } else {
                header('Location: ' . $media->file, true, 303);
            }

            return null;
        }
        // load the cache file or the local file
        $stream_file = ($cache_file && $file_target)
            ? $file_target
            : $streamConfiguration['file_path'];

        /* If we don't have a file, or the file is not readable */
        if (!$stream_file || !Core::is_readable(Core::conv_lc_file((string)$stream_file))) {
            $this->logger->error(
                "Media " . $stream_file . " ($media->title). Invalid media, file not found or file unreadable",
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            header('HTTP/1.1 404 Invalid media, file not found or file unreadable');

            return null;
        }

        // don't abort the script if user skips this media because we need to update now_playing
        ignore_user_abort(true);

        // Format the media name
        $media_name = (!empty($stream_name))
            ? $stream_name
            : $media->get_stream_name() . "." . $streamConfiguration['file_type'];
        $transcode_to = ($transcode_cfg == 'never' || $cache_file || ($is_download && !$transcode_to))
            ? null
            : Stream::get_transcode_format($streamConfiguration['file_type'], $transcode_to, $player, $type);

        header('Access-Control-Allow-Origin: *');

        $sessionkey = ($session_id === '')
            ? Stream::get_session()
            : $session_id;
        $agent = (!empty($client))
            ? $client
            : Session::agent($sessionkey);
        $location = Session::get_geolocation($sessionkey);

        // If they are just trying to download make sure they have rights and then present them with the download file
        if ($is_download && !$transcode_to) {
            $this->logger->notice(
                'Downloading raw file...',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            // STUPID IE
            $media_name = str_replace(['?', '/', '\\'], "_", $streamConfiguration['file_name']);
            $headers    = $this->browser->getDownloadHeaders($media_name, $media->mime, false, (string)Core::get_filesize($stream_file));

            foreach ($headers as $headerName => $value) {
                header(sprintf('%s: %s', $headerName, $value));
            }

            $filepointer = fopen(Core::conv_lc_file($stream_file), 'rb');
            if (!is_resource($filepointer)) {
                $this->logger->error(
                    "Error: Unable to open " . $stream_file . " for downloading",
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                return null;
            }

            if (Core::get_server('REQUEST_METHOD') != 'HEAD') {
                if (!$share_id) {
                    $this->logger->debug(
                        'Registering download stats for {' . $media->get_stream_name() . '}...',
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                    Stats::insert($type, $media->id, $user_id, $agent, $location, 'download', $time);
                } else {
                    Stats::insert($type, $media->id, $user_id, 'share.php', [], 'download', $time);
                }
            }

            // Check to see if we should be throttling because we can get away with it
            if (AmpConfig::get('rate_limit') > 0) {
                while (!feof($filepointer)) {
                    echo fread($filepointer, (int)(round(AmpConfig::get('rate_limit', 8192) * 1024)));
                    flush();
                    sleep(1);
                }
            } else {
                fpassthru($filepointer);
            }

            fclose($filepointer);

            return null;
        } // if they are trying to download and they can

        // Prevent the script from timing out
        set_time_limit(0);

        $this->logger->debug(
            $action . ' file (' . $stream_file . '}...',
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );
        $this->logger->debug(
            'Media type {' . $streamConfiguration['file_type'] . '}',
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        if ($cpaction) {
            $this->logger->debug(
                'Custom play action {' . $cpaction . '}',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }
        // transcode_to should only have an effect if the media is the wrong format
        $transcode_to = ($transcode_cfg == 'never' || $transcode_to == $streamConfiguration['file_type'])
            ? null
            : $transcode_to;

        if ($transcode_to) {
            $this->logger->debug(
                'Transcode to {' . $transcode_to . '}',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }

        // If custom play action or already cached, do not try to transcode
        if (!$cpaction && !$original && !$cache_file) {
            $valid_types = $media->get_stream_types($player);
            if ($transcode_cfg != 'never' && in_array('transcode', $valid_types) && $type !== 'podcast_episode') {
                if ($transcode_to) {
                    $transcode = true;
                    $this->logger->debug(
                        'Transcoding due to explicit request for ' . $transcode_to,
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                } elseif ($transcode_cfg == 'always') {
                    $transcode = true;
                    $this->logger->debug(
                        'Transcoding due to always',
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                } else {
                    /** @var Song|Video $media */
                    $media_bitrate = floor($media->bitrate / 1024);
                    //$this->logger->debug("requested bitrate $bitrate <=> $media_bitrate ({$media->bitrate}) media bitrate", [LegacyLogger::CONTEXT_TYPE => self::class]);
                    if (($bitrate > 0 && $bitrate < $media_bitrate) || ($maxbitrate > 0 && $maxbitrate < $media_bitrate)) {
                        $transcode = true;
                        $this->logger->debug(
                            'Transcoding because explicit bitrate request',
                            [LegacyLogger::CONTEXT_TYPE => self::class]
                        );
                    } elseif (!in_array('native', $valid_types) && !$is_download) {
                        $transcode = true;
                        $this->logger->debug(
                            'Transcoding because native streaming is unavailable',
                            [LegacyLogger::CONTEXT_TYPE => self::class]
                        );
                    } elseif (!empty($subtitle)) {
                        $transcode = true;
                        $this->logger->debug(
                            'Transcoding because subtitle requested',
                            [LegacyLogger::CONTEXT_TYPE => self::class]
                        );
                    }
                }
            } elseif ($transcode_cfg != 'never') {
                $this->logger->notice(
                    'Transcoding is not enforced for ' . $streamConfiguration['file_type'],
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            } else {
                $this->logger->debug(
                    'Transcode disabled in user settings.',
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }
        }

        $troptions = [];
        if ($transcode) {
            $transcode_settings = $media->get_transcode_settings($transcode_to, $player, $troptions);
            if ($bitrate) {
                $troptions['bitrate'] = ($maxbitrate > 0 && $maxbitrate < $media_bitrate)
                    ? $maxbitrate
                    : $bitrate;
            }
            if ($maxbitrate > 0) {
                $troptions['maxbitrate'] = $maxbitrate;
            }
            if ($subtitle) {
                $troptions['subtitle'] = $subtitle;
            }
            if ($resolution) {
                $troptions['resolution'] = $resolution;
            }
            if ($quality) {
                $troptions['quality'] = $quality;
            }

            if (array_key_exists('frame', $_REQUEST)) {
                $troptions['frame'] = (float) $_REQUEST['frame'];
                if (array_key_exists('duration', $_REQUEST)) {
                    $troptions['duration'] = (float) $_REQUEST['duration'];
                }
            } elseif (array_key_exists('segment', $_REQUEST)) {
                // 10 seconds segment. Should it be an option?
                $ssize            = 10;
                $send_all_in_once = true; // Should we use temporary folder instead?
                $this->logger->debug(
                    'Sending all data in one piece.',
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
                $troptions['frame']    = (int)($_REQUEST['segment']) * $ssize;
                $troptions['duration'] = ($troptions['frame'] + $ssize <= $media->time)
                    ? $ssize
                    : ($media->time - $troptions['frame']);
            }

            $transcoder  = Stream::start_transcode($media, $transcode_settings, $troptions);
            $filepointer = $transcoder['handle'] ?? null;
            $media_name  = $media->get_artist_fullname() . " - " . $media->title . "." . ($transcoder['format'] ?? '');
        } elseif ($cpaction && $media instanceof Song) {
            $transcoder  = $media->run_custom_play_action((int)$cpaction, $transcode_to ?? '');
            $filepointer = $transcoder['handle'] ?? null;
            $transcode   = true;
        } else {
            $filepointer = fopen(Core::conv_lc_file($stream_file), 'rb');
        }

        //$this->logger->debug('troptions ' . print_r($troptions, true), [LegacyLogger::CONTEXT_TYPE => self::class]);
        if ($transcode) {
            if ($cache_file) {
                $stream_size = Core::get_filesize($stream_file);
            } else {
                // Content-length guessing if required by the player.
                // Otherwise it shouldn't be used as we are not really sure about final length when transcoding
                $transcode_settings = Stream::get_transcode_settings_for_media(
                    $streamConfiguration['file_type'],
                    $transcode_to,
                    $player,
                    $streamConfiguration['file_type'],
                    $troptions
                );
                $transcode_to = $transcode_settings['format'] ?? $format;

                // At this point, the bitrate has already been decided inside Stream::start_transcode
                // so we just try to emulate that logic here
                $stream_rate = 0;
                if (isset($troptions['bitrate'])) {
                    // note that the bitrate transcode option is stored as metric bits i.e. kilobits*1000 instead of kilobits*1024
                    $stream_rate = $troptions['bitrate'] / 1024;
                } elseif (!empty($transcode_settings)) {
                    $stream_rate = Stream::get_max_bitrate($media, $transcode_settings, $troptions);
                }

                // We always guess MP3 content length even when not required, since that codec calculates properly
                if ($this->requestParser->getFromRequest('content_length') == 'required' || $transcode_to == 'mp3') {
                    if ($media->time > 0 && $stream_rate > 0) {
                        $stream_size = (int)(($media->time * $stream_rate * 1024) / 8);
                    } else {
                        $this->logger->debug(
                            'Bad media duration / stream bitrate. Content-length calculation skipped.',
                            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                        );
                        $stream_size = 0;
                    }
                } else {
                    $stream_size = 0;
                }
            }
        } else {
            $stream_size = ($cache_file)
                ? Core::get_filesize($stream_file)
                : $streamConfiguration['file_size'];
        }

        if (!is_resource($filepointer)) {
            $this->logger->error(
                "Failed to open " . $stream_file . " for streaming",
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return null;
        }

        if (!$transcode) {
            header('ETag: ' . $media->id);
        }
        // Handle Content-Range

        $start        = 0;
        $end          = 0;
        $range_values = sscanf(Core::get_server('HTTP_RANGE'), "bytes=%d-%d", $start, $end);

        if (!$transcode && $range_values > 0 && ($start > 0 || $end > 0)) {
            // Calculate stream size from byte range
            if ($range_values >= 2) {
                $end = (int)min($end, $streamConfiguration['file_size'] - 1);
            } else {
                $end = $streamConfiguration['file_size'] - 1;
            }
            $stream_size = (int)($end - ((int)$start)) + 1;

            if ($stream_size === 0) {
                $this->logger->error(
                    'Content-Range header received, which we cannot fulfill due to unknown final length (transcoding?)',
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            } else {
                $this->logger->debug(
                    'Content-Range header received, skipping ' . $start . ' bytes out of ' . $streamConfiguration['file_size'],
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
                fseek($filepointer, (int)$start);

                $range = $start . '-' . $end . '/' . $streamConfiguration['file_size'];
                header('HTTP/1.1 206 Partial Content');
                header('Content-Range: bytes ' . $range);
            }
        }

        if (!isset($_REQUEST['segment'])) {
            if ($media->time) {
                header('X-Content-Duration: ' . $media->time);
            }

            // Stats registering must be done before play. Do not move it.
            // It can be slow because of scrobbler plugins (lastfm, ...)
            if ((int)$start > 0) {
                $this->logger->debug(
                    'Content-Range doesn\'t start from 0, stats should already be registered previously; not collecting stats',
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            } else {
                if (!$is_download && $record_stats) {
                    Stream::insert_now_playing($media->getId(), $user_id, (int) $media->time, $session_id, $media->getMediaType()->value);
                }
                if (Core::get_server('REQUEST_METHOD') != 'HEAD') {
                    if ($is_download) {
                        if (!$share_id) {
                            $this->logger->debug(
                                'Registering download stats for {' . $media->get_stream_name() . '}...',
                                [LegacyLogger::CONTEXT_TYPE => self::class]
                            );
                            Stats::insert($type, $media->id, $user_id, $agent, $location, 'download', $time);
                        } else {
                            Stats::insert($type, $media->id, $user_id, 'share.php', [], 'download', $time);
                        }
                    } elseif (!$share_id && $record_stats) {
                        $this->logger->notice(
                            'Registering stream @' . $time . ' for ' . $user_id . ': ' . $media->get_stream_name() . ' {' . $media->id . '}',
                            [LegacyLogger::CONTEXT_TYPE => self::class]
                        );
                        // internal scrobbling (user_activity and object_count tables)
                        if ($media->set_played($user_id, $agent, $location, $time) && $user->id && get_class($media) == Song::class) {
                            // scrobble plugins
                            User::save_mediaplay($user, $media);
                        }
                    } elseif ($share_id > 0) {
                        // shares are people too
                        $media->set_played(0, 'share.php', [], $time);
                    }
                }
            }
        }

        if ($transcode || $random || $demo_id) {
            header('Accept-Ranges: none');
        } else {
            header('Accept-Ranges: bytes');
        }

        if ($transcode && !empty($transcoder)) {
            $mime = ($type == 'video')
                ? Video::type_to_mime($transcoder['format'] ?? '')
                : Song::type_to_mime($transcoder['format'] ?? '');
            // Non-blocking stream doesn't work in Windows (php bug since 2005 and still here in 2020...)
            // We don't want to wait indefinitely for a potential error so we just ignore it.
            // https://bugs.php.net/bug.php?id=47918
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                // This to avoid hang, see http://php.net/manual/en/function.proc-open.php#89338
                $transcode_error = fread($transcoder['stderr'], 4096);
                if (!empty($transcode_error)) {
                    $this->logger->error(
                        'Transcode stderr: ' . $transcode_error,
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                }
                fclose($transcoder['stderr']);
            }
        } else {
            // output file might not be the same type
            $mime = ($type == 'video')
                ? Video::type_to_mime($streamConfiguration['file_type'])
                : Song::type_to_mime($streamConfiguration['file_type']);
        }

        // Close sql connection
        // Warning: do not call functions requiring sql after this point
        Dba::disconnect();
        // Free the session write lock
        // Warning: Do not change any session variable after this call
        session_write_close();

        $headers = $this->browser->getDownloadHeaders($media_name, $mime, false, (string)$stream_size);

        foreach ($headers as $headerName => $value) {
            header(sprintf('%s: %s', $headerName, $value));
        }

        $bytes_streamed = 0;

        // Actually do the streaming
        $buf_all = '';
        $r_arr   = [$filepointer];
        $w_arr   = $e_arr = [];
        $status  = stream_select($r_arr, $w_arr, $e_arr, null);
        if ($status === false) {
            $this->logger->error(
                'stream_select failed.',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            // close any leftover handle and processes
            fclose($filepointer);
            if ($transcode && !empty($transcoder)) {
                Stream::kill_process($transcoder);
            }

            return null;
        } elseif ($status > 0) {
            do {
                $read_size = ($transcode)
                    ? 2048
                    : min(2048, max(0, $stream_size - $bytes_streamed));

                if ($read_size === 0) {
                    break;
                }
                if ($buf = fread($filepointer, $read_size)) {
                    if ($send_all_in_once) {
                        $buf_all .= $buf;
                    } else {
                        echo $buf;
                        ob_flush();
                        flush();
                    }
                    $bytes_streamed += strlen($buf);
                }
            } while (
                !feof($filepointer) &&
                (
                    connection_status() == 0 &&
                    (
                        $transcode ||
                        $bytes_streamed < $stream_size
                    )
                )
            );
        }

        if ($send_all_in_once && connection_status() == 0) {
            header("Content-Length: " . strlen($buf_all));
            echo $buf_all;
            ob_flush();
            flush();
        }

        $real_bytes_streamed = $bytes_streamed;
        // Need to make sure enough bytes were sent.
        if ($bytes_streamed < $stream_size && (connection_status() == 0)) {
            // This stop's a client requesting the same content-range repeatedly
            print(str_repeat(' ', $stream_size - $bytes_streamed));
            $bytes_streamed = $stream_size;
        }

        // end output buffering
        ob_end_flush();

        // close any leftover handle and processes
        fclose($filepointer);
        if ($transcode && !empty($transcoder)) {
            Stream::kill_process($transcoder);
        }

        if ($bytes_streamed === 0 && $stream_size === 0) {
            http_response_code(416);
            $this->logger->debug(
                'Stream ended: No bytes left to stream',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return null;
        }

        $this->logger->debug(
            'Stream ended at ' . $bytes_streamed . ' (' . $real_bytes_streamed . ') bytes out of ' . $stream_size,
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        return null;
    }
}
