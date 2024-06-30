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
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Search;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;

/** @var Search $libitem */
?>
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
    <?php
        if (AmpConfig::get('directplay')) {
            echo Ajax::button('?page=stream&action=directplay&object_type=search&object_id=' . $libitem->id, 'play_circle', T_('Play'), 'play_playlist_' . $libitem->id);
            if (Stream_Playlist::check_autoplay_next()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=search&object_id=' . $libitem->id . '&playnext=true', 'menu_open', T_('Play next'), 'nextplay_playlist_' . $libitem->id);
            }
            if (Stream_Playlist::check_autoplay_append()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=search&object_id=' . $libitem->id . '&append=true', 'low_priority', T_('Play last'), 'addplay_playlist_' . $libitem->id);
            }
        } ?>
    </div>
</td>
<td class="cel_playlist"><?php echo $libitem->get_f_link(); ?></td>
<td class="cel_add">
    <span class="cel_item_add">
        <?php echo Ajax::button('?action=basket&type=search&id=' . $libitem->id, 'new_window', T_('Add to Temporary Playlist'), 'add_playlist_' . $libitem->id); ?>
        <a id="<?php echo 'add_playlist_' . $libitem->id; ?>" onclick="showPlaylistDialog(event, 'search', '<?php echo $libitem->id; ?>')">
            <?php echo Ui::get_material_symbol('playlist_add', T_('Add to playlist')); ?>
        </a>
    </span>
</td>
<td class="cel_last_update"><?php echo $libitem->f_last_update; ?></td>
<td class="cel_type"><?php echo $libitem->get_f_type(); ?></td>
<td class="cel_random"><?php echo($libitem->random ? T_('Yes') : T_('No')); ?></td>
<td class="cel_limit"><?php echo(($libitem->limit > 0) ? $libitem->limit : T_('None')); ?></td>

<?php if (User::is_registered() && (AmpConfig::get('ratings'))) { ?>
    <td class="cel_ratings">
        <span class="cel_rating" id="rating_<?php echo $libitem->getId(); ?>_search">
            <?php echo Rating::show($libitem->getId(), 'search'); ?>
        </span>
        <span class="cel_userflag" id="userflag_<?php echo $libitem->getId(); ?>_search">
            <?php echo Userflag::show($libitem->getId(), 'search'); ?>
        </span>
    </td>
<?php } ?>
<td class="cel_owner"><?php echo $libitem->username; ?></td>
<td class="cel_action">
<?php global $dic; // @todo remove after refactoring
$zipHandler = $dic->get(ZipHandlerInterface::class);
if (Access::check_function(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) && $zipHandler->isZipable('search')) { ?>
                <a class="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/batch.php?action=search&id=<?php echo $libitem->id; ?>">
                    <?php echo Ui::get_material_symbol('folder_zip', T_('Batch download')); ?>
                </a>
<?php }
if ($libitem->has_access()) { ?>
                <a id="<?php echo 'edit_playlist_' . $libitem->id; ?>" onclick="showEditDialog('search_row', '<?php echo $libitem->id; ?>', '<?php echo 'edit_playlist_' . $libitem->id; ?>', '<?php echo addslashes(T_('Smart Playlist Edit')); ?>', 'smartplaylist_row_')">
                    <?php echo Ui::get_material_symbol('edit', T_('Edit')); ?>
                </a>
    <?php echo Ajax::button('?page=browse&action=delete_object&type=smartplaylist&id=' . $libitem->id, 'close', T_('Delete'), 'delete_playlist_' . $libitem->id);
} ?>
</td>
