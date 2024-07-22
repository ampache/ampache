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

// Because this is a reset of the persons password make the form a little more secure

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;

/** @var Ampache\Repository\Model\User $client */

$web_path       = (string)AmpConfig::get('web_path', '');
$display_fields = (array) AmpConfig::get('registration_display_fields');
$access100      = Access::check('interface', 100); ?>
<?php echo AmpError::display('general'); ?>
<form method="post" name="preferences" action="<?php echo $web_path; ?>/preferences.php?action=update_user" enctype="multipart/form-data">
    <table class="tabledata">
        <?php if (in_array('fullname', $display_fields)) { ?>
            <tr>
                <td><?php echo T_('Full Name'); ?>:</td>
                <td><input type="text" name="fullname" id="fullname" value="<?php echo scrub_out($client->fullname); ?>" /></td>
            </tr>
        <?php } ?>
        <tr>
            <td><?php echo T_('E-mail'); ?>:</td>
            <td><input type="text" name="email" id="email" value="<?php echo scrub_out($client->email); ?>" /></td>
        </tr>
        <?php if (in_array('website', $display_fields)) { ?>
            <tr>
                <td><?php echo T_('Website'); ?>:</td>
                <td><input type="text" name="website" id="website" value="<?php echo scrub_out($client->website); ?>" /></td>
            </tr>
        <?php } ?>
        <?php if (in_array('state', $display_fields)) { ?>
            <tr>
                <td><?php echo T_('State'); ?>:</td>
                <td><input type="text" name="state" id="state" value="<?php echo scrub_out($client->state); ?>" /></td>
            </tr>
        <?php } ?>
        <?php if (in_array('city', $display_fields)) { ?>
            <tr>
                <td><?php echo T_('City'); ?>:</td>
                <td><input type="text" name="city" id="city" value="<?php echo scrub_out($client->city); ?>" /></td>
            </tr>
        <?php } ?>
        <tr>
            <td><?php echo T_('New Password'); ?>:</td>
            <td><?php echo AmpError::display('password'); ?><input id="password1" type="password" name="password1" maxlength="64" autocomplete="new-password" /></td>
        </tr>
        <tr>
            <td><?php echo T_('Confirm Password'); ?>:</td>
            <td><input id="password2" type="password" name="password2" maxlength="64" autocomplete="new-password" /></td>
        </tr>
        <tr>
            <td><?php echo T_('Avatar'); ?> (&lt; <?php echo Ui::format_bytes(AmpConfig::get('max_upload_size')); ?>)</td>
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
                <a href="<?php echo $web_path; ?>/admin/users.php?action=show_delete_avatar&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_icon('delete', T_('Delete')); ?></a>
                <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo AmpConfig::get('max_upload_size'); ?>" /></td>
        </tr>
        <tr>
            <td>
                <?php echo T_('API key'); ?>
                <?php if ($access100) { ?>
                    <a href="<?php echo $web_path; ?>/admin/users.php?action=show_generate_apikey&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_icon('random', T_('Generate new API key')); ?></a>&nbsp;
                    <a href="<?php echo $web_path; ?>/admin/users.php?action=show_delete_apikey&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_icon('delete', T_('Delete')); ?></a>
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
                    <a href="<?php echo $web_path; ?>/admin/users.php?action=show_generate_streamtoken&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_icon('random', T_('Generate new Stream token')); ?></a>&nbsp;
                    <a href="<?php echo $web_path; ?>/admin/users.php?action=show_delete_streamtoken&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_icon('delete', T_('Delete')); ?></a>
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
                    <a href="<?php echo $web_path; ?>/admin/users.php?action=show_generate_rsstoken&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_icon('random', T_('Generate new RSS token')); ?></a>
                    <a href="<?php echo $web_path; ?>/admin/users.php?action=show_delete_rsstoken&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_icon('delete', T_('Delete')); ?></a>
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
            <td><?php echo T_('Clear Stats'); ?>:</td>
            <td><input type="checkbox" name="clear_stats" value="1" /></td>
        </tr>
    </table>
    <div class="formValidation">
            <input type="hidden" name="user_id" value="<?php echo scrub_out((string)$client->id); ?>" />
            <?php echo Core::form_register('update_user'); ?>
            <input type="hidden" name="tab" value="<?php echo scrub_out(Core::get_request('tab')); ?>" />
            <input class="button" type="submit" value="<?php echo T_('Update Account'); ?>" />
    </div>
</form>
