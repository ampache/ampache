<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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
 */
?>
<?php UI::show_box_top(T_('Adding a New User'), 'box box_add_user'); ?>
<?php Error::display('general'); ?>
<form name="add_user" enctype="multpart/form-data" method="post" action="<?php echo AmpConfig::get('web_path') . "/admin/users.php?action=add_user"; ?>">
    <table class="tabledata" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <?php echo  T_('Username'); ?>:
            </td>
            <td>
                <input type="text" name="username" maxlength="128" value="<?php echo scrub_out($_POST['username']); ?>" />
                <?php Error::display('username'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo  T_('Full Name'); ?>:</td>
            <td>
                <input type="text" name="fullname" value="<?php echo scrub_out($_POST['fullname']); ?>" />
            </td>
        </tr>
        <tr>
            <td>
                <?php echo  T_('E-mail'); ?>:
            </td>
            <td>
                <input type="text" name="email" value="<?php echo scrub_out($_POST['email']); ?>" />
            </td>
        </tr>
        <tr>
            <td>
                <?php echo  T_('Website'); ?>:
            </td>
            <td>
                <input type="text" name="website" value="<?php echo scrub_out($_POST['website']); ?>" />
            </td>
        </tr>
        <tr>
            <td>
                <?php echo  T_('Password'); ?> :
            </td>
            <td>
                <input type="password" name="password_1" value="" />
                <?php Error::display('password'); ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo  T_('Confirm Password'); ?>:
            </td>
            <td>
                <input type="password" name="password_2" value="" />
            </td>
        </tr>
        <tr>
            <td>
                <?php echo  T_('User Access Level'); ?>:
            </td>
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
            <td>
                <?php echo T_('Avatar'); ?>
            </td>
            <td>
                <input type="file" id="avatar" name="avatar" value="" />
                <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo AmpConfig::get('max_upload_size'); ?>" />
            </td>
        </tr>
    </table>
    <div class="formValidation">
        <?php echo Core::form_register('add_user'); ?>
        <input type="submit" value="<?php echo T_('Add User'); ?>" />
    </div>
</form>
<?php UI::show_box_bottom(); ?>
