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

$thresh_value = AmpConfig::get('stats_threshold');

$sql    = Stats::get_top_sql('album', $thresh_value);
$browse = new Browse();
// We limit threshold for all items otherwise the counter will not be the same that the top_sql query.
// Example: Item '1234' => 3 counts during period with 'get_top_sql'. Without threshold, 'show_objects' would return the total which could be 24 during all time)
$browse->set_threshold($thresh_value);
$browse->set_type('album', $sql);
$browse->set_simple_browse(true);
$browse->show_objects();
$browse->store();

$sql    = Stats::get_top_sql('artist', $thresh_value);
$browse = new Browse();
$browse->set_threshold($thresh_value);
$browse->set_type('artist', $sql);
$browse->set_simple_browse(true);
$browse->show_objects();
$browse->store();

$sql    = Stats::get_top_sql('song', $thresh_value);
$browse = new Browse();
$browse->set_threshold($thresh_value);
$browse->set_type('song', $sql);
$browse->set_simple_browse(true);
$browse->show_objects();
$browse->store();

if (AmpConfig::get('allow_video')) {
    $sql    = Stats::get_top_sql('video');
    $browse = new Browse();
    $browse->set_type('video', $sql);
    $browse->set_simple_browse(true);
    $browse->show_objects(null);
    $browse->store();
}

UI::show_box_bottom();
