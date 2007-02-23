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
?>
<?php 
$name = "is_" . $GLOBALS['user']->prefs['play_type'];
${$name} = 'selected="selected" ';
if (has_preference_access('play_type')){
?>
<!--<select id="play_type_switch" style="font-size:0.9em;" name="type"> -->
<select id="play_type_switch" name="type" onchange="ajaxPut('<?php echo $ajax_url; ?>?action=change_play_type<?php echo $required_info; ?>');return true;"> 
	<?php if (conf('allow_stream_playback')) { ?>
		<option value="stream" <?php echo $is_stream; ?>><?php echo _('Stream'); ?></option>
	<?php } if (conf('allow_localplay_playback')) { ?>
		<option value="localplay" <?php echo $is_localplay; ?>><?php echo _('Localplay'); ?></option>
	<?php } if (conf('allow_downsample_playback')) { ?>
		<option value="downsample" <?php echo $is_downsample; ?>><?php echo _('Downsample'); ?></option>
	<?php } if (conf('allow_democratic_playback')) { ?>
		<option value="democratic" <?php echo $is_democratic; ?>><?php echo _('Democratic'); ?></option>
	<?php } ?>
	<option value="xspf_player" <?php echo $is_xspf_player; ?>><?php echo _('XSPF Player'); ?></option>
</select>
<?php
} else { echo ucwords($GLOBALS['user']->prefs['play_type']);}
