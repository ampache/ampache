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
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Democratic;

/** @var Democratic $democratic */

$level25  = ($democratic->level == 25) ? 'selected' : '';
$level50  = ($democratic->level == 50) ? 'selected' : '';
$level75  = ($democratic->level == 75) ? 'selected' : '';
$level100 = ($democratic->level == 100) ? 'selected' : '';
$default  = ($democratic->primary) ? 'checked' : '';
Ui::show_box_top(T_('Configure Democratic Playlist')); ?>
<form method="post" action="<?php echo AmpConfig::get_web_path('/client'); ?>/democratic.php?action=create" enctype="multipart/form-data">
    <table class="tabledata">
        <tr>
            <td><?php echo T_('Name'); ?></td>
            <td><input type="text" name="name" value="<?php echo scrub_out($democratic->name); ?>" /></td>
        </tr>
        <tr>
            <td><?php echo T_('Base Playlist'); ?></td>
            <td><?php echo Democratic::show_playlist_select('democratic', (string)$democratic->base_playlist); ?></td>
        </tr>
        <tr>
            <td><?php echo T_('Cooldown Time'); ?></td>
            <td><input type="text" maxlength="6" name="cooldown" value="<?php echo $democratic->cooldown; ?>" />&nbsp;(<?php echo T_('minutes'); ?>)</td>
        </tr>
        <tr>
            <td><?php echo T_('Level'); ?></td>
            <td>
                <select name="level">
                    <option value="25" <?php echo $level25; ?>><?php echo T_('User'); ?></option>
                    <option value="50" <?php echo $level50; ?>><?php echo T_('Content Manager'); ?></option>
                    <option value="75" <?php echo $level75; ?>><?php echo T_('Catalog Manager'); ?></option>
                    <option value="100" <?php echo $level100; ?>><?php echo T_('Admin'); ?></option>
                </select>
            </td>
        <tr>
            <td><?php echo T_('Make Default'); ?></td>
            <td><input type="checkbox" name="make_default" value="1" <?php echo $default; ?> /></td>
        </tr>
    </table>
    <div class="formValidation">
        <?php echo Core::form_register('create_democratic'); ?>
        <input type="submit" value="<?php echo T_('Update'); ?>" />
    </div>
</form>
<?php Ui::show_box_bottom(); ?>
