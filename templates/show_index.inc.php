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
?>
<?php
if (isset($_REQUEST['xspf']) && isset ($_REQUEST['play_info'])){
	require_once Config::get('prefix') . '/templates/show_embed_xspf.inc.php';
}
?>

<div id="np_data">
        <?php show_now_playing(); ?>
</div> <!-- Close Now Playing Div -->
<!-- Recently Played -->
<div id="random_selection">
	<?php
		$albums = get_random_albums('6'); 
		if (count($albums)) { require_once Config::get('prefix') . '/templates/show_random_albums.inc.php'; } 
	?>
</div> 
<div id="recently_played">
        <?php
                $data = get_recently_played();
		show_box_top(_('Recently Played')); 
                if (count($data)) { require_once Config::get('prefix') . '/templates/show_recently_played.inc.php'; }
		show_box_bottom(); 
        ?>
</div>
<div id="catalog_info">
        <?php show_local_catalog_info(); ?>
</div>
<div id="recent_added">
	<?php 
		$objects = Stats::get_newest('album'); 
		$headers = array('f_link'=>_('Newest Albums')); 
		show_box_top('','info-box'); 
		require Config::get('prefix') . '/templates/show_objects.inc.php';
		show_box_bottom(); 
		$objects = Stats::get_newest('artist'); 
		$headers = array('f_name_link'=>_('Newest Artists')); 
		show_box_top('','info-box'); 
		require Config::get('prefix') . '/templates/show_objects.inc.php'; 
		show_box_bottom(); 
		$objects = Stats::get_newest('genre'); 
		$headers = array('f_link'=>_('Newest Genres')); 
		show_box_top('','info-box'); 
		require Config::get('prefix') . '/templates/show_objects.inc.php';
		show_box_bottom(); 
	?>
</div>
