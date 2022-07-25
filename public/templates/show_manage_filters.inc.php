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

use Ampache\Repository\Model\Catalog;
use Ampache\Module\Util\Ui;

//debug_event(self::class, 'manage filters template', 5);
/** @var Ampache\Repository\Model\Browse $browse */
/** @var array $object_ids */
Ui::show_box_top(T_('Manage Catalog Filters'), 'box box_manage_filter');
?>

<?php //require Ui::find_template('list_header.inc.php');?>
<table class="tabledata striped-rows" data-objecttype="filter">
    <thead>
        <tr class="th-top">
            <th class="cel_name essential persist"><?php echo T_('Filter name'); ?></th>
            <th class="cel_num_users essential"><?php echo T_('Number of Users'); ?></th>
            <th class="cel_num_catalogs essential"><?php echo T_('Number of Catalogs'); ?></th>
            <th class="cel_action cel_action_text essential"><?php echo T_('Actions'); ?></th>
        </tr>
    </thead>
    <tbody>
<?php
$filters = Catalog::get_catalog_filter_names();

foreach ($filters as $filter) {
    $filter_id    = Catalog::get_catalog_filter_by_name($filter);
    $num_users    = Catalog::filter_user_count($filter_id);
    $num_catalogs = Catalog::filter_catalog_count($filter_id);
    //debug_event(self::class, "Values:  fname:$filter, fid:$filter_id, nu:$num_users, nc:num_catalogs", 5);?>
        <tr id="<?php $filter ?>">
            <?php require Ui::find_template('show_filter_row.inc.php'); ?>
        </tr>
<?php
}
?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_name"><?php echo T_('Filter Name'); ?></th>
            <th class="cel_num_users"><?php echo T_('Number of Users'); ?></th>
            <th class="cel_num_catalogs"><?php echo T_('Number of Catalogs'); ?></th>
            <th class="cel_action cel_action_text"><?php echo T_('Actions'); ?></th>
        </tr>
    </tfoot>
</table>
<?php //require Ui::find_template('list_header.inc.php');?>
