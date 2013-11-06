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

/*

 This is the wrapper for opening music streams from this server.  This script
   will play the local version or redirect to the remote server if that be
   the case.  Also this will update local statistics for songs as well.
   This is also where it decides if you need to be downsampled.
*/
define('NO_SESSION','1');
require_once '../lib/init.php';
ob_end_clean();

/* These parameters had better come in on the url. */
$uid         = scrub_in($_REQUEST['uid']);
$oid         = $_REQUEST['oid']
            // FIXME: Any place that doesn't use oid should be fixed
            ? scrub_in($_REQUEST['oid'])
            : scrub_in($_REQUEST['song']);
$sid         = scrub_in($_REQUEST['ssid']);
$video        = make_bool($_REQUEST['video']);
$type        = scrub_in($_REQUEST['type']);
$transcode_to	= scrub_in($_REQUEST['transcode_to']);

if ($video) {
    // FIXME: Compatibility hack, should eventually be removed
    $type = 'video';
}

if (!$type) {
    // FIXME: Compatibility hack, should eventually be removed
    $type = 'song';
}

if ($type == 'playlist') {
    $playlist_type = scrub_in($_REQUEST['playlist_type']);
    $oid = $sid;
}

/* This is specifically for tmp playlist requests */
$demo_id    = scrub_in($_REQUEST['demo_id']);
$random        = scrub_in($_REQUEST['random']);

/* First things first, if we don't have a uid/oid stop here */
if (empty($oid) && empty($demo_id) && empty($random)) {
    debug_event('play', 'No object UID specified, nothing to play', 2);
    header('HTTP/1.1 400 Nothing To Play');
    exit;
}

if (empty($uid)) {
    debug_event('play', 'No user specified', 2);
    header('HTTP/1.1 400 No User Specified');
    exit;
}

/* Misc Housework */
$GLOBALS['user'] = new User($uid);
Preference::init();

/* If the user has been disabled (true value) */
if (make_bool($GLOBALS['user']->disabled)) {
    debug_event('UI::access_denied', "$user->username is currently disabled, stream access denied",'3');
    header('HTTP/1.1 403 User Disabled');
    exit;
}

// If require session is set then we need to make sure we're legit
if (Config::get('require_session')) {
    if (!Config::get('require_localnet_session') AND Access::check_network('network',$GLOBALS['user']->id,'5')) {
        debug_event('play', 'Streaming access allowed for local network IP ' . $_SERVER['REMOTE_ADDR'],'5');
    }
    else if(!Session::exists('stream', $sid)) {
        debug_event('UI::access_denied', 'Streaming access denied: ' . $GLOBALS['user']->username . "'s session has expired", 3);
            header('HTTP/1.1 403 Session Expired');
        exit;
    }

    // Now that we've confirmed the session is valid
    // extend it
    Session::extend($sid, 'stream');
}


/* Update the users last seen information */
$GLOBALS['user']->update_last_seen();

/* If we are in demo mode.. die here */
if (Config::get('demo_mode') || (!Access::check('interface','25') )) {
    debug_event('UI::access_denied', "Streaming Access Denied:" .Config::get('demo_mode') . "is the value of demo_mode. Current user level is " . $GLOBALS['user']->access,'3');
    UI::access_denied();
    exit;
}

/*
   If they are using access lists let's make sure
   that they have enough access to play this mojo
*/
if (Config::get('access_control')) {
    if (!Access::check_network('stream',$GLOBALS['user']->id,'25') AND
        !Access::check_network('network',$GLOBALS['user']->id,'25')) {
        debug_event('UI::access_denied', "Streaming Access Denied: " . $_SERVER['REMOTE_ADDR'] . " does not have stream level access",'3');
        UI::access_denied();
        exit;
    }
} // access_control is enabled

// Handle playlist downloads
if ($type == 'playlist') {
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
    }
    else {
        // Pull history
        $oid = $democratic->get_next_object($song_cool_check);
        $oids = $democratic->get_cool_songs();
        while (in_array($oid,$oids)) {
            $song_cool_check++;
            $oid = $democratic->get_next_object($song_cool_check);
            if ($song_cool_check >= '5') { break; }
        } // while we've got the 'new' song in old the array

    } // end if we've got a cooldown
} // if democratic ID passed

/**
 * if we are doing random let's pull the random object
 */
if ($random) {
    if ($start < 1) {
        $oid = Random::get_single_song($_REQUEST['random_type']);
        // Save this one in case we do a seek
        $_SESSION['random']['last'] = $oid;
    }
    else {
        $oid = $_SESSION['random']['last'];
    }
} // if random

if ($type == 'song') {
    /* Base Checks passed create the song object */
    $media = new Song($oid);
    $media->format();
}
else {
    $media = new Video($oid);
    $media->format();
}

// Build up the catalog for our current object
$catalog = new Catalog($media->catalog);

/* If the song is disabled */
if (!make_bool($media->enabled)) {
    debug_event('Play',"Error: $media->file is currently disabled, song skipped",'5');
    // Check to see if this is a democratic playlist, if so remove it completely
    if ($demo_id) { $democratic->delete_from_oid($oid,'song'); }
    exit;
}

// If we are running in Legalize mode, don't play songs already playing
if (Config::get('lock_songs')) {
    if (!Stream::check_lock_media($media->id,get_class($media))) {
        exit;
    }
}

if ($catalog->catalog_type == 'remote') {
    $remote_handle = $catalog->connect();

    // If we don't get anything back we failed and should bail now
    if (!$remote_handle) {
        debug_event('play', 'Connection to remote server failed', 1);
        exit;
    }

    $handshake = $remote_handle->info();
    $url = $media->file . '&ssid=' . $handshake['auth'];

    header('Location: ' . $url);
    debug_event('play', 'Started remote stream - ' . $url, 5);

    // Handle democratic removal 
    if ($demo_id) {
        $democratic->delete_from_oid($oid, 'song');
    }

    exit;
}

/* If we don't have a file, or the file is not readable */
if (!$media->file || !Core::is_readable($media->file)) {

    // We need to make sure this isn't democratic play, if it is then remove
    // the song from the vote list
    if (is_object($tmp_playlist)) {
        $tmp_playlist->delete_track($oid);
    }
    // FIXME: why are these separate?
    // Remove the song votes if this is a democratic song
    if ($demo_id) { $democratic->delete_from_oid($oid,'song'); }

    debug_event('play', "Song $media->file ($media->title) does not have a valid filename specified", 2);
    header('HTTP/1.1 404 Invalid song, file not found or file unreadable');
    exit;
}

// don't abort the script if user skips this song because we need to update now_playing
ignore_user_abort(true);

// Format the song name
$media_name = $media->f_artist_full . " - " . $media->title . "." . $media->type;


// Generate browser class for sending headers
$browser = new Horde_Browser();

/* If they are just trying to download make sure they have rights
 * and then present them with the download file
 */
if ($_GET['action'] == 'download' AND Config::get('download')) {

    // STUPID IE
    $media->format_pattern();
    $media_name = str_replace(array('?','/','\\'),"_",$media->f_file);

    $browser->downloadHeaders($media_name,$media->mime,false,$media->size);
    $fp = fopen($media->file,'rb');
    $bytesStreamed = 0;

    if (!is_resource($fp)) {
                debug_event('Play',"Error: Unable to open $media->file for downloading",'2');
        exit();
        }

    // Check to see if we should be throttling because we can get away with it
    if (Config::get('rate_limit') > 0) {
        while (!feof($fp)) {
            echo fread($fp,round(Config::get('rate_limit')*1024));
            $bytesStreamed += round(Config::get('rate_limit')*1024);
            flush();
            sleep(1);
        }
    }
    else {
        fpassthru($fp);
    }

    // Make sure that a good chunk of the song has been played
    if ($bytesStreamed >= $media->size) {
            debug_event('Play','Downloaded, Registering stats for ' . $media->title,'5');
            $GLOBALS['user']->update_stats($media->id);
    } // if enough bytes are streamed

    fclose($fp);
    exit();

} // if they are trying to download and they can

// Prevent the script from timing out
set_time_limit(0);

// We're about to start. Record this user's IP.
if (Config::get('track_user_ip')) {
    $GLOBALS['user']->insert_ip_history();
}

$force_downsample = false;
if (Config::get('downsample_remote')) {
    if (!Access::check_network('network', $GLOBALS['user']->id,'0')) {
        debug_event('play', 'Downsampling enabled for non-local address ' . $_SERVER['REMOTE_ADDR'], 5);
        $force_downsample = true;
    }
}

// Determine whether to transcode
$transcode = false;
$transcode_cfg = Config::get('transcode');
// transcode_to should only have an effect if the song is the wrong format
$transcode_to = $transcode_to == $media->type ? null : $transcode_to;
$valid_types = $media->get_stream_types();
if ($transcode_cfg != 'never' && in_array('transcode', $valid_types)) {
    if ($transcode_to) {
        $transcode = true;
        debug_event('play', 'Transcoding due to explicit request for ' . $transcode_to, 5);
    }
    else if ($transcode_cfg == 'always') {
        $transcode = true;
        debug_event('play', 'Transcoding due to always', 5);
    }
    else if ($force_downsample) {
        $transcode = true;
        debug_event('play', 'Transcoding due to downsample_remote', 5);
    }
    else if (!in_array('native', $valid_types)) {
        $transcode = true;
        debug_event('play', 'Transcoding because native streaming is unavailable', 5);
    }
    else {
        debug_event('play', 'Decided not to transcode', 5);
    }
}
else if ($transcode_to) {
    debug_event('play', 'Transcoding is impossible but we received an explicit request for ' . $transcode_to, 2);
}

if ($transcode) {
    header('Accept-Ranges: none');
    $transcoder = Stream::start_transcode($media, $transcode_to);
    $fp = $transcoder['handle'];
    $media_name = $media->f_artist_full . " - " . $media->title . "." . $transcoder['format'];
}
else if (!in_array('native', $valid_types)) {
    debug_event('play', 'Not transcoding and native streaming is not supported, aborting', 2);
    exit();
}
else {
    header('Accept-Ranges: bytes');
    $fp = fopen($media->file, 'rb');
}

if (!is_resource($fp)) {
    debug_event('play', "Failed to open $media->file for streaming", 2);
    exit();
}

// Put this song in the now_playing table only if it's a song for now...
if (get_class($media) == 'Song') {
    Stream::insert_now_playing($media->id,$uid,$media->time,$sid,get_class($media));
}

if ($transcode) {
    $stream_size = null;
}
else {
    $stream_size = $media->size;
}

// Handle Content-Range

sscanf($_SERVER['HTTP_RANGE'], "bytes=%d-%d", $start, $end);

if ($start > 0 || $end > 0 ) {
    // Calculate stream size from byte range
    if (isset($end)) {
        $end = min($end, $media->size - 1);
        $stream_size = ($end - $start) + 1;
    }
    else {
        $stream_size = $media->size - $start;
    }

    if ($transcode) {
        debug_event('play', 'Bad client behaviour. Content-Range header received, which we cannot fulfill due to transcoding', 2);
        $stream_size = null;
    }
    else {
        debug_event('play', 'Content-Range header received, skipping ' . $start . ' bytes out of ' . $media->size, 5);
        fseek($fp, $start);

        $range = $start . '-' . $end . '/' . $media->size;
        header('HTTP/1.1 206 Partial Content');
        header('Content-Range: bytes ' . $range);
    }
}
else {
    debug_event('play','Starting stream of ' . $media->file . ' with size ' . $media->size, 5);
}

$mime = $transcode 
    ? $media->type_to_mime($transcoder['format'])
    : $media->mime;

$browser->downloadHeaders($media_name, $mime, false, $stream_size);

$bytes_streamed = 0;

// Actually do the streaming
do {
    $read_size = $transcode
        ? 2048
        : min(2048, $stream_size - $bytes_streamed);
    $buf = fread($fp, $read_size);
    print($buf);
    $bytes_streamed += strlen($buf);
} while (!feof($fp) && (connection_status() == 0) && ($transcode || $bytes_streamed < $stream_size));

$real_bytes_streamed = $bytes_streamed;
// Need to make sure enough bytes were sent.
if($bytes_streamed < $stream_size && (connection_status() == 0)) {
    print(str_repeat(' ', $stream_size - $bytes_streamed));
    $bytes_streamed = $stream_size;
}

// Make sure that a good chunk of the song has been played
$target = 131072;
if ($stream_size) {
    if ($stream_size > 1048576) {
        $target = 262144;
    }
    else if ($stream_size < 360448) {
        $target = $stream_size / 1.1;
    }
    else {
        $target = $stream_size / 4;
    }
}

if ($start > $target) {
    debug_event('play', 'Content-Range was more than ' . $target . ' into the file, not collecting stats', 5);
}
else if ($bytes_streamed > $target) {
    // FIXME: This check looks suspicious
    if (get_class($media) == 'Song') {
        debug_event('play', 'Registering stats for ' . $media->title, 5);
        $GLOBALS['user']->update_stats($media->id);
        $media->set_played();
    }
}
else {
    debug_event('play', $bytes_streamed .' of ' . $stream_size . ' streamed; not collecting stats', 5);
}

// If this is a democratic playlist remove the entry.
// We do this regardless of play amount.
if ($demo_id) { $democratic->delete_from_oid($oid,'song'); }

if ($transcode) {
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        $stderr = fread($transcoder['stderr'], 8192);
        fclose($transcoder['stderr']);
    }
    fclose($fp);
    proc_close($transcoder['process']);
    debug_event('transcode_cmd', $stderr, 5);
}
else {
    fclose($fp);
}

debug_event('play', 'Stream ended at ' . $bytes_streamed . ' (' . $real_bytes_streamed . ') bytes out of ' . $stream_size, 5);

?>
