<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */
?>
<?php /* HINT: Username */ UI::show_box_top(sprintf(T_('%s IP History'), $working_user->fullname)); ?>
<div id="information_actions">
<ul>
<li>
<?php if (isset($_REQUEST['all'])){ ?>
    <a href="<?php echo Config::get('web_path')?>/admin/users.php?action=show_ip_history&user_id=<?php echo $working_user->id?>"><?php echo UI::get_icon('disable'); ?></a>
    <?php echo T_('Show Unique'); ?>
<?php }else{ ?>
    <a href="<?php echo Config::get('web_path')?>/admin/users.php?action=show_ip_history&user_id=<?php echo $working_user->id?>&all"><?php echo UI::get_icon('add'); ?></a>
    <?php echo T_('Show All'); ?>
<?php }?>
</li>
</ul>
</div>
<br />
<br />
<table class="tabledata" cellpadding="0" cellspacing="0">
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
        <?php echo date("d/m/Y H\hi",$data['date']); ?>
    </td>
    <td class="cel_ipaddress">
        <?php echo inet_ntop($data['ip']); ?>
    </td>
</tr>
<?php } ?>
<tr class="th-bottom">
  <th class="cel_date"><?php echo T_('Date'); ?></th>
     <th class="cel_ipaddress"><?php echo T_('IP Address'); ?></th>
</tr>

</table>
<?php UI::show_box_bottom(); ?>
