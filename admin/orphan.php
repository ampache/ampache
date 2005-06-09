<?php

/*

 Copyright (c) 2001 - 2005 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/


/*!
	@header Orphaned Admin Page
 View and edit orphan files

*/

require('../modules/init.php');


if (!$user->has_access(100)) { 
  header("Location: " . conf('web_path') . "/index.php?access=denied");
  exit();
}


if ( $type and $action == 'show_songs' ) {
  print("<p style=\"font-size: 12px; font-weight: bold;\"> Orphaned Songs with missing $type information </p>");

  $song_ids = get_orphan_songs($type);
  show_songs($song_ids);
}

show_template('header');

show_menu_items('Admin');
show_admin_menu('Catalog');

if ( $action == 'show_orphan_songs' ) {
	print("<p style=\"font-size: 12px; font-weight: bold;\"> Orphaned songs with no artist </p>");

	$song_ids = get_orphan_songs();
	show_songs($song_ids);
}
elseif ( $action == 'show_orphan_albums' ) {
	print("<p style=\"font-size: 12px; font-weight: bold;\"> Orphaned albums with no name </p>");

	$song_ids = get_orphan_albums();
	show_songs($song_ids);
}

?>


<hr>

</body>
</html>
