<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */ ?>
<?php UI::show_box_top(T_('Add Label'), 'box box_add_label'); ?>
<form name="label" method="post" action="<?php echo AmpConfig::get('web_path'); ?>/labels.php?action=add_label">
<table class="tabledata">
<tr>
    <td><?php echo T_('Name'); ?></td>
    <td><input type="text" name="name" value="<?php echo scrub_out($_REQUEST['name']); ?>" />
        <?php AmpError::display('name'); ?>
    </td>
</tr>
<tr>
    <td><?php echo  T_('Category'); ?></td>
        <td>
            <select name="category">
                <option value="personal" <?php if (empty($_REQUEST['category']) || $_REQUEST['category'] === "personal") {
    echo "selected";
} ?>><?php echo T_('Personal'); ?></option>
                <option value="association" <?php if (Core::get_request('category') === "association") {
    echo "selected";
} ?>><?php echo T_('Association'); ?></option>
                <option value="company" <?php if (Core::get_request('category') === "company") {
    echo "selected";
} ?>><?php echo T_('Company'); ?></option>
            </select>
        </td>
</tr>
<tr>
    <td><?php echo T_('Summary'); ?></td>
    <td>
        <textarea name="summary" cols="44" rows="4"><?php echo scrub_out($_REQUEST['summary']); ?></textarea>
        <?php AmpError::display('summary'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Address'); ?></td>
    <td><input type="text" name="address" value="<?php echo scrub_out($_REQUEST['address']); ?>" />
        <?php AmpError::display('address'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('E-mail'); ?></td>
    <td><input type="text" name="email" value="<?php echo scrub_out($_REQUEST['email']); ?>" />
        <?php AmpError::display('email'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Website'); ?></td>
    <td><input type="text" name="website" value="<?php echo scrub_out($_REQUEST['website']); ?>" />
        <?php AmpError::display('website'); ?>
    </td>
</tr>
</table>
<div class="formValidation">
    <?php echo Core::form_register('add_label'); ?>
    <input class="button" type="submit" value="<?php echo T_('Add'); ?>" />
</div>
</form>
<?php UI::show_box_bottom(); ?>
