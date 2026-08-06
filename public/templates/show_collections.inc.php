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

// show_collections.inc.php

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\User;

/** @var Ampache\Module\Database\Query\Browse $browse */
/** @var list<int> $object_ids */

$web_path = AmpConfig::get_web_path();

$is_table  = !$browse->is_grid_view();
$cel_cover = ($is_table) ? 'cel_cover' : 'grid_cover';
$css_class = ($is_table) ? '' : ' gridview';

// Rows are rendered here rather than through the deprecated PHPTal Gui view adapter layer
$show_direct_play = AmpConfig::get('directplay');
$show_ratings     = User::is_registered() && AmpConfig::get('ratings');

// translate once
$name_text   = T_('Name');
$items_text  = T_('# Items');
$type_text   = T_('Type');
$holds_text  = T_('Holds');
$owner_text  = T_('Owner');
$rating_text = T_('Rating');
$action_text = T_('Actions');

$sort_url = '?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=collection&sort=';

/** @var CollectionRepositoryInterface $collectionRepository */
$user                 = Core::get_global('user');
$user                 = ($user instanceof User) ? $user : null;

if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) { ?>
<div id="information_actions">
    <ul>
        <li>
            <a href="<?php echo $web_path; ?>/collection.php?action=show_create">
                <?php echo Ui::get_material_symbol('add_circle', T_('Create Collection')); ?>
                <?php echo T_('Create Collection'); ?>
            </a>
        </li>
    </ul>
</div>
<?php }

if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
<table class="tabledata striped-rows <?php echo $css_class; ?>" data-objecttype="collection">
    <thead>
        <tr class="th-top">
            <th class="cel_play essential"></th>
            <th class="<?php echo $cel_cover; ?> optional"><?php echo T_('Art'); ?></th>
            <th class="cel_collection essential persist"><?php echo Ajax::text($sort_url . 'name', $name_text, 'collection_sort_name'); ?></th>
            <th class="cel_medias optional"><?php echo Ajax::text($sort_url . 'last_count', $items_text, 'collection_sort_last_count'); ?></th>
            <th class="cel_type optional"><?php echo Ajax::text($sort_url . 'type', $type_text, 'collection_sort_type'); ?></th>
            <th class="cel_object_type optional"><?php echo Ajax::text($sort_url . 'object_type', $holds_text, 'collection_sort_object_type'); ?></th>
<?php if ($show_ratings) { ?>
            <th class="cel_ratings optional"><?php echo Ajax::text($sort_url . 'rating', $rating_text, 'collection_sort_rating'); ?></th>
<?php } ?>
            <th class="cel_owner essential"><?php echo Ajax::text($sort_url . 'username', $owner_text, 'collection_sort_username'); ?></th>
            <th class="cel_action essential"><?php echo $action_text; ?></th>
        </tr>
    </thead>
    <tbody>
<?php if ($show_ratings) {
    Rating::build_cache('collection', $object_ids);
    Userflag::build_cache('collection', $object_ids);
}

foreach ($object_ids as $collection_id) {
    $libitem = $collectionRepository->findById((int) $collection_id);
    // Skip a row whose collection vanished between the browse query and this loop
    if (
        $libitem === null
        || !$libitem->isVisible($user)
    ) {
        continue;
    }

    $item_count = $libitem->get_item_count(); ?>
        <tr id="collection_row_<?php echo $libitem->getId(); ?>" class="libitem_menu" data-object-type="collection" data-object-id="<?php echo $libitem->getId(); ?>">
            <td class="cel_play">
<?php if ($show_direct_play && $item_count > 0) {
    echo Ajax::button('?page=stream&action=directplay&object_type=collection&object_id=' . $libitem->getId(), 'play_circle', T_('Play'), 'play_collection_' . $libitem->getId());
} ?>
            </td>
            <td class="<?php echo $cel_cover; ?>">
<?php // linked rather than picker-enabled: `Art::display()` only offers the edit/clear actions when no link is
    // given, and on a browse row the thumbnail should navigate to the collection
    $libitem->display_art(['width' => 100, 'height' => 100], true, true); ?>
            </td>
            <td class="cel_collection"><a href="<?php echo $libitem->get_link(); ?>"><?php echo scrub_out($libitem->getFullname()); ?></a></td>
            <td class="cel_medias"><?php echo $item_count; ?></td>
            <td class="cel_type"><?php echo scrub_out((string) $libitem->type); ?></td>
            <td class="cel_object_type">
<?php echo ($libitem->object_type === null || $libitem->object_type === '')
    ? T_('Mixed')
    : scrub_out($libitem->object_type); ?>
            </td>
<?php if ($show_ratings) { ?>
            <td class="cel_ratings">
                <div id="rating_<?php echo $libitem->getId(); ?>_collection"><?php echo Rating::show($libitem->getId(), 'collection'); ?></div>
                <div id="userflag_<?php echo $libitem->getId(); ?>_collection"><?php echo Userflag::show($libitem->getId(), 'collection'); ?></div>
            </td>
<?php } ?>
            <td class="cel_owner"><?php echo scrub_out((string) $libitem->username); ?></td>
            <td class="cel_action">
<?php if ($libitem->has_collaborate()) { ?>
                <a id="<?php echo 'edit_collection_' . $libitem->getId(); ?>" onclick="showEditDialog('collection_row', '<?php echo $libitem->getId(); ?>', '<?php echo 'edit_collection_' . $libitem->getId(); ?>', '<?php echo addslashes(T_('Collection Edit')); ?>', '')">
                    <?php echo Ui::get_material_symbol('edit', T_('Edit')); ?>
                </a>
<?php }
if ($libitem->has_access()) { ?>
                <a href="<?php echo $web_path; ?>/collection.php?action=delete_collection&amp;collection=<?php echo $libitem->getId(); ?>" data-confirm="<?php echo T_('Do you really want to delete this Collection?'); ?>">
                    <?php echo Ui::get_material_symbol('close', T_('Delete')); ?>
                </a>
<?php } ?>
            </td>
        </tr>
<?php } ?>
<?php if (!count($object_ids)) { ?>
        <tr>
            <td colspan="<?php echo ($show_ratings) ? 9 : 8; ?>"><span class="nodata"><?php echo T_('Found nothing to show'); ?></span></td>
        </tr>
<?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play"></th>
            <th class="<?php echo $cel_cover; ?>"></th>
            <th class="cel_collection"><?php echo $name_text; ?></th>
            <th class="cel_medias"><?php echo $items_text; ?></th>
            <th class="cel_type"><?php echo $type_text; ?></th>
            <th class="cel_object_type"><?php echo $holds_text; ?></th>
<?php if ($show_ratings) { ?>
            <th class="cel_ratings"><?php echo $rating_text; ?></th>
<?php } ?>
            <th class="cel_owner"><?php echo $owner_text; ?></th>
            <th class="cel_action"><?php echo $action_text; ?></th>
        </tr>
    </tfoot>
</table>
<?php show_table_render(); ?>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
