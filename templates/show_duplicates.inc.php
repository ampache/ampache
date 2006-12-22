<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
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
?>
<?php show_box_top(_('Find Duplicates')); ?>
<form name="duplicates" action="<?php echo conf('web_path'); ?>/admin/duplicates.php" method="post" enctype="multipart/form-data" >
<table cellspacing="0" cellpadding="3" border="0" width="450">
        <tr>
                <td valign="top"><?php echo _('Search Type'); ?>:</td>
                <td>
		<?php 
			$name = 'check_' . $_REQUEST['search_type']; 
			${$name} = ' checked="checked" ';
		?>
                <input type="radio" name="search_type" value="title"<?php echo $check_title; ?>/><?php echo _('Title'); ?><br />
                <input type="radio" name="search_type" value="artist_title"<?php echo $check_artist_title; ?>/><?php echo _('Artist and Title'); ?><br />
               	<input type="radio" name="search_type" value="artist_album_title"<?php echo $check_artist_album_title; ?>/><?php echo _('Artist, Album and Title'); ?><br />
                </td>
        </tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<?php if ($_REQUEST['auto']) { $auto_check = ' checked="checked"'; } ?>
			<input type="checkbox" name="auto" value="1" <?php echo $auto_check; ?>/><?php echo _('Select Best Guess'); ?>
		</td>
	</tr>
        <tr>
                <td></td>
                <td>
                        <input type="hidden" name="action" value="search" />
                        <input type="submit" value="<?php echo _('Search'); ?>" />
                </td>
        </tr>
</table>
</form>
<?php show_box_bottom(); ?>
