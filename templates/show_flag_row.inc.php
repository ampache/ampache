<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
<tr id="flagged_<?php echo $flag->id; ?>" class="<?php echo flip_class(); ?>">
	<td class="cel_object"><?php echo $flag->f_name; ?></td>
	<td class="cel_username"><?php echo $flag->f_user; ?></td>
	<td class="cel_flag"><?php $flag->print_flag(); ?></td>
	<td class="cel_comment"><?php echo scrub_out($flag->comment); ?></td>
	<td class="cel_status"><?php $flag->print_status(); ?></td>
	<td class="cel_action">
	<?php if ($flag->approved) { ?>
		<?php echo Ajax::button('?page=flag&action=reject&flag_id=' . $flag->id,'disable',_('Reject'),'reject_flag_' . $flag->id); ?>
	<?php } else { ?>
		<?php echo Ajax::button('?page=flag&action=accept&flag_id=' . $flag->id,'enable',_('Enable'),'enable_flag_' . $flag->id); ?>
	<?php } ?>
	</td>
</tr>
