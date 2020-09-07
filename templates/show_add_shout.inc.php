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
 */

$object_type = strtolower(get_class($object)); ?>
<div>
<?php if (Access::check('interface', 25)) { ?>
<div style="float: right">
<?php
$boxtitle = T_('Post to Shoutbox');
    if ($data) {
        $boxtitle .= ' (' . $data . ')';
    }
    UI::show_box_top($boxtitle, 'box box_add_shout'); ?>
<form method="post" enctype="multipart/form-data" action="<?php echo AmpConfig::get('web_path'); ?>/shout.php?action=add_shout">
<table id="shoutbox-input">
<tr>
    <td><strong><?php echo T_('Comment:'); ?></strong>
</tr>
<tr>
    <td><textarea rows="5" cols="35" maxlength="2000" name="comment"></textarea></td>
</tr>
<?php if (Access::check('interface', 50)) { ?>
<tr>
    <td><input type="checkbox" name="sticky" /> <strong><?php echo T_('Stick this comment'); ?></strong></td>
</tr>
<?php
    } ?>
<tr>
    <td>
        <?php echo Core::form_register('add_shout'); ?>
        <input type="hidden" name="object_id" value="<?php echo $object->id; ?>" />
        <input type="hidden" name="object_type" value="<?php echo $object_type; ?>" />
        <input type="hidden" name="data" value="<?php echo $data; ?>" />
        <input type="submit" value="<?php echo T_('Create'); ?>" /></td>
</tr>
</table>
</form>
<?php UI::show_box_bottom(); ?>
</div>
<?php
} ?>
<div style="display: inline;">
<?php
$boxtitle = $object->f_title . ' ' . T_('Shoutbox');
UI::show_box_top($boxtitle, 'box box_add_shout'); ?>
<?php
$shouts = Shoutbox::get_shouts($object_type, $object->id);
if (count($shouts)) {
    require_once AmpConfig::get('prefix') . UI::find_template('show_shoutbox.inc.php');
} ?>
<?php UI::show_box_bottom(); ?>
</div>
</div>
