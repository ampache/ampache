<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

$web_path = AmpConfig::get('web_path');

if ($libitem->enabled) {
    $icon     = 'disable';
    $icontext = T_('Disable');
} else {
    $icon     = 'enable';
    $icontext = T_('Enable');
}
$button_flip_state_id = 'button_flip_state_' . $libitem->id; ?>
<td class="cel_catalog"><?php echo $libitem->f_link; ?></td>
<td class="cel_info"><?php echo scrub_out($libitem->f_info); ?></td>
<td class="cel_lastverify"><?php echo scrub_out($libitem->f_update); ?></td>
<td class="cel_lastadd"><?php echo scrub_out($libitem->f_add); ?></td>
<td class="cel_lastclean"><?php echo scrub_out($libitem->f_clean); ?></td>
<td class="cel_action cel_action_text">
<?php if (!$libitem->isReady()) { ?>
    <a href="<?php echo $web_path; ?>/admin/catalog.php?action=add_to_catalog&catalogs[]=<?php echo $libitem->id; ?>"><b><?php echo T_('Make it ready ..'); ?></b></a><br />
<?php
} ?>
<form>
    <select name="catalog_action_menu">
<?php if ($libitem->isReady()) { ?>
        <option value="add_to_catalog"><?php echo T_('Add'); ?></option>
        <option value="update_catalog"><?php echo T_('Verify'); ?></option>
        <option value="clean_catalog"><?php echo T_('Clean'); ?></option>
        <option value="full_service"><?php echo T_('Update'); ?></option>
        <option value="gather_media_art"><?php echo T_('Gather Art'); ?></option>
        <option value="update_file_tags"><?php echo T_('Update File Tags'); ?></option>

<?php
    } ?>
        <option value="show_delete_catalog"><?php echo T_('Delete'); ?></option>
    </select>
    <input type="button" onClick="NavigateTo('<?php echo $web_path; ?>/admin/catalog.php?action=' + this.form.catalog_action_menu.options[this.form.catalog_action_menu.selectedIndex].value + '&catalogs[]=<?php echo $libitem->id; ?>');" value="<?php echo T_('Go'); ?>">
    <?php if (AmpConfig::get('catalog_disable')) { ?>
        <span id="<?php echo $button_flip_state_id; ?>">
            <?php echo Ajax::button('?page=catalog&action=flip_state&catalog_id=' . $libitem->id, $icon, $icontext, 'flip_state_' . $libitem->id); ?>
        </span>
    <?php
    } ?>
</form>
</td>