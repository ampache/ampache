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
?>
<table class="tabledata" cellpadding="0" cellspacing="0" border="0">
<tr class="table-header">
	<th align="center"><?php echo _('Fullname'); ?> (<?php echo _('Username'); ?>)</th>
        <th align="center"><?php echo _('Last Seen'); ?></th>
        <th align="center"><?php echo _('Activity'); ?></th>
        <th align="center"><?php echo _('Action'); ?></th>
</tr>
<?php foreach ($users as $user_id) { $data = new User($user_id); $data->format_user(); ?>
<tr class="<?php echo flip_class(); ?>">
	<td>
		<a href="<?php echo $web_path; ?>/admin/users.php?action=show_edit&amp;user=<?php echo scrub_out($data->id); ?>">
		<?php echo scrub_out($data->fullname); ?> (<?php echo scrub_out($data->username); ?>)</a>
	</td>
        <td align="center">
		<?php echo scrub_out($data->f_last_seen); ?>
	</td>
	<td>
		<?php echo scrub_out($data->f_useage); ?>
	</td>
        <td>
		<a href="<?php echo $web_path; ?>/admin/users.php?action=edit&amp;user=<?php echo scrub_out($data->id); ?>">
			<?php echo _('Edit'); ?>
		</a>
	</td>
</tr>
<?php } if (!count($users)) { ?>
<tr>
	<td colspan="4"><?php echo _('No Records Found'); ?></td>
</tr>
<?php } ?>
</table>
