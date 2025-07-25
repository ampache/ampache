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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;

global $dic;
$gatekeeper = $dic->get(GatekeeperFactoryInterface::class)->createGuiGatekeeper();

/** @var Ampache\Repository\Model\Artist $libitem */
/** @var Ampache\Repository\Model\Browse|null $browse */
/** @var bool $show_direct_play */
/** @var bool $show_playlist_add */
/** @var bool $hide_genres */
/** @var bool $show_ratings */
/** @var string $cel_cover */
/** @var string $cel_artist */
/** @var string $cel_time */
/** @var string $cel_counter */
/** @var string $cel_tags */

$web_path = AmpConfig::get_web_path();

?>
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
    <?php if ($show_direct_play) {
        echo Ajax::button('?page=stream&action=directplay&object_type=artist&object_id=' . $libitem->id, 'play_circle', T_('Play'), 'play_artist_' . $libitem->id);
        if (Stream_Playlist::check_autoplay_next()) {
            echo Ajax::button('?page=stream&action=directplay&object_type=artist&object_id=' . $libitem->id . '&playnext=true', 'menu_open', T_('Play next'), 'nextplay_artist_' . $libitem->id);
        }
        if (Stream_Playlist::check_autoplay_append()) {
            echo Ajax::button('?page=stream&action=directplay&object_type=artist&object_id=' . $libitem->id . '&append=true', 'low_priority', T_('Play last'), 'addplay_artist_' . $libitem->id);
        }
    } ?>
    </div>
</td>
<?php $name = scrub_out((string)$libitem->get_fullname()); ?>
<td class="<?php echo $cel_cover; ?>">
    <?php $size = (isset($browse) && $browse->is_grid_view())
        ? ['width' => 150, 'height' => 150]
        : ['width' => 100, 'height' => 100];
Art::display('artist', $libitem->id, $name, $size, $web_path . '/artists.php?action=show&artist=' . $libitem->id); ?>
</td>
<td class="<?php echo $cel_artist; ?>"><?php echo $libitem->get_f_link(); ?></td>
<td class="cel_add">
    <span class="cel_item_add">
    <?php if ($show_playlist_add) {
        echo Ajax::button('?action=basket&type=artist&id=' . $libitem->id, 'new_window', T_('Add to Temporary Playlist'), 'add_artist_' . $libitem->id);
        echo Ajax::button('?action=basket&type=artist_random&id=' . $libitem->id, 'shuffle', T_('Random to Temporary Playlist'), 'random_artist_' . $libitem->id); ?>
            <a id="<?php echo 'add_to_playlist_' . $libitem->id; ?>" onclick="showPlaylistDialog(event, 'artist', '<?php echo $libitem->id; ?>')">
                <?php echo Ui::get_material_symbol('playlist_add', T_('Add to playlist')); ?>
            </a>
        <?php
    } ?>
    </span>
</td>
<td class="cel_songs optional"><?php echo $libitem->song_count; ?></td>
<td class="cel_albums optional"><?php echo $libitem->get_album_count(); ?></td>
<td class="<?php echo $cel_time; ?> optional"><?php echo $libitem->get_f_time(); ?></td>
<?php if (AmpConfig::get('show_played_times')) { ?>
    <td class="<?php echo $cel_counter; ?> optional"><?php echo $libitem->total_count; ?></td>
<?php } ?>
<?php if (!$hide_genres) { ?>
<td class="<?php echo $cel_tags; ?>"><?php echo $libitem->get_f_tags(); ?></td>
<?php } ?>
<?php if ($show_ratings) { ?>
        <td class="cel_ratings">
            <?php if (AmpConfig::get('ratings')) { ?>
                <div class="rating">
                    <span class="cel_rating" id="rating_<?php echo $libitem->id; ?>_artist">
                        <?php echo Rating::show($libitem->id, 'artist'); ?>
                    </span>
                    <span class="cel_userflag" id="userflag_<?php echo $libitem->id; ?>_artist">
                        <?php echo Userflag::show($libitem->id, 'artist'); ?>
                    </span>
                </div>
            <?php } ?>
        </td>
    <?php } ?>
<td class="cel_action">
<?php if (!AmpConfig::get('use_auth') || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) {
    if (AmpConfig::get('sociable')) { ?>
    <a href="<?php echo $web_path; ?>/shout.php?action=show_add_shout&type=artist&id=<?php echo $libitem->id; ?>">
        <?php echo Ui::get_material_symbol('comment', T_('Post Shout')); ?>
    </a>
    <?php }
    if (isset($browse) && canEditArtist($libitem, $gatekeeper->getUserId())) { ?>
        <a id="<?php echo 'edit_artist_' . $libitem->id; ?>" onclick="showEditDialog('artist_row', '<?php echo $libitem->id; ?>', '<?php echo 'edit_artist_' . $libitem->id; ?>', '<?php echo addslashes(T_('Artist Edit')); ?>', 'artist_', '<?php echo '&browse_id=' . $browse->getId(); ?>')">
        <?php echo Ui::get_material_symbol('edit', T_('Edit')); ?>
        </a>
    <?php }
    if (Catalog::can_remove($libitem)) { ?>
        <a id="<?php echo 'delete_artist_' . $libitem->id; ?>" href="<?php echo $web_path; ?>/artists.php?action=delete&artist_id=<?php echo $libitem->id; ?>">
            <?php echo Ui::get_material_symbol('close', T_('Delete')); ?>
        </a>
    <?php }
    } ?>
</td>
