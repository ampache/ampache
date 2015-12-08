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
?>
<div>
<?php
$objects = Stats::get_top('album');
$headers = array('f_link' => T_('Most Popular Albums'));
UI::show_box_top('','info-box box_popular_albums');
require AmpConfig::get('prefix') . UI::find_template('show_objects.inc.php');
UI::show_box_bottom();

$objects = Stats::get_top('artist');
$headers = array('f_link' => T_('Most Popular Artists'));
UI::show_box_top('','info-box box_popular_artists');
require AmpConfig::get('prefix') . UI::find_template('show_objects.inc.php');
UI::show_box_bottom();

if (AmpConfig::get('allow_video')) {
    $objects = Stats::get_top('video');
    $headers = array('f_link' => T_('Most Popular Videos'));
    UI::show_box_top('','info-box box_popular_videos');
    require AmpConfig::get('prefix') . UI::find_template('show_objects.inc.php');
    UI::show_box_bottom();
}

?>
</div>
