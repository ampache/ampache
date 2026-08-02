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

// show_collection.inc.php

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Api\RefreshReordered\RefreshCollectionItemsAction;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\User;

/** @var Collection $collection */
/** @var array<int, array{object_type: LibraryItemEnum, object_id: int, track_id: int, track: int, time: int}> $object_ids */

$web_path = AmpConfig::get_web_path('/client');

ob_start();
echo $collection->getFullname();
$title = ob_get_contents();
ob_end_clean();

// The play buttons queue the expansion of the members; a collection of labels expands to nothing
$playable = $collection->get_medias() !== [];

Ui::show_box_top('<div id="collection_row_' . $collection->getId() . '">' . $title . '</div>', 'info-box'); ?>
<div class="item_right_info">
<?php $size = Ui::is_grid_view('collection')
    ? ['width' => 150, 'height' => 150]
    : ['width' => 384, 'height' => 384];
$collection->display_art($size, false, false); ?>
</div>
<?php if (User::is_registered() && AmpConfig::get('ratings')) { ?>
<span id="rating_<?php echo $collection->getId(); ?>_collection">
    <?php echo Rating::show($collection->getId(), 'collection'); ?>
</span>
<span id="userflag_<?php echo $collection->getId(); ?>_collection">
    <?php echo Userflag::show($collection->getId(), 'collection'); ?>
</span>
<?php } ?>
<div id="information_actions">
    <ul>
        <li>
            <?php echo T_('Owner'); ?>: <?php echo scrub_out($collection->username ?? ''); ?>
        </li>
        <li>
            <?php echo T_('Type'); ?>:
            <?php echo ($collection->object_type === null || $collection->object_type === '')
                ? T_('Mixed')
                : scrub_out($collection->object_type); ?>
        </li>
<?php if ($playable && AmpConfig::get('directplay')) { ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=collection&object_id=' . $collection->getId(), 'play_circle', T_('Play All'), 'directplay_full_' . $collection->getId()); ?>
        </li>
<?php }
if ($playable && Stream_Playlist::check_autoplay_next()) { ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=collection&object_id=' . $collection->getId() . '&playnext=true', 'menu_open', T_('Play All Next'), 'nextplay_collection_' . $collection->getId()); ?>
        </li>
<?php }
if ($playable && Stream_Playlist::check_autoplay_append()) { ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=collection&object_id=' . $collection->getId() . '&append=true', 'low_priority', T_('Play All Last'), 'addplay_collection_' . $collection->getId()); ?>
        </li>
<?php }
if ($collection->has_collaborate()) { ?>
        <li>
            <a id="<?php echo 'edit_collection_' . $collection->getId(); ?>" onclick="showEditDialog('collection_row', '<?php echo $collection->getId(); ?>', '<?php echo 'edit_collection_' . $collection->getId(); ?>', '<?php echo addslashes(T_('Collection Edit')); ?>', '')">
                <?php echo Ui::get_material_symbol('edit', T_('Edit')); ?>
                <?php echo T_('Edit'); ?>
            </a>
        </li>
<?php
    // Only a mixed collection is dragged here; a pinned one is shown through its own type's browse, which has
    // no drag handle and no order of its own to save
    if ($collection->object_type === null || $collection->object_type === '') { ?>
        <li>
            <?php // Confirmed first: the link sits next to Edit and Delete, and a stray click would overwrite the
            // stored order with whatever the page happens to be showing. `data-confirm` is no use here because an
            // inline onclick fires before the delegated handler can intercept it.?>
            <a onclick="window.ampacheConfirm('<?php echo addslashes(T_('Save the current order of this collection?')); ?>').then(function (ok) { if (ok) { submitNewItemsOrder('<?php echo $collection->getId(); ?>', 'reorder_collection_table', 'track_', '<?php echo $web_path; ?>/collection.php?action=set_track_numbers&collection=<?php echo $collection->getId(); ?>', '<?php echo RefreshCollectionItemsAction::REQUEST_KEY; ?>'); } })">
                <?php echo Ui::get_material_symbol('save', T_('Save Track Order'));
        echo "&nbsp;" . T_('Save Track Order'); ?>
            </a>
        </li>
<?php }
    }
if ($collection->has_access()) { ?>
        <li>
            <a href="<?php echo $web_path; ?>/collection.php?action=delete_collection&amp;collection=<?php echo $collection->getId(); ?>" data-confirm="<?php echo T_('Do you really want to delete this Collection?'); ?>">
                <?php echo Ui::get_material_symbol('close'); ?>
                <?php echo T_('Delete'); ?>
            </a>
        </li>
<?php } ?>
    </ul>
</div>
<?php Ui::show_box_bottom(); ?>
<?php if ($object_ids === []) { ?>
    <p><?php echo T_('This Collection is empty'); ?></p>
<?php } else {
    $pinnedType = ($collection->object_type === null || $collection->object_type === '')
        ? null
        : Collection::normalizeType($collection->object_type);

    $browse = new Browse();
    $browse->set_use_filters(false);
    $browse->set_static_content(true);

    if ($pinnedType !== null && Browse::is_valid_type($pinnedType)) {
        // Every member shares one type, so that type's own browse can render it the way it always does
        $browse->set_type($pinnedType);
        $browse->show_objects(array_column($object_ids, 'object_id'));
        $browse->store();
    } else { ?>
<div id="reordered_list_<?php echo $collection->getId(); ?>">
<?php
        // Mixed, so the members stay in one list in curated order and each row names its own type. The second
        // argument turns the drag handling on, the same flag a playlist passes.
        $browse->set_type('collection_items');
        $browse->add_supplemental_object('collection', $collection);
        $browse->show_objects($object_ids, true);
        $browse->store(); ?>
</div>
<?php }
    } ?>
