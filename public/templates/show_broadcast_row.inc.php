<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
use Ampache\Repository\Model\Broadcast;
use Ampache\Module\Api\Ajax;

/** @var Broadcast $libitem */
?>
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
    <?php if (AmpConfig::get('directplay')) {
        echo Ajax::button('?page=stream&action=directplay&object_type=broadcast&object_id=' . $libitem->id, 'play_circle', T_('Play'), 'play_broadcast_' . $libitem->id);
    } ?>
    </div>
</td>
<td class="cel_name"><?php echo $libitem->name; ?></td>
<td class="cel_genre"><?php echo $libitem->get_f_tags(); ?></td>
<td class="cel_started"><?php echo(($libitem->started) ? T_('Yes') : T_('No')); ?></td>
<td class="cel_listeners"><?php echo $libitem->listeners; ?></td>
<td class="cel_action"><?php $libitem->show_action_buttons(); ?></td>
