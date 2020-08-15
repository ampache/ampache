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
<?php UI::show_box_top(T_('Shares')); ?>
<div id="information_actions">
    <ul>
        <li>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/share.php?action=clean"><?php echo UI::get_icon('clean', T_('Clean')); ?> <?php echo T_('Clean Expired Shared Objects'); ?></a>
        </li>
    </ul>
</div>

<?php require_once AmpConfig::get('prefix') . UI::find_template('show_stats_share.inc.php'); ?>
<?php UI::show_box_bottom(); ?>
