<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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

$web_path = AmpConfig::get('web_path');

?>
<?php if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php'; ?>
<table class="tabledata" cellpadding="0" cellspacing="0" data-objecttype="user">
<colgroup>
  <col id="col_username" />
  <col id="col_lastseen" />
  <col id="col_registrationdate" />
  <col id="col_activity" />
    <?php if (AmpConfig::get('track_user_ip')) { ?>
  <col id="col_lastip" />
    <?php } ?>
  <col id="col_action" />
  <col id="col_online" />
</colgroup>
<thead>
    <tr class="th-top">
      <th class="cel_username"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=fullname', T_('Fullname'),'users_sort_fullname'); ?>( <?php echo Ajax::text('?page=browse&action=set_sort&type=user&sort=username', T_('Username'),'users_sort_username');?>)</th>
      <th class="cel_lastseen"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=last_seen', T_('Last Seen'),'users_sort_lastseen'); ?></th>
      <th class="cel_registrationdate"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=create_date', T_('Registration Date'),'users_sort_createdate'); ?></th>
      <th class="cel_activity"><?php echo T_('Activity'); ?></th>
        <?php if (AmpConfig::get('track_user_ip')) { ?>
      <th class="cel_lastip"><?php echo T_('Last Ip'); ?></th>
        <?php } ?>
        <th class="cel_action"><?php echo T_('Action'); ?></th>
      <th class="cel_online"><?php echo T_('On-line'); ?></th>
    </tr>
</thead>
<tbody>
<?php
foreach ($object_ids as $user_id) {
    $libitem = new User($user_id);
    $libitem->format();
    $last_seen     = $libitem->last_seen ? date("m\/d\/Y - H:i",$libitem->last_seen) : T_('Never');
    $create_date    = $libitem->create_date ? date("m\/d\/Y - H:i",$libitem->create_date) : T_('Unknown');
?>
<tr class="<?php echo UI::flip_class(); ?>" id="admin_user_<?php echo $libitem->id; ?>">
    <?php require AmpConfig::get('prefix') . '/templates/show_user_row.inc.php'; ?>
</tr>
<?php } //end foreach users ?>
</tbody>
<tfoot>
    <tr class="th-bottom">
        <th class="cel_username"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=fullname', T_('Fullname'),'users_sort_fullname1'); ?>( <?php echo Ajax::text('?page=browse&action=set_sort&type=user&sort=username', T_('Username'),'users_sort_username1');?>)</th>
      <th class="cel_lastseen"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=last_seen', T_('Last Seen'),'users_sort_lastseen1'); ?></th>
      <th class="cel_registrationdate"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=create_date', T_('Registration Date'),'users_sort_createdate1'); ?></th>
      <th class="cel_activity"><?php echo T_('Activity'); ?></th>
        <?php if (AmpConfig::get('track_user_ip')) { ?>
      <th class="cel_lastip"><?php echo T_('Last Ip'); ?></th>
        <?php } ?>
        <th class="cel_action"><?php echo T_('Action'); ?></th>
      <th class="cel_online"><?php echo T_('On-line'); ?></th>
    </tr>
</tfoot>
</table>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/tabledata.js" language="javascript" type="text/javascript"></script>
<?php if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php'; ?>
