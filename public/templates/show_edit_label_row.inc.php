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
/** @var Label $libitem */

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\Label;
use Ampache\Module\Authorization\Access;

?>
<div>
    <form method="post" id="edit_label_<?php echo $libitem->getId(); ?>" class="edit_dialog_content">
        <table class="tabledata">
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Name') ?></td>
                <td><input type="text" name="name" value="<?php echo scrub_out($libitem->getName()); ?>" autofocus /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('MusicBrainz ID') ?></td>
                <td>
                    <?php if (Access::check('interface', AccessLevelEnum::LEVEL_CONTENT_MANAGER)) { ?>
                        <input type="text" name="mbid" value="<?php echo $libitem->getMusicBrainzId(); ?>" />
                        <?php
                    } else {
                        echo $libitem->getMusicBrainzId();
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Category') ?></td>
                <td>
                    <select name="category">
                        <option value="personal" <?php if (empty($libitem->getCategory()) || $libitem->getCategory() === "personal") {
                        echo "selected";
                    } ?>><?php echo T_('Personal'); ?></option>
                        <option value="association" <?php if ($libitem->getCategory() === "association") {
                        echo "selected";
                    } ?>><?php echo T_('Association'); ?></option>
                        <option value="company" <?php if ($libitem->getCategory() === "company") {
                        echo "selected";
                    } ?>><?php echo T_('Company'); ?></option>
                        <option value="imprint" <?php if ($libitem->getCategory() === "imprint") {
                        echo "selected";
                    } ?>><?php echo T_('Imprint'); ?></option>
                        <option value="production" <?php if ($libitem->getCategory() === "production") {
                        echo "selected";
                    } ?>><?php echo T_('Production'); ?></option>
                        <option value="original production" <?php if ($libitem->getCategory() === "original production") {
                        echo "selected";
                    } ?>><?php echo T_('Original Production'); ?></option>
                        <option value="bootleg production" <?php if ($libitem->getCategory() === "bootleg production") {
                        echo "selected";
                    } ?>><?php echo T_('Bootleg Production'); ?></option>
                        <option value="reissue production" <?php if ($libitem->getCategory() === "reissue production") {
                        echo "selected";
                    } ?>><?php echo T_('Reissue Production'); ?></option>
                        <option value="distributor" <?php if ($libitem->getCategory() === "distributor") {
                        echo "selected";
                    } ?>><?php echo T_('Distributor'); ?></option>
                        <option value="holding" <?php if ($libitem->getCategory() === "holding") {
                        echo "selected";
                    } ?>><?php echo T_('Holding'); ?></option>
                        <option value="rights society" <?php if ($libitem->getCategory() === "rights society") {
                        echo "selected";
                    } ?>><?php echo T_('Rights Society'); ?></option>
                        <option value="tag_generated" <?php if ($libitem->getCategory() === "tag_generated") {
                        echo "selected";
                    } ?>><?php echo T_('Tag Generated'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Summary') ?></td>
                <td><textarea name="summary" cols="44" rows="4"><?php echo scrub_out($libitem->getSummary()); ?></textarea></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Address') ?></td>
                <td><input type="text" name="address" value="<?php echo scrub_out($libitem->getAddress()); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Country') ?></td>
                <td><input type="text" name="country" value="<?php echo scrub_out($libitem->getCountry()); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('E-mail') ?></td>
                <td><input type="text" name="email" value="<?php echo scrub_out($libitem->getEmail()); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Website') ?></td>
                <td><input type="text" name="website" value="<?php echo scrub_out($libitem->getWebsite()); ?>" /></td>
            </tr>
            <tr>
                <td><?php echo  T_('Status'); ?></td>
                <td>
                    <select name="active">
                        <option value="1" <?php if ($libitem->getActive() === 1) {
                        echo "selected";
                    } ?>><?php echo T_('Active'); ?></option>
                        <option value="0" <?php if ($libitem->getActive() === 0) {
                        echo "selected";
                    } ?>><?php echo T_('Inactive'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <input type="hidden" name="id" value="<?php echo $libitem->getId(); ?>" />
        <input type="hidden" name="type" value="label_row" />
    </form>
</div>
