<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; version 2
 of the License. 

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
?>
<?php show_box_top(_('Find Duplicates')); ?>
<form name="duplicates" action="<?php echo Config::get('web_path'); ?>/admin/duplicates.php?action=find_duplicates" method="post" enctype="multipart/form-data" >
<table cellspacing="0" cellpadding="3">
<tr>
	<td valign="top"><strong><?php echo _('Search Type'); ?>:</strong></td>
        <td>
	<?php 
		$name = 'check_' . scrub_in($_REQUEST['search_type']);
		${$name} = ' checked="checked" ';
	?>
	<input type="radio" name="search_type" value="title"<?php echo $check_title; ?>/><?php echo _('Title'); ?><br />
        <input type="radio" name="search_type" value="artist_title"<?php echo $check_artist_title; ?>/><?php echo _('Artist and Title'); ?><br />
        <input type="radio" name="search_type" value="artist_album_title"<?php echo $check_artist_album_title; ?>/><?php echo _('Artist, Album and Title'); ?><br />
        <?php if ($_REQUEST['search_disabled']) { $disabled_check = ' checked="checked"'; } ?>
        <input type="checkbox" name="search_disabled" value="1" <?php echo $disabled_check; ?>/><?php echo _('Search Disabled Songs'); ?><br />
	</td>
</tr>
</table>
<div class="formValidation">
      <input type="submit" value="<?php echo _('Find Duplicates'); ?>" />
</div>
</form>
<?php show_box_bottom(); ?>
