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
$localplay = init_localplay();
$songs = $localplay->get();
?>

<div class="text-box">
<span class="header2"><?php echo ucfirst($localplay->type); ?> <?php echo _('Localplay'); ?></span>
<ul class="text-action">
<?php if ($localplay->has_function('delete_all')) { ?>
	<li><a href="<?php echo $web_path; ?>/localplay.php?action=delete_all"><?php echo _('Clear Playlist'); ?></a></li>
<?php } ?>
</ul>
</div>
<br />
<div class="text-box">
<table class="border" cellspacing="0" border="0">
<tr class="table-header">
	<th><?php echo _('Track'); ?></th>
	<th><?php echo _('Name'); ?></th>
	<th><?php echo _('Action'); ?></th>
</tr>
<?php foreach ($songs as $song) { ?>
<tr class="<?php echo flip_class(); ?>">
	<td>
		<?php echo scrub_out($song['track']); ?>
	</td>
	<td>
		<?php echo $localplay->format_name($song['name'],$song['id']); ?>
	</td>
	<td>
	<a href="<?php echo $web_path; ?>/localplay.php?action=delete_song&amp;song_id=<?php echo $song['id']; ?>"><?php echo _('Delete'); ?></a>
	</td>
</tr>
<?php } if (!count($songs)) { ?>
<tr class="<?php echo flip_class(); ?>">
	<td colspan="3"><span class="error"><?php echo _('No Records Found'); ?></span></td>
</tr>
<?php } ?>
</table>
</div>
