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

// show_edit_collection_row.inc.php

use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\User;

/** @var Collection $libitem */

$current_type = (string) ($libitem->object_type ?? ''); ?>
<div>
    <form method="post" id="edit_collection_<?php echo $libitem->getId(); ?>" class="edit_dialog_content">
        <table class="tabledata">
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Name'); ?></td>
                <td><input type="text" name="name" value="<?php echo scrub_out($libitem->name); ?>" autofocus /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Type'); ?></td>
                <td>
                    <select name="collection_type">
                        <option value="public"<?php echo ($libitem->type === 'public') ? ' selected="selected"' : ''; ?>><?php echo T_('Public'); ?></option>
                        <option value="private"<?php echo ($libitem->type !== 'public') ? ' selected="selected"' : ''; ?>><?php echo T_('Private'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Holds'); ?></td>
                <td>
<?php // Only types the current contents allow are offered, so a refused re-pin cannot be chosen in the first
// place; `Collection::update()` still checks, because the API shares the same rule.
$options = ['<option value=""' . (($current_type === '') ? ' selected="selected"' : '') . '>' . T_('Mixed') . '</option>'];
foreach (Collection::VALID_TYPES as $objectType) {
    if ($objectType !== $current_type && $libitem->conflictingType($objectType) !== null) {
        continue;
    }

    $selected  = ($objectType === $current_type) ? ' selected="selected"' : '';
    $options[] = '<option value="' . $objectType . '"' . $selected . '>' . scrub_out($objectType) . '</option>';
}

$disabled = (count($options) === 1) ? ' disabled="disabled"' : '';

echo '<select name="object_type"' . $disabled . '>' . implode("\n", $options) . '</select>'; ?>
                </td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Collaborate'); ?></td>
                <td>
<?php $ids = explode(',', (string) $libitem->collaborate);
$options   = [];
$users     = User::getValidArray();
foreach ($users as $user_id => $user_name) {
    $selected  = (in_array((string) $user_id, $ids)) ? ' selected="selected"' : '';
    $options[] = '<option value="' . $user_id . '"' . $selected . '>' . scrub_out($user_name) . '</option>';
}

if ($options !== []) {
    echo '<select multiple size="5" name="collaborate[]" style="height: 90px;">' . implode("\n", $options) . '</select>';
} ?>
                </td>
            </tr>
        </table>
        <input type="hidden" name="id" value="<?php echo $libitem->getId(); ?>" />
        <input type="hidden" name="type" value="collection_row" />
    </form>
</div>
