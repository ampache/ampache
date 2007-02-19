<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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

$web_path = conf('web_path');
?>
<form id="songs" method="post" enctype="multipart/form-data" action="<?php echo conf('web_path'); ?>/admin/flag.php?action=reject_flags">
<table class="tabledata" cellspacing="0" cellpadding="0">
<tr class="table-header">
	<th><a href="#" onclick="check_select('song'); return false;"><?php echo _('Select'); ?></a></th>
	<th><?php echo _('Object'); ?></th>
	<th><?php echo _('User'); ?></th>
	<th><?php echo _('Flag'); ?></th>
	<th><?php echo _('Comment'); ?></th>
	<th><?php echo _('Status'); ?></th>
	<th><?php echo _('Action'); ?></th>
</tr>
<?php foreach ($flagged as $data) { $flag = new Flag($data); ?>
<tr class="<?php echo flip_class(); ?>">
	<td align="center">
		<input type="checkbox" name="song[]" value="<?php echo $flag->id; ?>" id="song_<?php echo $flag->id; ?>" />
	</td>
	<td><a href="<?php echo conf('web_path'); ?>/admin/flag.php?action=show_edit_song&song=<?php echo $flag->object_id; ?>"><?php $flag->print_name(); ?></a></td>
	<td><?php echo scrub_out($flag->f_user_username); ?></td>
	<td><?php $flag->print_flag(); ?></td>
	<td><?php echo scrub_out($flag->comment); ?></td>
	<td><?php $flag->print_status(); ?></td>
	<td align="center">
	<?php if ($flag->approved) { ?>
		<a href="<?php echo $web_path; ?>/admin/flag.php?action=reject_flag&amp;flag_id=<?php echo $flag->id; ?>">
			<?php echo get_user_icon('disable'); ?>
		</a>
	<?php } else { ?>
		<a href="<?php echo $web_path; ?>/admin/flag.php?action=approve_flag&amp;flag_id=<?php echo $flag->id; ?>">
			<?php echo get_user_icon('enable'); ?>
		</a>
	<?php } ?>
	</td>
</tr>
<?php } if (!count($flagged)) { ?>
<tr class="<?php echo flip_class(); ?>">
	<td colspan="7" class="error"><?php echo _('No Records Found'); ?></td>
</tr>
<?php } ?>
<tr class="<?php echo flip_class(); ?>">
	<td colspan="7">
		<select name="update_action">
			<option value="reject"><?php echo _('Reject'); ?></option>
			<option value="approve"><?php echo _('Approve'); ?></option>
		</select>
		<input class="button" type="submit" value="<?php echo _('Update'); ?>" />
	</td>
</tr>
</table>
</form>
<div class="text-action">
<a href="<?php echo $web_path; ?>/admin/flag.php?action=show_flagged">
	<?php echo _('Show All'); ?>...
</a>
</div>
