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

<p class="header1"><?php echo _('Edit Access List'); ?></p>

<form name="edit_access" method="post" enctype="multipart/form-data" action="<?php echo conf('web_path'); ?>/admin/access.php">
<table class="text-box">
<tr>
	<td><?php echo _('Name'); ?>: </td>
	<td><?php echo scrub_out($access->name); ?></td>
</tr>
<tr>
	<td><?php echo _('Start IP Address'); ?>:</td>
	<td>
		<input type="text" name="start" value="<?php echo int2ip($access->start); ?>" size="20" maxlength="15" />
	</td>
</tr>
<tr>
	<td><?php echo _('End IP Address'); ?>:</td>
	<td>
		<input type="text" name="end" value="<?php echo int2ip($access->end); ?>" size="20" maxlength="15" />
	</td>
</tr>
<tr>
	<td><?php echo _('Level'); ?>:</td>
	<td>
		<select name="level">
			<?php $name = 'level_' . $access->level; ${$name} = 'selected="selected"'; ?>
			<option value="5"  <?php echo $level_5;  ?>><?php echo _('Demo'); ?></option>
			<option value="25" <?php echo $level_25; ?>><?php echo _('Stream'); ?></option>
			<option value="50" <?php echo $level_50; ?>><?php echo _('Stream/Download'); ?></option>
			<option value="75" <?php echo $level_75; ?>><?php echo _('XML-RPC'); ?></option>
		</select>
	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>
		<input type="hidden" name="access_id" value="<?php echo scrub_out($access->id); ?>" />
		<input type="hidden" name="action" value="update_host" />
		<input type="submit" value="<?php echo _('Update'); ?>" />
	</td>
</tr>
</table>
</form>
