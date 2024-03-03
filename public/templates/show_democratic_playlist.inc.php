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
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\Playlist;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Song;

/** @var Ampache\Repository\Model\Browse $browse */
/** @var list<array{object_type: string, object_id: int, id: int}> $object_ids */

$democratic = Democratic::get_current_playlist();
$web_path   = (string)AmpConfig::get('web_path', '');
$use_search = AmpConfig::get('demo_use_search');
$access100  = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN);
$showAlbum  = AmpConfig::get('album_group');
if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
<table class="tabledata striped-rows">
<colgroup>
  <col id="col_action" />
  <col id="col_votes" />
  <col id="col_title" />
  <col id="col_album" />
  <col id="col_artist" />
  <col id="col_time" />
  <?php if ($access100) { ?>
  <col id="col_admin" />
  <?php } ?>
</colgroup>
<?php if (empty($object_ids) && isset($democratic->base_playlist)) {
    $playlist = ($use_search)
        ? new Search($democratic->base_playlist)
        : new Playlist($democratic->base_playlist); ?>
<tr>
    <td><?php echo T_('Playing from base playlist'); ?>.</a></td>
</tr>
<?php
} else { ?>
<thead>
    <tr class="th-top">
        <th class="cel_action"><?php echo T_('Action'); ?></th>
        <th class="cel_votes"><?php echo T_('Votes'); ?></th>
        <th class="cel_title"><?php echo T_('Title'); ?></th>
        <th class="cel_album"><?php echo T_('Album'); ?></th>
        <th class="cel_artist"><?php echo T_('Artist'); ?></th>
        <th class="cel_time"><?php echo T_('Time'); ?></th>
        <?php if ($access100) { ?>
        <th class="cel_admin"><?php echo T_('Admin'); ?></th>
        <?php } ?>
    </tr>
</thead>
<tbody>
<?php $democratic->set_parent();
    foreach ($object_ids as $item) {
        if (!is_array($item)) {
            $item = (array) $item;
        }
        $className = ObjectTypeToClassNameMapper::map($item['object_type']);
        /** @var Song $media */
        $media = new $className($item['object_id']);
        if ($media->isNew()) {
            continue;
        }
        $media->format(); ?>
<tr>
    <td class="cel_action">
    <?php if ($democratic->has_vote($item['object_id'], $item['object_type'])) {
        echo Ajax::button('?page=democratic&action=delete_vote&row_id=' . $item['id'], 'delete', T_('Remove Vote'), 'remove_vote_' . $item['id']);
    } else {
        echo Ajax::button('?page=democratic&action=add_vote&object_id=' . $media->id . '&type=' . scrub_out($item['object_type']), 'tick', T_('Add Vote'), 'remove_vote_' . $item['id']);
    } ?>
    </td>
    <td class="cel_votes" ><?php echo scrub_out((string) $democratic->get_vote($item['id'])); ?></td>
    <td class="cel_title"><?php echo $media->get_f_link(); ?></td>
    <td class="cel_album"><?php echo ($showAlbum) ? $media->get_f_album_link() : $media->get_f_album_disk_link(); ?></td>
    <td class="cel_artist"><?php echo $media->get_f_artist_link(); ?></td>
    <td class="cel_time"><?php echo $media->f_time; ?></td>
    <?php if ($access100) { ?>
    <td class="cel_admin">
    <?php echo Ajax::button('?page=democratic&action=delete&row_id=' . $item['id'], 'disable', T_('Delete'), 'delete_row_' . $item['id']); ?>
    </td>
    <?php } ?>
</tr>
<?php
    } ?>
</tbody>
<tfoot>
    <tr class="th-bottom">
        <th class="cel_action"><?php echo T_('Action'); ?></th>
        <th class="cel_votes"><?php echo T_('Votes'); ?></th>
        <th class="cel_title"><?php echo T_('Title'); ?></th>
        <th class="cel_album"><?php echo T_('Album'); ?></th>
        <th class="cel_artist"><?php echo T_('Artist'); ?></th>
        <th class="cel_time"><?php echo T_('Time'); ?></th>
        <?php if ($access100) { ?>
        <th class="cel_admin"><?php echo T_('Admin'); ?></th>
        <?php } ?>
    </tr>
</tfoot>
<?php } ?>
</table>
<?php show_table_render(); ?>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
