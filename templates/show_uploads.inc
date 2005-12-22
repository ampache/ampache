<?php
/*      

 Copyright (c) 2001 - 2005 Ampache.org
 All rights reserved.

 *Created by Rosensama*

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

/* Get the current Quarantined Uploads */
$uploads = get_uploads(); 

if (count($uploads)) { 
?>
<table class="tabledata" cellspacing="0" cellpadding="0" border="1">
<tr class="table-header">
	<td><?php echo _('Action'); ?></td>
	<td><?php echo _('Status'); ?></td>
	<td><?php echo _('Song'); ?></td>
	<td><?php echo _('Artist'); ?></td>
	<td><?php echo _('Album'); ?></td>
	<td><?php echo _('Genre'); ?></td>
	<td><?php echo _('Time'); ?></td>
	<td><?php echo _('Bitrate'); ?></td>
	<td><?php echo _('Size'); ?></td>
	<td><?php echo _('Filename'); ?></td>
	<td><?php echo _('User'); ?></td>
	<td><?php echo _('Date'); ?></td>
</tr>
<?php foreach ($uploads as $upload) { ?>
<tr class="<?php echo flip_class(); ?>">
	<?php if($GLOBALS['user']->has_access(100)) { ?>
	<td>
		<a href="<?php echo conf('web_path'); ?>/upload.php?action=add&id=<?php echo $upload['id']; ?>"><?php echo _('Add'); ?></a>&nbsp;|&nbsp;
		<a href="<?php echo conf('web_path'); ?>/upload.php?action=delete&id=<?php echo $upload['id']; ?>"><?php echo _('Delete'); ?></a>
	</td>
	<?php } else { ?>
	<td>N/A</td>
	<?php } ?>
	<td><?php echo ucfirst($upload['action']); ?></td>
	<td><?php echo $upload['title']; ?></td>
	<td><?php echo $upload['artist']; ?></td>
	<td><?php echo $upload['album']; ?></td>
	<td><?php echo $upload['time']; ?></td>
	<td><?php echo $upload['genre']; ?></td>
	<td><?php echo intval($upload['bitrate']/1000); ?> - <?php echo $upload['mode']; ?></td>
	<td><?php echo sprintf("%.2f",($upload['size']/1048576)); ?>MB</td>
	<td><?php echo $upload['file']; ?></td>
	<td><?php echo $upload['user']; ?></td>
	<td><?php echo date('m/d/Y H:i:s',$upload['addition_time']); ?></td>
</tr>
<?php } ?>
</table>
<?php } // if count uploads ?>
