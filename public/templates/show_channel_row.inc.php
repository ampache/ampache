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
 */

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Channel;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Util\Ui;

/** @var Channel $libitem */

?>
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
    <?php if (AmpConfig::get('directplay')) {
    echo Ajax::button('?page=stream&action=directplay&object_type=channel&object_id=' . $libitem->id, 'play', T_('Play'), 'play_channel_' . $libitem->id);
} ?>
    </div>
</td>
<td class="cel_id"><?php echo $libitem->id; ?></td>
<td class="cel_name"><?php echo $libitem->getName(); ?></td>
<td class="cel_interface"><?php echo $libitem->getInterface(); ?></td>
<td class="cel_port"><?php echo $libitem->getPort(); ?></td>
<td class="cel_data"><?php echo $libitem->get_target_object()->f_link; ?></td>
<!--<td class="cel_random"><?php echo($libitem->getRandom() ? T_('Yes') : T_('No')); ?></td>
<td class="cel_loop"><?php echo($libitem->getLoop() ? T_('Yes') : T_('No')); ?></td>-->
<td class="cel_streamtype"><?php echo $libitem->getStreamType(); ?></td>
<td class="cel_bitrate"><?php echo $libitem->getBitrate(); ?></td>
<td class="cel_startdate"><?php echo date("c", $libitem->getStartDate()); ?></td>
<td class="cel_listeners"><?php echo $libitem->getListeners(); ?></td>
<td class="cel_streamurl">
    <?php echo $libitem->get_stream_url(); ?><br />
    <?php if ($libitem->getIsPrivate()) {
    echo Ui::get_icon('lock', T_('Authentication Required'));
} ?>
    <?php echo $libitem->get_stream_proxy_url(); ?>
</td>
<td class="cel_state"><div id="channel_state_<?php echo $libitem->id; ?>"><?php echo $libitem->get_channel_state(); ?></div></td>
<td class="cel_action"><?php echo Ui::getChannelActionButtons($libitem); ?></td>
