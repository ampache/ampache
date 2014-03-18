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
?>
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
    <?php if (AmpConfig::get('directplay')) { ?>
        <?php echo Ajax::button('?page=stream&action=directplay&playtype=channel&channel_id=' . $channel->id,'play', T_('Play'),'play_channel_' . $channel->id); ?>
<?php } ?>
    </div>
</td>
<td class="cel_id"><?php echo $channel->id; ?></td>
<td class="cel_name"><?php echo $channel->name; ?></td>
<td class="cel_interface"><?php echo $channel->interface; ?></td>
<td class="cel_port"><?php echo $channel->port; ?></td>
<td class="cel_data"><?php echo $channel->get_target_object()->f_name_link; ?></td>
<!--<td class="cel_random"><?php echo ($channel->random ? T_('Yes') : T_('No')); ?></td>
<td class="cel_loop"><?php echo ($channel->loop ? T_('Yes') : T_('No')); ?></td>-->
<td class="cel_streamtype"><?php echo $channel->stream_type; ?></td>
<td class="cel_bitrate"><?php echo $channel->bitrate; ?></td>
<td class="cel_startdate"><?php echo date("c", $channel->start_date); ?></td>
<td class="cel_listeners"><?php echo $channel->listeners; ?></td>
<td class="cel_streamurl">
    <?php echo $channel->get_stream_url(); ?><br />
    <?php if ($channel->is_private) { echo UI::get_icon('lock', T_('Authentication Required')); } ?>
    <?php echo $channel->get_stream_proxy_url(); ?>
</td>
<td class="cel_state"><div id="channel_state_<?php echo $channel->id; ?>"><?php echo $channel->get_channel_state(); ?></div></td>
<td class="cel_action"><?php $channel->show_action_buttons(); ?></td>
