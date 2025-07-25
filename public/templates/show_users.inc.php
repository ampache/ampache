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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\User;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\User\Following\UserFollowStateRendererInterface;
use Ampache\Module\Util\Ui;

/** @var Ampache\Repository\Model\Browse $browse */
/** @var list<int> $object_ids */

$web_path = AmpConfig::get_web_path();

$admin_path = AmpConfig::get_web_path('/admin');

// show_user_row.inc.php
$t_send_pm     = T_('Send private message');
$t_edit        = T_('Edit');
$t_preferences = T_('Preferences');
$t_enable      = T_('Enable');
$t_disable     = T_('Disable');
$t_delete      = T_('Delete');
if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
<table class="tabledata striped-rows <?php echo $browse->get_css_class(); ?>" data-objecttype="user">
<colgroup>
  <col id="col_username" />
  <col id="col_lastseen" />
  <col id="col_registrationdate" />
<?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) { ?>
  <col id="col_activity" />
<?php if (AmpConfig::get('track_user_ip')) { ?>
  <col id="col_lastip" />
<?php } ?>
<?php } ?>
  <col id="col_action" />
  <col id="col_online" />
</colgroup>
<thead>
    <tr class="th-top">
      <th class="cel_username essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=username', T_('Username'), 'users_sort_username1'); ?><?php echo " " . Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=fullname', "(" . T_('Full Name') . ")", 'users_sort_fullname1'); ?></th>
      <th class="cel_lastseen"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=last_seen', T_('Last Seen'), 'users_sort_lastseen'); ?></th>
      <th class="cel_registrationdate"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=create_date', T_('Registration Date'), 'users_sort_createdate'); ?></th>
      <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) { ?>
      <th class="cel_activity"><?php echo T_('Activity'); ?></th>
      <?php if (AmpConfig::get('track_user_ip')) { ?>
      <th class="cel_lastip"><?php echo T_('Last IP'); ?></th>
      <?php } ?>
      <?php } ?>
      <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) && AmpConfig::get('sociable')) { ?>
      <th class="cel_follow essential"><?php echo T_('Following'); ?></th>
      <?php } ?>
      <th class="cel_action essential"><?php echo T_('Action'); ?></th>
      <th class="cel_online"><?php echo T_('Online'); ?></th>
    </tr>
</thead>
<tbody>
<?php

global $dic;
$userFollowStateRenderer = $dic->get(UserFollowStateRendererInterface::class);

foreach ($object_ids as $user_id) {
    $libitem = new User($user_id);
    if ($libitem->isNew()) {
        continue;
    }

    $last_seen   = ($libitem->last_seen) ? get_datetime($libitem->last_seen) : T_('Never');
    $create_date = ($libitem->create_date) ? get_datetime($libitem->create_date) : T_('Unknown'); ?>
<tr id="admin_user_<?php echo $libitem->id; ?>">
    <?php require Ui::find_template('show_user_row.inc.php'); ?>
</tr>
<?php } ?>
</tbody>
<tfoot>
    <tr class="th-bottom">
      <th class="cel_username"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=username', T_('Username'), 'users_sort_username1'); ?><?php echo " " . Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=fullname', "(" . T_('Full Name') . ")", 'users_sort_fullname1'); ?></th>
      <th class="cel_lastseen"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=last_seen', T_('Last Seen'), 'users_sort_lastseen1'); ?></th>
      <th class="cel_registrationdate"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=user&sort=create_date', T_('Registration Date'), 'users_sort_createdate1'); ?></th>
      <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) { ?>
      <th class="cel_activity"><?php echo T_('Activity'); ?></th>
      <?php if (AmpConfig::get('track_user_ip')) { ?>
      <th class="cel_lastip"><?php echo T_('Last IP'); ?></th>
      <?php } ?>
      <?php } ?>
      <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) && AmpConfig::get('sociable')) { ?>
      <th class="cel_follow"><?php echo T_('Following'); ?></th>
      <?php } ?>
      <th class="cel_action"><?php echo T_('Action'); ?></th>
      <th class="cel_online"><?php echo T_('Online'); ?></th>
    </tr>
</tfoot>
</table>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
