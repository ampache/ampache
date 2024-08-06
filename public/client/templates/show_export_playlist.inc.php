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
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Catalog;

Ui::show_box_top(T_('Export Playlist to File'), 'box box_export_playlist'); ?>
<form method="post" name="export_playlist" action="<?php echo AmpConfig::get('web_path'); ?>/stream.php" enctype="multipart/form-data">
    <table class="tabledata">
        <tr>
            <td>
                <h2><?php echo T_('Playlist'); ?></h2>
            </td>
            <td>
            <?php
            $user    = Core::get_global('user');
            $browse = new Browse();
            $browse->set_type('playlist_search');
            $browse->set_sort('name', 'ASC');
            $browse->set_filter('playlist_open', $user?->getId());
            $objects = $browse->get_objects();
            $options = [];
            if (!empty($objects)) {
                $playlists = Catalog::get_name_array($objects, 'playlist_search', 'name');
                foreach ($playlists as $list) {
                    $options[] = '<option value="' . ($list['id'] ?? '') . '">' . scrub_out(($list['name'] ?? '')) . '</option>';
                }
            echo '<select name="object_id">' . implode("\n", $options) . '</select>';
            } ?>
            </td>
        </tr>
    </table>
    <div class="formValidation">
        <input type="hidden" name="action" value="stream_item" />
        <input type="hidden" name="object_type" value="playlist" />
        <input type="submit" value="<?php echo T_('Export Playlist'); ?>" />
    </div>
</form>
<?php Ui::show_box_bottom(); ?>
