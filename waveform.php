<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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

/**
 * Album Art
 * This pulls album art out of the file using the getid3 library
 * and dumps it to the browser as an image mime type.
 *
 */

require_once 'lib/init.php';

if (!AmpConfig::get('waveform')) {
    exit();
}

// Prevent user from aborting script
ignore_user_abort(true);
set_time_limit(300);

// Write/close session data to release session lock for this script.
// This to allow other pages from the same session to be processed
// Warning: Do not change any session variable after this call
session_write_close();

$id       = $_REQUEST['song_id'];
$waveform = Waveform::get($id);
if ($waveform) {
    header('Content-type: image/png');
    echo $waveform;
}
