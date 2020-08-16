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
 */ ?>
    <td class="cel_username">
        <a href="<?php echo $web_path; ?>/stats.php?action=show_user&amp;user_id=<?php echo $libitem->id; ?>">
            <?php
                if ($libitem->f_avatar_mini) {
                    echo $libitem->f_avatar_mini;
                }
                echo $libitem->username;
                if ($libitem->fullname_public || Access::check('interface', 100)) {
                    echo " (" . $libitem->fullname . ")";
                } ?>
        </a>
    </td>
    <td class="cel_lastseen"><?php echo $last_seen; ?></td>
    <td class="cel_registrationdate"><?php echo $create_date; ?></td>
    <?php
        if (Access::check('interface', 50)) { ?>
            <td class="cel_activity"><?php echo $libitem->f_usage; ?></td>
        <?php
            if (AmpConfig::get('track_user_ip')) { ?>
                <td class="cel_lastip">
                    <a href="<?php echo $web_path; ?>/admin/users.php?action=show_ip_history&amp;user_id=<?php echo $libitem->id; ?>">
                        <?php echo $libitem->ip_history; ?>
                    </a>
                </td>
                <?php
            }
        }
        if (Access::check('interface', 25) && AmpConfig::get('sociable')) { ?>
            <td class="cel_follow"><?php echo $libitem->get_display_follow(); ?></td>
            <?php
        } ?>
    <td class="cel_action">
    <?php
        if (Access::check('interface', 25) && AmpConfig::get('sociable')) { ?>
            <a id="<?php echo 'reply_pvmsg_' . $libitem->id ?>" href="<?php echo AmpConfig::get('web_path'); ?>/pvmsg.php?action=show_add_message&to_user=<?php echo $libitem->username; ?>">
                <?php echo UI::get_icon('mail', T_('Send private message')); ?>
            </a>
        <?php
        } ?>
    <?php
        if (Access::check('interface', 100)) { ?>
            <a href="<?php echo $web_path; ?>/admin/users.php?action=show_edit&amp;user_id=<?php echo $libitem->id; ?>">
                <?php echo UI::get_icon('edit', T_('Edit')); ?>
            </a>
            <a href="<?php echo $web_path; ?>/admin/users.php?action=show_preferences&amp;user_id=<?php echo $libitem->id; ?>">
                <?php echo UI::get_icon('preferences', T_('Preferences')); ?>
            </a>
        <?php
            // FIXME: Fix this for the extra permission levels
            if ($libitem->disabled == '1') {
                echo "<a href=\"" . $web_path . "/admin/users.php?action=enable&amp;user_id=$libitem->id\">" . UI::get_icon('enable', T_('Enable')) . "</a>";
            } else {
                echo "<a href=\"" . $web_path . "/admin/users.php?action=disable&amp;user_id=$libitem->id\">" . UI::get_icon('disable', T_('Disable')) . "</a>";
            } ?>
        <a href="<?php echo $web_path; ?>/admin/users.php?action=delete&user_id=<?php echo $libitem->id; ?>">
            <?php echo UI::get_icon('delete', T_('Delete')); ?>
        </a>
        <?php
        } ?>
    </td>
    <?php
        if (($libitem->is_logged_in()) && ($libitem->is_online())) {
            echo "<td class=\"cel_online user_online\"> &nbsp; </td>";
        } elseif ($libitem->disabled == 1) {
            echo "<td class=\"cel_online user_disabled\"> &nbsp; </td>";
        } else {
            echo "<td class=\"cel_online user_offline\"> &nbsp; </td>";
        } ?>
