<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Show Shout Row
 *
 * PHP version 5
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @category	Template
 * @package	Template
 * @author	Karl Vollmer <vollmer@ampache.org>
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @version	PHP 5.2
 * @link	http://www.ampache.org/
 * @since	File available since Release 1.0
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
                <?php echo get_user_icon('edit', _('Edit')); ?>
                </a>

                <a href="<?php echo $web_path; ?>/admin/shout.php?action=delete&amp;shout_id=<?php echo $shout->id; ?>">
                <?php echo get_user_icon('delete', _('Delete')); ?>
                </a>
	</td>
</tr>
