<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
$web_path = Config::get('web_path');

/* Flagged Information Gathering */
$flagged	= Flag::get_recent(10);

/* Disabled Information Gathering */
$songs = Catalog::get_disabled(10);
?>

<?php show_box_top(_('Last Ten Flagged Records')); ?>
	<?php require Config::get('prefix') . '/templates/show_flagged.inc.php'; ?>
<?php show_box_bottom(); ?>

<?php show_box_top(_('Disabled Songs')); ?>
	<!-- Show Last 10 Disabled Songs -->&nbsp;
	<?php require Config::get('prefix') . '/templates/show_disabled_songs.inc.php'; ?>
	<div>
	<a class="button" href="<?php echo $web_path; ?>/admin/catalog.php?action=show_disabled"><?php echo _('Show All'); ?>...</a>
	</div>
<?php show_box_bottom(); ?>
