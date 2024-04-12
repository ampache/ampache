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

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\TvShow;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;

/** @var TvShow $libitem */
/** @var bool $hide_genres */
/** @var bool $show_ratings */
/** @var string $cel_cover */
/** @var string $cel_tags */
?>
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
    <?php
        if (AmpConfig::get('directplay')) {
            echo Ajax::button('?page=stream&action=directplay&object_type=tvshow&object_id=' . $libitem->id, 'play_circle', T_('Play'), 'play_tvshow_' . $libitem->id);
            if (Stream_Playlist::check_autoplay_next()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=tvshow&object_id=' . $libitem->id . '&playnext=true', 'menu_open', T_('Play next'), 'nextplay_tvshow_' . $libitem->id);
            }
            if (Stream_Playlist::check_autoplay_append()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=tvshow&object_id=' . $libitem->id . '&append=true', 'playlist_add', T_('Play last'), 'addplay_tvshow_' . $libitem->id);
            }
        } ?>
    </div>
</td>
<td class="<?php echo $cel_cover; ?>">
    <?php Art::display('tvshow', $libitem->id, (string)$libitem->get_fullname(), 6, $libitem->get_link()); ?>
</td>
<td class="cel_tvshow"><?php echo $libitem->get_f_link(); ?></td>
<td class="cel_episodes"><?php echo $libitem->episodes; ?></td>
<td class="cel_seasons"><?php echo $libitem->seasons; ?></td>
<?php if (!$hide_genres) { ?>
<td class="<?php echo $cel_tags; ?>"><?php echo $libitem->f_tags; ?></td>
<?php } ?>
<?php if ($show_ratings) { ?>
        <td class="cel_ratings">
            <?php if (AmpConfig::get('ratings')) { ?>
                <span class="cel_rating" id="rating_<?php echo $libitem->id; ?>_tvshow"><?php echo Rating::show($libitem->id, 'tvshow'); ?></span>
                <span class="cel_userflag" id="userflag_<?php echo $libitem->id; ?>_tvshow"><?php echo Userflag::show($libitem->id, 'tvshow'); ?></span>
            <?php } ?>
        </td>
    <?php } ?>
<td class="cel_action">
<?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) { ?>
    <a id="<?php echo 'edit_tvshow_' . $libitem->id; ?>" onclick="showEditDialog('tvshow_row', '<?php echo $libitem->id; ?>', '<?php echo 'edit_tvshow_' . $libitem->id; ?>', '<?php echo addslashes(T_('TV Show Edit')); ?>', 'tvshow_')">
        <?php echo Ui::get_material_symbol('edit', T_('Edit')); ?>
    </a>
<?php } ?>
<?php if (Catalog::can_remove($libitem)) { ?>
    <a id="<?php echo 'delete_tvshow_' . $libitem->id; ?>" href="<?php echo AmpConfig::get('web_path'); ?>/tvshows.php?action=delete&tvshow_id=<?php echo $libitem->id; ?>">
        <?php echo Ui::get_material_symbol('close', T_('Delete')); ?>
    </a>
<?php } ?>
</td>
