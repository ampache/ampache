<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/*
 * Show Add Live Stream
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
<?php show_box_top(_('Add Radio Station')); ?>
<form name="radio" method="post" action="<?php echo Config::get('web_path'); ?>/radio.php?action=create">
<table>
<tr>
	<td><?php echo _('Name'); ?></td>
	<td>
		<input type="text" name="name" size="20" value="<?php echo scrub_out($_REQUEST['name']); ?>" />
		<?php Error::display('name'); ?>
	</td>
</tr>
<tr>
	<td><?php echo _('Homepage'); ?></td>
	<td>
		<input type="text" name="site_url" value="<?php echo scrub_out($_REQUEST['site_url']); ?>" />
		<?php Error::display('site_url'); ?>
	</td>
</tr>
<tr>
	<td><?php echo _('Stream URL'); ?></td>
	<td>
		<input type="text" name="url" value="<?php echo scrub_out($_REQUEST['url']); ?>" />
		<?php Error::display('url'); ?>
	</td>
</tr>
<tr>
	<td><?php echo _('Frequency'); ?></td>
	<td>
		<input type="text" name="frequency" value="<?php echo scrub_out($_REQUEST['frequency']); ?>" />
	</td>
</tr>
<tr>
	<td><?php echo _('Callsign'); ?></td>
	<td>
		<input type="text" name="call_sign" value="<?php echo scrub_out($_REQUEST['call_sign']); ?>" />
	</td>
</tr>
<tr>
	<td><?php echo _('Catalog'); ?></td>
	<td>
		<?php echo show_catalog_select('catalog',intval($_REQUEST['catalog'])); ?>
	</td>
</tr>
</table>
<div class="formValidation">
	<?php echo Core::form_register('add_radio'); ?>
	<input class="button" type="submit" value="<?php echo _('Add'); ?>" />
</div>
</form>
<?php show_box_bottom(); ?>
