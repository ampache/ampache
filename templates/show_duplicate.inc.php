<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Show Duplicate
 *
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @package	Ampache
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @link	http://www.ampache.org/
 */

?>
<?php show_box_top(_('Find Duplicates'), 'box box_duplicate'); ?>
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
