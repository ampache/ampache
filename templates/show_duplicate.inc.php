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
?>
<?php UI::show_box_top(T_('Find Duplicates'), 'box box_duplicate'); ?>
<form name="duplicates" action="<?php echo AmpConfig::get('web_path'); ?>/admin/duplicates.php?action=find_duplicates" method="post" enctype="multipart/form-data" >
    <table cellspacing="0" cellpadding="3">
        <tr>
            <td valign="top"><strong><?php echo T_('Search Type'); ?>:</strong></td>
            <td>
                <input type="radio" name="search_type" value="title" /><?php echo T_('Title'); ?><br />
                <input type="radio" name="search_type" value="artist_title" /><?php echo T_('Artist and Title'); ?><br />
                <input type="radio" name="search_type" value="artist_album_title" /><?php echo T_('Artist, Album and Title'); ?><br />
            </td>
        </tr>
    </table>
    <div class="formValidation">
          <input type="submit" value="<?php echo T_('Find Duplicates'); ?>" />
    </div>
</form>
<?php UI::show_box_bottom(); ?>
