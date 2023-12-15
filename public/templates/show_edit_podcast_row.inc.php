<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

use Ampache\Repository\Model\Podcast;

/** @var Podcast $libitem */
?>
<div>
    <form method="post" id="edit_podcast_<?php echo $libitem->getId(); ?>" class="edit_dialog_content">
        <table class="tabledata">
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Feed'); ?></td>
                <td><input type="text" name="feed" value="<?php echo scrub_out($libitem->getFeed()); ?>" autofocus /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Title'); ?></td>
                <td><input type="text" name="title" value="<?php echo scrub_out($libitem->getTitle()); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Description'); ?></td>
                <td><textarea name="description" cols="44" rows="4"><?php echo scrub_out($libitem->get_description()); ?></textarea></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Language'); ?></td>
                <td><input type="text" name="language" value="<?php echo scrub_out($libitem->getLanguage()); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Copyright'); ?></td>
                <td><input type="text" name="copyright" value="<?php echo scrub_out($libitem->getCopyright()); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Website'); ?></td>
                <td><input type="text" name="website" value="<?php echo scrub_out($libitem->getWebsite()); ?>" /></td>
            </tr>
        </table>
        <input type="hidden" name="id" value="<?php echo $libitem->getId(); ?>" />
        <input type="hidden" name="type" value="podcast_row" />
    </form>
</div>
