<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
?>
<?php UI::show_box_top(T_('Create Share'), 'box box_add_share'); ?>
<form name="share" method="post" action="<?php echo AmpConfig::get('web_path'); ?>/share.php?action=create">
<input type="hidden" name="type" value="<?php echo scrub_out($_REQUEST['type']); ?>" />
<input type="hidden" name="id" value="<?php echo scrub_out($_REQUEST['id']); ?>" />
<table class="tabledata" cellspacing="0" cellpadding="0">
<tr>
    <td><?php echo T_('Shared Object'); ?></td>
    <td>
        <?php echo $object->f_link; ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Secret'); ?></td>
    <td>
        <input type="text" name="secret" value="<?php echo scrub_out($_REQUEST['secret'] ?: Share::generate_secret()); ?>" />
        <?php AmpError::display('secret'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Max Counter'); ?></td>
    <td>
        <input type="text" name="max_counter" value="<?php echo scrub_out($_REQUEST['max_counter'] ?: '0'); ?>" />
        <?php AmpError::display('max_counter'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Expiry Days'); ?></td>
    <td>
        <input type="text" name="expire" value="<?php echo scrub_out($_REQUEST['expire'] ?: AmpConfig::get('share_expire')); ?>" />
    </td>
</tr>
<tr>
    <td><?php echo T_('Allow Stream'); ?></td>
    <td>
        <input type="checkbox" name="allow_stream" value="1" <?php echo ($_REQUEST['allow_stream'] || $_SERVER['REQUEST_METHOD'] === 'GET') ? 'checked' : ''; ?> />
    </td>
</tr>
<?php if ((($_REQUEST['type'] == 'song' || $_REQUEST['type'] == 'video') && Access::check_function('download')) || Access::check_function('batch_download')) {
    ?>
<tr>
    <td><?php echo T_('Allow Download'); ?></td>
    <td>
        <input type="checkbox" name="allow_download" value="1" <?php echo ($_REQUEST['allow_download'] || $_SERVER['REQUEST_METHOD'] === 'GET') ? 'checked' : ''; ?> />
    </td>
</tr>
<?php
} ?>
</table>
<div class="formValidation">
    <?php echo Core::form_register('add_share'); ?>
    <input class="button" type="submit" value="<?php echo T_('Create'); ?>" />
</div>
</form>
<?php UI::show_box_bottom(); ?>
