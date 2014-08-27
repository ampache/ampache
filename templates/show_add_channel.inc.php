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
<?php UI::show_box_top(T_('Create Channel'), 'box box_add_channel'); ?>
<form name="share" method="post" action="<?php echo AmpConfig::get('web_path'); ?>/channel.php?action=create">
<input type="hidden" name="type" value="<?php echo scrub_out($_REQUEST['type']); ?>" />
<input type="hidden" name="id" value="<?php echo scrub_out($_REQUEST['id']); ?>" />
<table class="tabledata" cellspacing="0" cellpadding="0">
<tr>
    <td><?php echo T_('Stream Source'); ?></td>
    <td>
        <?php echo $object->f_link; ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Name'); ?></td>
    <td>
        <input type="text" name="name" value="<?php echo scrub_out($_REQUEST['secret']); ?>" />
        <?php Error::display('name'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Description'); ?></td>
    <td>
        <input type="text" name="description" value="<?php echo scrub_out($_REQUEST['description']); ?>" />
        <?php Error::display('description'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('URL'); ?></td>
    <td>
        <input type="text" name="url" value="<?php echo scrub_out($_REQUEST['url'] ?: AmpConfig::get('web_path')); ?>" />
        <?php Error::display('url'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Interface'); ?></td>
    <td>
        <input type="text" name="interface" value="<?php echo scrub_out($_REQUEST['interface'] ?: '127.0.0.1'); ?>" />
        <?php Error::display('interface'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Port'); ?></td>
    <td>
        <input type="text" name="port" value="<?php echo scrub_out($_REQUEST['port'] ?: Channel::get_next_port()); ?>" />
        <?php Error::display('port'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Authentication Required'); ?></td>
    <td>
        <input type="checkbox" name="private" value="1" <?php echo ($_REQUEST['private']) ? 'checked' : ''; ?> />
    </td>
</tr>
<tr>
    <td><?php echo T_('Random'); ?></td>
    <td>
        <input type="checkbox" name="random" value="1" <?php echo ($_REQUEST['random']) ? 'checked' : ''; ?> />
    </td>
</tr>
<tr>
    <td><?php echo T_('Loop'); ?></td>
    <td>
        <input type="checkbox" name="loop" value="1" <?php echo ($_REQUEST['loop'] || $_SERVER['REQUEST_METHOD'] === 'GET') ? 'checked' : ''; ?> />
    </td>
</tr>
<tr>
    <td><?php echo T_('Max Listeners'); ?></td>
    <td>
        <input type="text" name="max_listeners" value="<?php echo scrub_out($_REQUEST['max_listeners'] ?: '32'); ?>" />
        <?php Error::display('max_listeners'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Stream Type'); ?></td>
    <td>
        <input type="text" name="stream_type" value="<?php echo scrub_out($_REQUEST['stream_type'] ?: 'mp3'); ?>" />
        <?php Error::display('stream_type'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Bitrate'); ?></td>
    <td>
        <input type="text" name="bitrate" value="<?php echo scrub_out($_REQUEST['bitrate'] ?: '128'); ?>" />
        <?php Error::display('bitrate'); ?>
    </td>
</tr>
</table>
<div class="formValidation">
    <?php echo Core::form_register('add_channel'); ?>
    <input class="button" type="submit" value="<?php echo T_('Create'); ?>" />
</div>
</form>
<?php UI::show_box_bottom(); ?>
