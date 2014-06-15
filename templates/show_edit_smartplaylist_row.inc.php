<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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
<div>
    <form method="post" id="edit_playlist_<?php echo $playlist->id; ?>" class="edit_dialog_content">
        <table class="tabledata" cellspacing="0" cellpadding="0">
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Name') ?></td>
                <td><input type="text" name="name" value="<?php echo scrub_out($playlist->name); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Type') ?></td>
                <td>
                    <?php $name = 'select_' . $playlist->type; ?>
                    <?php ${$name} = ' selected="selected"'; ?>
                    <select name="pl_type">
                        <option value="public"<?php echo $select_public; ?>><?php echo T_('Public'); ?></option>
                        <option value="private"<?php echo $select_private; ?>><?php echo T_('Private'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Random') ?></td>
                <td><input type="checkbox" name="random" value="1" <?php if ($playlist->random) echo "checked"; ?> /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Item Limit (0 = unlimited)') ?></td>
                <td><input type="text" name="limit" value="<?php echo scrub_out($playlist->limit); ?>" /></td>
            </tr>
        </table>
        <input type="hidden" name="id" value="<?php echo $playlist->id; ?>" />
        <input type="hidden" name="type" value="smartplaylist_row" />
    </form>
</div>
