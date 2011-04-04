<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Show Recommended Artists
 *
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
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
 * @package	Ampache
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @link	http://www.ampache.org/
 */

?>
<?php show_box_top(_('Similar Artists'), 'info-box'); ?>
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
	<th class="cel_add"><?php echo _('Add'); ?></th>
	<th class="cel_artist"><?php echo _('Artist'); ?></th>
	<th class="cel_songs"><?php echo _('Songs');  ?></th>
	<th class="cel_albums"><?php echo _('Albums'); ?></th>
	<th class="cel_time"><?php echo _('Time'); ?></th>
	<th class="cel_tags"><?php echo _('Tags'); ?></th>
	<th class="cel_rating"> <?php echo _('Rating'); ?> </th>
	<th class="cel_action"> <?php echo _('Action'); ?> </th>
</tr>
<?php
// Cache the ratings we are going to use
if (Config::get('ratings')) { Rating::build_cache('artist',$object_ids); }

/* Foreach through every artist that has been passed to us */
foreach ($object_ids as $artist_id) {
		$artist = new Artist($artist_id);
		$artist->format();
?>
<tr id="artist_<?php echo $artist->id; ?>" class="<?php echo flip_class(); ?>">
	<?php require Config::get('prefix') . '/templates/show_artist_row.inc.php'; ?>
</tr>
<?php } //end foreach ($artists as $artist) ?>
<?php if (!count($object_ids)) { ?>
<tr class="<?php echo flip_class(); ?>">
	<td colspan="5"><span class="fatalerror"><?php echo _('Not Enough Data'); ?></span></td>
</tr>
<?php } ?>
<tr class="th-bottom">
	<th class="cel_add"><?php echo _('Add'); ?></th>
	<th class="cel_artist"><?php echo _('Artist'); ?></th>
	<th class="cel_songs"> <?php echo _('Songs');  ?> </th>
	<th class="cel_albums"> <?php echo _('Albums'); ?> </th>
	<th class="cel_time"> <?php echo _('Time'); ?> </th>
	<th class="cel_tags"><?php echo _('Tags'); ?></th>
	<th class="cel_rating"> <?php echo _('Rating'); ?> </th>
	<th class="cel_action"> <?php echo _('Action'); ?> </th>
</tr>
</table>
