<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; Version 2 of the
 licence.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/
?>
<div id="play_type_switch">
<?php 
$name = "is_" . Config::get('play_type');
${$name} = 'selected="selected" ';

if (Preference::has_access('play_type')) {
?>
<form method="post" id="play_type_form" action="javascript.void(0);">
<select id="play_type_select" name="type"> 
	<?php if (Config::get('allow_stream_playback')) { ?>
		<option value="stream" <?php echo $is_stream; ?>><?php echo _('Stream'); ?></option>
	<?php } if (Config::get('allow_localplay_playback')) { ?>
		<option value="localplay" <?php echo $is_localplay; ?>><?php echo _('Localplay'); ?></option>
	<?php } if (Config::get('allow_democratic_playback')) { ?>
		<option value="democratic" <?php echo $is_democratic; ?>><?php echo _('Democratic'); ?></option>
	<?php } ?>
	<option value="xspf_player" <?php echo $is_xspf_player; ?>><?php echo _('Flash Player'); ?></option>
</select>
<?php echo Ajax::observe('play_type_select','change',Ajax::action('?page=stream&action=set_play_type','play_type_select','play_type_form'),'1'); ?>
</form>
<?php
} // if they have access
// Else just show what it currently is
else { echo ucwords(Config::get('play_type')); }
?>
</div>
