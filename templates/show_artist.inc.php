<?php
/*

 Copyright (c) Ampache.org
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
require Config::get('prefix') . '/templates/show_artist_box.inc.php';
?>
<?php
	Browse::reset_filters(); 
	Browse::set_type('album'); 
	Browse::set_static_content(1); 
	Browse::save_objects($albums); 
	$taglist = Tag::get_many_tags('album', $object_ids);
	Browse::show_objects(); 
?>
