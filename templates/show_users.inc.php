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

$web_path = conf('web_path');
$total_items = $view->total_items;
$admin_menu = "admin/";

show_box_top(_('Manage Users')); 
?>
<table class="tabledata" cellpadding="0" cellspacing="10" border="0">
<tr>
<td>
<?php
	echo get_user_icon('add_user') . '&nbsp;'; 
	echo '<a href="' . $web_path . '/admin/users.php?action=show_add_user">' . _('Add a new user') . '</a>';
	if (isset ($_REQUEST['action']) && $_REQUEST['action'] == "show_inactive"){
	?>
</td>
</tr>
<form name="show_inactive" enctype="multipart/form-data" method="request" action="<?php echo conf('web_path') . "/admin/users.php"; ?>">
<tr align="center">
        <td>
        Inactive users for&nbsp;&nbsp;<input type=text name="days" size="4" value="<?php if (isset ($_REQUEST['days'])){ echo $_REQUEST['days'];}?>" />&nbsp;&nbsp;days
        </td>
</tr>
<tr>
	<td>
	<input type="hidden" name="action" value="show_inactive" />
	<input type="Submit" />
	</td>
</tr>
</form>
	<?php
	}?>
</table>
<?php
show_box_bottom(); 
?>
<?php show_box_top(); ?>
<table class="tabledata" cellpadding="0" cellspacing="0" border="0">
<tr class="table-header" align="center">
        <td colspan="11">
        <?php  if ($view->offset_limit) { require (conf('prefix') . "/templates/list_header.inc"); } ?>
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
	<?php if (conf('track_user_ip')) { ?>
        <td align="center">
		<b><?php echo _('Last Ip'); ?></b>
	</td>
	<?php } ?>
	<td colspan="5">&nbsp;</td>
        <td align="center">
		<b><?php echo _('On-line'); ?></b>
	</td>
</tr>
<?php
foreach ($users as $working_user) { 
	$working_user->format_user();
        $last_seen = date("m\/d\/Y - H:i",$working_user->last_seen);
        if (!$working_user->last_seen) { $last_seen = _('Never'); }
        $create_date = date("m\/d\/Y - H:i",$working_user->create_date);
        if (!$working_user->create_date) { $create_date = _('Unknown'); }
?>
<tr class="<?php echo flip_class(); ?>" align="center">
	<td align="left">
		<a href="<?php echo $web_path; ?>/admin/users.php?action=edit&amp;user_id=<?php echo $working_user->id; ?>">
			<?php echo $working_user->fullname; ?> (<?php echo $working_user->username; ?>)
		</a>
	</td>
        <td>
		<?php echo $last_seen; ?>
	</td>
        <td>
		<?php echo $create_date; ?>
	</td>

        <td>
		<?php echo $working_user->f_useage; ?>
	</td>
	<?php if (conf('track_user_ip')) { ?>
        <td>
		<a href="<?php echo $web_path; ?>/admin/users.php?action=show_ip_history&amp;user_id=<?php echo $working_user->id; ?>">
			<?php echo $working_user->ip_history; ?>
		</a>
	</td>
	<?php } ?>
        <td>
		<a href="<?php echo $web_path; ?>/admin/users.php?action=edit&amp;user_id=<?php echo $working_user->id; ?>">
			<?php echo get_user_icon('edit'); ?>
		</a>
	</td>
        <td>
		<a href="<?php echo $web_path; ?>/admin/preferences.php?action=user&amp;user_id=<?php echo $working_user->id; ?>">
			<?php echo get_user_icon('preferences'); ?>
		</a>
	</td>
	<td>
		<a href="<?php echo $web_path; ?>/stats.php?action=user_stats&amp;user_id=<?php echo $working_user->id; ?>">
			<?php echo get_user_icon('statistics'); ?>
		</a>
	</td>
	<?php
	//FIXME: Fix this for the extra permission levels
	if ($working_user->disabled == '1') { 
		echo "<td><a href=\"".$web_path."/admin/users.php?action=enable&amp;user_id=$working_user->id\">" . get_user_icon('enable') . "</a></td>";
	}
	else {
		echo "<td><a href=\"".$web_path."/admin/users.php?action=disable&amp;user_id=$working_user->id\">" . get_user_icon('disable') ."</a></td>";
	}
	?>
	<td>
		<a href="<?php echo $web_path; ?>/admin/users.php?action=delete&amp;user_id=<?php echo $working_user->id; ?>">
		<?php echo get_user_icon('delete'); ?>
		</a>
	</td>
       <?php
	if (($working_user->is_logged_in()) and ($working_user->is_online())) {
		echo "<td class=\"user_online\"> &nbsp; </td>";
	} elseif ($working_user->disabled == 1) {
		echo "<td class=\"user_disabled\"> &nbsp; </td>";
	} else {
		echo "<td class=\"user_offline\"> &nbsp; </td>";
	}
?>	
</tr>
<?php } //end foreach users ?>
</table>
<?php show_box_bottom(); ?>
