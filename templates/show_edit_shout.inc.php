<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Show Edit Shout
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
<?php show_box_top(_('Edit existing Shoutbox Post')); ?>
<form method="post" enctype="multipart/form-data" action="<?php echo Config::get('web_path'); ?>/admin/shout.php?action=edit_shout">
<input type="hidden" name="shout_id" value="<?php echo $shout->id; ?>" />
<table class="tabledata" cellpadding="0" cellspacing="0">
<tr>
	<td><strong><?php /* HINT: Client link, Object link */ printf(_('Created by: %s for %s'), $client->f_link, $object->f_link); ?></strong>
<tr>
<tr>
	<td><strong><?php echo _('Comment:'); ?></strong>
</tr>
<tr>
	<td><textarea rows="5" cols="70" name="comment"><?php echo $shout->text; ?></textarea></td>
</tr>
<tr>
	<td><input type="checkbox" name="sticky" <?php if ($shout->sticky == "1") { echo "checked"; } ?>/> <strong><?php echo _('Make Sticky'); ?></strong></td>
</tr>
<tr>
	<td>
		<input type="submit" value="<?php echo _('Update'); ?>" />
	</td>
</tr>
</table>
</form>
<?php show_box_bottom(); ?>
