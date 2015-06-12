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

$thcount = 6;
?>
<?php if (Access::check('interface','50') || AmpConfig::get('upload_allow_edit')) { ?>
<div id="information_actions">
    <ul>
        <li><?php echo UI::get_icon('add', T_('Add')); ?> <a href="<?php echo AmpConfig::get('web_path'); ?>/labels.php?action=show_add_label"><?php echo T_('Create a new label'); ?></a></li>
    </ul>
</div>
<?php } ?>
<?php if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php'; ?>
<table class="tabledata" cellpadding="0" cellspacing="0" data-objecttype="label">
    <thead>
        <tr class="th-top">
            <?php if (Art::is_enabled()) { ++$thcount; ?>
                <th class="cel_cover optional"><?php echo T_('Art'); ?></th>
            <?php } ?>
            <th class="cel_label essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=label&sort=name', T_('Label'),'label_sort_name'); ?></th>
            <th class="cel_category essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=label&sort=category', T_('Category'),'label_sort_category'); ?></th>
            <th class="cel_artists optional"><?php echo T_('Artists');  ?></th>
            <th class="cel_action essential"><?php echo T_('Action'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        /* Foreach through every label that has been passed to us */
        foreach ($object_ids as $label_id) {
            $libitem = new Label($label_id);
            $libitem->format();
        ?>
        <tr id="label_<?php echo $libitem->id; ?>" class="<?php echo UI::flip_class(); ?>">
            <?php require AmpConfig::get('prefix') . '/templates/show_label_row.inc.php'; ?>
        </tr>
        <?php } ?>
        <?php if (!count($object_ids)) { ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="<?php echo $thcount; ?>"><span class="nodata"><?php echo T_('No label found'); ?></span></td>
        </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <?php if (Art::is_enabled()) { ?>
                <th class="cel_cover"><?php echo T_('Art'); ?></th>
            <?php } ?>
            <th class="cel_label essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=label&sort=name', T_('Label'),'label_sort_name'); ?></th>
            <th class="cel_category essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=label&sort=category', T_('Category'),'label_sort_category'); ?></th>
            <th class="cel_artists optional"><?php echo T_('Artists');  ?></th>
            <th class="cel_action essential"> <?php echo T_('Action'); ?> </th>
        </tr>
    </tfoot>
</table>

<?php show_table_render(); ?>
<?php if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php'; ?>
