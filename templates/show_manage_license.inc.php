<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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
<div id="information_actions">
    <ul>
        <li>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/admin/license.php?action=show_create"><?php echo T_('Create new license'); ?></a>
        </li>
    </ul>
</div>
<table class="tabledata" cellpadding="0" cellspacing="0">
    <thead>
        <tr class="th-top">
            <th class="cel_name"><?php echo T_('Name'); ?></th>
            <th class="cel_description"><?php echo T_('Description'); ?></th>
            <th class="cel_action"><?php echo T_('Action'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($object_ids as $license_id) {
            $libitem = new License($license_id);
            $libitem->format();

            require AmpConfig::get('prefix') . '/templates/show_license_row.inc.php';
        ?>
        <?php } if (!count($object_ids)) { ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="6" class="error"><?php echo T_('No Licenses Found'); ?></td>
        </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_name"><?php echo T_('Name'); ?></th>
            <th class="cel_description"><?php echo T_('Description'); ?></th>
            <th class="cel_action"><?php echo T_('Action'); ?></th>
        </tr>
    </tfoot>
</table>
