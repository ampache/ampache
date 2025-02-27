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
use Ampache\Repository\Model\Catalog;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;

$default_rename = "%T - %t";
$default_sort   = "%a/%A";
$allow_video    = AmpConfig::get('allow_video'); ?>
<?php Ui::show_box_top(T_('Add Catalog'), 'box box_add_catalog'); ?>
<p><?php
$catalog_filter = "";
if (AmpConfig::get('catalog_filter')) {
    $catalog_filter = "<br>" . T_("New catalogs are added to the DEFAULT group when created");
}
echo T_("In the form below enter either a local path (i.e. /data/music) or the URL to a remote Ampache installation (i.e http://theotherampache.com)") . $catalog_filter; ?></p>
&nbsp;
<?php echo AmpError::display('general'); ?>

<form name="update_catalog" method="post" action="<?php echo AmpConfig::get_web_path('/admin'); ?>/catalog.php" enctype="multipart/form-data">
    <table class="tabledata">
        <tr>
            <td style="width: 25%;"><?php echo T_('Catalog Name'); ?>: </td>
            <td><input type="text" name="name" value="<?php echo scrub_out(Core::get_post('name')); ?>" /></td>
            <td style="vertical-align:top; font-family: monospace;" rowspan="6" id="patterns_example">
                <strong><?php echo T_('Auto-inserted Fields'); ?>:</strong><br />
                <span class="format-specifier">%A</span> = <?php echo T_('Album'); ?><br />
                <span class="format-specifier">%B</span> = <?php echo T_('Album Artist'); ?><br />
                <span class="format-specifier">%a</span> = <?php echo T_('Song Artist'); ?><br />
                <span class="format-specifier">%m</span> = <?php echo T_('Artist'); ?><br />
                <span class="format-specifier">%t</span> = <?php echo T_('Song Title'); ?><br />
                <span class="format-specifier">%T</span> = <?php echo T_('Track (0 padded)'); ?><br />
                <span class="format-specifier">%d</span> = <?php echo T_('Disk'); ?><br />
                <span class="format-specifier">%g</span> = <?php echo T_('Genre'); ?><br />
                <span class="format-specifier">%y</span> = <?php echo T_('Year'); ?><br />
                <span class="format-specifier">%Y</span> = <?php echo T_('Original Year'); ?><br />
                <span class="format-specifier">%c</span> = <?php echo T_('Comment'); ?><br />
                <span class="format-specifier">%l</span> = <?php echo T_('Label'); ?><br />
                <span class="format-specifier">%r</span> = <?php echo T_('Release Type'); ?><br />
                <span class="format-specifier">%R</span> = <?php echo T_('Release Status'); ?><br />
                <span class="format-specifier">%s</span> = <?php echo T_('Release Comment'); ?><br />
                <span class="format-specifier">%C</span> = <?php echo T_('Catalog Number'); ?><br />
                <span class="format-specifier">%b</span> = <?php echo T_('Barcode'); ?><br />
                <span class="format-specifier">%o</span> = <?php echo T_('Ignore'); ?><br />
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Catalog Type'); ?>: </td>
            <td>
                <?php
                echo '<script>' . "var type_fields = new Array();type_fields['none'] = '';";
$seltypes = '<option value="none">[' . T_("Select") . ']</option>';

foreach (Catalog::CATALOG_TYPES as $type => $className) {
    /** @var Catalog $catalog */
    $catalog = new $className();

    if ($catalog->is_installed()) {
        $seltypes .= '<option value="' . $type . '">' . $type . '</option>';
        echo "type_fields['" . $type . "'] = \"";
        $fields = $catalog->catalog_fields();
        $help   = $catalog->get_create_help();
        if (!empty($help)) {
            echo "<tr><td></td><td>" . $help . "</td></tr>";
        }
        foreach ($fields as $key => $field) {
            echo "<tr><td style='width: 25%;'>" . $field['description'] . ":</td><td>";
            $value = (array_key_exists('value', $field)) ? $field['value'] : '';

            switch ($field['type']) {
                case 'checkbox':
                    echo "<input type='checkbox' name='" . $key . "' value='1' " . ((!empty($value)) ? 'checked' : '') . "/>";
                    break;
                default:
                    echo "<input type='" . $field['type'] . "' name='" . $key . "' value='" . $value . "' />";
                    break;
            }
            echo "</td></tr>";
        }
        echo "\";";
    }
}

echo "function catalogTypeChanged() {var sel = document.getElementById('catalog_type');var seltype = sel.options[sel.selectedIndex].value;var ftbl = document.getElementById('catalog_type_fields');ftbl.innerHTML = '<table class=\"tabledata\">' + type_fields[seltype] + '</table>';} </script><select name=\"type\" id=\"catalog_type\" onChange=\"catalogTypeChanged();\">" . $seltypes . "</select>";
?>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Filename Pattern'); ?>: </td>
            <td><input type="text" name="rename_pattern" value="<?php echo $default_rename; ?>" /></td>
        </tr>
        <tr>
            <td><?php echo T_('Folder Pattern'); ?>:<br /><?php echo T_("(no leading or ending '/')"); ?></td>
            <td><input type="text" name="sort_pattern" value="<?php echo $default_sort; ?>" /></td>
        </tr>
        <tr>
            <td><?php echo T_('Gather Art'); ?>:</td>
            <td><input type="checkbox" name="gather_art" value="1" checked /></td>
        </tr>
        <tr>
            <td><?php echo T_('Build Playlists from Playlist Files. (m3u, m3u8, asx, pls, xspf)'); ?>:</td>
            <td><input type="checkbox" name="parse_playlist" value="1" /></td>
        </tr>
        <tr>
            <td><?php echo T_('Catalog Media Type'); ?>:</td>
            <td>

                <select name="gather_media">
                    <option value="music"><?php echo T_('Music'); ?></option>
            <?php if (AmpConfig::get('podcast')) { ?>
                    <option value="podcast"><?php echo T_('Podcast'); ?></option>
            <?php } ?>
            <?php if ($allow_video) { ?>
                    <option value="video"><?php echo T_('Video'); ?></option>
            <?php } ?>
                </select>
            </td>
        </tr>
    </table>
    <div id="catalog_type_fields">
    </div>
    <div class="formValidation">
        <input type="hidden" name="action" value="add_catalog" />
        <?php echo Core::form_register('add_catalog'); ?>
        <input class="button" type="submit" value="<?php echo T_('Add Catalog'); ?>" />
    </div>
</form>
<?php Ui::show_box_bottom(); ?>
