<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
$web_path 	= Config::get('web_path');
$catalogs 	= Catalog::get_catalogs();

?>
<?php show_box_top(_('Catalogs')); ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_name" />
  <col id="col_action" />
</colgroup>
<tr class="th-top">
	<th class="cel_name"><?php echo _('Name'); ?></th>
	<th class="cel_action"><?php echo _('Action'); ?></th>
</tr>
<?php foreach ($catalogs as $catalog) { ?>
<tr class="<?php echo flip_class(); ?>">
	<td class="cel_name">
		<a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_customize_catalog&amp;catalog_id=<?php echo $catalog->id; ?>">
		<?php echo scrub_out($catalog->name); ?></a>
		&nbsp;&nbsp;(<?php echo scrub_out($catalog->path); ?>)
	</td>
	<td class="cel_action">
		<a href="<?php echo $web_path; ?>/admin/catalog.php?action=add_to_catalog&amp;catalogs[]=<?php echo $catalog->id; ?>">
		<?php echo _('Add'); ?></a>&nbsp;|&nbsp;
		<a href="<?php echo $web_path; ?>/admin/catalog.php?action=update_catalog&amp;catalogs[]=<?php echo $catalog->id; ?>">
		<?php echo _('Verify'); ?></a>&nbsp;|&nbsp;
		<a href="<?php echo $web_path; ?>/admin/catalog.php?action=clean_catalog&amp;catalogs[]=<?php echo $catalog->id; ?>">
		<?php echo _('Clean'); ?></a>&nbsp;|&nbsp;
		<a href="<?php echo $web_path; ?>/admin/catalog.php?action=full_service&amp;catalogs[]=<?php echo $catalog->id; ?>">
		<?php echo _('All'); ?></a>&nbsp;|&nbsp;
		<a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_delete_catalog&amp;catalog_id=<?php echo $catalog->id; ?>">
		<?php echo _('Delete'); ?></a>
	</td>
</tr>
<!--
<tr class="<?php echo flip_class(); ?>">
	<td colspan="2">
		<?php echo _('Fast'); ?><input type="checkbox" name="fast" value="1" />
		<?php echo _('Gather Art'); ?><input type="checkbox" name="gather_art" value="1" />
	</td>
</tr>
-->
<?php } // end foreach ?>
<?php if (!count($catalogs)) { ?>
<tr>
	<td colspan="2">
	<?php echo _('No Catalogs Found'); ?>
	</td>
</tr>
<?php } // end if no catalogs ?>
<tr class="th-bottom">
	<th class="cel_name"><?php echo _('Name'); ?></th>
	<th class="cel_action"><?php echo _('Action'); ?></th>
</tr>
</table>
<div>
<a class="button" href="<?php echo $web_path; ?>/admin/catalog.php?action=clean_all_catalogs"><?php echo _('Clean All'); ?></a>
<a class="button" href="<?php echo $web_path; ?>/admin/catalog.php?action=update_all_catalogs"><?php echo _('Verify All'); ?></a>
<a class="button" href="<?php echo $web_path; ?>/admin/catalog.php?action=add_to_all_catalogs"><?php echo _('Add to All'); ?></a>
<a class="button" href="<?php echo $web_path; ?>/admin/catalog.php?action=full_service"><?php echo _('Update All'); ?></a><hr noshade="noshade" size="3" />
<a class="button" href="<?php echo $web_path; ?>/admin/catalog.php?action=show_add_catalog"><?php echo _('Add a Catalog'); ?></a>
<a class="button" href="<?php echo $web_path; ?>/admin/catalog.php?action=show_clear_stats"><?php echo _('Clear Catalog Stats'); ?></a>
<a class="button" href="<?php echo $web_path; ?>/admin/catalog.php?action=gather_album_art"><?php echo _('Gather Album Art'); ?></a>
</div>
<?php show_box_bottom(); ?>

<?php show_box_top(_('Other Tools')); ?>
<div>
	<a class="button" href="<?php echo $web_path; ?>/admin/duplicates.php"><?php echo _('Show Duplicate Songs'); ?></a>
	<a class="button" href="<?php echo $web_path; ?>/admin/catalog.php?action=clear_now_playing"><?php echo _('Clear Now Playing'); ?></a>
	<a class="button" href="<?php echo $web_path; ?>/admin/system.php?action=generate_config"><?php echo _('Generate New Config'); ?></a>
	<a class="button" href="<?php echo $web_path; ?>/admin/preferences.php?action=show_set_preferences"><?php echo _('Preferences Permissions'); ?></a>
	<a class="button" href="<?php echo $web_path; ?>/admin/system.php?action=export&amp;export=itunes"><?php echo _('Export To Itunes DB'); ?></a>
	<a class="button" href="<?php echo $web_path; ?>/admin/users.php?action=show_inactive&amp;days=30"><?php echo _('Show Inactive Users'); ?></a>
<!--	<a class="button" href="<?php echo $web_path; ?>/admin/system.php?action=check_version"><?php echo _('Check for New Version'); ?></a>-->
</div>
<?php show_box_bottom(); ?>
