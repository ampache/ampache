<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */
?>
<div>
<?php
$objects = Stats::get_top('album');
$headers = array('f_link' => T_('Most Popular Albums'));
UI::show_box_top('','info-box box_popular_albums');
require AmpConfig::get('prefix') . '/templates/show_objects.inc.php';
UI::show_box_bottom();

$objects = Stats::get_top('artist');
$headers = array('f_name_link' => T_('Most Popular Artists'));
UI::show_box_top('','info-box box_popular_artists');
require AmpConfig::get('prefix') . '/templates/show_objects.inc.php';
UI::show_box_bottom();

if (AmpConfig::get('allow_video')) {
    $objects = Stats::get_top('video');
    $headers = array('f_name_link' => T_('Most Popular Videos'));
    UI::show_box_top('','info-box box_popular_videos');
    require AmpConfig::get('prefix') . '/templates/show_objects.inc.php';
    UI::show_box_bottom();
}

?>
</div>
