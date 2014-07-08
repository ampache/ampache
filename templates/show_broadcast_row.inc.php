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
?>
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
    <?php if (AmpConfig::get('directplay')) { ?>
        <?php echo Ajax::button('?page=stream&action=directplay&object_type=broadcast&object_id=' . $libitem->id,'play', T_('Play'),'play_broadcast_' . $libitem->id); ?>
<?php } ?>
    </div>
</td>
<td class="cel_name"><?php echo $libitem->name; ?></td>
<td class="cel_genre"><?php echo $libitem->f_tags; ?></td>
<td class="cel_started"><?php echo ($libitem->started ? T_('Yes') : T_('No')); ?></td>
<td class="cel_listeners"><?php echo $libitem->listeners; ?></td>
<td class="cel_action"><?php $libitem->show_action_buttons(); ?></td>
