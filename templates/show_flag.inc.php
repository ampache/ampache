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

$web_path = conf('web_path');
$type = scrub_in($_REQUEST['type']);

switch ($type) { 
	case 'song':
		$song = new Song($_REQUEST['id']);
		$song->format_song();
		$title	= scrub_out($song->f_title . " by " . $song->f_artist_full);
		$file 	= scrub_out($song->file);
	break;
	case 'album':
	break;
	case 'artist':
	break;
	default:
	break;
} // end type switch
?>	

<?php show_box_top(_('Flag Song')); ?>	
<form name="flag" method="post" action="<?php echo $web_path; ?>/flag.php" enctype="multipart/form-data">
<table class="tabledata">
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('File'); ?>:</td>
	<td><?php echo $file; ?></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Item'); ?>:</td>
	<td><strong><?php echo $title; ?></strong></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Reason to flag'); ?>:</td>
	<td>
		<select name="flag_type">
			<option value="delete"><?php echo _('Delete'); ?></option>
			<option value="retag"><?php echo _('Incorrect Tags'); ?></option>
			<option value="reencode"><?php echo _('Re-encode'); ?></option>
			<option value="other"><?php echo _('Other'); ?></option>
		</select>
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Comment'); ?>:</td>
	<td><input name="comment" type="text" size="50" maxlength="128" value="" /></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td> &nbsp; </td>
	<td>
		<input type="submit" value="<?php echo _('Flag'); ?>" />
		<input type="hidden" name="id" value="<?php echo scrub_out($_REQUEST['id']); ?>" />
		<input type="hidden" name="action" value="flag" />
		<input type="hidden" name="type" value="<?php echo scrub_out($type); ?>" />
	</td>
</tr>
</table>
</form>
<?php show_box_bottom(); ?>
