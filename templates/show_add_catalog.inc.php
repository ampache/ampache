<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
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

$default_rename = "%T - %t";
$default_sort   = "%a/%A";
?>
<?php UI::show_box_top(T_('Add a Catalog'), 'box box_add_catalog'); ?>
<p><?php echo T_("In the form below enter either a local path (i.e. /data/music) or the URL to a remote Ampache installation (i.e http://theotherampache.com)"); ?></p>
&nbsp;
<?php AmpError::display('general'); ?>

<form name="update_catalog" method="post" action="<?php echo AmpConfig::get('web_path'); ?>/admin/catalog.php" enctype="multipart/form-data">
    <table class="tabledata" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 25%;"><?php echo T_('Catalog Name'); ?>: </td>
            <td><input type="text" name="name" value="<?php echo scrub_out($_POST['name']); ?>" /></td>
            <td style="vertical-align:top; font-family: monospace;" rowspan="6" id="patterns_example">
                <strong><?php echo T_('Auto-inserted Fields'); ?>:</strong><br />
                <span class="format-specifier">%A</span> = <?php echo T_('album name'); ?><br />
                <span class="format-specifier">%a</span> = <?php echo T_('artist name'); ?><br />
                <span class="format-specifier">%c</span> = <?php echo T_('id3 comment'); ?><br />
                <span class="format-specifier">%T</span> = <?php echo T_('track number (padded with leading 0)'); ?><br />
                <span class="format-specifier">%t</span> = <?php echo T_('song title'); ?><br />
                <span class="format-specifier">%y</span> = <?php echo T_('year'); ?><br />
                <span class="format-specifier">%o</span> = <?php echo T_('other'); ?><br />
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Catalog Type'); ?>: </td>
            <td><?php Catalog::show_catalog_types(); ?></td>
        </tr>
        <tr>
            <td><?php echo T_('Filename Pattern'); ?>: </td>
            <td><input type="text" name="rename_pattern" value="<?php echo $default_rename; ?>" /></td>
        </tr>
        <tr>
            <td><?php echo T_('Folder Pattern'); ?>:<br /><?php echo T_("(no leading or ending '/')"); ?></td>
            <td valign="top"><input type="text" name="sort_pattern" value="<?php echo $default_sort; ?>" /></td>
        </tr>
        <tr>
            <td valign="top"><?php echo T_('Gather Art'); ?>:</td>
            <td><input type="checkbox" name="gather_art" value="1" checked /></td>
        </tr>
        <tr>
            <td valign="top"><?php echo T_('Build Playlists from playlist Files'); ?> (m3u, m3u8, asx, pls, xspf):</td>
            <td><input type="checkbox" name="parse_playlist" value="1" /></td>
        </tr>
        <tr>
            <td valign="top"><?php echo T_('Gather media types'); ?>:</td>
            <td>

                <select name="gather_media">
                    <option value="music"><?php echo T_('Music'); ?></option>
            <?php if (AmpConfig::get('allow_video')) {
    ?>
                    <option value="clip"><?php echo T_('Music Clip'); ?></option>
                    <option value="tvshow"><?php echo T_('TV Show'); ?></option>
                    <option value="movie"><?php echo T_('Movie'); ?></option>
                    <option value="personal_video"><?php echo T_('Personal Video'); ?></option>
            <?php

}
            if (AmpConfig::get('podcast')) {
                ?>
                    <option value="podcast"><?php echo T_('Podcast'); ?></option>
            <?php

            }
            ?>
                </select>
            </td>
        </tr>
    </table>
    <div id="catalog_type_fields">
    </div>
    <div class="formValidation">
        <input type="hidden" name="action" value="add_catalog" />
        <?php echo Core::form_register('add_catalog'); ?>
        <input class="button" type="submit" value="<?php echo T_('Add Catalog'); ?>" />
    </div>
</form>
<?php UI::show_box_bottom(); ?>
