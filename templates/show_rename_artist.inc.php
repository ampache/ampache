<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Show Rename Artist
 *
 * PHP version 5
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
 * @category	Template
 * @package	Template
 * @author	Karl Vollmer <vollmer@ampache.org>
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @version	PHP 5.2
 * @link	http://www.ampache.org/
 * @since	File available since Release 1.0
 */

?>
<script type='text/javascript'>
function insert()
{
	document.getElementById('artist_name').value = '<?php echo $artist->name; ?>';
}
</script>
<?php /* HINT: Artist Name */ show_box_top(sprintf(_('Rename %s'), $artist->name)); ?>
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
