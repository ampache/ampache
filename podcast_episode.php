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

        $episode_id = (string) scrub_in($_REQUEST['podcast_episode_id']);
        show_confirmation(T_('Are You Sure?'),
            T_("The Podcast Episode will be deleted"),
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
            debug_event('podcast_episode', 'Unauthorized to remove the episode `.' . $episode->id . '`.', 1);
            UI::access_denied();

            return false;
        }

        if ($episode->remove()) {
            show_confirmation(T_('No Problem'), T_('Podcast Episode has been deleted'), AmpConfig::get('web_path'));
        } else {
            show_confirmation(T_("There Was a Problem"), T_("Couldn't delete this Podcast Episode"), AmpConfig::get('web_path'));
        }
        break;
    case 'show':
    default:
        $episode = new Podcast_Episode($_REQUEST['podcast_episode']);
        $episode->format();
        require_once AmpConfig::get('prefix') . UI::find_template('show_podcast_episode.inc.php');
        break;
}

// Show the Footer
UI::show_query_stats();
UI::show_footer();
