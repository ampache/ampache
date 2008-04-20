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
<tr id="flagged_<?php echo $shout->id; ?>" class="<?php echo flip_class(); ?>">
	<td class="cel_object"><?php echo $object->f_link; ?></td>
	<td class="cel_username"><?php echo $client->f_link; ?></td>
	<td class="cel_sticky"><?php echo $shout->sticky; ?></td>
	<td class="cel_comment"><?php echo scrub_out($shout->text); ?></td>
	<td class="cel_date"><?php echo $shout->date; ?></td>
	<td class="cel_action">

                <a href="<?php echo $web_path; ?>/admin/shout.php?action=show_edit&amp;shout_id=<?php echo $shout->id; ?>">
                <?php echo get_user_icon('edit'); ?>
                </a>

                <a href="<?php echo $web_path; ?>/admin/shout.php?action=delete&amp;shout_id=<?php echo $shout->id; ?>">
                <?php echo get_user_icon('delete'); ?>
                </a>
	</td>
</tr>
