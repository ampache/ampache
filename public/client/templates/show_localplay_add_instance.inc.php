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
use Ampache\Module\Util\Ui;

/** @var array<string, array{description: string, type: string}> $fields */

Ui::show_box_top(T_('Add Localplay Instance'), 'box box_localplay_add_instance'); ?>
<form method="post" action="<?php echo AmpConfig::get_web_path('/client'); ?>/localplay.php?action=add_instance">
<table class="tabledata">
<?php foreach ($fields as $key => $field) { ?>
<tr>
    <td><?php echo $field['description']; ?></td>
    <td><input type="<?php echo $field["type"]; ?>" name="<?php echo $key; ?>" /></td>
</tr>
<?php } ?>
</table>
    <div class="formValidation">
        <input type="submit" value="<?php echo T_('Add Instance'); ?>" />
  </div>
</form>
<?php Ui::show_box_bottom(); ?>