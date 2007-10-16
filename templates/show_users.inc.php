<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
  <col id="br_username" />
  <col id="br_lastseen" />
  <col id="br_registrationdate" />
  <col id="br_activity" />
  <col id="br_lastip" />
  <col id="br_action" />
  <col id="br_online" />
</colgroup>
<tr class="table-header th-top">
	<th><?php echo _('Fullname'); ?>(<?php echo _('Username'); ?>)</th>
  <th><?php echo _('Last Seen'); ?></th>
  <th><?php echo _('Registration Date'); ?></th>
  <th><?php echo _('Activity'); ?></th>
	<?php if (Config::get('track_user_ip')) { ?>
  <th><?php echo _('Last Ip'); ?></th>
	<?php } ?>
	<th><?php echo _('Action'); ?></th>
  <th><?php echo _('On-line'); ?></th>
</tr>
<?php
foreach ($object_ids as $user_id) { 
	$client = new User($user_id); 
	$client->format(); 
        $last_seen 	= $client->last_seen ? date("m\/d\/Y - H:i",$client->last_seen) : _('Never');
        $create_date	= $client->create_date ? date("m\/d\/Y - H:i",$client->create_date) : _('Unknown');
?>
<tr class="<?php echo flip_class(); ?>" align="center">
	<td align="left">
		<a href="<?php echo $web_path; ?>/stats.php?action=show_user&amp;user_id=<?php echo $client->id; ?>">
			<?php echo $client->fullname; ?> (<?php echo $client->username; ?>)
		</a>
	</td>
        <td>
		<?php echo $last_seen; ?>
	</td>
        <td>
		<?php echo $create_date; ?>
	</td>

        <td>
		<?php echo $client->f_useage; ?>
	</td>
	<?php if (Config::get('track_user_ip')) { ?>
		<td>
		<a href="<?php echo $web_path; ?>/admin/users.php?action=show_ip_history&amp;user_id=<?php echo $client->id; ?>">
			<?php echo $client->ip_history; ?>
		</a>
		</td>
	<?php } ?>
	<td>
		<a href="<?php echo $web_path; ?>/admin/users.php?action=show_edit&amp;user_id=<?php echo $client->id; ?>">
			<?php echo get_user_icon('edit'); ?>
		</a>
	<?php
	//FIXME: Fix this for the extra permission levels
	if ($client->disabled == '1') { 
		echo "<a href=\"".$web_path."/admin/users.php?action=enable&amp;user_id=$client->id\">" . get_user_icon('enable') . "</a>";
	}
	else {
		echo "<a href=\"".$web_path."/admin/users.php?action=disable&amp;user_id=$client->id\">" . get_user_icon('disable') ."</a>";
	}
	?>
		<a href="<?php echo $web_path; ?>/admin/users.php?action=delete&amp;user_id=<?php echo $client->id; ?>">
		<?php echo get_user_icon('delete'); ?>
		</a>
	</td>
       <?php
	if (($client->is_logged_in()) AND ($client->is_online())) {
		echo "<td class=\"user_online\"> &nbsp; </td>";
	} elseif ($client->disabled == 1) {
		echo "<td class=\"user_disabled\"> &nbsp; </td>";
	} else {
		echo "<td class=\"user_offline\"> &nbsp; </td>";
	}
?>	
</tr>
<?php } //end foreach users ?>
<tr class="table-header th-bottom">
	<th><?php echo _('Fullname'); ?>(<?php echo _('Username'); ?>)</th>
  <th><?php echo _('Last Seen'); ?></th>
  <th><?php echo _('Registration Date'); ?></th>
  <th><?php echo _('Activity'); ?></th>
	<?php if (Config::get('track_user_ip')) { ?>
  <th><?php echo _('Last Ip'); ?></th>
	<?php } ?>
	<th><?php echo _('Action'); ?></th>
  <th><?php echo _('On-line'); ?></th>
</tr>
</table>
