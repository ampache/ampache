<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

$web_path = Config::get('web_path');

?>
<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_username" />
  <col id="col_lastseen" />
  <col id="col_registrationdate" />
  <col id="col_activity" />
	<?php if (Config::get('track_user_ip')) { ?>
  <col id="col_lastip" />
	<?php } ?>
  <col id="col_action" />
  <col id="col_online" />
</colgroup>
<tr class="th-top">
  <th class="cel_username"><?php echo Ajax::text('?page=browse&action=set_sort&type=user&sort=fullname',_('Fullname'),'users_sort_fullname'); ?>( <?php echo Ajax::text('?page=browse&action=set_sort&type=user&sort=username',_('Username'),'users_sort_username');?>)</th>
  <th class="cel_lastseen"><?php echo Ajax::text('?page=browse&action=set_sort&type=user&sort=last_seen',_('Last Seen'),'users_sort_lastseen'); ?></th>
  <th class="cel_registrationdate"><?php echo Ajax::text('?page=browse&action=set_sort&type=user&sort=create_date',_('Registration Date'),'users_sort_createdate'); ?></th>
  <th class="cel_activity"><?php echo _('Activity'); ?></th>
	<?php if (Config::get('track_user_ip')) { ?>
  <th class="cel_lastip"><?php echo _('Last Ip'); ?></th>
	<?php } ?>
	<th class="cel_action"><?php echo _('Action'); ?></th>
  <th class="cel_online"><?php echo _('On-line'); ?></th>
</tr>
<?php
foreach ($object_ids as $user_id) { 
	$client = new User($user_id); 
	$client->format(); 
        $last_seen 	= $client->last_seen ? date("m\/d\/Y - H:i",$client->last_seen) : _('Never');
        $create_date	= $client->create_date ? date("m\/d\/Y - H:i",$client->create_date) : _('Unknown');
?>
<tr class="<?php echo flip_class(); ?>" align="center" id="admin_user_<?php echo $client->id; ?>">
	<?php require Config::get('prefix') . '/templates/show_user_row.inc.php'; ?>
</tr>
<?php } //end foreach users ?>
<tr class="th-bottom">
	<th class="cel_username"><?php echo Ajax::text('?page=browse&action=set_sort&type=user&sort=fullname',_('Fullname'),'users_sort_fullname1'); ?>( <?php echo Ajax::text('?page=browse&action=set_sort&type=user&sort=username',_('Username'),'users_sort_username1');?>)</th>
  <th class="cel_lastseen"><?php echo Ajax::text('?page=browse&action=set_sort&type=user&sort=last_seen',_('Last Seen'),'users_sort_lastseen1'); ?></th>
  <th class="cel_registrationdate"><?php echo Ajax::text('?page=browse&action=set_sort&type=user&sort=create_date',_('Registration Date'),'users_sort_createdate1'); ?></th>
  <th class="cel_activity"><?php echo _('Activity'); ?></th>
	<?php if (Config::get('track_user_ip')) { ?>
  <th class="cel_lastip"><?php echo _('Last Ip'); ?></th>
	<?php } ?>
	<th class="cel_action"><?php echo _('Action'); ?></th>
  <th class="cel_online"><?php echo _('On-line'); ?></th>
</tr>
</table>
