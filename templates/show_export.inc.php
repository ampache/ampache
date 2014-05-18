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

$name = 'export_' . $_REQUEST['export_format'];
${$name} = ' selected="selected"';
$name = 'catalog_' . $_REQUEST['export_catalog'];
${$name} = ' selected="selected"';

UI::show_box_top(T_('Export Catalog'), 'box box_export'); ?>
<form name="duplicates" action="<?php echo AmpConfig::get('web_path'); ?>/admin/export.php?action=export" method="post" enctype="multipart/form-data" >
<table class="tableform" cellspacing="0" cellpadding="3">
<tr>
    <td valign="top"><strong><?php echo T_('Catalog'); ?>:</strong></td>
    <td>
        <select id="export_catalog" name="export_catalog">
            <option value=""><?php echo T_('All'); ?></option>
<?php
        $catalog_ids = Catalog::get_catalogs();
        foreach ($catalog_ids as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);
            $current_name = 'catalog_' . $catalog->id;

?>
            <option value="<?php echo $catalog->id; ?>"<?php echo ${$current_name}; ?>><?php echo scrub_out($catalog->name); ?></option>
<?php
        }
?>
        </select>
    </td>
</tr>
<tr>
    <td valign="top"><strong><?php echo T_('Format'); ?>:</strong></td>
    <td>
        <select id="export_format" name="export_format">
            <option value="csv" <?php echo $export_csv; ?>>CSV</option>
            <option value="itunes" <?php echo $export_itunes; ?>>iTunes</option>
        </select>
    </td>
</tr>
</table>
<div class="formValidation">
      <input type="submit" value="<?php echo T_('Export'); ?>" />
</div>
</form>
<?php UI::show_box_bottom(); ?>
