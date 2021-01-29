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
<?php UI::show_box_top(T_('Add Radio Station'), 'box box_add_live_stream'); ?>
<form name="radio" method="post" action="<?php echo AmpConfig::get('web_path'); ?>/radio.php?action=create">
<table class="tabledata">
<tr>
    <td><?php echo T_('Name'); ?></td>
    <td><input type="text" name="name" value="<?php echo scrub_out($_REQUEST['name']); ?>" />
        <?php AmpError::display('name'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Homepage'); ?></td>
    <td><input type="text" name="site_url" value="<?php echo scrub_out($_REQUEST['site_url']); ?>" />
        <?php AmpError::display('site_url'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Stream URL'); ?></td>
    <td><input type="text" name="url" value="<?php echo scrub_out($_REQUEST['url']); ?>" />
        <?php AmpError::display('url'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Codec'); ?></td>
    <td><input type="text" name="codec" value="<?php echo scrub_out($_REQUEST['codec']); ?>" />
        <?php AmpError::display('codec'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Catalog'); ?></td>
    <td><?php echo show_catalog_select('catalog', (int) ($_REQUEST['catalog'])); ?></td>
</tr>
</table>
<div class="formValidation">
    <?php echo Core::form_register('add_radio'); ?>
    <input class="button" type="submit" value="<?php echo T_('Add'); ?>" />
</div>
</form>
<?php UI::show_box_bottom(); ?>
