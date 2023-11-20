<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\User;

/** @var User $workingUser */
/** @var Traversable<array{date: int, ip: string}> $history */
/** @var bool $showAll */
/** @var string $webPath */

?>
<div id="information_actions">
<ul>
<li>
<?php if ($showAll === true) { ?>
    <a href="<?php echo $webPath?>/admin/users.php?action=show_ip_history&user_id=<?php echo $workingUser->getId()?>">
        <?php echo Ui::get_icon('disable', T_('Disable')); ?>
        <?php echo T_('Show Unique'); ?>
    </a>
<?php } else { ?>
    <a href="<?php echo $webPath?>/admin/users.php?action=show_ip_history&user_id=<?php echo $workingUser->getId()?>&all">
        <?php echo Ui::get_icon('add', T_('Add')); ?>
        <?php echo T_('Show All'); ?>
    </a>
<?php } ?>
</li>
</ul>
</div>
<br />
<br />
<table class="tabledata striped-rows">
<colgroup>
  <col id="col_date" />
  <col id="col_ipaddress" />
</colgroup>
<tr class="th-top">
    <th class="cel_date"><?php echo T_('Date'); ?></th>
    <th class="cel_ipaddress"><?php echo T_('IP Address'); ?></th>
</tr>
<?php foreach ($history as $data) { ?>
<tr>
    <td class="cel_date">
        <?php echo get_datetime($data['date']); ?>
    </td>
    <td class="cel_ipaddress">
        <?php echo $data['ip'] ?: T_('Invalid'); ?>
    </td>
</tr>
<?php } ?>
<tr class="th-bottom">
    <th class="cel_date"><?php echo T_('Date'); ?></th>
    <th class="cel_ipaddress"><?php echo T_('IP Address'); ?></th>
</tr>

</table>
