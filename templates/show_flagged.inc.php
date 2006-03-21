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
<table class="border" cellspacing="0" cellpadding="0">
<tr class="table-header">
	<th><?php echo _('Object'); ?></th>
	<th><?php echo _('Flag'); ?></th>
	<th><?php echo _('Status'); ?></th>
	<th><?php echo _('Action'); ?></th>
</tr>
<?php foreach ($flagged as $flag_id) { $flag = new Flag($flag_id); ?>
<tr class="<?php echo flip_class(); ?>">
	<td><?php $flag->print_name(); ?></td>
	<td><?php $flag->print_flag(); ?></td>
	<td><?php $flag->print_status(); ?></td>
	<td>
	<?php if ($flag->approved) { ?>
		<a href="<?php echo $web_path; ?>/admin/flag.php?action=reject_flag&amp;flag_id=<?php echo $flag->id; ?>">
			<?php echo _('Reject'); ?>
		</a>
	<?php } else { ?>
		<a href="<?php echo $web_path; ?>/admin/flag.php?action=approve_flag&amp;flag_id=<?php echo $flag->id; ?>">
			<?php echo _('Approve'); ?>
		</a>
	<?php } ?>
	</td>
</tr>
<?php } ?>
</table>
