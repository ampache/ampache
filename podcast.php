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

if (!AmpConfig::get('podcast')) {
    UI::access_denied();

    return false;
}

UI::show_header();

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'show_create':
        if (!Access::check('interface', 75)) {
            UI::access_denied();

            return false;
        }

        require_once AmpConfig::get('prefix') . UI::find_template('show_add_podcast.inc.php');

        break;
    case 'create':
        if (!Access::check('interface', 75) || AmpConfig::get('demo_mode')) {
            UI::access_denied();

            return false;
        }

        if (!Core::form_verify('add_podcast', 'post')) {
            UI::access_denied();

            return false;
        }

        // Try to create the sucker
        $results = Podcast::create($_POST);

        if (!$results) {
            require_once AmpConfig::get('prefix') . UI::find_template('show_add_podcast.inc.php');
        } else {
            $title  = T_('No Problem');
            $body   = T_('Subscribed to the Podcast');
            show_confirmation($title, $body, AmpConfig::get('web_path') . '/browse.php?action=podcast');
        }
        break;
    case 'delete':
        if (!Access::check('interface', 75) || AmpConfig::get('demo_mode')) {
            UI::access_denied();

            return false;
        }

        $podcast_id = (string) scrub_in($_REQUEST['podcast_id']);
        show_confirmation(T_('Are You Sure?'),
            T_("The Podcast will be removed from the database"),
            AmpConfig::get('web_path') . "/podcast.php?action=confirm_delete&podcast_id=" . $podcast_id,
            1,
            'delete_podcast'
        );
        break;
    case 'confirm_delete':
        if (!Access::check('interface', 75) || AmpConfig::get('demo_mode')) {
            UI::access_denied();

            return false;
        }

        $podcast = new Podcast((int) $_REQUEST['podcast_id']);
        if ($podcast->remove()) {
            show_confirmation(T_('No Problem'), T_('Podcast has been deleted'), AmpConfig::get('web_path') . '/browse.php?action=podcast');
        } else {
            show_confirmation(T_("There Was a Problem"), T_("Couldn't delete this Podcast."), AmpConfig::get('web_path') . '/browse.php?action=podcast');
        }
        break;
    case 'show':
        $podcast_id = (int) filter_input(INPUT_GET, 'podcast', FILTER_SANITIZE_NUMBER_INT);
        if ($podcast_id > 0) {
            $podcast = new Podcast($podcast_id);
            $podcast->format();
            $object_ids  = $podcast->get_episodes();
            $object_type = 'podcast_episode';
            require_once AmpConfig::get('prefix') . UI::find_template('show_podcast.inc.php');
        }
        break;
} // end data collection

// Show the Footer
UI::show_query_stats();
UI::show_footer();
