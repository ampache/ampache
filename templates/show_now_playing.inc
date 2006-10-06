<?php
/*

Copyright (c) 2001 - 2006 Ampache.org
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
?>
<table class="box" cellspacing="0" cellpadding="0" style="width:625px;">
<tr>
        <td class="box-left-top"></td>
        <td class="box-top"></td>
        <td class="box-right-top"></td>
</tr>
<tr>
        <td class="box-left" rowspan="2"></td>
        <td class="box-title"><?php echo _('Now Playing'); ?></td>
        <td class="box-right" rowspan="2"></td>
</tr>
<tr><td style="padding-top:3px;">
<?php 
foreach ($results as $item) {
	$song = $item['song'];
	$np_user = $item['user'];

	/* If we've gotten a non-song object just skip this row */
	if (!is_object($song)) { continue; }
	if (!$np_user->fullname) { $np_user->fullname = "Ampache User"; }

	require(conf('prefix') . '/templates/show_now_playing_row.inc.php');

} // end foreach
?>
</td></tr>
<tr>
        <td class="box-left-bottom"></td>
        <td class="box-bottom" style="width:100%;"></td>
        <td class="box-right-bottom"></td>
</tr>
</table>
<?php } // end if count results ?>
