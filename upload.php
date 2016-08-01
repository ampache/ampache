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

if (!AmpConfig::get('allow_upload') || !Access::check('interface', '25')) {
    UI::access_denied();
    exit;
}

$upload_max = return_bytes(ini_get('upload_max_filesize'));
$post_max   = return_bytes(ini_get('post_max_size'));
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
        require AmpConfig::get('prefix') . UI::find_template('show_add_upload.inc.php');
        break;
} // switch on the action

UI::show_footer();
