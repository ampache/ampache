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
<div id="localplay-control">
<?php echo Ajax::button('?page=localplay&action=command&command=prev', 'prev', T_('Previous'), 'localplay_control_previous'); ?>
<?php echo Ajax::button('?page=localplay&action=command&command=stop', 'stop', T_('Stop'), 'localplay_control_stop'); ?>
<?php echo Ajax::button('?page=localplay&action=command&command=pause', 'pause', T_('Pause'), 'localplay_control_pause'); ?>
<?php echo Ajax::button('?page=localplay&action=command&command=play', 'play', T_('Play'), 'localplay_control_play'); ?>
<?php echo Ajax::button('?page=localplay&action=command&command=next', 'next', T_('Next'), 'localplay_control_next'); ?>
</p>
<?php echo Ajax::button('?page=localplay&action=command&command=volume_mute', 'volumemute', T_('Mute'), 'localplay_mute'); ?>
<?php echo Ajax::button('?page=localplay&action=command&command=volume_down', 'volumedn', T_('Decrease Volume'), 'localplay_volume_dn'); ?>
<?php echo Ajax::button('?page=localplay&action=command&command=volume_up', 'volumeup', T_('Increase Volume'), 'localplay_volume_up'); ?>
</div>
