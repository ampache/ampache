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

require_once 'lib/init.php';

UI::show_header();

switch ($_REQUEST['action']) {
    case 'delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $episode_id = scrub_in($_REQUEST['podcast_episode_id']);
        show_confirmation(
            T_('Podcast Episode Deletion'),
            T_('Are you sure you want to permanently delete this episode?'),
            AmpConfig::get('web_path') . "/podcast_episode.php?action=confirm_delete&podcast_episode_id=" . $episode_id,
            1,
            'delete_podcast_episode'
        );
    break;
    case 'confirm_delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $episode = new Podcast_Episode($_REQUEST['podcast_episode_id']);
        if (!Catalog::can_remove($episode)) {
            debug_event('video', 'Unauthorized to remove the episode `.' . $episode->id . '`.', 1);
            UI::access_denied();
            exit;
        }

        if ($episode->remove()) {
            show_confirmation(T_('Podcast Episode Deletion'), T_('Episode has been deleted.'), AmpConfig::get('web_path'));
        } else {
            show_confirmation(T_('Podcast Episode Deletion'), T_('Cannot delete this episode.'), AmpConfig::get('web_path'));
        }
    break;
    case 'show':
    default:
        $episode = new Podcast_Episode($_REQUEST['podcast_episode']);
        $episode->format();
        require_once AmpConfig::get('prefix') . UI::find_template('show_podcast_episode.inc.php');
    break;
}

UI::show_footer();
