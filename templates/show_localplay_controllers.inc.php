<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
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

$web_path = AmpConfig::get('web_path');
?>
<!-- Plugin we've found -->
<table class="tabledata" cellpadding="0" cellspacing="0">
    <thead>
        <tr class="th-top">
            <th class="cel_name"><?php echo T_('Name'); ?></th>
            <th class="cel_description"><?php echo T_('Description'); ?></th>
            <th class="cel_version"><?php echo T_('Version'); ?></th>
            <th class="cel_action"><?php echo T_('Action'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($controllers as $controller) {
            $localplay = new Localplay($controller);
            if (!$localplay->player_loaded()) { continue; }
            $localplay->format();
            if (Localplay::is_enabled($controller)) {
                $action     = 'confirm_uninstall_localplay';
                $action_txt    = T_('Disable');
            } else {
                $action = 'install_localplay';
                $action_txt    = T_('Activate');
            }
        ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td class="cel_name"><?php echo scrub_out($localplay->f_name); ?></td>
            <td class="cel_description"><?php echo scrub_out($localplay->f_description); ?></td>
            <td class="cel_version"><?php echo scrub_out($localplay->f_version); ?></td>
            <td class="cel_action"><a href="<?php echo $web_path; ?>/admin/modules.php?action=<?php echo $action; ?>&amp;type=<?php echo urlencode($controller); ?>"><?php echo $action_txt; ?></a></td>
        </tr>
        <?php } if (!count($controllers)) { ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="4"><span class="error"><?php echo T_('No Records Found'); ?></span></td>
        </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_name"><?php echo T_('Name'); ?></th>
            <th class="cel_description"><?php echo T_('Description'); ?></th>
            <th class="cel_version"><?php echo T_('Version'); ?></th>
            <th class="cel_action"><?php echo T_('Action'); ?></th>
        </tr>
    </tfoot>
</table>
<br />
