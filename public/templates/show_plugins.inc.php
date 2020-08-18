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
            <th class="cel_name"><?php echo T_('Name'); ?></th>
            <th class="cel_description"><?php echo T_('Description'); ?></th>
            <th class="cel_version"><?php echo T_('Version'); ?></th>
            <th class="cel_iversion"><?php echo T_('Installed Version'); ?></th>
            <th class="cel_action"><?php echo T_('Action'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($plugins as $plugin_name) {
            $plugin            = new Plugin($plugin_name);
            $installed_version = Plugin::get_plugin_version($plugin->_plugin->name);
            if (!$installed_version) {
                $action = "<a href=\"" . $web_path . "/admin/modules.php?action=install_plugin&amp;plugin=" . scrub_out($plugin_name) . "\">" .
                                T_('Activate') . "</a>";
            } else {
                $action = "<a href=\"" . $web_path . "/admin/modules.php?action=confirm_uninstall_plugin&amp;plugin=" . scrub_out($plugin_name) . "\">" .
                                T_('Deactivate') . "</a>";
                if ($installed_version < $plugin->_plugin->version) {
                    $action .= '&nbsp;&nbsp;<a href="' . $web_path .
                    '/admin/modules.php?action=upgrade_plugin&amp;plugin=' .
                    scrub_out($plugin_name) . '">' . T_('Upgrade') . '</a>';
                }
            } ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td class="cel_name"><?php echo scrub_out($plugin->_plugin->name); ?></td>
            <td class="cel_description"><?php echo scrub_out($plugin->_plugin->description); ?></td>
            <td class="cel_version"><?php echo scrub_out($plugin->_plugin->version); ?></td>
            <td class="cel_iversion"><?php echo scrub_out($installed_version); ?></td>
            <td class="cel_action"><?php echo $action; ?></td>
        </tr>
        <?php
        } if (!count($plugins)) { ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="5"><span class="error"><?php echo T_('No records found'); ?></span></td>
        </tr>
        <?php
        } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_name"><?php echo T_('Name'); ?></th>
            <th class="cel_description"><?php echo T_('Description'); ?></th>
            <th class="cel_version"><?php echo T_('Version'); ?></th>
            <th class="cel_iversion"><?php echo T_('Installed Version'); ?></th>
            <th class="cel_action"><?php echo T_('Action'); ?></th>
        </tr>
    </tfoot>
</table>
<br />
