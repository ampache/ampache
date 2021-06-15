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

use Ampache\Config\AmpConfig;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;

?>
<?php Ui::show_box_top(T_('Add Label'), 'box box_add_label'); ?>
<form name="label" method="post" action="<?php echo AmpConfig::get('web_path'); ?>/labels.php?action=add_label">
<table class="tabledata">
<tr>
    <td><?php echo T_('Name'); ?></td>
    <td><input type="text" name="name" value="<?php echo scrub_out($_REQUEST['name']); ?>" />
        <?php echo AmpError::display('name'); ?>
    </td>
</tr>
    <tr>
        <td><?php echo T_('MusicBrainz ID'); ?></td>
        <td><input type="text" name="mbid" value="<?php echo scrub_out($_REQUEST['mbid']); ?>" />
            <?php echo AmpError::display('mbid'); ?>
        </td>
    </tr>
<tr>
    <td><?php echo T_('Category'); ?></td>
        <td>
            <select name="category">
                <option value="personal" <?php if (empty($_REQUEST['category']) || $_REQUEST['category'] === "personal") {
    echo "selected";
} ?>><?php echo T_('Personal'); ?></option>
                <option value="association" <?php if (Core::get_request('category') === "association") {
    echo "selected";
} ?>><?php echo T_('Association'); ?></option>
                <option value="company" <?php if (Core::get_request('category') === "company") {
    echo "selected";
} ?>><?php echo T_('Company'); ?></option>
                <option value="imprint" <?php if (Core::get_request('category') === "imprint") {
    echo "selected";
} ?>><?php echo T_('Imprint'); ?></option>
                <option value="production" <?php if (Core::get_request('category') === "production") {
    echo "selected";
} ?>><?php echo T_('Production'); ?></option>
                <option value="original production" <?php if (Core::get_request('category') === "original production") {
    echo "selected";
} ?>><?php echo T_('Original Production'); ?></option>
                <option value="bootleg production" <?php if (Core::get_request('category') === "bootleg production") {
    echo "selected";
} ?>><?php echo T_('Bootleg Production'); ?></option>
                <option value="reissue production" <?php if (Core::get_request('category') === "reissue production") {
    echo "selected";
} ?>><?php echo T_('Reissue Production'); ?></option>
                <option value="distributor" <?php if (Core::get_request('category') === "distributor") {
    echo "selected";
} ?>><?php echo T_('Distributor'); ?></option>
                <option value="holding" <?php if (Core::get_request('category') === "holding") {
    echo "selected";
} ?>><?php echo T_('Holding'); ?></option>
                <option value="rights society" <?php if (Core::get_request('category') === "rights society") {
    echo "selected";
} ?>><?php echo T_('Rights Society'); ?></option>
                <option value="tag_generated" <?php if (Core::get_request('category') === "tag_generated") {
    echo "selected";
} ?>><?php echo T_('Tag Generated'); ?></option>
            </select>
        </td>
</tr>
<tr>
    <td><?php echo T_('Summary'); ?></td>
    <td>
        <textarea name="summary" cols="44" rows="4"><?php echo scrub_out($_REQUEST['summary']); ?></textarea>
        <?php echo AmpError::display('summary'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Address'); ?></td>
    <td><input type="text" name="address" value="<?php echo scrub_out($_REQUEST['address']); ?>" />
        <?php echo AmpError::display('address'); ?>
    </td>
</tr>
    <tr>
        <td><?php echo T_('Country'); ?></td>
        <td><input type="text" name="country" value="<?php echo scrub_out($_REQUEST['country']); ?>" />
            <?php echo AmpError::display('country'); ?>
        </td>
    </tr>
<tr>
    <td><?php echo T_('E-mail'); ?></td>
    <td><input type="text" name="email" value="<?php echo scrub_out($_REQUEST['email']); ?>" />
        <?php echo AmpError::display('email'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Website'); ?></td>
    <td><input type="text" name="website" value="<?php echo scrub_out($_REQUEST['website']); ?>" />
        <?php echo AmpError::display('website'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Status'); ?></td>
    <td>
        <select name="active">
            <option value="1" <?php if ((int)$_REQUEST['active'] === 1) {
    echo "selected";
} ?>><?php echo T_('Active'); ?></option>
            <option value="0" <?php if (empty($_REQUEST['active']) || (int)$_REQUEST['active'] === 0) {
    echo "selected";
} ?>><?php echo T_('Inactive'); ?></option>
        </select>
    </td>
</tr>
</table>
<div class="formValidation">
    <?php echo Core::form_register('add_label'); ?>
    <input class="button" type="submit" value="<?php echo T_('Add'); ?>" />
</div>
</form>
<?php Ui::show_box_bottom(); ?>
