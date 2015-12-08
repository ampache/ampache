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

/**
 * Display Switch
 */
switch ($_REQUEST['action']) {
    case 'delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $tvshow_season_id = scrub_in($_REQUEST['tvshow_season_id']);
        show_confirmation(
            T_('TVShow Season Deletion'),
            T_('Are you sure you want to permanently delete this tvshow season?'),
            AmpConfig::get('web_path') . "/tvshow_seasons.php?action=confirm_delete&tvshow_season_id=" . $tvshow_season_id,
            1,
            'delete_tvshow_season'
        );
    break;
    case 'confirm_delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $tvshow_season = new TVShow_Season($_REQUEST['tvshow_season_id']);
        if (!Catalog::can_remove($tvshow_season)) {
            debug_event('tvshow_season', 'Unauthorized to remove the tvshow `.' . $tvshow_season->id . '`.', 1);
            UI::access_denied();
            exit;
        }

        if ($tvshow_season->remove_from_disk()) {
            show_confirmation(T_('TVShow Season Deletion'), T_('TVShow Season has been deleted.'), AmpConfig::get('web_path'));
        } else {
            show_confirmation(T_('TVShow Season Deletion'), T_('Cannot delete this tvshow season.'), AmpConfig::get('web_path'));
        }
    break;
    case 'show':
        $season = new TVShow_Season($_REQUEST['season']);
        $season->format();
        $object_ids  = $season->get_episodes();
        $object_type = 'tvshow_episode';
        require_once AmpConfig::get('prefix') . UI::find_template('show_tvshow_season.inc.php');
        break;
} // end switch

UI::show_footer();
