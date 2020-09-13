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
<?php UI::show_box_top(T_('Edit Existing Shoutbox Post')); ?>
<form method="post" enctype="multipart/form-data" action="<?php echo AmpConfig::get('web_path'); ?>/admin/shout.php?action=edit_shout">
<input type="hidden" name="shout_id" value="<?php echo $shout->id; ?>" />
<table class="tabledata">
<tr>
    <td><strong><?php /* HINT: %1 Client link, %2 Object link */ printf(T_('Created by: %1$s for %2$s'), $client->f_link, $object->f_link); ?></strong>
<tr>
<tr>
    <td><strong><?php echo T_('Comment:'); ?></strong>
</tr>
<tr>
    <td><textarea rows="5" cols="70"  maxlength="2000" name="comment" autofocus><?php echo $shout->text; ?></textarea></td>
</tr>
<tr>
    <td><input type="checkbox" name="sticky" <?php if ($shout->sticky == "1") {
    echo "checked";
} ?>/> <strong><?php echo T_('Stick this comment'); ?></strong></td>
</tr>
<tr>
    <td><input type="submit" value="<?php echo T_('Update'); ?>" /></td>
</tr>
</table>
</form>
<?php UI::show_box_bottom(); ?>
