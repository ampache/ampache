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

$web_path = Config::get('web_path');

require Config::get('prefix') . '/templates/show_artist_box.inc.php';
//require Config::get('prefix') . '/templates/show_artist_tagcloud.inc.php';
?>
<?php
	Browse::reset_filters(); 
	Browse::set_type('album'); 
	//Browse::set_filter('artist', $artist->id);
	Browse::set_filter_from_request($_REQUEST);
	$objs = Browse::get_objects();
	if (sizeof($objs)) {
	  $tagcloudHead = _('Tags for albums of') . ' ' . $artist->f_name;
	  $taglist = TagCloud::get_tags('album', $objs);
	  $tagcloudList = TagCloud::filter_with_prefs($taglist);
	  require Config::get('prefix') . '/templates/show_tagcloud.inc.php';
	}
	Browse::show_objects(); 
?>
