<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Show Confirmation
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

$confirmation = Core::form_register($form_name);
?>
<?php show_box_top(scrub_out($title), 'box box_confirmation'); ?>
<?php echo $text; ?>
<br />
	<form method="post" action="<?php echo $path; ?>" style="display:inline;">
	<input type="submit" value="<?php echo T_('Continue'); ?>" />
	<?php echo $confirmation; ?>
	</form>
<?php if ($cancel) { ?>
	<form method="post" action="<?php echo Config::get('web_path') . '/' . return_referer(); ?>" style="display:inline;">
	<input type="submit" value="<?php echo T_('Cancel'); ?>" />
	<?php echo $confirmation; ?>
	</form>
<?php } ?>
<?php show_box_bottom(); ?>
