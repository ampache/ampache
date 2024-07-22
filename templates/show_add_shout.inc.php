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
use Ampache\Module\Authorization\Access;
use Ampache\Module\Shout\ShoutRendererInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Shoutbox;

/** @var string $data */
/** @var Ampache\Repository\Model\library_item $object */
/** @var string $object_type */
/** @var Traversable<Shoutbox> $shouts */
/** @var ShoutRendererInterface $shoutRenderer */
?>
<div>
<?php if (Access::check('interface', 25)) { ?>
<div style="float: right">
<?php
$boxtitle = T_('Post to Shoutbox');
    if (!empty($data)) {
        $boxtitle .= ' (' . $data . ')';
    }
    Ui::show_box_top($boxtitle, 'box box_add_shout'); ?>
<form method="post" enctype="multipart/form-data" action="<?php echo AmpConfig::get('web_path'); ?>/shout.php?action=add_shout">
<table id="shoutbox-input">
<tr>
    <td><strong><?php echo T_('Comment'); ?></strong>
</tr>
<tr>
    <td><textarea rows="5" cols="35" maxlength="2000" name="comment"></textarea></td>
</tr>
<?php if (Access::check('interface', 50)) { ?>
<tr>
    <td><input type="checkbox" name="sticky" /> <strong><?php echo T_('Stick this comment'); ?></strong></td>
</tr>
<?php } ?>
<tr>
    <td>
        <?php echo Core::form_register('add_shout'); ?>
        <input type="hidden" name="object_id" value="<?php echo $object->getId(); ?>" />
        <input type="hidden" name="object_type" value="<?php echo $object_type; ?>" />
        <input type="hidden" name="data" value="<?php echo $data; ?>" />
        <input type="submit" value="<?php echo T_('Create'); ?>" /></td>
</tr>
</table>
</form>
<?php Ui::show_box_bottom(); ?>
</div>
<?php } ?>
<div style="display: inline;">
<?php
$boxtitle = $object->get_fullname() . ' ' . T_('Shoutbox');
Ui::show_box_top($boxtitle, 'box box_add_shout'); ?>
<?php
$shouts = iterator_to_array($shouts);

if ($shouts !== []) {
    require_once Ui::find_template('show_shoutbox.inc.php');
} ?>
<?php Ui::show_box_bottom(); ?>
</div>
</div>
