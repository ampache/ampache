<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
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

require_once 'lib/init.php';

UI::show_header();

switch ($_REQUEST['action']) {
    case 'delete':
        if (AmpConfig::get('demo_mode')) { break; }

        $video_id = scrub_in($_REQUEST['video_id']);
        show_confirmation(
            T_('Video Deletion'),
            T_('Are you sure you want to permanently delete this video?'),
            AmpConfig::get('web_path')."/video.php?action=confirm_delete&video_id=" . $video_id,
            1,
            'delete_video'
        );
    break;
    case 'confirm_delete':
        if (AmpConfig::get('demo_mode')) { break; }

        $video = Video::create_from_id($_REQUEST['video_id']);
        if (!Catalog::can_remove($video)) {
            debug_event('video', 'Unauthorized to remove the video `.' . $video->id . '`.', 1);
            UI::access_denied();
            exit;
        }

        if ($video->remove_from_disk()) {
            show_confirmation(T_('Video Deletion'), T_('Video has been deleted.'), AmpConfig::get('web_path'));
        } else {
            show_confirmation(T_('Video Deletion'), T_('Cannot delete this video.'), AmpConfig::get('web_path'));
        }
    break;
    case 'show_video':
    default:
        $video = Video::create_from_id($_REQUEST['video_id']);
        $video->format();
        require_once AmpConfig::get('prefix') . '/templates/show_video.inc.php';
    break;
}

UI::show_footer();
