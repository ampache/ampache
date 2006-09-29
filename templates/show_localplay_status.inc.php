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
<strong><?php echo $localplay->get_user_state($status['state']) ?></strong><br />
&nbsp;&nbsp;<?php echo '[' . $status['track'] . '] - ' . $track_name . '<br />'; ?>
<div align="center"><?php require (conf('prefix') . '/templates/show_localplay_control.inc.php'); ?></div>
<div align="center">
	<?php if ($localplay->has_function('volume_up')) { ?>
	<span class="up_button" onclick="ajaxPut('<?php echo $ajax_url; ?>?action=localplay&amp;cmd=volume_up<?php echo $required_info; ?>','localplay_state');return true;">
	        <img src="<?php echo $web_path; ?>/images/localplay/volup.gif" alt="volume up" />
	</span>
	<?php } ?>
	<?php if ($localplay->has_function('volume_down')) { ?>
	<span class="down_button" onclick="ajaxPut('<?php echo $ajax_url; ?>?action=localplay&amp;cmd=volume_down<?php echo $required_info; ?>','localplay_state');return true;">
	        <img src="<?php echo $web_path; ?>/images/localplay/voldn.gif" alt="volume down" />
	</span>
	<?php } ?>
	<?php if ($localplay->has_function('volume_mute')) { ?>
	<span class="mute_button" onclick="ajaxPut('<?php echo $ajax_url; ?>?action=localplay&amp;cmd=volume_mute<?php echo $required_info; ?>','localplay_state');return true;">
	        <img src="<?php echo $web_path; ?>/images/localplay/volmute.gif" alt="volume mute" />
	</span>
	<?php } ?>
	<br />
	<?php echo _('Volume') . ":" . $status['volume']; ?>
</div>
<br />
<?php if ($localplay->has_function('repeat')) { ?>
	<?php echo _('Repeat') . ":" . print_boolean($status['repeat']); ?> | 
	<a href="<?php echo $web_path; ?>/localplay.php?action=repeat&amp;value=<?php echo invert_boolean($status['repeat']); ?>">
		<?php echo print_boolean(invert_boolean($status['repeat'])); ?>
	</a><br />
	<?php } ?>
<?php if ($localplay->has_function('random')) { ?>
	<?php echo _('Random') . ":" . print_boolean($status['random']); ?> | 
	<a href="<?php echo $web_path; ?>/localplay.php?action=random&amp;value=<?php echo invert_boolean($status['random']); ?>">
		<?php echo print_boolean(invert_boolean($status['random'])); ?>
	</a><br />
<?php } ?>
