<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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

/**
 * action switch
 */
switch ($_REQUEST['action']) {
    case 'search':
        $browse = new Browse();
        require_once AmpConfig::get('prefix') . '/templates/show_search_form.inc.php';
        require_once AmpConfig::get('prefix') . '/templates/show_search_options.inc.php';
        $results = Search::run($_REQUEST);
        $browse->set_type($_REQUEST['type']);
        $browse->show_objects($results);
        $browse->store();
    break;
    case 'save_as_track':
        $playlist_id = save_search($_REQUEST);
        $playlist = new Playlist($playlist_id);
        show_confirmation(T_('Search Saved'),sprintf(T_('Your Search has been saved as a track in %s'), $playlist->name), AmpConfig::get('web_path') . "/search.php");
    break;
    case 'save_as_smartplaylist':
        $playlist = new Search();
        $playlist->parse_rules(Search::clean_request($_REQUEST));
        $playlist->save();
        show_confirmation(T_('Search Saved'),sprintf(T_('Your Search has been saved as a Smart Playlist with name %s'), $playlist->name), AmpConfig::get('web_path') . "/browse.php?action=smartplaylist");
    break;
    case 'descriptor':
        // This is a little special we don't want header/footers so trash what we've got in the OB
        ob_clean();
        require_once AmpConfig::get('prefix') . '/templates/show_search_descriptor.inc.php';
        exit;
    default:
        require_once AmpConfig::get('prefix') . '/templates/show_search_form.inc.php';
    break;
}

/* Show the Footer */
UI::show_footer();
