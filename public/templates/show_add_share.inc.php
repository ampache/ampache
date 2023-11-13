<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
 */

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;

/** @var bool $has_failed */
/** @var string $message */
/** @var Ampache\Repository\Model\Song|Ampache\Repository\Model\Album|Ampache\Repository\Model\AlbumDisk|Ampache\Repository\Model\Playlist|Ampache\Repository\Model\Video $object */
/** @var string $token */

$allow_stream   = $_REQUEST['allow_stream'] ?? false;
$allow_download = $_REQUEST['allow_download'] ?? false;

Ui::show_box_top(T_('Create Share'), 'box box_add_share'); ?>
<form name="share" method="post" action="<?php echo AmpConfig::get('web_path'); ?>/share.php?action=create">
<input type="hidden" name="type" value="<?php echo scrub_out(Core::get_request('type')); ?>" />
<input type="hidden" name="id" value="<?php echo scrub_out(Core::get_request('id')); ?>" />
<table class="tabledata">
<tr>
    <td><?php echo T_('Share'); ?></td>
    <td><?php echo $object->get_f_link() ?? ''; ?></td>
</tr>
<tr>
    <td><?php echo T_('Secret'); ?></td>
    <td><input type="text" name="secret" maxlength="20" value="<?php echo scrub_out($_REQUEST['secret'] ?? $token); ?>" />
        <?php echo AmpError::display('secret'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Max Counter'); ?></td>
    <td><input type="text" name="max_counter" value="<?php echo scrub_out($_REQUEST['max_counter'] ?? '0'); ?>" />
        <?php echo AmpError::display('max_counter'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Expiry Days'); ?></td>
    <td><input type="text" name="expire" value="<?php echo scrub_out($_REQUEST['expire'] ?? AmpConfig::get('share_expire', 7)); ?>" /></td>
</tr>
<tr>
    <td><?php echo T_('Allow Stream'); ?></td>
    <td><input type="checkbox" name="allow_stream" value="1" <?php echo ($allow_stream || Core::get_server('REQUEST_METHOD') === 'GET') ? 'checked' : ''; ?> /></td>
</tr>
<?php if (((Core::get_request('type') == 'song' || Core::get_request('type') == 'video') && (Access::check_function('download')) || Access::check_function('batch_download'))) { ?>
<tr>
    <td><?php echo T_('Allow Download'); ?></td>
    <td><input type="checkbox" name="allow_download" value="1" <?php echo ($allow_stream || Core::get_server('REQUEST_METHOD') === 'GET') ? 'checked' : ''; ?> /></td>
</tr>
<?php } ?>
<?php if ($has_failed !== false) { ?>
<tr>
    <td>&nbsp;</td>
    <td><p class="alert alert-danger"><?php echo $message; ?></p></td>
</tr>
<?php } ?>
</table>
<div class="formValidation">
    <?php echo Core::form_register('add_share'); ?>
    <input class="button" type="submit" value="<?php echo T_('Create'); ?>" />
</div>
</form>
<?php Ui::show_box_bottom(); ?>
