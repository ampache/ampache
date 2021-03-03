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

$web_path = AmpConfig::get('web_path'); ?>
<?php if ($browse->is_show_header()) {
    require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
} ?>
<table class="tabledata <?php echo $browse->get_css_class() ?>" data-objecttype="user">
<colgroup>
  <col id="col_username" />
  <col id="col_lastseen" />
  <col id="col_registrationdate" />
<?php if (Access::check('interface', 50)) { ?>
  <col id="col_activity" />
<?php if (AmpConfig::get('track_user_ip')) { ?>
  <col id="col_lastip" />
<?php
    } ?>
<?php
} ?>
  <col id="col_action" />
  <col id="col_online" />
</colgroup>
<thead>
    <tr class="th-top">
      <th class="cel_username essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=username', T_('Username'), 'users_sort_username1');?><?php echo " ( " . Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=fullname', T_('Full Name'), 'users_sort_fullname1') . ")";?></th>
      <th class="cel_lastseen"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=last_seen', T_('Last Seen'), 'users_sort_lastseen'); ?></th>
      <th class="cel_registrationdate"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=create_date', T_('Registration Date'), 'users_sort_createdate'); ?></th>
      <?php if (Access::check('interface', 50)) { ?>
      <th class="cel_activity"><?php echo T_('Activity'); ?></th>
      <?php if (AmpConfig::get('track_user_ip')) { ?>
      <th class="cel_lastip"><?php echo T_('Last IP'); ?></th>
      <?php
        } ?>
      <?php
    } ?>
      <?php if (Access::check('interface', 25) && AmpConfig::get('sociable')) { ?>
      <th class="cel_follow essential"><?php echo T_('Following'); ?></th>
      <?php
    } ?>
      <th class="cel_action essential"><?php echo T_('Action'); ?></th>
      <th class="cel_online"><?php echo T_('Online'); ?></th>
    </tr>
</thead>
<tbody>
<?php
foreach ($object_ids as $user_id) {
        $libitem = new User($user_id);
        $libitem->format();
        $format         = AmpConfig::get('custom_datetime') ? (string) AmpConfig::get('custom_datetime') : 'm/d/Y H:i';
        $last_seen      = $libitem->last_seen ? get_datetime($time_format, $libitem->last_seen) : T_('Never');
        $create_date    = $libitem->create_date ? get_datetime($time_format, $libitem->create_date) : T_('Unknown'); ?>
<tr class="<?php echo UI::flip_class(); ?>" id="admin_user_<?php echo $libitem->id; ?>">
    <?php require AmpConfig::get('prefix') . UI::find_template('show_user_row.inc.php'); ?>
</tr>
<?php
    } //end foreach users?>
</tbody>
<tfoot>
    <tr class="th-bottom">
      <th class="cel_username"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=username', T_('Username'), 'users_sort_username1');?><?php echo " ( " . Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=fullname', T_('Full Name'), 'users_sort_fullname1') . ")";?></th>
      <th class="cel_lastseen"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=last_seen', T_('Last Seen'), 'users_sort_lastseen1'); ?></th>
      <th class="cel_registrationdate"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=create_date', T_('Registration Date'), 'users_sort_createdate1'); ?></th>
      <?php if (Access::check('interface', 50)) { ?>
      <th class="cel_activity"><?php echo T_('Activity'); ?></th>
      <?php if (AmpConfig::get('track_user_ip')) { ?>
      <th class="cel_lastip"><?php echo T_('Last IP'); ?></th>
      <?php
        } ?>
      <?php
    } ?>
      <?php if (Access::check('interface', 25) && AmpConfig::get('sociable')) { ?>
      <th class="cel_follow"><?php echo T_('Following'); ?></th>
      <?php
    } ?>
      <th class="cel_action"><?php echo T_('Action'); ?></th>
      <th class="cel_online"><?php echo T_('Online'); ?></th>
    </tr>
</tfoot>
</table>
<?php if ($browse->is_show_header()) {
        require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
    } ?>
