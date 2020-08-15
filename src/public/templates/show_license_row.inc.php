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
<tr id="license_<?php echo $libitem->id; ?>" class="<?php echo UI::flip_class(); ?>">
    <td class="cel_name"><?php echo $libitem->f_link; ?></td>
    <td class="cel_description"><?php echo $libitem->description; ?></td>
    <td class="cel_action">
        <a href="<?php echo $web_path; ?>/admin/license.php?action=show_edit&license_id=<?php echo $libitem->id; ?>">
            <?php echo UI::get_icon('edit', T_('Edit')); ?>
        </a>
        <a href="<?php echo $web_path; ?>/admin/license.php?action=delete&license_id=<?php echo $libitem->id; ?>">
            <?php echo UI::get_icon('delete', T_('Delete')); ?>
        </a>
    </td>
</tr>
