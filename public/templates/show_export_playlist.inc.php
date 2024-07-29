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

$user   = Core::get_global('user');
$browse = new Browse($user);

$browse->set_type('playlist_search');
$browse->set_sort('name', 'ASC');
$browse->set_filter('playlist_open', $user->getId());
$browse->set_conditions(html_entity_decode((string)($input['cond'] ?? '')));

$playlists = $browse->get_objects();

Ui::show_box_top(T_('Export Playlists'), 'box box_export_playlist'); ?>
<form method="post" name="export_playlist" action="<?php echo AmpConfig::get('web_path'); ?>/playlist.php" enctype="multipart/form-data">
    <table class="tabledata">
        <tr>
            <td>
                <?php echo T_('Playlist'); ?>:
            </td>
            <td>
            <?php 
if (!empty($playlists)) {
    foreach ($playlists as $list_id => $list_name) {
        $selected  = ($list_id === $libitem->refresh_id) ? ' selected="selected"' : '';
        $options[] = '<option value="' . $list_id . '"' . $selected . '>' . scrub_out($list_name) . '</option>';
    }
    echo '<select name="playlist_id">' . implode("\n", $options) . '</select>';
} ?>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Type'); ?>:</td>
            <td>
                <select name="playlist_type">
                    <option value="m3u">m3u</option>
                    <option value="m3u8" selected="selected">m3u8</option>
                    <option value="asx">asx</option>
                    <option value="pls">pls</option>
                    <option value="xspf">xspf</option>
                </select>
            </td>
        </tr>
    </table>
    <div class="formValidation">
        <input type="hidden" name="action" value="export_playlist" />
        <input type="submit" value="<?php echo T_('Export Playlist'); ?>" />
    </div>
</form>
<?php Ui::show_box_bottom(); ?>
