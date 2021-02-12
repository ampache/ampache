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
<?php /* HINT: Username */ UI::show_box_top(sprintf(T_('%s IP History'), $working_user->fullname)); ?>
<div id="information_actions">
<ul>
<li>
<?php if (isset($_REQUEST['all'])) { ?>
    <a href="<?php echo AmpConfig::get('web_path')?>/admin/users.php?action=show_ip_history&user_id=<?php echo $working_user->id?>"><?php echo UI::get_icon('disable', T_('Disable')); ?></a>
    <?php echo T_('Show Unique'); ?>
<?php
} else { ?>
    <a href="<?php echo AmpConfig::get('web_path')?>/admin/users.php?action=show_ip_history&user_id=<?php echo $working_user->id?>&all"><?php echo UI::get_icon('add', T_('Add')); ?></a>
    <?php echo T_('Show All'); ?>
<?php
    }?>
</li>
</ul>
</div>
<br />
<br />
<table class="tabledata">
<colgroup>
  <col id="col_date" />
  <col id="col_ipaddress" />
</colgroup>
<tr class="th-top">
  <th class="cel_date"><?php echo T_('Date'); ?></th>
     <th class="cel_ipaddress"><?php echo T_('IP Address'); ?></th>
</tr>
<?php foreach ($history as $data) { ?>
<tr class="<?php echo UI::flip_class(); ?>">
    <td class="cel_date">
        <?php echo get_datetime($time_format, (int) $data['date']); ?>
    </td>
    <td class="cel_ipaddress">
        <?php echo (inet_ntop($data['ip'])) ? inet_ntop($data['ip']) : T_('Invalid'); ?>
    </td>
</tr>
<?php
    } ?>
<tr class="th-bottom">
  <th class="cel_date"><?php echo T_('Date'); ?></th>
     <th class="cel_ipaddress"><?php echo T_('IP Address'); ?></th>
</tr>

</table>
<?php UI::show_box_bottom(); ?>
