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
<div class="text-box"> 
<span class="header2"><?php echo _('Catalogs'); ?></span>
<!-- Current Catalogs -->
<table border="0" cellpadding="0" cellspacing="0">
<tr class="table-header">
	<td><?php echo _('Name'); ?></td>
	<td align="center"><?php echo _('Action'); ?></td>
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
		<?php echo _('Update'); ?></a>&nbsp;|&nbsp;
		<a href="<?php echo $web_path; ?>/admin/catalog.php?action=clean_catalog&amp;catalogs[]=<?php echo $catalog->id; ?>">
		<?php echo _('Clean'); ?></a>&nbsp;|&nbsp;
		<a href="<?php echo $web_path; ?>/admin/catalog.php?action=full_service&amp;catalogs[]=<?php echo $catalog->id; ?>">
		<?php echo _('All'); ?></a>
	</td>
</tr>
<?php } // end foreach ?>
</table>
<form method="post" enctype="multipart/form-data" action="<?php echo $web_path; ?>/admin/catalog.php?action=full_service">
<input class="button" type="submit" value="<?php echo _('Update Everything'); ?>" />
</form>
<span class="header2"><?php echo _('Other Tools'); ?></span><br />
<ul style="list-style:none;">
	<li><a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_add_catalog"><?php echo _('Add a Catalog'); ?></a></li>
	<li><a href="<?php echo $web_path; ?>/admin/duplicates.php"><?php echo _('Show Duplicate Songs'); ?></a></li>
	<li><a href="<?php echo $web_path; ?>/admin/catalog.php?show_disabled"><?php echo _('Show Disabled Songs'); ?></a></li>
</ul>
</div>
