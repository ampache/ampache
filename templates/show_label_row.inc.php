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
<?php
if (Art::is_enabled()) {
    $name = scrub_out($libitem->f_name); ?>
    <td class="<?php echo $cel_cover; ?>">
        <?php Art::display('label', $libitem->id, $name, 1, AmpConfig::get('web_path') . '/labels.php?action=show&label=' . $libitem->id); ?>
    </td>
    <?php
} ?>
<td class="cel_label"><?php echo $libitem->f_link; ?></td>
<td class="cel_category"><?php echo $libitem->category; ?></td>
<td class="cel_artists"><?php echo $libitem->artists; ?></td>
<td class="cel_action">
<?php if (!AmpConfig::get('use_auth') || Access::check('interface', 25)) {
        if (AmpConfig::get('sociable')) { ?>
    <a href="<?php echo AmpConfig::get('web_path') ?>/shout.php?action=show_add_shout&type=label&amp;id=<?php echo $libitem->id ?>">
        <?php echo UI::get_icon('comment', T_('Post Shout')) ?>
    </a>
    <?php
    }
        if (Catalog::can_remove($libitem)) { ?>
        <a id="<?php echo 'delete_label_' . $libitem->id ?>" href="<?php echo AmpConfig::get('web_path') ?>/labels.php?action=delete&label_id=<?php echo $libitem->id ?>">
            <?php echo UI::get_icon('delete', T_('Delete')) ?>
        </a>
    <?php
    }
    } ?>
</td>
