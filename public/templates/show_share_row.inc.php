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

/** @var Share $libitem */

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Share;

?>

<td class="cel_object"><?php echo $libitem->getObjectUrl(); ?></td>
<td class="cel_object_type"><?php echo $libitem->getObjectType(); ?></td>
<td class="cel_user"><?php echo $libitem->getUserName(); ?></td>
<td class="cel_creation_date"><?php echo $libitem->getCreationDateFormatted(); ?></td>
<td class="cel_lastvisit_date"><?php echo $libitem->getLastVisitDateFormatted(); ?></td>
<td class="cel_counter"><?php echo $libitem->getCounter(); ?></td>
<td class="cel_max_counter"><?php echo $libitem->getMaxCounter(); ?></td>
<td class="cel_allow_stream"><?php echo $libitem->getAllowStream(); ?></td>
<td class="cel_allow_download"><?php echo $libitem->getAllowDownload(); ?></td>
<td class="cel_expire"><?php echo $libitem->getExpireDays(); ?></td>
<td class="cel_public_url"><?php echo $libitem->getPublicUrl(); ?></td>
<td class="cel_action">
    <div id="share_action_<?php echo $libitem->getId(); ?>">
    <?php


    if ($libitem->getId()) {
        if (Core::get_global('user')->has_access('75') || $libitem->getUserId() == Core::get_global('user')->getId()) {
            if ($libitem->getAllowDownload()) {
                echo sprintf(
                    '<a class="nohtml" href="%s&action=download">%s</a>',
                    $libitem->getPublicUrl(),
                    Ui::get_icon('download', T_('Download'))
                );
            }
            echo sprintf(
                '<a id="edit_share_ %s" onclick="showEditDialog(\'share_row\', \'%s\', \'edit_share_%s\', \'%s\', \'share_\')">%s</a>',
                $libitem->getId(),
                $libitem->getId(),
                $libitem->getId(),
                T_('Share Edit'),
                Ui::get_icon('edit', T_('Edit'))
            );
            echo sprintf(
                '<a href="%s/share.php?action=show_delete&id=%s">%s</a>',
                AmpConfig::get('web_path'),
                $libitem->getId(),
                Ui::get_icon('delete', T_('Delete'))
            );
        }
    }
    ?>
    </div>
</td>
