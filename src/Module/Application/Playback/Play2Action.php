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
 *
 */

declare(strict_types=0);

namespace Ampache\Module\Application\Playback;

use Ampache\Config\AmpConfig;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authentication\AuthenticationManagerInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Session;
use Ampache\Module\User\Tracking\UserTrackerInterface;
use Ampache\Module\Util\Horde_Browser;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
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

final class Play2Action implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'play2';

    private RequestParserInterface $requestParser;

    private Horde_Browser $browser;

    private AuthenticationManagerInterface $authenticationManager;

    private NetworkCheckerInterface $networkChecker;

    private UserRepositoryInterface $userRepository;

    private LoggerInterface $logger;

    private UserTrackerInterface $userTracker;

    public function __construct(
        RequestParserInterface $requestParser,
        Horde_Browser $browser,
        AuthenticationManagerInterface $authenticationManager,
        NetworkCheckerInterface $networkChecker,
        UserRepositoryInterface $userRepository,
        LoggerInterface $logger,
        UserTrackerInterface $userTracker
    ) {
        $this->requestParser         = $requestParser;
        $this->browser               = $browser;
        $this->authenticationManager = $authenticationManager;
        $this->networkChecker        = $networkChecker;
        $this->userRepository        = $userRepository;
        $this->logger                = $logger;
        $this->userTracker           = $userTracker;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        ob_end_clean();

        //$this->logger->debug(print_r(apache_request_headers(), true), [LegacyLogger::CONTEXT_TYPE => __CLASS__]);

        /**
         * The following code takes a "beautiful" url, splits it into key/value pairs and
         * then replaces the PHP $_REQUEST as if the URL had arrived in un-beautified form.
         * (This is necessary to avoid some DLNA players barfing on the URL, particularly Windows Media Player)
         *
         * The reason for not trying to do the whole job in mod_rewrite is that there are typically
         * more than 10 arguments to this function now, and that's tricky with mod_rewrite's 10 arg limit
         */
        $use_auth   = AmpConfig::get('use_auth');
        $slashcount = substr_count($_SERVER['QUERY_STRING'], '/');
        if ($slashcount > 2) {
            // e.g. ssid/3ca112fff23376ef7c74f018497dd39d/type/song/oid/280/uid/player/api/name/Glad.mp3
            $new_arr     = explode('/', $_SERVER['QUERY_STRING']);
            $new_request = array();
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
            $session_id   = scrub_in((string) ($new_request['ssid'] ?? ''));
            $type         = scrub_in((string) ($new_request['type'] ?? ''));
            $client       = scrub_in((string) ($new_request['client'] ?? ''));
            $cache        = scrub_in((string) ($new_request['cache'] ?? '1'));
            $bitrate      = (int)scrub_in((string) ($new_request['bitrate'] ?? 0));
            $player       = scrub_in((string) ($new_request['player'] ?? ''));
            $format       = scrub_in((string) ($new_request['format'] ?? ''));
            $original     = ($format == 'raw');
            $transcode_to = (!$original && $format != '')
                ? $format
                : scrub_in((string) ($new_request['transcode_to'] ?? ''));

            // Share id and secret if used
            $share_id = (int)scrub_in((string) ($new_request['share_id'] ?? 0));
            $secret   = scrub_in((string) ($new_request['share_secret'] ?? ''));

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
            $session_id   = scrub_in((string) filter_input(INPUT_GET, 'ssid', FILTER_SANITIZE_SPECIAL_CHARS));
            $type         = scrub_in((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS));
            $client       = scrub_in((string) filter_input(INPUT_GET, 'client', FILTER_SANITIZE_SPECIAL_CHARS));
            $cache        = scrub_in((string) filter_input(INPUT_GET, 'cache', FILTER_SANITIZE_NUMBER_INT));
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
            $cpaction = filter_input(INPUT_GET, 'custom_play_action', FILTER_SANITIZE_SPECIAL_CHARS);
        }
        $this->logger->debug('Called for action: {' . $this->requestParser->getFromRequest('action') . '}', [LegacyLogger::CONTEXT_TYPE => __CLASS__]);
        //$this->logger->debug('REQUEST: ' . print_r($_REQUEST, true), [LegacyLogger::CONTEXT_TYPE => __CLASS__]);
        //$debug = array(
        //    'action' => $action,
        //    'name' => $stream_name,
        //    'oid' => $object_id,
        //    'uid' => $user_id,
        //    'ssid' => $session_id,
        //    'type' => $type,
        //    'client' => $client,
        //    'cache' => $cache,
        //    'bitrate' => $bitrate,
        //    'player' => $player,
        //    'format' => $format,
        //    'original' => $original,
        //    'transcode_to' => $transcode_to,
        //    'share_id' => $share_id,
        //    'share_secret' => $secret,
        //    'demo_id' => $demo_id,
        //    'random' => $random
        //);
        //foreach ($debug as $key => $value) {
        //    $this->logger->debug($key . ': ' . $value, [LegacyLogger::CONTEXT_TYPE => __CLASS__]);
        //}

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
        if ($cache == '1') {
            $action = 'download';
        }
        $record_stats = true;
        // allow disabling stat recording from the play url
        if ($action == 'download' && !in_array($type, array('song', 'video', 'podcast_episode'))) {
            $this->logger->debug(
                'record_stats disabled: cache {' . $type . "}",
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            $record_stats = false;
        }
        $is_download   = ($action == 'download');
        $maxbitrate    = 0;
        $media_bitrate = 0;
        $quality       = 0;
        $resolution    = '';
        $subtitle      = '';
        $time          = time();

        if (AmpConfig::get('transcode_player_customize') && !$original) {
            // Trick to avoid LimitInternalRecursion reconfiguration
            $vsettings = scrub_in((string) filter_input(INPUT_GET, 'transcode_to', FILTER_SANITIZE_SPECIAL_CHARS));
            if (!empty($vsettings)) {
                $vparts  = explode('-', $vsettings);
                $v_count = count($vparts);
                for ($i = 0; $i < $v_count; $i += 2) {
                    switch ($vparts[$i]) {
                        case 'maxbitrate':
                            $maxbitrate = (int) ($vparts[$i + 1]);
                            break;
                        case 'resolution':
                            $resolution = $vparts[$i + 1];
                            break;
                        case 'quality':
                            $quality = (int) ($vparts[$i + 1]);
                            break;
                    }
                }
            }
        }

        if (!$type) {
            $type = 'song';
        }

        $this->logger->debug(
            "Asked for type {" . $type . "}",
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        if ($type == 'playlist') {
            $playlist_type = $this->requestParser->getFromRequest('playlist_type');
            $object_id     = $session_id;
        }

        // First things first, if we don't have a uid/oid stop here
        if (empty($object_id) && (!$demo_id && !$share_id && !$secret && !$random)) {
            $this->logger->error(
                'No object OID specified, nothing to play',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            header('HTTP/1.1 400 Nothing To Play');

            return null;
        }

        // Authenticate the user if specified
        $username = $this->requestParser->getFromRequest('PHP_AUTH_USER');
        if (empty($username)) {
            $username = $this->requestParser->getFromRequest('u');
        }
        $password = $this->requestParser->getFromRequest('PHP_AUTH_PW');
        if (empty($password)) {
            $password = $this->requestParser->getFromRequest('p');
        }
        $apikey    = $this->requestParser->getFromRequest('apikey');
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
                if (!Session::exists('stream', $session_id)) {
                    Session::create(
                        array(
                            'sid' => $session_id,
                            'username' => $user->username,
                            'value' => '',
                            'type' => 'stream',
                            'agent' => ''
                        )
                    );
                } else {
                    Session::update_agent($session_id, $agent);
                    Session::extend($session_id, 'stream');
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

        $session_name = AmpConfig::get('session_name');
        // Identify the user according to it's web session
        // We try to avoid the generic 'Ampache User' as much as possible
        if (!($user instanceof User) && array_key_exists($session_name, $_COOKIE) && Session::exists('interface', $_COOKIE[$session_name])) {
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
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
                header('HTTP/1.1 403 User disabled');

                return null;
            }

            // If require_session is set then we need to make sure we're legit
            if (!$user_auth && $use_auth && AmpConfig::get('require_session')) {
                if (!AmpConfig::get('require_localnet_session') && $this->networkChecker->check(AccessLevelEnum::TYPE_NETWORK, Core::get_global('user')->id, AccessLevelEnum::LEVEL_GUEST)) {
                    $this->logger->notice(
                        'Streaming access allowed for local network IP ' . filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP),
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                } elseif (!Session::exists('stream', $session_id)) {
                    // No valid session id given, try with cookie session from web interface
                    $session_id = $_COOKIE[$session_name] ?? false;
                    if ($session_id === false || !Session::exists('interface', $session_id)) {
                        $this->logger->warning(
                            "Streaming access denied: Session $session_id has expired",
                            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                        );
                        header('HTTP/1.1 403 Session Expired');

                        return null;
                    }
                }
                // Now that we've confirmed the session is valid extend it
                Session::extend($session_id, 'stream');
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

            if (!$share->is_shared_media($object_id)) {
                header('HTTP/1.1 403 Access Unauthorized');

                return null;
            }

            $user = new User($share->user);
        }

        if ((!$user instanceof User || $user->id < 1) && (!$share_id && !$secret)) {
            $this->logger->error(
                'No user specified {' . print_r($user, true) . '}',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
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
                !$this->networkChecker->check(AccessLevelEnum::TYPE_STREAM, Core::get_global('user')->id) &&
                !$this->networkChecker->check(AccessLevelEnum::TYPE_NETWORK, Core::get_global('user')->id)
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
                $democratic->delete_from_oid($object_id, $type);

                // If the media is disabled
                if ((isset($media->enabled) && !make_bool($media->enabled)) || !Core::is_readable(Core::conv_lc_file((string)$media->file))) {
                    $this->logger->warning(
                        "Error: " . $media->file . " is currently disabled, song skipped",
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
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
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            header('HTTP/1.1 404 File not found');

            return null;
        } // if democratic ID passed

        /**
         * if we are doing random let's pull the random object and redirect to that media files URL
         */
        if ($random === 1) {
            $last_id   = (int)(User::get_user_data($user_id, 'random_song')['random_song'] ?? 0);
            $last_time = (int)(User::get_user_data($user_id, 'random_time')['random_time'] ?? 0);
            if ($last_id > 0 && $last_time >= $time) {
                // continue the current object
                $object_id = $last_id;
                $this->logger->debug(
                    'Called random again too quickly sending last song id: {' . $object_id . '}',
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
            } else {
                // get a new random object and redirect to that object
                if (array_key_exists('random_type', $_REQUEST)) {
                    $rtype = $this->requestParser->getFromRequest('random_type');
                } else {
                    $rtype = $type;
                }
                $object_id = Random::get_single_song($rtype, $user, (int)$this->requestParser->getFromRequest('random_id'));
            }
            $media = new Song($object_id);
            if ($media->id > 0) {
                // If the media is disabled
                if ((isset($media->enabled) && !make_bool($media->enabled)) || !Core::is_readable(Core::conv_lc_file((string)$media->file))) {
                    $this->logger->warning(
                        "Error: " . $media->file . " is currently disabled, song skipped",
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
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
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            header('HTTP/1.1 404 File not found');

            return null;
        } // if random

        if ($type == 'video') {
            $media = new Video((int) $object_id);
            if (array_key_exists('subtitle', $_REQUEST)) {
                $subtitle = $media->get_subtitle_file($this->requestParser->getFromRequest('subtitle'));
            }
        } elseif ($type == 'song_preview') {
            $media = new Song_Preview((int) $object_id);
        } elseif ($type == 'podcast_episode') {
            $media = new Podcast_Episode((int) $object_id);
        } else {
            // default to song
            $media = new Song((int) $object_id);
        }
        $media->format();

        if (!User::stream_control(array(array('object_type' => $type, 'object_id' => $media->id)))) {
            throw new AccessDeniedException(
                sprintf(
                    'Stream control failed for user %s on %s',
                    Core::get_global('user')->username,
                    $media->get_stream_name()
                )
            );
        }

        $transcode     = false;
        $transcode_cfg = AmpConfig::get('transcode');
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
            if (!Catalog::has_access($mediaCatalogId, $user->id) && ($mediaOwnerId && (int)$mediaOwnerId !== $user->id)) {
                $this->logger->warning(
                    "Error: You are not allowed to play $media->file",
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );

                return null;
            }
            // If we are running in Legalize mode, don't play medias already playing
            if (AmpConfig::get('lock_songs')) {
                if (!Stream::check_lock_media($media->id, $type)) {
                    return null;
                }
            }
            $cache_path   = (string)AmpConfig::get('cache_path', '');
            $cache_target = (string)AmpConfig::get('cache_target', '');
            $file_target  = Catalog::get_cache_path($media->id, $mediaCatalogId, $cache_path, $cache_target);
            if ($transcode_cfg != 'never' && !$is_download && ($file_target && is_file($file_target))) {
                $this->logger->debug(
                    'Found pre-cached file {' . $file_target . '}',
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
                $cache_file   = true;
                $original     = true;
                $media->file  = $file_target;
                $media->size  = Core::get_filesize($file_target);
                $media->type  = $cache_target;
                $transcode_to = null;
            } else {
                // Build up the catalog for our current object
                $catalog = Catalog::create_from_id($mediaCatalogId);
                if (!$catalog instanceof Catalog) {
                    return null;
                }
                $media = $catalog->prepare_media($media);
                // Subsonic and remote catalogs redirect you to the remote url so stop here
                if ($media == null) {
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
            : $media->file;

        /* If we don't have a file, or the file is not readable */
        if (!$stream_file || !Core::is_readable(Core::conv_lc_file((string)$stream_file))) {
            $this->logger->error(
                "Media " . $stream_file . " ($media->title) does not have a valid filename specified",
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            header('HTTP/1.1 404 Invalid media, file not found or file unreadable');

            return null;
        }

        // don't abort the script if user skips this media because we need to update now_playing
        ignore_user_abort(true);

        // Format the media name
        $media_name = (!empty($stream_name))
            ? $stream_name
            : $media->get_stream_name() . "." . $media->type;
        $transcode_to = ($transcode_cfg == 'never' || $cache_file || ($is_download && !$transcode_to))
            ? null
            : Stream::get_transcode_format((string)$media->type, $transcode_to, $player, $type);

        header('Access-Control-Allow-Origin: *');

        $sessionkey = $session_id ?? Stream::get_session();
        $agent      = (!empty($client))
            ? $client
            : Session::agent($sessionkey);
        $location = Session::get_geolocation($sessionkey);

        // If they are just trying to download make sure they have rights and then present them with the download file
        if ($is_download && !$transcode_to) {
            $this->logger->notice(
                'Downloading raw file...',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            // STUPID IE
            $media_name = str_replace(array('?', '/', '\\'), "_", $media->f_file);
            $headers    = $this->browser->getDownloadHeaders($media_name, $media->mime, false, (string)$media->size);

            foreach ($headers as $headerName => $value) {
                header(sprintf('%s: %s', $headerName, $value));
            }

            $filepointer = fopen(Core::conv_lc_file($stream_file), 'rb');
            if (!is_resource($filepointer)) {
                $this->logger->error(
                    "Error: Unable to open " . $stream_file . " for downloading",
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );

                return null;
            }

            if (Core::get_server('REQUEST_METHOD') != 'HEAD') {
                if (!$share_id) {
                    $this->logger->debug(
                        'Registering download stats for {' . $media->get_stream_name() . '}...',
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                    Stats::insert($type, $media->id, $user_id, $agent, $location, 'download', $time);
                } else {
                    Stats::insert($type, $media->id, $user_id, 'share.php', array(), 'download', $time);
                }
            }

            // Check to see if we should be throttling because we can get away with it
            if (AmpConfig::get('rate_limit') > 0) {
                while (!feof($filepointer)) {
                    echo fread($filepointer, (int) (round(AmpConfig::get('rate_limit') * 1024)));
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

        // We're about to start. Record this user's IP.
        if (AmpConfig::get('track_user_ip') && !empty(Core::get_global('user'))) {
            $this->userTracker->trackIpAddress(Core::get_global('user'));
        }

        $this->logger->debug(
            $action . ' file (' . $stream_file . '}...',
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );
        $this->logger->debug(
            'Media type {' . $media->type . '}',
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        if ($cpaction) {
            $this->logger->debug(
                'Custom play action {' . $cpaction . '}',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
        }
        // transcode_to should only have an effect if the media is the wrong format
        $transcode_to = ($transcode_cfg == 'never' || $transcode_to == $media->type)
            ? null
            : $transcode_to;

        if ($transcode_to) {
            $this->logger->debug(
                'Transcode to {' . (string) $transcode_to . '}',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
        }

        // If custom play action or already cached, do not try to transcode
        if (!$cpaction && !$original && !$cache_file) {
            $valid_types = $media->get_stream_types($player);
            if ($transcode_cfg != 'never' && in_array('transcode', $valid_types) && $type !== 'podcast_episode') {
                if ($transcode_to) {
                    $transcode = true;
                    $this->logger->debug(
                        'Transcoding due to explicit request for ' . (string) $transcode_to,
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                } elseif ($transcode_cfg == 'always') {
                    $transcode = true;
                    $this->logger->debug(
                        'Transcoding due to always',
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                } else {
                    /** @var Song|Video $media */
                    $media_bitrate = floor($media->bitrate / 1024);
                    //$this->logger->debug("requested bitrate $bitrate <=> $media_bitrate ({$media->bitrate}) media bitrate", [LegacyLogger::CONTEXT_TYPE => __CLASS__]);
                    if (($bitrate > 0 && $bitrate < $media_bitrate) || ($maxbitrate > 0 && $maxbitrate < $media_bitrate)) {
                        $transcode = true;
                        $this->logger->debug(
                            'Transcoding because explicit bitrate request',
                            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                        );
                    } elseif (!in_array('native', $valid_types) && !$is_download) {
                        $transcode = true;
                        $this->logger->debug(
                            'Transcoding because native streaming is unavailable',
                            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                        );
                    } elseif (!empty($subtitle)) {
                        $transcode = true;
                        $this->logger->debug(
                            'Transcoding because subtitle requested',
                            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                        );
                    }
                }
            } else {
                if ($transcode_cfg != 'never') {
                    $this->logger->notice(
                        'Transcoding is not enforced for ' . $media->type,
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                } else {
                    $this->logger->debug(
                        'Transcode disabled in user settings.',
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                }
            }
        }

        $transcode_settings = array();
        $troptions          = array();
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
                $troptions['frame'] = (float) $this->requestParser->getFromRequest('frame');
                if (array_key_exists('duration', $_REQUEST)) {
                    $troptions['duration'] = (float) $this->requestParser->getFromRequest('duration');
                }
            } elseif (array_key_exists('segment', $_REQUEST)) {
                // 10 seconds segment. Should it be an option?
                $ssize = 10;
                $this->logger->debug(
                    'Sending all data in one piece.',
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
                $troptions['frame']    = (int) ($this->requestParser->getFromRequest('segment')) * $ssize;
                $troptions['duration'] = ($troptions['frame'] + $ssize <= $media->time)
                    ? $ssize
                    : ($media->time - $troptions['frame']);
            }

            $transcoder  = Stream::start_transcode($media, $transcode_settings, $troptions);
            $filepointer = $transcoder['handle'] ?? null;
            $media_name  = $media->f_artist_full . " - " . $media->title . "." . ($transcoder['format'] ?? '');
        } else {
            if ($cpaction && $media instanceof Song) {
                $transcoder  = $media->run_custom_play_action($cpaction, $transcode_to ?? '');
                $filepointer = $transcoder['handle'] ?? null;
                $transcode   = true;
            } else {
                $filepointer = fopen(Core::conv_lc_file($stream_file), 'rb');
            }
        }
        //$this->logger->debug('troptions ' . print_r($troptions, true), [LegacyLogger::CONTEXT_TYPE => __CLASS__]);
        if ($transcode) {
            $maxbitrate = (empty($transcode_settings))
                ? $media->bitrate / 1024
                : Stream::get_max_bitrate($media, $transcode_settings);
            if ($media->time > 0 && $maxbitrate > 0) {
                $stream_size = (int)(($media->time * $maxbitrate * 1024) / 8);
            } else {
                $this->logger->debug(
                    'Bad media duration / Max bitrate. Content-length calculation skipped.',
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
                $stream_size = 0;
            }
        } else {
            $stream_size = (int)$media->size;
        }

        if (!is_resource($filepointer)) {
            $this->logger->error(
                "Failed to open " . $stream_file . " for streaming",
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
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

        if ($range_values > 0 && ($start > 0 || $end > 0)) {
            // Calculate stream size from byte range
            if ($range_values >= 2) {
                $end = (int)min($end, $media->size - 1);
            } else {
                $end = $media->size - 1;
            }
            $stream_size = (int)($end - ((int)$start)) + 1;

            if ($stream_size === 0) {
                $this->logger->error(
                    'Content-Range header received, which we cannot fulfill due to unknown final length (transcoding?)',
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
            } elseif (!$transcode) {
                $this->logger->debug(
                    'Content-Range header received, skipping ' . $start . ' bytes out of ' . $media->size,
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
                fseek($filepointer, (int)$start);

                $range = $start . '-' . $end . '/' . $media->size;
                header('HTTP/1.1 206 Partial Content');
                header('Content-Range: bytes ' . $range);
            }
        }

        if (empty($this->requestParser->getFromRequest('segment'))) {
            if ($media->time) {
                header('X-Content-Duration: ' . $media->time);
            }

            // Stats registering must be done before play. Do not move it.
            // It can be slow because of scrobbler plugins (lastfm, ...)
            if ((int)$start > 0) {
                $this->logger->debug(
                    'Content-Range doesn\'t start from 0, stats should already be registered previously; not collecting stats',
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
            } else {
                if (!$is_download && $record_stats) {
                    Stream::insert_now_playing((int) $media->id, $user_id, (int) $media->time, $session_id, ObjectTypeToClassNameMapper::reverseMap(get_class($media)));
                }
                if (Core::get_server('REQUEST_METHOD') != 'HEAD') {
                    if ($is_download) {
                        if (!$share_id) {
                            $this->logger->debug(
                                'Registering download stats for {' . $media->get_stream_name() . '}...',
                                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                            );
                            Stats::insert($type, $media->id, $user_id, $agent, $location, 'download', $time);
                        } else {
                            Stats::insert($type, $media->id, $user_id, 'share.php', array(), 'download', $time);
                        }
                    } elseif (!$share_id && $record_stats) {
                        $this->logger->notice(
                            'Registering stream @' . $time . ' for ' . $user_id . ': ' . $media->get_stream_name() . ' {' . $media->id . '}',
                            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                        );
                        // internal scrobbling (user_activity and object_count tables)
                        if ($media->set_played($user_id, $agent, $location, $time) && $user->id && get_class($media) == Song::class) {
                            // scrobble plugins
                            User::save_mediaplay($user, $media);
                        }
                    } elseif ($share_id > 0) {
                        // shares are people too
                        $media->set_played(0, 'share.php', array(), $time);
                    }
                }
            }
        }

        if ($random || $demo_id) {
            header('Accept-Ranges: none');
        } else {
            header('Accept-Ranges: bytes');
        }

        if ($transcode && isset($transcoder)) {
            $mime = ($type === 'video')
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
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                }
                fclose($transcoder['stderr']);
            }
        } else {
            // output file might not be the same type
            $mime = ($type === 'video')
                ? Video::type_to_mime($media->type)
                : Song::type_to_mime($media->type);
        }

        // Close sql connection
        // Warning: do not call functions requiring sql after this point
        Dba::disconnect();
        // Free the session write lock
        // Warning: Do not change any session variable after this call
        session_write_close();

        // Actually do the streaming
        if (!$transcode) {
            $headers = $this->browser->getDownloadHeaders($media_name, $mime, false, (string)$stream_size);
            foreach ($headers as $headerName => $value) {
                header(sprintf('%s: %s', $headerName, $value));
            }
        }
        $bytes_streamed = 0;
        $buf_all        = '';
        $r_arr          = array($filepointer);
        $w_arr          = $e_arr = array();
        $status         = stream_select($r_arr, $w_arr, $e_arr, 2);
        if ($status === false) {
            $this->logger->error(
                'stream_select failed.',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            // close any leftover handle and processes
            fclose($filepointer);
            if ($transcode && isset($transcoder)) {
                Stream::kill_process($transcoder);
            }

            return null;
        } elseif ($status > 0) {
            do {
                $read_size = $transcode
                    ? 2048
                    : min(2048, max(0, $stream_size - $bytes_streamed));

                if ($buf = fread($filepointer, $read_size)) {
                    if ($transcode) {
                        $buf_all .= $buf;
                    } else {
                        echo $buf;
                        ob_flush();
                        flush();
                    }

                    $bytes_streamed += strlen($buf);
                }
            } while (!feof($filepointer) && (connection_status() == 0 && ($transcode || $bytes_streamed < $stream_size)));
        }

        if ($transcode && connection_status() == 0) {
            $headers = $this->browser->getDownloadHeaders($media_name, $mime, false, (string)strlen($buf_all));
            foreach ($headers as $headerName => $value) {
                header(sprintf('%s: %s', $headerName, $value));
            }
            echo $buf_all;
            ob_flush();
            flush();
        }
        // end output buffering
        ob_end_flush();

        // Need to make sure enough bytes were sent.
        if ($bytes_streamed < $stream_size && (connection_status() == 0)) {
            // This stop's a client requesting the same content-range repeatedly
            print(str_repeat(' ', $stream_size - $bytes_streamed));
        }

        // close any leftover handle and processes
        fclose($filepointer);
        if ($transcode && isset($transcoder)) {
            Stream::kill_process($transcoder);
        }

        $this->logger->debug(
            'Stream ended at ' . $bytes_streamed . ' bytes out of ' . $stream_size,
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        return null;
    }
}
