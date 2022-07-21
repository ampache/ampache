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
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Catalog;

/** @var Ampache\Repository\Model\User $client */

Ui::show_box_top(T_('Edit Catalog Filter'), 'box box_add_filter');

if (!AmpConfig::get('catalog_filter')) {
    echo "Catalog filters are disabled.  Please enable 'catalog_filters=true' in apache.cfg.php";
} else {
    $filter_id   = (int) $request->getQueryParams()['filter_id'] ?? 0;
    $filter_name = $request->getQueryParams()['filter_name']; ?><p><?php echo T_("In the form below you can rename a filter name and change which catalogs are included in this filter.  If a catalog is not checked, it will be excluded from any users assigned to this profile."); ?></p>
&nbsp;
  <?php echo AmpError::display('general'); ?>
  <form name="edit_filter" enctype="multpart/form-data" method="post" action="<?php echo AmpConfig::get('web_path') . "/admin/filter.php?action=update_filter"; ?>">
    <table class="tabledata">
        <tr>
            <td><?php echo T_('Filter Name'); ?>: *</td>
            <td><input type="text" name="name" maxlength="128" value="<?php echo $filter_name ?>" >
                <?php echo AmpError::display('name'); ?>
		<input type="hidden" name="filter_id" value="<?php echo $filter_id?>" />
            </td>
        </tr>
        <tr>
<?php
    echo "<td>" . T_('Included Catalogs') . ":</td><td></td></tr>";

    $catalogs = Catalog::get_catalogs();
    foreach ($catalogs as $catalog) {
        $cn      = Catalog::get_catalog_name($catalog);
        $checked = (Catalog::check_filter_catalog_enabled($filter_id, $catalog)) ? 'checked' : '';
        echo "<tr><td>$cn</td>" . '<td><input type="checkbox" value="1" name="' . $cn . '" ' . $checked . '></td></tr>';
    } ?>
    </table>
    <div class="formValidation">
        <?php echo Core::form_register('update_filter'); ?>
        <input type="submit" value="<?php echo T_('Edit Filter'); ?>" />
    </div>
</form>
<?php
}
Ui::show_box_bottom();
?>
