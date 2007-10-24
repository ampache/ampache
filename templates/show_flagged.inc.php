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

$web_path = Config::get('web_path');
?>
<form id="songs" method="post" enctype="multipart/form-data" action="<?php echo Config::get('web_path'); ?>/admin/flag.php?action=reject_flags">
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_select" />
  <col id="col_object" />
  <col id="col_username" />
  <col id="col_flag" />
  <col id="col_comment" />
  <col id="col_status" />
  <col id="col_action" />
</colgroup>
<tr class="th-top">
	<th class="cel_select"><a href="#" onclick="check_select('song'); return false;"><?php echo _('Select'); ?></a></th>
	<th class="cel_object"><?php echo _('Object'); ?></th>
	<th class="cel_username"><?php echo _('User'); ?></th>
	<th class="cel_flag"><?php echo _('Flag'); ?></th>
	<th class="cel_comment"><?php echo _('Comment'); ?></th>
	<th class="cel_status"><?php echo _('Status'); ?></th>
	<th class="cel_action"><?php echo _('Action'); ?></th>
</tr>
<?php foreach ($flagged as $data) { $flag = new Flag($data); ?>
<tr class="<?php echo flip_class(); ?>">
	<td class="cel_select">
		<input type="checkbox" name="song[]" value="<?php echo $flag->id; ?>" id="song_<?php echo $flag->id; ?>" />
	</td>
	<td class="cel_object"><a href="<?php echo Config::get('web_path'); ?>/admin/flag.php?action=show_edit_song&song=<?php echo $flag->object_id; ?>"><?php $flag->print_name(); ?></a></td>
	<td class="cel_username"><?php echo scrub_out($flag->f_user_username); ?></td>
	<td class="cel_flag"><?php $flag->print_flag(); ?></td>
	<td class="cel_comment"><?php echo scrub_out($flag->comment); ?></td>
	<td class="cel_status"><?php $flag->print_status(); ?></td>
	<td class="cel_action">
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
<tr class="th-bottom">
	<th class="cel_select"><a href="#" onclick="check_select('song'); return false;"><?php echo _('Select'); ?></a></th>
	<th class="cel_object"><?php echo _('Object'); ?></th>
	<th class="cel_username"><?php echo _('User'); ?></th>
	<th class="cel_flag"><?php echo _('Flag'); ?></th>
	<th class="cel_comment"><?php echo _('Comment'); ?></th>
	<th class="cel_status"><?php echo _('Status'); ?></th>
	<th class="cel_action"><?php echo _('Action'); ?></th>
</tr>
</table>
</form>
<div class="text-action">
<a href="<?php echo $web_path; ?>/admin/flag.php?action=show_flagged">
	<?php echo _('Show All'); ?>...
</a>
</div>
