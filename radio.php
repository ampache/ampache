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

// Switch on Action
switch ($_REQUEST['action']) {
    case 'show_create':
        if (!Access::check('interface', 75)) {
            UI::access_denied();
            exit;
        }

        require_once AmpConfig::get('prefix') . '/templates/show_add_live_stream.inc.php';

    break;
    case 'create':
        if (!Access::check('interface', 75) || AmpConfig::get('demo_mode')) {
            UI::access_denied();
            exit;
        }

        if (!Core::form_verify('add_radio','post')) {
            UI::access_denied();
            exit;
        }

        // Try to create the sucker
        $results = Live_Stream::create($_POST);

        if (!$results) {
            require_once AmpConfig::get('prefix') . '/templates/show_add_live_stream.inc.php';
        } else {
            $body = T_('Radio Station Added');
            $title = '';
            show_confirmation($title,$body,AmpConfig::get('web_path') . '/browse.php?action=live_stream');
        }
    break;
} // end data collection

UI::show_footer();
