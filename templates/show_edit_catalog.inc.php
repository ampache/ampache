<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

UI::show_box_top(sprintf(T_('Settings for %s') , $catalog->name . ' (' . $catalog->f_info . ')'), 'box box_edit_catalog');
?>
<form method="post" action="<?php echo AmpConfig::get('web_path'); ?>/admin/catalog.php" enctype="multipart/form-data">
    <table class="tabledata" cellspacing="0" cellpadding="0">
        <tr>
            <td><?php echo T_('Name'); ?>:</td>
            <td><input type="text" name="name" value="<?php echo scrub_out($catalog->name); ?>" autofocus></input></td>
            <td style="vertical-align:top; font-family: monospace;" rowspan="5">
                <strong><?php echo T_('Auto-inserted Fields'); ?>:</strong><br />
                <span class="format-specifier">%A</span>= <?php echo T_('album name'); ?><br />
                <span class="format-specifier">%a</span>= <?php echo T_('artist name'); ?><br />
                <span class="format-specifier">%c</span>= <?php echo T_('id3 comment'); ?><br />
                <span class="format-specifier">%T</span>= <?php echo T_('track number (padded with leading 0)'); ?><br />
                <span class="format-specifier">%t</span>= <?php echo T_('song title'); ?><br />
                <span class="format-specifier">%y</span>= <?php echo T_('year'); ?><br />
                <span class="format-specifier">%o</span>= <?php echo T_('other'); ?><br />
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Catalog Type'); ?></td>
            <td><?php echo scrub_out(ucfirst($catalog->catalog_type)); ?></td>
        </tr>
        <tr>
            <td><?php echo T_('Filename pattern'); ?>:</td>
            <td>
                <input type="text" name="rename_pattern" value="<?php echo scrub_out($catalog->rename_pattern); ?>" />
            </td>
        </tr>
        <tr>
            <td>
                <?php echo T_('Folder Pattern'); ?>:<br /><?php echo T_('(no leading or ending \'/\')'); ?>
            </td>
            <td>
                <input type="text" name="sort_pattern" value="<?php echo scrub_out($catalog->sort_pattern);?>" />
            </td>
        </tr>
    </table>
    <div class="formValidation">
        <input type="hidden" name="catalog_id" value="<?php echo scrub_out($catalog->id); ?>" />
        <input type="hidden" name="action" value="update_catalog_settings" />
        <input class="button" type="submit" value="<?php echo T_('Save Catalog Settings'); ?>" />
    </div>
</form>
<?php UI::show_box_bottom(); ?>
