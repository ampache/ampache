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
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Democratic;

/** @var Democratic $democratic */

/**
 * show_playlist_select
 * This one is for playlists!
 * @param string $name
 * @param string $selected
 * @param string $style
 */
function show_playlist_select($name, $selected = '', $style = '')
{
    echo "<select name=\"$name\" style=\"$style\">\n";
    echo "\t<option value=\"\">" . T_('None') . "</option>\n";

    $sql              = "SELECT `id`, `name` FROM `playlist` ORDER BY `name`";
    $db_results       = Dba::read($sql);
    $nb_items         = Dba::num_rows($db_results);
    $index            = 1;
    $already_selected = false;

    while ($row = Dba::fetch_assoc($db_results)) {
        $select_txt = '';
        if (!$already_selected && ($row['id'] == $selected || $index == $nb_items)) {
            $select_txt       = 'selected="selected"';
            $already_selected = true;
        }

        echo "\t<option value=\"" . $row['id'] . "\" $select_txt>" . scrub_out($row['name']) . "</option>\n";
        ++$index;
    } // end while users

    echo "</select>\n";
} // show_playlist_select

Ui::show_box_top(T_('Configure Democratic Playlist')); ?>
<form method="post" action="<?php echo AmpConfig::get('web_path'); ?>/democratic.php?action=create" enctype="multipart/form-data">
    <table class="tabledata">
        <tr>
            <td><?php echo T_('Name'); ?></td>
            <td><input type="text" name="name" value="<?php echo scrub_out($democratic->name); ?>" /></td>
        </tr>
        <tr>
            <td><?php echo T_('Base Playlist'); ?></td>
            <td><?php show_playlist_select('democratic', $democratic->base_playlist); ?></td>
        </tr>
        <tr>
            <td><?php echo T_('Cooldown Time'); ?></td>
            <td><input type="text" maxlength="6" name="cooldown" value="<?php echo $democratic->cooldown; ?>" />&nbsp;(<?php echo T_('minutes'); ?>)</td>
        </tr>
        <tr>
            <td><?php echo T_('Level'); ?></td>
            <td>
                <select name="level">
                    <option value="25" <?php if ($democratic->level == 25) {
    echo "selected";
} ?>><?php echo T_('User'); ?></option>
                    <option value="50" <?php if ($democratic->level == 50) {
    echo "selected";
} ?>><?php echo T_('Content Manager'); ?></option>
                    <option value="75" <?php if ($democratic->level == 75) {
    echo "selected";
} ?>><?php echo T_('Catalog Manager'); ?></option>
                    <option value="100" <?php if ($democratic->level == 100) {
    echo "selected";
} ?>><?php echo T_('Admin'); ?></option>
                </select>

        <tr>
            <td><?php echo T_('Make Default'); ?></td>
            <td><input type="checkbox" name="make_default" value="1" <?php if ($democratic->primary) {
    echo "checked";
} ?> /></td>
        </tr>
        <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr>
            <td><?php echo T_('Force Democratic Play'); ?></td>
            <td><input type="checkbox" value="1" name="force_democratic" /></td>
        </tr>
    </table>
    <div class="formValidation">
        <?php echo Core::form_register('create_democratic'); ?>
        <input type="submit" value="<?php echo T_('Update'); ?>" />
    </div>
</form>
<?php Ui::show_box_bottom(); ?>
