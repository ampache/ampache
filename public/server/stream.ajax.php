<?php
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


/**
 * Sub-Ajax page, requires AJAX_INCLUDE
 */
if (!defined('AJAX_INCLUDE')) {
    return false;
}

debug_event('stream.ajax', 'Called for action {' . Core::get_request('action') . '}', 5);

$results = array();

// Switch on the actions
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
                $key = 'allow_' . Core::get_post('type') . '_playback';
                if (!AmpConfig::get($key)) {
                    $results['rfc3514'] = '0x1';
                    break 2;
                }
                $new = Core::get_post('type');
                break;
            case 'web_player':
                $new = 'web_player';
                break;
            default:
                $new                = 'stream';
                $results['rfc3514'] = '0x1';
                break 2;
        } // end switch

        $current = AmpConfig::get('play_type');

        // Go ahead and update their preference
        if (Preference::update('play_type', Core::get_global('user')->id, $new)) {
            AmpConfig::set('play_type', $new, true);
        }

        if (($new == 'localplay' && $current != 'localplay') || ($current == 'localplay' && $new != 'localplay')) {
            $results['rightbar'] = UI::ajax_include('rightbar.inc.php');
        }

        $results['rfc3514'] = '0x0';
        break;
    case 'directplay':
        $object_type = Core::get_request('object_type');
        $object_id   = $_GET['object_id'];
        if (is_array($object_id)) {
            $object_id = implode(',', $object_id);
        }
        debug_event('stream.ajax', 'Called for ' . $object_type . ': {' . $object_id . '}', 5);

        if (Core::is_playable_item($object_type)) {
            $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=play_item&object_type=' . $object_type . '&object_id=' . $object_id;
            if ($_REQUEST['custom_play_action']) {
                $_SESSION['iframe']['target'] .= '&custom_play_action=' . $_REQUEST['custom_play_action'];
            }
            if (!empty($_REQUEST['append'])) {
                $_SESSION['iframe']['target'] .= '&append=true';
            }
            if (!empty($_REQUEST['playnext'])) {
                $_SESSION['iframe']['target'] .= '&playnext=true';
            }
            if ($_REQUEST['subtitle']) {
                $_SESSION['iframe']['subtitle'] = $_REQUEST['subtitle'];
            } else {
                if (isset($_SESSION['iframe']['subtitle'])) {
                    unset($_SESSION['iframe']['subtitle']);
                }
            }
            $results['rfc3514'] = '<script>' . Core::get_reloadutil() . '(\'' . AmpConfig::get('web_path') . '/util.php\');</script>';
        }
        break;
    case 'basket':
        // Go ahead and see if we should clear the playlist here or not,
        // we might not actually clear it in the session.
        if (($_REQUEST['playlist_method'] == 'clear' || AmpConfig::get('playlist_method') == 'clear')) {
            define('NO_SONGS', '1');
            ob_start();
            require_once AmpConfig::get('prefix') . UI::find_template('rightbar.inc.php');
            $results['rightbar'] = ob_get_clean();
        }

        // We need to set the basket up!
        $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=basket&playlist_method=' . scrub_out($_REQUEST['playlist_method']);
        $results['rfc3514']           = '<script>' . Core::get_reloadutil() . '(\'' . AmpConfig::get('web_path') . '/util.php\');</script>';
        break;
    default:
        $results['rfc3514'] = '0x1';
        break;
} // switch on action;

// We always do this
echo (string) xoutput_from_array($results);
