<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
use Ampache\Repository\Model\License;

/** @var License $license */

?>
<form method="post" enctype="multipart/form-data" action="<?php echo AmpConfig::get('web_path'); ?>/admin/license.php?action=edit">
<input type="hidden" name="license_id" value="<?php echo $license->getId(); ?>" />
<table class="tabledata">
<tr>
    <td class="edit_dialog_content_header"><?php echo T_('Name'); ?></td>
    <td><input type="text" name="name" value="<?php echo scrub_out($license->getName()); ?>" autofocus /></td>
</tr>
<tr>
    <td><?php echo T_('Description:'); ?></td>
    <td><textarea rows="5" cols="70" maxlength="250" name="description"><?php echo $license->getDescription(); ?></textarea></td>
</tr>
<tr>
    <td class="edit_dialog_content_header"><?php echo T_('External Link'); ?></td>
    <td><input type="text" name="external_link" value="<?php echo $license->getExternalLink(); ?>" /></td>
</tr>
<tr>
    <td><input type="submit" value="<?php echo T_('Confirm'); ?>" /></td>
</tr>
</table>
</form>
