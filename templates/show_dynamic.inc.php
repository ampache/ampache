<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
?>
<?php UI::show_box_top(T_('Advanced Random Rules')); ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
    <tr class="th-top">
        <th class="col_field"><?php echo T_('Field'); ?></th>
        <th class="col_operator"><?php echo T_('Operator'); ?></th>
        <th class="col_value"><?php echo T_('Value'); ?></th>
        <th class="col_method"><?php echo T_('Method'); ?></th>
    </tr>
    <tr>
        <td valign="top">
            <select name="field">
            <?php
                $fields = Song::get_fields();
                foreach ($fields as $key=>$value) {
                    $name = ucfirst(str_replace('_',' ',$key));
                    ?>
                <option value="<?php echo scrub_out($key);
                    ?>"><?php echo scrub_out($name);
                    ?></option>
            <?php 
                } ?>
            </select>
        </td>
        <td>
            <select name="operator">
                <option value="eq">=</option>
                <option value="nq">!=</option>
                <option value="gt">&gt;</option>
                <option value="gte">&gt;=</option>
                <option value="lt">&lt;</option>
                <option value="lte">&lt;=</option>
                <option value="like"><?php echo T_('Like'); ?></option>
            </select>
        </td>
        <td valign="top">
            <input type="text" name="value" />
        </td>
        <td valign="top">
            <select name="method">
                <option value="OR"><?php echo T_('OR'); ?></option>
                <option value="AND"><?php echo T_('AND'); ?></option>
            </select>
        </td>
    </tr>
    <tr>
        <td>
            <?php echo Ajax::button('?page=random&action=add_rule','add', T_('Add Rule'),'add_random_rule'); ?><?php echo T_('Add Rule'); ?>
        </td>
        <td>
            <?php echo Ajax::button('?page=random&action=save_rules','download', T_('Save Rules As'),'save_random_rules'); ?><?php echo T_('Save Rules As'); ?>
        </td>
        <td colspan="2">
            <?php echo Ajax::button('?page=random&action=load_rules','cog', T_('Load Saved Rules'),'load_random_rules'); ?><?php echo T_('Load Saved Rules'); ?>
        </td>
    </tr>
    <tr>
        <td colspan="4">
            <div id="rule_status"></div>
        </td>
    </tr>
</table>
<?php UI::show_box_bottom(); ?>
