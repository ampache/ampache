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
<?php show_box_top(_('Advanced Random Rules')); ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
	<col id="col_field" />
	<col id="col_operator" />
	<col id="col_value" />
	<col id="col_method" />
</colgroup>
<tr class="th-top">
	<th class="col_field"><?php echo _('Field'); ?></th>
	<th class="col_operator"><?php echo _('Operator'); ?></th>
	<th class="col_value"><?php echo _('Value'); ?></th>
	<th class="col_method"><?php echo _('Method'); ?></th>
</tr>
<tr>
	<td valign="top">
		<select name="field">
		<?php 
			$fields = Song::get_fields(); 
			foreach ($fields as $key=>$value) { 
				$name = ucfirst(str_replace('_',' ',$key));
		?>
			<option name="<?php echo scrub_out($key); ?>"><?php echo scrub_out($name); ?></option>
		<?php } ?>
		</select>
	</td>
	<td>
		<select name="operator">
			<option value="=">=</option>
			<option value="!=">!=</option>
			<option value=">">&gt;</option>
			<option value=">=">&gt;=</option>
			<option value="<">&lt;</option>
			<option value="<=">&lt;=</option>
			<option value="LIKE"><?php echo _('Like'); ?></option>
		</select>
	</td>
	<td valign="top">
		<input type="text" name="value" />
	</td>
	<td valign="top">
		<select name="method">
			<option value="OR"><?php echo _('OR'); ?></option>
			<option value="AND"><?php echo _('AND'); ?></option>
		</select>
	</td>
</tr>
<tr>
	<td>
		<?php echo Ajax::button('?page=random&action=add_rule','add',_('Add Rule'),'add_random_rule'); ?><?php echo _('Add Rule'); ?>
	</td>
	<td>
		<?php echo Ajax::button('?page=random&action=save_rules','download',_('Save Rules As'),'save_random_rules'); ?><?php echo _('Save Rules As'); ?>
	</td>
	<td colspan="2">
		<?php echo Ajax::button('?page=random&action=load_rules','cog',_('Load Saved Rules'),'load_random_rules'); ?><?php echo _('Load Saved Rules'); ?>
	</td>
	
</tr>
<tr>
	<td colspan="4">
		<div id="rule_status"></div>
	</td>
</tr>
</table>
<?php show_box_bottom(); ?>
