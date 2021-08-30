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
 */

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Application\Admin\Access\Lib\AccessListItemInterface;
use Ampache\Module\Application\Admin\Access\Lib\AccessListTypeEnum;
use Ampache\Module\Util\Ui;

?>
<?php Ui::show_box_top(T_('Access Control'), 'box box_access_control');
$addcurrent = T_('Add Current Host');
$addrpc     = T_('Add API / RPC Host');
$addlocal   = T_('Add Local Network Definition');
$web_path   = AmpConfig::get('web_path'); ?>
<div id="information_actions" class="left-column">
<ul>
    <li>
        <a class="option-list" href="<?php echo $web_path; ?>/admin/access.php?action=show_add&add_type=<?php echo AccessListTypeEnum::ADD_TYPE_CURRENT; ?>"><?php echo Ui::get_icon('add_user', $addcurrent) . ' ' . $addcurrent; ?></a>
    </li>
    <li>
        <a class="option-list" href="<?php echo $web_path; ?>/admin/access.php?action=show_add&add_type=<?php echo AccessListTypeEnum::ADD_TYPE_RPC; ?>"><?php echo Ui::get_icon('cog', $addrpc) . ' ' . $addrpc; ?></a>
    </li>
    <li>
        <a class="option-list" href="<?php echo $web_path; ?>/admin/access.php?action=show_add&add_type=<?php echo AccessListTypeEnum::ADD_TYPE_LOCAL ?>"><?php echo Ui::get_icon('home', $addlocal) . ' ' . $addlocal; ?></a>
    <li>
        <a class="option-list" href="<?php echo $web_path; ?>/admin/access.php?action=show_add_advanced"><?php echo Ui::get_icon('add_key', T_('Advanced Add')) . ' ' . T_('Advanced Add'); ?></a>
    </li>
</ul>
</div>
<?php Ui::show_box_bottom(); ?>
<?php Ui::show_box_top(T_('Access Control Entries'), 'box box_access_entries'); ?>
<?php Ajax::start_container('browse_content', 'browse_content'); ?>
<?php if ($list !== []) { ?>
<table class="tabledata striped-rows">
<thead>
    <tr class="th-top">
        <th><?php echo T_('Name'); ?></th>
        <th><?php echo T_('Start Address'); ?></th>
        <th><?php echo T_('End Address'); ?></th>
        <th><?php echo T_('Level'); ?></th>
        <th><?php echo T_('User'); ?></th>
        <th><?php echo T_('Type'); ?></th>
        <th><?php echo T_('Action'); ?></th>
    </tr>
</thead>
<tbody>
<?php
    /* Start foreach List Item */
    /** @var AccessListItemInterface $access $access */
    foreach ($list as $access) {
        ?>
<tr>
    <td><?php echo scrub_out($access->getName()); ?></td>
    <td><?php echo $access->getStartIp(); ?></td>
    <td><?php echo $access->getEndIp(); ?></td>
    <td><?php echo $access->getLevelName(); ?></td>
    <td><?php echo $access->getUserName(); ?></td>
    <td><?php echo $access->getTypeName(); ?></td>
    <td>
        <a href="<?php echo $web_path; ?>/admin/access.php?action=show_edit_record&amp;access_id=<?php echo $access->getId(); ?>"><?php echo Ui::get_icon('edit', T_('Edit')); ?></a>
        <a href="<?php echo $web_path; ?>/admin/access.php?action=show_delete_record&amp;access_id=<?php echo $access->getId(); ?>"><?php echo Ui::get_icon('delete', T_('Delete')); ?></a>
    </td>
</tr>
    <?php
    } // end foreach?>
</tbody>
</table>
<?php
} // end if count?>
<?php Ajax::end_container(); ?>
<?php Ui::show_box_bottom(); ?>
