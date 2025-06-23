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
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;

/** @var Ampache\Repository\Model\Browse $browse */
/** @var string|null $search_type */

Ui::show_box_top(T_('Options'), 'info-box');
$search_type = $search_type ?? (string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS); ?>
<div id="information_actions">
<ul>
<?php if (in_array($search_type, ['song', 'album', 'artist'])) {
    if (AmpConfig::get('directplay')) { ?>
        <li>
        <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=browse&object_id=' . $browse->id, 'play_circle', T_('Play'), 'play_browse_browse_' . $browse->id); ?>
        </li>
        <?php if (Stream_Playlist::check_autoplay_next()) { ?>
            <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=browse&object_id=' . $browse->id . '&playnext=true', 'menu_open', T_('Play next'), 'nextplay_browse_' . $browse->id); ?>
            </li>
        <?php }
        if (Stream_Playlist::check_autoplay_append()) { ?>
            <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=browse&object_id=' . $browse->id . '&append=true', 'low_priority', T_('Play last'), 'addplay_browse_' . $browse->id); ?>
            </li>
        <?php }
        } ?>
    <li>
        <?php echo Ajax::button_with_text('?action=basket&type=browse_set&browse_id=' . $browse->id, 'new_window', T_('Add to Temporary Playlist'), 'add_search_results'); ?>
    </li>
    <li>
        <?php echo Ajax::button_with_text('?action=basket&type=browse_set_random&browse_id=' . $browse->id, 'shuffle', T_('Random to Temporary Playlist'), 'add_search_results_random'); ?>
    </li>
<?php }
global $dic; // @todo remove after refactoring
$zipHandler = $dic->get(ZipHandlerInterface::class);
if (Access::check_function(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) && $zipHandler->isZipable($search_type)) { ?>
<li>
    <a class="nohtml" href="<?php echo AmpConfig::get_web_path('/client'); ?>/batch.php?action=browse&type=<?php echo scrub_out($search_type); ?>&browse_id=<?php echo $browse->id; ?>">
        <?php echo Ui::get_material_symbol('folder_zip', T_('Batch download')); ?>
        <?php echo T_('Batch download'); ?>
    </a>
</li>
    <?php } ?>
</ul>
</div>
<?php Ui::show_box_bottom(); ?>
