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
?>
<?php UI::show_box_top(T_('Similar Artists'), 'info-box'); ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_add" />
  <col id="col_artist" />
  <col id="col_songs" />
  <col id="col_albums" />
  <col id="col_tags" />
  <col id="col_rating" />
  <col id="col_action" />
</colgroup>
<tr class="th-top">
    <th class="cel_add"><?php echo T_('Add'); ?></th>
    <th class="cel_artist"><?php echo T_('Artist'); ?></th>
    <th class="cel_songs"><?php echo T_('Songs');  ?></th>
    <th class="cel_albums"><?php echo T_('Albums'); ?></th>
    <th class="cel_time"><?php echo T_('Time'); ?></th>
    <th class="cel_tags"><?php echo T_('Tags'); ?></th>
    <th class="cel_rating"> <?php echo T_('Rating'); ?> </th>
    <th class="cel_action"> <?php echo T_('Action'); ?> </th>
</tr>
<?php
// Cache the ratings we are going to use
if (Config::get('ratings')) { Rating::build_cache('artist',$object_ids); }

/* Foreach through every artist that has been passed to us */
foreach ($object_ids as $artist_id) {
        $artist = new Artist($artist_id);
        $artist->format();
?>
<tr id="artist_<?php echo $artist->id; ?>" class="<?php echo UI::flip_class(); ?>">
    <?php require Config::get('prefix') . '/templates/show_artist_row.inc.php'; ?>
</tr>
<?php } //end foreach ($artists as $artist) ?>
<?php if (!count($object_ids)) { ?>
<tr class="<?php echo UI::flip_class(); ?>">
    <td colspan="5"><span class="fatalerror"><?php echo T_('Not Enough Data'); ?></span></td>
</tr>
<?php } ?>
<tr class="th-bottom">
    <th class="cel_add"><?php echo T_('Add'); ?></th>
    <th class="cel_artist"><?php echo T_('Artist'); ?></th>
    <th class="cel_songs"> <?php echo T_('Songs');  ?> </th>
    <th class="cel_albums"> <?php echo T_('Albums'); ?> </th>
    <th class="cel_time"> <?php echo T_('Time'); ?> </th>
    <th class="cel_tags"><?php echo T_('Tags'); ?></th>
    <th class="cel_rating"> <?php echo T_('Rating'); ?> </th>
    <th class="cel_action"> <?php echo T_('Action'); ?> </th>
</tr>
</table>
