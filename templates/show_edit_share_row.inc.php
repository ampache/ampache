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
<div>
    <form method="post" id="edit_share_<?php echo $libitem->id; ?>" class="edit_dialog_content">
        <table class="tabledata" cellspacing="0" cellpadding="0">
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Shared Object') ?></td>
                <td><?php echo $libitem->f_object_link; ?></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Max Counter') ?></td>
                <td><input type="text" name="max_counter" value="<?php echo scrub_out($libitem->max_counter); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Expiry Days') ?></td>
                <td><input type="text" name="expire" value="<?php echo scrub_out($libitem->expire_days); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"></td>
                <td><input type="checkbox" name="allow_stream" value="1" <?php echo ($libitem->allow_stream) ? 'checked' : ''; ?> /> <?php echo T_('Allow Stream') ?></td>
            </tr>
<?php if ((($libitem->object_type == 'song' || $libitem->object_type == 'video') && Access::check_function('download')) || Access::check_function('batch_download')) { ?>
            <tr>
                <td class="edit_dialog_content_header"></td>
                <td><input type="checkbox" name="allow_download" value="1" <?php echo ($libitem->allow_download) ? 'checked' : ''; ?> /> <?php echo T_('Allow Download') ?></td>
            </tr>
<?php } ?>
        </table>
        <input type="hidden" name="id" value="<?php echo $libitem->id; ?>" />
        <input type="hidden" name="type" value="share_row" />
    </form>
</div>
