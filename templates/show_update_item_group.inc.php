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
UI::show_box_top(T_('Starting Update from Tags'), 'box box_update_items');

// update from high to low so you return to the first disk in a group album
rsort($objects);
foreach ($objects as $object) {
    $return_id = Catalog::update_single_item($type, $object);
}

//The target URL has changed so it needs to be updated
if ($object_id != $return_id) {
    $object_id  = $return_id;
    $target_url = AmpConfig::get('web_path') . '/' . $type . 's.php?action=show&amp;' . $type . '=' . $object_id;
}

//gather art for this item
$art = new Art($object_id, $type);
if (!$art->has_db_info() && !AmpConfig::get('art_order') == 'db') {
    if (is_array($catalog_id) && $catalog_id[0] != '') {
        Catalog::gather_art_item($type, $object_id);
    }
}
 ?>
<br />
<strong><?php echo T_('Update from tags complete'); ?></strong>&nbsp;&nbsp;
<a class="button" href="<?php echo $target_url; ?>"><?php echo T_('Continue'); ?></a>
<?php UI::show_box_bottom(); ?>
