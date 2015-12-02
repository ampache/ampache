<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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

/*
 * Variable/Non-DB object display takes headers & objects
 */

?>
<table class="tabledata" cellspacing="0">
    <tr>
    <?php foreach ($headers as $header) {
    ?>
        <th class="th-top"><?php echo $header;
    ?></th>
    <?php 
} ?>
    </tr>
    <?php foreach ($objects as $object) {
    $object->format();
    ?>
    <tr id="object_row_<?php echo $object->id;
    ?>" class="<?php echo UI::flip_class();
    ?>">
        <?php require AmpConfig::get('prefix') . UI::find_template('show_object_row.inc.php');
    ?>
    </tr>
    <?php 
} ?>
    <?php if (!count($objects)) {
    ?>
    <tr>
        <td colspan="<?php echo count($headers);
    ?>">
            <span class="error"><?php echo T_('Not Enough Data');
    ?></span>
        </td>
    </tr>
    <?php 
} ?>
</table>
