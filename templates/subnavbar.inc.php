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

/**
 * This file expect an array of 'items' which have ['0']['url'] ['0']['title']
 * ['0']['active'] == true/false and ['0']['cssclass'] this is called from show_submenu($items);
 */
 ?>
<ul class="subnavside">
<?php
    foreach ($items as $item) {
        $li_class = '';
        if ($item['url'] == $item['active']) {
            $li_class = "class=\"activesubmenu\"";
        }
        $li_id = "id=\"" . $item['cssclass'] . "\""; ?>
        <li <?php echo $li_class;
        echo $li_id; ?>><a href="<?php echo AmpConfig::get('web_path') . "/" . $item['url']; ?>"><?php echo $item['title']; ?></a></li>
    <?php unset($li_id);
    } // END foreach ($items as $item)?>
</ul>
