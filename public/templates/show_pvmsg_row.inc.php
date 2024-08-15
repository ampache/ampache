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
use Ampache\Repository\Model\PrivateMsg;
use Ampache\Module\Util\Ui;

/** @var PrivateMsg $libitem */

$web_path = AmpConfig::get_web_path(); ?>
<td class="cel_select"><input type="checkbox" name="pvmsg_select[]" value="<?php echo $libitem->getId(); ?>" title="<?php echo T_('Select'); ?>" /></td>
<td class="cel_subject"><?php echo $libitem->getLinkFormatted(); ?></td>
<td class="cel_from_user"><?php echo $libitem->getSenderUserLink(); ?></td>
<td class="cel_to_user"><?php echo $libitem->getRecipientUserLink(); ?></td>
<td class="cel_creation_date"><?php echo $libitem->getCreationDateFormatted(); ?></td>
<td class="cel_action">
<a id="<?php echo 'reply_pvmsg_' . $libitem->getId(); ?>" href="<?php echo $web_path; ?>/pvmsg.php?action=show_add_message&reply_to=<?php echo $libitem->getId(); ?>">
    <?php echo Ui::get_material_symbol('mail', T_('Reply')); ?>
</a>
<a id="<?php echo 'delete_pvmsg_' . $libitem->getId(); ?>" href="<?php echo $web_path; ?>/pvmsg.php?action=delete&msgs=<?php echo $libitem->getId(); ?>">
    <?php echo Ui::get_material_symbol('close', T_('Delete')); ?>
</a>
</td>
