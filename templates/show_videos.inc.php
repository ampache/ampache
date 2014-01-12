<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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

$web_path = AmpConfig::get('web_path');
if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php';
$thcount = 7;
?>
<table class="tabledata" cellpadding="0" cellspacing="0">
    <tr class="th-top">
        <?php if (AmpConfig::get('directplay')) { ++$thcount; ?>
        <th class="cel_directplay"><?php echo T_('Play'); ?></th>
        <?php } ?>
        <th class="cel_add"><?php echo T_('Add'); ?></th>
        <th class="cel_title"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=title', T_('Title'),'sort_video_title'); ?></th>
        <th class="cel_codec"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=codec', T_('Codec'),'sort_video_codec'); ?></th>
        <th class="cel_resolution"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=resolution', T_('Resolution'),'sort_video_rez'); ?></th>
        <th class="cel_length"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=length', T_('Time'),'sort_video_length'); ?></th>
        <th class="cel_tags"><?php echo T_('Tags'); ?></th>
        <th class="cel_action"><?php echo T_('Action'); ?></th>
    </tr>
    <?php
    /* Foreach through every artist that has been passed to us */
    foreach ($object_ids as $video_id) {
            $video = new Video($video_id);
            $video->format();
    ?>
    <tr id="video_<?php echo $video->id; ?>" class="<?php echo UI::flip_class(); ?>">
        <?php require AmpConfig::get('prefix') . '/templates/show_video_row.inc.php'; ?>
    </tr>
    <?php } //end foreach  ?>
    <?php if (!count($object_ids)) { ?>
    <tr class="<?php echo UI::flip_class(); ?>">
        <td colspan="<?php echo $thcount ?>"><span class="nodata"><?php echo T_('No video found'); ?></span></td>
    </tr>
    <?php } ?>
    <tr class="th-bottom">
        <?php if (AmpConfig::get('directplay')) { ?>
        <th class="cel_directplay"><?php echo T_('Play'); ?></th>
        <?php } ?>
        <th class="cel_add"><?php echo T_('Add'); ?></th>
        <th class="cel_title"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=title', T_('Title'),'sort_video_title'); ?></th>
        <th class="cel_codec"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=codec', T_('Codec'),'sort_video_codec'); ?></th>
        <th class="cel_resolution"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=resolution', T_('Resolution'),'sort_video_rez'); ?></th>
        <th class="cel_length"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=length', T_('Time'),'sort_video_length'); ?></th>
        <th class="cel_tags"><?php echo T_('Tags'); ?></th>
        <th class="cel_action"><?php echo T_('Action'); ?></th>
    </tr>
</table>
<?php if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php'; ?>
