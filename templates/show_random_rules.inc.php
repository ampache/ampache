<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
?>
<?php show_box_top(_('Rules')); ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
	<col id="col_field" />
	<col id="col_operator" />
	<col id="col_value" />
	<col id="col_method" />
	<col id="col_action" />
</colgroup>
<tr class="th-top">
	<th class="col_field"><?php echo _('Field'); ?></th>
	<th class="col_operator"><?php echo _('Operator'); ?></th>
	<th class="col_value"><?php echo _('Value'); ?></th>
	<th class="col_method"><?php echo _('Method'); ?></th>
	<th class="col_action"><?php echo _('Action'); ?></th>
</tr>
<tr>
	<td></td>
	<td></td>
	<td></td>
	<td></td>
	<td></td>
</tr>
</table>
<?php show_box_bottom(); ?>
