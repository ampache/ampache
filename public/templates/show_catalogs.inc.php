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

use Ampache\Repository\Model\Catalog;
use Ampache\Module\Util\Ui;

/** @var Ampache\Repository\Model\Browse $browse */
/** @var list<int> $object_ids */

if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
<table class="tabledata striped-rows <?php echo $browse->get_css_class(); ?>" data-objecttype="catalog">
    <thead>
        <tr class="th-top">
            <th class="cel_catalog essential persist"><?php echo T_('Name'); ?></th>
            <th class="cel_info essential"><?php echo T_('Path'); ?></th>
            <th class="cel_lastverify optional"><?php echo T_('Last Verify'); ?></th>
            <th class="cel_lastadd optional"><?php echo T_('Last Add'); ?></th>
            <th class="cel_lastclean optional "><?php echo T_('Last Clean'); ?></th>
            <th class="cel_action cel_action_text essential"><?php echo T_('Actions'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
            foreach ($object_ids as $catalog_id) {
                $catalog = Catalog::create_from_id($catalog_id);
                if ($catalog === null) {
                    continue;
                } ?>
        <tr id="catalog_<?php echo $catalog->id; ?>">
            <?php require Ui::find_template('show_catalog_row.inc.php'); ?>
        </tr>
        <?php
            } ?>
        <?php if (!count($object_ids)) { ?>
        <tr>
            <td colspan="6"><span class="nodata"><?php echo T_('No Catalog found'); ?></span></td>
        </tr>
            <?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_catalog"><?php echo T_('Name'); ?></th>
            <th class="cel_info"><?php echo T_('Path'); ?></th>
            <th class="cel_lastverify"><?php echo T_('Last Verify'); ?></th>
            <th class="cel_lastadd"><?php echo T_('Last Add'); ?></th>
            <th class="cel_lastclean"><?php echo T_('Last Clean'); ?></th>
            <th class="cel_action cel_action_text"><?php echo T_('Actions'); ?></th>
        </tr>
    </tfoot>
</table>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
