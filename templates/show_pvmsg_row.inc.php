<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
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
 */

?>
<td class="cel_select"><input type="checkbox" name="pvmsg_select[]" value="<?php echo $libitem->id; ?>" title="<?php echo T_('Select'); ?>" /></td>
<td class="cel_subject"><?php echo $libitem->f_link; ?></td>
<td class="cel_from_user"><?php echo $libitem->f_from_user_link; ?></td>
<td class="cel_to_user"><?php echo $libitem->f_to_user_link; ?></td>
<td class="cel_creation_date"><?php echo $libitem->f_creation_date; ?></td>
<td class="cel_action">
<a id="<?php echo 'reply_pvmsg_'.$libitem->id ?>" href="<?php echo AmpConfig::get('web_path'); ?>/pvmsg.php?action=show_add_message&reply_to=<?php echo $libitem->id; ?>">
    <?php echo UI::get_icon('mail', T_('Reply')); ?>
</a>
<a id="<?php echo 'delete_pvmsg_'.$libitem->id ?>" href="<?php echo AmpConfig::get('web_path'); ?>/pvmsg.php?action=delete&msgs=<?php echo $libitem->id; ?>">
    <?php echo UI::get_icon('delete', T_('Delete')); ?>
</a>
</td>
