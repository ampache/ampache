<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2019 Ampache.org
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

require_once 'lib/init.php';

UI::show_header();

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $video_id = scrub_in(filter_input(INPUT_GET, 'video_id', FILTER_SANITIZE_SPECIAL_CHARS));
        show_confirmation(
            T_('Video Deletion'),
            T_('Are you sure you want to permanently delete this video?'),
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

        if ($video->remove_from_disk()) {
            show_confirmation(T_('Video Deletion'), T_('Video has been deleted.'), AmpConfig::get('web_path'));
        } else {
            show_confirmation(T_('Video Deletion'), T_('Cannot delete this video.'), AmpConfig::get('web_path'));
        }
    break;
    case 'show_video':
    default:
        $video = Video::create_from_id(filter_input(INPUT_GET, 'video_id', FILTER_SANITIZE_SPECIAL_CHARS));
        $video->format();
        require_once AmpConfig::get('prefix') . UI::find_template('show_video.inc.php');
    break;
}

UI::show_footer();
