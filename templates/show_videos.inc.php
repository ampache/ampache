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

$web_path = Config::get('web_path');
?>
<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_add" />
  <col id="col_title" />
  <col id="col_codec" />
  <col id="col_resolution" />
  <col id="col_length" />
  <col id="col_tags" />
  <col id="col-action" />
</colgroup>
<tr class="th-top">
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
    <?php require Config::get('prefix') . '/templates/show_video_row.inc.php'; ?>
</tr>
<?php } //end foreach  ?>
<?php if (!count($object_ids)) { ?>
<tr class="<?php echo UI::flip_class(); ?>">
    <td colspan="7"><span class="fatalerror"><?php echo T_('Not Enough Data'); ?></span></td>
</tr>
<?php } ?>
<tr class="th-bottom">
    <th class="cel_add"><?php echo T_('Add'); ?></th>
    <th class="cel_title"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=title', T_('Title'),'sort_video_title'); ?></th>
    <th class="cel_codec"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=codec', T_('Codec'),'sort_video_codec'); ?></th>
    <th class="cel_resolution"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=resolution', T_('Resolution'),'sort_video_rez'); ?></th>
    <th class="cel_length"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=length', T_('Time'),'sort_video_length'); ?></th>
    <th class="cel_tags"><?php echo T_('Tags'); ?></th>
    <th class="cel_action"><?php echo T_('Action'); ?></th>
</tr>
</table>
<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
