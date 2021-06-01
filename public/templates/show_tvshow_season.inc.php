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
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Catalog\MediaDeletionCheckerInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\TVShow_Season;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;

/** @var TVShow_Season $season */
/** @var string $web_path */
/** @var string $object_type */
/** @var int[] $object_ids */
/** @var MediaDeletionCheckerInterface $mediaDeletionChecker */

$browse = new Browse();
$browse->set_type($object_type);

global $dic;
$mediaDeletionChecker = $dic->get(MediaDeletionCheckerInterface::class);

Ui::show_box_top($season->getNameFormatted() . ' - ' . $season->getTvShow()->getLinkFormatted(), 'info-box'); ?>
<div class="item_right_info">
    <?php
    echo Art::display('tvshow_season', $season->id, $season->getNameFormatted(), 6); ?>
</div>
<?php if (User::is_registered()) { ?>
    <?php
    if (AmpConfig::get('ratings')) { ?>
    <div id="rating_<?php echo (int) ($season->id); ?>_tvshow_season" style="display:inline;">
        <?php echo Rating::show($season->id, 'tvshow_season'); ?>
    </div>
    <?php
    } ?>
    <?php if (AmpConfig::get('userflags')) { ?>
    <div style="display:table-cell;" id="userflag_<?php echo $season->id; ?>_tvshow_season">
            <?php echo Userflag::show($season->id, 'tvshow_season'); ?>
    </div>
    <?php
    } ?>
<?php
    } ?>
<div id="information_actions">
    <h3><?php echo T_('Actions'); ?>:</h3>
    <ul>
        <?php if (AmpConfig::get('directplay')) { ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=tvshow_season&object_id=' . $season->id, 'play', T_('Play All'), 'directplay_full_' . $season->id); ?>
        </li>
        <?php
    } ?>
        <?php if (Stream_Playlist::check_autoplay_next()) { ?>
            <li>
                <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=season&object_id=' . $season->id . '&playnext=true', 'play_next', T_('Play All Next'), 'nextplay_season_' . $season->id); ?>
            </li>
            <?php
        } ?>
        <?php if (Stream_Playlist::check_autoplay_append()) { ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=season&object_id=' . $season->id . '&append=true', 'play_add', T_('Play All Last'), 'addplay_season_' . $season->id); ?>
        </li>
        <?php
    } ?>
        <?php if (Access::check('interface', 50)) { ?>
        <li>
            <a id="<?php echo 'edit_tvshow_season_' . $season->id ?>" onclick="showEditDialog('tvshow_season_row', '<?php echo $season->id ?>', '<?php echo 'edit_tvshow_season_' . $season->id ?>', '<?php echo T_('Season Edit') ?>', '')">
                <?php echo Ui::get_icon('edit', T_('Edit')); ?>
                <?php echo T_('Edit Season'); ?>
            </a>
        </li>
        <?php
    } ?>

        <?php if ($mediaDeletionChecker->mayDelete($season, Core::get_global('user')->getId())) { ?>
        <li>
            <a id="<?php echo 'delete_tvshow_season_' . $season->id ?>" href="<?php echo $web_path; ?>/tvshow_seasons.php?action=delete&tvshow_season_id=<?php echo $season->id; ?>">
                <?php echo Ui::get_icon('delete', T_('Delete')); ?>
                <?php echo T_('Delete'); ?>
            </a>
        </li>
        <?php
    } ?>
    </ul>
</div>
<?php Ui::show_box_bottom(); ?>
<div class="tabs_wrapper">
    <div id="tabs_container">
        <ul id="tabs">
            <li class="tab_active"><a href="#episodes"><?php echo T_('Episodes'); ?></a></li>
            <!-- Needed to avoid the 'only one' bug -->
            <li></li>
        </ul>
    </div>
    <div id="tabs_content">
        <div id="episodes" class="tab_content" style="display: block;">
<?php
    $browse->show_objects($object_ids, true);
    $browse->store(); ?>
        </div>
    </div>
</div>
