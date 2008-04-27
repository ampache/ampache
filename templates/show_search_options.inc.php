<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; version 2
 of the License.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
?>
<?php show_box_top(_('Options')); ?>
<div id="search_options">
<ul>
	<li><?php echo Ajax::text('?action=basket&type=browse_set',_('Add Search Results'),'add_search_results'); ?></li>
	<?php if (Access::check_function('batch_download')) { ?>
	<li><a href="<?php echo Config::get('web_path'); ?>/batch.php?action=browse"><?php echo _('Batch Download'); ?></a></li>
	<?php } ?>
</ul>
</div>
<?php show_box_bottom(); ?>
