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
?>
<?php UI::show_box_top(T_('Show Catalogs'), 'box box_manage_catalogs') ?>
<div id="information_actions">
    <ul style="float: left;">
        <li>
            <a class="option-list" href="<?php echo AmpConfig::get('web_path'); ?>/admin/catalog.php?action=gather_media_art"><?php echo T_('Gather All Art'); ?></a>
        </li>
        <li>
            <a class="option-list" href="<?php echo AmpConfig::get('web_path'); ?>/admin/catalog.php?action=show_disabled"><?php echo T_('Show disabled songs'); ?></a>
        </li>
        <li>
            <a class="option-list" href="<?php echo AmpConfig::get('web_path'); ?>/admin/catalog.php?action=add_to_all_catalogs"><?php echo T_('Add to All'); ?></a>
        </li>
        <li>
            <a class="option-list" href="<?php echo AmpConfig::get('web_path'); ?>/admin/catalog.php?action=update_all_catalogs"><?php echo T_('Verify All'); ?></a>
        </li>
        <li>
            <a class="option-list" href="<?php echo AmpConfig::get('web_path'); ?>/admin/catalog.php?action=clean_all_catalogs"><?php echo T_('Clean All'); ?></a>
        </li>
        <li>
            <a class="option-list" href="<?php echo AmpConfig::get('web_path'); ?>/admin/catalog.php?action=full_service"><?php echo T_('Update All'); ?></a>
        </li>
        <li>
            <a class="option-list" href="<?php echo AmpConfig::get('web_path'); ?>/admin/catalog.php?action=clear_stats"><?php echo T_('Clear Stats'); ?></a>
        </li>
    </ul>
    <form style="padding-left: 250px;" method="post" action="<?php echo AmpConfig::get('web_path'); ?>/admin/catalog.php?action=update_from">
        <table class="tabledata2" cellspacing="0" cellpadding="0">
            <tr>
                <td><?php /* HINT: /data/myNewMusic */ ?><?php printf (T_('Add from [%s]'), '<span class="information">/data/myNewMusic</span>'); ?></td>
                <td><input type="text" name="add_path" value="/" /></td>
            </tr>
            <tr>
                <td><?php /* HINT: /data/myUpdatedMusic */ ?><?php printf (T_('Update from [%s]'), '<span class="information">/data/myUpdatedMusic</span>'); ?></td>
                <td><input type="text" name="update_path" value="/" /></td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:right;"><input type="submit" value="<?php echo T_('Update'); ?>" /></td>
            </tr>
        </table>
    </form>
</div>
<?php
    UI::show_box_bottom();
    $catalog_ids = Catalog::get_catalogs();
    $browse = new Browse();
    $browse->set_type('catalog');
    $browse->set_static_content(true);
    $browse->save_objects($catalog_ids);
    $browse->show_objects($catalog_ids);
    $browse->store();
?>
