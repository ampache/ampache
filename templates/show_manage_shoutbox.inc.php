<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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

$web_path = Config::get('web_path');
?>
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_object" />
  <col id="col_username" />
  <col id="col_sticky" />
  <col id="col_comment" />
  <col id="col_date" />
  <col id="col_action" />
</colgroup>
<tr class="th-top">
    <th class="cel_object"><?php echo T_('Object'); ?></th>
    <th class="cel_username"><?php echo T_('User'); ?></th>
    <th class="cel_flag"><?php echo T_('Sticky'); ?></th>
    <th class="cel_comment"><?php echo T_('Comment'); ?></th>
    <th class="cel_date"><?php echo T_('Date Added'); ?></th>
    <th class="cel_action"><?php echo T_('Action'); ?></th>
</tr>
<?php
foreach ($object_ids as $shout_id) {
    $shout = new Shoutbox($shout_id);
    $shout->format();
        $object = Shoutbox::get_object($shout->object_type,$shout->object_id);
        $object->format();
        $client = new User($shout->user);
        $client->format();

    require Config::get('prefix') . '/templates/show_shout_row.inc.php';
?>
<?php } if (!count($object_ids)) { ?>
<tr class="<?php echo UI::flip_class(); ?>">
    <td colspan="7" class="error"><?php echo T_('No Records Found'); ?></td>
</tr>
<?php } ?>
<tr class="th-bottom">
    <th class="cel_object"><?php echo T_('Object'); ?></th>
    <th class="cel_username"><?php echo T_('User'); ?></th>
    <th class="cel_sticky"><?php echo T_('Sticky'); ?></th>
    <th class="cel_comment"><?php echo T_('Comment'); ?></th>
    <th class="cel_date"><?php echo T_('Date Added'); ?></th>
    <th class="cel_action"><?php echo T_('Action'); ?></th>
</tr>
</table>
