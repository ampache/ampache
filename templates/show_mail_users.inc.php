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

<?php UI::show_box_top(T_('E-mail Users'), 'box box_mail_users'); ?>
<form name="mail" method="post" action="<?php echo AmpConfig::get('web_path'); ?>/admin/mail.php?action=send_mail" enctype="multipart/form-data">
    <table class="tabledata">
        <tr>
            <td><?php echo T_('Mail to'); ?>:</td>
            <td>
                <select name="to">
                    <option value="all" title="<?php echo T_('Mail Everyone'); ?>"><?php echo T_('All'); ?></option>
                    <option value="users" title="<?php echo T_('Mail Users'); ?>"><?php echo T_('User'); ?></option>
                    <option value="admins" title="<?php echo T_('Mail Admins'); ?>"><?php echo T_('Admin'); ?></option>
                    <option value="inactive" title="<?php echo T_('Mail Inactive Users'); ?>"><?php echo T_('Inactive Users'); ?>&nbsp;</option>
                </select>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('From'); ?>:</td>
            <td>
                <select name="from">
                    <option value="self" title="<?php echo T_('Self'); ?>"><?php echo T_('Yourself'); ?></option>
                    <option value="system" title="<?php echo T_('System'); ?>"><?php echo T_('Ampache'); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Subject'); ?>:</td>
            <td colspan="3">
                <input name="subject" value="<?php echo scrub_out(Core::get_post('subject')); ?>" /></td>
        </tr>
        <tr>
            <td><?php echo T_('Message'); ?>:</td>
            <td>
                <textarea class="input" name="message" rows="10" cols="70"></textarea>
            </td>
        </tr>
    </table>
    <div class="formValidation">
        <input class="button" type="submit" value="<?php echo T_('Send e-mail'); ?>" />
    </div>
</form>
<?php UI::show_box_bottom(); ?>
