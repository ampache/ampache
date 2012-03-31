<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Show Objects
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

/*
 * Variable/Non-DB object display takes headers & objects
 */

?>
<table class="tabledata" cellspacing="0">
<tr>
<?php foreach ($headers as $header) { ?>
	<th class="th-top"><?php echo $header; ?></th>
<?php } ?>
</tr>
<?php
	foreach ($objects as $object) {
		$object->format();
?>
<tr id="object_row_<?php echo $object->id; ?>" class="<?php echo flip_class(); ?>">
	<?php require Config::get('prefix') . '/templates/show_object_row.inc.php'; ?>
</tr>
<?php } ?>
<?php if (!count($objects)) { ?>
<tr>
	<td colspan="<?php echo count($headers); ?>">
	<span class="error"><?php echo T_('Not Enough Data'); ?></span>
	</td>
</tr>
<?php } ?>
</table>
