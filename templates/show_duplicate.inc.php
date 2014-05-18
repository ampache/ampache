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
