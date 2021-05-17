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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Broadcast;
use Ampache\Module\Api\Ajax;

/** @var Broadcast $libitem */
?>
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
    <?php if (AmpConfig::get('directplay')) {
    echo Ajax::button('?page=stream&action=directplay&object_type=broadcast&object_id=' . $libitem->getId(), 'play', T_('Play'), 'play_broadcast_' . $libitem->getId());
} ?>
    </div>
</td>
<td class="cel_name"><?php echo $libitem->getName(); ?></td>
<td class="cel_genre"><?php echo $libitem->getTagsFormatted(); ?></td>
<td class="cel_started"><?php echo($libitem->getStarted() ? T_('Yes') : T_('No')); ?></td>
<td class="cel_listeners"><?php echo $libitem->getListeners(); ?></td>
<td class="cel_action"><?php

    if ($libitem->isNew() === false) {
        if (Core::get_global('user')->has_access(AccessLevelEnum::LEVEL_MANAGER)) {
            echo sprintf(
                '<a id="edit_broadcast_ %s" onclick="showEditDialog(\'broadcast_row\', \'%s\', \'edit_broadcast_%s\', \'%s\', \'broadcast_row_\')">%s</a>',
                $libitem->getId(),
                $libitem->getId(),
                $libitem->getId(),
                T_('Broadcast Edit'),
                Ui::get_icon(
                    'edit',
                    T_('Edit')
                )
            );
            echo sprintf(
                ' <a href="%s/broadcast.php?action=show_delete&id=%s">%s</a>',
                AmpConfig::get('web_path'),
                $libitem->getId(),
                Ui::get_icon(
                    'delete',
                    T_('Delete')
                )
            );
        }
    }
    ?></td>
