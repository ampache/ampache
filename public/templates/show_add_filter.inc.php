<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Catalog;

/** @var string $filter_name */

Ui::show_box_top(T_('Add Catalog Filter'), 'box box_add_filter');

if (!AmpConfig::get('catalog_filter')) {
    echo T_("Please enable 'catalog_filter' in your sever config file");
} else { ?>
  <p><?php echo T_("Catalog filters are a way to stop users accessing different catalogs"); ?></p>
  <p><?php echo T_("If you do not tick a catalog, it will be hidden from users that you assign to this filter"); ?></p>
&nbsp;
  <?php echo AmpError::display('general'); ?>
  <form name="add_filter" enctype="multipart/form-data" method="post" action="<?php echo AmpConfig::get('web_path') . "/admin/filter.php?action=add_filter"; ?>">
    <table class="tabledata">
        <tr>
            <td><?php echo T_('Filter Name'); ?>:</td>
            <td><input type="text" name="name" maxlength="128" value="<?php echo $filter_name; ?>" />
                <?php echo AmpError::display('name'); ?>
            </td>
        </tr>
        <tr>
<?php echo "<td>" . T_('Catalogs') . ":</td><td></td></tr>";
    $catalogs = Catalog::get_catalogs();
    foreach ($catalogs as $catalog_id) {
        $catalog_name = Catalog::getName($catalog_id);
        echo "<tr><td>$catalog_name</td>" . '<td><input type="checkbox" name="catalog_' . $catalog_id . '" value="1"></td></tr>';
    } ?>
    </table>
    <div class="formValidation">
        <?php echo Core::form_register('add_filter'); ?>
        <input type="submit" value="<?php echo T_('Add Filter'); ?>" />
    </div>
</form>
<?php Ui::show_box_bottom();
} ?>
