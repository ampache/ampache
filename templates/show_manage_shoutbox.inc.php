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
<table class="tabledata" cellpadding="0" cellspacing="0">
    <thead>
        <tr class="th-top">
            <th class="cel_object"><?php echo T_('Object'); ?></th>
            <th class="cel_username"><?php echo T_('User'); ?></th>
            <th class="cel_sticky"><?php echo T_('Sticky'); ?></th>
            <th class="cel_comment"><?php echo T_('Comment'); ?></th>
            <th class="cel_date"><?php echo T_('Date Added'); ?></th>
            <th class="cel_action"><?php echo T_('Action'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($object_ids as $shout_id) {
            $libitem = new Shoutbox($shout_id);
            $libitem->format();

            $object = Shoutbox::get_object($libitem->object_type, $libitem->object_id);
            $object->format();
            $client = new User($libitem->user);
            $client->format();

            require AmpConfig::get('prefix') . '/templates/show_shout_row.inc.php';
        ?>
        <?php } if (!count($object_ids)) { ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="6" class="error"><?php echo T_('No Records Found'); ?></td>
        </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_object"><?php echo T_('Object'); ?></th>
            <th class="cel_username"><?php echo T_('User'); ?></th>
            <th class="cel_sticky"><?php echo T_('Sticky'); ?></th>
            <th class="cel_comment"><?php echo T_('Comment'); ?></th>
            <th class="cel_date"><?php echo T_('Date Added'); ?></th>
            <th class="cel_action"><?php echo T_('Action'); ?></th>
        </tr>
    </tfoot>
</table>
