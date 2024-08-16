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

Ui::show_box_top(T_('Importing a Playlist from a File'), 'box box_import_playlist'); ?>
<form method="post" name="import_playlist" action="<?php echo AmpConfig::get_web_path('/client'); ?>/playlist.php" enctype="multipart/form-data">
    <table class="tabledata">
        <tr>
            <td>
                <?php echo T_('Filename'); ?> (<?php echo AmpConfig::get('catalog_playlist_pattern'); ?>):
            </td>
            <td><input type="file" name="filename" value="<?php echo scrub_out(Core::get_request('filename')); ?>" /></td>
        </tr>
        <tr>
            <td><?php echo T_('Type'); ?>:</td>
            <td>
                <select name="playlist_visibility">
                    <option value="private"><?php echo T_("Private"); ?></option>
                    <option value="public" selected="selected"><?php echo T_("Public"); ?></option>
                </select>
            </td>
        </tr>
    </table>
    <div class="formValidation">
        <input type="hidden" name="action" value="import_playlist" />
        <input type="submit" value="<?php echo T_('Import Playlist'); ?>" />
    </div>
</form>
<?php Ui::show_box_bottom(); ?>
