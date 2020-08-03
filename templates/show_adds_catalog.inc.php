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

UI::show_box_top(T_('Starting New Media Search'), 'box box_adds_catalog');
/* HINT: Catalog Name */
printf(T_('Starting New Media Search on "%s" Catalog'), "<strong>[ $this->name ]</strong>");
echo "<br />\n";
echo T_('Found') . ': <span id="add_count_' . $this->id . '">' . T_('None') . '</span><br />';
echo T_('Reading') . ':<span id="add_dir_' . $this->id . '"></span><br />';
UI::show_box_bottom();
