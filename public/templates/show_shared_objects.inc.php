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

use Ampache\Repository\Model\Share;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Util\Ui;

/** @var Ampache\Repository\Model\Browse $browse */
/** @var list<int> $object_ids */
?>
<table class="tabledata striped-rows <?php echo $browse->get_css_class(); ?>" data-objecttype="share">
    <thead>
    <tr class="th-top">
            <th class="cel_object essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=share&sort=object', T_('Object'), 'share_sort_object'); ?></th>
            <th class="cel_object_type optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=share&sort=object_type', T_('Object Type'), 'share_sort_object_type'); ?></th>
            <th class="cel_user optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=share&sort=user', T_('User'), 'share_sort_user'); ?></th>
            <th class="cel_creation_date optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=share&sort=creation_date', T_('Creation Date'), 'share_sort_creation_date'); ?></th>
            <th class="cel_lastvisit_date optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=share&sort=lastvisit_date', T_('Last Visit'), 'share_sort_lastvisit_date'); ?></th>
            <th class="cel_counter optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=share&sort=counter', T_('Counter'), 'share_sort_counter'); ?></th>
            <th class="cel_max_counter optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=share&sort=max_counter', T_('Max Counter'), 'share_sort_max_counter'); ?></th>
            <th class="cel_allow_stream optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=share&sort=allow_stream', T_('Allow Stream'), 'share_sort_allow_stream'); ?></th>
            <th class="cel_allow_download optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=share&sort=allow_download', T_('Allow Download'), 'share_sort_allow_download'); ?></th>
            <th class="cel_expire optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=share&sort=expire', T_('Expiry Days'), 'share_sort_expire'); ?></th>
            <th class="cel_public_url essential"><?php echo T_('Public URL'); ?></th>
            <th class="cel_action essential"><?php echo T_('Actions'); ?></th>
        </tr>
    </thead>
    <tbody>
<?php foreach ($object_ids as $share_id) {
    $libitem = new Share($share_id);
    if ($libitem->hasObject()) { ?>
        <tr id="share_<?php echo $libitem->id; ?>">
        <?php require Ui::find_template('show_share_row.inc.php'); ?>
        </tr>
    <?php } ?>
<?php } ?>
    </tbody>
</table>
<?php Ui::show_box_bottom(); ?>
