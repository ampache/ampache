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


<?

// Various settings for the 'bandwidth' option
$feature_sets = array
	(
	BANDWIDTH_LOW => array('now', 'played'),
	BANDWIDTH_MEDIUM => array('now', 'random', 'played'),
	BANDWIDTH_HIGH => array('now', 'random', 'shout', 'played', 'added')
	);

$feature_limits = array (
	BANDWIDTH_LOW => array
		(
		'shout' => 7,
		'played' => 7,
		'added' => 7
		),
	BANDWIDTH_MEDIUM => array
		(
		'shout' => 10,
		'played' => 10,
		'added' => 10
		),
	BANDWIDTH_HIGH => array
		(
		'shout' => 10,
		'played' => 20,
		'added' => 20
		)
	);

$features = $feature_sets[Config::get('bandwidth')];

foreach ($features as $feature) {
	switch ($feature) {
		case 'shout':
			?><div id="shout_objects"><?
			
			$shouts = shoutBox::get_top($feature_limits[Config::get('bandwidth')][$feature]); 
			
			if (count($shouts)) require_once Config::get('prefix') . '/templates/show_shoutbox.inc.php';
			
			?></div><?
			
			break;
		case 'played':
			?><div id="recently_played"><?
			
			$data = Song::get_recently_played('', $feature_limits[Config::get('bandwidth')][$feature]);
			
			Song::build_cache(array_keys($data)); 
			
			require_once Config::get('prefix') . '/templates/show_recently_played.inc.php';
			
			?></div><?
			
			break;
		case 'added':
			show_box_top("Recently Added Albums");
			
			$object_ids = Stats::get_newest('album', $feature_limits[Config::get('bandwidth')][$feature]);
			
			echo _('Newest Albums');
			
			require_once Config::get('prefix') . '/templates/show_albums.inc.php';
			
			show_box_bottom();
			
			break;
		case 'now':
			?><div id="now_playing"><?
			
			show_now_playing();
			
			?></div><?
			
			break;
		case 'random':
			echo Ajax::observe('window','load',Ajax::action('?page=index&action=random_albums','random_albums'));
			
			?><div id="random_selection"><?
			
			show_box_top(_('Albums of the Moment')); echo _('Loading...'); show_box_bottom();
			
			?></div><?
			
			break;
	}
}
?>

