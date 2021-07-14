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
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Label;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Util\Ui;

$thcount = 7;
//mashup and grid view need different css
$cel_cover = ($is_table) ? "cel_cover" : 'grid_cover'; ?>
<?php if (Access::check('interface', 50) || AmpConfig::get('upload_allow_edit')) { ?>
<div id="information_actions">
    <ul>
        <li>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/labels.php?action=show_add_label">
                <?php echo Ui::get_icon('add', T_('Add')); ?>
                <?php echo T_('Create Label'); ?>
            </a>
        </li>
    </ul>
</div>
<?php
} ?>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
<table class="tabledata striped-rows <?php echo $browse->get_css_class() ?>" data-objecttype="label">
    <thead>
        <tr class="th-top">
            <th class="<?php echo $cel_cover; ?> optional"><?php echo T_('Art'); ?></th>
            <th class="cel_label essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=label&sort=name', T_('Label'), 'label_sort_name'); ?></th>
            <th class="cel_category essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=label&sort=category', T_('Category'), 'label_sort_category'); ?></th>
            <th class="cel_artists optional"><?php echo T_('Artists');  ?></th>
            <th class="cel_country optional"><?php echo T_('Country');  ?></th>
            <th class="cel_status optional"><?php echo T_('Status');  ?></th>
            <th class="cel_action essential"><?php echo T_('Action'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        /* Foreach through every label that has been passed to us */
        foreach ($object_ids as $label_id) {
            $libitem = new Label($label_id);
            $libitem->format(); ?>
        <tr id="label_<?php echo $libitem->id; ?>">
            <?php require Ui::find_template('show_label_row.inc.php'); ?>
        </tr>
        <?php
        } ?>
        <?php if (!count($object_ids)) { ?>
        <tr>
            <td colspan="<?php echo $thcount; ?>"><span class="nodata"><?php echo T_('No label found'); ?></span></td>
        </tr>
        <?php
        } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="<?php echo $cel_cover; ?>"><?php echo T_('Art'); ?></th>
            <th class="cel_label essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=label&sort=name', T_('Label'), 'label_sort_name'); ?></th>
            <th class="cel_category essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=label&sort=category', T_('Category'), 'label_sort_category'); ?></th>
            <th class="cel_artists optional"><?php echo T_('Artists');  ?></th>
            <th class="cel_country optional"><?php echo T_('Country');  ?></th>
            <th class="cel_status optional"><?php echo T_('Status');  ?></th>
            <th class="cel_action essential"> <?php echo T_('Action'); ?> </th>
        </tr>
    </tfoot>
</table>

<?php show_table_render(); ?>
<?php if ($browse->is_show_header()) {
            require Ui::find_template('list_header.inc.php');
        } ?>
