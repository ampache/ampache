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

/**
 * Browse Menu
 * This menu has all the different ways you can browse your ampache music collection
 * This calls different options of the browse.php file
 * @package Web Interface
 * @catagory Browse
 * @author Karl Vollmer
 */

$web_path = conf('web_path');

$items = array(
	        _("Artist")      => "$web_path/browse.php?action=artist",
		_("Albums")      => "$web_path/browse.php?action=albums",
		_("Genre")       => "$web_path/browse.php?action=genre",
        );

?>
<ul id="adminmenu">

	<?php
	foreach ( array_keys($items) as $item ) {
		if ( $admin_highlight == $item ) {
        		echo "<li class=\"active\"><a class=\"active\" href=\"$items[$item]\">" . $item . "</a></li>\n";
		}
		else {
        		echo "<li><a href=\"$items[$item]\">" . $item . "</a></li>\n";
		}
	}

	?>
</ul>
<br />
