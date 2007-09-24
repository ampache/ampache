<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
$last_seen      = $client->last_seen ? date("m\/d\/y - H:i",$client->last_seen) : _('Never');
$create_date    = $client->create_date ? date("m\/d\/y - H:i",$client->create_date) : _('Unknown');
$client->format(); 
?>
<?php show_box_top($client->fullname); ?>
<table cellspacing="0">
<tr>
	<td valign="top">
		<strong><?php echo _('Full Name'); ?>:</strong> <?php echo $client->fullname; ?><br />
		<strong><?php echo _('Create Date'); ?>:</strong> <?php echo $create_date; ?><br />
		<strong><?php echo _('Last Seen'); ?>:</strong> <?php echo $last_seen; ?><br />
		<strong><?php echo _('Activity'); ?>:</strong> <?php echo $client->f_useage; ?><br />
		<?php if ($client->is_logged_in() AND $client->is_online()) { ?>
			<i style="color:green;"><?php echo _('User is Online Now'); ?></i>
		<?php } else { ?>
			<i style="color:red;"><?php echo _('User is Offline Now'); ?></i>
		<?php } ?>
		
	</td>
	<td valign="top">
		<h2><?php echo _('Active Playlist'); ?></h2>
		<div style="padding-left:10px;">
		<?php 
			$tmp_playlist = new tmpPlaylist(tmpPlaylist::get_from_userid($client->id)); 
			$object_ids = $tmp_playlist->get_items(); 
			foreach ($object_ids as $object_data) { 
				if ($object_data['1'] == 'radio' || $object_data['1'] == 'song') { 
					$object = new $object_data['1']($object_data['0']);
					$object->format(); 
				} 
				else { 
					$object = new Random(); 
					$object->f_link = Random::get_type_name($object_data['1']); 
				} 
		?>
		<?php echo $object->f_link; ?><br />
		<?php } ?>
		</div>
	</td>
</tr>
<tr>
	<td valign="top">
		&nbsp;
	</td>
	<td>
	<?php 
		echo "<h2>" . _('Recently Played') . "</h2>\n"; 
		$data = get_recently_played($client->id); 
		require Config::get('prefix') . '/templates/show_recently_played.inc.php'; 
	?>
	</td>
</tr>
</table>
<?php show_box_bottom(); ?>
