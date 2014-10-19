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

/**
 * Album Art
 * This pulls album art out of the file using the getid3 library
 * and dumps it to the browser as an image mime type.
 *
 */

require_once 'lib/init.php';

if (!AmpConfig::get('waveform')) exit();

// Prevent user from aborting script
ignore_user_abort(true);
set_time_limit(300);

// Write/close session data to release session lock for this script.
// This to allow other pages from the same session to be processed
// Do NOT change any session variable after this call
session_write_close();

$id = $_REQUEST['song_id'];
$waveform = Waveform::get($id);
if ($waveform) {
    header('Content-type: image/png');
    echo $waveform;
}
