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


/**
 * Sub-Ajax page, requires AJAX_INCLUDE
 */
if (!defined('AJAX_INCLUDE')) {
    exit;
}

debug_event('stream.ajax.php', 'Called for action {' . $_REQUEST['action'] . '}', 5);

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
                $new                = 'stream';
                $results['rfc3514'] = '0x1';
            break 2;
        } // end switch

        $current = AmpConfig::get('play_type');

        // Go ahead and update their preference
        if (Preference::update('play_type',$GLOBALS['user']->id,$new)) {
            AmpConfig::set('play_type', $new, true);
        }

        if (($new == 'localplay' and $current != 'localplay') or ($current == 'localplay' and $new != 'localplay')) {
            $results['rightbar'] = UI::ajax_include('rightbar.inc.php');
        }

        $results['rfc3514'] = '0x0';
    break;
    case 'directplay':

        debug_event('stream.ajax.php', 'Play type {' . $_REQUEST['playtype'] . '}', 5);
        $object_type = $_REQUEST['object_type'];
        $object_id   = $_REQUEST['object_id'];
        if (is_array($object_id)) {
            $object_id = implode(',', $object_id);
        }

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
            $results['rfc3514'] = '<script type="text/javascript">' . Core::get_reloadutil() . '(\'' . AmpConfig::get('web_path') . '/util.php\');</script>';
        }
    break;
    case 'basket':
        // Go ahead and see if we should clear the playlist here or not,
        // we might not actually clear it in the session.
        if ( ($_REQUEST['playlist_method'] == 'clear' || AmpConfig::get('playlist_method') == 'clear')) {
            define('NO_SONGS','1');
            ob_start();
            require_once AmpConfig::get('prefix') . UI::find_template('rightbar.inc.php');
            $results['rightbar'] = ob_get_clean();
        }

        // We need to set the basket up!
        $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=basket&playlist_method=' . scrub_out($_REQUEST['playlist_method']);
        $results['rfc3514']           = '<script type="text/javascript">' . Core::get_reloadutil() . '(\'' . AmpConfig::get('web_path') . '/util.php\');</script>';
    break;
    default:
        $results['rfc3514'] = '0x1';
    break;
} // switch on action;

// We always do this
echo xoutput_from_array($results);
