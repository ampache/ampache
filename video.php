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

$a_root = realpath(__DIR__);
require_once $a_root . '/lib/init.php';

UI::show_header();

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $video_id = (string) scrub_in(filter_input(INPUT_GET, 'video_id', FILTER_SANITIZE_SPECIAL_CHARS));
        show_confirmation(T_('Are You Sure?'),
            T_("The Video will be deleted"),
            AmpConfig::get('web_path') . "/video.php?action=confirm_delete&video_id=" . $video_id,
            1,
            'delete_video'
        );
        break;
    case 'confirm_delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $video = Video::create_from_id(filter_input(INPUT_GET, 'video_id', FILTER_SANITIZE_SPECIAL_CHARS));
        if (!Catalog::can_remove($video)) {
            debug_event('video', 'Unauthorized to remove the video `.' . $video->id . '`.', 1);
            UI::access_denied();

            return false;
        }

        if ($video->remove()) {
            show_confirmation(T_('No Problem'), T_('Video has been deleted'), AmpConfig::get('web_path'));
        } else {
            show_confirmation(T_("There Was a Problem"), T_("Couldn't delete this Video."), AmpConfig::get('web_path'));
        }
        break;
    case 'show_video':
    default:
        $video = Video::create_from_id(filter_input(INPUT_GET, 'video_id', FILTER_SANITIZE_SPECIAL_CHARS));
        $video->format();
        $time_format = AmpConfig::get('custom_datetime') ? (string) AmpConfig::get('custom_datetime') : 'm/d/Y H:i';
        require_once AmpConfig::get('prefix') . UI::find_template('show_video.inc.php');
        break;
}

// Show the Footer
UI::show_query_stats();
UI::show_footer();
