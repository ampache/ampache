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
use Ampache\Module\Api\Ajax;

$web_path   = AmpConfig::get_web_path('/client');
$admin_path = AmpConfig::get_web_path('/admin');
/** @var Ampache\Repository\Model\Catalog $catalog */

if ($catalog->enabled) {
    $icon     = 'unpublished';
    $icontext = T_('Disable');
} else {
    $icon     = 'check_circle';
    $icontext = T_('Enable');
}
$button_flip_state_id = 'button_flip_state_' . $catalog->id; ?>
<td class="cel_catalog"><?php echo $catalog->get_f_link(); ?></td>
<td class="cel_info"><?php echo scrub_out($catalog->f_info); ?></td>
<td class="cel_lastverify"><?php echo scrub_out($catalog->f_update); ?></td>
<td class="cel_lastadd"><?php echo scrub_out($catalog->f_add); ?></td>
<td class="cel_lastclean"><?php echo scrub_out($catalog->f_clean); ?></td>
<td class="cel_action cel_action_text">
<?php if (!$catalog->isReady()) { ?>
    <a href="<?php echo $admin_path; ?>/catalog.php?action=add_to_catalog&catalogs[]=<?php echo $catalog->id; ?>"><b><?php echo T_('Make it ready ..'); ?></b></a><br />
<?php } ?>
<form name="catalog_action_<?php echo $catalog->id; ?>" method="post" action="<?php echo $admin_path; ?>/catalog.php">
    <select name="action">
<?php if ($catalog->isReady()) { ?>
        <option value="add_to_catalog"><?php echo T_('Add'); ?></option>
        <option value="update_catalog"><?php echo T_('Verify'); ?></option>
        <option value="clean_catalog"><?php echo T_('Clean'); ?></option>
        <option value="full_service"><?php echo T_('Update'); ?></option>
        <option value="gather_media_art"><?php echo T_('Gather Art'); ?></option>
        <option value="import_to_catalog"><?php echo T_('Import'); ?></option>
        <option value="update_file_tags"><?php echo T_('Update File Tags'); ?></option>
        <option value="garbage_collect"><?php echo T_('Garbage Collection'); ?></option>

<?php } ?>
        <option value="show_delete_catalog"><?php echo T_('Delete'); ?></option>
    </select>
    <div class="formValidation">
        <input class="button" type="submit" value="<?php echo T_('Go'); ?>" />
        <input type="hidden" name="catalogs[]" value="=<?php echo $catalog->id; ?>" />
    </div>
    <?php if (AmpConfig::get('catalog_disable')) { ?>
        <span id="<?php echo $button_flip_state_id; ?>">
            <?php echo Ajax::button('?page=catalog&action=flip_state&catalog_id=' . $catalog->id, $icon, $icontext, 'flip_state_' . $catalog->id); ?>
        </span>
    <?php } ?>
</form>
</td>
