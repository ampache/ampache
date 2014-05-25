<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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


/**
 * Sub-Ajax page, requires AJAX_INCLUDE
 */
if (!defined('AJAX_INCLUDE')) { exit; }

debug_event('stream.ajax.php', 'Called for action {'.$_REQUEST['action'].'}', 5);

$results = array();
switch ($_REQUEST['action']) {
    case 'set_play_type':
        // Make sure they have the rights to do this
        if (!Preference::has_access('play_type')) {
            $results['rfc3514'] = '0x1';
            break;
        }

        switch ($_POST['type']) {
            case 'stream':
            case 'localplay':
            case 'democratic':
                $key = 'allow_' . $_POST['type'] . '_playback';
                if (!AmpConfig::get($key)) {
                    $results['rfc3514'] = '0x1';
                    break 2;
                }
                $new = $_POST['type'];
            break;
            case 'web_player':
                $new = $_POST['type'];
                // Rien a faire
            break;
            default:
                $new = 'stream';
                $results['rfc3514'] = '0x1';
            break 2;
        } // end switch

        $current = AmpConfig::get('play_type');

        // Go ahead and update their preference
        if (Preference::update('play_type',$GLOBALS['user']->id,$new)) {
            AmpConfig::set('play_type', $new, true);
        }

        if (($new == 'localplay' AND $current != 'localplay') OR ($current == 'localplay' AND $new != 'localplay')) {
            $results['rightbar'] = UI::ajax_include('rightbar.inc.php');
        }

        $results['rfc3514'] = '0x0';
    break;
    case 'directplay':

        debug_event('stream.ajax.php', 'Play type {'.$_REQUEST['playtype'].'}', 5);
        switch ($_REQUEST['playtype']) {
            case 'album':
                $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=album&album_id='.implode(',', $_REQUEST['album_id']);
            break;
            case 'artist':
                $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=artist&artist_id='.$_REQUEST['artist_id'];
            break;
            case 'song':
                $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=single_song&song_id='.$_REQUEST['song_id'];
                if ($_REQUEST['custom_play_action']) {
                    $_SESSION['iframe']['target'] .= '&custom_play_action=' . $_REQUEST['custom_play_action'];
                }
            break;
            case 'video':
                $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=single_video&video_id='.$_REQUEST['video_id'];
            break;
            case 'playlist':
                $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=playlist&playlist_id='.$_REQUEST['playlist_id'];
            break;
            case 'smartplaylist':
                $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=smartplaylist&playlist_id='.$_REQUEST['playlist_id'];
            break;
            case 'live_stream':
                $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=live_stream&stream_id='.$_REQUEST['stream_id'];
            break;
            case 'album_preview':
                $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=album_preview&mbid='.$_REQUEST['mbid'];
            break;
            case 'song_preview':
                $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=song_preview&id='.$_REQUEST['id'];
            break;
            case 'channel':
                $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=channel&id='.$_REQUEST['channel_id'];
            break;
            case 'broadcast':
                $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=broadcast&id='.$_REQUEST['broadcast_id'];
            break;
        }
        if (!empty($_REQUEST['append'])) {
            $_SESSION['iframe']['target'] .= '&append=true';
        }
        $results['rfc3514'] = '<script type="text/javascript">reloadUtil(\''. AmpConfig::get('web_path') . '/util.php\');</script>';
    break;
    case 'basket':
        // Go ahead and see if we should clear the playlist here or not,
        // we might not actually clear it in the session.
        if ( ($_REQUEST['playlist_method'] == 'clear' || AmpConfig::get('playlist_method') == 'clear')) {
            define('NO_SONGS','1');
            ob_start();
            require_once AmpConfig::get('prefix') . '/templates/rightbar.inc.php';
            $results['rightbar'] = ob_get_clean();
        }

        // We need to set the basket up!
        $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=basket&playlist_method=' . scrub_out($_REQUEST['playlist_method']);
        $results['rfc3514'] = '<script type="text/javascript">reloadUtil(\''. AmpConfig::get('web_path') . '/util.php\');</script>';
    break;
    default:
        $results['rfc3514'] = '0x1';
    break;
} // switch on action;

// We always do this
echo xoutput_from_array($results);
