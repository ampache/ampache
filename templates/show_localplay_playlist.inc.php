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

$songs = $localplay->get();
$status = $localplay->status();
?>
<table cellspacing="0">
<tr class="table-header">
	<th><?php echo _('Track'); ?></th>
	<th><?php echo _('Name'); ?></th>
	<th><?php echo _('Action'); ?></th>
</tr>
<?php 
foreach ($songs as $song) { 
	$class = '';
	if ($status['track'] == $song['track']) { $class=' class="lp_current"'; } 	
?>
<tr class="<?php echo flip_class(); ?>">
	<td>
		<?php echo scrub_out($song['track']); ?>
	</td>
	<td<?php echo $class; ?>>
		<?php echo $localplay->format_name($song['name'],$song['id']); ?>
	</td>
	<td>
	<a href="<?php echo $web_path; ?>/localplay.php?action=delete_song&amp;song_id=<?php echo scrub_out($song['id']); ?>">
		<?php echo get_user_icon('delete'); ?>
	</a>
	</td>
</tr>
<?php } if (!count($songs)) { ?>
<tr class="<?php echo flip_class(); ?>">
	<td colspan="3"><span class="error"><?php echo _('No Records Found'); ?></span></td>
</tr>
<?php } ?>
</table>
