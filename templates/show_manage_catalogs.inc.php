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
 */ ?>
<?php UI::show_box_top(T_('Show Catalogs'), 'box box_manage_catalogs') ?>
<div id="information_actions">
    <ul style="float: left;">
        <li>
            <a class="option-list" href="<?php echo AmpConfig::get('web_path'); ?>/admin/catalog.php?action=gather_media_art"><?php echo T_('Gather All Art'); ?></a>
        </li>
        <li>
            <a class="option-list" href="<?php echo AmpConfig::get('web_path'); ?>/admin/catalog.php?action=update__all_file_tags"><?php echo T_('Update All File Tags'); ?></a>
        </li>
        <li>
            <a class="option-list" href="<?php echo AmpConfig::get('web_path'); ?>/admin/catalog.php?action=show_disabled"><?php echo T_('Show Disabled Songs'); ?></a>
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
        <table class="tabledata2">
            <tr>
                <td><?php /* HINT: /data/myNewMusic */ ?><?php printf(T_('Add new files from: %s'), '<span class="information">/data/myNewMusic</span>'); ?></td>
                <td><input type="text" name="add_path" value="/" /></td>
            </tr>
            <tr>
                <td><?php /* HINT: /data/myUpdatedMusic */ ?><?php printf(T_('Update existing files in: %s'), '<span class="information">/data/myUpdatedMusic</span>'); ?></td>
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
    $browse      = new Browse();
    $browse->set_type('catalog');
    $browse->set_static_content(true);
    $browse->save_objects($catalog_ids);
    $browse->show_objects($catalog_ids);
    $browse->store(); ?>