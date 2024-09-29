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
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Module\Util\Ui;

global $dic;
$licenseRepository = $dic->get(LicenseRepositoryInterface::class);

/** @var list<int> $object_ids */

$admin_path = AmpConfig::get_web_path('/admin'); ?>
<div id="information_actions">
    <ul>
        <li>
            <a href="<?php echo $admin_path; ?>/license.php?action=show_create"><?php echo T_('Create License'); ?></a>
        </li>
    </ul>
</div>
<table class="tabledata striped-rows">
    <thead>
        <tr class="th-top">
            <th class="cel_name"><?php echo T_('Name'); ?></th>
            <th class="cel_description"><?php echo T_('Description'); ?></th>
            <th class="cel_order"><?php echo T_('Order'); ?></th>
            <th class="cel_action"><?php echo T_('Action'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($object_ids as $license_id) {
            $libitem = $licenseRepository->findById($license_id);
            if ($libitem === null) {
                continue;
            }
            require Ui::find_template('show_license_row.inc.php');
        } ?>
        <?php if (!count($object_ids)) { ?>
        <tr>
            <td colspan="6" class="error"><?php echo T_('No licenses found'); ?></td>
        </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_name"><?php echo T_('Name'); ?></th>
            <th class="cel_description"><?php echo T_('Description'); ?></th>
            <th class="cel_order"><?php echo T_('Order'); ?></th>
            <th class="cel_action"><?php echo T_('Action'); ?></th>
        </tr>
    </tfoot>
</table>
