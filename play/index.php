<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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

/*

 This is the wrapper for opening music streams from this server.  This script
   will play the local version or redirect to the remote server if that be
   the case.  Also this will update local statistics for songs as well.
   This is also where it decides if you need to be downsampled.
*/
define('NO_SESSION','1');
require_once '../lib/init.php';
ob_end_clean();

//debug_event('play', print_r(apache_request_headers(), true), 5);

/* These parameters had better come in on the url. */
$uid            = scrub_in($_REQUEST['uid']);
$oid            = scrub_in($_REQUEST['oid']);
$sid            = scrub_in($_REQUEST['ssid']);
$type           = scrub_in($_REQUEST['type']);

$transcode_to = null;
$player       = null;
$bitrate      = 0;
$maxbitrate   = 0;
$resolution   = '';
$quality      = 0;

if (isset($_REQUEST['player'])) {
    $player = $_REQUEST['player'];
}

if (AmpConfig::get('transcode_player_customize')) {
    $transcode_to = scrub_in($_REQUEST['transcode_to']);
    $bitrate      = intval($_REQUEST['bitrate']);

    // Trick to avoid LimitInternalRecursion reconfiguration
    $vsettings = $_REQUEST['vsettings'];
    if ($vsettings) {
        $vparts = explode('-', $vsettings);
        for ($i = 0; $i < count($vparts); $i += 2) {
            switch ($vparts[$i]) {
                case 'maxbitrate':
                    $maxbitrate = intval($vparts[$i + 1]);
                    break;
                case 'resolution':
                    $resolution = $vparts[$i + 1];
                    break;
                case 'quality':
                    $quality = intval($vparts[$i + 1]);
                    break;
            }
        }
    }
}
$share_id         = intval($_REQUEST['share_id']);
$subtitle         = '';
$send_all_in_once = false;

if (!$type) {
    $type = 'song';
}

debug_event('play', 'Asked for type {' . $type . "}", 5);

if ($type == 'playlist') {
    $playlist_type = scrub_in($_REQUEST['playlist_type']);
    $oid           = $sid;
}

/* This is specifically for tmp playlist requests */
$demo_id    = Dba::escape($_REQUEST['demo_id']);
$random     = Dba::escape($_REQUEST['random']);

/* First things first, if we don't have a uid/oid stop here */
if (empty($oid) && empty($demo_id) && empty($random)) {
    debug_event('play', 'No object UID specified, nothing to play', 2);
    header('HTTP/1.1 400 Nothing To Play');
    exit;
}

// Authenticate the user if specified
$u = $_SERVER['PHP_AUTH_USER'];
if (empty($u)) {
    $u = $_REQUEST['u'];
}
$p = $_SERVER['PHP_AUTH_PW'];
if (empty($p)) {
    $p = $_REQUEST['p'];
}
$apikey = $_REQUEST['apikey'];

// If explicit user authentification was passed
$user_authenticated = false;
if (!empty($apikey)) {
    $user = User::get_from_apikey($apikey);
    if ($user != null) {
        $GLOBALS['user'] = $user;
        $uid             = $GLOBALS['user']->id;
        Preference::init();
        $user_authenticated = true;
    }
} elseif (!empty($u) && !empty($p)) {
    $auth = Auth::login($u, $p);
    if ($auth['success']) {
        $GLOBALS['user'] = User::get_from_username($auth['username']);
        $uid             = $GLOBALS['user']->id;
        Preference::init();
        $user_authenticated = true;
    }
}

if (empty($uid)) {
    debug_event('play', 'No user specified', 2);
    header('HTTP/1.1 400 No User Specified');
    exit;
} elseif ($uid == '-1' && AmpConfig::get('use_auth')) {
    // Identify the user according to it's web session
    // We try to avoid the generic 'Ampache User' as much as possible
    if (Session::exists('interface', $_COOKIE[AmpConfig::get('session_name')])) {
        Session::check();
        $user = User::get_from_username($_SESSION['userdata']['username']);
        $uid  = $user->id;
    }
}

if (!$share_id) {
    // No explicit authentication, use session
    if (!$user_authenticated) {
        $GLOBALS['user'] = new User($uid);
        Preference::init();

        /* If the user has been disabled (true value) */
        if (make_bool($GLOBALS['user']->disabled)) {
            debug_event('UI::access_denied', $GLOBALS['user']->username . " is currently disabled, stream access denied", '3');
            header('HTTP/1.1 403 User Disabled');
            exit;
        }

        // If require session is set then we need to make sure we're legit
        if (AmpConfig::get('use_auth') && AmpConfig::get('require_session')) {
            if (!AmpConfig::get('require_localnet_session') and Access::check_network('network',$GLOBALS['user']->id,'5')) {
                debug_event('play', 'Streaming access allowed for local network IP ' . $_SERVER['REMOTE_ADDR'],'5');
            } else {
                if (!Session::exists('stream', $sid)) {
                    // No valid session id given, try with cookie session from web interface
                $sid = $_COOKIE[AmpConfig::get('session_name')];
                    if (!Session::exists('interface', $sid)) {
                        debug_event('UI::access_denied', 'Streaming access denied: ' . $GLOBALS['user']->username . "'s session has expired", 3);
                        header('HTTP/1.1 403 Session Expired');
                        exit;
                    }
                }
            }

            // Now that we've confirmed the session is valid
            // extend it
            Session::extend($sid, 'stream');
        }
    }

    /* Update the users last seen information */
    $GLOBALS['user']->update_last_seen();
} else {
    $secret = $_REQUEST['share_secret'];
    $share  = new Share($share_id);

    if (!$share->is_valid($secret, 'stream')) {
        header('HTTP/1.1 403 Access Unauthorized');
        exit;
    }

    if (!$share->is_shared_media($oid)) {
        header('HTTP/1.1 403 Access Unauthorized');
        exit;
    }

    $GLOBALS['user'] = new User($share->user);
    Preference::init();
}

/* If we are in demo mode.. die here */
if (AmpConfig::get('demo_mode') || (!Access::check('interface','25') )) {
    debug_event('UI::access_denied', "Streaming Access Denied:" . AmpConfig::get('demo_mode') . "is the value of demo_mode. Current user level is " . $GLOBALS['user']->access,'3');
    UI::access_denied();
    exit;
}

/*
   If they are using access lists let's make sure
   that they have enough access to play this mojo
*/
if (AmpConfig::get('access_control')) {
    if (!Access::check_network('stream',$GLOBALS['user']->id,'25') and
        !Access::check_network('network',$GLOBALS['user']->id,'25')) {
        debug_event('UI::access_denied', "Streaming Access Denied: " . $_SERVER['REMOTE_ADDR'] . " does not have stream level access",'3');
        UI::access_denied();
        exit;
    }
} // access_control is enabled

// Handle playlist downloads
if ($type == 'playlist' && isset($playlist_type)) {
    $playlist = new Stream_Playlist($oid);
    // Some rudimentary security
    if ($uid != $playlist->user) {
        UI::access_denied();
        exit;
    }
    $playlist->generate_playlist($playlist_type, false);
    exit;
}

/**
 * If we've got a tmp playlist then get the
 * current song, and do any other crazyness
 * we need to
 */
if ($demo_id) {
    $democratic = new Democratic($demo_id);
    $democratic->set_parent();

    // If there is a cooldown we need to make sure this song isn't a repeat
    if (!$democratic->cooldown) {
        /* This takes into account votes etc and removes the */
        $oid = $democratic->get_next_object();
    } else {
        // Pull history
        $song_cool_check = 0;
        $oid             = $democratic->get_next_object($song_cool_check);
        $oids            = $democratic->get_cool_songs();
        while (in_array($oid,$oids)) {
            $song_cool_check++;
            $oid = $democratic->get_next_object($song_cool_check);
            if ($song_cool_check >= '5') {
                break;
            }
        } // while we've got the 'new' song in old the array
    } // end if we've got a cooldown
} // if democratic ID passed

/**
 * if we are doing random let's pull the random object
 */
if ($random) {
    if ($_REQUEST['start'] < 1) {
        if (isset($_REQUEST['random_type'])) {
            $rtype = $_REQUEST['random_type'];
        } else {
            $rtype = $type;
        }
        $oid = Random::get_single_song($rtype);
        if ($oid) {
            // Save this one in case we do a seek
            $_SESSION['random']['last'] = $oid;
        }
    } else {
        $oid = $_SESSION['random']['last'];
    }
} // if random

if ($type == 'song') {
    /* Base Checks passed create the song object */
    $media = new Song($oid);
    $media->format();
} elseif ($type == 'song_preview') {
    $media = new Song_Preview($oid);
    $media->format();
} elseif ($type == 'podcast_episode') {
    $media = new Podcast_Episode($oid);
    $media->format();
} else {
    $type  = 'video';
    $media = new Video($oid);
    if (isset($_REQUEST['subtitle'])) {
        $subtitle = $media->get_subtitle_file($_REQUEST['subtitle']);
    }
    $media->format();
}

if (!User::stream_control(array(array('object_type' => $type, 'object_id' => $media->id)))) {
    debug_event('UI::access_denied', 'Stream control failed for user ' . $GLOBALS['user']->username . ' on ' . $media->get_stream_name(), 3);
    UI::access_denied();
    exit;
}

if ($media->catalog) {
    // Build up the catalog for our current object
    $catalog = Catalog::create_from_id($media->catalog);

    /* If the media is disabled */
    if (isset($media->enabled) && !make_bool($media->enabled)) {
        debug_event('Play', "Error: $media->file is currently disabled, song skipped", '5');
        // Check to see if this is a democratic playlist, if so remove it completely
        if ($demo_id && isset($democratic)) {
            $democratic->delete_from_oid($oid, $type);
        }
        header('HTTP/1.1 404 File Disabled');
        exit;
    }

    // If we are running in Legalize mode, don't play medias already playing
    if (AmpConfig::get('lock_songs')) {
        if (!Stream::check_lock_media($media->id, $type)) {
            exit;
        }
    }

    $media = $catalog->prepare_media($media);
} else {
    // No catalog, must be song preview or something like that => just redirect to file

    if ($type == "song_preview") {
        $media->stream();
    } else {
        header('Location: ' . $media->file);
        exit;
    }
}
if ($media == null) {
    // Handle democratic removal
    if ($demo_id && isset($democratic)) {
        $democratic->delete_from_oid($oid, $type);
    }
    exit;
}

/* If we don't have a file, or the file is not readable */
if (!$media->file || !Core::is_readable(Core::conv_lc_file($media->file))) {

    // We need to make sure this isn't democratic play, if it is then remove
    // the media from the vote list
    if (is_object($tmp_playlist)) {
        $tmp_playlist->delete_track($oid);
    }
    // FIXME: why are these separate?
    // Remove the media votes if this is a democratic song
    if ($demo_id && isset($democratic)) {
        $democratic->delete_from_oid($oid, $type);
    }

    debug_event('play', "Media $media->file ($media->title) does not have a valid filename specified", 2);
    header('HTTP/1.1 404 Invalid media, file not found or file unreadable');
    exit;
}

// don't abort the script if user skips this media because we need to update now_playing
ignore_user_abort(true);

// Format the media name
$media_name = $media->get_stream_name() . "." . $media->type;

header('Access-Control-Allow-Origin: *');

// Generate browser class for sending headers
$browser = new Horde_Browser();

/* If they are just trying to download make sure they have rights
 * and then present them with the download file
 */
if ($_GET['action'] == 'download' and AmpConfig::get('download')) {
    debug_event('play', 'Downloading file...', 5);
    // STUPID IE
    $media_name = str_replace(array('?','/','\\'),"_",$media->f_file);

    $browser->downloadHeaders($media_name,$media->mime,false,$media->size);
    $fp            = fopen(Core::conv_lc_file($media->file), 'rb');
    $bytesStreamed = 0;

    if (!is_resource($fp)) {
        debug_event('Play',"Error: Unable to open $media->file for downloading",'2');
        exit();
    }

    if (!$share_id) {
        if ($_SERVER['REQUEST_METHOD'] != 'HEAD') {
            debug_event('play', 'Registering download stats for {' . $media->get_stream_name() . '}...', '5');
            $sessionkey = $sid ?: Stream::get_session();
            $agent      = Session::agent($sessionkey);
            $location   = Session::get_geolocation($sessionkey);
            Stats::insert($type, $media->id, $uid, $agent, $location, 'download');
        }
    }

    // Check to see if we should be throttling because we can get away with it
    if (AmpConfig::get('rate_limit') > 0) {
        while (!feof($fp)) {
            echo fread($fp,round(AmpConfig::get('rate_limit')*1024));
            $bytesStreamed += round(AmpConfig::get('rate_limit')*1024);
            flush();
            sleep(1);
        }
    } else {
        fpassthru($fp);
    }

    fclose($fp);
    exit();
} // if they are trying to download and they can

// Prevent the script from timing out
set_time_limit(0);

// We're about to start. Record this user's IP.
if (AmpConfig::get('track_user_ip')) {
    $GLOBALS['user']->insert_ip_history();
}

$force_downsample = false;
if (AmpConfig::get('downsample_remote')) {
    if (!Access::check_network('network', $GLOBALS['user']->id, '0')) {
        debug_event('play', 'Downsampling enabled for non-local address ' . $_SERVER['REMOTE_ADDR'], 5);
        $force_downsample = true;
    }
}

debug_event('play', 'Playing file (' . $media->file . '}...', 5);
debug_event('play', 'Media type {' . $media->type . '}', 5);

$cpaction = $_REQUEST['custom_play_action'];
// Determine whether to transcode
$transcode = false;
// transcode_to should only have an effect if the media is the wrong format
$transcode_to = $transcode_to == $media->type ? null : $transcode_to;

debug_event('play', 'Custom play action {' . $cpaction . '}', 5);
debug_event('play', 'Transcode to {' . $transcode_to . '}', 5);

// If custom play action, do not try to transcode
if (!$cpaction) {
    $transcode_cfg = AmpConfig::get('transcode');
    $valid_types   = $media->get_stream_types($player);
    if ($transcode_cfg != 'never' && in_array('transcode', $valid_types)) {
        if ($transcode_to) {
            $transcode = true;
            debug_event('play', 'Transcoding due to explicit request for ' . $transcode_to, 5);
        } else {
            if ($transcode_cfg == 'always') {
                $transcode = true;
                debug_event('play', 'Transcoding due to always', 5);
            } else {
                if ($force_downsample) {
                    $transcode = true;
                    debug_event('play', 'Transcoding due to downsample_remote', 5);
                } else {
                    if (($bitrate > 0 && ($bitrate * 100) < $media->bitrate) || ($maxbitrate > 0 && ($maxbitrate * 100) < $media->bitrate)) {
                        $transcode = true;
                        debug_event('play', 'Transcoding because explicit bitrate request', 5);
                    } else {
                        if (!in_array('native', $valid_types)) {
                            $transcode = true;
                            debug_event('play', 'Transcoding because native streaming is unavailable', 5);
                        } else {
                            if (!empty($subtitle)) {
                                $transcode = true;
                                debug_event('play', 'Transcoding because subtitle requested', 5);
                            } else {
                                debug_event('play', 'Decided not to transcode', 5);
                            }
                        }
                    }
                }
            }
        }
    } else {
        if ($transcode_cfg != 'never') {
            debug_event('play', 'Transcoding is not enabled for this media type. Valid types: {' . json_encode($valid_types) . '}', 5);
        } else {
            debug_event('play', 'Transcode disabled in user settings.', 5);
        }
    }
}

if ($transcode) {
    $troptions = array();
    if ($bitrate) {
        $troptions['bitrate'] = $bitrate;
    }
    if ($maxbitrate) {
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

    if (isset($_REQUEST['frame'])) {
        $troptions['frame'] = floatval($_REQUEST['frame']);
        if (isset($_REQUEST['duration'])) {
            $troptions['duration'] = floatval($_REQUEST['duration']);
        }
    } else {
        if (isset($_REQUEST['segment'])) {
            // 10 seconds segment. Should it be an option?
            $ssize            = 10;
            $send_all_in_once = true; // Should we use temporary folder instead?
            debug_event('play', 'Sending all data in once.', 5);
            $troptions['frame']    = intval($_REQUEST['segment']) * $ssize;
            $troptions['duration'] = ($troptions['frame'] + $ssize <= $media->time) ? $ssize : ($media->time - $troptions['frame']);
        }
    }

    $transcoder = Stream::start_transcode($media, $transcode_to, $player, $troptions);
    $fp         = $transcoder['handle'];
    $media_name = $media->f_artist_full . " - " . $media->title . "." . $transcoder['format'];
} else {
    if ($cpaction) {
        $transcoder = $media->run_custom_play_action($cpaction, $transcode_to);
        $fp         = $transcoder['handle'];
        $transcode  = true;
    } else {
        $fp = fopen(Core::conv_lc_file($media->file), 'rb');
    }
}

if ($transcode) {
    // Content-length guessing if required by the player.
    // Otherwise it shouldn't be used as we are not really sure about final length when transcoding
    if ($_REQUEST['content_length'] == 'required') {
        $max_bitrate = Stream::get_allowed_bitrate($media);
        if ($media->time > 0 && $max_bitrate > 0) {
            $stream_size = ($media->time * $max_bitrate * 1000) / 8;
        } else {
            debug_event('play', 'Bad media duration / Max bitrate. Content-length calculation skipped.', 5);
            $stream_size = null;
        }
    } else {
        $stream_size = null;
    }
} else {
    $stream_size = $media->size;
}

if (!is_resource($fp)) {
    debug_event('play', "Failed to open $media->file for streaming", 2);
    exit();
}

if (!$transcode) {
    header('ETag: ' . $media->id);
}
Stream::insert_now_playing($media->id, $uid, $media->time, $sid, get_class($media));

// Handle Content-Range

$start        = 0;
$end          = 0;
$range_values = sscanf($_SERVER['HTTP_RANGE'], "bytes=%d-%d", $start, $end);

if ($range_values > 0 && ($start > 0 || $end > 0)) {
    // Calculate stream size from byte range
    if ($range_values >= 2) {
        $end = min($end, $media->size - 1);
    } else {
        $end = $media->size - 1;
    }
    $stream_size = ($end - $start) + 1;

    if ($stream_size == null) {
        debug_event('play', 'Content-Range header received, which we cannot fulfill due to unknown final length (transcoding?)', 2);
    } else {
        if ($transcode) {
            debug_event('play', 'We should transcode only for a calculated frame range, but not yet supported here.', 2);
            $stream_size = null;
        } else {
            debug_event('play', 'Content-Range header received, skipping ' . $start . ' bytes out of ' . $media->size, 5);
            fseek($fp, $start);

            $range = $start . '-' . $end . '/' . $media->size;
            header('HTTP/1.1 206 Partial Content');
            header('Content-Range: bytes ' . $range);
        }
    }
} else {
    debug_event('play','Starting stream of ' . $media->file . ' with size ' . $media->size, 5);
}

if (!isset($_REQUEST['segment'])) {
    if ($media->time) {
        header('X-Content-Duration: ' . $media->time);
    }

    // Stats registering must be done before play. Do not move it.
    // It can be slow because of scrobbler plugins (lastfm, ...)
    if ($start > 0) {
        debug_event('play', 'Content-Range doesn\'t start from 0, stats should already be registered previously; not collecting stats', 5);
    } else {
        if (!$share_id) {
            if ($_SERVER['REQUEST_METHOD'] != 'HEAD') {
                debug_event('play', 'Registering stream stats for {' . $media->get_stream_name() . '}...', 5);
                $sessionkey = $sid ?: Stream::get_session();
                $agent      = Session::agent($sessionkey);
                $location   = Session::get_geolocation($sessionkey);
                $GLOBALS['user']->update_stats($type, $media->id, $agent, $location, isset($_REQUEST['noscrobble']));
            }
        }
    }
}

if ($transcode || $demo_id) {
    header('Accept-Ranges: none');
} else {
    header('Accept-Ranges: bytes');
}

$mime = $media->mime;
if ($transcode && isset($transcoder)) {
    $mime = $media->type_to_mime($transcoder['format']);
    // Non-blocking stream doesn't work in Windows (php bug since 2005 and still here in 2015...)
    // We don't want to wait indefinitly for a potential error so we just ignore it.
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        // This to avoid hang, see http://php.net/manual/es/function.proc-open.php#89338
        $transcode_error = fread($transcoder['stderr'], 4096);
        if (!empty($transcode_error)) {
            debug_event('play', 'Transcode stderr: ' . $transcode_error, 1);
        }
        fclose($transcoder['stderr']);
    }
};

// If this is a democratic playlist remove the entry.
// We do this regardless of play amount.
if ($demo_id && isset($democratic)) {
    $democratic->delete_from_oid($oid, $type);
}

// Close sql connection
// Warning: do not call functions requiring sql after this point
Dba::disconnect();
// Free the session write lock
// Warning: Do not change any session variable after this call
session_write_close();

$browser->downloadHeaders($media_name, $mime, false, $stream_size);

$bytes_streamed = 0;

// Actually do the streaming
$buf_all = '';
$r_arr   = array($fp);
$w_arr   = $e_arr   = null;
$status  = stream_select($r_arr, $w_arr, $e_arr, 2);
if ($status === false) {
    debug_event('play', 'stream_select failed.', 1);
} elseif ($status > 0) {
    do {
        $read_size = $transcode ? 2048 : min(2048, $stream_size - $bytes_streamed);
        if ($buf = fread($fp, $read_size)) {
            if ($send_all_in_once) {
                $buf_all .= $buf;
            } else {
                if (!empty($buf)) {
                    print($buf);
                    if (ob_get_length()) {
                        ob_flush();
                        flush();
                        ob_end_flush();
                    }
                    ob_start();
                }
            }
            $bytes_streamed += strlen($buf);
        }
    } while (!feof($fp) && (connection_status() == 0) && ($transcode || $bytes_streamed < $stream_size));
}

if ($send_all_in_once && connection_status() == 0) {
    header("Content-Length: " . strlen($buf_all));
    print($buf_all);
    ob_flush();
}

$real_bytes_streamed = $bytes_streamed;
// Need to make sure enough bytes were sent.
if ($bytes_streamed < $stream_size && (connection_status() == 0)) {
    print(str_repeat(' ', $stream_size - $bytes_streamed));
    $bytes_streamed = $stream_size;
}

if ($transcode && isset($transcoder)) {
    fclose($fp);

    Stream::kill_process($transcoder);
} else {
    fclose($fp);
}

debug_event('play', 'Stream ended at ' . $bytes_streamed . ' (' . $real_bytes_streamed . ') bytes out of ' . $stream_size, 5);
