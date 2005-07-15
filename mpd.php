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
	@header MPD control
	@discussion Ampache MPD control center

*/
require_once("modules/init.php");
show_template('header');
if (conf('refresh_limit') > 0) { show_template('javascript_refresh'); }
show_menu_items('Local Play');
show_clear();
DebugBreak();
if ($user->prefs['play_type'] == 'mpd') {
	show_mpd_control();
	echo "<div align='center'> <table border='0'> <tr>";
	show_mpd_pl();
	echo "</tr> </table> </div>";
}

show_clear();
show_page_footer('Local Play','',$user->prefs['display_menu'] );

?>
