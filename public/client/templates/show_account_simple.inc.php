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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\AmpError;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\User;

/** @var User $client */

$web_path = AmpConfig::get_web_path('/client');

$admin_path = AmpConfig::get_web_path('/admin');

$access100      = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN);
$display_fields = (array) AmpConfig::get('registration_display_fields'); ?>
<?php echo AmpError::display('general'); ?>
    <table class="tabledata">
            <tr>
                <td><?php echo T_('Full Name'); ?>:</td>
                <td>
                    <?php echo scrub_out($client->fullname); ?>
                </td>
            </tr>
        <tr>
            <td><?php echo T_('E-mail'); ?>:</td>
            <td>
                <?php echo scrub_out($client->email); ?>
            </td>
        </tr>
            <tr>
                <td><?php echo T_('Website'); ?>:</td>
                <td>
                    <?php echo scrub_out($client->website); ?>
                </td>
            </tr>
            <tr>
                <td><?php echo T_('State'); ?>:</td>
                <td>
                    <?php echo scrub_out($client->state); ?>
                </td>
            </tr>
            <tr>
                <td><?php echo T_('City'); ?>:</td>
                <td>
                    <?php echo scrub_out($client->city); ?>
                </td>
            </tr>
        <tr>
            <td>
                <?php echo T_('Avatar'); ?> (&lt; <?php echo Ui::format_bytes(AmpConfig::get('max_upload_size')); ?>)
            </td>
        <tr>
            <td>
        </td>
        <td>
          <?php
                echo $client->get_f_avatar('f_avatar') ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo T_('API key'); ?>
                <?php if ($access100) { ?>
                    <a href="<?php echo $admin_path; ?>/users.php?action=show_generate_apikey&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_material_symbol('cycle', T_('Generate new API key')); ?></a>
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
                <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)) { ?>
                    <a href="<?php echo $admin_path; ?>/users.php?action=show_generate_streamtoken&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_material_symbol('cycle', T_('Generate new Stream token')); ?></a>
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
    </table>
