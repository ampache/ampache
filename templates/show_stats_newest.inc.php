<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
?>
<div id="recent_added">
        <?php
                $objects = Stats::get_newest('album');
                $headers = array('f_link'=>_('Newest Albums'));
                show_box_top('','info-box box_newest_albums');
                require Config::get('prefix') . '/templates/show_objects.inc.php';
                show_box_bottom();
                $objects = Stats::get_newest('artist');
                $headers = array('f_name_link'=>_('Newest Artists'));
                show_box_top('','info-box box_newest_artists');
                require Config::get('prefix') . '/templates/show_objects.inc.php';
                show_box_bottom();
                $objects = Stats::get_newest('genre');
                $headers = array('f_link'=>_('Newest Genres'));
                show_box_top('','info-box box_newest_genres');
                require Config::get('prefix') . '/templates/show_objects.inc.php';
                show_box_bottom();
        ?>
</div>

