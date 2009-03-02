<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
$stats = Catalog::get_stats();
$catalogs = Catalog::get_catalogs(); 
?>
<?php show_box_top(_('Statistics')); ?>
<em><?php echo _('Catalogs'); ?></em>
<table class="tabledata" cellpadding="3" cellspacing="1">
<tr class="th-top">
        <th><?php echo _('Connected Users'); ?></th>
        <th><?php echo _('Total Users'); ?></th>
        <th><?php echo _('Albums'); ?></th>
        <th><?php echo _('Artists'); ?></th>
        <th><?php echo _('Songs'); ?></th>
	<th><?php echo _('Video'); ?></th>
        <th><?php echo _('Tags'); ?></th>
        <th><?php echo _('Catalog Size'); ?></th>
        <th><?php echo _('Catalog Time'); ?></th>
</tr>
<tr>
        <td><?php echo $stats['connected']; ?></td>
        <td><?php echo $stats['users'] ?></td>
        <td><?php echo $stats['albums']; ?></td>
        <td><?php echo $stats['artists']; ?></td>
        <td><?php echo $stats['songs']; ?></td>
	<td><?php echo $stats['video']; ?></td>
        <td><?php echo $stats['tags']; ?></td>
        <td><?php echo $stats['total_size']; ?> <?php echo $stats['size_unit']; ?></td>
        <td><?php echo $stats['time_text']; ?></td>
</tr>
</table>
<hr />
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_catalog" />
  <col id="col_path" />
  <col id="col_lastverify" />
  <col id="col_lastadd" />
  <col id="col_lastclean" />
  <col id="col_songs" />
  <col id="col_video" />
  <col id="col_total" />
</colgroup>
<tr class="th-top">
        <th class="cel_catalog"><?php echo _('Name'); ?></th>
        <th class="cel_path"><?php echo _('Path'); ?></th>
        <th class="cel_lastverify"><?php echo _('Last Verify'); ?></th>
        <th class="cel_lastadd"><?php echo _('Last Add'); ?></th>
        <th class="cel_lastclean"><?php echo _('Last Clean'); ?></th>
	<th class="cel_songs"><?php echo _('Songs'); ?></th>
	<th class="cel_video"><?php echo _('Video'); ?></th>
	<th class="cel_total"><?php echo _('Catalog Size'); ?></th>
</tr>
<?php foreach ($catalogs as $catalog_id) { 
		$catalog = new Catalog($catalog_id); 
		$catalog->format(); 
		$stats = Catalog::get_stats($catalog_id); 
?>
<tr>
	<td class="cel_catalog"><?php echo $catalog->name; ?></td>
	<td class="cel_path"><?php echo scrub_out($catalog->f_path); ?></td>
	<td class="cel_lastverify"><?php echo scrub_out($catalog->f_update); ?></td>
	<td class="cel_lastadd"><?php echo scrub_out($catalog->f_add); ?></td>
	<td class="cel_lastclean"><?php echo scrub_out($catalog->f_clean); ?></td>
	<td class="cel_songs"><?php echo scrub_out($stats['songs']); ?></td>
	<td class="cel_video"><?php echo scrub_out($stats['video']); ?></td>
	<td class="cel_total"><?php echo scrub_out($stats['total_size'] . ' ' . $stats['size_unit']); ?></td>
</tr>
<?php } ?>

</table>
<?php show_box_bottom(); ?>
