<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Util\Ui;

/** @var LocalPlay $localplay */
/** @var list<string, string> $instances */
/** @var list<array{description: string, type: string}> $fields */

Ui::show_box_top(T_('Show Localplay Instances'), 'box box_localplay_instances'); ?>
<table class="tabledata striped-rows">
<tr>
    <?php foreach ($fields as $key => $field) { ?>
        <th><?php echo $field['description']; ?></th>
    <?php } ?>
    <th><?php echo T_('Action'); ?></th>
</tr>
<?php foreach ($instances as $uid => $name) {
    $instance = $localplay->get_instance($uid); ?>
<tr id="localplay_instance_<?php echo $uid; ?>">
    <?php foreach ($fields as $key => $field) { ?>
    <td>
        <?php
            if ($field["type"] != "password") {
                echo scrub_out($instance[$key]);
            } else {
                echo "*****";
            } ?>
    </td>
    <?php } ?>
    <td>
        <a href="<?php echo AmpConfig::get('web_path'); ?>/localplay.php?action=edit_instance&instance=<?php echo $uid; ?>"><?php echo Ui::get_material_symbol('edit', T_('Edit Instance')); ?></a>
        <?php echo Ajax::button('?page=localplay&action=delete_instance&instance=' . $uid, 'close', T_('Delete'), 'delete_instance_' . $uid); ?>
    </td>
</tr>
<?php
} ?>
</table>
<?php Ui::show_box_bottom(); ?>
