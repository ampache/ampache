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
    <form method="post" id="edit_tag_<?php echo $libitem->id; ?>" class="edit_dialog_content">
        <table class="tabledata">
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Name') ?></td>
                <td><input type="text" name="name" value="<?php echo scrub_out($libitem->name); ?>" autofocus /></td>
            </tr>
            <tr><td>&nbsp;</td></tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Re-tag') ?></td>
                <td><input type="text" name="edit_tags" id="edit_tags" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Persistent') ?></td>
                <td><input type="checkbox" name="merge_persist" value="1" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Keep Existing') ?></td>
                <td><input type="checkbox" name="keep_existing" value="1" /></td>
            </tr>
        </table>
        <input type="hidden" name="id" value="<?php echo $libitem->id; ?>" />
        <input type="hidden" name="type" value="tag_row" />
    </form>
</div>
