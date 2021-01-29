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
    <form method="post" id="edit_album_<?php echo $libitem->id; ?>" class="edit_dialog_content">
        <table class="tabledata">
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Name') ?></td>
                <td><input type="text" name="name" value="<?php echo scrub_out($libitem->full_name); ?>" autofocus /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Artist') ?></td>
                <td>
                    <?php
                        if (Access::check('interface', 50)) {
                            if ($libitem->artist_count == '1') {
                                show_artist_select('artist', $libitem->artist_id);
                            } else {
                                echo T_('Various');
                            }
                        } else {
                            echo $libitem->f_artist_name;
                        } ?>
                </td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Album Artist') ?></td>
                <td>
                    <?php
                        if (Access::check('interface', 50)) {
                            show_artist_select('album_artist', $libitem->album_artist, true, $libitem->id, true); ?>
                    <div id="album_artist_select_album_<?php echo $libitem->id ?>">
                        <?php echo Ajax::observe('album_artist_select_' . $libitem->id, 'change', 'check_inline_song_edit("album_artist", ' . $libitem->id . ')'); ?>
                    </div>
                    <?php
                        } else {
                            echo $libitem->f_album_artist_name;
                        } ?>
                </td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Year') ?></td>
                <td><input type="text" name="year" value="<?php echo scrub_out($libitem->year); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Disk') ?></td>
                <td><input type="text" name="disk" value="<?php echo scrub_out($libitem->disk); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('MusicBrainz ID') ?></td>
                <td>
                    <?php if (Access::check('interface', 50)) { ?>
                        <input type="text" name="mbid" value="<?php echo $libitem->mbid; ?>" />
                    <?php
                    } else {
                        echo $libitem->mbid;
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('MusicBrainz Release Group ID') ?></td>
                <td>
                <?php if (Access::check('interface', 50)) { ?>
                    <input type="text" name="mbid_group" value="<?php echo $libitem->mbid_group; ?>" />
                <?php
                    } else {
                        echo $libitem->mbid_group;
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Release Type') ?></td>
                <td><input type="text" name="release_type" value="<?php echo $libitem->release_type; ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Catalog Number') ?></td>
                <td><input type="text" name="catalog_number" value="<?php echo $libitem->catalog_number; ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Barcode') ?></td>
                <td><input type="text" name="barcode" value="<?php echo $libitem->barcode; ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Original Year') ?></td>
                <td><input type="text" name="original_year" value="<?php echo $libitem->original_year; ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Tags') ?></td>
                <td><input type="text" name="edit_tags" id="edit_tags" value="<?php echo Tag::get_display($libitem->tags); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"></td>
                <td><input type="checkbox" name="overwrite_childs" value="checked" />&nbsp;<?php echo T_('Overwrite tags of sub songs') ?></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"></td>
                <td><input type="checkbox" name="add_to_childs" value="checked" />&nbsp;<?php echo T_('Add tags to sub songs') ?></td>
            </tr>
        </table>
        <input type="hidden" name="id" value="<?php echo $libitem->id; ?>" />
        <input type="hidden" name="type" value="album_row" />
    </form>
</div>
