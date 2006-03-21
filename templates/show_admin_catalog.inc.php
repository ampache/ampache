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
$web_path 	= conf('web_path');
$catalogs 	= Catalog::get_catalogs();

?>
<span class="header1"><?php echo _('Catalogs'); ?></span>
<div class="text-box"> 
<!-- Current Catalogs -->
<table border="0" cellpadding="0" cellspacing="0">
<tr class="table-header">
	<td><?php echo _('Name'); ?></td>
	<td align="center"><?php echo _('Action'); ?></td>
</tr>
<?php foreach ($catalogs as $catalog) { ?>
<tr class="<?php echo flip_class(); ?>">
	<td>
		<a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_customize_catalog&amp;catalog_id=<?php echo $catalog->id; ?>">
		<?php echo $catalog->name; ?></a>
		&nbsp;&nbsp;(<?php echo $catalog->path; ?>)
	</td>
	<td>
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
<?php } // end foreach ?>
<?php if (!count($catalogs)) { ?>
<tr>
	<td colspan="2">
	<?php echo _('No Catalogs Found'); ?>
	</td>
</tr>
<?php } // end if no catalogs ?>
</table>
<form id="catalog_tools" method="post" enctype="multipart/form-data" style="Display:inline;">
<input class="button" type="button" value="<?php echo _('Clean Everything'); ?>" onclick="return SubmitToPage('catalog_tools','<?php echo $web_path; ?>/admin/catalog.php?action=clean_all_catalogs');" />
<input class="button" type="button" value="<?php echo _('Verify Everything'); ?>" onclick="return SubmitToPage('catalog_tools','<?php echo $web_path; ?>/admin/catalog.php?action=update_all_catalogs');" />
<input class="button" type="button" value="<?php echo _('Add to Everything'); ?>" onclick="return SubmitToPage('catalog_tools',<?php echo $web_path; ?>/admin/catalog.php?action=add_to_all_catalogs');" />
<input class="button" type="button" value="<?php echo _('Update Everything'); ?>" onclick="return SubmitToPage('catalog_tools',<?php echo $web_path; ?>/admin/catalog.php?action=full_service');" />
<br />
<span class="header2"><?php echo _('Other Tools'); ?></span><br />
<a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_add_catalog"><?php echo _('Add a Catalog'); ?></a><br />
<a href="<?php echo $web_path; ?>/admin/duplicates.php"><?php echo _('Show Duplicate Songs'); ?></a><br />
<a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_disabled"><?php echo _('Show Disabled Songs'); ?></a><br />
<a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_clear_stats"><?php echo _('Clear Catalog Stats'); ?></a><br />
<a href="<?php echo $web_path; ?>/admin/catalog.php?action=clear_now_playing"><?php echo _('Clear Now Playing'); ?></a><br />
<a href="<?php echo $web_path; ?>/admin/catalog.php?action=gather_album_art"><?php echo _('Gather Album Art'); ?></a><br />
</div>
<br />
