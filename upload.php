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

if (!AmpConfig::get('allow_upload') || !Access::check('interface', '25')) {
    UI::access_denied();
    exit;
}

$upload_max = return_bytes(ini_get('upload_max_filesize'));
$post_max = return_bytes(ini_get('post_max_size'));
if ($post_max > 0 && ($post_max < $upload_max || $upload_max == 0)) {
    $upload_max = $post_max;
}
// Check to handle POST requests exceeding max post size.
if ($_SERVER['CONTENT_LENGTH'] > 0 && $post_max > 0 && $_SERVER['CONTENT_LENGTH'] > $post_max) {
    Upload::rerror();
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
