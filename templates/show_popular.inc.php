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
<?php show_box_top(_('Information')); ?>
<em><?php echo _('Songs'); ?></em>
<?php $object_ids = Stats::get_top('song'); ?>
<?php require_once Config::get('prefix') . '/templates/show_songs.inc.php'; ?>
<em><?php echo _('Albums'); ?></em>
<?php $object_ids = Stats::get_top('album'); ?>
<?php require_once Config::get('prefix') . '/templates/show_albums.inc.php'; ?>
<em><?php echo _('Artists'); ?></em>
<?php $object_ids = Stats::get_top('artist'); ?>
<?php require_once Config::get('prefix') . '/templates/show_artists.inc.php'; ?>
<?php show_box_bottom(); ?>
