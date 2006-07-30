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

$web_path = conf('web_path'); 
$localplay = init_localplay();

$required_info 	= "&amp;user_id=" . $GLOBALS['user']->id . "&amp;sessid=" . session_id(); 
$ajax_url	= $web_path . '/server/ajax.server.php';
$status		= $localplay->status();

/* Format the track name */
$track_name = $status['track_artist'] . ' - ' . $status['track_album'] . ' - ' . $status['track_title'];

/* This is a cheezball fix for when we were unable to find a
 * artist/album (or one wasn't provided)
 */
$track_name = ltrim(ltrim($track_name,' - '));

?>
<div class="text-box">
<?php echo _('State') .": ". ucfirst($status['state']); ?><br />
<?php echo _('Repeat') . ":" . print_boolean($status['repeat']); ?>&nbsp;|&nbsp;
<?php echo _('Random') . ":" . print_boolean($status['random']); ?><br />
<?php echo _('Volume') . ":" . $status['volume']; ?><br />
<br />
<span class="header2"><?php echo _('Now Playing') . '</span><br />[' . $status['track'] . '] - ' . $track_name . '<br />'; ?>
</div>
