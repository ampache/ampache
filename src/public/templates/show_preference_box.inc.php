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

/* I'm cheating a little here, check to see if we want to show the
 * Apply to All button on this page
 */
if (Access::check('interface', 100) && $_REQUEST['action'] == 'admin') {
    $is_admin = true;
} ?>
<h4><?php echo T_($preferences['title']); ?></h4>
<table class="tabledata">
<colgroup>
  <col id="col_preference" />
  <col id="col_value" />
    <?php if ($is_admin) { ?>
  <col id="col_applytoall" />
  <col id="col_level" />
    <?php
} ?>
</colgroup>
<thead>
    <tr class="th-top">
        <th class="cel_preference"><?php echo T_('Preference'); ?></th>
        <th class="cel_value"><?php echo T_('Value'); ?></th>
        <?php if ($is_admin) { ?>
        <th class="cel_applytoall"><?php echo T_('Apply to All'); ?></th>
        <th class="cel_level"><?php echo T_('Access Level'); ?></th>
        <?php
    } ?>
    </tr>
</thead>
<tbody>
    <?php
    $lastsubcat = '';
    foreach ($preferences['prefs'] as $pref) {
        if ($pref['subcategory'] != $lastsubcat) {
            $lastsubcat = $pref['subcategory'];
            $fsubcat    = $lastsubcat;
            if (!empty($fsubcat)) { ?>
                <tr class="<?php echo UI::flip_class() ?>"><td colspan="4"><h5><?php echo ucwords(T_($fsubcat)) ?></h5></td></tr>
                <?php
            }
        } ?>
        <tr class="<?php echo UI::flip_class() ?>">
            <td class="cel_preference"><?php echo T_($pref['description']); ?></td>
            <td class="cel_value">
                <?php create_preference_input($pref['name'], $pref['value']); ?>
            </td>
            <?php if ($is_admin) { ?>
                <td class="cel_applytoall"><input type="checkbox" name="check_<?php echo $pref['name']; ?>" value="1" /></td>
                <td class="cel_level">
                    <?php $name         = 'on_' . $pref['level'];
            ${$name}                    = 'selected="selected"'; ?>
                    <select name="level_<?php echo $pref['name']; ?>">
                        <option value="5" <?php echo $on_5; ?>><?php echo T_('Guest'); ?></option>
                        <option value="25" <?php echo $on_25; ?>><?php echo T_('User'); ?></option>
                        <option value="50" <?php echo $on_50; ?>><?php echo T_('Content Manager'); ?></option>
                        <option value="75" <?php echo $on_75; ?>><?php echo T_('Catalog Manager'); ?></option>
                        <option value="100" <?php echo $on_100; ?>><?php echo T_('Admin'); ?></option>
                    </select>
                    <?php unset(${$name}); ?>
                </td>
            <?php
        } ?>
        </tr>
    <?php
    } // End foreach ($preferences['prefs'] as $pref)?>
</tbody>
<tfoot>
    <tr class="th-bottom">
        <th class="cel_preference"><?php echo T_('Preference'); ?></th>
        <th class="cel_value"><?php echo T_('Value'); ?></th>
        <?php if ($is_admin) { ?>
        <th class="cel_applytoall"><?php echo T_('Apply to All'); ?></th>
        <th class="cel_level"><?php echo T_('Access Level'); ?></th>
        <?php
    } ?>
    </tr>
</tfoot>
</table>
