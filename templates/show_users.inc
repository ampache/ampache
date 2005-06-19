<?php
/*

 Copyright (c) 2004 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
/*!
 @header Show Users (admin section)

*/
$total_items = $view->total_items;
$admin_menu = "admin/";
?>


<table class="tabledata" cellpadding="0" cellspacing="0" border="0">
<tr class="even" align="center">
        <td colspan="8">
        <?php  if ($view->offset_limit) { require (conf('prefix') . "/templates/list_header.inc"); } ?>
        </td>
</tr>
<tr class="table-header">
	<td align="center">
		<a href="<?php echo conf('web_path'); ?>/<?php echo $_SESSION['view_script']; ?>?action=<?php echo $_REQUEST['action']; ?>&amp;keep_view=true&amp;sort_type=username&amp;sort_order=0">
		<b><?php echo _("Username"); ?></b>
		</a> 
		<a href="<?php echo conf('web_path'); ?>/<?php echo $_SESSION['view_script']; ?>?action=<?php echo $_REQUEST['action']; ?>&amp;keep_view=true&amp;sort_type=fullname&amp;sort_order=0">
		<b>(<?php echo _("Fullname"); ?>)</b>
		</a>
	</td>
        <td align="center">
		<a href="<?php echo conf('web_path'); ?>/<?php echo $_SESSION['view_script']; ?>?action=<?php echo $_REQUEST['action']; ?>&amp;keep_view=true&amp;sort_type=last_seen&amp;sort_order=0">
		<b><?php echo _("Last Seen"); ?></b>
		</a>
	</td>
        <td align="center">
		<b><?php echo _("Edit"); ?></b>
	</td>
        <td align="center">
		<b><?php echo _("Prefs"); ?></b>
	</td>
        <td align="center">
		<b><?php echo _("Delete"); ?></b>
	</td>
        <td align="center">
		<b><?php echo _("Set Access"); ?></b>
	</td>
        <td align="center">
		<b><?php echo _("Disable"); ?></b>
	</td>
        <td align="center">
		<b><?php echo _("On-line"); ?></b>
	</td>
</tr>
<?php

while ( $results = mysql_fetch_object($db_result) ) {
	$user = new User($results->username);
        $last_seen = date("m\/d\/Y - H:i",$user->last_seen);
        if (!$user->last_seen) { $last_seen = "Never"; }

?>
<tr class="even">
	<td>
		<a href="<?php echo conf('web_path'); ?>/admin/users.php?action=edit&amp;user=<?php echo $user->username; ?>">
			<?php echo $user->fullname; ?> (<?php echo $user->username; ?>)
		</a>
	</td>
        <td align="center">
		<?php echo $last_seen; ?>
	</td>
        <td>
		<a href="<?php echo conf('web_path'); ?>/admin/users.php?action=edit&amp;user=<?php echo $user->username; ?>">
			<?php echo _("edit"); ?>
		</a>
	</td>
        <td>
		<a href="<?php echo conf('web_path'); ?>/admin/preferences.php?action=user&amp;user_id=<?php echo $user->id; ?>">
			<?php echo _("prefs"); ?>
		</a>
	</td>
        <td>
		<a href="<?php echo conf('web_path'); ?>/admin/users.php?action=delete&amp;user=<?php echo $user->username; ?>">
			<?php echo _("delete"); ?>
		</a>
	</td>
	<?php 
			//FIXME: Fix this for the extra permission levels
                        if ($user->access == 'admin') {
                                echo "<td><a href=\"".conf('web_path')."/admin/users.php?action=update&amp;user=$user->username&amp;level=user\">" . _("set to user") . "</a></td>";
                                echo "<td><a href=\"".conf('web_path')."/admin/users.php?action=update&amp;user=$user->username&amp;level=disabled\">" . _("disable") . "</a></td>";
                        }
                        elseif ($user->access == 'user') {
                                echo "<td><a href=\"".conf('web_path')."/admin/users.php?action=update&amp;user=$user->username&amp;level=admin\">" . _("set to admin") . "</a></td>";
                                echo "<td><a href=\"".conf('web_path')."/admin/users.php?action=update&amp;user=$user->username&amp;level=disabled\">" . _("disable") . "</a></td>";
                        }
                        elseif ($user->access == 'disabled') {
                                 echo "<td><a href=\"".conf('web_path')."/admin/users.php?action=update&user=$user->username&amp;level=admin\">" . _("set to admin") . "</a></td>";
                                 echo "<td><a href=\"".conf('web_path')."/admin/users.php?action=update&amp;user=$user->username&amp;level=user\">" . _("set to user") . "</a></td>";
			}
			elseif ($user->access == '1') { 
                                echo "<td><a href=\"".conf('web_path')."/admin/users.php?action=update&user=$user->username&amp;level=user\">" . _("set to user") . "</a></td>";
                                echo "<td><a href=\"".conf('web_path')."/admin/users.php?action=update&amp;user=$user->username&amp;level=disabled\">" . _("disable") ."</a></td>";
 			}
                        if ( $user->is_logged_in() and $user->is_online() ) {
                                echo "<td bgcolor=\"green\"> &nbsp; </td>";
                        }
                        else {
                                echo "<td bgcolor=\"red\"> &nbsp; </td>";
                        }
?>
</tr>
<?php } ?>
</table>
