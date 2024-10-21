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

use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\Share;
use Ampache\Module\Authorization\Access;

/** @var Share $libitem */ ?>
<div>
    <form method="post" id="edit_share_<?php echo $libitem->id; ?>" class="edit_dialog_content">
        <table class="tabledata">
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Share'); ?></td>
                <td><?php echo $libitem->getObjectUrl(); ?></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Max Counter'); ?></td>
                <td><input type="text" name="max_counter" value="<?php echo scrub_out((string)$libitem->max_counter); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Expiry Days'); ?></td>
                <td><input type="text" name="expire" value="<?php echo scrub_out((string)$libitem->expire_days); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"></td>
                <td><input type="checkbox" name="allow_stream" value="1" <?php echo ($libitem->allow_stream) ? 'checked' : ''; ?> /> <?php echo T_('Allow Stream'); ?></td>
            </tr>
<?php global $dic; // @todo remove after refactoring
$zipHandler = $dic->get(ZipHandlerInterface::class);
if ((in_array($libitem->object_type, ['song', 'video', 'podcast_episode']) && (Access::check_function(AccessFunctionEnum::FUNCTION_DOWNLOAD))) || (Access::check_function(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) && $zipHandler->isZipable($libitem->object_type))) { ?>
            <tr>
                <td class="edit_dialog_content_header"></td>
                <td><input type="checkbox" name="allow_download" value="1" <?php echo ($libitem->allow_download) ? 'checked' : ''; ?> /> <?php echo T_('Allow Download'); ?></td>
            </tr>
        <?php } ?>
        </table>
        <input type="hidden" name="id" value="<?php echo $libitem->id; ?>" />
        <input type="hidden" name="type" value="share_row" />
    </form>
</div>
