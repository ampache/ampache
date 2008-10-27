<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation. 

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
?>
<td colspan="6">
<form method="post" id="edit_live_stream_<?php echo $radio->id; ?>">
<table class="inline-edit" cellpadding="3" cellspacing="0">
<tr>
	<th><?php echo _('Name'); ?></th>
	<th><?php echo _('Stream URL'); ?></th>
	<th><?php echo _('Homepage'); ?></th>
	<th><?php echo _('Callsign'); ?></th>
	<th><?php echo _('Frequency'); ?></th>
	<th>&nbsp;</th>
</tr>
<tr>
<td>
	<input type="text" name="name" value="<?php echo scrub_out($radio->name); ?>" size="9" />
</td>
<td>
	<input type="text" name="url" value="<?php echo scrub_out($radio->url); ?>" size ="12" />
</td>
<td>
	<input type="text" name="site_url" value="<?php echo scrub_out($radio->site_url); ?>" size="9" />
</td>
<td>
	<input type="text" name="call_sign" value="<?php echo scrub_out($radio->call_sign); ?>" size="6" />
</td>
<td>
	<input type="text" name="frequency" value="<?php echo scrub_out($radio->frequency); ?>" size="6" />
</td>
<td>
	<input type="hidden" name="id" value="<?php echo $radio->id; ?>" />
	<input type="hidden" name="type" value="live_stream" />
	<?php echo Ajax::button('?action=edit_object&id=' . $radio->id . '&type=live_stream','download',_('Save Changes'),'save_live_stream_' . $radio->id,'edit_live_stream_' . $radio->id); ?>
</td>
</tr>
</table>
</form>
</td>
