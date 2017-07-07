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
<?php if ($browse->get_show_header()) {
    require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
} ?>
<table class="tabledata <?php echo $browse->get_css_class() ?>" cellpadding="0" cellspacing="0"  data-objecttype="broadcast">
    <thead>
        <tr class="th-top">
            <th class="cel_play essential"></th>
            <th class="cel_name essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=broadcast&sort=name', T_('Name'), 'broadcast_sort_name'); ?></th>
            <th class="cel_genre optional"><?php echo T_('Genre'); ?></th>
            <th class="cel_started optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=broadcast&sort=started', T_('Started'), 'broadcast_sort_started'); ?></th>
            <th class="cel_listeners optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=broadcast&sort=listeners', T_('Listeners'), 'broadcast_sort_listeners'); ?></th>
            <th class="cel_action essential"><?php echo T_('Actions'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($object_ids as $broadcast_id) {
            $libitem = new Broadcast($broadcast_id);
            $libitem->format(); ?>
        <tr class="<?php echo UI::flip_class(); ?>" id="broadcast_row_<?php echo $libitem->id; ?>">
            <?php require AmpConfig::get('prefix') . UI::find_template('show_broadcast_row.inc.php'); ?>
        </tr>
        <?php
        } ?>
        <?php if (!count($object_ids)) {
            ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="6"><span class="nodata"><?php echo T_('No broadcast found'); ?></span></td>
        </tr>
        <?php
        } ?>
    </tbody>
</table>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/tabledata.js" language="javascript" type="text/javascript"></script>
<?php if ($browse->get_show_header()) {
            require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
        } ?>
