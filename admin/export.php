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

require_once '../lib/init.php';

if (!Access::check('interface','100')) {
    UI::access_denied();
    exit;
}

UI::show_header();

/* Switch on Action */
switch ($_REQUEST['action']) {
    case 'export':

        // This may take a while
        set_time_limit(0);

        // Clear everything we've done so far
        ob_end_clean();

        // This will disable buffering so contents are sent immediately to browser.
        // This is very useful for large catalogs because it will immediately display the download dialog to user,
        // instead of waiting until contents are generated, which could take a long time.
        ob_implicit_flush(true);

        header("Content-Transfer-Encoding: binary");
        header("Cache-control: public");

        $date = date("d/m/Y",time());

        switch ($_REQUEST['export_format']) {
            case 'itunes':
                header("Content-Type: application/itunes+xml; charset=utf-8");
                header("Content-Disposition: attachment; filename=\"ampache-itunes-$date.xml\"");
                Catalog::export('itunes', $_REQUEST['export_catalog']);
            break;
            case 'csv':
                header("Content-Type: application/vnd.ms-excel");
                header("Content-Disposition: filename=\"ampache-export-$date.csv\"");
                Catalog::export('csv', $_REQUEST['export_catalog']);
            break;
        } // end switch on format

        // We don't want the footer so we're done here
        exit;
    default:
        require_once AmpConfig::get('prefix') . '/templates/show_export.inc.php';
    break;
} // end switch on action

UI::show_footer();
