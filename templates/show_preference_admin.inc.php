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
 */

UI::show_box_top(T_('Preferences')); ?>
<form method="post" action="<?php echo AmpConfig::get('web_path'); ?>/admin/preferences.php" enctype="multipart/form-data">
<table class="tabledata">
<colgroup>
  <col id="col_preference" />
  <col id="col_level" />
</colgroup>
<tr class="th-top">
    <th class="cel_preference"><?php echo T_('Preference'); ?></th>
    <th class="cel_level"><?php echo T_('Level'); ?></th>
</tr>
<?php foreach ($preferences as $preference) {
    unset($is_25, $is_5, $is_100); ?>
<tr class="<?php echo UI::flip_class(); ?>">
    <td class="cel_preference"><?php echo scrub_out(T_($preference['description'])); ?></td>
    <td class="cel_level">
        <?php $level_name = "is_" . $preference['level'];
    ${$level_name}        = 'selected="selected"'; ?>
        <select name="prefs[<?php echo scrub_out($preference['name']); ?>]">
            <option value="5" <?php echo $is_5; ?>><?php echo T_('Guest'); ?></option>
            <option value="25" <?php echo $is_25; ?>><?php echo T_('User'); ?></option>
            <option value="100" <?php echo $is_100; ?>><?php echo T_('Admin'); ?></option>
        </select>
    </td>
</tr>
<?php
} ?>
<tr class="th-bottom">
    <th class="cel_preference"><?php echo T_('Preference'); ?></th>
    <th class="cel_level"><?php echo T_('Level'); ?></th>
</tr>
</table>
<div class="formValidation">
        <input type="hidden" name="action" value="set_preferences" />
        <input type="submit" value="<?php echo T_('Update'); ?>" />
</div>
</form>
<?php UI::show_box_bottom(); ?>
