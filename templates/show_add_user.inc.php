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
<?php UI::show_box_top(T_('Add User'), 'box box_add_user'); ?>
<?php AmpError::display('general'); ?>
<form name="add_user" enctype="multpart/form-data" method="post" action="<?php echo AmpConfig::get('web_path') . "/admin/users.php?action=add_user"; ?>">
    <table class="tabledata">
        <tr>
            <td><?php echo  T_('Username'); ?>: *</td>
            <td><input type="text" name="username" maxlength="128" value="<?php echo scrub_out(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)); ?>" />
                <?php AmpError::display('username'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo  T_('Password'); ?>: *</td>
            <td><input type="password" name="password_1" value="" />
                <?php AmpError::display('password'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo  T_('Confirm Password'); ?>: *</td>
            <td><input type="password" name="password_2" value="" /></td>
        </tr>
        <tr>
            <td><?php echo  T_('Full Name'); ?>:</td>
            <td><input type="text" name="fullname" value="<?php echo scrub_out(Core::get_post('fullname')); ?>" />
                <?php AmpError::display('fullname'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo  T_('E-mail'); ?>: *</td>
            <td><input type="text" name="email" value="<?php echo scrub_out(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL)); ?>" />
                <?php AmpError::display('email'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo  T_('Website'); ?>:</td>
            <td><input type="text" name="website" value="<?php echo scrub_out(Core::get_post('website')); ?>" />
                <?php AmpError::display('website'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo  T_('User Access Level'); ?>:</td>
                <td>
                    <?php $var_name = "on_" . $client->access; ${$var_name} = 'selected="selected"'; ?>
                    <select name="access">
                        <option value="5" <?php echo $on_5; ?>><?php echo T_('Guest'); ?></option>
                        <option value="25" <?php echo $on_25; ?>><?php echo T_('User'); ?></option>
                        <option value="50" <?php echo $on_50; ?>><?php echo T_('Content Manager'); ?></option>
                        <option value="75" <?php echo $on_75; ?>><?php echo T_('Catalog Manager'); ?></option>
                        <option value="100" <?php echo $on_100; ?>><?php echo T_('Admin'); ?></option>
                    </select>
                </td>
        </tr>
        <tr>
            <td><?php echo T_('Avatar'); ?> (&lt; <?php echo UI::format_bytes(AmpConfig::get('max_upload_size')); ?>)</td>
            <td><input type="file" id="avatar" name="avatar" value="" />
                <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo AmpConfig::get('max_upload_size'); ?>" /></td>
        </tr>
    </table>
    <div class="formValidation">
        <?php echo Core::form_register('add_user'); ?>
        <input type="submit" value="<?php echo T_('Add User'); ?>" />
    </div>
</form>
<?php UI::show_box_bottom(); ?>
