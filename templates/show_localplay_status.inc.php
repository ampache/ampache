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

$status = $localplay->status();
$now_playing = $status['track_title'] ? $status['track_title'] . ' - ' . $status['track_album'] . ' - ' . $status['track_artist'] : '';
?>
<?php Ajax::start_container('localplay_status'); ?>
<?php UI::show_box_top(T_('Localplay Control') . ' - '. strtoupper($localplay->type), 'box box_localplay_status'); ?>
<?php echo T_('Now Playing'); ?>:<i><?php echo $now_playing; ?></i>
<div id="information_actions">
<ul>
<li>
<?php echo Ajax::button('?page=localplay&action=command&command=volume_mute','volumemute', T_('Mute'),'localplay_mute'); ?>
<?php echo Ajax::button('?page=localplay&action=command&command=volume_down','volumedn', T_('Decrease Volume'),'localplay_volume_dn'); ?>
<?php echo Ajax::button('?page=localplay&action=command&command=volume_up','volumeup', T_('Increase Volume'),'localplay_volume_up'); ?>
<?php echo T_('Volume'); ?>:<?php echo $status['volume']; ?>%
</li>
<li>
    <?php echo print_bool($status['repeat']); ?> |
    <?php echo Ajax::text('?page=localplay&action=repeat&value=' . invert_bool($status['repeat']), print_bool(invert_bool($status['repeat'])), 'localplay_repeat'); ?>
    <?php echo T_('Repeat'); ?>
</li>
<li>
    <?php echo print_bool($status['random']); ?> |
    <?php echo Ajax::text('?page=localplay&action=random&value=' . invert_bool($status['random']), print_bool(invert_bool($status['random'])), 'localplay_random'); ?>
    <?php echo T_('Random'); ?>
</li>
<li>
    <?php echo Ajax::button('?page=localplay&action=command&command=delete_all','delete', T_('Clear Playlist'),'localplay_clear_all'); ?><?php echo T_('Clear Playlist'); ?>
</li>
</ul>
</div>
<?php UI::show_box_bottom(); ?>
<?php Ajax::end_container(); ?>
