<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
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
 * action switch
 */
switch ($_REQUEST['action']) {
    case 'search':
        if ($_REQUEST['rule_1'] != 'missing_artist') {
            $browse = new Browse();
            require_once AmpConfig::get('prefix') . UI::find_template('show_search_form.inc.php');
            require_once AmpConfig::get('prefix') . UI::find_template('show_search_options.inc.php');
            $results = Search::run($_REQUEST);
            $browse->set_type($_REQUEST['type']);
            $browse->show_objects($results);
            $browse->store();
        } else {
            $wartists = Wanted::search_missing_artists($_REQUEST['rule_1_input']);
            require_once AmpConfig::get('prefix') . UI::find_template('show_missing_artists.inc.php');
            echo '<a href="http://musicbrainz.org/search?query=' . rawurlencode($_REQUEST['rule_1_input']) . '&type=artist&method=indexed" target="_blank">' . T_('View on MusicBrainz') . '</a><br />';
        }
    break;
    case 'save_as_smartplaylist':
        if (!Access::check('interface', 25)) {
            UI::access_denied();
            exit();
        }
        $playlist = new Search();
        $playlist->parse_rules(Search::clean_request($_REQUEST));
        $playlist->save();
        show_confirmation(T_('Search Saved'), sprintf(T_('Your Search has been saved as a Smart Playlist with name %s'), $playlist->name), AmpConfig::get('web_path') . "/browse.php?action=smartplaylist");
    break;
    case 'descriptor':
        // This is a little special we don't want header/footers so trash what we've got in the OB
        ob_clean();
        require_once AmpConfig::get('prefix') . UI::find_template('show_search_descriptor.inc.php');
        exit;
    default:
        require_once AmpConfig::get('prefix') . UI::find_template('show_search_form.inc.php');
    break;
}

/* Show the Footer */
UI::show_footer();
