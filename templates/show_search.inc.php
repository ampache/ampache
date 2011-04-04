<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Show Search
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

/**
 * search template
 * This is the template for the searches... amazing!
 */
?>
<?php show_box_top(_('Search Ampache') . "..."); ?>
<form id="search" name="search" method="post" action="<?php echo Config::get('web_path'); ?>/search.php?type=<?php echo $_REQUEST['type'] ? scrub_out($_REQUEST['type']) : 'song'; ?>" enctype="multipart/form-data" style="Display:inline">
<table class="tabledata" cellpadding="3" cellspacing="0">
	<tr>
		<td><?php if ($_REQUEST['type'] != 'song') { ?><a href="<?php echo Config::get('web_path'); ?>/search.php?type=song"><?php echo _('Songs'); ?></a><?php } else { echo _('Songs'); } ?></td>
		<td><?php if ($_REQUEST['type'] != 'album') { ?><a href="<?php echo Config::get('web_path'); ?>/search.php?type=album"><?php echo _('Albums'); ?></a><?php } else { echo _('Albums'); } ?></td>
		<td><?php if ($_REQUEST['type'] != 'artist') { ?><a href="<?php echo Config::get('web_path'); ?>/search.php?type=artist"><?php echo _('Artists'); ?></a><?php } else { echo _('Artists'); } ?></td>
		<td><?php if ($_REQUEST['type'] != 'video') { ?><a href="<?php echo Config::get('web_path'); ?>/search.php?type=video"><?php echo _('Videos'); ?></a><?php } else { echo _('Videos'); } ?></td>
	</tr>
	<tr><td>&nbsp;</td></tr>
</table>
<table class="tabledata" cellpadding="3" cellspacing="0">
	<tr>
	<td><?php echo _('Maximum Results'); ?></td>
        <td>
                <select name="limit">
                        <option value="0"><?php echo _('Unlimited'); ?></option>
                        <option value="25" <?php if($_REQUEST['limit']=="25") echo "selected=\"selected\""?>>25</option>
                        <option value="50" <?php if($_REQUEST['limit']=="50") echo "selected=\"selected\""?>>50</option>
                        <option value="100" <?php if($_REQUEST['limit']=="100") echo "selected=\"selected\""?>>100</option>
                        <option value="500" <?php if($_REQUEST['limit']=="500") echo "selected=\"selected\""?>>500</option>
                </select>
        </td>
	</tr>
</table>

<?php require Config::get('prefix') . '/templates/show_rules.inc.php'; ?>

<div class="formValidation">
	        <input class="button" type="submit" value="<?php echo _('Search'); ?>" />&nbsp;&nbsp;
<?php if ($_REQUEST['type'] == 'song' || ! $_REQUEST['type']) { ?>
		<input id="savesearchbutton" class="button" type="submit" value="<?php echo _('Save as Smart Playlist'); ?>" onClick="$('hiddenaction').setValue('save_as_smartplaylist');" />&nbsp;&nbsp;
<?php } ?>
	        <input type="hidden" id="hiddenaction" name="action" value="search" />
</div>
</form>
<?php show_box_bottom(); ?>
