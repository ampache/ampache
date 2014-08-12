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

$libitem = Video::create_from_id($libitem->id);
$libitem->format();
$video_type = strtolower(get_class($libitem));
?>
<div>
    <form method="post" id="edit_video_<?php echo $libitem->id; ?>" class="edit_dialog_content">
        <table class="tabledata" cellspacing="0" cellpadding="0">
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Title') ?></td>
                <td><input type="text" name="title" value="<?php echo scrub_out($libitem->title); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Release Date') ?></td>
                <td><input type="text" name="release_date" value="<?php echo $libitem->f_release_date; ?>" /></td>
            </tr>
<?php
if ($video_type != 'video') {
    require AmpConfig::get('prefix') . '/templates/show_partial_edit_' . $video_type . '_row.inc.php';
}
?>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Tags') ?></td>
                <td>
                    <input type="text" name="edit_tags" id="edit_tags" value="<?php echo Tag::get_display($libitem->tags); ?>" />
                </td>
            </tr>
        </table>
        <input type="hidden" name="id" value="<?php echo $libitem->id; ?>" />
        <input type="hidden" name="type" value="<?php echo $video_type; ?>_row" />
    </form>
</div>
