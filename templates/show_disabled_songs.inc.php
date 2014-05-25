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
<br />
<form name="songs" method="post" action="<?php echo AmpConfig::get('web_path'); ?>/admin/catalog.php" enctype="multipart/form-data" style="Display:inline">
    <table class="tabledata" cellpadding="0" cellspacing="0">
        <thead>
            <tr class="th-top">
                <th class="cel_select"><a href="#" onclick="check_select('song'); return false;"><?php echo T_('Select'); ?></a></th>
                <th class="cel_song"><?php echo T_('Title'); ?></th>
                <th class="cel_album"><?php echo T_('Album'); ?></th>
                <th class="cel_artist"><?php echo T_('Artist'); ?></th>
                <th class="cel_filename"><?php echo T_('Filename'); ?></th>
                <th class="cel_additiontime"><?php echo T_('Addition Time'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($songs as $song) { ?>
                <tr class="<?php echo UI::flip_class(); ?>">
                    <td class="cel_select"><input type="checkbox" name="song[]" value="<?php echo $song->id; ?>" /></td>
                    <td class="cel_song"><?php echo $song->title; ?></td>
                    <td class="cel_album"><?php echo $song->get_album_name($song->album); ?></td>
                    <td class="cel_artist"><?php echo $song->get_artist_name($song->album); ?></td>
                    <td class="cel_filename"><?php echo $song->file; ?></td>
                    <td class="cel_additiontime"><?php echo date("h:i:s, m/d/y",$song->addition_time); ?></td>
                </tr>
            <?php } if (!count($songs)) { ?>
                <tr class="<?php echo UI::flip_class(); ?>">
                    <td colspan="6"><span class="error"><?php echo T_('No Records Found'); ?></span></td>
                </tr>
            <?php } ?>
        </tbody>
        <tfoot>
            <tr class="th-bottom">
                <th class="cel_select"><a href="#" onclick="check_select('song'); return false;"><?php echo T_('Select'); ?></a></th>
                <th class="cel_song"><?php echo T_('Title'); ?></th>
                <th class="cel_album"><?php echo T_('Album'); ?></th>
                <th class="cel_artist"><?php echo T_('Artist'); ?></th>
                <th class="cel_filename"><?php echo T_('Filename'); ?></th>
                <th class="cel_additiontime"><?php echo T_('Addition Time'); ?></th>
            </tr>
        </tfoot>
    </table>
    <div class="formValidation">
        <input class="button" type="submit" value="<?php echo T_('Enable'); ?>" />&nbsp;&nbsp;
        <input type="hidden" name="action" value="enable_disabled" />
    </div>
</form>
