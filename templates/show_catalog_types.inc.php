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
            if ($catalog == null) { continue; }
            $catalog->format();
            if ($catalog->is_installed()) {
                $action     = 'confirm_uninstall_catalog_type';
                $action_txt    = T_('Disable');
            } else {
                $action = 'install_catalog_type';
                $action_txt    = T_('Activate');
            }
        ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td class="cel_type"><?php echo scrub_out($catalog->get_type()); ?></td>
            <td class="cel_description"><?php echo scrub_out($catalog->get_description()); ?></td>
            <td class="cel_version"><?php echo scrub_out($catalog->get_version()); ?></td>
            <td class="cel_action"><a href="<?php echo $web_path; ?>/admin/modules.php?action=<?php echo $action; ?>&amp;type=<?php echo urlencode($catalog->get_type()); ?>"><?php echo $action_txt; ?></a></td>
        </tr>
        <?php } if (!count($catalogs)) { ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="4"><span class="error"><?php echo T_('No Records Found'); ?></span></td>
        </tr>
        <?php } ?>
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
