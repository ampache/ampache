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
    <form method="post" id="edit_channel_<?php echo $channel->id; ?>" class="edit_dialog_content">
        <table class="tabledata" cellspacing="0" cellpadding="0">
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Name') ?></td>
                <td><input type="text" name="name" value="<?php echo scrub_out($channel->name); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Description') ?></td>
                <td><input type="text" name="description" value="<?php echo scrub_out($channel->description); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Url') ?></td>
                <td><input type="text" name="url" value="<?php echo scrub_out($channel->url); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Interface') ?></td>
                <td><input type="text" name="interface" value="<?php echo scrub_out($channel->interface); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Port') ?></td>
                <td><input type="text" name="port" value="<?php echo scrub_out($channel->port); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"></td>
                <td><input type="checkbox" name="private" value="1" <?php echo ($channel->is_private) ? 'checked' : ''; ?> /> <?php echo T_('Authentication Required') ?></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"></td>
                <td><input type="checkbox" name="random" value="1" <?php echo ($channel->random) ? 'checked' : ''; ?> /> <?php echo T_('Random') ?></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"></td>
                <td><input type="checkbox" name="loop" value="1" <?php echo ($channel->loop) ? 'checked' : ''; ?> /> <?php echo T_('Loop') ?></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Max Listeners') ?></td>
                <td><input type="text" name="max_listeners" value="<?php echo scrub_out($channel->max_listeners); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Stream Type') ?></td>
                <td><input type="text" name="stream_type" value="<?php echo scrub_out($channel->stream_type); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Bitrate') ?></td>
                <td><input type="text" name="bitrate" value="<?php echo scrub_out($channel->bitrate); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Genre') ?></td>
                <td><input type="text" name="edit_tags" id="edit_tags" value="<?php echo Tag::get_display($channel->tags); ?>" /></td>
            </tr>
        </table>
        <input type="hidden" name="id" value="<?php echo $channel->id; ?>" />
        <input type="hidden" name="type" value="channel_row" />
    </form>
</div>
