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

$web_path = AmpConfig::get('web_path'); ?>
<!-- Plugin we've found -->
<table class="tabledata">
    <thead>
        <tr class="th-top">
            <th class="cel_type"><?php echo T_('Type'); ?></th>
            <th class="cel_description"><?php echo T_('Description'); ?></th>
            <th class="cel_version"><?php echo T_('Version'); ?></th>
            <th class="cel_action"><?php echo T_('Action'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($catalogs as $type) {
            $catalog = Catalog::create_catalog_type($type);
            if ($catalog === null) {
                continue;
            }
            $catalog->format();
            if ($catalog->is_installed()) {
                $action        = 'confirm_uninstall_catalog_type';
                $action_txt    = T_('Disable');
            } else {
                $action        = 'install_catalog_type';
                $action_txt    = T_('Activate');
            } ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td class="cel_type"><?php echo scrub_out($catalog->get_type()); ?></td>
            <td class="cel_description"><?php echo scrub_out($catalog->get_description()); ?></td>
            <td class="cel_version"><?php echo scrub_out($catalog->get_version()); ?></td>
            <td class="cel_action"><a href="<?php echo $web_path; ?>/admin/modules.php?action=<?php echo $action; ?>&amp;type=<?php echo urlencode($catalog->get_type()); ?>"><?php echo $action_txt; ?></a></td>
        </tr>
        <?php
        } if (!count($catalogs)) { ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="4"><span class="error"><?php echo T_('No records found'); ?></span></td>
        </tr>
        <?php
        } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_type"><?php echo T_('Type'); ?></th>
            <th class="cel_description"><?php echo T_('Description'); ?></th>
            <th class="cel_version"><?php echo T_('Version'); ?></th>
            <th class="cel_action"><?php echo T_('Action'); ?></th>
        </tr>
    </tfoot>
</table>
<br />
