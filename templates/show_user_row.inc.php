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
?>
    <td class="cel_username">
        <a href="<?php echo $web_path; ?>/stats.php?action=show_user&amp;user_id=<?php echo $libitem->id; ?>">
<?php
if ($libitem->f_avatar_mini) {
    echo $libitem->f_avatar_mini;
}
?>
            <?php echo $libitem->fullname; ?> (<?php echo $libitem->username; ?>)
        </a>
    </td>
  <td class="cel_lastseen"><?php echo $last_seen; ?></td>
  <td class="cel_registrationdate"><?php echo $create_date; ?></td>
  <td class="cel_activity"><?php echo $libitem->f_useage; ?></td>
    <?php if (AmpConfig::get('track_user_ip')) { ?>
        <td class="cel_lastip">
        <a href="<?php echo $web_path; ?>/admin/users.php?action=show_ip_history&amp;user_id=<?php echo $libitem->id; ?>">
            <?php echo $libitem->ip_history; ?>
        </a>
        </td>
    <?php } ?>
    <td class="cel_action">
        <a href="<?php echo $web_path; ?>/admin/users.php?action=show_edit&amp;user_id=<?php echo $libitem->id; ?>"><?php echo UI::get_icon('edit', T_('Edit')); ?></a>
        <a href="<?php echo $web_path; ?>/admin/users.php?action=show_preferences&amp;user_id=<?php echo $libitem->id; ?>"><?php echo UI::get_icon('preferences', T_('Preferences')); ?></a>
    <?php
    //FIXME: Fix this for the extra permission levels
    if ($libitem->disabled == '1') {
        echo "<a href=\"".$web_path."/admin/users.php?action=enable&amp;user_id=$libitem->id\">" . UI::get_icon('enable', T_('Enable')) . "</a>";
    } else {
        echo "<a href=\"".$web_path."/admin/users.php?action=disable&amp;user_id=$libitem->id\">" . UI::get_icon('disable', T_('Disable')) ."</a>";
    }
    ?>
        <a href="<?php echo $web_path; ?>/admin/users.php?action=delete&user_id=<?php echo $libitem->id; ?>"><?php echo UI::get_icon('delete', T_('Delete')); ?></a>
    </td>
       <?php
    if (($libitem->is_logged_in()) AND ($libitem->is_online())) {
        echo "<td class=\"cel_online user_online\"> &nbsp; </td>";
    } elseif ($libitem->disabled == 1) {
        echo "<td class=\"cel_online user_disabled\"> &nbsp; </td>";
    } else {
        echo "<td class=\"cel_online user_offline\"> &nbsp; </td>";
    }
?>
