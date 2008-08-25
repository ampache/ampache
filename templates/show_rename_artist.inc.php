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
?>
<script type='text/javascript'>
function insert()
{
	document.getElementById('artist_name').value = '<?php echo $artist->name; ?>';
}
</script>
<?php show_box_top(sprintf(_('Rename %s'), $artist->name)); ?>
<form name="rename_artist" method="post" action="<?php echo conf('web_path'); ?>/artists.php?action=rename&amp;artist=<?php echo $artist->id; ?>" style="Display:inline;">
        <?php show_artist_pulldown($artist->id, "artist_id", 4); ?>
	<br />
	<?php echo _('OR'); ?><br />
	<input type="text" name="artist_name" size="30" value="<?php echo scrub_out($_REQUEST['artist_name']); ?>" id="artist_name" />
	<a href="javascript:insert()">[<?php echo _('Insert current'); ?>]</a><br />
	<?php $GLOBALS['error']->print_error('artist_name'); ?>
	<input type="checkbox" name="update_id3" value="yes" />&nbsp; <?php echo _('Update id3 tags') ?><br />
	<input type="submit" value="<?php echo _('Rename'); ?>" /><br />
</form>
<?php show_box_bottom(); ?>
