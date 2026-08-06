<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

// show_add_playlist.inc.php

use Ampache\Config\AmpConfig;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;

Ui::show_box_top(T_('Create Playlist'), 'box box_add_playlist'); ?>
<form name="playlist" method="post" action="<?php echo AmpConfig::get_web_path('/client'); ?>/playlist.php?action=create">
    <table class="tabledata">
        <tr>
            <td><?php echo T_('Name'); ?></td>
            <td><input type="text" name="name" value="<?php echo scrub_out((string) ($_REQUEST['name'] ?? '')); ?>" autofocus />
                <?php echo AmpError::display('name'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Type'); ?></td>
            <td>
                <select name="type">
                    <option value="private"<?php echo (($_REQUEST['type'] ?? 'private') === 'private') ? ' selected="selected"' : ''; ?>><?php echo T_('Private'); ?></option>
                    <option value="public"<?php echo (($_REQUEST['type'] ?? '') === 'public') ? ' selected="selected"' : ''; ?>><?php echo T_('Public'); ?></option>
                </select>
            </td>
        </tr>
    </table>
    <div class="formValidation">
        <?php echo Core::form_register('add_playlist'); ?>
        <input class="button" type="submit" value="<?php echo T_('Create'); ?>" />
    </div>
</form>
<?php Ui::show_box_bottom(); ?>
