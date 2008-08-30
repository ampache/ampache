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
<div id="xspf_player"><!-- Start XSPF Player -->
<?php
    if (isset($_REQUEST['xspf']) && isset ($_REQUEST['tmpplaylist_id'])){ 
      require_once Config::get('prefix') . '/templates/show_embed_xspf.inc.php';
    }
?>
</div><!-- End XSPF Player -->



<div id="now_playing">
        <?php show_now_playing(); ?>
</div> <!-- Close Now Playing Div -->
<!-- Randomly selected albums of the moment --> 
	<?php echo Ajax::observe('window','load',Ajax::action('?page=index&action=random_albums','random_albums')); ?>
<div id="random_selection">
	<?php show_box_top(_('Albums of the Moment')); echo _('Loading...'); show_box_bottom(); ?>
</div> 
<!-- Recently Played -->
<div id="recently_played">
        <?php
                $data = Song::get_recently_played();
		Song::build_cache(array_keys($data)); 
                require_once Config::get('prefix') . '/templates/show_recently_played.inc.php'; 
        ?>
</div>
<!-- Shoutbox Objects, if shoutbox is enabled --> 
<?php if (Config::get('shoutbox')) { ?>
<div id="shout_objects">
	<?php 
		$shouts = shoutBox::get_top('5'); 
		if (count($shouts)) { 
			require_once Config::get('prefix') . '/templates/show_shoutbox.inc.php'; 
		} 
	?>
</div>
<?php } ?>

