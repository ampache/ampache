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
?>

<ul>
<?php
    $broadcasts = Broadcast::get_broadcasts($GLOBALS['user']->id);
    foreach ($broadcasts as $broadcast_id) {
        $broadcast = new Broadcast($broadcast_id);
        $broadcast->format();
?>
    <li>
        <a href="javascript:void(0);" id="rb_append_dbroadcast_<?php echo $broadcast->id; ?>" onclick="handleBroadcastAction('<?php echo AmpConfig::get('ajax_url').'?page=player&action=broadcast&broadcast_id='.$broadcast->id; ?>', 'rb_append_dbroadcast_<?php echo $broadcast->id; ?>');">
            <?php echo $broadcast->f_name; ?>
        </a>
    </li>
<?php } ?>
</ul><br />
<a href="javascript:void(0);" id="rb_append_dbroadcast_new" onclick="handleBroadcastAction('<?php echo AmpConfig::get('ajax_url'). '?page=player&action=broadcast'; ?>', 'rb_append_dbroadcast_new');">
    <?php echo T_('New broadcast'); ?>
</a>
