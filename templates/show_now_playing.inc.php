<?php
/*

Copyright (c) Ampache.org
All rights reserved.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/**
 * This is the now playing container, it holds the master div for now playing
 * and loops through what's current playing as passed and includes
 * the now_playing_row's This will display regardless, but potentially
 * goes all ajaxie if you've got javascript on
 */

if (count($results)) {
$link = Config::get('use_rss') ? ' ' . AmpacheRSS::get_display('nowplaying') : ''; 
?>
<?php show_box_top(_('Now Playing') . $link); ?>
<?php 
foreach ($results as $item) {
	$media = $item['media'];
	$np_user = $item['client'];
	$agent = $item['agent'];

	/* If we've gotten a non-song object just skip this row */
	if (!is_object($media)) { continue; }
	if (!$np_user->fullname) { $np_user->fullname = "Ampache User"; }
?>
<div class="np_row">
<?php require Config::get('prefix') . '/templates/show_now_playing_row.inc.php'; ?>
</div>
<?php
} // end foreach
?>
<?php show_box_bottom(); ?>
<?php } // end if count results ?>
