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
<table class="tabledata" cellpadding="0" cellspacing="0" border="0">
<tr class="table-header" align="center">
        <td colspan="11">
        <?php  if ($view->offset_limit) { require Config::get('prefix') . '/templates/list_header.inc'; } ?>
        </td>
</tr>
<tr class="table-header">
	<td align="center">
		<a href="<?php echo $web_path; ?>/<?php echo $_SESSION['view_script']; ?>?action=<?php echo $_REQUEST['action']; ?>&amp;keep_view=true&amp;sort_type=fullname&amp;sort_order=0">
		<b><?php echo _('Fullname'); ?></b>
		</a>
		<a href="<?php echo $web_path; ?>/<?php echo $_SESSION['view_script']; ?>?action=<?php echo $_REQUEST['action']; ?>&amp;keep_view=true&amp;sort_type=username&amp;sort_order=0">
		<b>(<?php echo _('Username'); ?>)</b>
		</a>
	</td>
        <td align="center">
		<a href="<?php echo $web_path; ?>/<?php echo $_SESSION['view_script']; ?>?action=<?php echo $_REQUEST['action']; ?>&amp;keep_view=true&amp;sort_type=last_seen&amp;sort_order=0">
		<b><?php echo _('Last Seen'); ?></b>
		</a>
	</td>
        <td align="center">
		<a href="<?php echo $web_path; ?>/<?php echo $_SESSION['view_script']; ?>?action=<?php echo $_REQUEST['action']; ?>&amp;keep_view=true&amp;sort_type=create_date&amp;sort_order=0">
		<b><?php echo _('Registration Date'); ?></b>
		</a>
	</td>
        <td align="center">
		<b><?php echo _('Activity'); ?></b>
	</td>
	<?php if (Config::get('track_user_ip')) { ?>
        <td align="center">
		<b><?php echo _('Last Ip'); ?></b>
	</td>
	<?php } ?>
	<td align="center"><strong><?php echo _('Action'); ?></strong></td>
        <td align="center">
		<b><?php echo _('On-line'); ?></b>
	</td>
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
		<a href="<?php echo $web_path; ?>/admin/users.php?action=edit&amp;user_id=<?php echo $client->id; ?>">
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
	<td>
	<?php if (Config::get('track_user_ip')) { ?>
		<a href="<?php echo $web_path; ?>/admin/users.php?action=show_ip_history&amp;user_id=<?php echo $client->id; ?>">
			<?php echo $client->ip_history; ?>
		</a>
	<?php } ?>
		<a href="<?php echo $web_path; ?>/admin/users.php?action=show_edit&amp;user_id=<?php echo $client->id; ?>">
			<?php echo get_user_icon('edit'); ?>
		</a>
		<a href="<?php echo $web_path; ?>/admin/preferences.php?action=user&amp;user_id=<?php echo $client->id; ?>">
			<?php echo get_user_icon('preferences'); ?>
		</a>
	<?php
	//FIXME: Fix this for the extra permission levels
	if ($working_user->disabled == '1') { 
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
</table>
