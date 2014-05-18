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
    <form method="post" id="edit_song_<?php echo $song->id; ?>" class="edit_dialog_content">
        <table class="tabledata" cellspacing="0" cellpadding="0">
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Title') ?></td>
                <td><input type="text" name="title" value="<?php echo scrub_out($song->title); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Artist') ?></td>
                <td>
                    <?php show_artist_select('artist', $song->artist, true, $song->id); ?>
                    <div id="artist_select_song_<?php echo $song->id ?>">
                        <?php echo Ajax::observe('artist_select_'.$song->id, 'change', 'check_inline_song_edit("artist", '.$song->id.')'); ?>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Album') ?></td>
                <td>
                    <?php show_album_select('album', $song->album, true, $song->id); ?>
                    <div id="album_select_song_<?php echo $song->id ?>">
                        <?php echo Ajax::observe('album_select_'.$song->id, 'change', 'check_inline_song_edit("album", '.$song->id.')'); ?>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Track') ?></td>
                <td><input type="text" name="track" value="<?php echo scrub_out($song->track); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('MusicBrainz ID') ?></td>
                <td><input type="text" name="mbid" value="<?php echo $song->mbid; ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Tags') ?></td>
                <td>
                    <input type="text" name="edit_tags" id="edit_tags" value="<?php echo Tag::get_display($song->tags); ?>" />
                </td>
            </tr>
        </table>
        <input type="hidden" name="id" value="<?php echo $song->id; ?>" />
        <input type="hidden" name="type" value="song_row" />
    </form>
</div>
