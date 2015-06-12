<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
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
?>
<?php
if (Art::is_enabled()) {
    $name = scrub_out($libitem->f_name);
?>
<td class="cel_cover">
    <?php
    Art::display('label', $libitem->id, $name, 1, AmpConfig::get('web_path') . '/labels.php?action=show&label=' . $libitem->id);
    ?>
</td>
<?php } ?>
<td class="cel_label"><?php echo $libitem->f_link; ?></td>
<td class="cel_category"><?php echo $libitem->category; ?></td>
<td class="cel_artists"><?php echo $libitem->artists; ?></td>
<td class="cel_action">
<?php if (Access::check('interface','25')) { ?>
    <?php if (AmpConfig::get('sociable')) { ?>
    <a href="<?php echo AmpConfig::get('web_path'); ?>/shout.php?action=show_add_shout&type=label&amp;id=<?php echo $libitem->id; ?>">
        <?php echo UI::get_icon('comment', T_('Post Shout')); ?>
    </a>
    <?php } ?>
<?php } ?>
<?php if ($libitem->can_edit()) { ?>
    <a id="<?php echo 'edit_label_'.$libitem->id ?>" onclick="showEditDialog('label_row', '<?php echo $libitem->id ?>', '<?php echo 'edit_label_'.$libitem->id ?>', '<?php echo T_('Label edit') ?>', 'label_')">
        <?php echo UI::get_icon('edit', T_('Edit')); ?>
    </a>
<?php } ?>
<?php if (Catalog::can_remove($libitem)) { ?>
    <a id="<?php echo 'delete_label_'.$libitem->id ?>" href="<?php echo AmpConfig::get('web_path'); ?>/labels.php?action=delete&label_id=<?php echo $libitem->id; ?>">
        <?php echo UI::get_icon('delete', T_('Delete')); ?>
    </a>
<?php } ?>
</td>
