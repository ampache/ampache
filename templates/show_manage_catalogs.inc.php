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
?>
<?php show_box_top(_('Show Catalogs')) ?>
<div id="information_actions">
<table>
<tr>
<td>
<ul>
	<li><a href="<?php echo Config::get('web_path'); ?>/admin/catalog.php?action=gather_album_art"><?php echo _('Gather All Art'); ?></a></li>
	<li><a href="<?php echo Config::get('web_path'); ?>/admin/catalog.php?action=add_to_all_catalogs"><?php echo _('Add to All'); ?></a> </li>
	<li><a href="<?php echo Config::get('web_path'); ?>/admin/catalog.php?action=update_all_catalogs"><?php echo _('Verify All'); ?></a></li>
	<li><a href="<?php echo Config::get('web_path'); ?>/admin/catalog.php?action=clean_all_catalogs"><?php echo _('Clean All'); ?></a></li>
	<li><a href="<?php echo Config::get('web_path'); ?>/admin/catalog.php?action=full_service"><?php echo _('Update All'); ?></a></li>
	<li><a href="<?php echo Config::get('web_path'); ?>/admin/catalog.php?action=clear_stats"><?php echo _('Clear Stats'); ?></a></li>
</ul>
</td>
<td>
	<form method="post" action="<?php echo Config::get('web_path'); ?>/admin/catalog.php?action=update_from">
	<?php printf (_('Add From %s'), '<span class="information">/data/myNewMusic</span>'); ?><br />
	<input type="text" name="add_path" value="/" /><br />
	<?php printf (_('Update From %s'), '<span class="information">/data/myUpdatedMusic</span>'); ?><br />
	<input type="text" name="update_path" value="/" /><br />
<input type="submit" value="<?php echo _('Update'); ?>" />
</form>
</td>
</tr>
</table>
</div>
<?php show_box_bottom(); ?>
<?php 
                $catalog_ids = Catalog::get_catalogs();
                Browse::set_type('catalog');
                Browse::set_static_content(1);
                Browse::show_objects($catalog_ids);
?>

