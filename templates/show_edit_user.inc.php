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
<?php UI::show_box_top(T_('Editing Existing User')); ?>
<?php AmpError::display('general'); ?>
<form name="update_user" enctype="multipart/form-data" method="post" action="<?php echo AmpConfig::get('web_path') . "/admin/users.php"; ?>">
    <table class="tabledata">
        <tr>
            <th colspan="2"><?php echo T_('User Properties'); ?></th>
        </tr>
        <tr>
            <td><?php echo T_('Username'); ?>:</td>
            <td><input type="text" name="username" maxlength="128" value="<?php echo scrub_out($client->username); ?>" autofocus />
                <?php AmpError::display('username'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Full Name'); ?>:</td>
            <td><input type="text" name="fullname" value="<?php echo scrub_out($client->fullname); ?>" />
                <input type="checkbox" name="fullname_public" value="1" <?php if ($client->fullname_public) {
    echo "checked";
} ?> /> <?php echo T_('Public'); ?>
                <?php AmpError::display('fullname'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('E-mail'); ?>:</td>
            <td><input type="text" name="email" value="<?php echo scrub_out($client->email); ?>" />
                <?php AmpError::display('email'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo  T_('Website'); ?>:</td>
            <td><input type="text" name="website" value="<?php echo scrub_out($client->website); ?>" />
                <?php AmpError::display('website'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo  T_('State'); ?>:</td>
            <td><input type="text" name="state" value="<?php echo scrub_out($client->state); ?>" autocomplete="off" />
                <?php AmpError::display('state'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo  T_('City'); ?>:</td>
            <td><input type="text" name="city" value="<?php echo scrub_out($client->city); ?>" autocomplete="off" />
                <?php AmpError::display('city'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Password'); ?>:</td>
            <td><input type="password" name="password_1" value="" autocomplete="off" />
                <?php AmpError::display('password'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Confirm Password'); ?>:</td>
            <td><input type="password" name="password_2" value="" autocomplete="off" /></td>
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
        </tr>
        <tr>
            <td>
        </td>
        <td>
                <?php
                if ($client->f_avatar) {
                    echo $client->f_avatar;
                } ?>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/admin/users.php?action=show_delete_avatar&user_id=<?php echo $client->id; ?>"><?php echo UI::get_icon('delete', T_('Delete')); ?></a>
                <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo AmpConfig::get('max_upload_size'); ?>" /></td>
        </tr>
        <tr>
            <th colspan="2"><?php echo T_('Other Options'); ?></th>
        </tr>
        <tr>
            <td>
                <?php echo T_('API Key'); ?>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/admin/users.php?action=show_generate_apikey&user_id=<?php echo $client->id; ?>"><?php echo UI::get_icon('random', T_('Generate new API Key')); ?></a>
            </td>
            <td>
                <span>
                    <?php if ($client->apikey) { ?>
                    <br />
                    <div style="background-color: #ffffff; border: 8px solid #ffffff; width: 128px; height: 128px;">
                        <div id="apikey_qrcode"></div>
                    </div>
                    <br />
                    <script>$('#apikey_qrcode').qrcode({width: 128, height: 128, text: '<?php echo $client->apikey; ?>', background: '#ffffff', foreground: '#000000'});</script>
                    <?php echo $client->apikey; ?>
                    <?php
                } ?>
                </span>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo T_('RSS Token'); ?>
                <?php if (Access::check('interface', 100)) { ?>
                    <a href="<?php echo AmpConfig::get('web_path'); ?>/admin/users.php?action=show_generate_rsstoken&user_id=<?php echo $client->id; ?>"><?php echo UI::get_icon('random', T_('Generate new RSS token')); ?></a>
                <?php } ?>
            </td>
            <td>
                <span>
                    <?php if ($client->rsstoken) {
                    echo $client->rsstoken;
                } ?>
                </span>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Config Preset'); ?></td>
            <td>
                <select name="preset">
                    <option value=""></option>
                    <option value="democratic"><?php echo T_('Democratic'); ?></option>
                    <option value="localplay"><?php echo T_('Localplay'); ?></option>
                    <option value="flash"><?php echo T_('Flash'); ?></option>
                    <option value="stream"><?php echo T_('Stream'); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Prevent Preset Override'); ?></td>
            <td><input type="checkbox" value="1" name="prevent_override" /><span class="information"> <?php echo T_('This affects all non-admin accounts'); ?></span>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Clear Stats'); ?></td>
            <td><input type="checkbox" value="1" name="reset_stats" /></td>
        </tr>
    </table>
    <div class="formValidation">
        <input type="hidden" name="action" value="update_user" />
        <input type="submit" value="<?php echo T_('Update User'); ?>" />
        <?php echo Core::form_register('edit_user'); ?>
        <input type="hidden" name="user_id" value="<?php echo $client->id; ?>" />
    </div>
</form>
<?php UI::show_box_bottom(); ?>
