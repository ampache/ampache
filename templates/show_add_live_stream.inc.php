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
<?php UI::show_box_top(T_('Add Radio Station'), 'box box_add_live_stream'); ?>
<form name="radio" method="post" action="<?php echo AmpConfig::get('web_path'); ?>/radio.php?action=create">
<table class="tabledata" cellspacing="0" cellpadding="0">
<tr>
    <td><?php echo T_('Name'); ?></td>
    <td>
        <input type="text" name="name" value="<?php echo scrub_out($_REQUEST['name']); ?>" />
        <?php Error::display('name'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Homepage'); ?></td>
    <td>
        <input type="text" name="site_url" value="<?php echo scrub_out($_REQUEST['site_url']); ?>" />
        <?php Error::display('site_url'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Stream URL'); ?></td>
    <td>
        <input type="text" name="url" value="<?php echo scrub_out($_REQUEST['url']); ?>" />
        <?php Error::display('url'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Codec'); ?></td>
    <td>
        <input type="text" name="codec" value="<?php echo scrub_out($_REQUEST['codec']); ?>" />
    </td>
</tr>
<tr>
    <td><?php echo T_('Catalog'); ?></td>
    <td>
        <?php echo show_catalog_select('catalog',intval($_REQUEST['catalog'])); ?>
    </td>
</tr>
</table>
<div class="formValidation">
    <?php echo Core::form_register('add_radio'); ?>
    <input class="button" type="submit" value="<?php echo T_('Add'); ?>" />
</div>
</form>
<?php UI::show_box_bottom(); ?>
