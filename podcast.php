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

if (!AmpConfig::get('podcast')) {
    UI::access_denied();
    exit;
}

UI::show_header();

// Switch on Action
switch ($_REQUEST['action']) {
    case 'show_create':
        if (!Access::check('interface', 75)) {
            UI::access_denied();
            exit;
        }

        require_once AmpConfig::get('prefix') . UI::find_template('show_add_podcast.inc.php');

    break;
    case 'create':
        if (!Access::check('interface', 75) || AmpConfig::get('demo_mode')) {
            UI::access_denied();
            exit;
        }

        if (!Core::form_verify('add_podcast','post')) {
            UI::access_denied();
            exit;
        }

        // Try to create the sucker
        $results = Podcast::create($_POST);

        if (!$results) {
            require_once AmpConfig::get('prefix') . UI::find_template('show_add_podcast.inc.php');
        } else {
            $title  = T_('Subscribed to Podcast');
            $body   = '';
            show_confirmation($title,$body,AmpConfig::get('web_path') . '/browse.php?action=podcast');
        }
    break;
    case 'delete':
        if (!Access::check('interface', 75) || AmpConfig::get('demo_mode')) {
            UI::access_denied();
            exit;
        }

        $podcast_id = scrub_in($_REQUEST['podcast_id']);
        show_confirmation(
            T_('Podcast Deletion'),
            T_('Are you sure you want to delete this podcast?'),
            AmpConfig::get('web_path') . "/podcast.php?action=confirm_delete&podcast_id=" . $podcast_id,
            1,
            'delete_podcast'
        );
    break;
    case 'confirm_delete':
        if (!Access::check('interface', 75) || AmpConfig::get('demo_mode')) {
            UI::access_denied();
            exit;
        }

        $podcast = new Podcast($_REQUEST['podcast_id']);
        if ($podcast->remove()) {
            show_confirmation(T_('Podcast Deletion'), T_('Podcast has been deleted.'), AmpConfig::get('web_path') . '/browse.php?action=podcast');
        } else {
            show_confirmation(T_('Podcast Deletion'), T_('Cannot delete this podcast.'), AmpConfig::get('web_path') . '/browse.php?action=podcast');
        }
    break;
    case 'show':
        $podcast_id = intval($_REQUEST['podcast']);
        if ($podcast_id > 0) {
            $podcast = new Podcast($podcast_id);
            $podcast->format();
            $object_ids  = $podcast->get_episodes();
            $object_type = 'podcast_episode';
            require_once AmpConfig::get('prefix') . UI::find_template('show_podcast.inc.php');
        }
    break;
} // end data collection

UI::show_footer();
