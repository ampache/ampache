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

$last_seen      = $client->last_seen ? date("m\/d\/y - H:i",$client->last_seen) : T_('Never');
$create_date    = $client->create_date ? date("m\/d\/y - H:i",$client->create_date) : T_('Unknown');
$client->format();
?>
<?php UI::show_box_top($client->fullname); ?>
<table cellspacing="0">
<tr>
    <td valign="top">
        <strong><?php echo T_('Full Name'); ?>:</strong> <?php echo $client->fullname; ?><br />
        <strong><?php echo T_('Create Date'); ?>:</strong> <?php echo $create_date; ?><br />
        <strong><?php echo T_('Last Seen'); ?>:</strong> <?php echo $last_seen; ?><br />
        <strong><?php echo T_('Activity'); ?>:</strong> <?php echo $client->f_useage; ?><br />
        <?php if ($client->is_logged_in() AND $client->is_online()) { ?>
            <i style="color:green;"><?php echo T_('User is Online Now'); ?></i>
        <?php } else { ?>
            <i style="color:red;"><?php echo T_('User is Offline Now'); ?></i>
        <?php } ?>

    </td>
    <td valign="top">
        <h2><?php echo T_('Active Playlist'); ?></h2>
        <div style="padding-left:10px;">
        <?php
            $tmp_playlist = new Tmp_Playlist(Tmp_Playlist::get_from_userid($client->id));
            $object_ids = $tmp_playlist->get_items();
            foreach ($object_ids as $object_data) {
                $type = array_shift($object_data);
                $object = new $type(array_shift($object_data));
                $object->format();
        ?>
        <?php echo $object->f_link; ?><br />
        <?php } ?>
        </div>
    </td>
</tr>
</table>
<?php UI::show_box_bottom(); ?>
<?php
    $data = Song::get_recently_played($client->id);
    require Config::get('prefix') . '/templates/show_recently_played.inc.php';
?>

