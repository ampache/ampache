<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

$web_path = AmpConfig::get('web_path');

$icon = $libitem->enabled ? 'disable' : 'enable';
$button_flip_state_id = 'button_flip_state_' . $libitem->id;
?>
<td class="cel_catalog"><?php echo $libitem->f_name_link; ?></td>
<td class="cel_info"><?php echo scrub_out($libitem->f_info); ?></td>
<td class="cel_lastverify"><?php echo scrub_out($libitem->f_update); ?></td>
<td class="cel_lastadd"><?php echo scrub_out($libitem->f_add); ?></td>
<td class="cel_lastclean"><?php echo scrub_out($libitem->f_clean); ?></td>
<td class="cel_action cel_action_text">
<form>
    <select name="catalog_action_menu">
        <option value="add_to_catalog"><?php echo T_('Add'); ?></option>
        <option value="update_catalog"><?php echo T_('Verify'); ?></option>
        <option value="clean_catalog"><?php echo T_('Clean'); ?></option>
        <option value="full_service"><?php echo T_('Update'); ?></option>
        <option value="gather_media_art"><?php echo T_('Gather Art'); ?></option>
        <option value="show_delete_catalog"><?php echo T_('Delete'); ?></option>
    </select>
    <input type="button" onClick="NavigateTo('<?php echo $web_path; ?>/admin/catalog.php?action=' + this.form.catalog_action_menu.options[this.form.catalog_action_menu.selectedIndex].value + '&catalogs[]=<?php echo $libitem->id; ?>');" value="<?php echo T_('Go'); ?>">
    <?php if (AmpConfig::get('catalog_disable')) { ?>
        <span id="<?php echo($button_flip_state_id); ?>">
            <?php echo Ajax::button('?page=catalog&action=flip_state&catalog_id=' . $libitem->id, $icon, T_(ucfirst($icon)),'flip_state_' . $libitem->id); ?>
        </span>
    <?php } ?>
    </form>
</td>
