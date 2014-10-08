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

$last_seen = $client->last_seen ? date("m\/d\/y - H:i",$client->last_seen) : T_('Never');
$create_date = $client->create_date ? date("m\/d\/y - H:i",$client->create_date) : T_('Unknown');
$client->format();
?>
<?php UI::show_box_top($client->fullname); ?>
<?php
if ($client->f_avatar) {
    echo '<div class="user_avatar">' . $client->f_avatar . '</div>';
}
?>
<dl class="media_details">
    <?php $rowparity = UI::flip_class(); ?>
    <dt class="<?php echo $rowparity; ?>"><?php echo T_('Full Name'); ?></dt>
    <dd class="<?php echo $rowparity; ?>"><?php echo $client->fullname; ?></dd>
    <?php $rowparity = UI::flip_class(); ?>
    <dt class="<?php echo $rowparity; ?>"><?php echo T_('Create Date'); ?></dt>
    <dd class="<?php echo $rowparity; ?>"><?php echo $create_date; ?></dd>
    <?php $rowparity = UI::flip_class(); ?>
    <dt class="<?php echo $rowparity; ?>"><?php echo T_('Last Seen'); ?></dt>
    <dd class="<?php echo $rowparity; ?>"><?php echo $last_seen; ?></dd>
    <?php $rowparity = UI::flip_class(); ?>
    <dt class="<?php echo $rowparity; ?>"><?php echo T_('Activity'); ?></dt>
    <dd class="<?php echo $rowparity; ?>">
        <?php echo $client->f_useage; ?>
        <?php if (AmpConfig::get('statistical_graphs') && Access::check('interface','50')) { ?>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/stats.php?action=graph&user_id=<?php echo $client->id; ?>"><?php echo UI::get_icon('statistics', T_('Graphs')); ?></a>
        <?php } ?>
    </dd>
    <?php $rowparity = UI::flip_class(); ?>
    <dt class="<?php echo $rowparity; ?>"><?php echo T_('Status'); ?></dt>
    <dd class="<?php echo $rowparity; ?>">
    <?php if ($client->is_logged_in() AND $client->is_online()) { ?>
        <i style="color:green;"><?php echo T_('User is Online Now'); ?></i>
    <?php } else { ?>
        <i style="color:red;"><?php echo T_('User is Offline Now'); ?></i>
    <?php } ?>
    </dd>
</dl><br />
<?php UI::show_box_bottom(); ?>
<?php UI::show_box_top(T_('Active Playlist')); ?>
<table cellspacing="0">
    <tr>
        <td valign="top">
            <?php
                $tmp_playlist = new Tmp_Playlist(Tmp_Playlist::get_from_userid($client->id));
                $object_ids = $tmp_playlist->get_items();
                foreach ($object_ids as $object_data) {
                    $type = array_shift($object_data);
                    $object = new $type(array_shift($object_data));
                    $object->format();
                    echo $object->f_link; ?>
                <br />
            <?php } ?>
        </td>
    </tr>
</table><br />
<?php UI::show_box_bottom(); ?>
<?php
    $data = Song::get_recently_played($client->id);
    Song::build_cache(array_keys($data));
    $user_id = $client->id;
    require AmpConfig::get('prefix') . '/templates/show_recently_played.inc.php';
?>
