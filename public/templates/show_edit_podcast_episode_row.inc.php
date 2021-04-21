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

use Ampache\Repository\Model\PodcastEpisodeInterface;

/** @var PodcastEpisodeInterface $libitem */
?>
<div>
    <form method="post" id="edit_podcast_episode_<?php echo $libitem->getId(); ?>" class="edit_dialog_content">
        <table class="tabledata">
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Title') ?></td>
                <td><input type="text" name="title" value="<?php echo scrub_out($libitem->title); ?>" autofocus /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Description') ?></td>
                <td><textarea name="description" cols="44" rows="4"><?php echo scrub_out($libitem->getDescription()); ?></textarea></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('GUID') ?></td>
                <td><input type="text" name="guid" value="<?php echo scrub_out($libitem->getGuid()); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Author') ?></td>
                <td><input type="text" name="author" value="<?php echo scrub_out($libitem->getAuthor()); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Category') ?></td>
                <td><input type="text" name="category" value="<?php echo scrub_out($libitem->getCategory()); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Website') ?></td>
                <td><input type="text" name="website" value="<?php echo scrub_out($libitem->getWebsite()); ?>" /></td>
            </tr>
        </table>
        <input type="hidden" name="id" value="<?php echo $libitem->id; ?>" />
        <input type="hidden" name="type" value="podcast_episode_row" />
    </form>
</div>
