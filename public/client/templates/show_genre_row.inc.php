<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

// show_genre_row.inc.php

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Art\Art;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Tag;

/** @var Tag $libitem */
/** @var AjaxUriRetrieverInterface $ajaxUriRetriever */
/** @var bool $show_video */

$name     = scrub_out((string) $libitem->get_fullname());
$web_path = AmpConfig::get_web_path('/client'); ?>
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
    <?php
    // A genre queues the songs it holds, so it plays wherever a smartlist does
    if (AmpConfig::get('directplay')) {
        echo Ajax::button('?page=stream&action=directplay&object_type=tag&object_id=' . $libitem->id, 'play_circle', T_('Play'), 'play_genre_' . $libitem->id);
        if (Stream_Playlist::check_autoplay_next()) {
            echo Ajax::button('?page=stream&action=directplay&object_type=tag&object_id=' . $libitem->id . '&playnext=true', 'menu_open', T_('Play next'), 'nextplay_genre_' . $libitem->id);
        }
        if (Stream_Playlist::check_autoplay_append()) {
            echo Ajax::button('?page=stream&action=directplay&object_type=tag&object_id=' . $libitem->id . '&append=true', 'low_priority', T_('Play last'), 'addplay_genre_' . $libitem->id);
        }
    } ?>
    </div>
</td>
<td class="cel_cover">
    <?php Art::display('tag', $libitem->id, $name, ['width' => 100, 'height' => 100], $libitem->get_link()); ?>
</td>
<td class="cel_genre"><?php echo $libitem->get_f_link(); ?></td>
<td class="cel_add_list">
    <span class="cel_item_add">
        <?php echo Ajax::button('?action=basket&type=tag&id=' . $libitem->id, 'new_window', T_('Add to Temporary Playlist'), 'add_genre_' . $libitem->id);
if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) { ?>
        <a id="<?php echo 'add_to_playlist_' . $libitem->id; ?>" onclick="showPlaylistDialog(event, 'tag', '<?php echo $libitem->id; ?>')">
            <?php echo Ui::get_material_symbol('playlist_add', Ui::get_add_to_list_label()); ?>
        </a>
        <?php } ?>
    </span>
</td>
<td class="cel_songs"><?php echo $libitem->song; ?></td>
<td class="cel_albums"><?php echo $libitem->album; ?></td>
<td class="cel_artists"><?php echo $libitem->artist; ?></td>
<?php if ($show_video) { ?>
<td class="cel_videos"><?php echo $libitem->video; ?></td>
<?php } ?>
<td class="cel_action">
    <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) { ?>
    <?php // `tag_row` refreshes as the cloud's plain span, so this reloads the page instead of the row?>
    <a class="tag_edit" id="<?php echo 'edit_tag_' . $libitem->id; ?>" onclick="showEditDialog('tag_row', '<?php echo $libitem->id; ?>', '<?php echo 'edit_tag_' . $libitem->id; ?>', '<?php echo addslashes(T_('Edit')); ?>', '')">
        <?php echo Ui::get_material_symbol('edit', T_('Edit')); ?>
    </a>
    <a class="tag_delete" href="<?php echo $ajaxUriRetriever->getAjaxUri(); ?>?page=tag&action=delete&tag_id=<?php echo $libitem->id; ?>" data-confirm="<?php echo T_('Do you really want to delete this Tag?'); ?>">
        <?php echo Ui::get_material_symbol('close', T_('Delete')); ?>
    </a>
<?php } ?>
</td>
