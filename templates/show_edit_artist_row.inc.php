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
    <form method="post" id="edit_artist_<?php echo $libitem->id; ?>" class="edit_dialog_content">
        <table class="tabledata" cellspacing="0" cellpadding="0">
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Name') ?></td>
                <td><input type="text" name="name" value="<?php echo scrub_out($libitem->f_full_name); ?>" autofocus /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('MusicBrainz ID') ?></td>
                <td>
                    <?php if (Access::check('interface', 50)) { ?>
                    <input type="text" name="mbid" value="<?php echo $libitem->mbid; ?>" />
                    <?php } else { ?>
                    <?php echo $libitem->mbid; ?>
                    <?php } ?>
                </td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Summary') ?></td>
                <td><textarea name="summary" cols="44" rows="4"><?php echo scrub_out(trim($libitem->summary)); ?></textarea></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Place Formed') ?></td>
                <td><input type="text" name="placeformed" value="<?php echo scrub_out($libitem->placeformed); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Year Formed') ?></td>
                <td><input type="text" name="yearformed" value="<?php echo scrub_out($libitem->yearformed); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Tags') ?></td>
                <td><input type="text" name="edit_tags" id="edit_tags" value="<?php echo Tag::get_display($libitem->tags); ?>" /></td>
            </tr>
            <?php if (AmpConfig::get('label')) { ?>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Labels') ?></td>
                <td><input type="text" name="edit_labels" id="edit_labels" value="<?php echo Label::get_display($libitem->labels); ?>" /></td>
            </tr>
            <?php } ?>
            <tr>
                <td class="edit_dialog_content_header"></td>
                <td><input type="checkbox" name="overwrite_childs" value="checked" />&nbsp;<?php echo T_('Overwrite tags of sub albums and sub songs') ?></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"></td>
                <td><input type="checkbox" name="add_to_childs" value="checked" />&nbsp;<?php echo T_('Add tags to sub albums and sub songs') ?></td>
            </tr>
        </table>
        <input type="hidden" name="id" value="<?php echo $libitem->id; ?>" />
        <input type="hidden" name="type" value="artist_row" />
    </form>
</div>
