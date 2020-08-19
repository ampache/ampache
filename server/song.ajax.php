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

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'flip_state':
        if (!Access::check('interface', 75)) {
            debug_event('song.ajax', Core::get_global('user')->username . ' attempted to change the state of a song', 1);

            return false;
        }

        $song        = new Song($_REQUEST['song_id']);
        $new_enabled = $song->enabled ? false : true;
        Song::update_enabled($new_enabled, $song->id);
        $song->enabled = $new_enabled;
        $song->format();

        // Return the new Ajax::button
        $id           = 'button_flip_state_' . $song->id;
        if ($new_enabled) {
            $button     = 'disable';
            $buttontext = T_('Disable');
        } else {
            $button     = 'enable';
            $buttontext = T_('Enable');
        }
        $results[$id] = Ajax::button('?page=song&action=flip_state&song_id=' . $song->id, $button, $buttontext, 'flip_state_' . $song->id);
        break;
    case 'shouts':
        ob_start();
        $type   = Core::get_request('object_type');
        $songid = filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT);

        if ($type == "song") {
            $media  = new Song($songid);
            $shouts = Shoutbox::get_shouts($type, $songid);
            echo "<script>\r\n";
            echo "shouts = {};\r\n";
            foreach ($shouts as $shoutsid) {
                $shout = new Shoutbox($shoutsid);
                $shout->format();
                $key = (int) ($shout->data);
                echo "if (shouts['" . $key . "'] == undefined) { shouts['" . $key . "'] = new Array(); }\r\n";
                echo "shouts['" . $key . "'].push('" . addslashes($shout->get_display(false)) . "');\r\n";
                echo "$('.waveform-shouts').append('<div style=\'position:absolute; width: 3px; height: 3px; background-color: #2E2EFE; top: 15px; left: " . ((($shout->data / $media->time) * 400) - 1) . "px;\' />');\r\n";
            }
            echo "</script>\r\n";
        }
        $results['shouts_data'] = ob_get_clean();
        break;
    default:
        $results['rfc3514'] = '0x1';
        break;
} // switch on action;

// We always do this
echo (string) xoutput_from_array($results);
