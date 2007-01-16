<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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
<div id="np_data">
        <?php show_now_playing(); ?>
</div> <!-- Close Now Playing Div -->
<!-- Recently Played -->
<div id="random_selection">
	<?php
		$albums = get_random_albums('6'); 
		if (count($albums)) { require_once(conf('prefix') . '/templates/show_random_albums.inc.php'); } 
	?>
</div> 
<div id="recently_played">
        <?php
                $data = get_recently_played();
                if (count($data)) { require_once(conf('prefix') . '/templates/show_recently_played.inc.php'); }
        ?>
</div>
<div id="catalog_info">
        <?php
                $data = show_local_catalog_info();
                if (count($data)) { show_local_catalog_info(); }
        ?>
</div>

