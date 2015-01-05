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

if (!AmpConfig::get('allow_upload')) {
    UI::access_denied();
    exit;
}

/* Switch on the action passed in */
switch ($_REQUEST['actionp']) {
    case 'upload':
        if (AmpConfig::get('demo_mode')) {
            UI::access_denied();
            exit;
        }

        Upload::process();
        exit;

    default:
        UI::show_header();
        require AmpConfig::get('prefix') . '/templates/show_add_upload.inc.php';
        break;
} // switch on the action

UI::show_footer();
