<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */ ?>
<div>
    <form method="post" id="edit_channel_<?php echo $libitem->id; ?>" class="edit_dialog_content">
        <table class="tabledata">
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Stream Source') ?></td>
                <td><select name="object_id" autofocus>
<?php
                        $playlists = Playlist::get_playlists();
                        foreach ($playlists as $playlist_id) {
                            $playlist = new Playlist($playlist_id);
                            $playlist->format();
                            echo "<option value='" . $playlist->id . "'";
                            if ($playlist->id == $libitem->object_id) {
                                echo " selected";
                            }
                            echo ">" . $playlist->f_name . "</option>";
                        } ?>
                </select></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Name') ?></td>
                <td><input type="text" name="name" value="<?php echo scrub_out($libitem->name); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Description') ?></td>
                <td><input type="text" name="description" value="<?php echo scrub_out($libitem->description); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('URL') ?></td>
                <td><input type="text" name="url" value="<?php echo scrub_out($libitem->url); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Interface') ?></td>
                <td><input type="text" name="interface" value="<?php echo scrub_out($libitem->interface); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Port') ?></td>
                <td><input type="text" name="port" value="<?php echo scrub_out($libitem->port); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"></td>
                <td><input type="checkbox" name="private" value="1" <?php echo ($libitem->is_private) ? 'checked' : ''; ?> /> <?php echo T_('Authentication Required') ?></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"></td>
                <td><input type="checkbox" name="random" value="1" <?php echo ($libitem->random) ? 'checked' : ''; ?> /> <?php echo T_('Random') ?></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"></td>
                <td><input type="checkbox" name="loop" value="1" <?php echo ($libitem->loop) ? 'checked' : ''; ?> /> <?php echo T_('Loop') ?></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Max Listeners') ?></td>
                <td><input type="text" name="max_listeners" value="<?php echo scrub_out($libitem->max_listeners); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Stream Type') ?></td>
                <td><input type="text" name="stream_type" value="<?php echo scrub_out($libitem->stream_type); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Bitrate') ?></td>
                <td><input type="text" name="bitrate" value="<?php echo scrub_out($libitem->bitrate); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Genres') ?></td>
                <td><input type="text" name="edit_tags" id="edit_tags" value="<?php echo Tag::get_display($libitem->tags); ?>" /></td>
            </tr>
        </table>
        <input type="hidden" name="id" value="<?php echo $libitem->id; ?>" />
        <input type="hidden" name="type" value="channel_row" />
    </form>
</div>
