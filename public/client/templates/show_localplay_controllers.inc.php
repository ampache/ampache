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
use Ampache\Module\Playback\Localplay\LocalPlay;

/** @var list<string> $controllers */

$web_path = AmpConfig::get_web_path('/client');

$admin_path = AmpConfig::get_web_path('/admin'); ?>
<!-- Plugin we've found -->
<table class="tabledata striped-rows">
    <thead>
        <tr class="th-top">
            <th class="cel_name"><?php echo T_('Name'); ?></th>
            <th class="cel_description"><?php echo T_('Description'); ?></th>
            <th class="cel_version"><?php echo T_('Version'); ?></th>
            <th class="cel_action"><?php echo T_('Action'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($controllers as $controller) {
            $localplay = new LocalPlay($controller);
            if (!$localplay->player_loaded()) {
                continue;
            }

            if (LocalPlay::is_enabled($controller)) {
                $action     = 'confirm_uninstall_localplay';
                $action_txt = T_('Disable');
            } else {
                $action     = 'confirm_install_localplay';
                $action_txt = T_('Enable');
            } ?>
        <tr>
            <td class="cel_name"><?php echo scrub_out(ucfirst($localplay->type)); ?></td>
            <td class="cel_description"><?php echo scrub_out($localplay->get_f_description()); ?></td>
            <td class="cel_version"><?php echo scrub_out($localplay->get_f_version()); ?></td>
            <td class="cel_action"><a href="<?php echo $admin_path; ?>/modules.php?action=<?php echo $action; ?>&type=<?php echo urlencode($controller); ?>"><?php echo $action_txt; ?></a></td>
        </tr>
        <?php
        } if (!count($controllers)) { ?>
        <tr>
            <td colspan="4"><span class="error"><?php echo T_('No records found'); ?></span></td>
        </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_name"><?php echo T_('Name'); ?></th>
            <th class="cel_description"><?php echo T_('Description'); ?></th>
            <th class="cel_version"><?php echo T_('Version'); ?></th>
            <th class="cel_action"><?php echo T_('Action'); ?></th>
        </tr>
    </tfoot>
</table>
<br />
