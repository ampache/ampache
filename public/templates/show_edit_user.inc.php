<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
 */

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\User;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Catalog;

/** @var User $client */

$admin_path = AmpConfig::get_web_path('/admin');
$access100  = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN); ?>
<?php Ui::show_box_top(T_('Editing Existing User')); ?>
<?php echo AmpError::display('general'); ?>
<form name="update_user" enctype="multipart/form-data" method="post" action="<?php echo $admin_path . "/users.php"; ?>">
    <table class="tabledata">
        <tr>
            <th colspan="2"><?php echo T_('User Properties'); ?></th>
        </tr>
        <tr>
            <td><?php echo T_('Username'); ?></td>
            <td><input type="text" name="username" maxlength="128" value="<?php echo $client->username; ?>" autocomplete="off" autofocus />
                <?php echo AmpError::display('username'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Full Name'); ?></td>
            <td><input type="text" name="fullname" maxlength="255" value="<?php echo $client->fullname; ?>" />
                <input type="checkbox" name="fullname_public" value="1" <?php if ($client->fullname_public) {
                    echo "checked";
                } ?> /> <?php echo T_('Public'); ?>
                <?php echo AmpError::display('fullname'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('E-mail'); ?></td>
            <td><input type="text" name="email" maxlength="128" value="<?php echo scrub_out($client->email); ?>" />
                <?php echo AmpError::display('email'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Website'); ?></td>
            <td><input type="text" name="website" maxlength="255" value="<?php echo scrub_out($client->website); ?>" />
                <?php echo AmpError::display('website'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('State'); ?></td>
            <td><input type="text" name="state" maxlength="64" value="<?php echo scrub_out($client->state); ?>" autocomplete="off" />
                <?php echo AmpError::display('state'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('City'); ?></td>
            <td><input type="text" name="city" maxlength="64" value="<?php echo scrub_out($client->city); ?>" autocomplete="off" />
                <?php echo AmpError::display('city'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Password'); ?></td>
            <td><input type="password" name="password_1" maxlength="64" value="" autocomplete="new-password" />
                <?php echo AmpError::display('password'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Confirm Password'); ?></td>
            <td><input type="password" name="password_2" maxlength="64" value="" autocomplete="new-password" /></td>
        </tr>
        <tr>
            <td><?php echo T_('User Access Level'); ?></td>
            <td>
                <?php $user_access = 'on_' . (string)$client->access;
$on_5                              = '';
$on_25                             = '';
$on_50                             = '';
$on_75                             = '';
$on_100                            = '';
switch ($user_access) {
    case 'on_5':
        $on_5 = 'selected="selected"';
        break;
    case 'on_25':
        $on_25 = 'selected="selected"';
        break;
    case 'on_50':
        $on_50 = 'selected="selected"';
        break;
    case 'on_75':
        $on_75 = 'selected="selected"';
        break;
    case 'on_100':
        $on_100 = 'selected="selected"';
        break;
} ?>
                <select name="access">
                    <option value="5" <?php echo $on_5; ?>><?php echo T_('Guest'); ?></option>
                    <option value="25" <?php echo $on_25; ?>><?php echo T_('User'); ?></option>
                    <option value="50" <?php echo $on_50; ?>><?php echo T_('Content Manager'); ?></option>
                    <option value="75" <?php echo $on_75; ?>><?php echo T_('Catalog Manager'); ?></option>
                    <option value="100" <?php echo $on_100; ?>><?php echo T_('Admin'); ?></option>
                </select>
            </td>
        </tr>

<?php if (AmpConfig::get('catalog_filter')) { ?>
        <tr>
            <td><?php echo T_('Catalog Filter'); ?></td>
            <td><?php

    $filters = Catalog::get_catalog_filters();
    $options = [];
    foreach ($filters as $filter) {
        $selected = "";
        if ($filter['id'] == $client->catalog_filter_group) {
            $selected = ' selected = "selected" ';
        }
        $options[] = '<option value="' . $filter['id'] . '" ' . $selected . '>' . scrub_out($filter['name']) . '</option>';
    }
    echo '<select name="catalog_filter_group">' . implode("\n", $options) . '</select>';
} ?>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Avatar'); ?> (&lt; <?php echo Ui::format_bytes(AmpConfig::get('max_upload_size')); ?>)</td>
            <td><input type="file" id="avatar" name="avatar" value="" />
        </tr>
        <tr>
            <td>
            </td>
            <td>
                <?php echo $client->get_f_avatar('f_avatar'); ?>
                <a href="<?php echo $admin_path; ?>/users.php?action=show_delete_avatar&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_material_symbol('close', T_('Delete')); ?></a>
                <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo AmpConfig::get('max_upload_size'); ?>" />
            </td>
        </tr>
        <tr>
            <th colspan="2"><?php echo T_('Other Options'); ?></th>
        </tr>
        <tr>
            <td>
                <?php echo T_('API key'); ?>
                <a href="<?php echo $admin_path; ?>/users.php?action=show_generate_apikey&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_material_symbol('cycle', T_('Generate new API key')); ?></a>&nbsp;
                <?php if ($client->apikey) { ?>
                <a href="<?php echo $admin_path; ?>/users.php?action=show_delete_apikey&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_material_symbol('close', T_('Delete')); ?></a>
                <?php } ?>
            </td>
            <td>
                <span>
                    <?php if ($client->apikey) {
                        echo "<br /><div style=\"background-color: #ffffff; border: 8px solid #ffffff; width: 128px; height: 128px;\"><div id=\"apikey_qrcode\"></div></div><br /><script>$('#apikey_qrcode').qrcode({width: 128, height: 128, text: '" . $client->apikey . "', background: '#ffffff', foreground: '#000000'});</script>" . $client->apikey;
                    } ?>
                </span>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo T_('Stream Token'); ?>
                <?php if ($access100) { ?>
                    <a href="<?php echo $admin_path; ?>/users.php?action=show_generate_streamtoken&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_material_symbol('cycle', T_('Generate new Stream token')); ?></a>&nbsp;
                    <?php if ($client->streamtoken) { ?>
                    <a href="<?php echo $admin_path; ?>/users.php?action=show_delete_streamtoken&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_material_symbol('close', T_('Delete')); ?></a>
                    <?php } ?>
                <?php } ?>
            </td>
            <td>
                <span>
                    <?php if ($client->streamtoken) {
                        echo $client->streamtoken;
                    } ?>
                </span>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo T_('RSS Token'); ?>
                <?php if ($access100) { ?>
                    <a href="<?php echo $admin_path; ?>/users.php?action=show_generate_rsstoken&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_material_symbol('cycle', T_('Generate new RSS token')); ?></a>
                    <?php if ($client->rsstoken) { ?>
                    <a href="<?php echo $admin_path; ?>/users.php?action=show_delete_rsstoken&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_material_symbol('close', T_('Delete')); ?></a>
                    <?php } ?>
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
        <?php if ($client->access !== 100) { ?>
        <tr>
            <td><?php echo T_('Config Preset'); ?>&nbsp;<span class="information">(<?php echo T_('This affects all non-admin accounts'); ?>)</span></td>
            <td>
                <select name="preset">
                    <option value=""></option>preset
                    <option value="system"><?php echo T_('System'); ?></option>
                    <option value="default"><?php echo T_('Default'); ?></option>
                    <option value="minimalist"><?php echo T_('Minimalist'); ?></option>
                    <option value="community"><?php echo T_('Community'); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Prevent Preset Override'); ?></td>
            <td><input type="checkbox" checked="checked" value="1" name="prevent_override" />
            </td>
        </tr>
        <?php } ?>
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
<?php Ui::show_box_bottom(); ?>
