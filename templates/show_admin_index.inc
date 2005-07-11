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
	@header Show Admin Index
	@discussion shows the admin index 
*/

$sections 	= array(_("User Management") => conf('web_path') . "/admin/users.php",
			_("E-mail Management") => conf('web_path') . "/admin/mail.php",
			_("Catalog Managment") => conf('web_path') . "/admin/catalog.php",
			_("Admin Preferences") => conf('web_path') . "/admin/preferences.php",
			_("Access Lists") => conf('web_path') . "/admin/access.php");
			
$common		= array(_("Add a catalog") => conf('web_path') . "/admin/catalog.php?action=show_add_catalog",
			_("Add a new user") => conf('web_path') . "/admin/users.php?action=show_add_user",
			_("Clear Now Playing")   => conf('web_path') . "/admin/catalog.php?action=clear_now_playing",
			_("Add Access List Entry") => conf('web_path') . "/admin/access.php?action=show_add_host");
?>
<table cellpadding="5" border="0" cellspacing="0">
<tr>
	<td valign="top"><?php show_tool_box(_("Common Functions"),$common); ?><?php show_clear(); ?></td>
</tr>
<tr>
	<td valign="top"><?php show_tool_box(_("Admin Sections"),$sections); ?></td>
</tr>
</table>

