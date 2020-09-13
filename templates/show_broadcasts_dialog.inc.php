<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */ ?>

<ul>
<?php
    $broadcasts = Broadcast::get_broadcasts(Core::get_global('user')->id);
    foreach ($broadcasts as $broadcast_id) {
        $broadcast = new Broadcast((int) $broadcast_id);
        $broadcast->format(); ?>
    <li>
        <a href="javascript:void(0);" id="rb_append_dbroadcast_<?php echo $broadcast->id; ?>" onclick="handleBroadcastAction('<?php echo AmpConfig::get('ajax_url') . '?page=player&action=broadcast&broadcast_id=' . $broadcast->id; ?>', 'rb_append_dbroadcast_<?php echo $broadcast->id; ?>');">
            <?php echo $broadcast->f_name; ?>
        </a>
    </li>
<?php
    } ?>
</ul><br />
<a href="javascript:void(0);" id="rb_append_dbroadcast_new" onclick="handleBroadcastAction('<?php echo AmpConfig::get('ajax_url') . '?page=player&action=broadcast'; ?>', 'rb_append_dbroadcast_new');">
    <?php echo T_('New broadcast'); ?>
</a>
