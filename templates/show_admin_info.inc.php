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
$web_path = conf('web_path');

/* Flagged Information Gathering */
$flag = new Flag();
$flagged	= $flag->get_recent(10);
$total_flagged	= $flag->get_total();

/* Disabled Information Gathering */
$catalog = new Catalog(); 
$songs = $catalog->get_disabled(10);
?>

<?php show_box_top(_('Last Ten Flagged Records')); ?>
	<?php require (conf('prefix') . '/templates/show_flagged.inc.php'); ?>
	<div class="text-action">
	<a href="<?php echo $web_path; ?>/admin/flag.php?action=show_flagged"><?php echo _('Show All'); ?>...</a>
	</div>
<?php show_box_bottom(); ?>

<?php show_box_top(_('Disabled Songs')); ?>
	<!-- Show Last 10 Disabled Songs -->&nbsp;
	<?php require (conf('prefix') . '/templates/show_disabled_songs.inc'); ?>
	<div class="text-action">
	<a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_disabled"><?php echo _('Show All'); ?>...</a>
	</div>
<?php show_box_bottom(); ?>
