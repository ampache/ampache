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
    <a href="<?php echo $web_path; ?>/admin/catalog.php?action=add_to_catalog&amp;catalogs[]=<?php echo $libitem->id; ?>"><?php echo T_('Add'); ?></a>
    | <a href="<?php echo $web_path; ?>/admin/catalog.php?action=update_catalog&amp;catalogs[]=<?php echo $libitem->id; ?>"><?php echo T_('Verify'); ?></a>
        | <a href="<?php echo $web_path; ?>/admin/catalog.php?action=clean_catalog&amp;catalogs[]=<?php echo $libitem->id; ?>"><?php echo T_('Clean'); ?></a>
    | <a href="<?php echo $web_path; ?>/admin/catalog.php?action=full_service&amp;catalogs[]=<?php echo $libitem->id; ?>"><?php echo T_('Update'); ?></a>
    | <a href="<?php echo $web_path; ?>/admin/catalog.php?action=gather_media_art&amp;catalogs[]=<?php echo $libitem->id; ?>"><?php echo T_('Gather Art'); ?></a>
    | <a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_delete_catalog&amp;catalog_id=<?php echo $libitem->id; ?>"><?php echo T_('Delete'); ?></a>
<?php if (AmpConfig::get('catalog_disable')) { ?>
    | <span id="<?php echo($button_flip_state_id); ?>">
        <?php echo Ajax::button('?page=catalog&action=flip_state&catalog_id=' . $libitem->id, $icon, T_(ucfirst($icon)),'flip_state_' . $libitem->id); ?>
      </span>
<?php } ?>
</td>
