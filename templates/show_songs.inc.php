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

// First let's setup some vars we're going to use a lot
$web_path = Config::get('web_path'); 
$ajax_url = Config::get('ajax_url'); 

?>
<table class="tabledata" cellspacing="0" cellpadding="0">
<tr class="table-header" align="center">
	<td colspan="12">
	<?php //require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
	</td>
</tr>
<tr class="table-header">
	<th><?php echo _('Add'); ?></th>
	<th onclick="ajaxPut('<?php echo $ajax_url; ?>?action=browse&amp;sort=title');return true;" >
		<?php echo _('Song Title'); ?>
	</th>
	<th><?php echo _('Artist'); ?></th>
	<th><?php echo _('Album'); ?></th>
</tr>
<?php 
	foreach ($object_ids as $song_id) { 
		$song = new Song($song_id); 
		$song->format(); 
?>
<tr class="<?php echo flip_class(); ?>">
	<td onclick="ajaxPut('<?php echo $ajax_url; ?>?action=basket&amp;type=song&amp;id=<?php echo $song->id; ?>');return true;">
		<?php echo get_user_icon('add'); ?>
	</td>
	<td><?php echo $song->f_link; ?></td>
	<td><?php echo $song->f_artist_link; ?></td>
	<td><?php echo $song->f_album_link; ?></td>
</tr>
<?php } ?>
</table>
