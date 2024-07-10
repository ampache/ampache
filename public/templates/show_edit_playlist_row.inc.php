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

use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;

/** @var Playlist $libitem */
?>
<div>
    <form method="post" id="edit_playlist_<?php echo $libitem->id; ?>" class="edit_dialog_content">
        <table class="tabledata">
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Name'); ?></td>
                <td><input type="text" name="name" value="<?php echo scrub_out($libitem->name); ?>" autofocus /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Type'); ?></td>
                <td>
                    <?php $name    = 'select_' . $libitem->type; ?>
                    <?php ${$name} = ' selected="selected"'; ?>
                    <select name="pl_type">
                        <option value="public"<?php echo $select_public ?? ''; ?>><?php echo T_('Public'); ?></option>
                        <option value="private"<?php echo $select_private ?? ''; ?>><?php echo T_('Private'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    <?php echo T_('Owner'); ?><br />
                </td>
                <td>
                    <?php
                    $options = [];
if (!empty($users)) {
    foreach ($users as $user_id => $username) {
        $selected  = ($user_id == $libitem->user) ? ' selected="selected"' : '';
        $options[] = '<option value="' . $user_id . '"' . $selected . '>' . scrub_out($username) . '</option>';
    }
    echo '<select name="pl_user">' . implode("\n", $options) . '</select>';
} ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?php echo T_('Collaborate'); ?>:<br />
                </td>
                <td>
<?php $ids = explode(',', $libitem->collaborate);
$options   = array();
$users     = User::getValidArray();
if (!empty($users)) {
    foreach ($users as $user_id => $user_name) {
        $selected  = in_array($user_id, $ids) ? ' selected="selected"' : '';
        $options[] = '<option value="' . $user_id . '"' . $selected . '>' . scrub_out($user_name) . '</option>';
    }
    echo '<select multiple size="5" name="collaborate[]" style="height: 90px;">' . implode("\n", $options) . '</select>';
} ?>
                </td>
            </tr>
        </table>
        <input type="hidden" name="id" value="<?php echo $libitem->id; ?>" />
        <input type="hidden" name="type" value="playlist_row" />
    </form>
</div>
