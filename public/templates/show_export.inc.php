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
use Ampache\Repository\Model\Catalog;
use Ampache\Module\Util\Ui;

/** @var array<int, Catalog> $catalogs */
/** @var array<string, string> $exportTypes */

Ui::show_box_top(T_('Export Catalog'), 'box box_export'); ?>
<form name="export" action="<?php echo AmpConfig::get('web_path'); ?>/admin/export.php?action=export" method="post" enctype="multipart/form-data">
    <table class="tableform">
        <tr>
            <td><strong><?php echo T_('Catalog'); ?>:</strong></td>
            <td>
                <select id="export_catalog" name="export_catalog">
                    <option value=""><?php echo T_('All'); ?></option>
                    <?php
foreach ($catalogs as $catalog) {
    $current_name = 'catalog_' . $catalog->getId(); ?>
                        <option value="<?php echo $catalog->getId(); ?>" <?php echo $current_name; ?>><?php echo scrub_out($catalog->get_fullname()); ?></option>
                    <?php
} ?>
                </select>
            </td>
        </tr>
        <tr>
            <td><strong><?php echo T_('Format'); ?>:</strong></td>
            <td>
                <select id="export_format" name="export_format">
                    <?php
                    foreach ($exportTypes as $key => $label) {
                        echo sprintf('<option value="%s">%s</option>', $key, $label);
                    }
?>
                </select>
            </td>
        </tr>
    </table>
    <div class="formValidation">
        <input type="submit" value="<?php echo T_('Export'); ?>" />
    </div>
</form>
<?php Ui::show_box_bottom(); ?>
